<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db = getDB();
$page = max(1,(int)($_GET['page']??1)); $per = 20;
$total=(int)$db->query("SELECT COUNT(*) FROM wallet_transfers")->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/transfers?page={page}');

$stmt = $db->prepare("SELECT wt.*, s.full_name AS sender_name, s.email AS sender_email, r.full_name AS receiver_name, r.email AS receiver_email FROM wallet_transfers wt JOIN users s ON s.id=wt.sender_user_id JOIN users r ON r.id=wt.receiver_user_id ORDER BY wt.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per,$pag['offset']]);
$transfers = $stmt->fetchAll();

layout_begin_admin('Semua Pindahan');
?>
<div class="page-title">↔️ Semua Pindahan</div>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Penghantar</th><th>Penerima</th><th>Mata</th><th>Nilai RM</th><th>Status</th><th>Nota</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($transfers as $t): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $t['id'] ?></td>
          <td><div style="font-size:0.85rem;"><?= h($t['sender_name']) ?></div><div style="font-size:0.72rem;color:#9CA3AF;"><?= h($t['sender_email']) ?></div></td>
          <td><div style="font-size:0.85rem;"><?= h($t['receiver_name']) ?></div><div style="font-size:0.72rem;color:#9CA3AF;"><?= h($t['receiver_email']) ?></div></td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($t['points']) ?></td>
          <td style="font-size:0.82rem;">RM <?= number_format((float)$t['rm_reference_value'],2) ?></td>
          <td><?= status_badge($t['transfer_status']) ?></td>
          <td style="font-size:0.78rem;color:#6B7280;max-width:120px;"><?= h($t['note'] ?? '—') ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($t['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
