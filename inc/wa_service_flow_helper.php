<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/wa_quote_flow_helper.php';

function wa_service_flow_detect(string $intent, string $messageText): ?string {
    $text = strtolower(trim($messageText));

    if ($intent === 'quote_request') return 'quote_flow';
    if ($intent === 'brochure_request') return 'brochure_flow';
    if ($intent === 'demo_request') return 'demo_flow';

    if (strpos($text, 'quotation') !== false || strpos($text, 'quote') !== false) return 'quote_flow';
    if (strpos($text, 'brochure') !== false || strpos($text, 'catalog') !== false) return 'brochure_flow';
    if (strpos($text, 'demo') !== false || strpos($text, 'presentation') !== false) return 'demo_flow';

    return null;
}

function wa_service_flow_apply(int $conversationId, ?string $flow): void {
    if ($flow === null || $flow === '') return;

    $stmt = db()->prepare("
        UPDATE wa_conversations
        SET service_flow = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$flow, $conversationId]);
}