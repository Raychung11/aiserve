<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/ActionPlanManager.php';
require_once __DIR__ . '/../src/DepartmentManager.php';
require_once __DIR__ . '/../src/NotificationManager.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding'); exit;
}

$pageTitle = 'Action Plans';
$companyId = $activeCompanyId;
$success   = $error = '';
$canCreate = in_array($currentUser['role'], ['admin','principal','associate','manager','consultant']);

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' && $canCreate) {
            $dueRaw = trim($_POST['due_date'] ?? '');
            $id = ActionPlanManager::create([
                'company_id'     => $companyId,
                'indicator_id'   => trim($_POST['indicator_id'] ?? '') ?: null,
                'department_id'  => $_POST['department_id'] ? (int)$_POST['department_id'] : null,
                'created_by'     => $currentUser['id'],
                'assigned_to'    => $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null,
                'title'          => trim($_POST['title'] ?? ''),
                'description'    => trim($_POST['description'] ?? '') ?: null,
                'recommendation' => trim($_POST['recommendation'] ?? '') ?: null,
                'priority'       => $_POST['priority'] ?? 'medium',
                'due_date'       => $dueRaw ?: null,
            ]);
            if ($id && !empty($_POST['assigned_to'])) {
                NotificationManager::create(
                    (int)$_POST['assigned_to'], 'action_plan',
                    'New Action Plan Assigned',
                    $currentUser['name'] . ' assigned you: ' . trim($_POST['title']),
                    APP_URL . '/action-plans',
                    $companyId
                );
            }
            $success = 'Action plan created.';

        } elseif ($action === 'update_status' && isset($_POST['plan_id'])) {
            $plan = ActionPlanManager::getById((int)$_POST['plan_id']);
            if ($plan && $plan['company_id'] == $companyId) {
                ActionPlanManager::update((int)$_POST['plan_id'], ['status' => $_POST['new_status'] ?? 'open']);
                $success = 'Status updated.';
            }
        }
    }
}

if (!empty($_GET['deleted'])) $success = 'Action plan deleted.';
if (!empty($_GET['created'])) $success = 'Action plan created from gap analysis.';

$filterStatus = $_GET['status'] ?? '';
$plans        = ActionPlanManager::getForCompany($companyId, $filterStatus ?: null);
$stats        = ActionPlanManager::getStats($companyId);
$departments  = DepartmentManager::getForCompany($companyId);
$csrf         = Auth::csrfToken();

// Company users for assignment
$companyUsers = Database::fetchAll(
    'SELECT u.id, u.name, u.role FROM user_companies uc JOIN users u ON u.id = uc.user_id WHERE uc.company_id = ? ORDER BY u.name',
    [$companyId]
);

include __DIR__ . '/../includes/header.php';
?>
<style>
.ap-stat-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.ap-stat { background:#fff; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 20px;
           flex:1; min-width:110px; text-align:center; }
.ap-stat-num  { font-size:24px; font-weight:900; color:#0f172a; line-height:1; }
.ap-stat-lbl  { font-size:11px; color:#94a3b8; font-weight:600; margin-top:4px; }
.ap-stat.danger  .ap-stat-num { color:#dc2626; }
.ap-stat.warning .ap-stat-num { color:#d97706; }
.ap-stat.success .ap-stat-num { color:#16a34a; }

.ap-table th { font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em;
               background:#f8fafc; border-bottom:1.5px solid #e2e8f0; padding:10px 14px; }
.ap-table td { font-size:13px; color:#374151; padding:12px 14px; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
.ap-table tr:hover td { background:#f8fafc; }
.overdue-flag { color:#dc2626; font-size:11px; font-weight:700; }

.create-form-wrap { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:24px; }
.create-form-wrap h5 { font-size:15px; font-weight:700; color:#0f172a; margin-bottom:18px; }
.fw-600 { font-weight:600; }
</style>

<div class="app-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title">Action Plans</div>
      <div class="topbar-sub"><?= htmlspecialchars($activeCompany['name']) ?></div>
    </div>
  </div>
  <div class="content-body">

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="ap-stat-row">
      <div class="ap-stat <?= $stats['overdue'] > 0 ? 'danger' : '' ?>">
        <div class="ap-stat-num"><?= $stats['overdue'] ?></div>
        <div class="ap-stat-lbl">Overdue</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-num"><?= $stats['open'] ?></div>
        <div class="ap-stat-lbl">Open</div>
      </div>
      <div class="ap-stat warning">
        <div class="ap-stat-num"><?= $stats['in_progress'] ?></div>
        <div class="ap-stat-lbl">In Progress</div>
      </div>
      <div class="ap-stat success">
        <div class="ap-stat-num"><?= $stats['completed'] ?></div>
        <div class="ap-stat-lbl">Completed</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-num"><?= $stats['total'] ?></div>
        <div class="ap-stat-lbl">Total</div>
      </div>
    </div>

    <?php if ($canCreate): ?>
    <!-- Create form -->
    <div class="create-form-wrap">
      <h5><i class="bi bi-plus-circle me-2 text-primary"></i>New Action Plan</h5>
      <form method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action"     value="create">
        <div class="col-md-6">
          <label class="form-label fw-600 small">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Conduct energy audit for Factory 1" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 small">Priority</label>
          <select name="priority" class="form-select">
            <option value="critical">🔴 Critical</option>
            <option value="high">🟠 High</option>
            <option value="medium" selected>🟡 Medium</option>
            <option value="low">🟢 Low</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-600 small">Due Date</label>
          <input type="date" name="due_date" class="form-control" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600 small">Department</label>
          <select name="department_id" class="form-select">
            <option value="">— No department —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600 small">Assign To</label>
          <select name="assigned_to" class="form-select">
            <option value="">— Unassigned —</option>
            <?php foreach ($companyUsers as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600 small">Related Indicator ID</label>
          <input type="text" name="indicator_id" class="form-control" placeholder="e.g. SEDG-E01 (optional)">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600 small">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="What needs to be done?"></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600 small">Recommendation</label>
          <textarea name="recommendation" class="form-control" rows="2" placeholder="How to approach this?"></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-1"></i>Create Action Plan</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="mb-3">
      <?php foreach ([''=>'All','open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed','deferred'=>'Deferred'] as $v=>$l): ?>
      <a href="?status=<?= $v ?>" class="btn btn-sm me-1 <?= $filterStatus === $v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Plans table -->
    <?php if (empty($plans)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-clipboard2-check" style="font-size:3rem;opacity:.3"></i>
      <p class="mt-3">No action plans yet.<?= $canCreate ? ' Create one above.' : '' ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table ap-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Priority</th>
            <th>Department</th>
            <th>Assigned To</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($plans as $p):
            $isOverdue = $p['status'] !== 'completed' && $p['due_date'] && $p['due_date'] < date('Y-m-d');
        ?>
          <tr>
            <td>
              <a href="<?= url('action-plan-detail') ?>?id=<?= $p['id'] ?>"
                 style="font-weight:600;color:#0f172a;text-decoration:none"
                 class="d-block">
                <?= htmlspecialchars($p['title']) ?>
              </a>
              <?php if ($p['indicator_id']): ?>
              <small class="text-muted">Indicator: <?= htmlspecialchars($p['indicator_id']) ?></small>
              <?php endif; ?>
              <?php if ($p['description']): ?>
              <div style="font-size:12px;color:#64748b;margin-top:2px"><?= htmlspecialchars(mb_substr($p['description'],0,80)) ?><?= strlen($p['description'])>80?'…':'' ?></div>
              <?php endif; ?>
            </td>
            <td><?= ActionPlanManager::priorityBadge($p['priority']) ?></td>
            <td>
              <?php if ($p['department_name']): ?>
              <span style="background:<?= htmlspecialchars($p['department_color'] ?? '#64748b') ?>22;color:<?= htmlspecialchars($p['department_color'] ?? '#64748b') ?>;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700">
                <?= htmlspecialchars($p['department_name']) ?>
              </span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $p['assigned_to_name'] ? htmlspecialchars($p['assigned_to_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
            <td>
              <?php if ($p['due_date']): ?>
                <?= date('d M Y', strtotime($p['due_date'])) ?>
                <?php if ($isOverdue): ?><br><span class="overdue-flag"><i class="bi bi-exclamation-triangle-fill"></i> Overdue</span><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= ActionPlanManager::statusBadge($p['status']) ?></td>
            <td>
              <a href="<?= url('action-plan-detail') ?>?id=<?= $p['id'] ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-right me-1"></i>View
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
