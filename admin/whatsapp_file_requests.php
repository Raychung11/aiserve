<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT f.*, wc.push_name, wc.phone
    FROM wa_file_requests f
    INNER JOIN wa_contacts wc ON wc.id = f.wa_contact_id
    ORDER BY f.updated_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp File Requests');
?>

<div class="card">
    <h3 style="margin-top:0;">Requested Files</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Requested Message</th>
                    <th>File</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No file requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></td>
                        <td><?= h($r['request_type']) ?></td>
                        <td><span class="pill"><?= h($r['request_status']) ?></span></td>
                        <td><?= h($r['request_message']) ?></td>
                        <td>
                            <?php if (!empty($r['file_url'])): ?>
                                <a href="<?= h($r['file_url']) ?>" target="_blank">Open</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= h($r['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>