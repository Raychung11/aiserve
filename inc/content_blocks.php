<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function get_block(string $key, string $default = ''): string {
    $stmt = db()->prepare("SELECT block_content FROM content_blocks WHERE block_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['block_content'] : $default;
}

function get_block_title(string $key, string $default = ''): string {
    $stmt = db()->prepare("SELECT block_title FROM content_blocks WHERE block_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['block_title'] : $default;
}

function save_block(string $key, string $title, string $content): void {
    $stmt = db()->prepare("
        INSERT INTO content_blocks (block_key, block_title, block_content, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            block_title = VALUES(block_title),
            block_content = VALUES(block_content),
            updated_at = NOW()
    ");
    $stmt->execute([$key, $title, $content]);
}