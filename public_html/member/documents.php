<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    verify_csrf();
    $checklist_id = (int)($_POST['checklist_id'] ?? 0);
    $case_stmt    = $pdo->prepare('SELECT id FROM mm2h_cases WHERE applicant_id=? LIMIT 1');
    $case_stmt->execute([$uid]);
    $case_row = $case_stmt->fetch();
    $case_id  = $case_row ? (int)$case_row['id'] : null;

    $result = handle_upload($_FILES['document'], $uid);
    if ($result['success']) {
        $pdo->prepare(
            'INSERT INTO documents (user_id, case_id, checklist_id, file_name, original_name, file_type, file_size, file_path, status)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $uid, $case_id, $checklist_id ?: null,
            $result['file_name'], $result['original_name'],
            $result['file_type'], $result['file_size'],
            $result['file_path'], 'pending',
        ]);
        log_activity('upload_document', 'document', null);
        flash('success', 'Document "' . $result['original_name'] . '" uploaded successfully.');
    } else {
        flash('danger', $result['error']);
    }
    redirect('member/documents');
}

// Fetch checklist items with upload status
$checklist = $pdo->query('SELECT * FROM document_checklists WHERE status="active" ORDER BY sort_order')->fetchAll();
$lang      = current_lang();
$name_col  = $lang === 'en' ? 'name_en' : ($lang === 'zh_hant' ? 'name_zh_hant' : 'name_zh_hans');

// Uploaded docs
$docs_stmt = $pdo->prepare('SELECT d.*, dc.name_en AS checklist_name FROM documents d LEFT JOIN document_checklists dc ON dc.id=d.checklist_id WHERE d.user_id=? ORDER BY d.uploaded_at DESC');
$docs_stmt->execute([$uid]);
$my_docs = $docs_stmt->fetchAll();

// Group by checklist_id
$uploaded_by_checklist = [];
foreach ($my_docs as $doc) {
    $uploaded_by_checklist[$doc['checklist_id']][] = $doc;
}

$page_title = 'Documents — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-folder-fill me-2 text-gold"></i>My Documents</h1>
    </div>
    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Checklist + upload -->
      <div class="col-lg-8">
        <div class="mm2h-form-card">
          <h5 class="mb-4">Document Checklist</h5>
          <?php foreach ($checklist as $item): ?>
          <?php
          $item_docs   = $uploaded_by_checklist[$item['id']] ?? [];
          $latest_doc  = $item_docs[0] ?? null;
          $doc_status  = $latest_doc ? $latest_doc['status'] : null;
          $status_cls  = $doc_status ? "doc-status-$doc_status" : '';
          ?>
          <div class="doc-card mb-3 <?= $status_cls ?>">
            <div class="doc-icon">
              <?php if ($doc_status === 'verified'): ?>✅
              <?php elseif ($doc_status === 'rejected'): ?>❌
              <?php elseif ($doc_status === 'pending'): ?>⏳
              <?php else: ?>📄<?php endif; ?>
            </div>
            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold small"><?= h($item[$name_col] ?: $item['name_en']) ?></div>
              <?php if ($item['required']): ?>
              <span class="badge bg-danger" style="font-size:.6rem;">Required</span>
              <?php else: ?>
              <span class="badge bg-secondary" style="font-size:.6rem;">Optional</span>
              <?php endif; ?>
              <?php if ($latest_doc): ?>
              <div class="small text-muted mt-1">
                <?= h($latest_doc['original_name']) ?> · <?= status_badge($doc_status) ?>
                <?php if ($doc_status === 'rejected' && $latest_doc['review_notes']): ?>
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= h($latest_doc['review_notes']) ?></div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <div class="flex-shrink-0">
              <?php if (!$latest_doc || in_array($doc_status, ['rejected', 'resubmit'], true)): ?>
              <button class="btn btn-sm btn-gold" data-bs-toggle="collapse"
                      data-bs-target="#upload-<?= $item['id'] ?>">
                <i class="bi bi-upload me-1"></i><?= $latest_doc ? 'Re-upload' : 'Upload' ?>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <!-- Upload form (collapsed) -->
          <div class="collapse mb-3" id="upload-<?= $item['id'] ?>">
            <div class="mm2h-form-card" style="background:var(--light);border:1px dashed var(--secondary);">
              <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="checklist_id" value="<?= $item['id'] ?>">
                <div class="d-flex align-items-center gap-3">
                  <input type="file" name="document" class="form-control form-control-sm file-upload-input"
                         data-preview="preview-<?= $item['id'] ?>"
                         accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                  <button type="submit" class="btn btn-gold btn-sm flex-shrink-0">
                    <i class="bi bi-cloud-upload me-1"></i>Submit
                  </button>
                </div>
                <div id="preview-<?= $item['id'] ?>" class="small text-muted mt-1 d-none"></div>
                <div class="form-text">PDF, JPG, PNG, DOC up to 10MB</div>
              </form>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Extra upload (unlisted docs) -->
          <h6 class="mt-4 mb-3">Upload Other Document</h6>
          <div class="mm2h-form-card" style="background:var(--light);border:1px dashed var(--border);">
            <form method="POST" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="d-flex gap-3 align-items-center flex-wrap">
                <input type="file" name="document" class="form-control form-control-sm"
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required style="max-width:320px;">
                <button type="submit" class="btn btn-outline-gold btn-sm">
                  <i class="bi bi-cloud-upload me-1"></i>Upload
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Uploaded files list -->
      <div class="col-lg-4">
        <div class="mm2h-form-card">
          <h6 class="mb-3">All Uploaded Files (<?= count($my_docs) ?>)</h6>
          <?php if ($my_docs): ?>
          <?php foreach ($my_docs as $doc): ?>
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <div style="font-size:1.4rem">
              <?= in_array($doc['file_type'], ['pdf']) ? '📄' : '🖼️' ?>
            </div>
            <div class="min-w-0 flex-grow-1">
              <div class="small fw-semibold text-truncate"><?= h($doc['original_name']) ?></div>
              <div style="font-size:.72rem;" class="text-muted"><?= time_ago($doc['uploaded_at']) ?></div>
            </div>
            <?= status_badge($doc['status']) ?>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-muted small">No documents uploaded yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
