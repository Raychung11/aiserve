<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$message = '';
$error = '';

if ($id > 0) {
    try {
        $stmt = db()->prepare("SELECT * FROM ai_cs_showcases WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            $row = null;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (!function_exists('ai_cs_upload_file')) {
    function ai_cs_upload_file(array $file, string $prefix = 'ai_cs'): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        $mime = mime_content_type($tmpPath) ?: '';
        if (!isset($allowedMime[$mime])) {
            return null;
        }

        $ext = $allowedMime[$mime];
        $uploadDir = __DIR__ . '/../uploads/media/';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            return null;
        }

        return '/uploads/media/' . $filename;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $companyName = trim((string)($_POST['company_name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $shortDescription = trim((string)($_POST['short_description'] ?? ''));
    $fullDescription = trim((string)($_POST['full_description'] ?? ''));
    $coverImage = trim((string)($_POST['cover_image'] ?? ''));
    $badgeText = trim((string)($_POST['badge_text'] ?? ''));
    $tag1 = trim((string)($_POST['tag_1'] ?? ''));
    $tag2 = trim((string)($_POST['tag_2'] ?? ''));
    $tag3 = trim((string)($_POST['tag_3'] ?? ''));
    $liveUrl = trim((string)($_POST['live_url'] ?? ''));
    $ctaText = trim((string)($_POST['cta_text'] ?? ''));
    $ctaLink = trim((string)($_POST['cta_link'] ?? ''));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'draft'));

    if ($title === '') {
        $error = 'Title is required.';
    }

    if ($slug === '' && $title !== '') {
        if (function_exists('make_slug')) {
            $slug = make_slug($title);
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }
    }

    if ($error === '' && !empty($_FILES['cover_image_file']['name'])) {
        $uploaded = ai_cs_upload_file($_FILES['cover_image_file'], 'ai_cs');
        if ($uploaded !== null) {
            $coverImage = $uploaded;
        } else {
            $error = 'Cover image upload failed. Please use JPG, PNG, WEBP, GIF, or SVG.';
        }
    }

    if ($error === '') {
        try {
            if ($id > 0) {
                $stmt = db()->prepare("
                    UPDATE ai_cs_showcases
                    SET title=?, slug=?, company_name=?, category=?, short_description=?, full_description=?, cover_image=?, badge_text=?,
                        tag_1=?, tag_2=?, tag_3=?, live_url=?, cta_text=?, cta_link=?, is_featured=?, sort_order=?, status=?, updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([
                    $title, $slug, $companyName, $category, $shortDescription, $fullDescription, $coverImage, $badgeText,
                    $tag1, $tag2, $tag3, $liveUrl, $ctaText, $ctaLink, $isFeatured, $sortOrder, $status, $id
                ]);
                $message = 'Showcase updated successfully.';
            } else {
                $stmt = db()->prepare("
                    INSERT INTO ai_cs_showcases
                    (title, slug, company_name, category, short_description, full_description, cover_image, badge_text, tag_1, tag_2, tag_3, live_url, cta_text, cta_link, is_featured, sort_order, status, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $title, $slug, $companyName, $category, $shortDescription, $fullDescription, $coverImage, $badgeText,
                    $tag1, $tag2, $tag3, $liveUrl, $ctaText, $ctaLink, $isFeatured, $sortOrder, $status, $_SESSION['admin_id'] ?? null
                ]);
                $id = (int)db()->lastInsertId();
                $message = 'Showcase created successfully.';
            }

            $stmt = db()->prepare("SELECT * FROM ai_cs_showcases WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!is_array($row)) {
                $row = null;
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

admin_header($id > 0 ? 'Edit AI CS Showcase' : 'Create AI CS Showcase');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;"><?= $id > 0 ? 'Edit AI Customer Service Showcase' : 'Create AI Customer Service Showcase' ?></h3>
            <p class="muted" style="margin:6px 0 0;">Add client name, sector, photo, and proof-of-execution description.</p>
        </div>
        <div class="pill"><?= $id > 0 ? 'Showcase #' . (int)$id : 'New Showcase' ?></div>
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

    <div style="display:grid;grid-template-columns:minmax(0,1.2fr) 380px;gap:20px;" class="ai-cs-edit-grid">
        <div style="display:grid;gap:20px;">
            <div class="card">
                <h3 style="margin-top:0;">Showcase Details</h3>

                <div class="form-grid">
                    <div class="field full">
                        <label>Title</label>
                        <input type="text" id="showcase_title" name="title" value="<?= h($row['title'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label>Slug</label>
                        <input type="text" id="showcase_slug" name="slug" value="<?= h($row['slug'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?= h($row['company_name'] ?? '') ?>" placeholder="Cafe Chef Wan">
                    </div>

                    <div class="field">
                        <label>Category</label>
                        <input type="text" name="category" value="<?= h($row['category'] ?? '') ?>" placeholder="F&B / Travel / Member Service">
                    </div>

                    <div class="field full">
                        <label>Short Description</label>
                        <textarea name="short_description" id="short_description"><?= h($row['short_description'] ?? '') ?></textarea>
                    </div>

                    <div class="field full">
                        <label>Full Description</label>
                        <textarea name="full_description" style="min-height:220px;"><?= h($row['full_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Cover Image Upload</h3>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="upload-grid">
                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Drag & Drop Cover Image</label>

                        <div id="coverDropzone" style="
                            border:2px dashed #d8c9fb;
                            border-radius:18px;
                            padding:24px;
                            background:#faf8ff;
                            text-align:center;
                            cursor:pointer;
                        ">
                            <input type="file" name="cover_image_file" id="cover_image_file" accept="image/*" style="display:none;">
                            <div style="font-weight:700;color:#6d28d9;">Drop image here</div>
                            <div class="muted" style="margin-top:4px;">or click to upload</div>
                            <div class="muted" style="font-size:13px;margin-top:8px;">JPG, PNG, WEBP, GIF, or SVG</div>
                        </div>

                        <div class="field full" style="margin-top:14px;">
                            <label>Cover Image URL</label>
                            <input type="text" id="cover_image" name="cover_image" value="<?= h($row['cover_image'] ?? '') ?>" placeholder="/uploads/media/ai-cs-cover.jpg">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Preview</label>
                        <div style="
                            min-height:220px;
                            border:1px solid var(--line);
                            border-radius:18px;
                            overflow:hidden;
                            background:#faf8ff;
                            display:grid;
                            place-items:center;
                        ">
                            <img
                                id="coverPreview"
                                src="<?= h($row['cover_image'] ?? '') ?>"
                                alt="Cover Preview"
                                style="width:100%;height:100%;object-fit:cover;display:<?= !empty($row['cover_image']) ? 'block' : 'none' ?>;"
                            >
                            <div id="coverPreviewFallback" class="muted" style="<?= !empty($row['cover_image']) ? 'display:none;' : '' ?>">
                                No cover image selected
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Tags, CTA & Publishing</h3>

                <div class="form-grid">
                    <div class="field">
                        <label>Badge Text</label>
                        <input type="text" name="badge_text" value="<?= h($row['badge_text'] ?? '') ?>" placeholder="Live Deployment">
                    </div>

                    <div class="field">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="<?= h((string)($row['sort_order'] ?? 0)) ?>">
                    </div>

                    <div class="field">
                        <label>Tag 1</label>
                        <input type="text" name="tag_1" value="<?= h($row['tag_1'] ?? '') ?>" placeholder="Reservations">
                    </div>

                    <div class="field">
                        <label>Tag 2</label>
                        <input type="text" name="tag_2" value="<?= h($row['tag_2'] ?? '') ?>" placeholder="Lead Capture">
                    </div>

                    <div class="field">
                        <label>Tag 3</label>
                        <input type="text" name="tag_3" value="<?= h($row['tag_3'] ?? '') ?>" placeholder="Customer Support">
                    </div>

                    <div class="field">
                        <label>Live URL</label>
                        <input type="text" name="live_url" value="<?= h($row['live_url'] ?? '') ?>" placeholder="https://example.com">
                    </div>

                    <div class="field">
                        <label>CTA Text</label>
                        <input type="text" name="cta_text" value="<?= h($row['cta_text'] ?? '') ?>" placeholder="Talk to Us">
                    </div>

                    <div class="field">
                        <label>CTA Link</label>
                        <input type="text" name="cta_link" value="<?= h($row['cta_link'] ?? '') ?>" placeholder="/contact.php">
                    </div>

                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?= ($row['status'] ?? '') === 'draft' ? 'selected' : '' ?>>draft</option>
                            <option value="published" <?= ($row['status'] ?? '') === 'published' ? 'selected' : '' ?>>published</option>
                        </select>
                    </div>

                    <div class="field" style="align-self:end;">
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="is_featured" value="1" <?= !empty($row['is_featured']) ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                            Featured showcase
                        </label>
                    </div>

                    <div class="field full">
                        <button type="submit" class="btn">Save Showcase</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:20px;">
            <div class="card">
                <h3 style="margin-top:0;">Live Helpers</h3>
                <div style="display:grid;gap:14px;">
                    <div>
                        <div class="muted" style="font-size:13px;">Slug Preview</div>
                        <div id="slugPreview" style="font-weight:700;word-break:break-word;">/ai-customer-service-demo/<?= h($row['slug'] ?? '') ?></div>
                    </div>

                    <div>
                        <div class="muted" style="font-size:13px;">Short Description Length</div>
                        <div id="descCount" style="font-weight:700;">0 characters</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Builder Tips</h3>
                <div class="muted" style="font-size:14px;line-height:1.8;">
                    Use the client or business name clearly.<br>
                    Keep the short description benefit-driven.<br>
                    Add 2–3 tags to show use case scope.<br>
                    Mark stronger examples as featured.<br>
                    Use real screenshots for premium presentation.
                </div>
            </div>
        </div>
    </div>
</form>

<style>
@media (max-width: 1100px){
    .ai-cs-edit-grid{
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
    const titleField = document.getElementById('showcase_title');
    const slugField = document.getElementById('showcase_slug');
    const slugPreview = document.getElementById('slugPreview');
    const descField = document.getElementById('short_description');
    const descCount = document.getElementById('descCount');

    const coverDropzone = document.getElementById('coverDropzone');
    const coverInput = document.getElementById('cover_image_file');
    const coverUrlInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('coverPreview');
    const coverPreviewFallback = document.getElementById('coverPreviewFallback');

    function makeSlug(text) {
        return text.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    }

    function updateSlugPreview() {
        const slug = (slugField.value.trim() !== '') ? slugField.value.trim() : makeSlug(titleField.value);
        slugPreview.textContent = '/ai-customer-service-demo/' + slug;
    }

    function updateDescCount() {
        descCount.textContent = (descField.value || '').length + ' characters';
    }

    function updateCoverPreviewFromUrl() {
        const val = coverUrlInput.value.trim();
        if (val !== '') {
            coverPreview.src = val;
            coverPreview.style.display = 'block';
            coverPreviewFallback.style.display = 'none';
        } else {
            coverPreview.style.display = 'none';
            coverPreviewFallback.style.display = 'block';
        }
    }

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

    bindDropzone(coverDropzone, coverInput);

    if (coverInput) {
        coverInput.addEventListener('change', function(){
            const file = this.files && this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e){
                coverPreview.src = e.target.result;
                coverPreview.style.display = 'block';
                coverPreviewFallback.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (coverUrlInput) coverUrlInput.addEventListener('input', updateCoverPreviewFromUrl);
    if (titleField) titleField.addEventListener('input', updateSlugPreview);
    if (slugField) slugField.addEventListener('input', updateSlugPreview);
    if (descField) descField.addEventListener('input', updateDescCount);

    updateSlugPreview();
    updateDescCount();
    updateCoverPreviewFromUrl();
})();
</script>

<?php admin_footer(); ?>