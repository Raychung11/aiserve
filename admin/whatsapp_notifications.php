<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT n.*, c.remote_jid, wc.push_name, wc.phone
    FROM wa_internal_notifications n
    INNER JOIN wa_conversations c ON c.id = n.conversation_id
    INNER JOIN wa_contacts wc ON wc.id = n.wa_contact_id
    ORDER BY n.id DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Internal Notifications');
?>

<div class="card">
    <h3 style="margin-top:0;">Internal Notifications</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No notifications found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['notification_type']) ?></td>
                        <td><?= h($r['title']) ?></td>
                        <td><?= h($r['push_name']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><span class="pill"><?= h($r['notify_status']) ?></span></td>
                        <td><?= h($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Type</span><?= h($r['notification_type']) ?></div>
                    <div class="row"><span class="label">Title</span><?= h($r['title']) ?></div>
                    <div class="row"><span class="label">Contact</span><?= h($r['push_name']) ?></div>
                    <div class="row"><span class="label">Phone</span><?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Status</span><?= h($r['notify_status']) ?></div>
                    <div class="row"><span class="label">Created</span><?= h($r['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>