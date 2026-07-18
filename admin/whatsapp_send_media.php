<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/evolution_sender.php';

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

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $sendType = trim($_POST['send_type'] ?? 'image');
    $mediaUrl = trim($_POST['media_url'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $fileName = trim($_POST['file_name'] ?? 'document.pdf');

    $instanceName = $conversation['instance_name'] ?: get_setting('evolution_default_instance', 'main');
    $number = preg_replace('/[^0-9]/', '', (string)$conversation['phone']);

    if ($mediaUrl !== '') {
        if ($sendType === 'document') {
            $response = evolution_send_document($instanceName, $number, $mediaUrl, $fileName, $caption);
            $actionType = 'send_document';
            $messageType = 'documentMessage';
            $mime = 'application/pdf';
        } else {
            $response = evolution_send_image($instanceName, $number, $mediaUrl, $caption);
            $actionType = 'send_image';
            $messageType = 'imageMessage';
            $mime = 'image/jpeg';
        }

        wa_log_outbound(
            $instanceName,
            (int)$conversation['id'],
            $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
            (string)$conversation['remote_jid'],
            $actionType,
            [
                'number' => $number,
                'media_url' => $mediaUrl,
                'caption' => $caption,
                'file_name' => $fileName
            ],
            $response,
            $response['ok'] ? 'success' : 'failed',
            $_SESSION['admin_id'] ?? null
        );

        $outboundMessageId = 'manual-media-' . time() . '-' . bin2hex(random_bytes(4));
        wa_store_outbound_message(
            $instanceName,
            (int)$conversation['id'],
            $conversation['wa_contact_id'] ? (int)$conversation['wa_contact_id'] : null,
            (string)$conversation['remote_jid'],
            'manual_send_media',
            $outboundMessageId,
            $messageType,
            $caption !== '' ? $caption : $fileName,
            $mediaUrl,
            $mime
        );

        $flash = $response['ok'] ? 'Media sent successfully.' : 'Failed to send media.';
    }
}

admin_header('Send WhatsApp Media');
?>

<div class="card">
    <h3 style="margin-top:0;">Send Media</h3>
    <p class="muted">
        <?= h($conversation['push_name'] ?: 'Unknown') ?> • <?= h($conversation['phone']) ?>
    </p>

    <?php if ($flash !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($flash) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>

        <div class="form-grid">
            <div class="field">
                <label>Send Type</label>
                <select name="send_type">
                    <option value="image">image</option>
                    <option value="document">document</option>
                </select>
            </div>

            <div class="field">
                <label>File Name (for document)</label>
                <input type="text" name="file_name" value="document.pdf">
            </div>

            <div class="field full">
                <label>Media URL</label>
                <input type="text" name="media_url" placeholder="https://yourdomain.com/uploads/file.pdf" required>
            </div>

            <div class="field full">
                <label>Caption</label>
                <textarea name="caption" placeholder="Optional caption"></textarea>
            </div>

            <div class="field full" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Send Media</button>
                <a href="/admin/whatsapp_conversation.php?id=<?= (int)$conversation['id'] ?>" class="btn-secondary">Back</a>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>