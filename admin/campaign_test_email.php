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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $testEmail = trim($_POST['test_email'] ?? '');
    $testName  = trim($_POST['test_name'] ?? 'Test User');

    if ($testEmail !== '' && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $subject = str_replace('[Name]', $testName, (string)$campaign['subject']);
        $bodyText = str_replace('[Name]', $testName, (string)$campaign['body']);
        $htmlBody = nl2br(h($bodyText));

        $result = send_smtp_mail($testEmail, $testName, $subject, $htmlBody, $bodyText);
        $message = $result['ok'] ? 'Test email sent successfully.' : 'Failed: ' . $result['message'];
    } else {
        $message = 'Please enter a valid test email.';
    }
}

admin_header('Send Test Email');
?>

<div class="card">
    <h3 style="margin-top:0;">Send Test Email for Campaign #<?= (int)$campaign['id'] ?></h3>
    <p><strong>Subject:</strong> <?= h($campaign['subject']) ?></p>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Test Recipient Name</label>
                <input type="text" name="test_name" value="Test User">
            </div>

            <div class="field">
                <label>Test Recipient Email</label>
                <input type="email" name="test_email" required>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Send Test Email</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>