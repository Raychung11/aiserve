<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/wa_memory_helper.php';

function wa_quote_request_get(int $conversationId): ?array {
    $stmt = db()->prepare("
        SELECT *
        FROM wa_quote_requests
        WHERE conversation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function wa_quote_request_create_if_missing(int $conversationId, int $waContactId): void {
    $stmt = db()->prepare("
        INSERT IGNORE INTO wa_quote_requests
        (conversation_id, wa_contact_id, quote_status, created_at, updated_at)
        VALUES (?, ?, 'collecting', NOW(), NOW())
    ");
    $stmt->execute([$conversationId, $waContactId]);
}

function wa_quote_request_update_field(int $conversationId, string $field, string $value): void {
    $allowed = [
        'company_name',
        'contact_name',
        'product_interest',
        'quantity_needed',
        'budget_range',
        'timeline_needed',
        'special_requirements',
        'quote_status'
    ];

    if (!in_array($field, $allowed, true)) {
        return;
    }

    $sql = "UPDATE wa_quote_requests SET {$field} = ?, updated_at = NOW() WHERE conversation_id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$value, $conversationId]);
}

function wa_quote_missing_fields(array $quote): array {
    $required = [
        'company_name',
        'contact_name',
        'product_interest',
        'quantity_needed',
        'timeline_needed'
    ];

    $missing = [];
    foreach ($required as $field) {
        if (trim((string)($quote[$field] ?? '')) === '') {
            $missing[] = $field;
        }
    }

    return $missing;
}

function wa_quote_next_question(array $quote): ?string {
    $missing = wa_quote_missing_fields($quote);

    if (!$missing) {
        return null;
    }

    $field = $missing[0];

    $questions = [
        'company_name' => 'May I know your company name?',
        'contact_name' => 'May I know your name for the quotation record?',
        'product_interest' => 'What product or service are you interested in?',
        'quantity_needed' => 'What quantity or scope do you need?',
        'timeline_needed' => 'When do you need this quotation or delivery by?'
    ];

    return $questions[$field] ?? 'Could you share more details for the quotation request?';
}

function wa_quote_try_capture_from_message(int $conversationId, string $messageText): void {
    $text = trim($messageText);
    if ($text === '') return;

    $quote = wa_quote_request_get($conversationId);
    if (!$quote) return;

    $missing = wa_quote_missing_fields($quote);
    if (!$missing) return;

    $field = $missing[0];
    wa_quote_request_update_field($conversationId, $field, $text);
}