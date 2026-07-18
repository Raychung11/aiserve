<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_create_internal_notification(
    int $conversationId,
    int $waContactId,
    ?int $assignedAdminId,
    string $type,
    string $title,
    string $message
): int {
    $stmt = db()->prepare("
        INSERT INTO wa_internal_notifications
        (conversation_id, wa_contact_id, assigned_admin_id, notification_type, title, message_text, notify_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $conversationId,
        $waContactId,
        $assignedAdminId,
        $type,
        $title,
        $message
    ]);

    return (int)db()->lastInsertId();
}

function wa_notification_exists_today(int $conversationId, string $type): bool {
    $stmt = db()->prepare("
        SELECT id
        FROM wa_internal_notifications
        WHERE conversation_id = ?
          AND notification_type = ?
          AND created_at >= CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $type]);
    return (bool)$stmt->fetch();
}