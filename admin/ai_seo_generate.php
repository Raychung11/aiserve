<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/ai_endpoint.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/openai_helper.php';

header('Content-Type: application/json; charset=utf-8');

// CSRF (return JSON rather than the plain-text exit verify_csrf() uses)
$posted = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['admin_csrf'] ?? '', $posted)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$label       = trim((string)($_POST['label'] ?? ''));
$pageKey     = trim((string)($_POST['page_key'] ?? ''));
$currentTitle = trim((string)($_POST['current_title'] ?? ''));
$currentDesc  = trim((string)($_POST['current_description'] ?? ''));

if ($label === '' && $pageKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing page reference']);
    exit;
}

$system = 'You are an expert SEO copywriter for AiServe.my, the AI Business Operating System by '
    . 'SLV Group Sdn Bhd — an AI solutions company in Malaysia providing AI customer service, '
    . 'workflow automation, reporting and operational intelligence. Write concise, compelling, '
    . 'keyword-rich SEO metadata in English for a Malaysian business audience. Return JSON only.';

$user = "Page: {$label} (key: {$pageKey}).\n"
    . 'Current SEO title: ' . ($currentTitle !== '' ? $currentTitle : '(none)') . "\n"
    . 'Current meta description: ' . ($currentDesc !== '' ? $currentDesc : '(none)') . "\n\n"
    . 'Generate an improved SEO title (max 60 characters, include "AiServe.my" where natural) and '
    . 'a meta description (max 155 characters, benefit-driven with a soft call to action). '
    . 'Return JSON exactly as: {"seo_title": "...", "seo_description": "..."}';

$res = openai_chat_json([
    ['role' => 'system', 'content' => $system],
    ['role' => 'user', 'content' => $user],
]);

if (empty($res['ok'])) {
    echo json_encode(['ok' => false, 'error' => (string)($res['error'] ?? 'AI request failed')]);
    exit;
}

$data  = $res['data'] ?? [];
$title = trim((string)($data['seo_title'] ?? ''));
$desc  = trim((string)($data['seo_description'] ?? ''));

if ($title === '' && $desc === '') {
    echo json_encode(['ok' => false, 'error' => 'AI returned an empty result']);
    exit;
}

echo json_encode(['ok' => true, 'title' => $title, 'description' => $desc]);
