<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db   = getDB();
$page = max(1,(int)($_GET['page']??1)); $per = 20;
$total=(int)$db->query("SELECT COUNT(*) FROM referral_commissions")->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/referrals?page={page}');

$stmt=$db->prepare("SELECT rc.*, b.full_name AS beneficiary, s.full_name AS source_name FROM referral_commissions rc JOIN users b ON b.id=rc.beneficiary_user_id JOIN users s ON s.id=rc.source_user_id ORDER BY rc.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per,$pag['offset']]); $comms=$stmt->fetchAll();

$total_credited = $db->query("SELECT COALESCE(SUM(rm_value),0) FROM referral_commissions WHERE status='credited'")->fetchColumn();
$total_pts_credited = $db->query("SELECT COALESCE(SUM(points_earned),0) FROM referral_commissions WHERE status='credited'")->fetchColumn();

layout_begin_admin('Laporan Rujukan');
?>
<div class="page-title">📢 Laporan Komisen Rujukan</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= gold_format_points($total_pts_credited) ?></div><div class="stat-label">Jumlah Komisen (pts)</div></div>
  <div class="stat-card stat-card-blue"><div class="stat-value">RM <?= number_format((float)$total_credited,2) ?></div><div class="stat-label">Jumlah Nilai Komisen</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$db->query("SELECT COUNT(DISTINCT beneficiary_user_id) FROM referral_commissions WHERE status='credited'")->fetchColumn() ?></div><div class="stat-label">Penerima Komisen</div></div>
</div>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Penerima</th><th>Dari</th><th>Tahap</th><th>Kadar</th><th>Mata</th><th>RM</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($comms as $c): ?>
        <tr>
          <td style="font-size:0.82rem;"><?= h($c['beneficiary']) ?></td>
          <td style="font-size:0.82rem;"><?= h($c['source_name']) ?></td>
          <td><span class="badge badge-pending">L<?= $c['level'] ?></span></td>
          <td style="font-size:0.82rem;"><?= $c['rate_percent'] ?>%</td>
          <td style="font-weight:600;color:var(--gold-dark);">+<?= gold_format_points($c['points_earned']) ?></td>
          <td style="font-size:0.82rem;">RM <?= number_format((float)$c['rm_value'],2) ?></td>
          <td><?= status_badge($c['status']) ?></td>
          <td style="font-size:0.72rem;color:#9CA3AF;"><?= format_date($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
