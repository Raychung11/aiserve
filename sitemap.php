<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    APP_URL . '/',
    APP_URL . '/about.php',
    APP_URL . '/ai-bos.php',
    APP_URL . '/solutions.php',
    APP_URL . '/industries.php',
    APP_URL . '/contact.php',
    APP_URL . '/blog.php',
    APP_URL . '/search.php',
];

$posts = db()->query("SELECT slug FROM blog_posts WHERE status = 'published'")->fetchAll();
foreach ($posts as $post) {
    $urls[] = APP_URL . '/blog/' . rawurlencode($post['slug']);
}

$pages = db()->query("SELECT slug FROM landing_pages WHERE status = 'published'")->fetchAll();
foreach ($pages as $page) {
    $urls[] = APP_URL . '/p/' . rawurlencode($page['slug']);
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></loc>
    </url>
<?php endforeach; ?>
</urlset>