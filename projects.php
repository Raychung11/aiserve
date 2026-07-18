<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/projects_helper.php';

projects_ensure_schema();

$projects = [];
try {
    $stmt = db()->query("SELECT * FROM projects WHERE is_published = 1 ORDER BY id DESC");
    $projects = $stmt->fetchAll();
} catch (Throwable $e) {
    $projects = [];
}

$seo = seo_meta(
    'projects',
    'Our Projects | AiServe.my',
    'Selected projects and systems delivered by AiServe.my — AI customer service, workflow automation, and business systems for companies across Malaysia.'
);
$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

include __DIR__ . '/inc/public_header.php';
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container" style="max-width:980px;">
        <div class="eyebrow">Our Work</div>
        <h1>Projects we're building</h1>
        <p style="max-width:680px;">A selection of the systems and solutions our team is delivering — from AI customer service to workflow automation and custom business platforms.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$projects): ?>
            <div class="card"><p class="small" style="margin:0;">Projects will be published here soon.</p></div>
        <?php else: ?>
            <div class="grid-3">
                <?php foreach ($projects as $p): ?>
                    <?php
                        $name = (string)($p['name'] ?? '');
                        $client = trim((string)($p['client'] ?? ''));
                        $category = trim((string)($p['category'] ?? ''));
                        $desc = trim((string)($p['description'] ?? ''));
                        $cover = trim((string)($p['cover_image'] ?? ''));
                        $url = trim((string)($p['project_url'] ?? ''));
                        $statusLabel = project_status_label((string)($p['project_status'] ?? ''));
                    ?>
                    <article class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;">
                        <?php if ($cover !== ''): ?>
                            <div style="width:100%;aspect-ratio:16/10;background:#f5f0ff;border-bottom:1px solid var(--line);">
                                <img src="<?= h($cover) ?>" alt="<?= h($name) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                            </div>
                        <?php endif; ?>

                        <div style="padding:20px;display:flex;flex-direction:column;gap:10px;flex:1;">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <?php if ($category !== ''): ?>
                                    <span class="eyebrow" style="margin:0;"><?= h($category) ?></span>
                                <?php endif; ?>
                                <span class="pill"><?= h($statusLabel) ?></span>
                            </div>

                            <h3 style="margin:0;"><?= h($name) ?></h3>

                            <?php if ($client !== ''): ?>
                                <div class="small" style="margin:0;"><strong style="color:var(--dark);">Client:</strong> <?= h($client) ?></div>
                            <?php endif; ?>

                            <?php if ($desc !== ''): ?>
                                <p class="small" style="margin:0;line-height:1.7;"><?= nl2br(h($desc)) ?></p>
                            <?php endif; ?>

                            <?php if ($url !== ''): ?>
                                <div style="margin-top:auto;padding-top:8px;">
                                    <a href="<?= h($url) ?>" target="_blank" rel="noopener" class="btn-secondary">View project</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>
