<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/wa_assignment_helper.php';

function wa_task_create(
    int $conversationId,
    int $waContactId,
    ?int $crmContactId,
    string $taskType,
    string $title,
    string $description = '',
    string $priority = 'normal',
    ?string $dueAt = null,
    ?int $assignedAdminId = null
): int {
    if ($assignedAdminId === null) {
        $assignedAdminId = wa_pick_assigned_admin();
    }

    $stmt = db()->prepare("
        INSERT INTO wa_sales_tasks
        (conversation_id, wa_contact_id, crm_contact_id, assigned_admin_id, task_type, task_title, task_description, task_status, priority_level, due_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $conversationId,
        $waContactId,
        $crmContactId,
        $assignedAdminId,
        $taskType,
        $title,
        $description,
        $priority,
        $dueAt
    ]);

    return (int)db()->lastInsertId();
}

function wa_task_exists_open(int $conversationId, string $taskType): bool {
    $stmt = db()->prepare("
        SELECT id
        FROM wa_sales_tasks
        WHERE conversation_id = ?
          AND task_type = ?
          AND task_status IN ('open','in_progress','waiting_customer')
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $taskType]);
    return (bool)$stmt->fetch();
}