<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/evolution_normalizer.php';

function evolution_log_webhook(array $payload, array $normalized): int {
    $stmt = db()->prepare("
        INSERT INTO wa_webhook_logs
        (instance_name, event_type, message_id, remote_jid, payload_json, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $normalized['instance_name'] ?? '',
        $normalized['event_type'] ?? '',
        $normalized['message_id'] ?? '',
        $normalized['remote_jid'] ?? '',
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ]);

    return (int)db()->lastInsertId();
}

function evolution_find_or_create_contact(array $normalized): int {
    $stmt = db()->prepare("
        SELECT id FROM wa_contacts
        WHERE instance_name = ? AND remote_jid = ?
        LIMIT 1
    ");
    $stmt->execute([
        $normalized['instance_name'],
        $normalized['remote_jid']
    ]);
    $row = $stmt->fetch();

    if ($row) {
        $update = db()->prepare("
            UPDATE wa_contacts
            SET phone = ?, push_name = ?, last_message_at = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            $normalized['phone'],
            $normalized['push_name'],
            $normalized['message_time'],
            $row['id']
        ]);
        return (int)$row['id'];
    }

    $insert = db()->prepare("
        INSERT INTO wa_contacts
        (instance_name, remote_jid, phone, push_name, profile_name, last_message_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $insert->execute([
        $normalized['instance_name'],
        $normalized['remote_jid'],
        $normalized['phone'],
        $normalized['push_name'],
        $normalized['push_name'],
        $normalized['message_time']
    ]);

    return (int)db()->lastInsertId();
}

function evolution_find_or_create_conversation(array $normalized, int $contactId): int {
    $stmt = db()->prepare("
        SELECT id FROM wa_conversations
        WHERE instance_name = ? AND remote_jid = ?
        LIMIT 1
    ");
    $stmt->execute([
        $normalized['instance_name'],
        $normalized['remote_jid']
    ]);
    $row = $stmt->fetch();

    if ($row) {
        $update = db()->prepare("
            UPDATE wa_conversations
            SET wa_contact_id = ?, last_message_at = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            $contactId,
            $normalized['message_time'],
            $row['id']
        ]);
        return (int)$row['id'];
    }

    $insert = db()->prepare("
        INSERT INTO wa_conversations
        (instance_name, remote_jid, wa_contact_id, conversation_status, current_state, last_message_at, created_at, updated_at)
        VALUES (?, ?, ?, 'open', 'new', ?, NOW(), NOW())
    ");
    $insert->execute([
        $normalized['instance_name'],
        $normalized['remote_jid'],
        $contactId,
        $normalized['message_time']
    ]);

    return (int)db()->lastInsertId();
}

function evolution_message_exists(array $normalized): bool {
    if ($normalized['message_id'] === '') {
        return false;
    }

    $stmt = db()->prepare("
        SELECT id FROM wa_messages
        WHERE instance_name = ? AND message_id = ? AND event_type = ?
        LIMIT 1
    ");
    $stmt->execute([
        $normalized['instance_name'],
        $normalized['message_id'],
        $normalized['event_type']
    ]);

    return (bool)$stmt->fetch();
}

function evolution_store_message(array $payload, array $normalized, int $contactId, int $conversationId): int {
    $stmt = db()->prepare("
        INSERT INTO wa_messages
        (instance_name, conversation_id, wa_contact_id, event_type, message_id, remote_jid, direction, message_type, message_text, media_url, media_mime, raw_json, message_time, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $normalized['instance_name'],
        $conversationId,
        $contactId,
        $normalized['event_type'],
        $normalized['message_id'],
        $normalized['remote_jid'],
        $normalized['direction'],
        $normalized['message_type'],
        $normalized['message_text'],
        $normalized['media_url'],
        $normalized['media_mime'],
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $normalized['message_time']
    ]);

    $newId = (int)db()->lastInsertId();

    if (($normalized['direction'] ?? '') === 'inbound') {
        require_once __DIR__ . '/wa_sla_helper.php';
        require_once __DIR__ . '/wa_file_request_helper.php';
        require_once __DIR__ . '/wa_voice_helper.php';

        wa_sla_touch_inbound($conversationId, $contactId, (string)$normalized['message_time']);

        if (!empty($normalized['media_url'])) {
            wa_file_request_mark_received_by_conversation(
                $conversationId,
                (string)$normalized['media_url'],
                (string)($normalized['message_text'] ?? '')
            );
        }

        wa_voice_note_queue_if_needed([
            'id' => $newId,
            'conversation_id' => $conversationId,
            'wa_contact_id' => $contactId,
            'message_type' => $normalized['message_type'],
            'media_url' => $normalized['media_url'],
            'media_mime' => $normalized['media_mime'],
        ]);
    }

    return $newId;
}