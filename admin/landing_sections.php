<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;
if ($pageId <= 0) redirect('/admin/landing_pages.php');

$stmt = db()->prepare("SELECT * FROM landing_pages WHERE id = ? LIMIT 1");
$stmt->execute([$pageId]);
$page = $stmt->fetch();
if (!$page) redirect('/admin/landing_pages.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $sectionType = trim($_POST['section_type'] ?? 'text');
    $sectionTitle = trim($_POST['section_title'] ?? '');
    $sectionContent = trim($_POST['section_content'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    $stmt = db()->prepare("
        INSERT INTO landing_page_sections (landing_page_id, section_type, section_title, section_content, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$pageId, $sectionType, $sectionTitle, $sectionContent, $sortOrder]);

    redirect('/admin/landing_sections.php?page_id=' . $pageId);
}

$sections = db()->prepare("SELECT * FROM landing_page_sections WHERE landing_page_id = ? ORDER BY sort_order ASC, id ASC");
$sections->execute([$pageId]);
$rows = $sections->fetchAll();

admin_header('Landing Page Sections');
?>

<div class="card">
    <h3 style="margin-top:0;">Sections for <?= h($page['title']) ?></h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Section Type</label>
                <select name="section_type">
                    <option value="text">text</option>
                    <option value="feature">feature</option>
                    <option value="cta">cta</option>
                </select>
            </div>
            <div class="field">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="0">
            </div>
            <div class="field full">
                <label>Section Title</label>
                <input type="text" name="section_title">
            </div>
            <div class="field full">
                <label>Section Content</label>
                <textarea name="section_content"></textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Add Section</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Existing Sections</h3>
    <?php if (!$rows): ?>
        <p class="muted">No sections yet.</p>
    <?php else: ?>
        <?php foreach ($rows as $section): ?>
            <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
                <div><strong><?= h($section['section_title']) ?></strong> <span class="pill"><?= h($section['section_type']) ?></span></div>
                <div class="muted">Sort: <?= (int)$section['sort_order'] ?></div>
                <div style="white-space:pre-wrap;margin-top:8px;"><?= h($section['section_content']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>