<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_queue_action(
    string $instanceName,
    ?int $conversationId,
    ?int $contactId,
    string $remoteJid,
    string $actionType,
    array $payload,
    ?int $adminId = null
): int {
    $stmt = db()->prepare("
        INSERT INTO wa_action_queue
        (instance_name, conversation_id, wa_contact_id, remote_jid, action_type, payload_json, queue_status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([
        $instanceName,
        $conversationId,
        $contactId,
        $remoteJid,
        $actionType,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $adminId
    ]);

    return (int)db()->lastInsertId();
}