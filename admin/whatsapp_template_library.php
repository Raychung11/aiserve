<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $templateName = trim($_POST['template_name'] ?? '');
    $templateKey = trim($_POST['template_key'] ?? '');
    $intentKey = trim($_POST['intent_key'] ?? '');
    $departmentKey = trim($_POST['department_key'] ?? '');
    $languageKey = trim($_POST['language_key'] ?? 'English');
    $templateText = trim($_POST['template_text'] ?? '');

    if ($templateName !== '' && $templateKey !== '' && $templateText !== '') {
        $stmt = db()->prepare("
            INSERT INTO wa_template_library
            (template_name, template_key, intent_key, department_key, language_key, template_text, is_active, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                template_name = VALUES(template_name),
                intent_key = VALUES(intent_key),
                department_key = VALUES(department_key),
                language_key = VALUES(language_key),
                template_text = VALUES(template_text),
                updated_at = NOW()
        ");
        $stmt->execute([
            $templateName,
            $templateKey,
            $intentKey,
            $departmentKey,
            $languageKey,
            $templateText,
            $_SESSION['admin_id'] ?? null
        ]);
    }

    redirect('/admin/whatsapp_template_library.php');
}

$rows = db()->query("SELECT * FROM wa_template_library ORDER BY id DESC")->fetchAll();

admin_header('WhatsApp Template Library');
?>

<div class="card">
    <h3 style="margin-top:0;">Template Library</h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Template Name</label>
                <input type="text" name="template_name" required>
            </div>
            <div class="field">
                <label>Template Key</label>
                <input type="text" name="template_key" required>
            </div>
            <div class="field">
                <label>Intent Key</label>
                <input type="text" name="intent_key" placeholder="quote_request">
            </div>
            <div class="field">
                <label>Department Key</label>
                <input type="text" name="department_key" placeholder="sales">
            </div>
            <div class="field">
                <label>Language</label>
                <input type="text" name="language_key" value="English">
            </div>
            <div class="field full">
                <label>Template Text</label>
                <textarea name="template_text" required>Hello [Name], thank you for your interest. We are reviewing your request and will guide you shortly.</textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Save Template</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Saved Templates</h3>
    <?php foreach ($rows as $r): ?>
        <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
            <strong><?= h($r['template_name']) ?></strong>
            <div class="muted">Intent: <?= h($r['intent_key']) ?> | Dept: <?= h($r['department_key']) ?> | Lang: <?= h($r['language_key']) ?></div>
            <div style="white-space:pre-wrap;margin-top:6px;"><?= h($r['template_text']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>