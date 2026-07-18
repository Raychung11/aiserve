<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/wa_ai_engine.php';

$limit = 20;

$stmt = db()->prepare("
    SELECT q.*, m.message_text, m.direction, m.message_type, m.id AS wa_message_row_id,
           c.current_state, c.conversation_status, c.ai_enabled, c.instance_name, c.remote_jid, c.wa_contact_id, c.priority_level,
           wc.phone, wc.push_name
    FROM wa_ai_queue q
    INNER JOIN wa_messages m ON m.id = q.wa_message_id
    INNER JOIN wa_conversations c ON c.id = q.conversation_id
    LEFT JOIN wa_contacts wc ON wc.id = q.wa_contact_id
    WHERE q.queue_status = 'pending'
    ORDER BY q.id ASC
    LIMIT {$limit}
");
$stmt->execute();
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $lock = db()->prepare("UPDATE wa_ai_queue SET queue_status = 'processing' WHERE id = ? AND queue_status = 'pending'");
    $lock->execute([$job['id']]);

    if ($lock->rowCount() === 0) {
        continue;
    }

    $conversation = [
        'id' => $job['conversation_id'],
        'current_state' => $job['current_state'],
        'conversation_status' => $job['conversation_status'],
        'ai_enabled' => $job['ai_enabled'],
        'instance_name' => $job['instance_name'],
        'remote_jid' => $job['remote_jid'],
        'wa_contact_id' => $job['wa_contact_id'],
        'priority_level' => $job['priority_level'],
        'phone' => $job['phone'],
        'push_name' => $job['push_name'],
    ];

    $message = [
        'id' => $job['wa_message_row_id'],
        'message_text' => $job['message_text'],
        'direction' => $job['direction'],
        'message_type' => $job['message_type'],
    ];

    $result = wa_ai_process_message($conversation, $message);

    if ($result['status'] === 'done') {
        $upd = db()->prepare("
            UPDATE wa_ai_queue
            SET queue_status = 'done',
                intent_detected = ?,
                confidence_score = ?,
                state_before = ?,
                state_after = ?,
                ai_reply_text = ?,
                crm_synced = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([
            $result['intent'] ?? '',
            $result['lead_confidence_score'] ?? 0,
            $job['current_state'],
            $result['state_after'] ?? '',
            $result['reply'] ?? '',
            !empty($result['crm_contact_id']) ? 1 : 0,
            $job['id']
        ]);
    } elseif ($result['status'] === 'skipped') {
        $upd = db()->prepare("
            UPDATE wa_ai_queue
            SET queue_status = 'skipped',
                error_message = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([
            $result['reason'] ?? 'skipped',
            $job['id']
        ]);
    } else {
        $upd = db()->prepare("
            UPDATE wa_ai_queue
            SET queue_status = 'failed',
                intent_detected = ?,
                ai_reply_text = ?,
                error_message = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([
            $result['intent'] ?? '',
            $result['reply'] ?? '',
            $result['reason'] ?? 'failed',
            $job['id']
        ]);
    }
}

echo "Processed " . count($jobs) . " AI queue item(s).\n";