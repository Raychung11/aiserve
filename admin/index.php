<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$price = get_active_gold_price();

// ── Core stats ────────────────────────────────────────────────────────────────
$stats = [];
$stats['users']                 = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$stats['merchants']             = (int)$db->query("SELECT COUNT(*) FROM merchants WHERE status='active'")->fetchColumn();
$stats['pending_merchants']     = (int)$db->query("SELECT COUNT(*) FROM merchants WHERE status='pending'")->fetchColumn();
$stats['total_purchases_rm']    = (float)$db->query("SELECT COALESCE(SUM(rm_amount),0) FROM gold_purchases WHERE payment_status='paid'")->fetchColumn();
$stats['total_points_issued']   = (float)$db->query("SELECT COALESCE(SUM(points_credited),0) FROM gold_purchases WHERE purchase_status='credited'")->fetchColumn();
$stats['pending_payouts']       = (int)$db->query("SELECT COUNT(*) FROM merchant_payout_requests WHERE status='pending'")->fetchColumn();
$stats['marketplace_orders']    = (int)$db->query("SELECT COUNT(*) FROM marketplace_orders")->fetchColumn();
$stats['referral_commissions_rm'] = (float)$db->query("SELECT COALESCE(SUM(rm_value),0) FROM referral_commissions WHERE status='credited'")->fetchColumn();

// ── Pending action counts (for notification badges) ───────────────────────────
$pending['kyc']          = (int)$db->query("SELECT COUNT(*) FROM kyc_submissions WHERE status='pending'")->fetchColumn();
$pending['sell_gold']    = (int)$db->query("SELECT COUNT(*) FROM gold_sell_requests WHERE status='pending'")->fetchColumn();
$pending['ar_rahnu']     = (int)$db->query("SELECT COUNT(*) FROM ar_rahnu_applications WHERE status='pending'")->fetchColumn();
$pending['physical_gold']= (int)$db->query("SELECT COUNT(*) FROM gold_physical_redemptions WHERE status='pending'")->fetchColumn();

$total_pending = array_sum($pending) + $stats['pending_merchants'] + $stats['pending_payouts'];

// ── Today's activity ──────────────────────────────────────────────────────────
$today_purchases = (float)$db->query("SELECT COALESCE(SUM(rm_amount),0) FROM gold_purchases WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")->fetchColumn();
$today_new_users = (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// ── Recent audit activity ─────────────────────────────────────────────────────
$activity = $db->query("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON u.id=al.actor_user_id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

layout_begin_admin('Admin Dashboard');
?>

<div class="page-title">🏠 Admin Dashboard</div>

<!-- ══ NOTIFICATION CENTRE ══════════════════════════════════════════════════ -->
<?php if ($total_pending > 0): ?>
<div style="background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:1px solid #F59E0B;border-radius:12px;padding:18px 20px;margin-bottom:24px;">
  <div style="font-weight:700;color:#92400E;font-size:1rem;margin-bottom:14px;">
    🔔 Perlu Tindakan — <?= $total_pending ?> item menunggu
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">

    <?php if ($pending['kyc'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/kyc" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">🪪</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#92400E;"><?= $pending['kyc'] ?> eKYC Menunggu</div>
        <div style="font-size:0.75rem;color:#6B7280;">Perlu semak &amp; luluskan</div>
      </div>
      <div style="margin-left:auto;color:#F59E0B;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

    <?php if ($pending['sell_gold'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/sell-gold" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">💵</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#991B1B;"><?= $pending['sell_gold'] ?> Jual Emas Menunggu</div>
        <div style="font-size:0.75rem;color:#6B7280;">Proses pembayaran pengguna</div>
      </div>
      <div style="margin-left:auto;color:#EF4444;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

    <?php if ($pending['physical_gold'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/physical-gold" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">🥇</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#92400E;"><?= $pending['physical_gold'] ?> Emas Fizikal Menunggu</div>
        <div style="font-size:0.75rem;color:#6B7280;">Sedia plat untuk pengguna</div>
      </div>
      <div style="margin-left:auto;color:#F59E0B;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

    <?php if ($pending['ar_rahnu'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/ar-rahnu" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #C7D2FE;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">🕌</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#1E40AF;"><?= $pending['ar_rahnu'] ?> Ar Rahnu Menunggu</div>
        <div style="font-size:0.75rem;color:#6B7280;">Semak permohonan gadaian</div>
      </div>
      <div style="margin-left:auto;color:#3B82F6;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

    <?php if ($stats['pending_merchants'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/merchants" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #D1FAE5;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">🏪</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#065F46;"><?= $stats['pending_merchants'] ?> Pedagang Menunggu</div>
        <div style="font-size:0.75rem;color:#6B7280;">Luluskan akaun pedagang</div>
      </div>
      <div style="margin-left:auto;color:#10B981;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

    <?php if ($stats['pending_payouts'] > 0): ?>
    <a href="<?= APP_URL ?>/admin/payouts" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #E9D5FF;border-radius:8px;padding:12px 14px;text-decoration:none;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;">💰</div>
      <div>
        <div style="font-weight:700;font-size:0.95rem;color:#5B21B6;"><?= $stats['pending_payouts'] ?> Bayaran Pedagang</div>
        <div style="font-size:0.75rem;color:#6B7280;">Proses permintaan bayaran</div>
      </div>
      <div style="margin-left:auto;color:#8B5CF6;font-size:1.1rem;">→</div>
    </a>
    <?php endif; ?>

  </div>
</div>
<?php else: ?>
<div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:10px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:center;gap:10px;">
  <span style="font-size:1.4rem;">✅</span>
  <span style="font-weight:600;color:#065F46;">Tiada item menunggu tindakan. Semua beres!</span>
</div>
<?php endif; ?>

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

<!-- Main Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-value"><?= number_format($stats['users']) ?></div>
    <div class="stat-label">Jumlah Pengguna</div>
    <div class="stat-change up">+<?= $today_new_users ?> hari ini</div>
  </div>
  <div class="stat-card stat-card-green">
    <div class="stat-value"><?= number_format($stats['merchants']) ?></div>
    <div class="stat-label">Pedagang Aktif</div>
    <?php if ($stats['pending_merchants']): ?><div class="stat-change" style="color:#F59E0B;"><?= $stats['pending_merchants'] ?> menunggu kelulusan</div><?php endif; ?>
  </div>
  <div class="stat-card stat-card-blue">
    <div class="stat-value">RM <?= number_format($stats['total_purchases_rm'], 0) ?></div>
    <div class="stat-label">Jumlah Pembelian (RM)</div>
    <div class="stat-change up">RM <?= number_format($today_purchases, 2) ?> hari ini</div>
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
        <?php if (empty($activity)): ?>
        <tr><td colspan="4" style="text-align:center;color:#9CA3AF;padding:20px;">Tiada aktiviti lagi.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_end_admin(); ?>
