<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/evolution_sender.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('/admin/whatsapp_inbox.php');
}

$stmt = db()->prepare("
    SELECT
        c.*,
        wc.push_name,
        wc.phone,
        wc.profile_name
    FROM wa_conversations c
    LEFT JOIN wa_contacts wc ON wc.id = c.wa_contact_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$conversation = $stmt->fetch();

if (!$conversation) {
    redirect('/admin/whatsapp_inbox.php');
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['update_conversation'])) {
        $status = trim($_POST['conversation_status'] ?? 'open');
        $state = trim($_POST['current_state'] ?? 'new');
        $aiEnabled = isset($_POST['ai_enabled']) ? 1 : 0;
        $handoverNote = trim($_POST['handover_note'] ?? '');
        $priority = trim($_POST['priority_level'] ?? 'normal');
        $tags = trim($_POST['tags'] ?? '');

        $allowedStatus = ['open', 'pending_human', 'closed'];
        $allowedPriority = ['low', 'normal', 'high'];

        if (!in_array($status, $allowedStatus, true)) $status = 'open';
        if (!in_array($priority, $allowedPriority, true)) $priority = 'normal';

        $stmt = db()->prepare("
            UPDATE wa_conversations
            SET conversation_status = ?, current_state = ?, ai_enabled = ?, handover_note = ?, priority_level = ?, tags = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $state, $aiEnabled, $handoverNote, $priority, $tags, $id]);

        redirect('/admin/whatsapp_conversation.php?id=' . $id);
    }

    if (isset($_POST['send_text'])) {
        $messageText = trim($_POST['reply_text'] ?? '');

        if ($messageText !== '') {
            $instanceName = $conversation['instance_name'] ?: get_setting('evolution_default_instance', 'main');
            $number = preg_replace('/[^0-9]/', '', (string)$conversation['phone']);

            $response = evolution_send_text($instanceName, $number, $messageText);

            wa_log_outbound(
                $instanceName,
                (int)$conversation['id'],
                $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
                (string)$conversation['remote_jid'],
                'send_text',
                ['number' => $number, 'text' => $messageText],
                $response,
                $response['ok'] ? 'success' : 'failed',
                $_SESSION['admin_id'] ?? null
            );

            $outboundMessageId = 'manual-text-' . time() . '-' . bin2hex(random_bytes(4));
            wa_store_outbound_message(
                $instanceName,
                (int)$conversation['id'],
                $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
                (string)$conversation['remote_jid'],
                'manual_send_text',
                $outboundMessageId,
                'conversation',
                $messageText
            );

            $flash = $response['ok'] ? 'Text message sent.' : 'Failed to send text message.';
        }
    }
}

$msgStmt = db()->prepare("
    SELECT *
    FROM wa_messages
    WHERE conversation_id = ?
    ORDER BY id ASC
    LIMIT 500
");
$msgStmt->execute([$id]);
$messages = $msgStmt->fetchAll();

admin_header('WhatsApp Conversation');
?>

<div class="card">
    <h3 style="margin-top:0;">
        <?= h($conversation['push_name'] ?: $conversation['profile_name'] ?: 'Unknown Contact') ?>
    </h3>
    <p class="muted" style="margin-top:0;">
        <?= h($conversation['phone']) ?> • <?= h($conversation['remote_jid']) ?> • Instance: <?= h($conversation['instance_name']) ?>
    </p>

    <?php if ($flash !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($flash) ?></div>
    <?php endif; ?>

    <form method="post" style="margin-top:14px;">
        <?= csrf_input() ?>
        <input type="hidden" name="update_conversation" value="1">

        <div class="form-grid">
            <div class="field">
                <label>Conversation Status</label>
                <select name="conversation_status">
                    <option value="open" <?= $conversation['conversation_status']==='open' ? 'selected' : '' ?>>open</option>
                    <option value="pending_human" <?= $conversation['conversation_status']==='pending_human' ? 'selected' : '' ?>>pending_human</option>
                    <option value="closed" <?= $conversation['conversation_status']==='closed' ? 'selected' : '' ?>>closed</option>
                </select>
            </div>

            <div class="field">
                <label>Current State</label>
                <input type="text" name="current_state" value="<?= h($conversation['current_state']) ?>">
            </div>

            <div class="field">
                <label>Priority</label>
                <select name="priority_level">
                    <option value="low" <?= $conversation['priority_level']==='low' ? 'selected' : '' ?>>low</option>
                    <option value="normal" <?= $conversation['priority_level']==='normal' ? 'selected' : '' ?>>normal</option>
                    <option value="high" <?= $conversation['priority_level']==='high' ? 'selected' : '' ?>>high</option>
                </select>
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="ai_enabled" value="1" <?= (int)$conversation['ai_enabled'] === 1 ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    AI enabled
                </label>
            </div>

            <div class="field full">
                <label>Tags</label>
                <input type="text" name="tags" value="<?= h($conversation['tags']) ?>" placeholder="lead, vip, urgent">
            </div>

            <div class="field full">
                <label>Handover Note</label>
                <textarea name="handover_note"><?= h($conversation['handover_note']) ?></textarea>
            </div>

            <div class="field full" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Update Conversation</button>
                <a href="/admin/whatsapp_send_media.php?id=<?= (int)$conversation['id'] ?>" class="btn-secondary">Send Media</a>
                <a href="/admin/whatsapp_handover.php?id=<?= (int)$conversation['id'] ?>" class="btn-secondary">Handover</a>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Manual Reply</h3>

    <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="send_text" value="1">

        <div class="form-grid">
            <div class="field full">
                <label>Reply Text</label>
                <textarea name="reply_text" placeholder="Type your WhatsApp reply here..." required></textarea>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Send Text Reply</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Message History</h3>

    <?php if (!$messages): ?>
        <p class="muted">No messages found.</p>
    <?php else: ?>
        <div style="display:grid;gap:12px;">
            <?php foreach ($messages as $m): ?>
                <div style="
                    padding:14px;
                    border-radius:16px;
                    border:1px solid #e8defd;
                    background: <?= $m['direction'] === 'inbound' ? '#faf8ff' : '#f3f0ff' ?>;
                ">
                    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                        <strong><?= h($m['direction']) ?> • <?= h($m['message_type']) ?></strong>
                        <span class="muted"><?= h($m['message_time']) ?></span>
                    </div>

                    <?php if (!empty($m['message_text'])): ?>
                        <div style="white-space:pre-wrap;margin-top:8px;"><?= h($m['message_text']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($m['media_url'])): ?>
                        <div style="margin-top:8px;">
                            <a href="<?= h($m['media_url']) ?>" target="_blank">Open media</a>
                        </div>
                    <?php endif; ?>

                    <div class="muted" style="margin-top:8px;font-size:12px;">
                        Event: <?= h($m['event_type']) ?> | Message ID: <?= h($m['message_id']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>