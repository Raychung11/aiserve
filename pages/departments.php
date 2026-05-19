<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/DepartmentManager.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding'); exit;
}

$pageTitle = 'Departments';
$companyId = $activeCompanyId;
$success   = $error = '';

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'setup_defaults') {
            if (!DepartmentManager::hasAnyDepartments($companyId)) {
                DepartmentManager::setupDefaults($companyId);
                $success = '6 default departments created successfully.';
            } else {
                $error = 'Departments already exist for this company.';
            }

        } elseif ($action === 'create') {
            $name  = trim($_POST['name'] ?? '');
            $type  = $_POST['type']  ?? 'custom';
            $color = $_POST['color'] ?? '#64748b';
            $desc  = trim($_POST['description'] ?? '');
            if ($name === '') {
                $error = 'Department name is required.';
            } else {
                DepartmentManager::create($companyId, $name, $type, $color, $desc);
                $success = "Department '{$name}' created.";
            }

        } elseif ($action === 'toggle' && isset($_POST['dept_id'])) {
            $dept = DepartmentManager::getById((int)$_POST['dept_id']);
            if ($dept && $dept['company_id'] == $companyId) {
                DepartmentManager::update((int)$_POST['dept_id'], ['is_active' => $dept['is_active'] ? 0 : 1]);
                $success = 'Department updated.';
            }

        } elseif ($action === 'assign_member' && isset($_POST['dept_id'], $_POST['user_id'])) {
            $dept = DepartmentManager::getById((int)$_POST['dept_id']);
            if ($dept && $dept['company_id'] == $companyId) {
                DepartmentManager::assignUser((int)$_POST['dept_id'], (int)$_POST['user_id'], $_POST['dept_role'] ?? 'member');
                $success = 'Member added to ' . htmlspecialchars($dept['name']) . '.';
            }

        } elseif ($action === 'remove_member' && isset($_POST['dept_id'], $_POST['user_id'])) {
            $dept = DepartmentManager::getById((int)$_POST['dept_id']);
            if ($dept && $dept['company_id'] == $companyId) {
                DepartmentManager::removeUser((int)$_POST['dept_id'], (int)$_POST['user_id']);
                $success = 'Member removed.';
            }
        }
    }
}

$departments  = DepartmentManager::getForCompany($companyId, false);
$hasAny       = count($departments) > 0;
$csrf         = Auth::csrfToken();
$types        = ['hr_admin','production','hse','energy','procurement','logistics','finance','it','custom'];

// Company users for member assignment
$companyUsers = Database::fetchAll(
    'SELECT u.id, u.name, u.role FROM user_companies uc JOIN users u ON u.id = uc.user_id WHERE uc.company_id = ? ORDER BY u.name',
    [$companyId]
);

// Pre-fetch members for each department keyed by dept id
$deptMembers = [];
foreach ($departments as $dept) {
    $deptMembers[$dept['id']] = DepartmentManager::getMembers($dept['id']);
}

include __DIR__ . '/../includes/header.php';
?>
<style>
.dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 16px; }
.dept-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; overflow:hidden; }
.dept-card-top { height:6px; }
.dept-card-body { padding:18px 20px; }
.dept-name { font-size:15px; font-weight:700; color:#0f172a; margin-bottom:4px; }
.dept-type-badge { display:inline-block; background:#f1f5f9; color:#475569; font-size:11px; font-weight:600;
                   padding:2px 8px; border-radius:4px; margin-bottom:8px; }
.dept-desc { font-size:12px; color:#64748b; line-height:1.5; margin-bottom:12px; min-height:36px; }
.dept-meta { display:flex; align-items:center; justify-content:space-between; }
.dept-members { font-size:12px; color:#94a3b8; }
.dept-inactive { opacity:.55; }
.dept-actions { display:flex; gap:6px; }

.setup-banner { background:linear-gradient(135deg,#0f172a,#1e293b); color:#fff; border-radius:16px;
                padding:36px; text-align:center; margin-bottom:28px; }
.setup-banner h3 { font-size:20px; font-weight:800; margin-bottom:8px; }
.setup-banner p  { color:rgba(255,255,255,.7); font-size:14px; margin-bottom:20px; }

.add-form-wrap { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:28px; }
.add-form-wrap h5 { font-size:15px; font-weight:700; color:#0f172a; margin-bottom:16px; }
.color-swatch { width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; cursor:pointer; }
</style>

<div class="app-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title">Departments</div>
      <div class="topbar-sub"><?= htmlspecialchars($activeCompany['name']) ?></div>
    </div>
  </div>
  <div class="content-body">

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!$hasAny): ?>
    <!-- No departments yet -->
    <div class="setup-banner">
      <div style="font-size:48px;margin-bottom:16px">🏢</div>
      <h3>Set Up Your Department Structure</h3>
      <p>Departments organise data collection across your company.<br>
         Start with 6 standard modules or create your own.</p>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="setup_defaults">
        <button type="submit" class="btn btn-success btn-lg px-5">
          <i class="bi bi-magic me-2"></i>Auto-Create 6 Standard Departments
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Add department form -->
    <div class="add-form-wrap">
      <h5><i class="bi bi-plus-circle me-2 text-primary"></i>Add Department</h5>
      <form method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action"     value="create">
        <div class="col-md-4">
          <label class="form-label fw-600 small">Department Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Finance, IT, Marketing" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 small">Type</label>
          <select name="type" class="form-select">
            <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>"><?= DepartmentManager::typeLabel($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 small">Description</label>
          <input type="text" name="description" class="form-control" placeholder="Optional short description">
        </div>
        <div class="col-md-1">
          <label class="form-label fw-600 small">Colour</label>
          <input type="color" name="color" class="form-control form-control-color" value="#0ea5e9" style="height:38px">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Add</button>
        </div>
      </form>
    </div>

    <!-- Department cards -->
    <?php if ($hasAny): ?>
    <div class="dept-grid">
      <?php foreach ($departments as $dept): ?>
      <div class="dept-card <?= $dept['is_active'] ? '' : 'dept-inactive' ?>">
        <div class="dept-card-top" style="background:<?= htmlspecialchars($dept['color']) ?>"></div>
        <div class="dept-card-body">
          <div class="dept-name"><?= htmlspecialchars($dept['name']) ?></div>
          <div class="dept-type-badge"><?= DepartmentManager::typeLabel($dept['type']) ?></div>
          <div class="dept-desc"><?= htmlspecialchars($dept['description'] ?? '') ?: '<em class="text-muted">No description</em>' ?></div>
          <div class="dept-meta">
            <span class="dept-members"><i class="bi bi-people me-1"></i><?= (int)$dept['member_count'] ?> member<?= $dept['member_count'] != 1 ? 's' : '' ?></span>
            <div class="dept-actions">
              <?php if (!$dept['is_active']): ?>
              <span class="badge bg-secondary">Inactive</span>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-outline-primary"
                      onclick="openMembersModal(<?= $dept['id'] ?>)"
                      title="Manage members">
                <i class="bi bi-people-fill"></i>
              </button>
              <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"  value="toggle">
                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $dept['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="<?= $dept['is_active'] ? 'Deactivate' : 'Activate' ?>">
                  <i class="bi bi-<?= $dept['is_active'] ? 'pause' : 'play' ?>-fill"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div><!-- /content-body -->
</div><!-- /main-content -->
</div><!-- /app-layout -->

<!-- ── Manage Members Modal ───────────────────────────────────────────── -->
<div class="modal fade" id="membersModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-people-fill me-2 text-primary"></i><span id="modalDeptName">Department</span> — Members</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">

        <!-- Current members list -->
        <div id="membersList" style="max-height:340px;overflow-y:auto">
          <table class="table table-sm mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th>Name</th>
                <th>Platform Role</th>
                <th>Dept Role</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="membersBody"></tbody>
          </table>
          <div id="noMembers" class="text-center text-muted py-4" style="display:none">
            <i class="bi bi-people fs-3 mb-2"></i>
            <p class="mb-0 small">No members yet. Add one below.</p>
          </div>
        </div>

        <!-- Add member form -->
        <div class="border-top p-3">
          <form method="POST" class="row g-2 align-items-end" id="addMemberForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"   value="assign_member">
            <input type="hidden" name="dept_id"  id="modalDeptId">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Add Member</label>
              <select class="form-select" name="user_id" required id="addUserSelect">
                <option value="">— Select user —</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Role</label>
              <select class="form-select" name="dept_role">
                <option value="member">Member</option>
                <option value="head">Head</option>
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle me-1"></i>Add
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Department members data passed from PHP
const deptData = <?= json_encode(array_map(function($dept) use ($deptMembers) {
    return [
        'id'      => $dept['id'],
        'name'    => $dept['name'],
        'members' => $deptMembers[$dept['id']] ?? [],
    ];
}, $departments)) ?>;

const companyUsers = <?= json_encode(array_map(fn($u) => ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']], $companyUsers)) ?>;
const csrf = <?= json_encode($csrf) ?>;

function openMembersModal(deptId) {
    const dept = deptData.find(d => d.id == deptId);
    if (!dept) return;

    document.getElementById('modalDeptName').textContent = dept.name;
    document.getElementById('modalDeptId').value = deptId;

    // Render current members
    const tbody = document.getElementById('membersBody');
    tbody.innerHTML = '';
    const noMembers = document.getElementById('noMembers');

    if (dept.members.length === 0) {
        noMembers.style.display = '';
        document.getElementById('membersList').querySelector('table').style.display = 'none';
    } else {
        noMembers.style.display = 'none';
        document.getElementById('membersList').querySelector('table').style.display = '';
        dept.members.forEach(m => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escHtml(m.name)}</strong></td>
                <td><span class="badge bg-secondary">${escHtml(m.role)}</span></td>
                <td><span class="badge ${m.dept_role === 'head' ? 'bg-primary' : 'bg-light text-dark border'}">${m.dept_role === 'head' ? 'Head' : 'Member'}</span></td>
                <td>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="${escHtml(csrf)}">
                    <input type="hidden" name="action"   value="remove_member">
                    <input type="hidden" name="dept_id"  value="${deptId}">
                    <input type="hidden" name="user_id"  value="${m.id}">
                    <button type="submit" class="btn btn-xs btn-outline-danger"
                            onclick="return confirm('Remove ${escHtml(m.name)} from this department?')">
                      <i class="bi bi-x"></i>
                    </button>
                  </form>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    // Populate add-user dropdown (exclude current members)
    const memberIds = new Set(dept.members.map(m => m.id));
    const sel = document.getElementById('addUserSelect');
    sel.innerHTML = '<option value="">— Select user —</option>';
    companyUsers.forEach(u => {
        if (!memberIds.has(u.id)) {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name + ' (' + u.role + ')';
            sel.appendChild(opt);
        }
    });

    new bootstrap.Modal(document.getElementById('membersModal')).show();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
