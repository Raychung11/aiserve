<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/mailer.php';

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

$resultMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $filterStatus = trim($_POST['contact_status'] ?? '');
    $interest = trim($_POST['interest'] ?? '');
    $companySize = trim($_POST['company_size'] ?? '');
    $limit = (int)($_POST['send_limit'] ?? 50);
    $offset = (int)($_POST['send_offset'] ?? 0);

    if ($limit <= 0) $limit = 50;
    if ($limit > 500) $limit = 500;
    if ($offset < 0) $offset = 0;

    $sql = "SELECT * FROM contacts WHERE is_subscribed = 1";
    $params = [];

    if ($filterStatus !== '' && in_array($filterStatus, contact_status_options(), true)) {
        $sql .= " AND status = ?";
        $params[] = $filterStatus;
    }

    if ($interest !== '') {
        $sql .= " AND interest = ?";
        $params[] = $interest;
    }

    if ($companySize !== '') {
        $sql .= " AND company_size = ?";
        $params[] = $companySize;
    }

    $sql .= " ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($contacts as $contact) {
        $contactId = (int)$contact['id'];

        if (campaign_already_logged((int)$campaign['id'], $contactId)) {
            $skipped++;
            continue;
        }

        $token = ensure_unsubscribe_token($contactId);
        $unsubscribeUrl = APP_URL . '/unsubscribe.php?token=' . urlencode($token);

        $subject = str_replace('[Name]', (string)$contact['full_name'], (string)$campaign['subject']);
        $bodyText = str_replace('[Name]', (string)$contact['full_name'], (string)$campaign['body']);

        $htmlBody = nl2br(h($bodyText));
        $htmlBody .= '<br><br><small>If you no longer wish to receive these emails, <a href="' . h($unsubscribeUrl) . '">unsubscribe here</a>.</small>';

        $sendResult = send_smtp_mail(
            (string)$contact['email'],
            (string)$contact['full_name'],
            $subject,
            $htmlBody,
            $bodyText . "\n\nUnsubscribe: " . $unsubscribeUrl
        );

        $status = $sendResult['ok'] ? 'sent' : 'failed';

        $logStmt = db()->prepare("
            INSERT INTO email_campaign_recipients
            (campaign_id, contact_id, email, send_status, sent_at, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $campaign['id'],
            $contactId,
            $contact['email'],
            $status,
            $sendResult['ok'] ? date('Y-m-d H:i:s') : null
        ]);

        if ($sendResult['ok']) {
            $sent++;
        } else {
            $failed++;
        }
    }

    $campaignStatus = $sent > 0 ? 'sent' : $campaign['status'];
    $sentAt = $sent > 0 ? date('Y-m-d H:i:s') : null;

    $update = db()->prepare("UPDATE email_campaigns SET status = ?, sent_at = COALESCE(?, sent_at), updated_at = NOW() WHERE id = ?");
    $update->execute([$campaignStatus, $sentAt, $campaign['id']]);

    $resultMessage = "Campaign processed. Sent: {$sent}, Failed: {$failed}, Skipped: {$skipped}.";
}

$interestRows = db()->query("SELECT DISTINCT interest FROM contacts WHERE interest IS NOT NULL AND interest <> '' ORDER BY interest ASC")->fetchAll();
$sizeRows = db()->query("SELECT DISTINCT company_size FROM contacts WHERE company_size IS NOT NULL AND company_size <> '' ORDER BY company_size ASC")->fetchAll();

admin_header('Send Campaign');
?>

<div class="card">
    <h3 style="margin-top:0;">Send Campaign #<?= (int)$campaign['id'] ?></h3>
    <p><strong>Subject:</strong> <?= h($campaign['subject']) ?></p>
    <p class="muted">Only subscribed contacts will receive this campaign. Previously logged recipients will be skipped.</p>

    <?php if ($resultMessage !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($resultMessage) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Contact Status Filter</label>
                <select name="contact_status">
                    <option value="">All subscribed contacts</option>
                    <?php foreach (contact_status_options() as $opt): ?>
                        <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Interest Filter</label>
                <select name="interest">
                    <option value="">All interests</option>
                    <?php foreach ($interestRows as $row): ?>
                        <option value="<?= h($row['interest']) ?>"><?= h($row['interest']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Company Size Filter</label>
                <select name="company_size">
                    <option value="">All company sizes</option>
                    <?php foreach ($sizeRows as $row): ?>
                        <option value="<?= h($row['company_size']) ?>"><?= h($row['company_size']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Send Limit</label>
                <input type="number" name="send_limit" value="50" min="1" max="500">
            </div>

            <div class="field">
                <label>Send Offset</label>
                <input type="number" name="send_offset" value="0" min="0">
            </div>

            <div class="field full">
                <button type="submit" class="btn">Send Campaign Batch</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>