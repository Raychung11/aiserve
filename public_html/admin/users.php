<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$roles = ['member','agent','property_partner','bank_partner','biz_partner','affiliate','admin','super_admin'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($uid && $uid !== (int)$_SESSION['user_id']) {
        if ($action === 'change_role' && in_array($_POST['role'] ?? '', $roles, true)) {
            $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$_POST['role'], $uid]);
            flash('success', 'User role updated.');
        } elseif ($action === 'toggle_status') {
            $user = $pdo->prepare('SELECT status FROM users WHERE id=?');
            $user->execute([$uid]);
            $cur = $user->fetchColumn();
            $new = $cur === 'active' ? 'suspended' : 'active';
            $pdo->prepare('UPDATE users SET status=? WHERE id=?')->execute([$new, $uid]);
            flash('success', "User status set to $new.");
        }
        log_activity("admin_user_$action", 'user', $uid);
    }
    redirect('admin/users');
}

$search     = trim($_GET['q'] ?? '');
$role_filter= $_GET['role'] ?? '';
$page       = max(1,(int)($_GET['p'] ?? 1));
$per        = 25;
$where      = ['1=1']; $params = [];
if ($search) { $where[]='(u.full_name LIKE ? OR u.email LIKE ?)'; $s="%$search%"; $params=[$s,$s]; }
if ($role_filter && in_array($role_filter,$roles,true)) { $where[]='u.role=?'; $params[]=$role_filter; }
$wSQL = implode(' AND ', $where);
$total = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $wSQL");
$total->execute($params);
$pg = paginate((int)$total->fetchColumn(), $per, $page);
$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM mm2h_cases c WHERE c.applicant_id=u.id) AS case_count FROM users u WHERE $wSQL ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pg['offset']]));
$users = $stmt->fetchAll();

$page_title = 'Users — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-people me-2 text-gold"></i>Users</h1></div>
    <?php render_flash(); ?>
    <form method="GET" class="row g-2 mb-4">
      <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search name or email…" value="<?= h($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="role" class="form-select">
          <option value="">All roles</option>
          <?php foreach ($roles as $r): ?>
          <option value="<?=$r?>" <?=$role_filter===$r?'selected':''?>><?= ucwords(str_replace('_',' ',$r)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-gold"><i class="bi bi-search me-1"></i>Search</button>
        <a href="<?= APP_URL ?>/admin/users" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Cases</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $i => $u): ?>
          <tr>
            <td class="text-muted small"><?= $pg['offset']+$i+1 ?></td>
            <td class="fw-semibold"><?= h($u['full_name']) ?></td>
            <td class="small"><?= h($u['email']) ?></td>
            <td><?= status_badge($u['role']) ?></td>
            <td><?= status_badge($u['status']) ?></td>
            <td class="text-center"><?= (int)$u['case_count'] ?></td>
            <td class="text-muted small"><?= format_date($u['created_at']) ?></td>
            <td>
              <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <div class="d-flex gap-1">
                <!-- Change role -->
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="change_role">
                  <select name="role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?=$r?>" <?=$u['role']===$r?'selected':''?>><?= ucwords(str_replace('_',' ',$r)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <!-- Toggle status -->
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="toggle_status">
                  <button class="btn btn-sm <?= $u['status']==='active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                    <?= $u['status']==='active' ? 'Suspend' : 'Activate' ?>
                  </button>
                </form>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?><tr><td colspan="8" class="text-center py-4 text-muted">No users found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['total_pages']>1): ?>
    <nav class="mt-3"><ul class="pagination">
      <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?>
      <li class="page-item <?=$i===$page?'active':''?>">
        <a class="page-link" href="?q=<?=urlencode($search)?>&role=<?=urlencode($role_filter)?>&p=<?=$i?>"><?=$i?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
