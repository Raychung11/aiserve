<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db   = getDB();
$page = max(1,(int)($_GET['page']??1)); $per = 25;
$search = sanitize_string($_GET['q']??'');
$where = ['1=1']; $params = [];
if ($search) { $where[]="(al.action_type LIKE ? OR al.target_type LIKE ? OR u.full_name LIKE ?)"; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
$ws = implode(' AND ',$where);
$cnt = $db->prepare("SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id=al.actor_user_id WHERE $ws"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/audit-logs?page={page}'.($search?'&q='.urlencode($search):''));
$stmt=$db->prepare("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON u.id=al.actor_user_id WHERE $ws ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']])); $logs=$stmt->fetchAll();

layout_begin_admin('Log Audit');
?>
<div class="page-title">📋 Log Audit Sistem</div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
  <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari tindakan, jenis, pengguna..." class="input-kasih" style="max-width:320px;">
  <button type="submit" class="btn-gold btn-sm">Cari</button>
  <?php if ($search): ?><a href="<?= APP_URL ?>/admin/audit-logs" class="btn-gold-outline btn-sm">Reset</a><?php endif; ?>
</form>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Pengguna</th><th>Peranan</th><th>Tindakan</th><th>Sasaran</th><th>IP</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td style="font-size:0.82rem;"><?= h($l['full_name'] ?? 'Sistem') ?></td>
          <td><?= status_badge($l['actor_role'] ?? 'system') ?></td>
          <td><code style="font-size:0.72rem;background:#F3F4F6;padding:2px 6px;border-radius:4px;"><?= h($l['action_type']) ?></code></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($l['target_type']??'') ?> <?= $l['target_id'] ? '#'.$l['target_id'] : '' ?></td>
          <td style="font-size:0.72rem;color:#9CA3AF;"><?= h($l['ip_address'] ?? '') ?></td>
          <td style="font-size:0.72rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($l['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
