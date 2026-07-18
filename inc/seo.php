<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function seo_meta(string $pageKey, string $defaultTitle, string $defaultDescription): array {
    $stmt = db()->prepare("SELECT seo_title, seo_description FROM seo_pages WHERE page_key = ? LIMIT 1");
    $stmt->execute([$pageKey]);
    $row = $stmt->fetch();

    return [
        'title' => $row && !empty($row['seo_title']) ? (string)$row['seo_title'] : $defaultTitle,
        'description' => $row && !empty($row['seo_description']) ? (string)$row['seo_description'] : $defaultDescription,
    ];
}