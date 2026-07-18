<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_file_request_create(
    int $conversationId,
    int $waContactId,
    string $type,
    string $message
): int {
    $stmt = db()->prepare("
        INSERT INTO wa_file_requests
        (conversation_id, wa_contact_id, request_type, request_message, request_status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([$conversationId, $waContactId, $type, $message]);

    return (int)db()->lastInsertId();
}

function wa_file_request_mark_requested(int $id): void {
    $stmt = db()->prepare("
        UPDATE wa_file_requests
        SET request_status = 'requested', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$id]);
}

function wa_file_request_mark_received_by_conversation(int $conversationId, string $fileUrl = '', string $fileName = ''): void {
    $stmt = db()->prepare("
        UPDATE wa_file_requests
        SET request_status = 'received',
            file_url = COALESCE(NULLIF(?, ''), file_url),
            file_name = COALESCE(NULLIF(?, ''), file_name),
            updated_at = NOW()
        WHERE conversation_id = ?
          AND request_status IN ('pending','requested')
    ");
    $stmt->execute([$fileUrl, $fileName, $conversationId]);
}