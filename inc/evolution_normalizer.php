<?php
declare(strict_types=1);

function evolution_get_nested(array $data, array $path, $default = null) {
    $current = $data;
    foreach ($path as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) {
            return $default;
        }
        $current = $current[$key];
    }
    return $current;
}

function evolution_normalize_payload(array $payload): array {
    $eventType = (string)($payload['event'] ?? $payload['eventType'] ?? $payload['type'] ?? 'unknown');
    $instanceName = (string)(
        $payload['instance'] ??
        $payload['instanceName'] ??
        evolution_get_nested($payload, ['data', 'instance']) ??
        evolution_get_nested($payload, ['data', 'instanceName']) ??
        'default'
    );

    $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

    $messageId = (string)(
        evolution_get_nested($data, ['key', 'id']) ??
        $data['id'] ??
        $payload['messageId'] ??
        ''
    );

    $remoteJid = (string)(
        evolution_get_nested($data, ['key', 'remoteJid']) ??
        $data['remoteJid'] ??
        evolution_get_nested($payload, ['sender']) ??
        ''
    );

    $fromMe = (bool)(
        evolution_get_nested($data, ['key', 'fromMe']) ??
        $data['fromMe'] ??
        false
    );

    $pushName = (string)(
        $data['pushName'] ??
        $data['notifyName'] ??
        $payload['pushName'] ??
        ''
    );

    $messageType = 'unknown';
    $messageText = '';
    $mediaUrl = '';
    $mediaMime = '';

    if (isset($data['message']) && is_array($data['message'])) {
        $message = $data['message'];

        if (!empty($message['conversation'])) {
            $messageType = 'conversation';
            $messageText = (string)$message['conversation'];
        } elseif (!empty($message['extendedTextMessage']['text'])) {
            $messageType = 'extendedTextMessage';
            $messageText = (string)$message['extendedTextMessage']['text'];
        } elseif (!empty($message['imageMessage'])) {
            $messageType = 'imageMessage';
            $messageText = (string)($message['imageMessage']['caption'] ?? '');
            $mediaUrl = (string)($message['imageMessage']['url'] ?? '');
            $mediaMime = (string)($message['imageMessage']['mimetype'] ?? '');
        } elseif (!empty($message['documentMessage'])) {
            $messageType = 'documentMessage';
            $messageText = (string)($message['documentMessage']['fileName'] ?? '');
            $mediaUrl = (string)($message['documentMessage']['url'] ?? '');
            $mediaMime = (string)($message['documentMessage']['mimetype'] ?? '');
        } elseif (!empty($message['audioMessage'])) {
            $messageType = 'audioMessage';
            $mediaUrl = (string)($message['audioMessage']['url'] ?? '');
            $mediaMime = (string)($message['audioMessage']['mimetype'] ?? '');
        } elseif (!empty($message['videoMessage'])) {
            $messageType = 'videoMessage';
            $messageText = (string)($message['videoMessage']['caption'] ?? '');
            $mediaUrl = (string)($message['videoMessage']['url'] ?? '');
            $mediaMime = (string)($message['videoMessage']['mimetype'] ?? '');
        }
    } else {
        $messageText = (string)(
            $data['text'] ??
            $payload['text'] ??
            ''
        );
    }

    $timestampRaw = $data['messageTimestamp'] ?? $data['timestamp'] ?? time();
    $timestamp = is_numeric($timestampRaw) ? (int)$timestampRaw : time();
    if ($timestamp > 9999999999) {
        $timestamp = (int)floor($timestamp / 1000);
    }

    $direction = $fromMe ? 'outbound' : 'inbound';

    $phone = preg_replace('/@.*/', '', $remoteJid);
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);

    return [
        'instance_name' => $instanceName,
        'event_type'    => $eventType,
        'message_id'    => $messageId,
        'remote_jid'    => $remoteJid,
        'phone'         => $phone,
        'push_name'     => $pushName,
        'direction'     => $direction,
        'message_type'  => $messageType,
        'message_text'  => $messageText,
        'media_url'     => $mediaUrl,
        'media_mime'    => $mediaMime,
        'message_time'  => date('Y-m-d H:i:s', $timestamp),
    ];
}