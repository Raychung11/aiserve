<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_next_state(string $currentState, string $intent): string {
    if ($intent === 'greeting') return 'engaged';
    if ($intent === 'pricing_request') return 'pricing_discussion';
    if ($intent === 'demo_request') return 'demo_discussion';
    if ($intent === 'human_request') return 'pending_human';
    if ($intent === 'company_info') return 'collecting_requirements';
    if ($intent === 'service_question') return 'explaining_solution';
    if ($intent === 'unknown') return $currentState !== '' ? $currentState : 'engaged';

    return $currentState !== '' ? $currentState : 'engaged';
}

function wa_update_conversation_state(int $conversationId, string $newState, string $status = 'open', ?bool $aiEnabled = null): void {
    if ($aiEnabled === null) {
        $stmt = db()->prepare("
            UPDATE wa_conversations
            SET current_state = ?, conversation_status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newState, $status, $conversationId]);
        return;
    }

    $stmt = db()->prepare("
        UPDATE wa_conversations
        SET current_state = ?, conversation_status = ?, ai_enabled = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newState, $status, $aiEnabled ? 1 : 0, $conversationId]);
}