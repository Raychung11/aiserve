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

$mode    = trim((string)($_POST['mode'] ?? ''));
$brief   = trim((string)($_POST['brief'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));

$brand = 'AiServe.my is the AI Business Operating System by SLV Group Sdn Bhd — an AI solutions '
    . 'company in Malaysia offering AI customer service, workflow automation, reporting and '
    . 'operational intelligence. Audience: business owners and managers in Malaysia and Southeast Asia.';

if ($brief === '' && $subject === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Describe the campaign goal first.']);
    exit;
}

switch ($mode) {
    case 'campaign':
        $messages = [
            ['role' => 'system', 'content' =>
                "You are an expert B2B email marketing copywriter. {$brand} Write a professional, "
                . 'concise outreach email that is friendly but not pushy, with a clear single call to action. '
                . 'Use the placeholder [Name] for the recipient greeting. Plain text only (no HTML). Return JSON only.'],
            ['role' => 'user', 'content' =>
                "Campaign goal / brief: {$brief}\n\n"
                . 'Write the email. Return JSON exactly as: '
                . '{"subject": "compelling subject line under 60 characters", '
                . '"body": "the full plain-text email body, starting with \"Dear [Name],\" and ending with a sign-off from AiServe.my"}'],
        ];
        break;

    case 'photo_prompt':
        $messages = [
            ['role' => 'system', 'content' =>
                "You write prompts for an AI image generator to create marketing visuals for {$brand} "
                . 'Images must be modern, clean, professional and conceptual/abstract, with soft violet/purple '
                . 'accents, and must never contain text, words, logos or watermarks. Return JSON only.'],
            ['role' => 'user', 'content' =>
                'Campaign goal: ' . ($brief !== '' ? $brief : $subject) . "\n\n"
                . 'Write a single vivid image-generation prompt (max 90 words) for a wide landscape marketing image. '
                . 'Return JSON exactly as: {"photo_prompt": "..."}'],
        ];
        break;

    default:
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Unknown mode']);
        exit;
}

$res = openai_chat_json($messages);

if (empty($res['ok'])) {
    echo json_encode(['ok' => false, 'error' => (string)($res['error'] ?? 'AI request failed')]);
    exit;
}

$data = $res['data'] ?? [];
$out  = ['ok' => true];

foreach (['subject', 'body', 'photo_prompt'] as $field) {
    if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) !== '') {
        $out[$field] = trim($data[$field]);
    }
}

if (count($out) === 1) {
    echo json_encode(['ok' => false, 'error' => 'AI returned an empty result']);
    exit;
}

echo json_encode($out);
