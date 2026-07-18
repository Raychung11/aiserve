<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/openai_helper.php';
require_once __DIR__ . '/wa_business_rules.php';
require_once __DIR__ . '/wa_state_machine.php';
require_once __DIR__ . '/evolution_sender.php';
require_once __DIR__ . '/wa_lead_extractor.php';
require_once __DIR__ . '/wa_followup_helper.php';
require_once __DIR__ . '/wa_memory_helper.php';
require_once __DIR__ . '/wa_notification_helper.php';
require_once __DIR__ . '/wa_brochure_helper.php';
require_once __DIR__ . '/wa_quote_flow_helper.php';
require_once __DIR__ . '/wa_task_helper.php';
require_once __DIR__ . '/wa_routing_helper.php';
require_once __DIR__ . '/wa_service_flow_helper.php';
require_once __DIR__ . '/wa_quote_approval_helper.php';
require_once __DIR__ . '/wa_file_request_helper.php';
require_once __DIR__ . '/wa_template_helper.php';
require_once __DIR__ . '/wa_kb_helper.php';

function wa_ai_log(int $conversationId, int $waMessageId, string $stage, string $promptText, string $responseText = ''): void {
    $stmt = db()->prepare("
        INSERT INTO wa_ai_logs
        (conversation_id, wa_message_id, ai_stage, model_name, prompt_text, response_text, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $conversationId,
        $waMessageId,
        $stage,
        get_setting('openai_model', 'gpt-4.1-mini'),
        $promptText,
        $responseText
    ]);
}

function wa_ai_classify_intent(array $conversation, array $message): array {
    $text = trim((string)$message['message_text']);
    $language = wa_detect_language($text);

    $system = <<<TXT
You are an intent classifier for WhatsApp business conversations.
Return JSON only with:
- intent
- confidence
- reason

Allowed intents:
greeting
pricing_request
demo_request
human_request
company_info
service_question
brochure_request
quote_request
file_upload
unknown
TXT;

    $user = "Message: " . $text . "\nLanguage: " . $language;

    $result = openai_chat_json([
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user]
    ]);

    wa_ai_log((int)$conversation['id'], (int)$message['id'], 'intent_classification', $system . "\n\n" . $user, $result['raw_content'] ?? ($result['error'] ?? ''));

    if (!$result['ok']) {
        return ['intent' => 'unknown', 'confidence' => 0, 'reason' => $result['error'] ?? 'classification_failed'];
    }

    return [
        'intent' => (string)($result['data']['intent'] ?? 'unknown'),
        'confidence' => (float)($result['data']['confidence'] ?? 0),
        'reason' => (string)($result['data']['reason'] ?? '')
    ];
}

function wa_ai_generate_reply(array $conversation, array $message, string $intent): array {
    $brand = get_setting('wa_ai_brand_name', 'AiServe.io');
    $company = get_setting('wa_ai_company_name', 'SLV Group Sdn Bhd');
    $language = wa_detect_language((string)$message['message_text']);

    $memoryCompany = wa_memory_get((int)$conversation['id'], 'company_name', '');
    $memoryUseCase = wa_memory_get((int)$conversation['id'], 'use_case', '');

    $kb = wa_kb_match((string)$message['message_text']);
    if ($kb && !empty($kb['recommended_reply'])) {
        return ['ok' => true, 'reply' => (string)$kb['recommended_reply']];
    }

    $template = wa_template_find(
        $intent,
        (string)($conversation['assigned_department'] ?? ''),
        $language
    );

    if ($template && !empty($template['template_text'])) {
        $reply = wa_template_fill((string)$template['template_text'], [
            'name' => $conversation['push_name'] ?? '',
            'company' => $memoryCompany,
            'product' => wa_memory_get((int)$conversation['id'], 'interest', ''),
            'timeline' => wa_memory_get((int)$conversation['id'], 'timeline_needed', ''),
            'branch' => (string)($conversation['branch_name'] ?? '')
        ]);
        return ['ok' => true, 'reply' => $reply];
    }

    $system = <<<TXT
You are the WhatsApp assistant for {$brand} by {$company}.
Write a concise, professional, helpful reply.
Do not invent prices, timelines, or guarantees.
If exact business data is missing, ask for the key missing detail.
Use known memory context if relevant.
Reply in {$language}.
Return JSON only with:
- reply
TXT;

    $context = [
        'conversation_state' => $conversation['current_state'] ?? 'new',
        'priority' => $conversation['priority_level'] ?? 'normal',
        'customer_message' => $message['message_text'] ?? '',
        'intent' => $intent,
        'known_company_name' => $memoryCompany,
        'known_use_case' => $memoryUseCase,
        'assigned_department' => $conversation['assigned_department'] ?? '',
        'branch_name' => $conversation['branch_name'] ?? ''
    ];

    $user = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $result = openai_chat_json([
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user]
    ]);

    wa_ai_log((int)$conversation['id'], (int)$message['id'], 'reply_generation', $system . "\n\n" . $user, $result['raw_content'] ?? ($result['error'] ?? ''));

    if (!$result['ok']) {
        return ['ok' => false, 'reply' => '', 'error' => $result['error'] ?? 'reply_generation_failed'];
    }

    return ['ok' => true, 'reply' => (string)($result['data']['reply'] ?? '')];
}

function wa_handle_quote_flow(array $conversation, array $message, string $intent): ?array {
    if ($intent !== 'quote_request') {
        return null;
    }

    wa_quote_request_create_if_missing((int)$conversation['id'], (int)$conversation['wa_contact_id']);
    wa_quote_try_capture_from_message((int)$conversation['id'], (string)$message['message_text']);

    $quote = wa_quote_request_get((int)$conversation['id']);
    if (!$quote) {
        return null;
    }

    $nextQuestion = wa_quote_next_question($quote);

    if ($nextQuestion !== null) {
        return [
            'handled' => true,
            'reply' => $nextQuestion,
            'state_after' => 'quote_collecting'
        ];
    }

    wa_quote_request_update_field((int)$conversation['id'], 'quote_status', 'ready_for_sales');

    if (!wa_task_exists_open((int)$conversation['id'], 'quote_preparation')) {
        wa_task_create(
            (int)$conversation['id'],
            (int)$conversation['wa_contact_id'],
            null,
            'quote_preparation',
            'Prepare WhatsApp quotation',
            'Customer has completed the initial quotation information flow.',
            'high',
            date('Y-m-d H:i:s', strtotime('+4 hours'))
        );
    }

    $quote = wa_quote_request_get((int)$conversation['id']);
    wa_quote_approval_create_if_missing(
        (int)$conversation['id'],
        (int)$conversation['wa_contact_id'],
        $quote['id'] ?? null,
        null
    );

    return [
        'handled' => true,
        'reply' => 'Thank you. We have captured the key information for your quotation request. Our team will review it and follow up with you shortly.',
        'state_after' => 'quote_ready_for_sales'
    ];
}

function wa_handle_file_request_flow(array $conversation, string $intent): ?array {
    if ($intent !== 'file_upload') {
        return null;
    }

    $requestId = wa_file_request_create(
        (int)$conversation['id'],
        (int)$conversation['wa_contact_id'],
        'supporting_document',
        'Please share the relevant supporting document or file in this WhatsApp chat.'
    );
    wa_file_request_mark_requested($requestId);

    return [
        'handled' => true,
        'reply' => 'Thank you. Please send the relevant supporting document or file in this chat, and our team will review it.',
        'state_after' => 'waiting_file_upload'
    ];
}

function wa_post_reply_actions(array $conversation, array $message, string $intent): array {
    $profileResult = wa_extract_lead_profile($conversation, $message);

    $crmContactId = null;
    $leadConfidence = 0;
    $profile = [];

    if ($profileResult['ok']) {
        $profile = $profileResult['data'];
        $leadConfidence = (float)($profile['confidence_score'] ?? 0);

        wa_ai_log(
            (int)$conversation['id'],
            (int)$message['id'],
            'lead_extraction',
            'lead_extraction',
            $profileResult['raw_content'] ?? ''
        );

        wa_save_lead_profile(
            (int)$conversation['id'],
            (int)$conversation['wa_contact_id'],
            $profile,
            $profileResult['raw_content'] ?? ''
        );

        wa_update_contact_enrichment((int)$conversation['wa_contact_id'], $profile);

        if (!empty($profile['company_name'])) {
            wa_memory_set((int)$conversation['id'], (int)$conversation['wa_contact_id'], 'company_name', (string)$profile['company_name']);
        }
        if (!empty($profile['use_case'])) {
            wa_memory_set((int)$conversation['id'], (int)$conversation['wa_contact_id'], 'use_case', (string)$profile['use_case']);
        }
        if (!empty($profile['interest'])) {
            wa_memory_set((int)$conversation['id'], (int)$conversation['wa_contact_id'], 'interest', (string)$profile['interest']);
        }

        $threshold = (float)get_setting('wa_ai_confidence_threshold', '60');

        if ($leadConfidence >= $threshold) {
            $crmContactId = wa_sync_to_crm_contact($conversation, $profile);

            if ($crmContactId) {
                $stmt = db()->prepare("UPDATE wa_contacts SET crm_contact_id = ? WHERE id = ?");
                $stmt->execute([$crmContactId, $conversation['wa_contact_id']]);
            }
        }
    }

    $route = wa_route_detect((string)($message['message_text'] ?? ''));
    if ($route) {
        wa_apply_route(
            (int)$conversation['id'],
            (string)($route['assigned_department'] ?? ''),
            !empty($route['assigned_admin_id']) ? (int)$route['assigned_admin_id'] : null
        );
    }

    $flow = wa_service_flow_detect($intent, (string)($message['message_text'] ?? ''));
    if ($flow) {
        wa_service_flow_apply((int)$conversation['id'], $flow);
    }

    $scheduleDate = date('Y-m-d H:i:s', strtotime('+' . (int)get_setting('wa_followup_default_delay_hours', '24') . ' hours'));

    if (in_array($intent, ['pricing_request', 'quote_request', 'demo_request', 'brochure_request'], true)) {
        $followupType = $intent . '_followup';

        if (!wa_followup_exists((int)$conversation['id'], $followupType, date('Y-m-d'))) {
            wa_schedule_followup(
                (int)$conversation['id'],
                (int)$conversation['wa_contact_id'],
                $followupType,
                'Hello. We are following up on your earlier WhatsApp inquiry. Please let us know if you would like us to continue assisting you.',
                $scheduleDate,
                null
            );
        }
    }

    if (get_setting('wa_internal_notify_enabled', '1') === '1') {
        if (in_array($intent, ['quote_request', 'demo_request', 'human_request'], true)) {
            if (!wa_notification_exists_today((int)$conversation['id'], $intent)) {
                wa_create_internal_notification(
                    (int)$conversation['id'],
                    (int)$conversation['wa_contact_id'],
                    null,
                    $intent,
                    'WhatsApp lead requires follow-up',
                    'Intent: ' . $intent . ' | Contact: ' . ($conversation['push_name'] ?? '') . ' | Message: ' . ($message['message_text'] ?? '')
                );
            }
        }
    }

    if ($intent === 'demo_request' && !wa_task_exists_open((int)$conversation['id'], 'demo_followup')) {
        wa_task_create(
            (int)$conversation['id'],
            (int)$conversation['wa_contact_id'],
            $crmContactId,
            'demo_followup',
            'Arrange demo follow-up',
            'Customer requested a demo through WhatsApp.',
            'high',
            date('Y-m-d H:i:s', strtotime('+4 hours'))
        );
    }

    return [
        'crm_contact_id' => $crmContactId,
        'confidence_score' => $leadConfidence,
        'profile' => $profile
    ];
}

function wa_auto_asset_actions(array $conversation, string $intent): void {
    $instanceName = (string)($conversation['instance_name'] ?: get_setting('evolution_default_instance', 'main'));
    $phone = preg_replace('/[^0-9]/', '', (string)($conversation['phone'] ?? ''));

    if ($phone === '') {
        return;
    }

    if ($intent === 'brochure_request' && get_setting('wa_auto_brochure_enabled', '1') === '1') {
        wa_send_asset_to_contact(
            $instanceName,
            $phone,
            (string)$conversation['remote_jid'],
            (int)$conversation['id'],
            $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
            'default_brochure'
        );
    }

    if ($intent === 'quote_request' && get_setting('wa_auto_quote_enabled', '1') === '1') {
        wa_send_asset_to_contact(
            $instanceName,
            $phone,
            (string)$conversation['remote_jid'],
            (int)$conversation['id'],
            $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
            'default_quote_info'
        );
    }
}

function wa_ai_process_message(array $conversation, array $message): array {
    if ((int)$conversation['ai_enabled'] !== 1) {
        return ['status' => 'skipped', 'reason' => 'ai_disabled'];
    }

    if (($conversation['conversation_status'] ?? '') === 'pending_human') {
        return ['status' => 'skipped', 'reason' => 'pending_human'];
    }

    if (($message['direction'] ?? '') !== 'inbound') {
        return ['status' => 'skipped', 'reason' => 'not_inbound'];
    }

    $intentResult = wa_ai_classify_intent($conversation, $message);
    $intent = $intentResult['intent'] ?? 'unknown';
    $confidenceScore = (float)($intentResult['confidence'] ?? 0);

    $quoteFlow = wa_handle_quote_flow($conversation, $message, $intent);
    if ($quoteFlow && !empty($quoteFlow['handled'])) {
        $reply = (string)$quoteFlow['reply'];
        $newState = (string)($quoteFlow['state_after'] ?? 'quote_collecting');
    } else {
        $fileFlow = wa_handle_file_request_flow($conversation, $intent);
        if ($fileFlow && !empty($fileFlow['handled'])) {
            $reply = (string)$fileFlow['reply'];
            $newState = (string)($fileFlow['state_after'] ?? 'waiting_file_upload');
        } else {
            $rule = wa_business_rule_response($conversation, $message, $intent);
            if ($rule && !empty($rule['handled'])) {
                $reply = (string)$rule['reply'];
            } else {
                $replyResult = wa_ai_generate_reply($conversation, $message, $intent);
                if (!$replyResult['ok']) {
                    return ['status' => 'failed', 'reason' => $replyResult['error'] ?? 'reply_error', 'intent' => $intent];
                }
                $reply = trim((string)$replyResult['reply']);
            }

            if ($reply === '') {
                return ['status' => 'skipped', 'reason' => 'empty_reply', 'intent' => $intent];
            }

            $newState = wa_next_state((string)($conversation['current_state'] ?? 'new'), $intent);
        }
    }

    $newStatus = $intent === 'human_request' ? 'pending_human' : 'open';
    $aiEnabled = $intent === 'human_request' ? false : true;

    $instanceName = (string)($conversation['instance_name'] ?: get_setting('evolution_default_instance', 'main'));
    $number = preg_replace('/[^0-9]/', '', (string)($conversation['phone'] ?? ''));

    $send = evolution_send_text($instanceName, $number, $reply);

    wa_log_outbound(
        $instanceName,
        (int)$conversation['id'],
        $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
        (string)$conversation['remote_jid'],
        'ai_send_text',
        ['number' => $number, 'text' => $reply, 'intent' => $intent],
        $send,
        $send['ok'] ? 'success' : 'failed',
        null
    );

    if (!$send['ok']) {
        return ['status' => 'failed', 'reason' => 'send_failed', 'intent' => $intent, 'reply' => $reply];
    }

    $messageId = 'ai-text-' . time() . '-' . bin2hex(random_bytes(4));

    wa_store_outbound_message(
        $instanceName,
        (int)$conversation['id'],
        $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
        (string)$conversation['remote_jid'],
        'ai_send_text',
        $messageId,
        'conversation',
        $reply
    );

    wa_update_conversation_state((int)$conversation['id'], $newState, $newStatus, $aiEnabled);

    $postActions = wa_post_reply_actions($conversation, $message, $intent);
    wa_auto_asset_actions($conversation, $intent);

    return [
        'status' => 'done',
        'intent' => $intent,
        'reply' => $reply,
        'state_after' => $newState,
        'confidence_score' => $confidenceScore,
        'crm_contact_id' => $postActions['crm_contact_id'] ?? null,
        'lead_confidence_score' => $postActions['confidence_score'] ?? 0
    ];
}