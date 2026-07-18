<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/evolution_sender.php';

function wa_find_asset(string $assetKey): ?array {
    $stmt = db()->prepare("
        SELECT *
        FROM wa_assets
        WHERE asset_key = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$assetKey]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function wa_send_asset_to_contact(
    string $instanceName,
    string $phone,
    string $remoteJid,
    ?int $conversationId,
    ?int $waContactId,
    string $assetKey
): array {
    $asset = wa_find_asset($assetKey);
    if (!$asset) {
        return ['ok' => false, 'message' => 'Asset not found'];
    }

    if ($asset['asset_type'] === 'text') {
        $response = evolution_send_text($instanceName, $phone, (string)$asset['asset_caption']);
        wa_log_outbound(
            $instanceName,
            $conversationId,
            $waContactId,
            $remoteJid,
            'send_text_asset',
            ['phone' => $phone, 'asset_key' => $assetKey],
            $response,
            $response['ok'] ? 'success' : 'failed',
            null
        );
        return $response;
    }

    if ($asset['asset_type'] === 'image') {
        $response = evolution_send_image($instanceName, $phone, (string)$asset['asset_url'], (string)$asset['asset_caption']);
        wa_log_outbound(
            $instanceName,
            $conversationId,
            $waContactId,
            $remoteJid,
            'send_image_asset',
            ['phone' => $phone, 'asset_key' => $assetKey],
            $response,
            $response['ok'] ? 'success' : 'failed',
            null
        );
        return $response;
    }

    $response = evolution_send_document(
        $instanceName,
        $phone,
        (string)$asset['asset_url'],
        basename((string)$asset['asset_url']) ?: 'document.pdf',
        (string)$asset['asset_caption']
    );

    wa_log_outbound(
        $instanceName,
        $conversationId,
        $waContactId,
        $remoteJid,
        'send_document_asset',
        ['phone' => $phone, 'asset_key' => $assetKey],
        $response,
        $response['ok'] ? 'success' : 'failed',
        null
    );

    return $response;
}