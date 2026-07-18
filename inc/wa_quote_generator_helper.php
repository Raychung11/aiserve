<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_quote_reference(): string {
    return 'WAQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function wa_quote_build_text(array $quoteRequest, array $contact): string {
    $lines = [];
    $lines[] = 'Quotation Summary';
    $lines[] = 'Reference: ' . wa_quote_reference();
    $lines[] = '';
    $lines[] = 'Contact Name: ' . ($quoteRequest['contact_name'] ?? $contact['push_name'] ?? '');
    $lines[] = 'Company Name: ' . ($quoteRequest['company_name'] ?? '');
    $lines[] = 'Product Interest: ' . ($quoteRequest['product_interest'] ?? '');
    $lines[] = 'Quantity Needed: ' . ($quoteRequest['quantity_needed'] ?? '');
    $lines[] = 'Budget Range: ' . ($quoteRequest['budget_range'] ?? '');
    $lines[] = 'Timeline Needed: ' . ($quoteRequest['timeline_needed'] ?? '');
    $lines[] = 'Special Requirements: ' . ($quoteRequest['special_requirements'] ?? '');
    $lines[] = '';
    $lines[] = 'Prepared by AiServe.io WhatsApp workflow.';
    return implode("\n", $lines);
}

function wa_generated_quote_create(
    int $conversationId,
    int $waContactId,
    ?int $crmContactId,
    ?int $quoteRequestId,
    string $title,
    string $text,
    ?int $createdBy = null
): int {
    $reference = wa_quote_reference();

    $stmt = db()->prepare("
        INSERT INTO wa_generated_quotes
        (conversation_id, wa_contact_id, crm_contact_id, quote_request_id, quote_title, quote_reference, quote_text, quote_status, created_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW(), NOW())
    ");
    $stmt->execute([
        $conversationId,
        $waContactId,
        $crmContactId,
        $quoteRequestId,
        $title,
        $reference,
        $text,
        $createdBy
    ]);

    return (int)db()->lastInsertId();
}