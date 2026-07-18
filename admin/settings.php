<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_email',
        'smtp_from_name'
    ];

    foreach ($fields as $field) {
        set_setting($field, trim((string)($_POST[$field] ?? '')));
    }

    $message = 'Settings saved successfully.';
}

admin_header('Settings');
?>

<div class="card">
    <h3 style="margin-top:0;">SMTP Settings</h3>
    <p class="muted">Save your email sending settings here before sending campaigns.</p>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" value="<?= h(get_setting('smtp_host')) ?>">
            </div>

            <div class="field">
                <label>SMTP Port</label>
                <input type="text" name="smtp_port" value="<?= h(get_setting('smtp_port', '587')) ?>">
            </div>

            <div class="field">
                <label>SMTP Username</label>
                <input type="text" name="smtp_username" value="<?= h(get_setting('smtp_username')) ?>">
            </div>

            <div class="field">
                <label>SMTP Password</label>
                <input type="password" name="smtp_password" value="<?= h(get_setting('smtp_password')) ?>">
            </div>

            <div class="field">
                <label>Encryption</label>
                <select name="smtp_encryption">
                    <option value="tls" <?= get_setting('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>tls</option>
                    <option value="ssl" <?= get_setting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>ssl</option>
                </select>
            </div>

            <div class="field">
                <label>From Email</label>
                <input type="email" name="smtp_from_email" value="<?= h(get_setting('smtp_from_email', 'no-reply@aiserve.io')) ?>">
            </div>

            <div class="field full">
                <label>From Name</label>
                <input type="text" name="smtp_from_name" value="<?= h(get_setting('smtp_from_name', COMPANY_NAME)) ?>">
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Settings</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>