<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user_id = auth_id();
$user    = auth_user();
$db      = getDB();

// Referral stats
$referral_url  = APP_URL . '/register?ref=' . ($user['referral_code'] ?? '');
$referral_rates = get_referral_settings();

// My direct referrals (L1)
$stmt = $db->prepare("SELECT r.*, u.full_name, u.email, u.created_at AS joined_at FROM referrals r JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_user_id=? ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$my_referrals = $stmt->fetchAll();

// My commissions
$comm_stmt = $db->prepare("SELECT rc.*, u.full_name AS from_name FROM referral_commissions rc LEFT JOIN users u ON u.id=rc.source_user_id WHERE rc.beneficiary_user_id=? ORDER BY rc.created_at DESC LIMIT 20");
$comm_stmt->execute([$user_id]);
$commissions = $comm_stmt->fetchAll();

// Total earned
$total_stmt = $db->prepare("SELECT COALESCE(SUM(points_earned),0) AS total_pts, COALESCE(SUM(rm_value),0) AS total_rm FROM referral_commissions WHERE beneficiary_user_id=? AND status='credited'");
$total_stmt->execute([$user_id]);
$total_earned = $total_stmt->fetch();

layout_begin_user('Program Rujukan');
?>
<div class="page-title">📢 Program Rujukan Saya</div>

<!-- Referral URL Card -->
<div class="card-kasih card-gold" style="margin-bottom:20px;">
  <div style="margin-bottom:12px;">
    <div style="font-size:0.8rem;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Pautan Rujukan Anda</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <code style="background:#F3F4F6;padding:8px 12px;border-radius:6px;font-size:0.82rem;flex:1;word-break:break-all;"><?= h($referral_url) ?></code>
      <button class="btn-gold btn-sm" data-copy="<?= h($referral_url) ?>">📋 Salin</button>
    </div>
    <div style="margin-top:8px;font-size:0.8rem;color:#9CA3AF;">Kod: <strong style="color:var(--gold-dark);"><?= h($user['referral_code'] ?? '') ?></strong></div>
  </div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card">
    <div class="stat-value"><?= count($my_referrals) ?></div>
    <div class="stat-label">Rujukan Langsung (L1)</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= gold_format_points($total_earned['total_pts']) ?></div>
    <div class="stat-label">Jumlah Komisen (pts)</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= gold_format_rm($total_earned['total_rm']) ?></div>
    <div class="stat-label">Nilai Komisen (RM)</div>
  </div>
</div>

<!-- Rate info -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">💰 Kadar Komisen Rujukan</div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
    <?php foreach ([1,2,3] as $lvl): ?>
    <div style="background:<?= $lvl===1 ? 'linear-gradient(135deg,var(--gold),var(--gold-dark))' : '#F9FAFB' ?>;border-radius:8px;padding:14px;text-align:center;">
      <div style="font-size:0.7rem;<?= $lvl===1?'color:rgba(255,255,255,0.8)':'color:#9CA3AF' ?>;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Tahap <?= $lvl ?></div>
      <div style="font-size:1.6rem;font-weight:800;<?= $lvl===1?'color:#fff':'color:var(--gold-dark)' ?>;"><?= $referral_rates[$lvl] ?? '0' ?>%</div>
      <div style="font-size:0.72rem;<?= $lvl===1?'color:rgba(255,255,255,0.7)':'color:#9CA3AF' ?>;">
        <?= $lvl===1?'Rujukan anda':($lvl===2?'Rujukan rakan anda':'Rujukan 3 tahap') ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p style="font-size:0.78rem;color:#9CA3AF;margin-top:10px;">Komisen dikreditkan secara automatik apabila rujukan anda membuat pembelian emas yang layak.</p>
</div>

<!-- My referrals list -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">👥 Senarai Rujukan Saya</div>
  <?php if (empty($my_referrals)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Belum ada rujukan. Kongsi pautan anda sekarang!</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Nama</th><th>E-mel</th><th>Tarikh Sertai</th></tr></thead>
      <tbody>
        <?php foreach ($my_referrals as $ref): ?>
        <tr>
          <td><?= h($ref['full_name']) ?></td>
          <td style="font-size:0.82rem;color:#6B7280;"><?= h($ref['email']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= format_date($ref['joined_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Commission history -->
<div class="card-kasih">
  <div class="section-title">⭐ Sejarah Komisen</div>
  <?php if (empty($commissions)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Belum ada komisen diterima.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Dari</th><th>Tahap</th><th>Kadar</th><th>Mata</th><th>Nilai RM</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($commissions as $c): ?>
        <tr>
          <td style="font-size:0.82rem;"><?= h($c['from_name'] ?? '—') ?></td>
          <td><span class="badge badge-pending">L<?= $c['level'] ?></span></td>
          <td style="font-size:0.82rem;"><?= $c['rate_percent'] ?>%</td>
          <td style="font-weight:600;color:var(--gold-dark);">+<?= gold_format_points($c['points_earned']) ?></td>
          <td style="font-size:0.82rem;"><?= gold_format_rm($c['rm_value']) ?></td>
          <td><?= status_badge($c['status']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= format_date($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php layout_end_user(); ?>
