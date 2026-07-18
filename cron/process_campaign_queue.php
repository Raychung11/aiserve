<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/mailer.php';

$limit = 20;

$stmt = db()->prepare("
    SELECT * FROM campaign_queue
    WHERE queue_status = 'pending' AND scheduled_at <= NOW()
    ORDER BY id ASC
    LIMIT {$limit}
");
$stmt->execute();
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $update = db()->prepare("UPDATE campaign_queue SET queue_status = 'processing' WHERE id = ? AND queue_status = 'pending'");
    $update->execute([$job['id']]);

    $token = ensure_unsubscribe_token((int)$job['contact_id']);
    $unsubscribeUrl = APP_URL . '/unsubscribe.php?token=' . urlencode($token);

    $htmlBody = nl2br(h($job['body_text']));
    $htmlBody .= '<br><br><small>If you no longer wish to receive these emails, <a href="' . h($unsubscribeUrl) . '">unsubscribe here</a>.</small>';

    $sendResult = send_smtp_mail(
        (string)$job['email'],
        '',
        (string)$job['subject'],
        $htmlBody,
        (string)$job['body_text'] . "\n\nUnsubscribe: " . $unsubscribeUrl
    );

    if ($sendResult['ok']) {
        $stmt = db()->prepare("
            UPDATE campaign_queue
            SET queue_status = 'sent', processed_at = NOW(), error_message = NULL
            WHERE id = ?
        ");
        $stmt->execute([$job['id']]);

        $stmt = db()->prepare("
            INSERT INTO email_campaign_recipients
            (campaign_id, contact_id, email, send_status, sent_at, created_at)
            VALUES (?, ?, ?, 'sent', NOW(), NOW())
            ON DUPLICATE KEY UPDATE send_status='sent', sent_at=NOW()
        ");
        $stmt->execute([$job['campaign_id'], $job['contact_id'], $job['email']]);
    } else {
        $stmt = db()->prepare("
            UPDATE campaign_queue
            SET queue_status = 'failed', processed_at = NOW(), error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$sendResult['message'], $job['id']]);

        $stmt = db()->prepare("
            INSERT INTO email_campaign_recipients
            (campaign_id, contact_id, email, send_status, sent_at, created_at)
            VALUES (?, ?, ?, 'failed', NULL, NOW())
            ON DUPLICATE KEY UPDATE send_status='failed'
        ");
        $stmt->execute([$job['campaign_id'], $job['contact_id'], $job['email']]);
    }
}

echo "Processed " . count($jobs) . " queue item(s).\n";