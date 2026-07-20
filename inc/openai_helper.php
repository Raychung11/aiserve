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

/**
 * Generate an image via the OpenAI Images API and return the raw bytes.
 * Handles both gpt-image-1 (base64) and dall-e-3/2 (URL) responses.
 *
 * @return array{ok:bool, bytes?:string, ext?:string, model?:string, error?:string}
 */
function openai_image_generate(string $prompt, string $model = '', string $size = ''): array {
    $apiKey = get_setting('openai_api_key');
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Missing OpenAI API key'];
    }

    if ($model === '') {
        $model = get_setting('openai_image_model', 'gpt-image-1');
    }
    if ($size === '') {
        if (stripos($model, 'dall-e-3') !== false) {
            $size = '1792x1024';
        } elseif (stripos($model, 'dall-e-2') !== false) {
            $size = '1024x1024';
        } else {
            $size = '1536x1024'; // gpt-image-1 landscape
        }
    }

    $payload = [
        'model'  => $model,
        'prompt' => $prompt,
        'size'   => $size,
        'n'      => 1,
    ];
    // dall-e models accept response_format; gpt-image-1 always returns base64 and rejects it.
    if (stripos($model, 'dall-e') !== false) {
        $payload['response_format'] = 'b64_json';
    }

    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        return ['ok' => false, 'error' => $curlError];
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid response from OpenAI image API'];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => $data['error']['message'] ?? ('Image request failed (HTTP ' . $httpCode . ')')];
    }

    $item = $data['data'][0] ?? [];

    if (!empty($item['b64_json'])) {
        $bytes = base64_decode((string)$item['b64_json'], true);
        if ($bytes === false) {
            return ['ok' => false, 'error' => 'Could not decode image data'];
        }
        return ['ok' => true, 'bytes' => $bytes, 'ext' => 'png', 'model' => $model];
    }

    if (!empty($item['url'])) {
        $dl = curl_init((string)$item['url']);
        curl_setopt($dl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($dl, CURLOPT_TIMEOUT, 60);
        curl_setopt($dl, CURLOPT_FOLLOWLOCATION, true);
        $imgBytes = curl_exec($dl);
        $imgType  = (string)curl_getinfo($dl, CURLINFO_CONTENT_TYPE);
        $dlError  = curl_error($dl);
        curl_close($dl);

        if ($imgBytes === false || $imgBytes === '') {
            return ['ok' => false, 'error' => 'Could not download generated image: ' . $dlError];
        }
        $ext = 'png';
        if (stripos($imgType, 'jpeg') !== false || stripos($imgType, 'jpg') !== false) {
            $ext = 'jpg';
        } elseif (stripos($imgType, 'webp') !== false) {
            $ext = 'webp';
        }
        return ['ok' => true, 'bytes' => (string)$imgBytes, 'ext' => $ext, 'model' => $model];
    }

    return ['ok' => false, 'error' => 'No image returned by the API'];
}