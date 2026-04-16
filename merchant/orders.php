<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$user_id = auth_id();

$m_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$m_stmt->execute([$user_id]);
$merchant = $m_stmt->fetch();
if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }
$merchant_id = (int)$merchant['id'];

// ── Handle status update ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action   = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    $chk = $db->prepare("SELECT * FROM marketplace_orders WHERE id=? AND merchant_id=?");
    $chk->execute([$order_id, $merchant_id]);
    $order = $chk->fetch();

    if (!$order) { flash_set('main','Pesanan tidak dijumpai.','error'); redirect(APP_URL.'/merchant/orders'); }

    if ($action === 'process') {
        // Merchant starts processing (after buyer pays)
        if ($order['status'] === 'paid_by_points') {
            $db->prepare("UPDATE marketplace_orders SET status='merchant_processing', updated_at=NOW() WHERE id=?")->execute([$order_id]);
            flash_set('main','Pesanan #' . $order_id . ' sedang diproses.','success');
        }
    } elseif ($action === 'complete') {
        if ($order['status'] === 'merchant_processing') {
            $db->prepare("UPDATE marketplace_orders SET status='completed', updated_at=NOW() WHERE id=?")->execute([$order_id]);
            flash_set('main','Pesanan #' . $order_id . ' ditandakan selesai.','success');
        }
    } elseif ($action === 'cancel') {
        if (in_array($order['status'], ['pending', 'paid_by_points', 'merchant_processing'])) {
            $db->beginTransaction();
            try {
                // Refund buyer if payment was taken
                if (in_array($order['status'], ['paid_by_points', 'merchant_processing'])) {
                    $price      = get_active_gold_price();
                    $price_snap = $price ? (string)$price['price_per_g'] : '390.0000';
                    $pts        = (string)$order['total_points'];
                    $grams      = gold_grams_from_points($pts);
                    $rm_val     = gold_rm_from_points($pts, $price_snap);

                    // Refund buyer
                    $buyer_wid = ensure_wallet_exists((int)$order['buyer_user_id']);
                    ledger_credit($buyer_wid, (int)$order['buyer_user_id'], $pts, $grams, $rm_val, $price_snap,
                        'marketplace_refund', $order_id, 'Bayaran balik: pesanan #' . $order_id . ' dibatalkan pedagang');

                    // Reverse merchant credit
                    $m_wid = ensure_wallet_exists($user_id);
                    ledger_debit($m_wid, $user_id, $pts, $grams, $rm_val, $price_snap,
                        'marketplace_refund', $order_id, 'Bayaran balik dikeluarkan: pesanan #' . $order_id);
                }

                // Restore stock for each item
                $items = $db->prepare("SELECT product_id, qty FROM marketplace_order_items WHERE order_id=?");
                $items->execute([$order_id]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare("UPDATE marketplace_products SET stock_qty=stock_qty+? WHERE id=? AND stock_qty IS NOT NULL")
                       ->execute([$item['qty'], $item['product_id']]);
                }

                $db->prepare("UPDATE marketplace_orders SET status='cancelled', updated_at=NOW() WHERE id=?")->execute([$order_id]);
                $db->commit();
                flash_set('main','Pesanan #' . $order_id . ' dibatalkan.','success');
            } catch (\Throwable $e) {
                $db->rollBack();
                flash_set('main','Ralat membatalkan pesanan: ' . $e->getMessage(),'error');
            }
        }
    }
    redirect(APP_URL.'/merchant/orders');
}

// ── Fetch orders ─────────────────────────────────────────────────────────────
// Status label map matching actual schema ENUM
$statuses = [
    'pending'             => 'Tertangguh',
    'paid_by_points'      => 'Dibayar',
    'merchant_processing' => 'Diproses',
    'completed'           => 'Selesai',
    'cancelled'           => 'Dibatalkan',
    'refunded'            => 'Dikembalikan',
];

$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per           = 20;

$where  = "mo.merchant_id = ?";
$params = [$merchant_id];
if ($status_filter) { $where .= " AND mo.status=?"; $params[] = $status_filter; }
if ($q) { $where .= " AND (u.full_name LIKE ? OR mo.id LIKE ?)"; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM marketplace_orders mo JOIN users u ON u.id=mo.buyer_user_id WHERE {$where}");
$ct->execute($params);
$total = (int)$ct->fetchColumn();

$pag = paginate($total, $per, $page, APP_URL.'/merchant/orders?status='.urlencode($status_filter).'&q='.urlencode($q).'&page={page}');
$o_stmt = $db->prepare("SELECT mo.*,u.full_name AS buyer_name,u.email AS buyer_email FROM marketplace_orders mo JOIN users u ON u.id=mo.buyer_user_id WHERE {$where} ORDER BY mo.created_at DESC LIMIT ?,?");
$o_stmt->execute(array_merge($params, [$pag['offset'], $per]));
$orders = $o_stmt->fetchAll();

// Stats for this merchant
$s = $db->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='paid_by_points'      THEN 1 ELSE 0 END) AS new_orders,
    SUM(CASE WHEN status='merchant_processing' THEN 1 ELSE 0 END) AS processing,
    SUM(CASE WHEN status='completed'           THEN 1 ELSE 0 END) AS completed
FROM marketplace_orders WHERE merchant_id=?");
$s->execute([$merchant_id]);
$order_stats = $s->fetch();

layout_begin_merchant('Pesanan');
?>
<div class="page-title">📋 Pesanan Saya</div>
<?= flash_html('main') ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= (int)$order_stats['total'] ?></div><div class="stat-label">Jumlah Pesanan</div></div>
  <div class="stat-card stat-card-blue" style="<?= (int)$order_stats['new_orders']>0?'border-left:3px solid #EF4444;':'' ?>">
    <div class="stat-value" style="color:<?= (int)$order_stats['new_orders']>0?'#EF4444':'inherit' ?>;"><?= (int)$order_stats['new_orders'] ?></div>
    <div class="stat-label">Pesanan Baharu</div>
  </div>
  <div class="stat-card stat-card-purple"><div class="stat-value"><?= (int)$order_stats['processing'] ?></div><div class="stat-label">Sedang Diproses</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$order_stats['completed'] ?></div><div class="stat-label">Selesai</div></div>
</div>

<!-- Filter Bar -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:160px;">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $v => $l): ?>
          <option value="<?= $v ?>" <?= $status_filter===$v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Pembeli / #ID</label>
      <input type="text" name="q" class="form-input" placeholder="Nama atau ID pesanan..." value="<?= h($q) ?>" style="min-width:160px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/merchant/orders" class="btn-gold-outline btn-sm">Reset</a>
  </form>
</div>

<!-- Orders Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> pesanan</div>
  </div>
  <?php if (empty($orders)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;font-size:0.875rem;">Tiada pesanan dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr><th>#ID</th><th>Pembeli</th><th>Jumlah Mata</th><th>Status</th><th>Tarikh</th><th>Tindakan</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr style="<?= $o['status']==='paid_by_points' ? 'background:#FFFBEB;' : '' ?>">
          <td style="font-size:0.78rem;color:#9CA3AF;font-weight:600;">#<?= $o['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($o['buyer_name']) ?></div>
            <div style="font-size:0.75rem;color:#9CA3AF;"><?= h($o['buyer_email']) ?></div>
          </td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($o['total_points']) ?> pts</td>
          <td><?= status_badge($o['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($o['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
              <?php if ($o['status'] === 'paid_by_points'): ?>
              <form method="post" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="process">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-gold btn-sm">▶ Proses</button>
              </form>
              <?php endif; ?>

              <?php if ($o['status'] === 'merchant_processing'): ?>
              <form method="post" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-gold btn-sm">✅ Selesai</button>
              </form>
              <?php endif; ?>

              <?php if (in_array($o['status'], ['pending','paid_by_points','merchant_processing'])): ?>
              <form method="post" style="display:inline;" onsubmit="return confirm('Batalkan pesanan #<?= $o['id'] ?>? Mata akan dikembalikan kepada pembeli.')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;">Batal</button>
              </form>
              <?php endif; ?>

              <?php if ($o['status'] === 'completed' || $o['status'] === 'cancelled'): ?>
                <span style="color:#D1D5DB;font-size:0.78rem;">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_merchant(); ?>
