<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

// ── POST: confirm or cancel purchase ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid    = (int)($_POST['purchase_id'] ?? 0);
    $action = sanitize_string($_POST['action'] ?? '');

    $stmt = $db->prepare("SELECT gp.*, u.full_name, u.email FROM gold_purchases gp JOIN users u ON u.id = gp.user_id WHERE gp.id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch();

    if ($p && $action === 'confirm_payment' && $p['payment_status'] === 'paid' && $p['purchase_status'] === 'processing') {
        $wallet_id = ensure_wallet_exists((int)$p['user_id']);
        $desc = 'Pembelian Emas — RM ' . number_format((float)$p['rm_amount'], 2) . ' (Disahkan Admin)';
        $lid  = ledger_credit(
            $wallet_id,
            (int)$p['user_id'],
            $p['points_credited'],
            $p['grams_credited'],
            $p['rm_amount'],
            $p['price_per_g_snapshot'],
            'buy_credit',
            $pid,
            $desc
        );
        $db->prepare("UPDATE gold_purchases SET purchase_status='credited', ledger_entry_id=?, updated_at=NOW() WHERE id=?")->execute([$lid, $pid]);
        process_referral_commissions((int)$p['user_id'], 'gold_purchase', $pid, $p['points_credited'], $p['price_per_g_snapshot']);
        audit_log((int)$admin['id'], 'super_admin', 'purchase_confirmed', 'gold_purchases', $pid, ['status' => 'processing'], ['status' => 'credited', 'points' => $p['points_credited']]);
        flash_set('main', 'Pembayaran #' . $pid . ' disahkan — ' . gold_format_points($p['points_credited']) . ' pts dikreditkan kepada ' . h($p['full_name']) . '.', 'success');

    } elseif ($p && $action === 'cancel' && in_array($p['payment_status'], ['pending', 'paid'])) {
        $db->prepare("UPDATE gold_purchases SET payment_status='cancelled', purchase_status='failed', updated_at=NOW() WHERE id=?")->execute([$pid]);
        audit_log((int)$admin['id'], 'super_admin', 'purchase_cancelled', 'gold_purchases', $pid, ['payment_status' => $p['payment_status']], ['payment_status' => 'cancelled']);
        flash_set('main', 'Pembelian #' . $pid . ' dibatalkan.', 'warning');

    } else {
        flash_set('main', 'Tindakan tidak sah atau pembelian sudah diproses.', 'error');
    }
    redirect(APP_URL . '/admin/purchases');
}

// ── Filters ───────────────────────────────────────────────────────────────────
$payment_f  = sanitize_string($_GET['payment_status'] ?? '');
$purchase_f = sanitize_string($_GET['purchase_status'] ?? '');
$q          = trim($_GET['q'] ?? '');
$date_from  = sanitize_string($_GET['date_from'] ?? '');
$date_to    = sanitize_string($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per        = 20;

$where  = ['1=1'];
$params = [];
if ($payment_f)  { $where[] = 'gp.payment_status = ?';  $params[] = $payment_f; }
if ($purchase_f) { $where[] = 'gp.purchase_status = ?'; $params[] = $purchase_f; }
if ($q) {
    $where[]  = '(u.full_name LIKE ? OR u.email LIKE ? OR gp.id LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($date_from) { $where[] = 'DATE(gp.created_at) >= ?'; $params[] = $date_from; }
if ($date_to)   { $where[] = 'DATE(gp.created_at) <= ?'; $params[] = $date_to; }
$ws = implode(' AND ', $where);

// Stats (unfiltered totals for the day)
$today_stats = $db->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN payment_status='paid' AND purchase_status='processing' THEN 1 ELSE 0 END) AS awaiting,
    SUM(CASE WHEN purchase_status='credited' THEN 1 ELSE 0 END) AS credited,
    SUM(CASE WHEN purchase_status='credited' THEN points_credited ELSE 0 END) AS total_pts,
    SUM(CASE WHEN purchase_status='credited' THEN rm_amount ELSE 0 END) AS total_rm
FROM gold_purchases WHERE DATE(created_at)=CURDATE()")->fetch();

$cnt = $db->prepare("SELECT COUNT(*) FROM gold_purchases gp JOIN users u ON u.id=gp.user_id WHERE {$ws}");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();

$url_base = APP_URL . '/admin/purchases?payment_status=' . urlencode($payment_f) . '&purchase_status=' . urlencode($purchase_f) . '&q=' . urlencode($q) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '&page={page}';
$pag = paginate($total, $per, $page, $url_base);

$stmt = $db->prepare("SELECT gp.*, u.full_name, u.email FROM gold_purchases gp JOIN users u ON u.id=gp.user_id WHERE {$ws} ORDER BY gp.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$per, $pag['offset']]));
$purchases = $stmt->fetchAll();

layout_begin_admin('Semua Pembelian');
?>
<div class="page-title">🛒 Semua Pembelian</div>
<?= flash_html('main') ?>

<!-- Today's Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card">
    <div class="stat-value"><?= (int)$today_stats['total'] ?></div>
    <div class="stat-label">Pembelian Hari Ini</div>
  </div>
  <div class="stat-card stat-card-blue">
    <div class="stat-value" style="color:<?= (int)$today_stats['awaiting'] > 0 ? '#EF4444' : 'inherit' ?>;"><?= (int)$today_stats['awaiting'] ?></div>
    <div class="stat-label">Menunggu Pengesahan</div>
  </div>
  <div class="stat-card stat-card-green">
    <div class="stat-value"><?= (int)$today_stats['credited'] ?></div>
    <div class="stat-label">Dikreditkan Hari Ini</div>
  </div>
  <div class="stat-card stat-card-purple">
    <div class="stat-value" style="font-size:1rem;"><?= gold_format_rm((string)($today_stats['total_rm'] ?? '0')) ?></div>
    <div class="stat-label">Jumlah RM Hari Ini</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Nama / E-mel / #ID</label>
      <input type="text" name="q" class="form-input" placeholder="Ahmad, ahmad@..." value="<?= h($q) ?>" style="min-width:180px;">
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status Bayaran</label>
      <select name="payment_status" class="form-input">
        <option value="">Semua</option>
        <option value="pending"   <?= $payment_f==='pending'   ? 'selected':'' ?>>⏳ Tertangguh</option>
        <option value="paid"      <?= $payment_f==='paid'      ? 'selected':'' ?>>💳 Dibayar</option>
        <option value="failed"    <?= $payment_f==='failed'    ? 'selected':'' ?>>❌ Gagal</option>
        <option value="cancelled" <?= $payment_f==='cancelled' ? 'selected':'' ?>>🚫 Dibatalkan</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status Pembelian</label>
      <select name="purchase_status" class="form-input">
        <option value="">Semua</option>
        <option value="pending"    <?= $purchase_f==='pending'    ? 'selected':'' ?>>⏳ Tertangguh</option>
        <option value="processing" <?= $purchase_f==='processing' ? 'selected':'' ?>>🔄 Menunggu Sahkan</option>
        <option value="credited"   <?= $purchase_f==='credited'   ? 'selected':'' ?>>✅ Dikreditkan</option>
        <option value="failed"     <?= $purchase_f==='failed'     ? 'selected':'' ?>>❌ Gagal</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Dari Tarikh</label>
      <input type="date" name="date_from" class="form-input" value="<?= h($date_from) ?>">
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Hingga Tarikh</label>
      <input type="date" name="date_to" class="form-input" value="<?= h($date_to) ?>">
    </div>
    <div style="display:flex;gap:6px;">
      <button type="submit" class="btn-gold btn-sm">Tapis</button>
      <a href="<?= APP_URL ?>/admin/purchases" class="btn-gold-outline btn-sm">Reset</a>
    </div>
  </form>
</div>

<!-- Purchases Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> rekod</div>
    <?php if ((int)$today_stats['awaiting'] > 0): ?>
      <div style="background:#FEF3C7;color:#92400E;border-radius:8px;padding:6px 14px;font-size:0.82rem;font-weight:600;">
        ⚠️ <?= (int)$today_stats['awaiting'] ?> pembelian menunggu pengesahan anda
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($purchases)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:32px 0;font-size:0.875rem;">Tiada rekod dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr>
          <th>#ID</th>
          <th>Pengguna</th>
          <th>Jumlah RM</th>
          <th>Gold Points</th>
          <th>Harga/g</th>
          <th>Bukti</th>
          <th>Status Bayaran</th>
          <th>Status Kredit</th>
          <th>Tarikh</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($purchases as $p):
            // Parse proof filename from payment_reference
            $proof_file = null;
            $ref_display = $p['payment_reference'] ?? '';
            if ($p['payment_reference'] && str_contains($p['payment_reference'], '|proof:')) {
                [$ref_display, $proof_part] = explode('|proof:', $p['payment_reference'], 2);
                $proof_file = trim($proof_part);
            }
            $ref_display = trim($ref_display);
            $is_image = $proof_file && preg_match('/\.(jpg|jpeg|png|webp)$/i', $proof_file);
            $is_pdf   = $proof_file && str_ends_with(strtolower($proof_file), '.pdf');
        ?>
        <tr style="<?= ($p['payment_status']==='paid' && $p['purchase_status']==='processing') ? 'background:#FFFBEB;' : '' ?>">
          <td style="font-size:0.78rem;color:#9CA3AF;font-weight:600;">#<?= $p['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($p['full_name']) ?></div>
            <div style="font-size:0.75rem;color:#9CA3AF;"><?= h($p['email']) ?></div>
          </td>
          <td style="font-weight:700;font-size:0.95rem;">RM <?= number_format((float)$p['rm_amount'], 2) ?></td>
          <td style="color:var(--gold-dark);font-weight:600;"><?= gold_format_points($p['points_credited']) ?></td>
          <td style="font-size:0.8rem;color:#6B7280;">RM <?= number_format((float)$p['price_per_g_snapshot'], 4) ?></td>
          <td style="text-align:center;">
            <?php if ($proof_file): ?>
              <?php if ($is_image): ?>
                <a href="<?= APP_URL ?>/uploads/payments/<?= h($proof_file) ?>" target="_blank" title="Lihat bukti">
                  <img src="<?= APP_URL ?>/uploads/payments/<?= h($proof_file) ?>"
                       style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:2px solid #D1FAE5;cursor:pointer;">
                </a>
              <?php elseif ($is_pdf): ?>
                <a href="<?= APP_URL ?>/uploads/payments/<?= h($proof_file) ?>" target="_blank" class="btn-gold-outline btn-sm">📄 PDF</a>
              <?php endif; ?>
              <?php if ($ref_display): ?>
                <div style="font-size:0.7rem;color:#9CA3AF;margin-top:3px;"><?= h($ref_display) ?></div>
              <?php endif; ?>
            <?php elseif ($ref_display): ?>
              <span style="font-size:0.78rem;color:#6B7280;"><?= h($ref_display) ?></span>
            <?php else: ?>
              <span style="color:#D1D5DB;font-size:0.75rem;">—</span>
            <?php endif; ?>
          </td>
          <td><?= status_badge($p['payment_status']) ?></td>
          <td><?= status_badge($p['purchase_status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;">
            <?= format_date($p['created_at']) ?>
            <?php if ($p['paid_at']): ?>
              <br><span style="color:#10B981;">Bayar: <?= format_date($p['paid_at'], 'd M, h:i A') ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($p['payment_status'] === 'paid' && $p['purchase_status'] === 'processing'): ?>
            <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="purchase_id" value="<?= $p['id'] ?>">
              <button name="action" value="confirm_payment" class="btn-gold btn-sm"
                      onclick="return confirm('Sahkan pembayaran RM <?= number_format((float)$p['rm_amount'],2) ?> daripada <?= h(addslashes($p['full_name'])) ?> dan kreditkan <?= gold_format_points($p['points_credited']) ?> pts?')">
                ✅ Sahkan &amp; Kredit
              </button>
              <button name="action" value="cancel" class="btn-sm"
                      style="background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;"
                      onclick="return confirm('Batalkan pembelian ini?')">
                ❌ Batal
              </button>
            </form>
            <?php elseif ($p['payment_status'] === 'pending'): ?>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="purchase_id" value="<?= $p['id'] ?>">
              <button name="action" value="cancel" class="btn-sm"
                      style="background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;"
                      onclick="return confirm('Batalkan pembelian tertangguh ini?')">
                ❌ Batal
              </button>
            </form>
            <?php else: ?>
              <span style="color:#D1D5DB;font-size:0.8rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_admin(); ?>
