<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/openai_helper.php';

$message = '';
$messageType = 'ok';
$testResult = null;

/** Mask a stored secret for display. */
function ai_mask_key(string $k): string {
    if ($k === '') {
        return '';
    }
    if (strlen($k) <= 8) {
        return str_repeat('•', strlen($k));
    }
    return substr($k, 0, 3) . str_repeat('•', 6) . substr($k, -4);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Update key only when a new value is entered (so the masked field can be left blank).
    $postedKey = trim((string)($_POST['openai_api_key'] ?? ''));
    if ($postedKey !== '') {
        set_setting('openai_api_key', $postedKey);
    }

    $model = trim((string)($_POST['openai_model'] ?? ''));
    set_setting('openai_model', $model !== '' ? $model : 'gpt-4.1-mini');

    $imageModel = trim((string)($_POST['openai_image_model'] ?? ''));
    set_setting('openai_image_model', $imageModel !== '' ? $imageModel : 'dall-e-3');

    $message = 'AI settings saved.';

    // Optional connectivity test
    if (($_POST['action'] ?? '') === 'save_test') {
        $res = openai_chat_json(
            [['role' => 'user', 'content' => 'Reply with a JSON object {"ok": true}.']],
            'Return valid JSON only.'
        );
        if (!empty($res['ok'])) {
            $testResult = ['ok' => true, 'text' => 'Connection successful using model "' . ($res['model'] ?? get_setting('openai_model')) . '".'];
        } else {
            $testResult = ['ok' => false, 'text' => 'Test failed: ' . (string)($res['error'] ?? 'Unknown error')];
        }
    }
}

$currentKey = get_setting('openai_api_key');
$currentModel = get_setting('openai_model', 'gpt-4.1-mini');

admin_header('AI API Settings');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">AI API Settings</h3>
            <p class="muted" style="margin:6px 0 0;">Configure the OpenAI API used by the website chat assistant, WhatsApp AI, and the SEO assistant.</p>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success" style="margin-top:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($testResult !== null): ?>
        <div class="alert <?= $testResult['ok'] ? 'alert-success' : 'alert-error' ?>" style="margin-top:6px;"><?= h($testResult['text']) ?></div>
    <?php endif; ?>

    <form method="post" style="margin-top:8px;">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>OpenAI API Key</label>
                <input type="password" name="openai_api_key" autocomplete="off"
                       placeholder="<?= $currentKey !== '' ? 'Saved: ' . h(ai_mask_key($currentKey)) . ' — leave blank to keep' : 'sk-...' ?>">
                <div class="muted" style="font-size:13px;">
                    <?php if ($currentKey !== ''): ?>
                        A key is currently saved. Enter a new key only to replace it.
                    <?php else: ?>
                        No key saved yet. Paste your OpenAI API key (starts with <code>sk-</code>).
                    <?php endif; ?>
                </div>
            </div>

            <div class="field">
                <label>Text Model</label>
                <input type="text" name="openai_model" value="<?= h($currentModel) ?>" placeholder="gpt-4.1-mini">
                <div class="muted" style="font-size:13px;">e.g. gpt-4.1-mini, gpt-4.1, gpt-4o-mini</div>
            </div>

            <div class="field">
                <label>Image Model</label>
                <input type="text" name="openai_image_model" value="<?= h(get_setting('openai_image_model', 'dall-e-3')) ?>" placeholder="dall-e-3">
                <div class="muted" style="font-size:13px;">dall-e-3 (recommended — fast, works on any account) or gpt-image-1 (higher quality but slower and needs a verified OpenAI org)</div>
            </div>

            <div class="field full">
                <button type="submit" name="action" value="save" class="btn">Save Settings</button>
                <button type="submit" name="action" value="save_test" class="btn-secondary" style="margin-left:8px;">Save &amp; Test Connection</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Where this key is used</h3>
    <ul class="muted" style="margin:0;padding-left:18px;line-height:1.9;">
        <li>Website chat assistant (<code>/api/chat.php</code>)</li>
        <li>WhatsApp AI auto-replies and lead tools</li>
        <li>SEO assistant on the SEO Meta page</li>
    </ul>
    <p class="muted" style="margin:12px 0 0;font-size:13px;">
        The key is stored in the database and takes priority over any <code>OPENAI_API_KEY</code> set in the server <code>.env</code> file (which is used as a fallback).
    </p>
</div>

<?php admin_footer(); ?>
