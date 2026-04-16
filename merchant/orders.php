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

    // Verify order belongs to merchant
    $chk = $db->prepare("SELECT * FROM marketplace_orders WHERE id=? AND merchant_id=?");
    $chk->execute([$order_id, $merchant_id]);
    $order = $chk->fetch();

    if (!$order) { flash_set('main','Pesanan tidak dijumpai.','error'); redirect(APP_URL.'/merchant/orders'); }

    if ($action === 'ship') {
        if ($order['status'] === 'paid') {
            $db->prepare("UPDATE marketplace_orders SET status='shipped', updated_at=NOW() WHERE id=?")->execute([$order_id]);
            flash_set('main','Status pesanan dikemaskini: Dihantar.','success');
        }
    } elseif ($action === 'complete') {
        if ($order['status'] === 'shipped') {
            $db->prepare("UPDATE marketplace_orders SET status='completed', updated_at=NOW() WHERE id=?")->execute([$order_id]);
            flash_set('main','Pesanan ditandakan selesai.','success');
        }
    } elseif ($action === 'cancel') {
        if (in_array($order['status'], ['pending','paid'])) {
            $db->beginTransaction();
            try {
                // Refund buyer if already paid
                if ($order['status'] === 'paid') {
                    $wallet = get_wallet($order['buyer_user_id']);
                    $price  = get_active_gold_price();
                    $price_snap = $price ? (string)$price['price_per_g'] : '0';
                    $grams  = gold_grams_from_points((string)$order['total_points']);
                    $rm_val = $price ? gold_rm_from_points((string)$order['total_points'], $price_snap) : '0.00';
                    if ($wallet) {
                        ledger_credit((int)$wallet['id'], $order['buyer_user_id'], (string)$order['total_points'], $grams, $rm_val, $price_snap, 'marketplace_refund', $order_id, 'Bayaran balik: pesanan #'.$order_id.' dibatalkan pedagang');
                    }
                    // Reverse merchant debit
                    $m_wallet = get_wallet($user_id);
                    if ($m_wallet) {
                        ledger_debit((int)$m_wallet['id'], $user_id, (string)$order['total_points'], $grams, $rm_val, $price_snap, 'marketplace_refund', $order_id, 'Bayaran balik dikeluarkan: pesanan #'.$order_id);
                    }
                }
                // Restore stock
                $items = $db->prepare("SELECT * FROM marketplace_order_items WHERE order_id=?");
                $items->execute([$order_id]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare("UPDATE marketplace_products SET stock_quantity=stock_quantity+? WHERE id=?")->execute([$item['quantity'],$item['product_id']]);
                }
                $db->prepare("UPDATE marketplace_orders SET status='cancelled', updated_at=NOW() WHERE id=?")->execute([$order_id]);
                $db->commit();
                flash_set('main','Pesanan dibatalkan.','success');
            } catch (\Throwable $e) {
                $db->rollBack();
                flash_set('main','Ralat membatalkan pesanan.','error');
            }
        }
    }
    redirect(APP_URL.'/merchant/orders');
}

// ── Fetch orders ─────────────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1,(int)($_GET['page'] ?? 1));
$per           = 20;

$where  = "mo.merchant_id = ?";
$params = [$merchant_id];
if ($status_filter) { $where .= " AND mo.status=?"; $params[] = $status_filter; }
if ($q) { $where .= " AND (u.full_name LIKE ? OR mo.id LIKE ?)"; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM marketplace_orders mo JOIN users u ON u.id=mo.buyer_user_id WHERE {$where}");
$ct->execute($params); $total = (int)$ct->fetchColumn();

$pag = paginate($total, $per, $page, APP_URL.'/merchant/orders?status='.urlencode($status_filter).'&q='.urlencode($q).'&page={page}');
$params_pg = array_merge($params, [$pag['offset'], $per]);
$o_stmt = $db->prepare("SELECT mo.*,u.full_name AS buyer_name,u.email AS buyer_email FROM marketplace_orders mo JOIN users u ON u.id=mo.buyer_user_id WHERE {$where} ORDER BY mo.created_at DESC LIMIT ?,?");
$o_stmt->execute($params_pg);
$orders = $o_stmt->fetchAll();

$statuses = ['pending'=>'Tertangguh','paid'=>'Dibayar','shipped'=>'Dihantar','completed'=>'Selesai','cancelled'=>'Dibatalkan','refunded'=>'Dikembalikan'];

layout_begin_merchant('Pesanan');
?>
<div class="page-title">📋 Pesanan Saya</div>
<?= flash_html('main') ?>

<!-- Filter Bar -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:140px;">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $status_filter===$v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Pembeli/ID</label>
      <input type="text" name="q" class="form-input" placeholder="Nama atau ID..." value="<?= h($q) ?>" style="min-width:160px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/merchant/orders" class="btn-gold-outline btn-sm">Padam Penapis</a>
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
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $o['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($o['buyer_name']) ?></div>
            <div style="font-size:0.75rem;color:#9CA3AF;"><?= h($o['buyer_email']) ?></div>
          </td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($o['total_points']) ?> pts</td>
          <td><?= status_badge($o['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($o['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
              <?php if ($o['status']==='paid'): ?>
              <form method="post" style="display:inline;">
                <?= csrf_field() ?><input type="hidden" name="action" value="ship"><input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-gold btn-sm">Hantar</button>
              </form>
              <?php endif; ?>
              <?php if ($o['status']==='shipped'): ?>
              <form method="post" style="display:inline;">
                <?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-gold btn-sm">Selesai</button>
              </form>
              <?php endif; ?>
              <?php if (in_array($o['status'],['pending','paid'])): ?>
              <form method="post" style="display:inline;" onsubmit="return confirm('Batalkan pesanan ini?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;">Batal</button>
              </form>
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
