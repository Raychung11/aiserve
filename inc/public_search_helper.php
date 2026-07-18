<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function public_search_results(string $q): array {
    $q = trim($q);
    if ($q === '') return [];

    $like = '%' . $q . '%';

    $results = [];

    $stmt = db()->prepare("
        SELECT 'blog' AS type, title, slug, excerpt AS summary
        FROM blog_posts
        WHERE status = 'published' AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)
        ORDER BY published_at DESC
        LIMIT 10
    ");
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $row['url'] = '/blog_view.php?slug=' . urlencode($row['slug']);
        $results[] = $row;
    }

    $stmt = db()->prepare("
        SELECT 'page' AS type, title, slug, hero_text AS summary
        FROM landing_pages
        WHERE status = 'published' AND (title LIKE ? OR hero_title LIKE ? OR hero_text LIKE ? OR body_content LIKE ?)
        ORDER BY id DESC
        LIMIT 10
    ");
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $row['url'] = '/page.php?slug=' . urlencode($row['slug']);
        $results[] = $row;
    }

    return $results;
}