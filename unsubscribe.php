<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';

$token = trim($_GET['token'] ?? '');
$message = 'Invalid unsubscribe request.';

if ($token !== '') {
    $stmt = db()->prepare("SELECT id, full_name, email, is_subscribed FROM contacts WHERE unsubscribe_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $contact = $stmt->fetch();

    if ($contact) {
        if ((int)$contact['is_subscribed'] === 1) {
            $stmt = db()->prepare("UPDATE contacts SET is_subscribed = 0 WHERE id = ?");
            $stmt->execute([$contact['id']]);
        }
        $message = 'You have been unsubscribed successfully.';
    }
}

$pageTitle = 'Unsubscribe | AiServe.io';
$pageDescription = 'Manage your email subscription preferences.';
include __DIR__ . '/inc/public_header.php';
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container" style="max-width:760px;">
        <div class="hero-card">
            <div class="eyebrow">Email Preferences</div>
            <h1 style="font-size:42px;">Unsubscribe</h1>
            <p><?= h($message) ?></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>