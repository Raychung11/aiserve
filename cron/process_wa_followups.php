<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/evolution_sender.php';

$limit = 20;

$stmt = db()->prepare("
    SELECT f.*, c.instance_name, c.remote_jid, wc.phone
    FROM wa_followups f
    INNER JOIN wa_conversations c ON c.id = f.conversation_id
    INNER JOIN wa_contacts wc ON wc.id = f.wa_contact_id
    WHERE f.followup_status = 'pending'
      AND f.scheduled_for <= NOW()
    ORDER BY f.id ASC
    LIMIT {$limit}
");
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $instanceName = (string)($row['instance_name'] ?: get_setting('evolution_default_instance', 'main'));
    $phone = preg_replace('/[^0-9]/', '', (string)$row['phone']);
    $text = trim((string)$row['followup_message']);

    if ($phone === '' || $text === '') {
        $upd = db()->prepare("UPDATE wa_followups SET followup_status = 'failed', processed_at = NOW() WHERE id = ?");
        $upd->execute([$row['id']]);
        continue;
    }

    $send = evolution_send_text($instanceName, $phone, $text);

    wa_log_outbound(
        $instanceName,
        (int)$row['conversation_id'],
        (int)$row['wa_contact_id'],
        (string)$row['remote_jid'],
        'scheduled_followup',
        ['number' => $phone, 'text' => $text, 'followup_id' => $row['id']],
        $send,
        $send['ok'] ? 'success' : 'failed',
        null
    );

    if ($send['ok']) {
        $upd = db()->prepare("UPDATE wa_followups SET followup_status = 'sent', processed_at = NOW() WHERE id = ?");
        $upd->execute([$row['id']]);
    } else {
        $upd = db()->prepare("UPDATE wa_followups SET followup_status = 'failed', processed_at = NOW() WHERE id = ?");
        $upd->execute([$row['id']]);
    }
}

echo "Processed " . count($rows) . " WhatsApp follow-up(s).\n";