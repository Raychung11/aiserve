<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$user  = auth_user();
$user_id = auth_id();

$merchant_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$merchant_stmt->execute([$user_id]);
$merchant = $merchant_stmt->fetch();

if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }

$balance = get_wallet_balance($user_id);
$price   = get_active_gold_price();

// Stats
$tp = $db->prepare("SELECT COUNT(*) FROM marketplace_products WHERE merchant_id=? AND deleted_at IS NULL"); $tp->execute([$merchant['id']]); $total_products = (int)$tp->fetchColumn();
$po = $db->prepare("SELECT COUNT(*) FROM marketplace_orders WHERE merchant_id=? AND status IN ('paid_by_points','merchant_processing')"); $po->execute([$merchant['id']]); $pending_orders = (int)$po->fetchColumn();
$ts = $db->prepare("SELECT COALESCE(SUM(total_points),0) FROM marketplace_orders WHERE merchant_id=? AND status NOT IN ('cancelled','refunded')"); $ts->execute([$merchant['id']]); $total_sales = $ts->fetchColumn();
$pp = $db->prepare("SELECT COUNT(*) FROM merchant_payout_requests WHERE merchant_id=? AND status='pending'"); $pp->execute([$merchant['id']]); $pend_payouts = (int)$pp->fetchColumn();

// Recent orders
$orders = $db->prepare("SELECT mo.*, u.full_name AS buyer_name FROM marketplace_orders mo JOIN users u ON u.id=mo.buyer_user_id WHERE mo.merchant_id=? ORDER BY mo.created_at DESC LIMIT 5"); $orders->execute([$merchant['id']]); $recent_orders = $orders->fetchAll();

layout_begin_merchant('Merchant Dashboard');
?>
<div class="page-title">🏪 Dashboard Pedagang</div>

<?php if ($merchant['status'] !== 'active'): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
  ⚠️ Akaun pedagang anda <strong><?= status_badge($merchant['status']) ?></strong>. 
  <?= $merchant['status']==='pending' ? 'Sila tunggu kelulusan admin sebelum boleh menjual.' : 'Hubungi admin untuk maklumat lanjut.' ?>
  <?php if ($merchant['verification_notes']): ?><br><small><?= h($merchant['verification_notes']) ?></small><?php endif; ?>
</div>
<?php endif; ?>

<!-- Wallet Card -->
<div class="card-wallet" style="margin-bottom:20px;">
  <div class="wallet-balance-label">Baki Mata Pedagang</div>
  <div class="wallet-balance-points"><?= gold_format_points($balance['points']) ?> <span style="font-size:1rem;font-weight:400;">pts</span></div>
  <div class="wallet-balance-rm"><?= gold_format_rm($balance['rm_value']) ?></div>
  <?php if ($pend_payouts > 0): ?>
    <div style="margin-top:8px;"><span class="badge badge-pending"><?= $pend_payouts ?> permintaan bayaran tertangguh</span></div>
  <?php endif; ?>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= $total_products ?></div><div class="stat-label">Jumlah Produk</div></div>
  <div class="stat-card stat-card-blue"><div class="stat-value"><?= $pending_orders ?></div><div class="stat-label">Pesanan Aktif</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= gold_format_points($total_sales) ?></div><div class="stat-label">Jumlah Jualan (pts)</div></div>
  <div class="stat-card stat-card-purple"><div class="stat-value"><?= $pend_payouts ?></div><div class="stat-label">Bayaran Tertangguh</div></div>
</div>

<!-- Quick actions -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:20px;">
  <a href="<?= APP_URL ?>/merchant/products" class="card-kasih" style="text-align:center;text-decoration:none;">
    <div style="font-size:1.8rem;">📦</div><div style="font-weight:700;font-size:0.875rem;margin-top:6px;">Produk Saya</div>
  </a>
  <a href="<?= APP_URL ?>/merchant/orders" class="card-kasih" style="text-align:center;text-decoration:none;">
    <div style="font-size:1.8rem;">📋</div><div style="font-weight:700;font-size:0.875rem;margin-top:6px;">Pesanan</div>
  </a>
  <a href="<?= APP_URL ?>/merchant/payouts" class="card-kasih" style="text-align:center;text-decoration:none;">
    <div style="font-size:1.8rem;">💰</div><div style="font-weight:700;font-size:0.875rem;margin-top:6px;">Bayaran</div>
  </a>
</div>

<!-- Recent Orders -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">📋 Pesanan Terkini</div>
    <a href="<?= APP_URL ?>/merchant/orders" class="btn-gold-outline btn-sm">Lihat Semua</a>
  </div>
  <?php if (empty($recent_orders)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:16px 0;font-size:0.875rem;">Tiada pesanan lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Pembeli</th><th>Mata</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($recent_orders as $o): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $o['id'] ?></td>
          <td style="font-size:0.85rem;"><?= h($o['buyer_name']) ?></td>
          <td style="font-weight:600;color:var(--gold-dark);"><?= gold_format_points($o['total_points']) ?></td>
          <td><?= status_badge($o['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($o['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php layout_end_merchant(); ?>
