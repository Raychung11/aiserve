<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $kbTitle = trim($_POST['kb_title'] ?? '');
    $kbCategory = trim($_POST['kb_category'] ?? '');
    $keywords = trim($_POST['objection_keywords'] ?? '');
    $reply = trim($_POST['recommended_reply'] ?? '');
    $note = trim($_POST['internal_note'] ?? '');

    if ($kbTitle !== '') {
        $stmt = db()->prepare("
            INSERT INTO wa_knowledge_base
            (kb_title, kb_category, objection_keywords, recommended_reply, internal_note, is_active, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $kbTitle,
            $kbCategory,
            $keywords,
            $reply,
            $note,
            $_SESSION['admin_id'] ?? null
        ]);
    }

    redirect('/admin/whatsapp_knowledge_base.php');
}

$rows = db()->query("SELECT * FROM wa_knowledge_base ORDER BY id DESC")->fetchAll();

admin_header('WhatsApp Knowledge Base');
?>

<div class="card">
    <h3 style="margin-top:0;">Objection Handling Knowledge Base</h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Title</label>
                <input type="text" name="kb_title" required>
            </div>
            <div class="field">
                <label>Category</label>
                <input type="text" name="kb_category" placeholder="price objection">
            </div>
            <div class="field full">
                <label>Keywords</label>
                <input type="text" name="objection_keywords" placeholder="expensive,too costly,price high">
            </div>
            <div class="field full">
                <label>Recommended Reply</label>
                <textarea name="recommended_reply"></textarea>
            </div>
            <div class="field full">
                <label>Internal Note</label>
                <textarea name="internal_note"></textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Save KB Entry</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Saved KB Entries</h3>
    <?php foreach ($rows as $r): ?>
        <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
            <strong><?= h($r['kb_title']) ?></strong>
            <div class="muted">Category: <?= h($r['kb_category']) ?></div>
            <div class="muted">Keywords: <?= h($r['objection_keywords']) ?></div>
            <div style="white-space:pre-wrap;margin-top:6px;"><?= h($r['recommended_reply']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>