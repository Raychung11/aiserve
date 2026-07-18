<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$stmt = db()->prepare("
    UPDATE wa_sales_tasks
    SET priority_level = 'high', updated_at = NOW()
    WHERE task_status IN ('open','in_progress','waiting_customer')
      AND due_at IS NOT NULL
      AND due_at < NOW()
      AND priority_level <> 'high'
");
$stmt->execute();

echo "Processed overdue WhatsApp sales tasks.\n";