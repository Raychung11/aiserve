<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT q.*, wc.push_name, wc.phone
    FROM wa_generated_quotes q
    INNER JOIN wa_contacts wc ON wc.id = q.wa_contact_id
    ORDER BY q.created_at DESC
    LIMIT 200
")->fetchAll();

admin_header('Generated WhatsApp Quotes');
?>

<div class="card">
    <h3 style="margin-top:0;">Generated Quotes</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="5">No generated quotes yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['quote_reference']) ?></td>
                        <td><?= h($r['quote_title']) ?></td>
                        <td><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></td>
                        <td><span class="pill"><?= h($r['quote_status']) ?></span></td>
                        <td><?= h($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>