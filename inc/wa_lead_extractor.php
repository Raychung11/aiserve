<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/openai_helper.php';
require_once __DIR__ . '/wa_assignment_helper.php';

function wa_extract_lead_profile(array $conversation, array $message): array {
    $system = <<<TXT
You extract lead information from WhatsApp business chats.
Return JSON only with:
- name
- company_name
- business_type
- use_case
- interest
- confidence_score

If unknown, return empty string for fields.
confidence_score should be 0 to 100.
TXT;

    $user = json_encode([
        'conversation_state' => $conversation['current_state'] ?? '',
        'customer_name' => $conversation['push_name'] ?? '',
        'customer_phone' => $conversation['phone'] ?? '',
        'message' => $message['message_text'] ?? ''
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $result = openai_chat_json([
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user]
    ]);

    if (!$result['ok']) {
        return [
            'ok' => false,
            'error' => $result['error'] ?? 'lead_extraction_failed'
        ];
    }

    return [
        'ok' => true,
        'data' => [
            'name' => trim((string)($result['data']['name'] ?? '')),
            'company_name' => trim((string)($result['data']['company_name'] ?? '')),
            'business_type' => trim((string)($result['data']['business_type'] ?? '')),
            'use_case' => trim((string)($result['data']['use_case'] ?? '')),
            'interest' => trim((string)($result['data']['interest'] ?? '')),
            'confidence_score' => (float)($result['data']['confidence_score'] ?? 0),
        ],
        'raw_content' => $result['raw_content'] ?? ''
    ];
}

function wa_save_lead_profile(int $conversationId, int $waContactId, array $data, string $rawJson): void {
    $stmt = db()->prepare("
        INSERT INTO wa_lead_profiles
        (conversation_id, wa_contact_id, extracted_name, extracted_company, extracted_business_type, extracted_use_case, extracted_interest, extracted_phone, confidence_score, extraction_json, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, '', ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            extracted_name = VALUES(extracted_name),
            extracted_company = VALUES(extracted_company),
            extracted_business_type = VALUES(extracted_business_type),
            extracted_use_case = VALUES(extracted_use_case),
            extracted_interest = VALUES(extracted_interest),
            confidence_score = VALUES(confidence_score),
            extraction_json = VALUES(extraction_json),
            updated_at = NOW()
    ");
    $stmt->execute([
        $conversationId,
        $waContactId,
        $data['name'] ?? '',
        $data['company_name'] ?? '',
        $data['business_type'] ?? '',
        $data['use_case'] ?? '',
        $data['interest'] ?? '',
        $data['confidence_score'] ?? 0,
        $rawJson
    ]);
}

function wa_update_contact_enrichment(int $waContactId, array $data): void {
    $stmt = db()->prepare("
        UPDATE wa_contacts
        SET company_name = ?,
            business_type = ?,
            use_case = ?,
            lead_score = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $data['company_name'] ?? '',
        $data['business_type'] ?? '',
        $data['use_case'] ?? '',
        $data['confidence_score'] ?? 0,
        $waContactId
    ]);
}

function wa_sync_to_crm_contact(array $conversation, array $profile): ?int {
    if (empty($conversation['phone'])) {
        return null;
    }

    $phone = trim((string)$conversation['phone']);
    $name = trim((string)($profile['name'] ?? $conversation['push_name'] ?? 'WhatsApp Lead'));
    $companyName = trim((string)($profile['company_name'] ?? ''));
    $interest = trim((string)($profile['interest'] ?? 'WhatsApp Inquiry'));
    $message = trim((string)($profile['use_case'] ?? ''));

    $stmt = db()->prepare("SELECT id FROM contacts WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = db()->prepare("
            UPDATE contacts
            SET full_name = ?, company_name = ?, interest = ?, message = ?, status = 'new'
            WHERE id = ?
        ");
        $update->execute([$name, $companyName, $interest, $message, $existing['id']]);
        return (int)$existing['id'];
    }

    $companySize = '';
    $assignedAdminId = wa_pick_assigned_admin($interest, $profile['business_type'] ?? '');

    $insert = db()->prepare("
        INSERT INTO contacts
        (full_name, company_name, email, phone, interest, company_size, message, source_page, status, is_subscribed, unsubscribe_token, assigned_admin_id, created_at)
        VALUES (?, ?, '', ?, ?, ?, ?, 'whatsapp', 'new', 1, ?, ?, NOW())
    ");
    $insert->execute([
        $name,
        $companyName,
        $phone,
        $interest,
        $companySize,
        $message,
        bin2hex(random_bytes(16)),
        $assignedAdminId
    ]);

    return (int)db()->lastInsertId();
}