<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_sla_touch_inbound(int $conversationId, int $waContactId, string $time): void {
    $stmt = db()->prepare("
        INSERT INTO wa_sla_logs
        (conversation_id, wa_contact_id, first_inbound_at, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            first_inbound_at = COALESCE(first_inbound_at, VALUES(first_inbound_at)),
            updated_at = NOW()
    ");
    $stmt->execute([$conversationId, $waContactId, $time]);
}

function wa_sla_touch_outbound(int $conversationId, int $waContactId, string $time): void {
    $stmt = db()->prepare("
        SELECT first_inbound_at, first_outbound_at
        FROM wa_sla_logs
        WHERE conversation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();

    if (!$row) {
        $insert = db()->prepare("
            INSERT INTO wa_sla_logs
            (conversation_id, wa_contact_id, first_outbound_at, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $insert->execute([$conversationId, $waContactId, $time]);
        return;
    }

    $firstResponseMinutes = null;
    if (!empty($row['first_inbound_at']) && empty($row['first_outbound_at'])) {
        $firstResponseMinutes = max(0, (int)((strtotime($time) - strtotime($row['first_inbound_at'])) / 60));
    }

    $upd = db()->prepare("
        UPDATE wa_sla_logs
        SET first_outbound_at = COALESCE(first_outbound_at, ?),
            first_response_minutes = COALESCE(first_response_minutes, ?),
            updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $upd->execute([$time, $firstResponseMinutes, $conversationId]);
}

function wa_sla_close(int $conversationId, int $waContactId, string $time): void {
    $stmt = db()->prepare("
        SELECT first_inbound_at
        FROM wa_sla_logs
        WHERE conversation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();

    $resolutionMinutes = null;
    if (!empty($row['first_inbound_at'])) {
        $resolutionMinutes = max(0, (int)((strtotime($time) - strtotime($row['first_inbound_at'])) / 60));
    }

    $upd = db()->prepare("
        UPDATE wa_sla_logs
        SET closed_at = ?,
            total_resolution_minutes = ?,
            updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $upd->execute([$time, $resolutionMinutes, $conversationId]);
}