<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/seo.php';

header('Content-Type: application/xml; charset=utf-8');

/** @var array<int,array{loc:string,lastmod?:string,priority?:string}> $urls */
$urls = [];

$staticPages = [
    ['', '1.0'],
    ['ai-bos.php', '0.9'],
    ['solutions.php', '0.9'],
    ['sme-ai-transformation.php', '0.9'],
    ['industries.php', '0.8'],
    ['projects.php', '0.8'],
    ['demos.php', '0.8'],
    ['ai-customer-service-demo.php', '0.8'],
    ['about.php', '0.6'],
    ['contact.php', '0.7'],
    ['blog.php', '0.7'],
    ['search.php', '0.3'],
];
foreach ($staticPages as [$path, $priority]) {
    $urls[] = ['loc' => site_url($path), 'priority' => $priority];
}

try {
    $posts = db()->query("SELECT slug, COALESCE(updated_at, published_at, created_at) AS lastmod
                          FROM blog_posts WHERE status = 'published'")->fetchAll();
    foreach ($posts as $post) {
        $item = ['loc' => site_url('blog/' . rawurlencode((string)$post['slug'])), 'priority' => '0.6'];
        if (!empty($post['lastmod'])) {
            $ts = strtotime((string)$post['lastmod']);
            if ($ts) {
                $item['lastmod'] = date('c', $ts);
            }
        }
        $urls[] = $item;
    }
} catch (Throwable $e) {
    // table may be missing; skip silently
}

try {
    $pages = db()->query("SELECT slug, COALESCE(updated_at, created_at) AS lastmod
                          FROM landing_pages WHERE status = 'published'")->fetchAll();
    foreach ($pages as $page) {
        $item = ['loc' => site_url('p/' . rawurlencode((string)$page['slug'])), 'priority' => '0.5'];
        if (!empty($page['lastmod'])) {
            $ts = strtotime((string)$page['lastmod']);
            if ($ts) {
                $item['lastmod'] = date('c', $ts);
            }
        }
        $urls[] = $item;
    }
} catch (Throwable $e) {
    // skip silently
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8') ?></loc>
<?php if (!empty($url['lastmod'])): ?>
        <lastmod><?= htmlspecialchars($url['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
<?php endif; ?>
<?php if (!empty($url['priority'])): ?>
        <priority><?= htmlspecialchars($url['priority'], ENT_QUOTES, 'UTF-8') ?></priority>
<?php endif; ?>
    </url>
<?php endforeach; ?>
</urlset>
