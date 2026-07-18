<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/evolution_helper.php';

http_response_code(200);
header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| Verify a shared secret before processing anything
|--------------------------------------------------------------------------
| Without this, anyone who finds the URL can inject fake WhatsApp messages,
| create contacts/conversations, and trigger AI auto-replies. Configure the
| secret via the EVOLUTION_WEBHOOK_SECRET env var or the
| `evolution_webhook_secret` site setting, and send it on the request as the
| `apikey` / `X-Webhook-Secret` header or a `?secret=` query parameter.
| If no secret is configured the webhook stays open (backwards compatible).
*/
$expectedSecret = aiserve_env('EVOLUTION_WEBHOOK_SECRET');
if ($expectedSecret === '') {
    $expectedSecret = get_setting('evolution_webhook_secret', '');
}

if ($expectedSecret !== '') {
    $providedSecret = $_SERVER['HTTP_APIKEY']
        ?? $_SERVER['HTTP_X_WEBHOOK_SECRET']
        ?? ($_GET['secret'] ?? '');

    if (!is_string($providedSecret) || !hash_equals($expectedSecret, $providedSecret)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    echo json_encode(['ok' => false, 'message' => 'Empty payload']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

try {
    $normalized = evolution_normalize_payload($payload);

    evolution_log_webhook($payload, $normalized);

    if (($normalized['remote_jid'] ?? '') === '') {
        echo json_encode(['ok' => true, 'message' => 'No remote_jid; logged only']);
        exit;
    }

    if (($normalized['message_id'] ?? '') !== '' && evolution_message_exists($normalized)) {
        echo json_encode(['ok' => true, 'message' => 'Duplicate ignored']);
        exit;
    }

    $contactId = evolution_find_or_create_contact($normalized);
    $conversationId = evolution_find_or_create_conversation($normalized, $contactId);
    $waMessageId = evolution_store_message($payload, $normalized, $contactId, $conversationId);

    if (
        get_setting('wa_ai_auto_reply_enabled', '0') === '1' &&
        ($normalized['direction'] ?? '') === 'inbound'
    ) {
        $stmt = db()->prepare("
            INSERT IGNORE INTO wa_ai_queue
            (wa_message_id, conversation_id, wa_contact_id, queue_status, state_before, created_at)
            VALUES (?, ?, ?, 'pending', 'new', NOW())
        ");
        $stmt->execute([
            $waMessageId,
            $conversationId,
            $contactId
        ]);
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Webhook processed',
        'contact_id' => $contactId,
        'conversation_id' => $conversationId,
        'wa_message_id' => $waMessageId
    ]);
} catch (Throwable $e) {
    error_log('[evolution webhook] ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'message' => 'Processing error'
    ]);
}