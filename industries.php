<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$seo = seo_meta(
    'industries',
    'Industries | AiServe.my',
    'Explore the industries supported by AiServe.my, from retail and furniture to F&B, property, education, and multi-branch operations.'
);

$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

require_once __DIR__ . '/inc/public_header.php';

$stmt = db()->query("
    SELECT *
    FROM industry_pages
    WHERE status = 'published'
    ORDER BY is_featured DESC, sort_order ASC, id DESC
");
$rows = $stmt->fetchAll();

$featured = [];
$regular = [];
foreach ($rows as $industry) {
    if ((int)($industry['is_featured'] ?? 0) === 1 && count($featured) < 2) {
        $featured[] = $industry;
    } else {
        $regular[] = $industry;
    }
}
?>

<section class="hero-section" style="
    padding:84px 0 48px;
    background:
        radial-gradient(circle at top right, rgba(139,92,246,.16), transparent 24%),
        linear-gradient(135deg, rgba(109,40,217,.96), rgba(139,92,246,.88));
    color:#fff;
">
    <div class="container">
        <div style="max-width:860px;">
            <div style="display:inline-block;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.14);font-size:13px;font-weight:700;margin-bottom:18px;">
                Industry Solutions
            </div>
            <h1 style="font-size:clamp(38px, 6vw, 64px);line-height:1.03;margin:0 0 18px 0;color:#fff;">
                AI systems shaped around real industry workflows
            </h1>
            <p style="font-size:18px;line-height:1.8;max-width:760px;color:rgba(255,255,255,.92);margin:0;">
                AiServe.my supports businesses across multiple sectors by turning customer service, workflow, reporting, and management visibility into one practical AI-enabled layer.
            </p>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label">Featured Industries</div>
            <h2>Where execution matters most</h2>
            <p>Selected sectors where AI, workflow logic, and reporting visibility can create immediate operational value.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="industry-featured-grid">
            <?php foreach ($featured as $industry): ?>
                <article class="card" style="padding:0;overflow:hidden;">
                    <?php if (!empty($industry['cover_image'])): ?>
                        <div style="aspect-ratio:16/9;background:#f5f0ff;overflow:hidden;">
                            <img src="<?= h((string)$industry['cover_image']) ?>" alt="<?= h((string)$industry['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    <?php endif; ?>

                    <div style="padding:24px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
                            <span class="pill"><?= h((string)($industry['badge_text'] ?: 'Industry')) ?></span>
                            <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)($industry['icon_text'] ?: 'AI')) ?></span>
                        </div>

                        <h3 style="margin:0 0 12px 0;font-size:28px;line-height:1.12;"><?= h((string)$industry['title']) ?></h3>

                        <?php if (!empty($industry['short_description'])): ?>
                            <p style="margin:0;color:var(--muted);line-height:1.8;font-size:16px;"><?= h((string)$industry['short_description']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($industry['cta_text']) && !empty($industry['cta_link'])): ?>
                            <div style="margin-top:18px;">
                                <a href="<?= h((string)$industry['cta_link']) ?>" class="btn btn-secondary"><?= h((string)$industry['cta_text']) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label">All Supported Industries</div>
            <h2>Built for practical deployment</h2>
            <p>Add more industries any time from admin, with cover images, richer text, and featured positioning.</p>
        </div>

        <div class="grid-3 industry-grid">
            <?php if (!$rows): ?>
                <div class="card"><p>No industries published yet.</p></div>
            <?php else: ?>
                <?php foreach ($regular as $industry): ?>
                    <article class="card industry-card" style="padding:0;overflow:hidden;">
                        <?php if (!empty($industry['cover_image'])): ?>
                            <div class="industry-card-media">
                                <img src="<?= h((string)$industry['cover_image']) ?>" alt="<?= h((string)$industry['title']) ?>" class="industry-card-image">
                            </div>
                        <?php else: ?>
                            <div class="industry-card-media industry-card-placeholder">
                                <span><?= h((string)($industry['icon_text'] ?: strtoupper(substr((string)$industry['title'], 0, 1)))) ?></span>
                            </div>
                        <?php endif; ?>

                        <div style="padding:20px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                                <?php if (!empty($industry['badge_text'])): ?>
                                    <span class="pill"><?= h((string)$industry['badge_text']) ?></span>
                                <?php endif; ?>
                                <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)($industry['icon_text'] ?: 'AI')) ?></span>
                            </div>

                            <h3 style="margin:0 0 10px 0;line-height:1.2;"><?= h((string)$industry['title']) ?></h3>

                            <?php if (!empty($industry['short_description'])): ?>
                                <p style="margin:0;color:var(--muted);line-height:1.75;"><?= h((string)$industry['short_description']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($industry['cta_text']) && !empty($industry['cta_link'])): ?>
                                <div style="margin-top:16px;">
                                    <a href="<?= h((string)$industry['cta_link']) ?>" class="btn btn-secondary"><?= h((string)$industry['cta_text']) ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="card" style="padding:30px;background:linear-gradient(135deg, rgba(109,40,217,.96), rgba(139,92,246,.86));color:#fff;border:none;box-shadow:0 24px 60px rgba(109,40,217,.18);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="max-width:720px;">
                    <div style="font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.85;margin-bottom:10px;">Need Industry-Specific Setup</div>
                    <h2 style="margin:0 0 10px 0;font-size:clamp(28px,4vw,42px);line-height:1.08;color:#fff;">
                        We can shape the system around your workflow
                    </h2>
                    <p style="margin:0;color:rgba(255,255,255,.92);font-size:17px;line-height:1.75;">
                        From inquiry handling to reporting structure and AI guidance, we tailor the business layer to your real operational needs.
                    </p>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="/contact.php" class="btn" style="background:#fff;color:#6d28d9;">Talk to Us</a>
                    <a href="/demos.php" style="display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border-radius:999px;color:#fff;font-weight:700;border:1px solid rgba(255,255,255,.32);background:rgba(255,255,255,.08);">View Demos</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.industry-grid{align-items:stretch}
.industry-card{display:flex;flex-direction:column;height:100%}
.industry-card-media{width:100%;aspect-ratio:16/9;overflow:hidden;border-bottom:1px solid var(--line);background:#f5f0ff}
.industry-card-image{width:100%;height:100%;object-fit:cover;display:block}
.industry-card-placeholder{display:grid;place-items:center;background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:var(--primary);font-size:42px;font-weight:800}
@media (max-width: 900px){
    .industry-featured-grid{grid-template-columns:1fr !important;}
}
</style>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>