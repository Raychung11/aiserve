<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_detect_language(string $text): string {
    $text = trim($text);
    if ($text === '') return 'English';

    if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) return 'Chinese';
    if (preg_match('/\b(saya|anda|boleh|harga|terima kasih|selamat)\b/i', $text)) return 'Bahasa Malaysia';

    return 'English';
}

function wa_business_rule_response(array $conversation, array $message, string $intent): ?array {
    $text = strtolower(trim((string)($message['message_text'] ?? '')));

    if ($intent === 'greeting') {
        return [
            'handled' => true,
            'reply' => "Hello. Thank you for contacting AiServe.io. We help businesses build AI-powered systems for customer service, workflow automation, reporting, and operational intelligence. How can we help you today?"
        ];
    }

    if ($intent === 'pricing_request') {
        return [
            'handled' => true,
            'reply' => "Thank you for your interest. Our pricing depends on your use case, workflow scope, and deployment needs. Please share your business type and what you want to improve, and we will guide you to the right Ai-BOS subscription path."
        ];
    }

    if ($intent === 'demo_request') {
        return [
            'handled' => true,
            'reply' => "Great. We can arrange a demo or consultation. Please share your company name, business type, and the main process you want to improve, such as customer service, sales follow-up, reporting, or internal workflow."
        ];
    }

    if ($intent === 'human_request') {
        return [
            'handled' => true,
            'reply' => "Understood. We will arrange human follow-up for you. Please share your name, company name, and the main issue or requirement so our team can review it properly."
        ];
    }

    if ($intent === 'unknown' && $text === '') {
        return [
            'handled' => true,
            'reply' => "Thank you for your message. Could you please share a little more detail so we can assist you properly?"
        ];
    }

    return null;
}