<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_quote_approval_create_if_missing(
    int $conversationId,
    int $waContactId,
    ?int $quoteRequestId,
    ?int $requestedBy = null
): void {
    $stmt = db()->prepare("
        SELECT id
        FROM wa_quote_approvals
        WHERE conversation_id = ?
          AND approval_status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$conversationId]);
    if ($stmt->fetch()) {
        return;
    }

    $insert = db()->prepare("
        INSERT INTO wa_quote_approvals
        (conversation_id, wa_contact_id, quote_request_id, requested_by, approval_status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $insert->execute([$conversationId, $waContactId, $quoteRequestId, $requestedBy]);
}