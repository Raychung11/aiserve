<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = [
        'openai_api_key',
        'openai_model',
        'wa_ai_company_name',
        'wa_ai_brand_name',
        'wa_ai_default_language',
        'wa_ai_reply_delay_seconds'
    ];

    foreach ($fields as $field) {
        set_setting($field, trim((string)($_POST[$field] ?? '')));
    }

    set_setting('wa_ai_auto_reply_enabled', isset($_POST['wa_ai_auto_reply_enabled']) ? '1' : '0');

    $message = 'WhatsApp AI settings saved.';
}

admin_header('WhatsApp AI Settings');
?>

<div class="card">
    <h3 style="margin-top:0;">WhatsApp AI Settings</h3>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>

        <div class="form-grid">
            <div class="field full">
                <label>OpenAI API Key</label>
                <input type="password" name="openai_api_key" value="<?= h(get_setting('openai_api_key')) ?>">
            </div>

            <div class="field">
                <label>OpenAI Model</label>
                <input type="text" name="openai_model" value="<?= h(get_setting('openai_model', 'gpt-4.1-mini')) ?>">
            </div>

            <div class="field">
                <label>Reply Delay Seconds</label>
                <input type="number" name="wa_ai_reply_delay_seconds" value="<?= h(get_setting('wa_ai_reply_delay_seconds', '0')) ?>">
            </div>

            <div class="field">
                <label>Company Name</label>
                <input type="text" name="wa_ai_company_name" value="<?= h(get_setting('wa_ai_company_name', 'SLV Group Sdn Bhd')) ?>">
            </div>

            <div class="field">
                <label>Brand Name</label>
                <input type="text" name="wa_ai_brand_name" value="<?= h(get_setting('wa_ai_brand_name', 'AiServe.my')) ?>">
            </div>

            <div class="field">
                <label>Default Language</label>
                <input type="text" name="wa_ai_default_language" value="<?= h(get_setting('wa_ai_default_language', 'English')) ?>">
            </div>

            <div class="field full">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="wa_ai_auto_reply_enabled" value="1" <?= get_setting('wa_ai_auto_reply_enabled', '0') === '1' ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    Enable automatic AI replies
                </label>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save AI Settings</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>