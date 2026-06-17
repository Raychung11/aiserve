<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$error   = '';
$success = '';

// Ensure table exists (auto-install migration)
try {
    $pdo->query("SELECT 1 FROM mm2h_faqs LIMIT 1");
} catch (PDOException $e) {
    try {
        $sql = file_get_contents(__DIR__ . '/../db/migration_faq.sql');
        $pdo->exec($sql);
        $success = 'FAQ table created and seeded with IMI data.';
    } catch (PDOException $e2) {
        $error = 'Could not create FAQ table: ' . $e2->getMessage();
    }
}

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    verify_csrf();
    $pdo->prepare("DELETE FROM mm2h_faqs WHERE id = ?")->execute([$id]);
    flash('success', 'FAQ entry deleted.');
    header('Location: ' . APP_URL . '/admin/mm2h-faq');
    exit;
}

// ── TOGGLE ACTIVE ─────────────────────────────────────────────────────────────
if ($action === 'toggle' && $id) {
    verify_csrf();
    $pdo->prepare("UPDATE mm2h_faqs SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    header('Location: ' . APP_URL . '/admin/mm2h-faq');
    exit;
}

// ── SAVE (Create / Edit) ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'edit'])) {
    verify_csrf();
    $fields = [
        'category'           => trim($_POST['category']           ?? ''),
        'question'           => trim($_POST['question']           ?? ''),
        'answer'             => trim($_POST['answer']             ?? ''),
        'processing_time'    => trim($_POST['processing_time']    ?? ''),
        'required_documents' => trim($_POST['required_documents'] ?? ''),
        'fees'               => trim($_POST['fees']               ?? ''),
        'sort_order'         => (int)($_POST['sort_order']        ?? 0),
        'is_active'          => isset($_POST['is_active']) ? 1 : 0,
    ];

    if (!$fields['category'] || !$fields['question'] || !$fields['answer']) {
        $error = 'Category, question, and answer are required.';
    } else {
        if ($action === 'create') {
            $pdo->prepare(
                "INSERT INTO mm2h_faqs (category, question, answer, processing_time, required_documents, fees, sort_order, is_active)
                 VALUES (:category, :question, :answer, :processing_time, :required_documents, :fees, :sort_order, :is_active)"
            )->execute($fields);
            flash('success', 'FAQ entry created.');
        } else {
            $pdo->prepare(
                "UPDATE mm2h_faqs SET category=:category, question=:question, answer=:answer,
                 processing_time=:processing_time, required_documents=:required_documents,
                 fees=:fees, sort_order=:sort_order, is_active=:is_active WHERE id=:id"
            )->execute(array_merge($fields, ['id' => $id]));
            flash('success', 'FAQ entry updated.');
        }
        header('Location: ' . APP_URL . '/admin/mm2h-faq');
        exit;
    }
}

// ── LOAD FOR EDIT ─────────────────────────────────────────────────────────────
$edit_row = null;
if ($action === 'edit' && $id) {
    $edit_row = $pdo->prepare("SELECT * FROM mm2h_faqs WHERE id = ?")->execute([$id])
                  ? $pdo->prepare("SELECT * FROM mm2h_faqs WHERE id = ?") : null;
    $stmt = $pdo->prepare("SELECT * FROM mm2h_faqs WHERE id = ?");
    $stmt->execute([$id]);
    $edit_row = $stmt->fetch();
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$filter_cat = trim($_GET['cat'] ?? '');
$search     = trim($_GET['q']   ?? '');
$where = ['1=1'];
$params = [];
if ($filter_cat) { $where[] = 'category = ?'; $params[] = $filter_cat; }
if ($search)     { $where[] = '(question LIKE ? OR answer LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("SELECT * FROM mm2h_faqs WHERE " . implode(' AND ', $where) . " ORDER BY sort_order ASC, id ASC");
$stmt->execute($params);
$faqs = $stmt->fetchAll();

// All distinct categories for filter
$cats = $pdo->query("SELECT DISTINCT category FROM mm2h_faqs ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Count per category for stats
$cat_counts = [];
foreach ($pdo->query("SELECT category, COUNT(*) as n FROM mm2h_faqs GROUP BY category")->fetchAll() as $r) {
    $cat_counts[$r['category']] = (int)$r['n'];
}
$total   = array_sum($cat_counts);
$active  = (int)$pdo->query("SELECT COUNT(*) FROM mm2h_faqs WHERE is_active=1")->fetchColumn();

$page_title = 'MM2H FAQ Manager — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">

    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <h1><i class="bi bi-question-circle me-2 text-gold"></i>MM2H FAQ Manager</h1>
        <p class="text-muted mb-0">Manage application guide entries shown on the public MM2H Guide page.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/mm2h-guide" target="_blank" class="btn btn-outline-gold btn-sm">
          <i class="bi bi-box-arrow-up-right me-1"></i>View Public Page
        </a>
        <a href="?action=create" class="btn btn-gold btn-sm">
          <i class="bi bi-plus-lg me-1"></i>Add FAQ
        </a>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php echo get_flash() ?>

    <!-- ── STATS ──────────────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#C8A03C18;color:#C8A03C"><i class="bi bi-list-ul"></i></div>
          <div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total FAQs</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#19875418;color:#198754"><i class="bi bi-eye-fill"></i></div>
          <div><div class="stat-value"><?= $active ?></div><div class="stat-label">Active</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#1B496518;color:#1B4965"><i class="bi bi-folder2"></i></div>
          <div><div class="stat-value"><?= count($cat_counts) ?></div><div class="stat-label">Categories</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#dc354518;color:#dc3545"><i class="bi bi-eye-slash-fill"></i></div>
          <div><div class="stat-value"><?= $total - $active ?></div><div class="stat-label">Hidden</div></div>
        </div>
      </div>
    </div>

    <?php if (in_array($action, ['create', 'edit'])): ?>
    <!-- ── CREATE / EDIT FORM ───────────────────────────────────────────── -->
    <div class="mm2h-form-card mb-4">
      <h5 class="mb-4">
        <i class="bi bi-<?= $action === 'create' ? 'plus-circle' : 'pencil-square' ?> me-2 text-gold"></i>
        <?= $action === 'create' ? 'Add New FAQ Entry' : 'Edit FAQ Entry' ?>
      </h5>
      <form method="POST" action="?action=<?= $action ?><?= $id ? '&id='.$id : '' ?>">
        <?= csrf_field() ?>
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
            <input list="cat-list" name="category" class="form-control"
                   value="<?= h($edit_row['category'] ?? $_POST['category'] ?? '') ?>" required>
            <datalist id="cat-list">
              <option value="Visa Issuance &amp; Extensions">
              <option value="Permissions &amp; Domestic Helpers">
              <option value="Termination &amp; Changes">
              <option value="Fixed Deposit Withdrawals">
            </datalist>
            <div class="form-text">Pick existing or type a new category name.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Sort Order</label>
            <input type="number" name="sort_order" class="form-control"
                   value="<?= (int)($edit_row['sort_order'] ?? $_POST['sort_order'] ?? 0) ?>">
          </div>

          <div class="col-md-2 d-flex align-items-end pb-1">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                     <?= ($edit_row['is_active'] ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
            <input type="text" name="question" class="form-control"
                   value="<?= h($edit_row['question'] ?? $_POST['question'] ?? '') ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Answer <span class="text-danger">*</span></label>
            <textarea name="answer" class="form-control" rows="5" required><?= h($edit_row['answer'] ?? $_POST['answer'] ?? '') ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Processing Time</label>
            <input type="text" name="processing_time" class="form-control"
                   placeholder="e.g. 30 working days"
                   value="<?= h($edit_row['processing_time'] ?? $_POST['processing_time'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Fees</label>
            <input type="text" name="fees" class="form-control"
                   placeholder="e.g. RM 90 per year"
                   value="<?= h($edit_row['fees'] ?? $_POST['fees'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Required Documents</label>
            <textarea name="required_documents" class="form-control" rows="4"
                      placeholder="Separate each document with a semicolon ( ; )"><?= h($edit_row['required_documents'] ?? $_POST['required_documents'] ?? '') ?></textarea>
            <div class="form-text">Separate documents with semicolons. Example: <em>Valid passport; IMI form; Photographs</em></div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-gold px-4">
              <i class="bi bi-save me-1"></i><?= $action === 'create' ? 'Create Entry' : 'Save Changes' ?>
            </button>
            <a href="<?= APP_URL ?>/admin/mm2h-faq" class="btn btn-outline-secondary">Cancel</a>
          </div>

        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- ── FILTER BAR ────────────────────────────────────────────────────── -->
    <div class="mm2h-form-card mb-3 py-3">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Search</label>
          <input type="text" name="q" class="form-control form-control-sm"
                 placeholder="Search question or answer…" value="<?= h($search) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold mb-1">Category</label>
          <select name="cat" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= h($c) ?>" <?= $filter_cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-gold btn-sm flex-fill">Filter</button>
          <?php if ($filter_cat || $search): ?>
          <a href="<?= APP_URL ?>/admin/mm2h-faq" class="btn btn-outline-secondary btn-sm">Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ── TABLE ─────────────────────────────────────────────────────────── -->
    <div class="mm2h-form-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">Showing <?= count($faqs) ?> of <?= $total ?> entries</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th>Category</th>
              <th>Question</th>
              <th>Processing</th>
              <th style="width:80px;">Order</th>
              <th style="width:80px;">Status</th>
              <th style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($faqs as $f): ?>
            <tr>
              <td class="text-muted small"><?= (int)$f['id'] ?></td>
              <td>
                <span class="badge rounded-pill"
                      style="background:<?= match(true) {
                          str_contains($f['category'], 'Visa')       => '#C8A03C',
                          str_contains($f['category'], 'Permission')  => '#1B4965',
                          str_contains($f['category'], 'Terminat')    => '#dc3545',
                          str_contains($f['category'], 'Fixed')       => '#198754',
                          default                                     => '#6c757d',
                      } ?>22;color:<?= match(true) {
                          str_contains($f['category'], 'Visa')       => '#a07020',
                          str_contains($f['category'], 'Permission')  => '#1B4965',
                          str_contains($f['category'], 'Terminat')    => '#dc3545',
                          str_contains($f['category'], 'Fixed')       => '#198754',
                          default                                     => '#6c757d',
                      } ?>;border:1px solid currentColor;">
                  <?= h($f['category']) ?>
                </span>
              </td>
              <td>
                <div class="fw-semibold small"><?= h(mb_substr($f['question'], 0, 80)) ?><?= mb_strlen($f['question']) > 80 ? '…' : '' ?></div>
                <?php if ($f['answer']): ?>
                <div class="text-muted" style="font-size:.78rem;"><?= h(mb_substr(strip_tags($f['answer']), 0, 80)) ?>…</div>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= h($f['processing_time'] ?? '—') ?></td>
              <td class="text-center small"><?= (int)$f['sort_order'] ?></td>
              <td>
                <?php if ($f['is_active']): ?>
                <span class="badge bg-success-subtle text-success border border-success">Active</span>
                <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border">Hidden</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="?action=edit&id=<?= (int)$f['id'] ?>"
                     class="btn btn-sm btn-outline-secondary" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="?action=toggle&id=<?= (int)$f['id'] ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Active">
                      <i class="bi bi-<?= $f['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                    </button>
                  </form>
                  <form method="POST" action="?action=delete&id=<?= (int)$f['id'] ?>" class="d-inline"
                        onsubmit="return confirm('Delete this FAQ entry?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$faqs): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">No FAQ entries found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
