<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$seo = seo_meta(
    'ai_customer_service_demo',
    'AI Customer Service Demo | AiServe.my',
    'Explore how AiServe.my supports real businesses with AI customer service across F&B, travel, member services, and more.'
);

$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

require_once __DIR__ . '/inc/public_header.php';

$stmt = db()->query("
    SELECT *
    FROM ai_cs_showcases
    WHERE status = 'published'
    ORDER BY is_featured DESC, sort_order ASC, id DESC
");
$rows = $stmt->fetchAll();

$featured = [];
$regular = [];
foreach ($rows as $item) {
    if ((int)($item['is_featured'] ?? 0) === 1 && count($featured) < 3) {
        $featured[] = $item;
    } else {
        $regular[] = $item;
    }
}

$heroLabel = get_setting('ai_cs_page_hero_label', 'Real Business Deployment');
$heroTitle = get_setting('ai_cs_page_hero_title', 'AI customer service already supporting real businesses');
$heroSubtitle = get_setting('ai_cs_page_hero_subtitle', 'AiServe.my helps businesses improve inquiry handling, lead capture, service consistency, and workflow responsiveness through practical AI deployment.');
$heroBgUrl = get_setting('ai_cs_page_hero_bg_url', '');
$heroOverlayOpacity = (string)get_setting('ai_cs_page_hero_overlay_opacity', '0.88');

$showLogoStrip = get_setting('ai_cs_page_show_logo_strip', '1') === '1';
$showFeaturedSection = get_setting('ai_cs_page_show_featured_section', '1') === '1';
$showAllSection = get_setting('ai_cs_page_show_all_section', '1') === '1';
$showCtaSection = get_setting('ai_cs_page_show_cta_section', '1') === '1';

$featureBannerTitle = get_setting('ai_cs_page_feature_banner_title', 'AI customer service built from real business use cases');
$featureBannerSubtitle = get_setting('ai_cs_page_feature_banner_subtitle', 'From restaurants and travel operators to organisations and multi-branch businesses, AiServe.my helps turn customer interaction into a more structured, scalable, and intelligent service layer.');
$featureBannerImage = get_setting('ai_cs_page_feature_banner_image', '');

$featuredLabel = get_setting('ai_cs_page_featured_label', 'Featured Use Cases');
$featuredTitle = get_setting('ai_cs_page_featured_title', 'Selected business deployments');
$featuredSubtitle = get_setting('ai_cs_page_featured_subtitle', 'Real examples of AI customer service adapted to different business models and inquiry flows.');

$allLabel = get_setting('ai_cs_page_all_label', 'All Showcases');
$allTitle = get_setting('ai_cs_page_all_title', 'More examples across industries');
$allSubtitle = get_setting('ai_cs_page_all_subtitle', 'Expand this page any time from admin with more clients, sectors, and visuals.');

$ctaLabel = get_setting('ai_cs_page_cta_label', 'AiServe.my');
$ctaTitle = get_setting('ai_cs_page_cta_title', 'Want AI customer service built around your business?');
$ctaSubtitle = get_setting('ai_cs_page_cta_subtitle', 'We help businesses deploy AI customer service in a practical way, based on real inquiry patterns, workflow requirements, and service goals.');
$ctaButtonText = get_setting('ai_cs_page_cta_button_text', 'Talk to Us');
$ctaButtonLink = get_setting('ai_cs_page_cta_button_link', '/contact.php');
$ctaSecondaryText = get_setting('ai_cs_page_cta_secondary_text', 'View Demos');
$ctaSecondaryLink = get_setting('ai_cs_page_cta_secondary_link', '/demos.php');

$heroOverlay = is_numeric($heroOverlayOpacity) ? max(0, min(1, (float)$heroOverlayOpacity)) : 0.88;
?>

<section class="hero-section" style="
    padding:84px 0 48px;
    background:
        radial-gradient(circle at top right, rgba(139,92,246,.16), transparent 24%),
        linear-gradient(135deg, rgba(109,40,217,<?= $heroOverlay ?>), rgba(139,92,246,<?= $heroOverlay ?>))
        <?php if ($heroBgUrl !== ''): ?>, url('<?= h($heroBgUrl) ?>')<?php endif; ?>;
    background-size:cover;
    background-position:center;
    color:#fff;
">
    <div class="container">
        <div style="max-width:860px;">
            <div style="display:inline-block;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.14);font-size:13px;font-weight:700;margin-bottom:18px;">
                <?= h($heroLabel) ?>
            </div>

            <h1 style="font-size:clamp(38px, 6vw, 64px);line-height:1.03;margin:0 0 18px 0;color:#fff;">
                <?= h($heroTitle) ?>
            </h1>

            <p style="font-size:18px;line-height:1.8;max-width:760px;color:rgba(255,255,255,.92);margin:0;">
                <?= h($heroSubtitle) ?>
            </p>
        </div>
    </div>
</section>

<?php if ($showLogoStrip): ?>
    <?php require_once __DIR__ . '/inc/client_logo_strip.php'; ?>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:0;" class="ai-cs-feature-banner-grid">
                <div style="padding:28px;">
                    <div class="label">Why It Works</div>
                    <h2 style="margin:0 0 12px 0;line-height:1.1;"><?= h($featureBannerTitle) ?></h2>
                    <p style="margin:0;color:var(--muted);font-size:17px;line-height:1.8;">
                        <?= h($featureBannerSubtitle) ?>
                    </p>
                </div>
                <div style="min-height:260px;background:#f5f0ff;">
                    <?php if ($featureBannerImage !== ''): ?>
                        <img src="<?= h($featureBannerImage) ?>" alt="AI Customer Service Feature Banner" style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($showFeaturedSection && $featured): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label"><?= h($featuredLabel) ?></div>
            <h2><?= h($featuredTitle) ?></h2>
            <p><?= h($featuredSubtitle) ?></p>
        </div>

        <div class="grid-3">
            <?php foreach ($featured as $item): ?>
                <article class="card client-demo-card" style="padding:0;overflow:hidden;">
                    <?php if (!empty($item['cover_image'])): ?>
                        <div class="demo-card-media">
                            <img src="<?= h((string)$item['cover_image']) ?>" alt="<?= h((string)$item['title']) ?>" class="demo-card-image">
                        </div>
                    <?php endif; ?>

                    <div style="padding:20px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                            <?php if (!empty($item['badge_text'])): ?>
                                <span class="pill"><?= h((string)$item['badge_text']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['category'])): ?>
                                <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$item['category']) ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 style="margin:0 0 10px 0;"><?= h((string)$item['title']) ?></h3>

                        <?php if (!empty($item['company_name'])): ?>
                            <div class="small" style="margin-bottom:10px;font-weight:700;"><?= h((string)$item['company_name']) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($item['short_description'])): ?>
                            <p style="margin:0;color:var(--muted);"><?= h((string)$item['short_description']) ?></p>
                        <?php endif; ?>

                        <div class="demo-tag-row">
                            <?php foreach (['tag_1','tag_2','tag_3'] as $tagField): ?>
                                <?php if (!empty($item[$tagField])): ?>
                                    <span class="pill"><?= h((string)$item[$tagField]) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                            <a href="/ai-cs-showcase.php?slug=<?= urlencode((string)$item['slug']) ?>" class="btn-secondary">View Case</a>
                            <?php if (!empty($item['cta_text']) && !empty($item['cta_link'])): ?>
                                <a href="<?= h((string)$item['cta_link']) ?>" class="btn"><?= h((string)$item['cta_text']) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($showAllSection): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label"><?= h($allLabel) ?></div>
            <h2><?= h($allTitle) ?></h2>
            <p><?= h($allSubtitle) ?></p>
        </div>

        <div class="grid-3">
            <?php if (!$rows): ?>
                <div class="card"><p>No showcases published yet.</p></div>
            <?php else: ?>
                <?php foreach ($regular as $item): ?>
                    <article class="card client-demo-card" style="padding:0;overflow:hidden;">
                        <?php if (!empty($item['cover_image'])): ?>
                            <div class="demo-card-media">
                                <img src="<?= h((string)$item['cover_image']) ?>" alt="<?= h((string)$item['title']) ?>" class="demo-card-image">
                            </div>
                        <?php endif; ?>

                        <div style="padding:20px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                                <?php if (!empty($item['badge_text'])): ?>
                                    <span class="pill"><?= h((string)$item['badge_text']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['category'])): ?>
                                    <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$item['category']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="margin:0 0 10px 0;"><?= h((string)$item['title']) ?></h3>

                            <?php if (!empty($item['company_name'])): ?>
                                <div class="small" style="margin-bottom:10px;font-weight:700;"><?= h((string)$item['company_name']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($item['short_description'])): ?>
                                <p style="margin:0;color:var(--muted);"><?= h((string)$item['short_description']) ?></p>
                            <?php endif; ?>

                            <div class="demo-tag-row">
                                <?php foreach (['tag_1','tag_2','tag_3'] as $tagField): ?>
                                    <?php if (!empty($item[$tagField])): ?>
                                        <span class="pill"><?= h((string)$item[$tagField]) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top:16px;">
                                <a href="/ai-cs-showcase.php?slug=<?= urlencode((string)$item['slug']) ?>" class="btn-secondary">View Case</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($showCtaSection): ?>
<section class="section">
    <div class="container">
        <div class="card" style="padding:30px;background:linear-gradient(135deg, rgba(109,40,217,.96), rgba(139,92,246,.86));color:#fff;border:none;box-shadow:0 24px 60px rgba(109,40,217,.18);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="max-width:720px;">
                    <div style="font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.85;margin-bottom:10px;"><?= h($ctaLabel) ?></div>
                    <h2 style="margin:0 0 10px 0;font-size:clamp(28px,4vw,42px);line-height:1.08;color:#fff;">
                        <?= h($ctaTitle) ?>
                    </h2>
                    <p style="margin:0;color:rgba(255,255,255,.92);font-size:17px;line-height:1.75;">
                        <?= h($ctaSubtitle) ?>
                    </p>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?= h($ctaButtonLink) ?>" class="btn" style="background:#fff;color:#6d28d9;"><?= h($ctaButtonText) ?></a>
                    <a href="<?= h($ctaSecondaryLink) ?>" style="display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border-radius:999px;color:#fff;font-weight:700;border:1px solid rgba(255,255,255,.32);background:rgba(255,255,255,.08);"><?= h($ctaSecondaryText) ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.client-demo-card{height:100%}
.demo-card-media{
    width:100%;
    aspect-ratio:16/9;
    overflow:hidden;
    border-bottom:1px solid var(--line);
    background:#f5f0ff;
}
.demo-card-image{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}
.demo-tag-row{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:16px;
}
.demo-tag-row .pill{
    background:#f5f0ff;
    color:#6d28d9;
}
@media (max-width: 900px){
    .ai-cs-feature-banner-grid{
        grid-template-columns:1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>