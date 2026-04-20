<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action  = sanitize_string($_POST['action'] ?? '');
    $user_id_target = (int)($_POST['user_id'] ?? 0);
    if ($user_id_target && in_array($action, ['activate','suspend','reject'])) {
        $new_status = $action === 'activate' ? 'active' : ($action === 'suspend' ? 'suspended' : 'rejected');
        $old = $db->prepare("SELECT status FROM users WHERE id=?")->execute([$user_id_target]) ? null : null;
        $db->prepare("UPDATE users SET status=? WHERE id=? AND role != 'super_admin'")->execute([$new_status, $user_id_target]);
        audit_log((int)$admin['id'],'super_admin','user_status_changed','users',$user_id_target,null,['status'=>$new_status]);
        flash_set('main','Status pengguna berjaya dikemaskini.','success');
        redirect(APP_URL . '/admin/users');
    }
}

$search = sanitize_string($_GET['q'] ?? '');
$status = sanitize_string($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$per    = 20;

$where  = ["u.role != 'super_admin'"];
$params = [];
if ($search) { $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)"; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($status) { $where[] = "u.status=?"; $params[] = $status; }
$ws = implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM users u WHERE $ws")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM users u WHERE $ws")->execute($params) : 0;
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $ws"); $cnt_stmt->execute($params); $total = (int)$cnt_stmt->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/users?page={page}'.($search?'&q='.urlencode($search):'').($status?'&status='.urlencode($status):''));

$stmt = $db->prepare("SELECT u.*, (SELECT COALESCE(SUM(points),0) FROM wallet_ledger wl JOIN wallets w ON w.id=wl.wallet_id WHERE w.user_id=u.id AND wl.direction='credit' AND wl.status='completed') - (SELECT COALESCE(SUM(points),0) FROM wallet_ledger wl JOIN wallets w ON w.id=wl.wallet_id WHERE w.user_id=u.id AND wl.direction='debit' AND wl.status='completed') AS balance_pts FROM users u WHERE $ws ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']]));
$users = $stmt->fetchAll();

layout_begin_admin('Pengurusan Pengguna');
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div class="page-title" style="margin:0;">👤 Pengurusan Pengguna</div>
</div>

<!-- Filters -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;">
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari nama, e-mel, telefon..." class="input-kasih" style="flex:1;">
    <select name="status" class="select-kasih" style="width:140px;">
      <option value="">Semua Status</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>Aktif</option>
      <option value="pending" <?= $status==='pending'?'selected':'' ?>>Tertangguh</option>
      <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Digantung</option>
    </select>
    <button type="submit" class="btn-gold btn-sm">Cari</button>
    <?php if ($search || $status): ?><a href="<?= APP_URL ?>/admin/users" class="btn-gold-outline btn-sm">Reset</a><?php endif; ?>
  </form>
</div>

<div style="font-size:0.82rem;color:#6B7280;margin-bottom:12px;">Jumlah: <?= $total ?> pengguna</div>

<?= flash_html('main') ?>

<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih searchable-table">
      <thead><tr><th>ID</th><th>Nama</th><th>E-mel</th><th>Telefon</th><th>Peranan</th><th>Status</th><th>Baki Mata</th><th>Daftar</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $u['id'] ?></td>
          <td><?= h($u['full_name']) ?></td>
          <td style="font-size:0.82rem;"><?= h($u['email']) ?></td>
          <td style="font-size:0.82rem;"><?= h($u['phone'] ?? '—') ?></td>
          <td><?= status_badge($u['role']) ?></td>
          <td><?= status_badge($u['status']) ?></td>
          <td style="font-weight:600;color:var(--gold-dark);"><?= gold_format_points($u['balance_pts'] ?? '0') ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($u['created_at'],'d M Y') ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <?php if ($u['status'] !== 'active'): ?>
                <button name="action" value="activate" class="btn-gold btn-sm" style="padding:3px 8px;">Aktif</button>
              <?php endif; ?>
              <?php if ($u['status'] === 'active'): ?>
                <button name="action" value="suspend" class="btn-danger btn-sm" style="padding:3px 8px;" onclick="return confirm('Gantung pengguna ini?')">Gantung</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
