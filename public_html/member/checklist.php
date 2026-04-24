<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo  = db();
$uid  = (int)$_SESSION['user_id'];
$lang = current_lang();
$name_col = $lang === 'en' ? 'name_en' : ($lang === 'zh_hant' ? 'name_zh_hant' : 'name_zh_hans');

$checklist = $pdo->query('SELECT * FROM document_checklists WHERE status="active" ORDER BY sort_order')->fetchAll();

$docs_stmt = $pdo->prepare('SELECT checklist_id, status FROM documents WHERE user_id=? ORDER BY uploaded_at DESC');
$docs_stmt->execute([$uid]);
$uploaded = [];
foreach ($docs_stmt->fetchAll() as $d) {
    if (!isset($uploaded[$d['checklist_id']])) $uploaded[$d['checklist_id']] = $d['status'];
}

$required_total    = count(array_filter($checklist, fn($i) => $i['required']));
$required_verified = 0;
foreach ($checklist as $item) {
    if ($item['required'] && ($uploaded[$item['id']] ?? '') === 'verified') $required_verified++;
}
$progress_pct = $required_total ? round(($required_verified / $required_total) * 100) : 0;

$page_title = 'Document Checklist — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h1><i class="bi bi-list-check me-2 text-gold"></i>Document Checklist</h1>
      <a href="<?= APP_URL ?>/member/documents" class="btn btn-gold btn-sm">
        <i class="bi bi-upload me-1"></i>Upload Documents
      </a>
    </div>

    <!-- Progress bar -->
    <div class="mm2h-form-card mb-4">
      <div class="d-flex justify-content-between mb-2">
        <span class="fw-semibold">Required Documents Progress</span>
        <span class="fw-bold text-gold"><?= $required_verified ?>/<?= $required_total ?> Verified</span>
      </div>
      <div class="progress" style="height:12px;border-radius:6px;">
        <div class="progress-bar" style="width:<?= $progress_pct ?>%;background:var(--secondary);border-radius:6px;transition:width .5s ease;"></div>
      </div>
      <div class="small text-muted mt-2"><?= $progress_pct ?>% of required documents verified</div>
    </div>

    <div class="row g-3">
      <?php foreach ($checklist as $item):
        $status = $uploaded[$item['id']] ?? 'not_uploaded';
        $colors = [
          'verified'     => ['success', 'bi-check-circle-fill', 'Verified'],
          'pending'      => ['warning',  'bi-clock-fill',        'Pending Review'],
          'rejected'     => ['danger',   'bi-x-circle-fill',     'Rejected'],
          'resubmit'     => ['info',     'bi-arrow-repeat',      'Resubmit'],
          'not_uploaded' => ['secondary','bi-circle',            'Not Uploaded'],
        ];
        [$bcolor, $bicon, $blabel] = $colors[$status] ?? $colors['not_uploaded'];
      ?>
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded border border-<?= $bcolor === 'secondary' ? 'light' : $bcolor ?>">
          <i class="bi <?= $bicon ?> fs-4 text-<?= $bcolor ?>"></i>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold small"><?= h($item[$name_col] ?: $item['name_en']) ?></div>
            <div class="d-flex align-items-center gap-2 mt-1">
              <?php if ($item['required']): ?>
              <span class="badge bg-danger" style="font-size:.6rem;">Required</span>
              <?php else: ?>
              <span class="badge bg-secondary" style="font-size:.6rem;">Optional</span>
              <?php endif; ?>
              <span class="badge bg-<?= $bcolor ?>" style="font-size:.6rem;"><?= $blabel ?></span>
            </div>
          </div>
          <?php if ($status === 'not_uploaded' || in_array($status, ['rejected','resubmit'])): ?>
          <a href="<?= APP_URL ?>/member/documents" class="btn btn-sm btn-outline-gold flex-shrink-0">
            <i class="bi bi-upload"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
