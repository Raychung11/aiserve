<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
if ($conversationId <= 0) redirect('/admin/whatsapp_inbox.php');

$stmt = db()->prepare("
    SELECT c.*, wc.push_name, wc.phone
    FROM wa_conversations c
    LEFT JOIN wa_contacts wc ON wc.id = c.wa_contact_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$conversationId]);
$conversation = $stmt->fetch();
if (!$conversation) redirect('/admin/whatsapp_inbox.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $noteText = trim($_POST['note_text'] ?? '');
    if ($noteText !== '') {
        $stmt = db()->prepare("
            INSERT INTO wa_conversation_notes
            (conversation_id, wa_contact_id, admin_id, note_text, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $conversationId,
            $conversation['wa_contact_id'],
            $_SESSION['admin_id'] ?? null,
            $noteText
        ]);
    }

    redirect('/admin/whatsapp_notes.php?conversation_id=' . $conversationId);
}

$notesStmt = db()->prepare("
    SELECT n.*, a.full_name AS admin_name
    FROM wa_conversation_notes n
    LEFT JOIN admin_users a ON a.id = n.admin_id
    WHERE n.conversation_id = ?
    ORDER BY n.id DESC
");
$notesStmt->execute([$conversationId]);
$notes = $notesStmt->fetchAll();

admin_header('WhatsApp Notes');
?>

<div class="card">
    <h3 style="margin-top:0;">Conversation Notes</h3>
    <p class="muted"><?= h($conversation['push_name'] ?: 'Unknown') ?> • <?= h($conversation['phone']) ?></p>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Internal Note</label>
                <textarea name="note_text" required></textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Add Note</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Note History</h3>
    <?php if (!$notes): ?>
        <p class="muted">No notes yet.</p>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
                <div style="white-space:pre-wrap;"><?= h($note['note_text']) ?></div>
                <div class="muted" style="margin-top:8px;font-size:14px;">
                    By <?= h($note['admin_name']) ?> • <?= h($note['created_at']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>