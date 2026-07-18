<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function openai_chat_json(array $messages, string $responseInstruction = 'Return valid JSON only.'): array {
    $apiKey = get_setting('openai_api_key');
    $model  = get_setting('openai_model', 'gpt-4.1-mini');

    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Missing OpenAI API key'];
    }

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object']
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        return ['ok' => false, 'error' => $curlError];
    }

    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Invalid JSON response from OpenAI'];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => $decoded['error']['message'] ?? 'OpenAI request failed', 'raw' => $decoded];
    }

    $content = $decoded['choices'][0]['message']['content'] ?? '';
    $json = json_decode((string)$content, true);

    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'Model did not return valid JSON', 'content' => $content];
    }

    return ['ok' => true, 'data' => $json, 'raw_content' => $content, 'model' => $model];
}