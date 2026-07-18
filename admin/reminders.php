<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT n.*, c.full_name, c.company_name, c.email
    FROM contact_notes n
    INNER JOIN contacts c ON c.id = n.contact_id
    WHERE n.follow_up_date IS NOT NULL
    ORDER BY n.follow_up_date ASC, n.id DESC
")->fetchAll();

admin_header('Reminders');
?>

<div class="card">
    <h3 style="margin-top:0;">Follow-up Reminders</h3>
    <p class="muted">This page shows all contacts with follow-up dates from your notes.</p>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Follow Up Date</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Note</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No reminders found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['follow_up_date']) ?></td>
                        <td><?= h($r['full_name']) ?></td>
                        <td><?= h($r['company_name']) ?></td>
                        <td><?= h($r['email']) ?></td>
                        <td><?= h(mb_strimwidth((string)$r['note_text'], 0, 80, '...')) ?></td>
                        <td><a href="/admin/contact_notes.php?contact_id=<?= (int)$r['contact_id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Follow Up Date</span><?= h($r['follow_up_date']) ?></div>
                    <div class="row"><span class="label">Name</span><?= h($r['full_name']) ?></div>
                    <div class="row"><span class="label">Company</span><?= h($r['company_name']) ?></div>
                    <div class="row"><span class="label">Email</span><?= h($r['email']) ?></div>
                    <div class="row"><span class="label">Note</span><?= h(mb_strimwidth((string)$r['note_text'], 0, 80, '...')) ?></div>
                    <div class="row"><a href="/admin/contact_notes.php?contact_id=<?= (int)$r['contact_id'] ?>" class="btn-secondary">Open</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>