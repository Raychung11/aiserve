<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    exit('Post not found');
}

$post = null;

try {
    $stmt = db()->prepare("
        SELECT *
        FROM blog_posts
        WHERE slug = ?
          AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
} catch (Throwable $e) {
    $post = null;
}

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post Not Found | AiServe.my';
    $pageDescription = 'The requested blog post could not be found.';
    require_once __DIR__ . '/inc/public_header.php';
    ?>
    <section class="section">
        <div class="container">
            <div class="card">
                <h1 style="margin-top:0;">Post not found</h1>
                <p class="small">The requested article is not available.</p>
                <a href="/blog.php" class="btn-primary">Back to Blog</a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/inc/public_footer.php';
    exit;
}

$title = (string)($post['title'] ?? 'Blog Post');
$excerpt = trim((string)($post['excerpt'] ?? ''));
$content = trim((string)($post['content'] ?? ''));
$featuredImage = trim((string)($post['featured_image'] ?? ''));
$postType = trim((string)($post['post_type'] ?? 'blog'));
$publishedAtRaw = (string)($post['published_at'] ?? $post['created_at'] ?? '');
$publishedAtFormatted = $publishedAtRaw !== '' ? date('d M Y', strtotime($publishedAtRaw)) : '';

$seo = seo_meta(
    'blog_view',
    $title . ' | AiServe.my',
    $excerpt !== '' ? $excerpt : mb_strimwidth(strip_tags($content), 0, 160, '...')
);

$pageTitle = $seo['title'];
$pageDescription = $seo['description'];

// AI-search / rich-result signals for this article
$ogType = 'article';
$canonicalUrl = site_url('blog/' . rawurlencode($slug));
if ($featuredImage !== '') {
    $ogImage = site_url($featuredImage);
}
$pageJsonLd = seo_jsonld_block(seo_article_data($post));

require_once __DIR__ . '/inc/public_header.php';

/*
|--------------------------------------------------------------------------
| Simple rich content formatter
|--------------------------------------------------------------------------
*/
if (!function_exists('render_blog_content')) {
    function render_blog_content(string $content): string {
        $blocks = preg_split("/\\R{2,}/", $content) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            // Divider
            if ($block === '---') {
                $html .= '<hr>';
                continue;
            }

            // H2
            if (preg_match('/^##\s+(.+)$/u', $block, $m)) {
                $html .= '<h2>' . h(trim($m[1])) . '</h2>';
                continue;
            }

            // H3
            if (preg_match('/^###\s+(.+)$/u', $block, $m)) {
                $html .= '<h3>' . h(trim($m[1])) . '</h3>';
                continue;
            }

            // Quote block
            if (preg_match('/^>\s+/m', $block)) {
                $lines = preg_split("/\\R/", $block) ?: [];
                $quoteParts = [];
                foreach ($lines as $line) {
                    $line = preg_replace('/^>\s?/', '', trim($line));
                    if ($line !== '') {
                        $quoteParts[] = h($line);
                    }
                }
                $html .= '<blockquote>' . implode('<br>', $quoteParts) . '</blockquote>';
                continue;
            }

            // Bullet / numbered list
            if (preg_match('/^(\-|\*|\d+\.)\s+/m', $block)) {
                $lines = preg_split("/\\R/", $block) ?: [];
                $items = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $line = preg_replace('/^(\-|\*|\d+\.)\s+/', '', $line);
                    $line = h($line);
                    $line = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line);
                    $items[] = '<li>' . $line . '</li>';
                }
                $html .= '<ul>' . implode('', $items) . '</ul>';
                continue;
            }

            // Standard paragraph with bold support
            $safe = h($block);
            $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $safe);
            $safe = nl2br($safe);

            $html .= '<p>' . $safe . '</p>';
        }

        return $html;
    }
}
?>

<section class="section" style="padding-top:42px;">
    <div class="container" style="max-width:980px;">
        <article class="card blog-article-card" style="padding:0;overflow:hidden;">
            <?php if ($featuredImage !== ''): ?>
                <div class="blog-cover">
                    <img src="<?= h($featuredImage) ?>" alt="<?= h($title) ?>">
                </div>
            <?php endif; ?>

            <div class="blog-article-inner">
                <div class="blog-top-label-row">
                    <span class="pill"><?= h($postType !== '' ? $postType : 'blog') ?></span>
                    <?php if ($publishedAtFormatted !== ''): ?>
                        <span class="small">Published on <?= h($publishedAtFormatted) ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="blog-title"><?= h($title) ?></h1>

                <?php if ($excerpt !== ''): ?>
                    <div class="blog-excerpt">
                        <?= h($excerpt) ?>
                    </div>
                <?php endif; ?>

                <div class="blog-content">
                    <?= render_blog_content($content) ?>
                </div>

                <div class="blog-bottom-actions">
                    <a href="/blog.php" class="btn-secondary">Back to Blog</a>
                </div>
            </div>
        </article>
    </div>
</section>

<style>
.blog-article-card{
    border-radius:28px;
    box-shadow:0 22px 60px rgba(109,40,217,.10);
    background:rgba(255,255,255,.92);
}

.blog-cover{
    width:100%;
    background:#f5f0ff;
    border-bottom:1px solid var(--line);
}

.blog-cover img{
    width:100%;
    height:auto;
    max-height:460px;
    object-fit:cover;
    display:block;
}

.blog-article-inner{
    padding:38px;
}

.blog-top-label-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:16px;
}

.blog-title{
    margin:0 0 18px 0;
    font-size:clamp(34px,5vw,54px);
    line-height:1.04;
    letter-spacing:-1.4px;
    color:var(--dark);
    max-width:800px;
}

.blog-excerpt{
    max-width:760px;
    padding:18px 20px;
    background:linear-gradient(180deg,#faf8ff,#f6f1ff);
    border:1px solid var(--line);
    border-radius:18px;
    color:var(--muted);
    font-size:17px;
    line-height:1.8;
    margin-bottom:30px;
}

.blog-content{
    max-width:760px;
}

.blog-content h2{
    margin:38px 0 14px 0;
    font-size:30px;
    line-height:1.14;
    letter-spacing:-0.7px;
    color:var(--dark);
}

.blog-content h3{
    margin:28px 0 12px 0;
    font-size:22px;
    line-height:1.2;
    color:var(--dark);
}

.blog-content p{
    margin:0 0 18px 0;
    font-size:18px;
    line-height:1.95;
    color:var(--text);
    text-align:left;
}

.blog-content ul{
    margin:0 0 22px 0;
    padding-left:22px;
}

.blog-content li{
    margin:0 0 10px 0;
    color:var(--text);
    font-size:17px;
    line-height:1.85;
}

.blog-content blockquote{
    margin:0 0 22px 0;
    padding:18px 20px;
    border-left:4px solid var(--primary);
    background:#faf8ff;
    border-radius:16px;
    color:var(--muted);
    font-size:17px;
    line-height:1.8;
}

.blog-content hr{
    border:none;
    border-top:1px solid var(--line);
    margin:30px 0;
}

.blog-content strong{
    color:var(--dark);
    font-weight:800;
}

.blog-bottom-actions{
    margin-top:34px;
    padding-top:22px;
    border-top:1px solid var(--line);
}

@media (max-width: 760px){
    .blog-article-inner{
        padding:22px;
    }

    .blog-title{
        letter-spacing:-0.8px;
    }

    .blog-content p{
        font-size:16px;
        line-height:1.85;
    }

    .blog-content h2{
        font-size:24px;
    }

    .blog-content h3{
        font-size:20px;
    }
}
</style>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>