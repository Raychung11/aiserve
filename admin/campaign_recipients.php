<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('/admin/campaigns.php');
}

$stmt = db()->prepare("SELECT * FROM email_campaigns WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    redirect('/admin/campaigns.php');
}

$stmt = db()->prepare("
    SELECT r.*, c.full_name, c.company_name
    FROM email_campaign_recipients r
    LEFT JOIN contacts c ON c.id = r.contact_id
    WHERE r.campaign_id = ?
    ORDER BY r.id DESC
");
$stmt->execute([$id]);
$rows = $stmt->fetchAll();

admin_header('Campaign Recipient Logs');
?>

<div class="card">
    <h3 style="margin-top:0;">Campaign #<?= (int)$campaign['id'] ?> Recipient Logs</h3>
    <p><strong>Subject:</strong> <?= h($campaign['subject']) ?></p>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No logs yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['full_name']) ?></td>
                        <td><?= h($r['company_name']) ?></td>
                        <td><?= h($r['email']) ?></td>
                        <td><span class="pill"><?= h($r['send_status']) ?></span></td>
                        <td><?= h($r['sent_at']) ?></td>
                        <td><?= h($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>