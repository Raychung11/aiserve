<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$message = '';
$error = '';

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM client_logo_strips WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if (!function_exists('client_logo_upload_file')) {
    function client_logo_upload_file(array $file, string $prefix = 'client_logo'): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) return null;

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        $mime = mime_content_type($tmpPath) ?: '';
        if (!isset($allowedMime[$mime])) return null;

        $ext = $allowedMime[$mime];
        $uploadDir = __DIR__ . '/../uploads/media/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) return null;

        return '/uploads/media/' . $filename;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $companyName = trim((string)($_POST['company_name'] ?? ''));
    $logoImage = trim((string)($_POST['logo_image'] ?? ''));
    $websiteUrl = trim((string)($_POST['website_url'] ?? ''));
    $badgeText = trim((string)($_POST['badge_text'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'draft'));

    if (!empty($_FILES['logo_image_file']['name'])) {
        $uploaded = client_logo_upload_file($_FILES['logo_image_file'], 'client_logo');
        if ($uploaded !== null) {
            $logoImage = $uploaded;
        } else {
            $error = 'Logo upload failed. Please use JPG, PNG, WEBP, GIF, or SVG.';
        }
    }

    if ($error === '') {
        if ($id > 0) {
            $stmt = db()->prepare("
                UPDATE client_logo_strips
                SET company_name=?, logo_image=?, website_url=?, badge_text=?, sort_order=?, status=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$companyName, $logoImage, $websiteUrl, $badgeText, $sortOrder, $status, $id]);
            $message = 'Client logo updated successfully.';
        } else {
            $stmt = db()->prepare("
                INSERT INTO client_logo_strips
                (company_name, logo_image, website_url, badge_text, sort_order, status, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$companyName, $logoImage, $websiteUrl, $badgeText, $sortOrder, $status, $_SESSION['admin_id'] ?? null]);
            $id = (int)db()->lastInsertId();
            $message = 'Client logo created successfully.';
        }

        $stmt = db()->prepare("SELECT * FROM client_logo_strips WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
    }
}

admin_header($id > 0 ? 'Edit Client Logo' : 'Create Client Logo');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;"><?= $id > 0 ? 'Edit Client Logo' : 'Create Client Logo' ?></h3>
            <p class="muted" style="margin:6px 0 0;">Upload logo, add company name, and control public display order.</p>
        </div>
        <div class="pill"><?= $id > 0 ? 'Logo #' . (int)$id : 'New Logo' ?></div>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="card" style="margin-top:20px;border-color:#bbf7d0;background:#ecfdf3;color:#166534;"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="card" style="margin-top:20px;border-color:#fecaca;background:#fef2f2;color:#991b1b;"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_input() ?>

    <div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:20px;" class="client-logo-edit-grid">
        <div style="display:grid;gap:20px;">
            <div class="card">
                <h3 style="margin-top:0;">Client Logo Details</h3>

                <div class="form-grid">
                    <div class="field full">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?= h($row['company_name'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label>Website URL</label>
                        <input type="text" name="website_url" value="<?= h($row['website_url'] ?? '') ?>" placeholder="https://example.com">
                    </div>

                    <div class="field">
                        <label>Badge Text</label>
                        <input type="text" name="badge_text" value="<?= h($row['badge_text'] ?? '') ?>" placeholder="F&B / Travel / Organisation">
                    </div>

                    <div class="field">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="<?= h((string)($row['sort_order'] ?? 0)) ?>">
                    </div>

                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?= ($row['status'] ?? '') === 'draft' ? 'selected' : '' ?>>draft</option>
                            <option value="published" <?= ($row['status'] ?? '') === 'published' ? 'selected' : '' ?>>published</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Logo Upload</h3>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="upload-grid">
                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Drag & Drop Logo</label>

                        <div id="logoDropzone" style="
                            border:2px dashed #d8c9fb;
                            border-radius:18px;
                            padding:24px;
                            background:#faf8ff;
                            text-align:center;
                            cursor:pointer;
                        ">
                            <input type="file" name="logo_image_file" id="logo_image_file" accept="image/*" style="display:none;">
                            <div style="font-weight:700;color:#6d28d9;">Drop logo here</div>
                            <div class="muted" style="margin-top:4px;">or click to upload</div>
                            <div class="muted" style="font-size:13px;margin-top:8px;">Transparent PNG or SVG recommended</div>
                        </div>

                        <div class="field full" style="margin-top:14px;">
                            <label>Logo Image URL</label>
                            <input type="text" id="logo_image" name="logo_image" value="<?= h($row['logo_image'] ?? '') ?>" placeholder="/uploads/media/client-logo.png">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Preview</label>
                        <div style="
                            min-height:220px;
                            border:1px solid var(--line);
                            border-radius:18px;
                            overflow:hidden;
                            background:#fff;
                            display:grid;
                            place-items:center;
                            padding:20px;
                        ">
                            <img
                                id="logoPreview"
                                src="<?= h($row['logo_image'] ?? '') ?>"
                                alt="Logo Preview"
                                style="max-width:100%;max-height:120px;display:<?= !empty($row['logo_image']) ? 'block' : 'none' ?>;"
                            >
                            <div id="logoPreviewFallback" class="muted" style="<?= !empty($row['logo_image']) ? 'display:none;' : '' ?>">
                                No logo selected
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Client Logo</button>
            </div>
        </div>

        <div style="display:grid;gap:20px;">
            <div class="card">
                <h3 style="margin-top:0;">Builder Tips</h3>
                <div class="muted" style="font-size:14px;line-height:1.8;">
                    Use transparent logos where possible.<br>
                    Keep logo names consistent with company names.<br>
                    Use sort order to control strip position.<br>
                    Publish only the logos you want shown publicly.
                </div>
            </div>
        </div>
    </div>
</form>

<style>
@media (max-width: 1100px){
    .client-logo-edit-grid{
        grid-template-columns:1fr !important;
    }
}
@media (max-width: 900px){
    .upload-grid{
        grid-template-columns:1fr !important;
    }
}
</style>

<script>
(function(){
    const logoDropzone = document.getElementById('logoDropzone');
    const logoInput = document.getElementById('logo_image_file');
    const logoUrlInput = document.getElementById('logo_image');
    const logoPreview = document.getElementById('logoPreview');
    const logoPreviewFallback = document.getElementById('logoPreviewFallback');

    function bindDropzone(dropzone, input) {
        if (!dropzone || !input) return;

        dropzone.addEventListener('click', () => input.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#8b5cf6';
            dropzone.style.background = '#f5f0ff';
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.style.borderColor = '#d8c9fb';
            dropzone.style.background = '#faf8ff';
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#d8c9fb';
            dropzone.style.background = '#faf8ff';
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    bindDropzone(logoDropzone, logoInput);

    if (logoInput) {
        logoInput.addEventListener('change', function(){
            const file = this.files && this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e){
                logoPreview.src = e.target.result;
                logoPreview.style.display = 'block';
                logoPreviewFallback.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (logoUrlInput) {
        logoUrlInput.addEventListener('input', function(){
            const val = this.value.trim();
            if (val !== '') {
                logoPreview.src = val;
                logoPreview.style.display = 'block';
                logoPreviewFallback.style.display = 'none';
            } else {
                logoPreview.style.display = 'none';
                logoPreviewFallback.style.display = 'block';
            }
        });
    }
})();
</script>

<?php admin_footer(); ?>