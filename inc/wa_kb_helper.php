<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_kb_match(string $messageText): ?array {
    $text = strtolower(trim($messageText));
    if ($text === '') {
        return null;
    }

    $stmt = db()->query("
        SELECT *
        FROM wa_knowledge_base
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $keywords = array_filter(array_map('trim', explode(',', (string)$row['objection_keywords'])));
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && strpos($text, strtolower($keyword)) !== false) {
                return $row;
            }
        }
    }

    return null;
}