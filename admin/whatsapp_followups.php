<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT
        f.*,
        wc.push_name,
        wc.phone,
        c.id AS conversation_id
    FROM wa_followups f
    INNER JOIN wa_contacts wc ON wc.id = f.wa_contact_id
    INNER JOIN wa_conversations c ON c.id = f.conversation_id
    ORDER BY f.scheduled_for ASC, f.id DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Follow-ups');
?>

<div class="card">
    <h3 style="margin-top:0;">Scheduled WhatsApp Follow-ups</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Scheduled For</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No follow-ups scheduled.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['scheduled_for']) ?></td>
                        <td><?= h($r['push_name']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h($r['followup_type']) ?></td>
                        <td><span class="pill"><?= h($r['followup_status']) ?></span></td>
                        <td><?= h(mb_strimwidth((string)$r['followup_message'], 0, 80, '...')) ?></td>
                        <td><a href="/admin/whatsapp_conversation.php?id=<?= (int)$r['conversation_id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Scheduled</span><?= h($r['scheduled_for']) ?></div>
                    <div class="row"><span class="label">Name</span><?= h($r['push_name']) ?></div>
                    <div class="row"><span class="label">Phone</span><?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Type</span><?= h($r['followup_type']) ?></div>
                    <div class="row"><span class="label">Status</span><?= h($r['followup_status']) ?></div>
                    <div class="row"><span class="label">Message</span><?= h(mb_strimwidth((string)$r['followup_message'], 0, 80, '...')) ?></div>
                    <div class="row"><a href="/admin/whatsapp_conversation.php?id=<?= (int)$r['conversation_id'] ?>" class="btn-secondary">Open</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>