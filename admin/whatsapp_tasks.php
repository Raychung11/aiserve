<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT t.*, wc.push_name, wc.phone, a.full_name AS assigned_name
    FROM wa_sales_tasks t
    INNER JOIN wa_contacts wc ON wc.id = t.wa_contact_id
    LEFT JOIN admin_users a ON a.id = t.assigned_admin_id
    ORDER BY t.created_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Sales Tasks');
?>

<div class="card">
    <h3 style="margin-top:0;">Sales Tasks</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Assigned</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['task_title']) ?></td>
                        <td><?= h($r['push_name']) ?><br><span class="muted"><?= h($r['phone']) ?></span></td>
                        <td><?= h($r['assigned_name']) ?></td>
                        <td><?= h($r['task_type']) ?></td>
                        <td><span class="pill"><?= h($r['task_status']) ?></span></td>
                        <td><?= h($r['priority_level']) ?></td>
                        <td><?= h($r['due_at']) ?></td>
                        <td><a href="/admin/whatsapp_task_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Title</span><?= h($r['task_title']) ?></div>
                    <div class="row"><span class="label">Contact</span><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Assigned</span><?= h($r['assigned_name']) ?></div>
                    <div class="row"><span class="label">Type</span><?= h($r['task_type']) ?></div>
                    <div class="row"><span class="label">Status</span><?= h($r['task_status']) ?></div>
                    <div class="row"><span class="label">Priority</span><?= h($r['priority_level']) ?></div>
                    <div class="row"><span class="label">Due</span><?= h($r['due_at']) ?></div>
                    <div class="row"><a href="/admin/whatsapp_task_view.php?id=<?= (int)$r['id'] ?>" class="btn-secondary">Open</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>