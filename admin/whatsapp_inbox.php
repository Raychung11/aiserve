<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT
        c.id AS conversation_id,
        c.instance_name,
        c.remote_jid,
        c.conversation_status,
        c.current_state,
        c.ai_enabled,
        c.priority_level,
        c.last_message_at,
        wc.push_name,
        wc.phone,
        wc.profile_name,
        (
            SELECT wm.message_text
            FROM wa_messages wm
            WHERE wm.conversation_id = c.id
            ORDER BY wm.id DESC
            LIMIT 1
        ) AS last_message_text
    FROM wa_conversations c
    LEFT JOIN wa_contacts wc ON wc.id = c.wa_contact_id
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= " AND (
        wc.push_name LIKE ?
        OR wc.phone LIKE ?
        OR c.remote_jid LIKE ?
        OR c.current_state LIKE ?
    )";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$sql .= " ORDER BY c.last_message_at DESC, c.id DESC LIMIT 200";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_header('WhatsApp Inbox');
?>

<div class="card">
    <h3 style="margin-top:0;">WhatsApp Inbox</h3>

    <form method="get" style="margin-bottom:16px;">
        <div class="form-grid">
            <div class="field">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name, phone, jid, state">
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="btn">Search</button>
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table class="desktop-table">
           <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Instance</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>AI</th>
                    <th>Priority</th>
                    <th>Last Message</th>
                    <th>Last Time</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="10">No conversations found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name'] ?: $r['profile_name'] ?: 'Unknown') ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h($r['instance_name']) ?></td>
                        <td><span class="pill"><?= h($r['current_state']) ?></span></td>
                        <td><span class="pill"><?= h($r['conversation_status']) ?></span></td>
                        <td><?= (int)$r['ai_enabled'] === 1 ? 'On' : 'Off' ?></td>
                        <td><?= h($r['priority_level']) ?></td>
                        <td><?= h(mb_strimwidth((string)$r['last_message_text'], 0, 80, '...')) ?></td>
                        <td><?= h($r['last_message_at']) ?></td>
                        <td><a href="/admin/whatsapp_conversation.php?id=<?= (int)$r['conversation_id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Name</span><?= h($r['push_name'] ?: $r['profile_name'] ?: 'Unknown') ?></div>
                    <div class="row"><span class="label">Phone</span><?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Instance</span><?= h($r['instance_name']) ?></div>
                    <div class="row"><span class="label">State</span><?= h($r['current_state']) ?></div>
                    <div class="row"><span class="label">Status</span><?= h($r['conversation_status']) ?></div>
                    <div class="row"><span class="label">Last Message</span><?= h(mb_strimwidth((string)$r['last_message_text'], 0, 80, '...')) ?></div>
                    <div class="row"><span class="label">Last Time</span><?= h($r['last_message_at']) ?></div>
                    <div class="row"><a href="/admin/whatsapp_conversation.php?id=<?= (int)$r['conversation_id'] ?>" class="btn-secondary">Open</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>