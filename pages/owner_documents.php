<?php
require_once __DIR__.'/../includes/auth_check.php';

$flash      = [];
$ownerId    = (int)($_GET['owner_id'] ?? 0);
$propertyId = (int)($_GET['property_id'] ?? 0);

// Ensure upload directory exists
$uploadDir = __DIR__.'/../uploads/documents/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

// ── POST HANDLER ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Verify the target property belongs to this tenant
    $targetProp = (int)($_POST['property_id'] ?? 0);
    $prop = Database::fetchOne("SELECT id, owner_id FROM properties WHERE id=? AND tenant_id=? AND deleted_at IS NULL", [$targetProp, $_tenantId]);
    if (!$prop) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Property not found.']; header('Location: '.APP_URL.'/owner-documents'); exit; }

    if ($action === 'upload') {
        $title = trim($_POST['title'] ?? '');
        if (!$title) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Title is required.']; header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit; }

        $filePath = null;
        $mimeType = null;
        $fileSize = null;

        if (!empty($_FILES['document']['name'])) {
            $file      = $_FILES['document'];
            $allowedMime = ['application/pdf','image/jpeg','image/png','image/jpg','image/webp'];

            // Validate mime via finfo (not client-supplied type)
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($realMime, $allowedMime)) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'Only PDF and image files (JPG, PNG, WebP) are allowed.'];
                header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit;
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'File too large. Maximum 10MB.'];
                header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit;
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $filename = date('Ymd_His').'_'.$targetProp.'_'.substr(md5(uniqid()),0,6).'_'.$safeName.'.'.$ext;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir.$filename)) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'Failed to save file. Check uploads/ directory permissions.'];
                header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit;
            }
            $filePath = 'uploads/documents/'.$filename;
            $mimeType = $realMime;
            $fileSize = $file['size'];
        }

        $extUrl = trim($_POST['url'] ?? '');

        Database::insert('owner_documents', [
            'tenant_id'   => $_tenantId,
            'property_id' => $targetProp,
            'title'       => $title,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'file_path'   => $filePath,
            'url'         => ($extUrl && !$filePath) ? $extUrl : null,
            'mime_type'   => $mimeType,
            'file_size'   => $fileSize,
            'uploaded_by' => $_user['id'],
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::record('document.upload', "Uploaded doc '$title' for property #$targetProp", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Document added.'];
        header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit;
    }

    if ($action === 'delete') {
        $docId = (int)($_POST['doc_id'] ?? 0);
        $doc   = Database::fetchOne("SELECT * FROM owner_documents WHERE id=? AND tenant_id=?", [$docId, $_tenantId]);
        if ($doc) {
            if ($doc['file_path'] && file_exists(__DIR__.'/../'.$doc['file_path'])) {
                unlink(__DIR__.'/../'.$doc['file_path']);
            }
            Database::delete('owner_documents', 'id=? AND tenant_id=?', [$docId, $_tenantId]);
            ActivityLog::record('document.delete', "Deleted doc #$docId", $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Document deleted.'];
        }
        header('Location: '.APP_URL.'/owner-documents?property_id='.$targetProp); exit;
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// ── LOAD DATA ─────────────────────────────────────────────────────────────────
// Properties list for filter dropdown
$allProperties = Database::fetchAll(
    "SELECT p.id, p.name, o.name AS owner_name FROM properties p
     LEFT JOIN owners o ON o.id = p.owner_id
     WHERE p.tenant_id=? AND p.deleted_at IS NULL ORDER BY p.name",
    [$_tenantId]
);

// Filter by owner (load their properties)
if ($ownerId) {
    $ownerProps = array_column(
        Database::fetchAll("SELECT id FROM properties WHERE owner_id=? AND tenant_id=? AND deleted_at IS NULL", [$ownerId, $_tenantId]),
        'id'
    );
}

// Documents
$params = [$_tenantId];
$where  = 'd.tenant_id=?';
if ($propertyId) {
    $where  .= ' AND d.property_id=?';
    $params[] = $propertyId;
}

$docs = Database::fetchAll(
    "SELECT d.*, p.name AS property_name, p.owner_id,
            o.name AS owner_name, u.name AS uploader
     FROM owner_documents d
     JOIN properties p ON p.id = d.property_id
     LEFT JOIN owners o ON o.id = p.owner_id
     LEFT JOIN users u ON u.id = d.uploaded_by
     WHERE $where
     ORDER BY d.created_at DESC",
    $params
);

$pageTitle = 'Documents';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Owner Documents</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Upload documents for owners to view in their portal</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Upload Form -->
  <div class="col-lg-4">
    <div class="card-box">
      <h6 class="fw-semibold mb-3 pb-2 border-bottom">Upload Document</h6>
      <form method="POST" action="<?= APP_URL ?>/owner-documents" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="upload">
        <div class="mb-3">
          <label class="form-label fw-semibold">Property</label>
          <select name="property_id" class="form-select" required>
            <option value="">Select property</option>
            <?php foreach ($allProperties as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id']==$propertyId?'selected':'' ?>>
              <?= htmlspecialchars($p['name']) ?><?= $p['owner_name']?' — '.$p['owner_name']:'' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Tenancy Agreement 2025" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
          <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">File <span class="text-muted fw-normal">(PDF / Image, max 10MB)</span></label>
          <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">OR External URL</label>
          <input type="url" name="url" class="form-control" placeholder="https://drive.google.com/...">
          <div class="form-text">Google Drive / Dropbox link — used only if no file uploaded.</div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Upload Document</button>
      </form>
    </div>
  </div>

  <!-- Document List -->
  <div class="col-lg-8">
    <!-- Filter -->
    <div class="card-box mb-3">
      <form method="GET" action="<?= APP_URL ?>/owner-documents" class="row g-2">
        <div class="col-md-6">
          <select name="property_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Properties</option>
            <?php foreach ($allProperties as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id']==$propertyId?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <?php if ($docs): ?>
    <div class="card-box">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th style="font-size:.75rem;">Document</th>
            <th style="font-size:.75rem;">Property / Owner</th>
            <th style="font-size:.75rem;">Size</th>
            <th style="font-size:.75rem;">Uploaded</th>
            <th style="font-size:.75rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($docs as $doc): ?>
          <tr>
            <td>
              <div class="fw-semibold" style="font-size:.85rem;"><i class="bi bi-file-earmark-<?= str_contains($doc['mime_type']??'','image')?'image':'pdf' ?> me-1 text-muted"></i><?= htmlspecialchars($doc['title']) ?></div>
              <?php if ($doc['description']): ?>
              <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($doc['description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;">
              <div><?= htmlspecialchars($doc['property_name']) ?></div>
              <?php if ($doc['owner_name']): ?>
              <div class="text-muted"><?= htmlspecialchars($doc['owner_name']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;" class="text-muted">
              <?= $doc['file_size'] ? number_format($doc['file_size']/1024,0).' KB' : ($doc['url']?'URL':'—') ?>
            </td>
            <td style="font-size:.8rem;"><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?= APP_URL ?>/owner-document-download?id=<?= $doc['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px;" target="_blank"><i class="bi bi-download"></i></a>
                <form method="POST" action="<?= APP_URL ?>/owner-documents" onsubmit="return confirm('Delete this document?')">
                  <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                  <input type="hidden" name="property_id" value="<?= $doc['property_id'] ?>">
                  <button class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:2px 8px;"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card-box text-center py-5">
      <div style="font-size:3rem;">📂</div>
      <h5 class="mt-3 mb-2">No Documents</h5>
      <p class="text-muted">Upload documents using the form on the left.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>
