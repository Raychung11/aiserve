<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$taskByStaff = db()->query("
    SELECT a.full_name,
           COUNT(t.id) AS total_tasks,
           SUM(CASE WHEN t.task_status IN ('open','in_progress','waiting_customer') THEN 1 ELSE 0 END) AS active_tasks,
           SUM(CASE WHEN t.task_status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
    FROM admin_users a
    LEFT JOIN wa_sales_tasks t ON t.assigned_admin_id = a.id
    WHERE a.is_active = 1
    GROUP BY a.id
    ORDER BY total_tasks DESC, a.full_name ASC
")->fetchAll();

$slaRows = db()->query("
    SELECT
        AVG(first_response_minutes) AS avg_first_response,
        AVG(total_resolution_minutes) AS avg_resolution
    FROM wa_sla_logs
")->fetch();

admin_header('WhatsApp Manager Dashboard');
?>

<div class="stats">
    <div class="card stat">
        <strong><?= count_table('wa_sales_tasks') ?></strong>
        <span class="muted">Total WA Tasks</span>
    </div>
    <div class="card stat">
        <strong><?= count_table('wa_followups') ?></strong>
        <span class="muted">Scheduled Follow-ups</span>
    </div>
    <div class="card stat">
        <strong><?= (int)round((float)($slaRows['avg_first_response'] ?? 0)) ?></strong>
        <span class="muted">Avg First Response (min)</span>
    </div>
    <div class="card stat">
        <strong><?= (int)round((float)($slaRows['avg_resolution'] ?? 0)) ?></strong>
        <span class="muted">Avg Resolution (min)</span>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Team Task Load</h3>
    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Total Tasks</th>
                    <th>Active Tasks</th>
                    <th>Completed Tasks</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($taskByStaff as $r): ?>
                <tr>
                    <td><?= h($r['full_name']) ?></td>
                    <td><?= (int)$r['total_tasks'] ?></td>
                    <td><?= (int)$r['active_tasks'] ?></td>
                    <td><?= (int)$r['completed_tasks'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($taskByStaff as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Staff</span><?= h($r['full_name']) ?></div>
                    <div class="row"><span class="label">Total Tasks</span><?= (int)$r['total_tasks'] ?></div>
                    <div class="row"><span class="label">Active Tasks</span><?= (int)$r['active_tasks'] ?></div>
                    <div class="row"><span class="label">Completed Tasks</span><?= (int)$r['completed_tasks'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>