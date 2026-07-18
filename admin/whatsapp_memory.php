<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/wa_memory_helper.php';

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

$memory = wa_memory_all($conversationId);

admin_header('WhatsApp Memory');
?>

<div class="card">
    <h3 style="margin-top:0;">Conversation Memory</h3>
    <p class="muted"><?= h($conversation['push_name'] ?: 'Unknown') ?> • <?= h($conversation['phone']) ?></p>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$memory): ?>
                <tr><td colspan="3">No memory saved yet.</td></tr>
            <?php else: ?>
                <?php foreach ($memory as $m): ?>
                    <tr>
                        <td><?= h($m['memory_key']) ?></td>
                        <td><?= h($m['memory_value']) ?></td>
                        <td><?= h($m['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($memory as $m): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Key</span><?= h($m['memory_key']) ?></div>
                    <div class="row"><span class="label">Value</span><?= h($m['memory_value']) ?></div>
                    <div class="row"><span class="label">Updated</span><?= h($m['updated_at']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>