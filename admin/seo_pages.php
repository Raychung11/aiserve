<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$pages = [
    'home' => 'Homepage',
    'about' => 'About',
    'ai_bos' => 'Ai-BOS',
    'solutions' => 'Solutions',
    'industries' => 'Industries',
    'contact' => 'Contact',
    'blog' => 'Blog'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($pages as $key => $label) {
        $seoTitle = trim($_POST[$key . '_title'] ?? '');
        $seoDescription = trim($_POST[$key . '_description'] ?? '');

        $stmt = db()->prepare("
            INSERT INTO seo_pages (page_key, seo_title, seo_description, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE seo_title = VALUES(seo_title), seo_description = VALUES(seo_description), updated_at = NOW()
        ");
        $stmt->execute([$key, $seoTitle, $seoDescription]);
    }

    redirect('/admin/seo_pages.php');
}

$existing = db()->query("SELECT * FROM seo_pages")->fetchAll();
$map = [];
foreach ($existing as $row) {
    $map[$row['page_key']] = $row;
}

admin_header('SEO Meta');
?>

<div class="card">
    <h3 style="margin-top:0;">SEO Meta Editor</h3>
    <form method="post">
        <?= csrf_input() ?>
        <?php foreach ($pages as $key => $label): ?>
            <div class="card" style="margin-top:16px;">
                <div class="form-grid">
                    <div class="field full">
                        <label><?= h($label) ?> SEO Title</label>
                        <input type="text" name="<?= h($key) ?>_title" value="<?= h($map[$key]['seo_title'] ?? '') ?>">
                    </div>
                    <div class="field full">
                        <label><?= h($label) ?> SEO Description</label>
                        <textarea name="<?= h($key) ?>_description"><?= h($map[$key]['seo_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:18px;">
            <button type="submit" class="btn">Save SEO Meta</button>
        </div>
    </form>
</div>

<?php admin_footer(); ?>