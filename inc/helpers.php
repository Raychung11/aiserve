<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

// ============================================================
// GOLD CALCULATION HELPERS
// ============================================================

/**
 * Calculate gold points from RM amount
 * Formula: points = (rm_amount / price_per_g) * 100
 * Returns 4-decimal DECIMAL string
 */
function gold_points_from_rm(string $rm_amount, string $price_per_g): string {
    if ((float)$price_per_g <= 0) return '0.0000';
    $result = (bcdiv($rm_amount, $price_per_g, 8));
    $result = bcmul($result, '100', GOLD_POINT_DECIMALS);
    return number_format((float)$result, GOLD_POINT_DECIMALS, '.', '');
}

/**
 * Calculate RM value from gold points
 * Formula: rm = (points / 100) * price_per_g
 */
function gold_rm_from_points(string $points, string $price_per_g): string {
    $grams = bcdiv($points, '100', 8);
    return bcmul($grams, $price_per_g, GOLD_RM_DECIMALS);
}

function gold_grams_from_points(string $points): string {
    return bcdiv($points, '100', GOLD_GRAM_DECIMALS);
}

function gold_points_from_grams(string $grams): string {
    return bcmul($grams, '100', GOLD_POINT_DECIMALS);
}

function gold_format_points(string $points): string {
    return number_format((float)$points, 2, '.', ',');
}

function gold_format_rm(string $amount): string {
    return 'RM ' . number_format((float)$amount, 2, '.', ',');
}

function gold_format_grams(string $grams): string {
    return number_format((float)$grams, 4, '.', ',') . ' g';
}

// ============================================================
// DB / GOLD PRICE HELPERS
// ============================================================

function get_active_gold_price(): ?array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $db = getDB();
    $stmt = $db->query("SELECT * FROM gold_prices WHERE status='active' ORDER BY effective_at DESC LIMIT 1");
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function get_active_sell_price(): ?array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $db = getDB();
    $stmt = $db->query("SELECT * FROM gold_sell_prices WHERE status='active' ORDER BY effective_at DESC LIMIT 1");
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function get_wallet(int $user_id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

function get_wallet_balance(int $user_id): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN direction='credit' THEN points ELSE -points END), 0) AS points_balance,
            COALESCE(SUM(CASE WHEN direction='credit' THEN grams ELSE -grams END), 0) AS grams_balance
        FROM wallet_ledger wl
        JOIN wallets w ON w.id = wl.wallet_id
        WHERE w.user_id = ? AND wl.status='completed'
    ");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $points = $row ? (string)$row['points_balance'] : '0.0000';
    $grams  = $row ? (string)$row['grams_balance']  : '0.000000';
    $price  = get_active_gold_price();
    $rm_val = $price ? gold_rm_from_points($points, (string)$price['price_per_g']) : '0.00';
    return [
        'points'      => $points,
        'grams'       => $grams,
        'rm_value'    => $rm_val,
        'price_per_g' => $price ? (string)$price['price_per_g'] : '0',
    ];
}

function recalculate_wallet_balance(int $user_id): void {
    $db  = getDB();
    $bal = get_wallet_balance($user_id);
    $stmt = $db->prepare("UPDATE wallets SET points_balance=?, grams_balance=?, updated_at=NOW() WHERE user_id=?");
    $stmt->execute([$bal['points'], $bal['grams'], $user_id]);
}

// ============================================================
// LEDGER HELPERS
// ============================================================

function ledger_credit(int $wallet_id, int $user_id, string $points, string $grams, string $rm_val, string $price_snap, string $source_type, ?int $source_id, string $desc): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO wallet_ledger (wallet_id,user_id,direction,points,grams,rm_reference_value,price_per_g_snapshot,source_type,source_id,description,status,created_at) VALUES (?,?,'credit',?,?,?,?,?,?,?,'completed',NOW())");
    $stmt->execute([$wallet_id, $user_id, $points, $grams, $rm_val, $price_snap, $source_type, $source_id, $desc]);
    $id = (int)$db->lastInsertId();
    recalculate_wallet_balance($user_id);
    return $id;
}

function ledger_debit(int $wallet_id, int $user_id, string $points, string $grams, string $rm_val, string $price_snap, string $source_type, ?int $source_id, string $desc): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO wallet_ledger (wallet_id,user_id,direction,points,grams,rm_reference_value,price_per_g_snapshot,source_type,source_id,description,status,created_at) VALUES (?,?,'debit',?,?,?,?,?,?,?,'completed',NOW())");
    $stmt->execute([$wallet_id, $user_id, $points, $grams, $rm_val, $price_snap, $source_type, $source_id, $desc]);
    $id = (int)$db->lastInsertId();
    recalculate_wallet_balance($user_id);
    return $id;
}

// ============================================================
// REFERRAL HELPERS
// ============================================================

function get_referral_settings(): array {
    $db   = getDB();
    $rows = $db->query("SELECT level, rate_percent FROM referral_settings WHERE is_active=1 ORDER BY level")->fetchAll();
    $result = [];
    foreach ($rows as $r) $result[(int)$r['level']] = (string)$r['rate_percent'];
    return $result;
}

function process_referral_commissions(int $source_user_id, string $source_type, int $source_transaction_id, string $base_points, string $price_snap): void {
    $db    = getDB();
    $rates = get_referral_settings();
    if (empty($rates)) return;

    // Traverse upline chain up to 3 levels
    $current_user_id = $source_user_id;
    for ($level = 1; $level <= 3; $level++) {
        if (!isset($rates[$level])) break;

        $stmt = $db->prepare("SELECT referrer_user_id FROM referrals WHERE referred_user_id=?");
        $stmt->execute([$current_user_id]);
        $referral_row = $stmt->fetch();
        if (!$referral_row) break;

        $referrer_id       = (int)$referral_row['referrer_user_id'];
        $rate              = $rates[$level];
        $commission_points = bcmul($base_points, bcdiv($rate, '100', 8), GOLD_POINT_DECIMALS);
        $commission_grams  = gold_grams_from_points($commission_points);
        $commission_rm     = gold_rm_from_points($commission_points, $price_snap);

        // Get beneficiary wallet
        $wallet = get_wallet($referrer_id);
        if (!$wallet) {
            $current_user_id = $referrer_id;
            continue;
        }

        // Resolve referral record ID
        $ref_stmt2 = $db->prepare("SELECT id FROM referrals WHERE referred_user_id=?");
        $ref_stmt2->execute([$current_user_id]);
        $referral_record = $ref_stmt2->fetch();
        $referral_id     = $referral_record ? (int)$referral_record['id'] : 0;

        // Insert commission record (pending)
        $ins = $db->prepare("INSERT INTO referral_commissions (referral_id,beneficiary_user_id,source_user_id,source_transaction_type,source_transaction_id,level,rate_percent,points_earned,grams_earned,rm_value,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,'pending',NOW())");
        $ins->execute([$referral_id, $referrer_id, $source_user_id, $source_type, $source_transaction_id, $level, $rate, $commission_points, $commission_grams, $commission_rm]);
        $comm_id = (int)$db->lastInsertId();

        // Credit beneficiary wallet
        $ledger_id = ledger_credit(
            (int)$wallet['id'],
            $referrer_id,
            $commission_points,
            $commission_grams,
            $commission_rm,
            $price_snap,
            'referral_bonus',
            $comm_id,
            "Komisen Rujukan L{$level} dari transaksi #" . $source_transaction_id
        );

        // Mark commission as credited
        $db->prepare("UPDATE referral_commissions SET status='credited', ledger_entry_id=? WHERE id=?")->execute([$ledger_id, $comm_id]);

        $current_user_id = $referrer_id;
    }
}

// ============================================================
// UTILITY HELPERS
// ============================================================

function generate_referral_code(int $length = 8): string {
    $db    = getDB();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
        $stmt = $db->prepare("SELECT id FROM users WHERE referral_code=?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash_set(string $key, string $msg, string $type = 'success'): void {
    auth_start_session();
    $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
}

function flash_get(string $key): ?array {
    auth_start_session();
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function flash_html(string $key = 'main'): string {
    $flash = flash_get($key);
    if (!$flash) return '';
    $type = $flash['type'];
    $msg  = h($flash['msg']);
    $classes = [
        'success' => 'alert-success',
        'error'   => 'alert-error',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
    $cls = $classes[$type] ?? 'alert-info';
    return "<div class=\"alert {$cls}\">{$msg}</div>";
}

function format_date(string $dt, string $format = 'd M Y, h:i A'): string {
    if (!$dt) return '-';
    return date($format, strtotime($dt));
}

function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
        $stmt->execute([$key]);
        $row         = $stmt->fetch();
        $cache[$key] = $row ? (string)$row['setting_value'] : $default;
    } catch (\Throwable $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function set_setting(string $key, string $value, int $admin_id): bool {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_by, updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by), updated_at=NOW()");
    return $stmt->execute([$key, $value, $admin_id]);
}

function audit_log(int $actor_id, string $actor_role, string $action, string $target_type, int $target_id, mixed $old, mixed $new): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare("INSERT INTO audit_logs (actor_user_id,actor_role,action_type,target_type,target_id,old_value_json,new_value_json,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([
            $actor_id,
            $actor_role,
            $action,
            $target_type,
            $target_id,
            $old !== null ? json_encode($old) : null,
            $new !== null ? json_encode($new) : null,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (\Throwable $e) {
        // Non-fatal: log to file
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

function upload_file(string $input_name, array $allowed_types, string $upload_dir, int $max_size = 2097152): string|false {
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) return false;
    $file = $_FILES[$input_name];
    if ($file['size'] > $max_size) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types, true)) return false;
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext      = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $target   = rtrim($upload_dir, '/') . '/' . $filename;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $target)) return false;
    return $filename;
}

function is_valid_rm_amount(string $amount): bool {
    if (!is_numeric($amount)) return false;
    $min = (float)(get_setting('min_topup_rm', '5'));
    $max = (float)(get_setting('max_topup_rm', '50000'));
    $val = (float)$amount;
    return $val >= $min && $val <= $max;
}

function paginate(int $total, int $per_page, int $current_page, string $url_pattern): array {
    $total_pages  = (int)ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages ?: 1));
    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => ($current_page - 1) * $per_page,
        'url_pattern'  => $url_pattern,
        'has_prev'     => $current_page > 1,
        'has_next'     => $current_page < $total_pages,
        'prev_page'    => $current_page - 1,
        'next_page'    => $current_page + 1,
    ];
}

function pagination_html(array $pag): string {
    if ($pag['total_pages'] <= 1) return '';
    $html = '<div style="display:flex;gap:6px;justify-content:center;align-items:center;margin-top:16px;flex-wrap:wrap;">';
    $url  = function(int $p) use ($pag): string {
        return str_replace('{page}', (string)$p, $pag['url_pattern']);
    };
    if ($pag['has_prev']) {
        $html .= '<a href="' . h($url($pag['prev_page'])) . '" class="btn-gold-outline btn-sm">← Sebelum</a>';
    }
    $start = max(1, $pag['current_page'] - 2);
    $end   = min($pag['total_pages'], $pag['current_page'] + 2);
    if ($start > 1) $html .= '<a href="' . h($url(1)) . '" class="btn-gold-outline btn-sm">1</a>' . ($start > 2 ? '<span style="color:#9CA3AF;">…</span>' : '');
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $pag['current_page']) {
            $html .= '<span class="btn-gold btn-sm" style="pointer-events:none;">' . $i . '</span>';
        } else {
            $html .= '<a href="' . h($url($i)) . '" class="btn-gold-outline btn-sm">' . $i . '</a>';
        }
    }
    if ($end < $pag['total_pages']) {
        $html .= ($end < $pag['total_pages'] - 1 ? '<span style="color:#9CA3AF;">…</span>' : '') . '<a href="' . h($url($pag['total_pages'])) . '" class="btn-gold-outline btn-sm">' . $pag['total_pages'] . '</a>';
    }
    if ($pag['has_next']) {
        $html .= '<a href="' . h($url($pag['next_page'])) . '" class="btn-gold-outline btn-sm">Seterus →</a>';
    }
    $html .= '</div>';
    return $html;
}

function status_badge(string $status): string {
    $map = [
        'active'      => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Aktif'],
        'inactive'    => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Tidak Aktif'],
        'pending'     => ['bg'=>'#FEF3C7','color'=>'#92400E','label'=>'Tertangguh'],
        'approved'    => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Diluluskan'],
        'suspended'   => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Digantung'],
        'rejected'    => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Ditolak'],
        'completed'   => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Selesai'],
        'cancelled'   => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Dibatalkan'],
        'paid'        => ['bg'=>'#DBEAFE','color'=>'#1E40AF','label'=>'Dibayar'],
        'shipped'     => ['bg'=>'#EDE9FE','color'=>'#5B21B6','label'=>'Dihantar'],
        'refunded'    => ['bg'=>'#FEF3C7','color'=>'#92400E','label'=>'Dikembalikan'],
        'credited'    => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Dikreditkan'],
        'failed'      => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Gagal'],
        'superseded'  => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Diganti'],
        'processing'  => ['bg'=>'#DBEAFE','color'=>'#1E40AF','label'=>'Diproses'],
        'draft'               => ['bg'=>'#F3F4F6','color'=>'#374151','label'=>'Draf'],
        'sold_out'            => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Habis'],
        'paid_by_points'      => ['bg'=>'#DBEAFE','color'=>'#1E40AF','label'=>'Dibayar'],
        'merchant_processing' => ['bg'=>'#EDE9FE','color'=>'#5B21B6','label'=>'Diproses'],
        'paid'                => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Dibayar'],
        'active'              => ['bg'=>'#DBEAFE','color'=>'#1E40AF','label'=>'Aktif'],
        'redeemed'            => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Ditebus'],
        'defaulted'           => ['bg'=>'#FEE2E2','color'=>'#991B1B','label'=>'Tamat Tempoh'],
        'cancelled'           => ['bg'=>'#F3F4F6','color'=>'#6B7280','label'=>'Dibatalkan'],
        'ready'               => ['bg'=>'#D1FAE5','color'=>'#065F46','label'=>'Siap Ambil'],
        'collected'           => ['bg'=>'#DBEAFE','color'=>'#1D4ED8','label'=>'Dikutip'],
    ];
    $s   = strtolower($status);
    $cfg = $map[$s] ?? ['bg'=>'#F3F4F6','color'=>'#374151','label'=>h($status)];
    return '<span style="background:' . $cfg['bg'] . ';color:' . $cfg['color'] . ';border-radius:9999px;padding:2px 10px;font-size:0.75rem;font-weight:600;white-space:nowrap;">' . $cfg['label'] . '</span>';
}

function ensure_wallet_exists(int $user_id): int {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM wallets WHERE user_id=?");
    $stmt->execute([$user_id]);
    $row  = $stmt->fetch();
    if ($row) return (int)$row['id'];
    $db->prepare("INSERT INTO wallets (user_id, points_balance, grams_balance, created_at, updated_at) VALUES (?,0,0,NOW(),NOW())")->execute([$user_id]);
    return (int)$db->lastInsertId();
}
