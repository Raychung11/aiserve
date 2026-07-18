<?php
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: /');
    exit;
}

$stmt = db()->prepare("SELECT * FROM landing_pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page Not Found | AiServe.my';
    $pageDescription = 'The requested page could not be found.';
    include __DIR__ . '/inc/public_header.php';
    echo '<section class="hero"><div class="container"><h1>Page not found</h1></div></section>';
    include __DIR__ . '/inc/public_footer.php';
    exit;
}

$pageTitle = !empty($page['seo_title']) ? $page['seo_title'] : $page['title'] . ' | AiServe.my';
$pageDescription = !empty($page['seo_description']) ? $page['seo_description'] : ($page['hero_text'] ?? '');

include __DIR__ . '/inc/public_header.php';
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container" style="max-width:920px;">
        <div class="eyebrow">AiServe.my</div>
        <h1><?= h($page['hero_title'] ?: $page['title']) ?></h1>
        <p><?= h($page['hero_text']) ?></p>
    </div>
</section>

<?php
$secStmt = db()->prepare("SELECT * FROM landing_page_sections WHERE landing_page_id = ? ORDER BY sort_order ASC, id ASC");
$secStmt->execute([$page['id']]);
$sections = $secStmt->fetchAll();
?>

<?php if ($sections): ?>
<section class="section">
    <div class="container" style="max-width:920px;">
        <?php foreach ($sections as $section): ?>
            <div class="card" style="margin-bottom:18px;">
                <?php if (!empty($section['section_title'])): ?>
                    <h3 style="margin-top:0;"><?= h($section['section_title']) ?></h3>
                <?php endif; ?>
                <div style="white-space:pre-wrap;"><?= h($section['section_content']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container" style="max-width:920px;">
        <div class="card">
            <div style="white-space:pre-wrap;"><?= h($page['body_content']) ?></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>