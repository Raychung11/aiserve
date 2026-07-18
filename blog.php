<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$seo = seo_meta(
    'blog',
    'Blog | AiServe.my',
    'Read the latest insights, case studies, and AI business transformation content from AiServe.my.'
);

$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

require_once __DIR__ . '/inc/public_header.php';

$stmt = db()->query("
    SELECT *
    FROM blog_posts
    WHERE status = 'published'
    ORDER BY published_at DESC, id DESC
");
$rows = $stmt->fetchAll();
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container">
        <div class="eyebrow">Blog & Case Studies</div>
        <h1 style="max-width:900px;">Insights for AI-native business transformation</h1>
        <p style="max-width:820px;">
            Read updates, use cases, and case studies from AiServe.my.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid-3 blog-grid">
            <?php if (!$rows): ?>
                <div class="card">
                    <p>No posts yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rows as $post): ?>
                    <?php
                    $title = (string)($post['title'] ?? '');
                    $slug = (string)($post['slug'] ?? '');
                    $excerpt = (string)($post['excerpt'] ?? '');
                    $featuredImage = trim((string)($post['featured_image'] ?? ''));
                    $postType = (string)($post['post_type'] ?? 'blog');
                    $publishedAt = (string)($post['published_at'] ?? '');
                    ?>
                    <article class="card blog-card" style="padding:0;overflow:hidden;">
                        <a href="/blog/<?= urlencode($slug) ?>" style="display:block;">
                            <?php if ($featuredImage !== ''): ?>
                                <div class="blog-card-media">
                                    <img src="<?= h($featuredImage) ?>" alt="<?= h($title) ?>" class="blog-card-image">
                                </div>
                            <?php else: ?>
                                <div class="blog-card-media blog-card-placeholder">
                                    <span><?= h(strtoupper(substr($postType, 0, 1))) ?></span>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div style="padding:20px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                                <span class="pill"><?= h($postType) ?></span>
                                <?php if ($publishedAt !== ''): ?>
                                    <span class="small"><?= h(date('d M Y', strtotime($publishedAt))) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="margin:0 0 10px 0;line-height:1.25;">
                                <a href="/blog/<?= urlencode($slug) ?>">
                                    <?= h($title) ?>
                                </a>
                            </h3>

                            <p style="margin:0;color:var(--muted);">
                                <?= h($excerpt) ?>
                            </p>

                            <div style="margin-top:16px;">
                                <a href="/blog/<?= urlencode($slug) ?>" class="btn btn-secondary">Read More</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.blog-grid{
    align-items:stretch;
}

.blog-card{
    display:flex;
    flex-direction:column;
    height:100%;
}

.blog-card-media{
    width:100%;
    aspect-ratio: 16 / 9;
    overflow:hidden;
    border-bottom:1px solid var(--line);
    background:#f5f0ff;
}

.blog-card-image{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.blog-card-placeholder{
    display:grid;
    place-items:center;
    background:linear-gradient(135deg, #ede9fe, #ddd6fe);
    color:var(--primary);
    font-size:42px;
    font-weight:800;
}

.blog-card h3 a:hover{
    color:var(--primary);
}

@media (max-width: 760px){
    .blog-card-media{
        aspect-ratio: 16 / 10;
    }
}
</style>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>