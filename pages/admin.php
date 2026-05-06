<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Admin only
if ($currentUser['role'] !== 'admin') {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$pageTitle = 'Admin Panel';
$success   = $error = '';
$tab       = $_GET['tab'] ?? 'overview';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired.';
    } else {
        $action   = $_POST['action'] ?? '';
        $targetId = (int)($_POST['target_id'] ?? 0);

        if ($action === 'toggle_user' && $targetId && $targetId !== $currentUser['id']) {
            $u = Database::fetchOne('SELECT id, is_active FROM users WHERE id = ?', [$targetId]);
            if ($u) {
                Database::update('users', ['is_active' => $u['is_active'] ? 0 : 1], 'id = ?', [$targetId]);
                $success = 'User status updated.';
                Database::insert('activity_log', [
                    'user_id'     => $currentUser['id'],
                    'action'      => 'ADMIN_TOGGLE_USER',
                    'description' => 'Admin toggled user #' . $targetId . ' active=' . ($u['is_active'] ? 0 : 1),
                    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }
        } elseif ($action === 'set_role' && $targetId && $targetId !== $currentUser['id']) {
            $newRole = $_POST['role'] ?? '';
            if (in_array($newRole, ['admin', 'consultant', 'sme_owner'])) {
                Database::update('users', ['role' => $newRole], 'id = ?', [$targetId]);
                $success = 'User role updated to ' . $newRole . '.';
            }
        } elseif ($action === 'delete_company' && $targetId) {
            $co = Database::fetchOne('SELECT name FROM companies WHERE id = ?', [$targetId]);
            if ($co) {
                Database::query('DELETE FROM companies WHERE id = ?', [$targetId]);
                $success = 'Company "' . htmlspecialchars($co['name']) . '" deleted.';
                $tab = 'companies';
            }
        }
        // Redirect to avoid re-submit
        header('Location: ' . APP_URL . '/admin?tab=' . $tab . '&msg=' . urlencode($success ?: $error));
        exit;
    }
}

if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg']);
}

// --- Data queries ---
$allUsers     = Database::fetchAll('SELECT * FROM users ORDER BY created_at DESC');
$allCompanies = Database::fetchAll(
    'SELECT c.*, u.name AS creator_name,
            (SELECT COUNT(*) FROM user_companies uc WHERE uc.company_id = c.id) AS member_count,
            (SELECT COUNT(*) FROM esg_data ed WHERE ed.company_id = c.id) AS data_count
     FROM companies c
     LEFT JOIN users u ON c.created_by = u.id
     ORDER BY c.created_at DESC'
);
$stats = [
    'users'     => count($allUsers),
    'companies' => count($allCompanies),
    'esg_data'  => (int)(Database::fetchOne('SELECT COUNT(*) AS n FROM esg_data')['n'] ?? 0),
    'reports'   => (int)(Database::fetchOne('SELECT COUNT(*) AS n FROM reports')['n'] ?? 0),
    'logins_today' => (int)(Database::fetchOne(
        "SELECT COUNT(*) AS n FROM activity_log WHERE action='LOGIN' AND DATE(created_at) = CURDATE()"
    )['n'] ?? 0),
];
$recentActivity = Database::fetchAll(
    'SELECT al.*, u.name AS user_name FROM activity_log al
     LEFT JOIN users u ON al.user_id = u.id
     ORDER BY al.created_at DESC LIMIT 30'
);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-shield-lock me-2 text-danger"></i>Admin Panel</h1>
        <span class="topbar-subtitle">System management &bull; <?= date('d M Y') ?></span>
      </div>
      <div class="topbar-actions">
        <span class="badge bg-danger">Admin</span>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Stats row -->
      <div class="row g-3 mb-4">
        <?php
        $statItems = [
            ['label' => 'Total Users',     'value' => $stats['users'],       'icon' => 'bi-people-fill',       'color' => '#16a34a'],
            ['label' => 'Companies',       'value' => $stats['companies'],    'icon' => 'bi-buildings-fill',    'color' => '#0891b2'],
            ['label' => 'ESG Data Points', 'value' => $stats['esg_data'],    'icon' => 'bi-database-fill',     'color' => '#7c3aed'],
            ['label' => 'Reports Generated','value'=> $stats['reports'],     'icon' => 'bi-file-earmark-text', 'color' => '#d97706'],
            ['label' => 'Logins Today',    'value' => $stats['logins_today'],'icon' => 'bi-box-arrow-in-right','color' => '#0d9488'],
        ];
        foreach ($statItems as $si): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="admin-stat-card">
            <i class="bi <?= $si['icon'] ?>" style="color:<?= $si['color'] ?>"></i>
            <div class="admin-stat-num"><?= $si['value'] ?></div>
            <div class="admin-stat-label"><?= $si['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-4" id="adminTabs">
        <li class="nav-item">
          <a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="?tab=overview">
            <i class="bi bi-activity me-1"></i>Activity Log
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="?tab=users">
            <i class="bi bi-people me-1"></i>Users
            <span class="badge bg-secondary ms-1"><?= $stats['users'] ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $tab === 'companies' ? 'active' : '' ?>" href="?tab=companies">
            <i class="bi bi-buildings me-1"></i>Companies
            <span class="badge bg-secondary ms-1"><?= $stats['companies'] ?></span>
          </a>
        </li>
      </ul>

      <?php if ($tab === 'overview'): ?>
      <!-- Activity Log -->
      <div class="card">
        <div class="card-header">Recent Activity (Last 30 events)</div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($recentActivity as $log):
              $actionColors = [
                  'LOGIN' => 'success', 'LOGOUT' => 'secondary',
                  'COMPANY_CREATED' => 'primary', 'ESG_DATA_SAVED' => 'info',
                  'ADMIN_TOGGLE_USER' => 'warning',
              ];
              $badgeClass = $actionColors[$log['action']] ?? 'secondary';
            ?>
              <tr>
                <td class="text-muted small"><?= date('d M, H:i', strtotime($log['created_at'])) ?></td>
                <td><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
                <td><span class="badge bg-<?= $badgeClass ?>"><?= $log['action'] ?></span></td>
                <td class="small"><?= htmlspecialchars($log['description'] ?? '') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php elseif ($tab === 'users'): ?>
      <!-- Users table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>All Users (<?= count($allUsers) ?>)</span>
          <a href="<?= url('register') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person-plus me-1"></i>Add User
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($allUsers as $u):
              $isSelf = $u['id'] === $currentUser['id'];
              $roleColors = ['admin' => 'danger', 'consultant' => 'primary', 'sme_owner' => 'success'];
              $roleClass = $roleColors[$u['role']] ?? 'secondary';
            ?>
              <tr class="<?= $isSelf ? 'table-primary' : '' ?>">
                <td class="text-muted small"><?= $u['id'] ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar-sm"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                    <?= htmlspecialchars($u['name']) ?>
                    <?= $isSelf ? '<span class="badge bg-info ms-1">You</span>' : '' ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <?php if ($isSelf): ?>
                  <span class="badge bg-<?= $roleClass ?>"><?= $u['role'] ?></span>
                  <?php else: ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="set_role">
                    <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                    <select name="role" class="form-select form-select-sm admin-role-select"
                            onchange="this.form.submit()"
                            style="width:auto;display:inline-block;min-width:120px">
                      <?php foreach (['admin','consultant','sme_owner'] as $r): ?>
                      <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['is_active']): ?>
                  <span class="badge bg-success">Active</span>
                  <?php else: ?>
                  <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if (!$isSelf): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="toggle_user">
                    <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-xs <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                      <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php elseif ($tab === 'companies'): ?>
      <!-- Companies table -->
      <div class="card">
        <div class="card-header">All Companies (<?= count($allCompanies) ?>)</div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Company</th>
                <th>Industry</th>
                <th>Revenue</th>
                <th>Framework</th>
                <th>Members</th>
                <th>Data Points</th>
                <th>Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($allCompanies as $co): ?>
              <tr>
                <td class="text-muted small"><?= $co['id'] ?></td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($co['name']) ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($co['creator_name'] ?? '—') ?></div>
                </td>
                <td><?= htmlspecialchars($co['industry']) ?></td>
                <td><?= Benchmarker::revenueTierLabel($co['revenue_tier']) ?></td>
                <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= htmlspecialchars($co['framework']) ?></span></td>
                <td class="text-center"><?= $co['member_count'] ?></td>
                <td class="text-center"><?= $co['data_count'] ?></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($co['created_at'])) ?></td>
                <td>
                  <form method="POST" class="d-inline"
                        onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($co['name'])) ?> and all its data? This cannot be undone.')">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="delete_company">
                    <input type="hidden" name="target_id" value="<?= $co['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
