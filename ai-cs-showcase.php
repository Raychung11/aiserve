<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    exit('Showcase not found');
}

$stmt = db()->prepare("
    SELECT *
    FROM ai_cs_showcases
    WHERE slug = ?
      AND status = 'published'
    LIMIT 1
");
$stmt->execute([$slug]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit('Showcase not found');
}

$pageTitle = (string)$item['title'] . ' | AiServe.my';
$pageDescription = (string)($item['short_description'] ?: 'AI customer service showcase by AiServe.my');

require_once __DIR__ . '/inc/public_header.php';
?>

<section class="section" style="padding-top:42px;">
    <div class="container" style="max-width:980px;">
        <article class="card" style="padding:0;overflow:hidden;">
            <?php if (!empty($item['cover_image'])): ?>
                <div style="background:#f5f0ff;border-bottom:1px solid var(--line);">
                    <img src="<?= h((string)$item['cover_image']) ?>" alt="<?= h((string)$item['title']) ?>" style="width:100%;max-height:460px;object-fit:cover;display:block;">
                </div>
            <?php endif; ?>

            <div style="padding:38px;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
                    <?php if (!empty($item['badge_text'])): ?>
                        <span class="pill"><?= h((string)$item['badge_text']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['category'])): ?>
                        <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$item['category']) ?></span>
                    <?php endif; ?>
                </div>

                <h1 style="margin:0 0 14px 0;font-size:clamp(34px,5vw,54px);line-height:1.05;color:var(--dark);">
                    <?= h((string)$item['title']) ?>
                </h1>

                <?php if (!empty($item['company_name'])): ?>
                    <div class="small" style="margin-bottom:16px;font-weight:800;"><?= h((string)$item['company_name']) ?></div>
                <?php endif; ?>

                <?php if (!empty($item['short_description'])): ?>
                    <div style="padding:18px 20px;background:linear-gradient(180deg,#faf8ff,#f6f1ff);border:1px solid var(--line);border-radius:18px;color:var(--muted);font-size:17px;line-height:1.8;margin-bottom:28px;">
                        <?= h((string)$item['short_description']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($item['full_description'])): ?>
                    <div style="max-width:760px;font-size:18px;line-height:1.9;color:var(--text);white-space:pre-wrap;">
                        <?= h((string)$item['full_description']) ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:20px;">
                    <?php foreach (['tag_1','tag_2','tag_3'] as $tagField): ?>
                        <?php if (!empty($item[$tagField])): ?>
                            <span class="pill" style="background:#f5f0ff;color:#6d28d9;"><?= h((string)$item[$tagField]) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:28px;padding-top:22px;border-top:1px solid var(--line);display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="/ai-customer-service-demo.php" class="btn-secondary">Back to AI CS Demo</a>
                    <?php if (!empty($item['cta_text']) && !empty($item['cta_link'])): ?>
                        <a href="<?= h((string)$item['cta_link']) ?>" class="btn"><?= h((string)$item['cta_text']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>