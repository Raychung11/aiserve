<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$stmt = db()->prepare("
    SELECT *
    FROM wa_quote_approvals
    WHERE approval_status = 'pending'
    ORDER BY id ASC
    LIMIT 20
");
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    // Future enhancement: auto-notify approver.
    // For now leave pending.
}

echo "Checked " . count($rows) . " quote approval item(s).\n";