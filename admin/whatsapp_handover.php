<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('/admin/whatsapp_inbox.php');

$stmt = db()->prepare("
    SELECT c.*, wc.push_name, wc.phone
    FROM wa_conversations c
    LEFT JOIN wa_contacts wc ON wc.id = c.wa_contact_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$conversation = $stmt->fetch();

if (!$conversation) redirect('/admin/whatsapp_inbox.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $mode = trim($_POST['mode'] ?? 'human');
    $note = trim($_POST['handover_note'] ?? '');

    if ($mode === 'human') {
        $stmt = db()->prepare("
            UPDATE wa_conversations
            SET conversation_status = 'pending_human',
                ai_enabled = 0,
                handover_note = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$note, $id]);
    } else {
        $stmt = db()->prepare("
            UPDATE wa_conversations
            SET conversation_status = 'open',
                ai_enabled = 1,
                handover_note = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$note, $id]);
    }

    redirect('/admin/whatsapp_conversation.php?id=' . $id);
}

admin_header('WhatsApp Handover');
?>

<div class="card">
    <h3 style="margin-top:0;">Handover Control</h3>
    <p class="muted">
        <?= h($conversation['push_name'] ?: 'Unknown') ?> • <?= h($conversation['phone']) ?>
    </p>

    <form method="post">
        <?= csrf_input() ?>

        <div class="form-grid">
            <div class="field">
                <label>Mode</label>
                <select name="mode">
                    <option value="human">handover to human</option>
                    <option value="ai">return to AI</option>
                </select>
            </div>

            <div class="field full">
                <label>Note</label>
                <textarea name="handover_note"><?= h($conversation['handover_note']) ?></textarea>
            </div>

            <div class="field full" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Save Handover Mode</button>
                <a href="/admin/whatsapp_conversation.php?id=<?= (int)$conversation['id'] ?>" class="btn-secondary">Back</a>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>