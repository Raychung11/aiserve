<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM industry_pages WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $shortDescription = trim((string)($_POST['short_description'] ?? ''));
    $fullDescription = trim((string)($_POST['full_description'] ?? ''));
    $coverImage = trim((string)($_POST['cover_image'] ?? ''));
    $iconText = trim((string)($_POST['icon_text'] ?? ''));
    $badgeText = trim((string)($_POST['badge_text'] ?? ''));
    $ctaText = trim((string)($_POST['cta_text'] ?? ''));
    $ctaLink = trim((string)($_POST['cta_link'] ?? ''));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'draft'));

    if ($slug === '') {
        $slug = make_slug($title);
    }

    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE industry_pages
            SET title=?, slug=?, short_description=?, full_description=?, cover_image=?, icon_text=?, badge_text=?, cta_text=?, cta_link=?, is_featured=?, sort_order=?, status=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$title, $slug, $shortDescription, $fullDescription, $coverImage, $iconText, $badgeText, $ctaText, $ctaLink, $isFeatured, $sortOrder, $status, $id]);
        redirect('/admin/industry_edit.php?id=' . $id);
    } else {
        $stmt = db()->prepare("
            INSERT INTO industry_pages
            (title, slug, short_description, full_description, cover_image, icon_text, badge_text, cta_text, cta_link, is_featured, sort_order, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $slug, $shortDescription, $fullDescription, $coverImage, $iconText, $badgeText, $ctaText, $ctaLink, $isFeatured, $sortOrder, $status, $_SESSION['admin_id'] ?? null]);
        $newId = (int)db()->lastInsertId();
        redirect('/admin/industry_edit.php?id=' . $newId);
    }
}

admin_header($id > 0 ? 'Edit Industry' : 'Create Industry');
?>

<div class="card">
    <h3 style="margin-top:0;"><?= $id > 0 ? 'Edit Industry' : 'Create Industry' ?></h3>

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
                <label>Icon Text</label>
                <input type="text" name="icon_text" value="<?= h($row['icon_text'] ?? '') ?>" placeholder="RE / HR / F&B">
            </div>

            <div class="field full">
                <label>Short Description</label>
                <textarea name="short_description"><?= h($row['short_description'] ?? '') ?></textarea>
            </div>

            <div class="field full">
                <label>Full Description</label>
                <textarea name="full_description" style="min-height:220px;"><?= h($row['full_description'] ?? '') ?></textarea>
            </div>

            <div class="field full">
                <label>Cover Image URL</label>
                <input type="text" name="cover_image" value="<?= h($row['cover_image'] ?? '') ?>" placeholder="/uploads/media/industry-cover.jpg">
            </div>

            <div class="field">
                <label>Badge Text</label>
                <input type="text" name="badge_text" value="<?= h($row['badge_text'] ?? '') ?>" placeholder="AI + BI / Multi-Branch / Featured">
            </div>

            <div class="field">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= h((string)($row['sort_order'] ?? 0)) ?>">
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

            <div class="field full">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($row['is_featured']) ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    Featured industry
                </label>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Industry</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>