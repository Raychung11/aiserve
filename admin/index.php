<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$price = get_active_gold_price();

// Stats
$stats = [];
$stats['users']     = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$stats['merchants'] = (int)$db->query("SELECT COUNT(*) FROM merchants WHERE status='active'")->fetchColumn();
$stats['pending_merchants'] = (int)$db->query("SELECT COUNT(*) FROM merchants WHERE status='pending'")->fetchColumn();
$stats['total_purchases_rm'] = (float)$db->query("SELECT COALESCE(SUM(rm_amount),0) FROM gold_purchases WHERE payment_status='paid'")->fetchColumn();
$stats['total_points_issued'] = (float)$db->query("SELECT COALESCE(SUM(points_credited),0) FROM gold_purchases WHERE purchase_status='credited'")->fetchColumn();
$stats['pending_payouts'] = (int)$db->query("SELECT COUNT(*) FROM merchant_payout_requests WHERE status='pending'")->fetchColumn();
$stats['marketplace_orders'] = (int)$db->query("SELECT COUNT(*) FROM marketplace_orders")->fetchColumn();
$stats['referral_commissions_rm'] = (float)$db->query("SELECT COALESCE(SUM(rm_value),0) FROM referral_commissions WHERE status='credited'")->fetchColumn();

// Recent activity
$activity = $db->query("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON u.id=al.actor_user_id ORDER BY al.created_at DESC LIMIT 10")->fetchAll();

// Today's purchases
$today_purchases = $db->query("SELECT COALESCE(SUM(rm_amount),0) FROM gold_purchases WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")->fetchColumn();
$today_new_users = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();

layout_begin_admin('Admin Dashboard');
?>

<div class="page-title">🏠 Admin Dashboard</div>

<!-- Gold Price Banner -->
<div class="price-card" style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;text-align:left;">
  <div>
    <div class="price-label">Harga Emas Aktif (Ditetapkan Admin)</div>
    <?php if ($price): ?>
      <div class="price-big">RM <?= number_format((float)$price['price_per_g'],2) ?><span style="font-size:1.2rem;font-weight:400;color:#9CA3AF;">/gram</span></div>
      <div class="price-date">Dikemaskini: <?= format_date($price['effective_at']) ?> <?= $price['notes'] ? '— ' . h($price['notes']) : '' ?></div>
    <?php else: ?>
      <div style="font-size:1.5rem;font-weight:700;color:#DC2626;">⚠️ Belum Ditetapkan</div>
    <?php endif; ?>
  </div>
  <a href="<?= APP_URL ?>/admin/gold-price" class="btn-gold">Kemaskini Harga</a>
</div>

<!-- Alerts -->
<?php if ($stats['pending_merchants'] > 0): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
  ⚠️ <strong><?= $stats['pending_merchants'] ?> pedagang</strong> menunggu kelulusan. <a href="<?= APP_URL ?>/admin/merchants" style="font-weight:600;">Semak sekarang →</a>
</div>
<?php endif; ?>
<?php if ($stats['pending_payouts'] > 0): ?>
<div class="alert alert-info" style="margin-bottom:16px;">
  💰 <strong><?= $stats['pending_payouts'] ?> permintaan bayaran</strong> belum diproses. <a href="<?= APP_URL ?>/admin/payouts" style="font-weight:600;">Proses sekarang →</a>
</div>
<?php endif; ?>

<!-- Main Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-value"><?= number_format($stats['users']) ?></div>
    <div class="stat-label">Jumlah Pengguna Aktif</div>
    <div class="stat-change up">+<?= $today_new_users ?> hari ini</div>
  </div>
  <div class="stat-card stat-card-green">
    <div class="stat-value"><?= number_format($stats['merchants']) ?></div>
    <div class="stat-label">Pedagang Aktif</div>
    <?php if ($stats['pending_merchants']): ?><div class="stat-change" style="color:#F59E0B;"><?= $stats['pending_merchants'] ?> menunggu</div><?php endif; ?>
  </div>
  <div class="stat-card stat-card-blue">
    <div class="stat-value">RM <?= number_format($stats['total_purchases_rm'], 0) ?></div>
    <div class="stat-label">Jumlah Pembelian (RM)</div>
    <div class="stat-change up">RM <?= number_format((float)$today_purchases, 2) ?> hari ini</div>
  </div>
  <div class="stat-card" style="border-left-color:var(--gold);">
    <div class="stat-value"><?= gold_format_points($stats['total_points_issued']) ?></div>
    <div class="stat-label">Jumlah Mata Dikeluarkan</div>
  </div>
  <div class="stat-card stat-card-purple">
    <div class="stat-value"><?= number_format($stats['marketplace_orders']) ?></div>
    <div class="stat-label">Pesanan Pasaran Maya</div>
  </div>
  <div class="stat-card stat-card-red">
    <div class="stat-value">RM <?= number_format($stats['referral_commissions_rm'],0) ?></div>
    <div class="stat-label">Komisen Rujukan Dibayar</div>
  </div>
</div>

<!-- Recent Audit Activity -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <div class="section-title" style="margin:0;">📋 Aktiviti Terkini</div>
    <a href="<?= APP_URL ?>/admin/audit-logs" class="btn-gold-outline btn-sm">Lihat Semua</a>
  </div>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Pengguna</th><th>Tindakan</th><th>Sasaran</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($activity as $a): ?>
        <tr>
          <td style="font-size:0.82rem;"><?= h($a['full_name'] ?? 'Sistem') ?> <span style="color:#9CA3AF;">(<?= h($a['actor_role']) ?>)</span></td>
          <td><code style="font-size:0.75rem;background:#F3F4F6;padding:2px 6px;border-radius:4px;"><?= h($a['action_type']) ?></code></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($a['target_type'] ?? '') ?> <?= $a['target_id'] ? '#'.$a['target_id'] : '' ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($a['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end_admin(); ?>
