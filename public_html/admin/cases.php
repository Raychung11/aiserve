<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$case_statuses = [
  'new_lead','initial_consult','doc_collection','eligibility_review',
  'submitted_agent','gov_processing','conditional','fd_stage',
  'medical','final_approval','completed','rejected',
];

// Handle POST (update case)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $case_id    = (int)($_POST['case_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $notes      = trim($_POST['notes'] ?? '');
    $next_action= trim($_POST['next_action'] ?? '');
    $due_date   = $_POST['due_date'] ?? null;

    if ($case_id && in_array($new_status, $case_statuses, true)) {
        $pdo->prepare(
          'UPDATE mm2h_cases SET current_status=?, next_action=?, due_date=?, updated_at=NOW() WHERE id=?'
        )->execute([$new_status, $next_action, $due_date ?: null, $case_id]);
        if ($notes) {
            $pdo->prepare(
              'INSERT INTO case_notes (case_id, author_id, note) VALUES (?,?,?)'
            )->execute([$case_id, $_SESSION['user_id'], $notes]);
        }
        log_activity('admin_update_case', 'case', $case_id);
        flash('success', 'Case updated successfully.');
    }
    redirect('admin/cases');
}

// Single case view
$view_id = (int)($_GET['id'] ?? 0);
$single_case = null;
$case_notes_list = [];
if ($view_id) {
    $stmt = $pdo->prepare(
      'SELECT c.*, u.full_name, u.email, a.full_name AS agent_name
       FROM mm2h_cases c
       JOIN users u ON u.id=c.applicant_id
       LEFT JOIN users a ON a.id=c.assigned_agent_id
       WHERE c.id=?'
    );
    $stmt->execute([$view_id]);
    $single_case = $stmt->fetch();
    if ($single_case) {
        $notes_stmt = $pdo->prepare(
          'SELECT n.*, u.full_name AS author FROM case_notes n
           JOIN users u ON u.id=n.author_id WHERE n.case_id=? ORDER BY n.created_at DESC'
        );
        $notes_stmt->execute([$view_id]);
        $case_notes_list = $notes_stmt->fetchAll();
    }
}

// Case list
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = 20;
$where  = ['1=1'];
$params = [];
if ($status_filter && in_array($status_filter, $case_statuses, true)) {
    $where[] = 'c.current_status = ?';
    $params[] = $status_filter;
}
if ($search) {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR c.case_number LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s]);
}
$whereSQL = implode(' AND ', $where);
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM mm2h_cases c JOIN users u ON u.id=c.applicant_id WHERE $whereSQL");
$total_stmt->execute($params);
$pagination = paginate((int)$total_stmt->fetchColumn(), $per, $page);
$cases_stmt = $pdo->prepare(
  "SELECT c.*, u.full_name, u.email FROM mm2h_cases c
   JOIN users u ON u.id=c.applicant_id
   WHERE $whereSQL ORDER BY c.updated_at DESC LIMIT ? OFFSET ?"
);
$cases_stmt->execute(array_merge($params, [$per, $pagination['offset']]));
$cases = $cases_stmt->fetchAll();

$page_title = 'MM2H Cases — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">

    <?php if ($single_case): ?>
    <!-- Single case detail -->
    <div class="page-header">
      <a href="<?= APP_URL ?>/admin/cases" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i>Back to Cases
      </a>
      <h1>Case: <?= h($single_case['case_number']) ?></h1>
    </div>
    <?php render_flash(); ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <!-- Update form -->
        <div class="mm2h-form-card mb-4">
          <h5 class="mb-4">Update Case Status</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="case_id" value="<?= $single_case['id'] ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Current Status</label>
                <select name="new_status" class="form-select">
                  <?php foreach ($case_statuses as $s): ?>
                  <option value="<?= $s ?>" <?= $s === $single_case['current_status'] ? 'selected' : '' ?>>
                    <?= ucwords(str_replace('_',' ',$s)) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?= h($single_case['due_date'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Next Action</label>
                <input type="text" name="next_action" class="form-control" value="<?= h($single_case['next_action'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Add Note (internal)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Internal note…"></textarea>
              </div>
              <div class="col-12">
                <button class="btn btn-gold"><?= t('save') ?></button>
              </div>
            </div>
          </form>
        </div>
        <!-- Notes timeline -->
        <div class="mm2h-form-card">
          <h5 class="mb-4">Case Notes</h5>
          <?php if ($case_notes_list): ?>
          <?php foreach ($case_notes_list as $n): ?>
          <div class="border rounded-mm2h p-3 mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="fw-semibold small"><?= h($n['author']) ?></span>
              <span class="text-muted small"><?= time_ago($n['created_at']) ?></span>
            </div>
            <p class="mb-0 small"><?= h($n['note']) ?></p>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-muted">No notes yet.</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="mm2h-form-card mb-4">
          <h6 class="mb-3 fw-bold">Applicant</h6>
          <p class="mb-1"><strong><?= h($single_case['full_name']) ?></strong></p>
          <p class="mb-1 text-muted small"><?= h($single_case['email']) ?></p>
          <hr>
          <div class="d-flex justify-content-between small">
            <span>Status</span><span><?= status_badge($single_case['current_status']) ?></span>
          </div>
          <div class="d-flex justify-content-between small mt-2">
            <span>Agent</span><span><?= h($single_case['agent_name'] ?? 'Unassigned') ?></span>
          </div>
          <div class="d-flex justify-content-between small mt-2">
            <span>Due Date</span><span><?= format_date($single_case['due_date']) ?></span>
          </div>
          <div class="d-flex justify-content-between small mt-2">
            <span>Created</span><span><?= format_date($single_case['created_at']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- Cases list -->
    <div class="page-header"><h1><i class="bi bi-folder2-open me-2 text-gold"></i>MM2H Cases</h1></div>
    <?php render_flash(); ?>
    <form method="GET" class="row g-2 mb-4">
      <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search case #, name, email…" value="<?= h($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <?php foreach ($case_statuses as $s): ?>
          <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-gold"><i class="bi bi-search me-1"></i>Filter</button>
        <a href="<?= APP_URL ?>/admin/cases" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>Case #</th><th>Applicant</th><th>Agent</th><th>Status</th><th>Next Action</th><th>Due</th><th>Updated</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($cases as $c): ?>
          <tr>
            <td><a href="?id=<?= $c['id'] ?>" class="text-gold fw-bold"><?= h($c['case_number']) ?></a></td>
            <td>
              <div><?= h($c['full_name']) ?></div>
              <small class="text-muted"><?= h($c['email']) ?></small>
            </td>
            <td class="text-muted small">—</td>
            <td><?= status_badge($c['current_status']) ?></td>
            <td class="small"><?= h($c['next_action'] ?? '—') ?></td>
            <td class="small text-muted"><?= format_date($c['due_date']) ?></td>
            <td class="text-muted small"><?= time_ago($c['updated_at']) ?></td>
            <td><a href="?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-gold">View</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$cases): ?><tr><td colspan="8" class="text-center py-4 text-muted">No cases found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
    <nav class="mt-3"><ul class="pagination">
      <?php for ($i=1;$i<=$pagination['total_pages'];$i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_filter)?>&p=<?=$i?>"><?=$i?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
