<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/rich_editor.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM landing_pages WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $heroTitle = trim($_POST['hero_title'] ?? '');
    $heroText = trim($_POST['hero_text'] ?? '');
    $bodyContent = trim($_POST['body_content'] ?? '');
    $seoTitle = trim($_POST['seo_title'] ?? '');
    $seoDescription = trim($_POST['seo_description'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');

    if ($slug === '') {
        $slug = make_slug($title);
    }

    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE landing_pages
            SET title=?, slug=?, hero_title=?, hero_text=?, body_content=?, seo_title=?, seo_description=?, status=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$title, $slug, $heroTitle, $heroText, $bodyContent, $seoTitle, $seoDescription, $status, $id]);
        redirect('/admin/landing_page_edit.php?id=' . $id);
    } else {
        $stmt = db()->prepare("
            INSERT INTO landing_pages
            (title, slug, hero_title, hero_text, body_content, seo_title, seo_description, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $slug, $heroTitle, $heroText, $bodyContent, $seoTitle, $seoDescription, $status, $_SESSION['admin_id'] ?? null]);
        $newId = (int)db()->lastInsertId();
        redirect('/admin/landing_page_edit.php?id=' . $newId);
    }
}

admin_header($id > 0 ? 'Edit Landing Page' : 'Create Landing Page');
?>

<div class="card">
    <h3 style="margin-top:0;"><?= $id > 0 ? 'Edit Landing Page' : 'Create Landing Page' ?></h3>

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
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?= ($row['status'] ?? '')==='draft' ? 'selected' : '' ?>>draft</option>
                    <option value="published" <?= ($row['status'] ?? '')==='published' ? 'selected' : '' ?>>published</option>
                </select>
            </div>
            <div class="field full">
                <label>Hero Title</label>
                <input type="text" name="hero_title" value="<?= h($row['hero_title'] ?? '') ?>">
            </div>
            <div class="field full">
                <label>Hero Text</label>
                <textarea name="hero_text"><?= h($row['hero_text'] ?? '') ?></textarea>
            </div>
            <div class="field full">
                <label>Body Content</label>
                <div class="rich-toolbar">
                    <button type="button" onclick="wrapText('body_editor', '**', '**')">Bold</button>
                    <button type="button" onclick="wrapText('body_editor', '## ')">H2</button>
                    <button type="button" onclick="wrapText('body_editor', '- ')">Bullet</button>
                    <button type="button" onclick="insertLine('body_editor', '\n---\n')">Divider</button>
                </div>
                <textarea id="body_editor" name="body_content" style="min-height:320px;"><?= h($row['body_content'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label>SEO Title</label>
                <input type="text" name="seo_title" value="<?= h($row['seo_title'] ?? '') ?>">
            </div>
            <div class="field">
                <label>SEO Description</label>
                <textarea name="seo_description"><?= h($row['seo_description'] ?? '') ?></textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Save Landing Page</button>
            </div>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../inc/rich_editor.php'; ?>
<?php admin_footer(); ?>