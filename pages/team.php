<?php
require_once __DIR__ . '/../includes/auth_check.php';

$myRole = $currentUser['role'];
if (!in_array($myRole, ['principal', 'associate', 'admin'])) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$pageTitle = 'Team Management';
$success   = '';
$error     = '';

// Determine what sub-roles I can create
$canCreate = match ($myRole) {
    'principal' => 'associate',
    'associate' => 'manager',
    'admin'     => 'associate',
    default     => null,
};

$subRoleLabel = match ($canCreate) {
    'associate' => 'Associate',
    'manager'   => 'Manager',
    default     => '',
};

// Handle invite / create team member
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh.';
    } elseif ($_POST['action'] === 'add_member') {
        $inviteName  = trim($_POST['invite_name']  ?? '');
        $inviteEmail = trim($_POST['invite_email'] ?? '');
        $tempPass    = trim($_POST['temp_password'] ?? '');

        if (!$inviteName || !$inviteEmail || !$tempPass) {
            $error = 'All fields are required.';
        } elseif (!filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($tempPass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $result = Auth::register($inviteName, $inviteEmail, $tempPass, $canCreate);
            if ($result['success']) {
                // Set parent_id
                Database::query(
                    'UPDATE users SET parent_id = ? WHERE id = ?',
                    [$currentUser['id'], $result['user_id']]
                );
                $success = ucfirst($canCreate) . ' "' . htmlspecialchars($inviteName) . '" added successfully. They can log in with the temporary password you set.';
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($_POST['action'] === 'remove_member') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        // Verify member is a direct report
        $member = Database::fetchOne('SELECT id, name FROM users WHERE id = ? AND parent_id = ?', [$memberId, $currentUser['id']]);
        if ($member) {
            Database::query('UPDATE users SET is_active = 0 WHERE id = ?', [$memberId]);
            $success = '"' . htmlspecialchars($member['name']) . '" has been removed from your team.';
        } else {
            $error = 'Member not found.';
        }
    }
}

// Load team
$team = Hierarchy::getDirectReports($currentUser['id']);
foreach ($team as &$member) {
    if ($myRole === 'principal') {
        $member['sub_count']     = count(Hierarchy::getDirectReports($member['id']));
        $member['company_count'] = count(Hierarchy::getAssociateCompanies($member['id']));
    } else {
        $member['company_count'] = count(Hierarchy::getDirectCompanies($member['id']));
    }
}
unset($member);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-people-fill me-2 text-primary"></i>Team Management</h1>
        <span class="topbar-subtitle">
          <?php if ($myRole === 'principal'): ?>
            Manage your associates
          <?php else: ?>
            Manage your managers
          <?php endif; ?>
        </span>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <div class="row g-4">

        <!-- Current team -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">
                <i class="bi bi-people me-2"></i>
                My <?= $subRoleLabel ?>s (<?= count($team) ?>)
              </h5>
            </div>
            <?php if (empty($team)): ?>
            <div class="card-body text-center py-5 text-muted">
              <i class="bi bi-person-plus fs-2 d-block mb-2"></i>
              No <?= strtolower($subRoleLabel) ?>s added yet. Use the form to add your first.
            </div>
            <?php else: ?>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <?php if ($myRole === 'principal'): ?>
                      <th class="text-center">Managers</th>
                      <?php endif; ?>
                      <th class="text-center">Clients</th>
                      <th>Joined</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($team as $member): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <div class="hier-avatar hier-avatar-<?= htmlspecialchars($member['role']) ?>">
                            <?= strtoupper(substr($member['name'], 0, 1)) ?>
                          </div>
                          <div>
                            <div class="fw-semibold"><?= htmlspecialchars($member['name']) ?></div>
                            <span class="badge bg-light text-dark border"><?= ucfirst($member['role']) ?></span>
                          </div>
                        </div>
                      </td>
                      <td class="text-muted small"><?= htmlspecialchars($member['email']) ?></td>
                      <?php if ($myRole === 'principal'): ?>
                      <td class="text-center"><span class="badge bg-secondary"><?= $member['sub_count'] ?></span></td>
                      <?php endif; ?>
                      <td class="text-center"><span class="badge bg-info text-dark"><?= $member['company_count'] ?></span></td>
                      <td class="text-muted small"><?= date('d M Y', strtotime($member['created_at'])) ?></td>
                      <td>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove this team member?')">
                          <input type="hidden" name="csrf_token"  value="<?= Auth::csrfToken() ?>">
                          <input type="hidden" name="action"      value="remove_member">
                          <input type="hidden" name="member_id"   value="<?= $member['id'] ?>">
                          <button type="submit" class="btn btn-xs btn-outline-danger">
                            <i class="bi bi-person-x"></i> Remove
                          </button>
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

        <!-- Add member form -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">
                <i class="bi bi-person-plus-fill me-2 text-primary"></i>Add <?= $subRoleLabel ?>
              </h5>
            </div>
            <div class="card-body">
              <p class="text-muted small">
                <?php if ($myRole === 'principal'): ?>
                Add an accounting firm, COSEC firm, or senior consultant as an associate under your firm.
                <?php else: ?>
                Add a staff analyst or junior consultant as a manager to handle specific client assignments.
                <?php endif; ?>
              </p>
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="action"     value="add_member">

                <div class="mb-3">
                  <label class="form-label fw-semibold">Full Name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" name="invite_name" placeholder="Ahmad bin Ali" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Work Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" name="invite_email" placeholder="ahmad@firm.com.my" required>
                  </div>
                </div>
                <div class="mb-4">
                  <label class="form-label fw-semibold">Temporary Password</label>
                  <input type="text" class="form-control" name="temp_password"
                         placeholder="Min 8 chars — share securely" required minlength="8">
                  <div class="form-text">They should change this after first login.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-person-check me-2"></i>Add <?= $subRoleLabel ?>
                </button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
