<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function evolution_api_request(string $method, string $endpoint, array $body = []): array {
    $baseUrl = rtrim(get_setting('evolution_base_url'), '/');
    $apiKey  = get_setting('evolution_api_key');

    if ($baseUrl === '' || $apiKey === '') {
        return ['ok' => false, 'status' => 0, 'response' => ['message' => 'Evolution settings missing']];
    }

    $url = $baseUrl . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        $decoded = ['raw' => $raw];
    }

    if ($curlError !== '') {
        return ['ok' => false, 'status' => $httpCode, 'response' => ['message' => $curlError]];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'status' => $httpCode,
        'response' => $decoded
    ];
}

function evolution_send_text(string $instanceName, string $number, string $text): array {
    $payload = [
        'number' => $number,
        'text'   => $text
    ];

    return evolution_api_request('POST', '/message/sendText/' . rawurlencode($instanceName), $payload);
}

function evolution_send_image(string $instanceName, string $number, string $imageUrl, string $caption = ''): array {
    $payload = [
        'number' => $number,
        'mediatype' => 'image',
        'mimetype' => 'image/jpeg',
        'media' => $imageUrl,
        'caption' => $caption
    ];

    return evolution_api_request('POST', '/message/sendMedia/' . rawurlencode($instanceName), $payload);
}

function evolution_send_document(string $instanceName, string $number, string $fileUrl, string $fileName = 'document.pdf', string $caption = ''): array {
    $payload = [
        'number' => $number,
        'mediatype' => 'document',
        'mimetype' => 'application/pdf',
        'media' => $fileUrl,
        'fileName' => $fileName,
        'caption' => $caption
    ];

    return evolution_api_request('POST', '/message/sendMedia/' . rawurlencode($instanceName), $payload);
}

function wa_log_outbound(
    string $instanceName,
    ?int $conversationId,
    ?int $contactId,
    string $remoteJid,
    string $actionType,
    array $requestPayload,
    array $responsePayload,
    string $sendStatus,
    ?int $adminId
): void {
    $stmt = db()->prepare("
        INSERT INTO wa_outbound_logs
        (instance_name, conversation_id, wa_contact_id, remote_jid, action_type, request_json, response_json, send_status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $instanceName,
        $conversationId,
        $contactId,
        $remoteJid,
        $actionType,
        json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        json_encode($responsePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $sendStatus,
        $adminId
    ]);
}

function wa_store_outbound_message(
    string $instanceName,
    ?int $conversationId,
    ?int $contactId,
    string $remoteJid,
    string $eventType,
    string $messageId,
    string $messageType,
    string $messageText,
    string $mediaUrl = '',
    string $mediaMime = ''
): void {
    $stmt = db()->prepare("
        INSERT IGNORE INTO wa_messages
        (instance_name, conversation_id, wa_contact_id, event_type, message_id, remote_jid, direction, message_type, message_text, media_url, media_mime, raw_json, message_time, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'outbound', ?, ?, ?, ?, '{}', NOW(), NOW())
    ");
    $stmt->execute([
        $instanceName,
        $conversationId,
        $contactId,
        $eventType,
        $messageId,
        $remoteJid,
        $messageType,
        $messageText,
        $mediaUrl,
        $mediaMime
    ]);

    if ($conversationId && $contactId) {
        require_once __DIR__ . '/wa_sla_helper.php';
        wa_sla_touch_outbound((int)$conversationId, (int)$contactId, date('Y-m-d H:i:s'));
    }
}