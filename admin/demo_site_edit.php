<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM demo_sites WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $shortDescription = trim((string)($_POST['short_description'] ?? ''));
    $coverImage = trim((string)($_POST['cover_image'] ?? ''));
    $liveUrl = trim((string)($_POST['live_url'] ?? ''));
    $badgeText = trim((string)($_POST['badge_text'] ?? ''));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'draft'));

    if ($slug === '') {
        $slug = make_slug($title);
    }

    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE demo_sites
            SET title = ?, slug = ?, category = ?, short_description = ?, cover_image = ?, live_url = ?, badge_text = ?,
                is_featured = ?, sort_order = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $title, $slug, $category, $shortDescription, $coverImage, $liveUrl, $badgeText,
            $isFeatured, $sortOrder, $status, $id
        ]);
        redirect('/admin/demo_site_edit.php?id=' . $id);
    } else {
        $stmt = db()->prepare("
            INSERT INTO demo_sites
            (title, slug, category, short_description, cover_image, live_url, badge_text, is_featured, sort_order, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $title, $slug, $category, $shortDescription, $coverImage, $liveUrl, $badgeText,
            $isFeatured, $sortOrder, $status, $_SESSION['admin_id'] ?? null
        ]);
        $newId = (int)db()->lastInsertId();
        redirect('/admin/demo_site_edit.php?id=' . $newId);
    }
}

admin_header($id > 0 ? 'Edit Demo Site' : 'Create Demo Site');
?>

<div class="card">
    <h3 style="margin-top:0;"><?= $id > 0 ? 'Edit Demo Site' : 'Create Demo Site' ?></h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Title</label>
                <input type="text" name="title" value="<?= h($row['title'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Slug</label>
                <input type="text" name="slug" value="<?= h($row['slug'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Category</label>
                <input type="text" name="category" value="<?= h($row['category'] ?? '') ?>" placeholder="PropTech / HRTech / Safety / AI System">
            </div>

            <div class="field full">
                <label>Short Description</label>
                <textarea name="short_description"><?= h($row['short_description'] ?? '') ?></textarea>
            </div>

            <div class="field full">
                <label>Cover Image URL</label>
                <input type="text" name="cover_image" value="<?= h($row['cover_image'] ?? '') ?>" placeholder="/uploads/media/demo-cover.jpg">
            </div>

            <div class="field">
                <label>Live URL</label>
                <input type="text" name="live_url" value="<?= h($row['live_url'] ?? '') ?>" placeholder="https://propertyhub.my/">
            </div>

            <div class="field">
                <label>Badge Text</label>
                <input type="text" name="badge_text" value="<?= h($row['badge_text'] ?? '') ?>" placeholder="Live Demo / Featured / AI Workflow">
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

            <div class="field full">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($row['is_featured']) ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    Featured demo
                </label>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Demo Site</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>