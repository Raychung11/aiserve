<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT
        a.full_name,
        COUNT(t.id) AS total_tasks,
        SUM(CASE WHEN t.task_status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
        SUM(CASE WHEN t.task_status IN ('open','in_progress','waiting_customer') THEN 1 ELSE 0 END) AS active_tasks
    FROM admin_users a
    LEFT JOIN wa_sales_tasks t ON t.assigned_admin_id = a.id
    WHERE a.is_active = 1
    GROUP BY a.id
    ORDER BY completed_tasks DESC, total_tasks DESC
")->fetchAll();

admin_header('WhatsApp Agent Leaderboard');
?>

<div class="card">
    <h3 style="margin-top:0;">Agent Leaderboard</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Completed Tasks</th>
                    <th>Total Tasks</th>
                    <th>Active Tasks</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['full_name']) ?></td>
                    <td><?= (int)$r['completed_tasks'] ?></td>
                    <td><?= (int)$r['total_tasks'] ?></td>
                    <td><?= (int)$r['active_tasks'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>