<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$totalCampaigns = count_table('email_campaigns');
$draftCampaigns = count_campaigns_by_status('draft');
$readyCampaigns = count_campaigns_by_status('ready');
$sentCampaigns = count_campaigns_by_status('sent');

$totalRecipientLogs = count_table('email_campaign_recipients');
$sentLogs = count_recipients_by_send_status('sent');
$failedLogs = count_recipients_by_send_status('failed');

$stmt = db()->query("
    SELECT c.id, c.subject,
           COUNT(r.id) AS total_logs,
           SUM(CASE WHEN r.send_status = 'sent' THEN 1 ELSE 0 END) AS sent_count,
           SUM(CASE WHEN r.send_status = 'failed' THEN 1 ELSE 0 END) AS failed_count
    FROM email_campaigns c
    LEFT JOIN email_campaign_recipients r ON r.campaign_id = c.id
    GROUP BY c.id
    ORDER BY c.id DESC
");
$rows = $stmt->fetchAll();

admin_header('Campaign Analytics');
?>

<div class="stats">
    <div class="card stat">
        <strong><?= $totalCampaigns ?></strong>
        <span class="muted">Total Campaigns</span>
    </div>
    <div class="card stat">
        <strong><?= $sentCampaigns ?></strong>
        <span class="muted">Sent Campaigns</span>
    </div>
    <div class="card stat">
        <strong><?= $sentLogs ?></strong>
        <span class="muted">Emails Sent</span>
    </div>
    <div class="card stat">
        <strong><?= $failedLogs ?></strong>
        <span class="muted">Failed Emails</span>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Campaign Performance Summary</h3>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Total Logs</th>
                    <th>Sent</th>
                    <th>Failed</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No campaign analytics yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['subject']) ?></td>
                        <td><?= (int)$r['total_logs'] ?></td>
                        <td><?= (int)$r['sent_count'] ?></td>
                        <td><?= (int)$r['failed_count'] ?></td>
                        <td><a href="/admin/campaign_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>