<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$search  = trim($_GET['q'] ?? '');
$purpose = $_GET['purpose'] ?? '';
$page    = max(1, (int)($_GET['p'] ?? 1));
$per     = 20;

$where  = ["u.role = 'member'"];
$params = [];
if ($search) {
    $where[]  = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}
if ($purpose) {
    $where[]  = "mp.purpose = ?";
    $params[] = $purpose;
}
$whereSQL = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN member_profiles mp ON mp.user_id=u.id WHERE $whereSQL");
$total->execute($params);
$pagination = paginate((int)$total->fetchColumn(), $per, $page);

$stmt = $pdo->prepare(
  "SELECT u.id, u.full_name, u.email, u.phone, u.created_at, u.status,
          mp.nationality, mp.purpose, mp.subscription_plan, mp.onboarding_completed,
          (SELECT COUNT(*) FROM mm2h_cases c WHERE c.applicant_id=u.id) AS case_count
   FROM users u
   LEFT JOIN member_profiles mp ON mp.user_id=u.id
   WHERE $whereSQL
   ORDER BY u.created_at DESC
   LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$per, $pagination['offset']]));
$leads = $stmt->fetchAll();

// Convert to lead action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($_POST['action'] === 'create_case' && $uid) {
        $exists = $pdo->prepare('SELECT id FROM mm2h_cases WHERE applicant_id=?');
        $exists->execute([$uid]);
        if (!$exists->fetch()) {
            $cn = generate_case_number();
            $pdo->prepare(
              'INSERT INTO mm2h_cases (case_number, applicant_id, current_status) VALUES (?,?,?)'
            )->execute([$cn, $uid, 'new_lead']);
            log_activity('admin_create_case', 'user', $uid);
            flash('success', "Case $cn created.");
        } else {
            flash('warning', 'A case already exists for this user.');
        }
    }
    redirect('admin/leads');
}

$page_title = 'Leads — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-funnel me-2 text-gold"></i>Leads</h1>
    </div>
    <?php render_flash(); ?>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-4">
      <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Search name, email, phone…" value="<?= h($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="purpose" class="form-select">
          <option value="">All purposes</option>
          <?php foreach (['retirement','business','family','investment','property','education'] as $p): ?>
          <option value="<?= $p ?>" <?= $purpose === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-gold" type="submit"><i class="bi bi-search me-1"></i><?= t('search') ?></button>
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-outline-secondary"><?= t('cancel') ?></a>
      </div>
    </form>

    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Email / Phone</th><th>Nationality</th>
            <th>Purpose</th><th>Plan</th><th>Cases</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $i => $l): ?>
          <tr>
            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
            <td>
              <div class="fw-semibold"><?= h($l['full_name']) ?></div>
              <?php if ($l['onboarding_completed']): ?>
              <span class="badge bg-success" style="font-size:.65rem;">Profile Complete</span>
              <?php endif; ?>
            </td>
            <td>
              <div><?= h($l['email']) ?></div>
              <small class="text-muted"><?= h($l['phone'] ?? '—') ?></small>
            </td>
            <td><?= h($l['nationality'] ?? '—') ?></td>
            <td><?= h(ucfirst($l['purpose'] ?? '—')) ?></td>
            <td><?= status_badge($l['subscription_plan'] ?? 'free') ?></td>
            <td class="text-center"><?= (int)$l['case_count'] ?></td>
            <td class="text-muted small"><?= format_date($l['created_at']) ?></td>
            <td>
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $l['id'] ?>">
                <input type="hidden" name="action" value="create_case">
                <button class="btn btn-sm btn-gold" <?= $l['case_count'] > 0 ? 'disabled' : '' ?>
                        title="<?= $l['case_count'] > 0 ? 'Case exists' : 'Create MM2H Case' ?>">
                  <i class="bi bi-folder-plus"></i>
                </button>
              </form>
              <a href="<?= APP_URL ?>/admin/users?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-secondary ms-1">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$leads): ?>
          <tr><td colspan="9" class="text-center py-4 text-muted">No leads found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
      <ul class="pagination">
        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($search) ?>&purpose=<?= urlencode($purpose) ?>&p=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
