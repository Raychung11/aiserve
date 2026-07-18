<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_memory_set(int $conversationId, int $waContactId, string $key, string $value): void {
    $stmt = db()->prepare("
        INSERT INTO wa_conversation_memory
        (conversation_id, wa_contact_id, memory_key, memory_value, updated_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            memory_value = VALUES(memory_value),
            updated_at = NOW()
    ");
    $stmt->execute([$conversationId, $waContactId, $key, $value]);
}

function wa_memory_get(int $conversationId, string $key, string $default = ''): string {
    $stmt = db()->prepare("
        SELECT memory_value
        FROM wa_conversation_memory
        WHERE conversation_id = ? AND memory_key = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $key]);
    $row = $stmt->fetch();

    return $row ? (string)$row['memory_value'] : $default;
}

function wa_memory_all(int $conversationId): array {
    $stmt = db()->prepare("
        SELECT memory_key, memory_value, updated_at
        FROM wa_conversation_memory
        WHERE conversation_id = ?
        ORDER BY memory_key ASC
    ");
    $stmt->execute([$conversationId]);
    return $stmt->fetchAll();
}