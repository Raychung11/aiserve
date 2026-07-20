<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/ai_endpoint.php';
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

$mode     = trim((string)($_POST['mode'] ?? ''));
$title    = trim((string)($_POST['title'] ?? ''));
$content  = trim((string)($_POST['content'] ?? ''));
$excerpt  = trim((string)($_POST['excerpt'] ?? ''));
$postType = trim((string)($_POST['post_type'] ?? 'blog'));
$brief    = trim((string)($_POST['brief'] ?? ''));

$typeLabel = $postType === 'case_study' ? 'case study' : 'blog article';

$brand = 'AiServe.my is the AI Business Operating System by SLV Group Sdn Bhd — an AI solutions '
    . 'company in Malaysia offering AI customer service, workflow automation, reporting and '
    . 'operational intelligence. The audience is business owners and managers in Malaysia and Southeast Asia.';

$formatRules = "Format the body using ONLY this lightweight syntax: '## ' for section headings, "
    . "'### ' for subheadings, '- ' for bullet points, '> ' for a highlighted quote, a line with "
    . "just '---' for a divider, and '**bold**' for emphasis. Separate blocks with a blank line. "
    . 'Do NOT use links, images, tables, or raw HTML.';

// Truncate very long content sent for context to keep the request lean.
$contentForContext = mb_substr($content, 0, 6000);

$messages = [];

switch ($mode) {
    case 'article':
        if ($title === '' && $brief === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Provide a title or a brief first']);
            exit;
        }
        $messages = [
            ['role' => 'system', 'content' =>
                "You are an expert B2B content writer and SEO specialist. {$brand} "
                . "Write in clear, professional, confident English for a business audience. {$formatRules} Return JSON only."],
            ['role' => 'user', 'content' =>
                "Write a complete {$typeLabel}"
                . ($title !== '' ? " titled \"{$title}\"" : '') . '. '
                . ($brief !== '' ? "Angle/brief: {$brief}. " : '')
                . "Aim for roughly 500-800 words with a strong intro, 3-5 sections using '## ' headings, "
                . "short paragraphs, some bullet points, and a closing takeaway or call to action. "
                . "Return JSON exactly as: {\"content\": \"the full article body in the allowed syntax\", "
                . "\"excerpt\": \"1-2 sentence summary, max 200 characters\", "
                . "\"seo_title\": \"max 60 characters\", \"seo_description\": \"max 155 characters\"}"],
        ];
        break;

    case 'excerpt':
        if ($title === '' && $content === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Add a title or content first']);
            exit;
        }
        $messages = [
            ['role' => 'system', 'content' => "You are an expert editor. {$brand} Return JSON only."],
            ['role' => 'user', 'content' =>
                "Write a compelling excerpt (1-2 sentences, max 200 characters) for this {$typeLabel}.\n"
                . "Title: {$title}\n\nContent:\n{$contentForContext}\n\n"
                . 'Return JSON exactly as: {"excerpt": "..."}'],
        ];
        break;

    case 'seo':
        if ($title === '' && $content === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Add a title or content first']);
            exit;
        }
        $messages = [
            ['role' => 'system', 'content' => "You are an expert SEO copywriter. {$brand} Return JSON only."],
            ['role' => 'user', 'content' =>
                "Write SEO metadata for this {$typeLabel}.\n"
                . "Title: {$title}\nExcerpt: {$excerpt}\n\nContent:\n{$contentForContext}\n\n"
                . 'Generate an SEO title (max 60 characters) and a meta description (max 155 characters, '
                . 'benefit-driven with a soft call to action). '
                . 'Return JSON exactly as: {"seo_title": "...", "seo_description": "..."}'],
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

foreach (['content', 'excerpt', 'seo_title', 'seo_description'] as $field) {
    if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) !== '') {
        $out[$field] = trim($data[$field]);
    }
}

if (count($out) === 1) {
    echo json_encode(['ok' => false, 'error' => 'AI returned an empty result']);
    exit;
}

echo json_encode($out);
