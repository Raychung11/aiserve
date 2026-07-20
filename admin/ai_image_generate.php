<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/openai_helper.php';

header('Content-Type: application/json; charset=utf-8');

$posted = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['admin_csrf'] ?? '', $posted)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$title        = trim((string)($_POST['title'] ?? ''));
$context      = trim((string)($_POST['context'] ?? ''));
$customPrompt = trim((string)($_POST['prompt'] ?? ''));
$kind         = trim((string)($_POST['kind'] ?? 'post')); // post | project

if ($title === '' && $context === '' && $customPrompt === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Add a title first (or type an image prompt).']);
    exit;
}

// 1) Build an image prompt with the chat model unless the user supplied one.
$imagePrompt = $customPrompt;
if ($imagePrompt === '') {
    $contextForPrompt = mb_substr($context, 0, 1500);
    $res = openai_chat_json([
        ['role' => 'system', 'content' =>
            'You write concise prompts for an AI image generator to create a cover image for a B2B '
            . 'technology brand called AiServe.my (violet/purple brand palette). The image must be modern, '
            . 'clean, professional and conceptual/abstract. Never include text, words, letters, logos, '
            . 'watermarks or user-interface screenshots in the image. Return JSON only.'],
        ['role' => 'user', 'content' =>
            "Subject (" . ($kind === 'project' ? 'project' : 'article') . "): {$title}\n"
            . ($contextForPrompt !== '' ? "Details: {$contextForPrompt}\n" : '')
            . "\nWrite a single vivid image-generation prompt (max 90 words) for a wide landscape cover image. "
            . 'Style: modern, minimal, professional, soft violet and purple accents, subtle tech/AI motifs, '
            . 'clean lighting, no text. Return JSON exactly as: {"image_prompt": "..."}'],
    ]);

    if (empty($res['ok'])) {
        echo json_encode(['ok' => false, 'error' => 'Could not build image prompt: ' . (string)($res['error'] ?? 'unknown')]);
        exit;
    }
    $imagePrompt = trim((string)($res['data']['image_prompt'] ?? ''));
    if ($imagePrompt === '') {
        echo json_encode(['ok' => false, 'error' => 'The AI did not return an image prompt.']);
        exit;
    }
}

// 2) Generate the image.
$img = openai_image_generate($imagePrompt);
if (empty($img['ok'])) {
    echo json_encode(['ok' => false, 'error' => (string)($img['error'] ?? 'Image generation failed'), 'prompt' => $imagePrompt]);
    exit;
}

// 3) Save it into the media library.
$uploadDir = __DIR__ . '/../uploads/media/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$ext = preg_match('/^[a-z0-9]{1,5}$/i', (string)$img['ext']) ? (string)$img['ext'] : 'png';
$safeName = 'ai_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $uploadDir . $safeName;

if (file_put_contents($targetPath, $img['bytes']) === false) {
    echo json_encode(['ok' => false, 'error' => 'Could not save the generated image to the server.']);
    exit;
}

$relPath = '/uploads/media/' . $safeName;
$mimeType = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);

try {
    $stmt = db()->prepare("
        INSERT INTO media_library (file_name, file_path, file_url, file_type, uploaded_by, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $safeName,
        $relPath,
        rtrim(APP_URL, '/') . $relPath,
        $mimeType,
        $_SESSION['admin_id'] ?? null,
    ]);
} catch (Throwable $e) {
    // Image is saved on disk even if the library row fails; keep going.
}

echo json_encode(['ok' => true, 'url' => $relPath, 'prompt' => $imagePrompt, 'model' => $img['model'] ?? '']);
