<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$seo = seo_meta(
    'demos',
    'Demo Websites | AiServe.my',
    'Explore selected websites and digital systems built by AiServe.my, including AI-enabled business platforms, HR systems, and property solutions.'
);

$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

require_once __DIR__ . '/inc/public_header.php';

$stmt = db()->query("
    SELECT *
    FROM demo_sites
    WHERE status = 'published'
    ORDER BY is_featured DESC, sort_order ASC, id DESC
");
$rows = $stmt->fetchAll();

$featured = [];
$regular = [];

foreach ($rows as $demo) {
    if ((int)($demo['is_featured'] ?? 0) === 1 && count($featured) < 3) {
        $featured[] = $demo;
    } else {
        $regular[] = $demo;
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
            <div style="
                display:inline-block;
                padding:8px 12px;
                border-radius:999px;
                background:rgba(255,255,255,.14);
                font-size:13px;
                font-weight:700;
                margin-bottom:18px;
            ">
                Built by AiServe.my
            </div>

            <h1 style="font-size:clamp(38px, 6vw, 64px);line-height:1.03;margin:0 0 18px 0;color:#fff;">
                Demo websites and systems we have built
            </h1>

            <p style="font-size:18px;line-height:1.8;max-width:760px;color:rgba(255,255,255,.92);margin:0 0 24px 0;">
                Explore selected projects across property, HR, safety, and AI-enabled business operations.
                These demos show how we turn business ideas into practical digital systems.
            </p>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="#featured-demos" class="btn-primary" style="background:#fff;color:#6d28d9;">View Featured Demos</a>
                <a href="/contact.php" style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    min-height:46px;
                    padding:0 18px;
                    border-radius:999px;
                    color:#fff;
                    font-weight:700;
                    border:1px solid rgba(255,255,255,.35);
                    background:rgba(255,255,255,.08);
                ">Build With Us</a>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:24px;">
    <div class="container">
        <div class="stats" style="margin-top:0;">
            <div class="stat">
                <strong><?= count($rows) ?></strong>
                <span>Published Demo Projects</span>
            </div>
            <div class="stat">
                <strong><?= count($featured) ?></strong>
                <span>Featured Showcase Projects</span>
            </div>
            <div class="stat">
                <strong>AI + BI</strong>
                <span>Business Layer Positioning</span>
            </div>
            <div class="stat">
                <strong>Live</strong>
                <span>Real Systems, Not Mockups</span>
            </div>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section" id="featured-demos">
    <div class="container">
        <div class="section-head">
            <div class="label">Featured Showcase</div>
            <h2>Selected execution examples</h2>
            <p>
                A few highlighted projects that reflect our ability to build practical, business-ready digital systems.
            </p>
        </div>

        <div style="display:grid;grid-template-columns:1.15fr .85fr;gap:20px;" class="featured-demo-grid">
            <?php $main = $featured[0]; ?>
            <article class="card featured-main-card" style="padding:0;overflow:hidden;">
                <a href="<?= h((string)($main['live_url'] ?? '#')) ?>" target="_blank" style="display:block;">
                    <?php if (!empty($main['cover_image'])): ?>
                        <div class="demo-card-media large">
                            <img src="<?= h((string)$main['cover_image']) ?>" alt="<?= h((string)$main['title']) ?>" class="demo-card-image">
                        </div>
                    <?php else: ?>
                        <div class="demo-card-media large demo-card-placeholder">
                            <span><?= h(strtoupper(substr((string)$main['title'], 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                </a>

                <div style="padding:24px;">
                    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
                        <span class="pill"><?= h((string)($main['category'] ?? 'Demo')) ?></span>
                        <?php if (!empty($main['badge_text'])): ?>
                            <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$main['badge_text']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 style="margin:0 0 12px 0;font-size:32px;line-height:1.12;">
                        <?= h((string)$main['title']) ?>
                    </h3>

                    <?php if (!empty($main['short_description'])): ?>
                        <p style="margin:0;color:var(--muted);font-size:17px;line-height:1.8;">
                            <?= h((string)$main['short_description']) ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
                        <?php if (!empty($main['live_url'])): ?>
                            <a href="<?= h((string)$main['live_url']) ?>" target="_blank" class="btn btn-primary">Visit Live Demo</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <div style="display:grid;gap:20px;">
                <?php foreach (array_slice($featured, 1) as $demo): ?>
                    <article class="card demo-side-card" style="padding:0;overflow:hidden;">
                        <div style="display:grid;grid-template-columns:160px 1fr;min-height:100%;" class="side-card-grid">
                            <a href="<?= h((string)($demo['live_url'] ?? '#')) ?>" target="_blank" style="display:block;height:100%;">
                                <?php if (!empty($demo['cover_image'])): ?>
                                    <div class="demo-card-media side">
                                        <img src="<?= h((string)$demo['cover_image']) ?>" alt="<?= h((string)$demo['title']) ?>" class="demo-card-image">
                                    </div>
                                <?php else: ?>
                                    <div class="demo-card-media side demo-card-placeholder">
                                        <span><?= h(strtoupper(substr((string)$demo['title'], 0, 1))) ?></span>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div style="padding:18px;">
                                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
                                    <span class="pill"><?= h((string)($demo['category'] ?? 'Demo')) ?></span>
                                    <?php if (!empty($demo['badge_text'])): ?>
                                        <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$demo['badge_text']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 style="margin:0 0 8px 0;font-size:22px;line-height:1.2;">
                                    <?= h((string)$demo['title']) ?>
                                </h3>

                                <?php if (!empty($demo['short_description'])): ?>
                                    <p style="margin:0;color:var(--muted);font-size:15px;line-height:1.75;">
                                        <?= h((string)$demo['short_description']) ?>
                                    </p>
                                <?php endif; ?>

                                <div style="margin-top:14px;">
                                    <?php if (!empty($demo['live_url'])): ?>
                                        <a href="<?= h((string)$demo['live_url']) ?>" target="_blank">Open Demo</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label">All Demos</div>
            <h2>More systems and website examples</h2>
            <p>
                Browse additional live projects that reflect our execution capability across multiple industries.
            </p>
        </div>

        <div class="grid-3 demo-grid">
            <?php if (!$rows): ?>
                <div class="card">
                    <p>No demo sites published yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($regular as $demo): ?>
                    <article class="card demo-card" style="padding:0;overflow:hidden;">
                        <?php if (!empty($demo['cover_image'])): ?>
                            <div class="demo-card-media">
                                <img src="<?= h((string)$demo['cover_image']) ?>" alt="<?= h((string)$demo['title']) ?>" class="demo-card-image">
                            </div>
                        <?php else: ?>
                            <div class="demo-card-media demo-card-placeholder">
                                <span><?= h(strtoupper(substr((string)$demo['title'], 0, 1))) ?></span>
                            </div>
                        <?php endif; ?>

                        <div style="padding:20px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                                <?php if (!empty($demo['category'])): ?>
                                    <span class="pill"><?= h((string)$demo['category']) ?></span>
                                <?php endif; ?>

                                <?php if (!empty($demo['badge_text'])): ?>
                                    <span class="small" style="font-weight:700;color:var(--primary);"><?= h((string)$demo['badge_text']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="margin:0 0 10px 0;line-height:1.25;"><?= h((string)$demo['title']) ?></h3>

                            <?php if (!empty($demo['short_description'])): ?>
                                <p style="margin:0;color:var(--muted);line-height:1.75;">
                                    <?= h((string)$demo['short_description']) ?>
                                </p>
                            <?php endif; ?>

                            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                                <?php if (!empty($demo['live_url'])): ?>
                                    <a href="<?= h((string)$demo['live_url']) ?>" target="_blank" class="btn btn-secondary">Visit Demo</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="card" style="
            padding:30px;
            background:linear-gradient(135deg, rgba(109,40,217,.96), rgba(139,92,246,.86));
            color:#fff;
            border:none;
            box-shadow:0 24px 60px rgba(109,40,217,.18);
        ">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="max-width:720px;">
                    <div style="font-size:13px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.85;margin-bottom:10px;">
                        Build With AiServe.my
                    </div>
                    <h2 style="margin:0 0 10px 0;font-size:clamp(28px,4vw,42px);line-height:1.08;color:#fff;">
                        Want us to build something like this for your business?
                    </h2>
                    <p style="margin:0;color:rgba(255,255,255,.92);font-size:17px;line-height:1.75;">
                        We help companies turn ideas into practical websites, systems, AI workflows, and business operating layers.
                    </p>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="/contact.php" class="btn" style="background:#fff;color:#6d28d9;">Talk to Us</a>
                    <a href="/solutions.php" style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        min-height:46px;
                        padding:0 18px;
                        border-radius:999px;
                        color:#fff;
                        font-weight:700;
                        border:1px solid rgba(255,255,255,.32);
                        background:rgba(255,255,255,.08);
                    ">View Solutions</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.demo-grid{
    align-items:stretch;
}

.demo-card{
    display:flex;
    flex-direction:column;
    height:100%;
}

.demo-card-media{
    width:100%;
    aspect-ratio:16 / 9;
    overflow:hidden;
    border-bottom:1px solid var(--line);
    background:#f5f0ff;
}

.demo-card-media.large{
    aspect-ratio:16 / 8.5;
}

.demo-card-media.side{
    height:100%;
    aspect-ratio:auto;
    border-bottom:none;
    border-right:1px solid var(--line);
}

.demo-card-image{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.demo-card-placeholder{
    display:grid;
    place-items:center;
    background:linear-gradient(135deg, #ede9fe, #ddd6fe);
    color:var(--primary);
    font-size:42px;
    font-weight:800;
}

.featured-main-card{
    height:100%;
}

@media (max-width: 1024px){
    .featured-demo-grid{
        grid-template-columns:1fr !important;
    }

    .side-card-grid{
        grid-template-columns:1fr !important;
    }

    .demo-card-media.side{
        aspect-ratio:16 / 9;
        border-right:none;
        border-bottom:1px solid var(--line);
    }
}

@media (max-width: 760px){
    .demo-card-media,
    .demo-card-media.large,
    .demo-card-media.side{
        aspect-ratio:16 / 10;
    }
}
</style>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>