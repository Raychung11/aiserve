<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_schedule_followup(
    int $conversationId,
    int $waContactId,
    string $followupType,
    string $message,
    string $scheduledFor,
    ?int $createdBy = null
): int {
    $stmt = db()->prepare("
        INSERT INTO wa_followups
        (conversation_id, wa_contact_id, followup_type, scheduled_for, followup_status, followup_message, created_by, created_at)
        VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
    ");
    $stmt->execute([
        $conversationId,
        $waContactId,
        $followupType,
        $scheduledFor,
        $message,
        $createdBy
    ]);

    return (int)db()->lastInsertId();
}

function wa_followup_exists(int $conversationId, string $followupType, string $datePrefix): bool {
    $stmt = db()->prepare("
        SELECT id
        FROM wa_followups
        WHERE conversation_id = ?
          AND followup_type = ?
          AND scheduled_for LIKE ?
        LIMIT 1
    ");
    $stmt->execute([
        $conversationId,
        $followupType,
        $datePrefix . '%'
    ]);
    return (bool)$stmt->fetch();
}