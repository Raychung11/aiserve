<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/openai.php';
    require_once __DIR__ . '/../inc/site_knowledge_from_db.php';
} catch (Throwable $e) {
    error_log('[api/chat bootstrap] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Service temporarily unavailable.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Lightweight per-IP rate limit to protect the (unauthenticated) endpoint
 * from being abused to burn OpenAI credits. Allows $max requests per $window
 * seconds, tracked in a temp file per client IP.
 */
function chat_rate_limit(int $max = 15, int $window = 60): bool {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = sys_get_temp_dir() . '/aiserve_chat_rl_' . md5($ip) . '.json';

    $now = time();
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = array_filter($decoded, fn($t) => is_int($t) && ($now - $t) < $window);
        }
    }

    if (count($hits) >= $max) {
        return false;
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
    return true;
}

function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function detect_intent(string $query): string {
    $q = mb_strtolower($query);

    if (preg_match('/industry|industries|sector|sectors|business type/i', $q)) {
        return 'industries';
    }
    if (preg_match('/demo|demos|website|websites|case study|case studies|portfolio/i', $q)) {
        return 'demos';
    }
    if (preg_match('/customer service|whatsapp|chatbot|ai cs|inquiry|support/i', $q)) {
        return 'showcases';
    }
    if (preg_match('/client|clients|brand|brands|company|companies|who use/i', $q)) {
        return 'clients';
    }
    if (preg_match('/blog|article|articles|insight|insights/i', $q)) {
        return 'blog';
    }

    return 'general';
}

function source_type_priority(string $intent): array {
    return match ($intent) {
        'industries' => ['industry', 'core', 'showcase', 'demo', 'client', 'blog'],
        'demos' => ['demo', 'core', 'showcase', 'industry', 'client', 'blog'],
        'showcases' => ['showcase', 'client', 'industry', 'core', 'demo', 'blog'],
        'clients' => ['client', 'showcase', 'core', 'industry', 'demo', 'blog'],
        'blog' => ['blog', 'core', 'industry', 'demo', 'showcase', 'client'],
        default => ['core', 'industry', 'showcase', 'demo', 'client', 'blog'],
    };
}

function simple_retrieve(string $query, array $docs, string $intent, int $limit = 4): array {
    $q = mb_strtolower($query);
    $terms = preg_split('/\s+/u', $q) ?: [];
    $terms = array_values(array_filter($terms, fn($t) => mb_strlen($t) >= 3));

    $priority = source_type_priority($intent);
    $priorityMap = [];
    foreach ($priority as $i => $type) {
        $priorityMap[$type] = count($priority) - $i;
    }

    $scored = [];

    foreach ($docs as $doc) {
        $haystack = mb_strtolower((string)($doc['title'] ?? '') . ' ' . (string)($doc['content'] ?? ''));
        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $score += 3;
            }
        }

        $type = (string)($doc['source_type'] ?? 'other');
        $score += $priorityMap[$type] ?? 0;

        if ($intent === 'industries' && $type === 'industry') $score += 10;
        if ($intent === 'demos' && $type === 'demo') $score += 10;
        if ($intent === 'showcases' && $type === 'showcase') $score += 10;
        if ($intent === 'clients' && $type === 'client') $score += 10;
        if ($intent === 'blog' && $type === 'blog') $score += 10;

        if ($score > 0) {
            $doc['_score'] = $score;
            $scored[] = $doc;
        }
    }

    usort($scored, fn($a, $b) => ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0));
    return array_slice($scored, 0, $limit);
}

function build_context(array $docs): string {
    if (!$docs) {
        return "No matching website knowledge found.";
    }

    $parts = [];
    foreach ($docs as $i => $doc) {
        $n = $i + 1;
        $parts[] = "[Source {$n}]
Type: " . (string)($doc['source_type'] ?? '') . "
Title: " . (string)($doc['title'] ?? '') . "
URL: " . (string)($doc['url'] ?? '') . "
Content:
" . (string)($doc['content'] ?? '');
    }
    return implode("\n\n", $parts);
}

function build_php_fallback_answer(string $intent, array $matches): string {
    if (!$matches) {
        return "I could not find a clear answer from the current website knowledge. Please explore the relevant page on AiServe.my or contact our team for direct assistance.";
    }

    if ($intent === 'industries') {
        $items = [];
        foreach ($matches as $doc) {
            if (($doc['source_type'] ?? '') === 'industry') {
                $title = $doc['meta']['industry_title'] ?? '';
                if ($title !== '') $items[] = $title;
            }
        }
        $items = array_values(array_unique($items));
        if ($items) {
            return "Based on our website, AiServe supports industries such as " . implode(', ', $items) . ". You can explore more on the Industries page.";
        }
    }

    if ($intent === 'demos') {
        $items = [];
        foreach ($matches as $doc) {
            if (($doc['source_type'] ?? '') === 'demo') {
                $title = $doc['meta']['demo_title'] ?? '';
                if ($title !== '') $items[] = $title;
            }
        }
        $items = array_values(array_unique($items));
        if ($items) {
            return "AiServe has demo examples including " . implode(', ', $items) . ". You can view more on the Demos page.";
        }
    }

    if ($intent === 'showcases') {
        $items = [];
        foreach ($matches as $doc) {
            if (($doc['source_type'] ?? '') === 'showcase') {
                $company = $doc['meta']['company_name'] ?? '';
                $title = $doc['meta']['title'] ?? '';
                $items[] = $company !== '' ? $company : $title;
            }
        }
        $items = array_values(array_unique(array_filter($items)));
        if ($items) {
            return "AiServe’s AI customer service showcases include examples such as " . implode(', ', $items) . ". These use cases cover real business deployments across different sectors.";
        }
    }

    if ($intent === 'clients') {
        foreach ($matches as $doc) {
            if (($doc['source_type'] ?? '') === 'client') {
                $companies = $doc['meta']['companies'] ?? [];
                if (is_array($companies) && $companies) {
                    return "Selected brands and organisations supported by AiServe include " . implode(', ', $companies) . ".";
                }
            }
        }
    }

    $top = $matches[0];
    return "Here is the most relevant information I found from AiServe.my: " . trim((string)($top['content'] ?? ''));
}

function is_weak_reply(string $text): bool {
    $t = trim(mb_strtolower($text));
    if ($t === '') return true;

    $weakPatterns = [
        'sorry, i could not generate a reply',
        'i could not generate a reply',
        'sorry, something went wrong',
        'temporarily unavailable',
        'no reply',
    ];

    foreach ($weakPatterns as $pattern) {
        if (str_contains($t, $pattern)) {
            return true;
        }
    }

    return mb_strlen($t) < 30;
}

function is_high_intent(string $query): bool {
    $q = mb_strtolower($query);

    return preg_match('/
        contact|
        whatsapp|
        phone|
        call|
        email|
        reach|
        talk|
        sales|
        demo|
        quotation|
        quote|
        price|
        pricing|
        cost|
        how\ to\ start|
        get\ started|
        interested|
        sign\ up|
        consultation
    /ix', $q) === 1;
}

function contact_block(): string {
    $phone = '+6013-386 6827';
    $whatsapp = 'https://wa.me/60133866827';
    $email = 'hello@aiserve.my';
    $contactPage = 'https://aiserve.my/contact.php';

    return "\n\n---\n\n" .
        "You can reach our team directly:\n\n" .
        "Phone: {$phone}\n" .
        "WhatsApp: {$whatsapp}\n" .
        "Email: {$email}\n" .
        "Contact Page: {$contactPage}\n\n" .
        "Our team will assist you with consultation, demo, and solution setup.";
}

function openai_responses_call(string $userMessage, string $context, string $phpFallback): array {
    $apiKey = openai_api_key();
    if ($apiKey === '') {
        return [
            'ok' => false,
            'error' => 'OPENAI_API_KEY is missing.'
        ];
    }

    $systemPrompt = <<<PROMPT
You are the official AiServe.my website assistant for SLV Group Sdn Bhd.

Your tone:
- professional
- clear
- confident
- commercially helpful
- concise but polished

Your job:
- answer based on the provided AiServe.my website knowledge
- prioritize current website content, demos, industries, AI customer service showcases, and supported brands in the context
- do not invent services, industries, case studies, or claims not supported by the context
- if the answer is uncertain, say so professionally and direct the visitor to the contact page
- if the context is partial, still provide the best grounded answer possible

Fallback guidance:
- if the website knowledge is limited, use the provided structured fallback answer as a reliable base
- do not say you cannot answer unless both the context and fallback are insufficient

Style:
- avoid casual filler language
- avoid robotic wording
- sound like a premium business solution consultant
PROMPT;

    $payload = [
        'model' => openai_model(),
        'input' => [
            [
                'role' => 'system',
                'content' => [
                    ['type' => 'input_text', 'text' => $systemPrompt]
                ]
            ],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => "Website knowledge:\n\n" . $context]
                ]
            ],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => "Structured fallback answer:\n" . $phpFallback]
                ]
            ],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $userMessage]
                ]
            ]
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 60,
    ]);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $curlErr) {
        return [
            'ok' => false,
            'error' => 'Request failed: ' . $curlErr
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'error' => 'Invalid JSON response from OpenAI.'
        ];
    }

    if ($status >= 400) {
        return [
            'ok' => false,
            'error' => $data['error']['message'] ?? ('OpenAI HTTP ' . $status)
        ];
    }

    $text = $data['output_text'] ?? '';
    if (!is_string($text)) {
        $text = '';
    }

    return [
        'ok' => true,
        'text' => trim($text),
        'raw' => $data,
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }

    if (!chat_rate_limit()) {
        json_out(['ok' => false, 'error' => 'Too many requests. Please slow down.'], 429);
    }

    $body = read_json_body();
    $message = trim((string)($body['message'] ?? ''));

    if ($message === '') {
        json_out(['ok' => false, 'error' => 'Message is required.'], 422);
    }

    // Cap message length to avoid oversized / abusive prompts.
    if (mb_strlen($message) > 2000) {
        $message = mb_substr($message, 0, 2000);
    }

    $docs = aiserve_site_knowledge_from_db();
    $intent = detect_intent($message);
    $matches = simple_retrieve($message, $docs, $intent, 4);
    $context = build_context($matches);
    $phpFallback = build_php_fallback_answer($intent, $matches);

    $result = openai_responses_call($message, $context, $phpFallback);

    $reply = $phpFallback;
    if ($result['ok'] && !is_weak_reply((string)($result['text'] ?? ''))) {
        $reply = (string)$result['text'];
    }

    if (is_high_intent($message)) {
        $reply .= contact_block();
    }

    json_out([
        'ok' => true,
        'reply' => $reply,
        'sources' => array_map(function ($doc) {
            return [
                'title' => $doc['title'] ?? '',
                'url' => $doc['url'] ?? ''
            ];
        }, $matches)
    ]);
} catch (Throwable $e) {
    error_log('[api/chat] ' . $e->getMessage());
    json_out([
        'ok' => false,
        'error' => 'Chat backend error. Please try again later.',
    ], 500);
}