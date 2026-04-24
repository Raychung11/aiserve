<?php
/**
 * MM2H 管家 Platform — Helper Functions
 */

// ── Output sanitization ──────────────────────────────────────────────────────
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ── CSRF token ───────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}

// ── Flash messages ───────────────────────────────────────────────────────────
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function render_flash(): void {
    foreach (get_flash() as $f) {
        $type = h($f['type']);
        $msg  = h($f['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">"
           . $msg
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
           . '</div>';
    }
}

// ── Redirects ────────────────────────────────────────────────────────────────
function redirect(string $path): never {
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

// ── Referral code generator ──────────────────────────────────────────────────
function generate_referral_code(string $name): string {
    $prefix = strtoupper(substr(preg_replace('/[^A-Z]/i', '', $name), 0, 3));
    return $prefix . strtoupper(bin2hex(random_bytes(3)));
}

// ── Case number generator ────────────────────────────────────────────────────
function generate_case_number(): string {
    return 'MM2H-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// ── Pagination ───────────────────────────────────────────────────────────────
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = (int)ceil($total / $per_page);
    $offset      = ($current_page - 1) * $per_page;
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current_page,
        'total_pages' => $total_pages,
        'offset'      => $offset,
    ];
}

// ── File upload ──────────────────────────────────────────────────────────────
function handle_upload(array $file, int $user_id): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds limit (10 MB).'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES, true)) {
        return ['success' => false, 'error' => 'File type not allowed.'];
    }

    // Validate MIME type
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($file['tmp_name']);
    $allowed_mimes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (!isset($allowed_mimes[$ext]) || $allowed_mimes[$ext] !== $mime) {
        return ['success' => false, 'error' => 'Invalid file type.'];
    }

    $dir = UPLOAD_PATH . $user_id . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $safe_name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest      = $dir . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Could not save file.'];
    }

    return [
        'success'       => true,
        'file_name'     => $safe_name,
        'original_name' => $file['name'],
        'file_type'     => $ext,
        'file_size'     => $file['size'],
        'file_path'     => 'uploads/' . $user_id . '/' . $safe_name,
    ];
}

// ── Activity logging ─────────────────────────────────────────────────────────
function log_activity(string $action, ?string $target_type = null, ?int $target_id = null): void {
    $user_id = $_SESSION['user_id'] ?? null;
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $user_id,
            $action,
            $target_type,
            $target_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (PDOException $e) {
        // Non-fatal — don't break the app for logging failures
    }
}

// ── Format helpers ───────────────────────────────────────────────────────────
function format_money(float $amount, string $currency = 'MYR'): string {
    return $currency . ' ' . number_format($amount, 2);
}

function format_date(?string $date): string {
    if (!$date) return '—';
    return date('d M Y', strtotime($date));
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff / 60) . ' min ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)  return floor($diff / 86400) . ' days ago';
    return format_date($datetime);
}

function status_badge(string $status): string {
    $map = [
        'new_lead'          => 'secondary',
        'initial_consult'   => 'info',
        'doc_collection'    => 'primary',
        'eligibility_review'=> 'warning',
        'submitted_agent'   => 'primary',
        'gov_processing'    => 'warning',
        'conditional'       => 'info',
        'fd_stage'          => 'primary',
        'medical'           => 'warning',
        'final_approval'    => 'success',
        'completed'         => 'success',
        'rejected'          => 'danger',
        'active'            => 'success',
        'pending'           => 'warning',
        'verified'          => 'success',
        'approved'          => 'success',
        'paid'              => 'success',
        'rejected'          => 'danger',
        'not_started'       => 'secondary',
        'submitted'         => 'primary',
    ];
    $color = $map[$status] ?? 'secondary';
    $label = t('status_' . $status) ?: ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge bg-{$color}\">" . h($label) . '</span>';
}

// ── Setting helper ───────────────────────────────────────────────────────────
function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}
