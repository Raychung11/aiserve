<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');

    if ($subject !== '' && $body !== '') {
        if (!in_array($status, campaign_status_options(), true)) {
            $status = 'draft';
        }

        $stmt = db()->prepare("
            INSERT INTO email_campaigns (subject, body, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $subject,
            $body,
            $status,
            $_SESSION['admin_id'] ?? null
        ]);

        redirect('/admin/campaigns.php');
    }
}

$stmt = db()->query("SELECT * FROM email_campaigns ORDER BY id DESC");
$rows = $stmt->fetchAll();

admin_header('Campaigns');
?>

<div class="card">
    <h3 style="margin-top:0;">Create Campaign</h3>
    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Subject</label>
                <input type="text" name="subject" required>
            </div>
            <div class="field full">
                <label>Body</label>
                <textarea name="body" required>Dear [Name],

We would like to introduce AiServe.io, the AI Business Operating System by SLV Group Sdn Bhd.

AiServe.io helps businesses move beyond static software by building AI-powered systems for workflow, reporting, customer service, and operational intelligence.

If your team is exploring AI + BI transformation, we would be glad to schedule a discussion.

Best regards,
SLV Group Sdn Bhd
AiServe.io</textarea>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="draft">draft</option>
                    <option value="ready">ready</option>
                </select>
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="btn">Save Campaign</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Saved Campaigns</h3>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Updated</th>
                    <th>Open</th>
                    <th>Recipients</th>
                    <th>Send</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8">No campaigns yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['subject']) ?></td>
                        <td><span class="pill"><?= h($r['status']) ?></span></td>
                        <td><?= h($r['sent_at'] ?? '') ?></td>
                        <td><?= h($r['updated_at']) ?></td>
                        <td><a href="/admin/campaign_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                        <td><a href="/admin/campaign_recipients.php?id=<?= (int)$r['id'] ?>">Logs</a></td>
                        <td>
                            <?php if ($r['status'] !== 'sent'): ?>
                                <a href="/admin/campaign_send.php?id=<?= (int)$r['id'] ?>">Send</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>