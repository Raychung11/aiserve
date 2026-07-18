<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$limit = 20;

$stmt = db()->prepare("
    SELECT *
    FROM wa_internal_notifications
    WHERE notify_status = 'pending'
    ORDER BY id ASC
    LIMIT {$limit}
");
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    // For now, mark as sent after logging phase.
    // Later you can integrate internal WhatsApp send here.
    $upd = db()->prepare("
        UPDATE wa_internal_notifications
        SET notify_status = 'sent', processed_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$row['id']]);
}

echo "Processed " . count($rows) . " internal notification(s).\n";