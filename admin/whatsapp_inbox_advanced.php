<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$department = trim($_GET['department'] ?? '');
$assignee = (int)($_GET['assignee'] ?? 0);
$status = trim($_GET['status'] ?? '');
$branch = trim($_GET['branch'] ?? '');

$sql = "
    SELECT
        c.id AS conversation_id,
        c.instance_name,
        c.remote_jid,
        c.conversation_status,
        c.current_state,
        c.assigned_department,
        c.branch_name,
        c.assigned_admin_id,
        c.last_message_at,
        wc.push_name,
        wc.phone,
        a.full_name AS assigned_name
    FROM wa_conversations c
    LEFT JOIN wa_contacts wc ON wc.id = c.wa_contact_id
    LEFT JOIN admin_users a ON a.id = c.assigned_admin_id
    WHERE 1=1
";
$params = [];

if ($department !== '') {
    $sql .= " AND c.assigned_department = ?";
    $params[] = $department;
}
if ($assignee > 0) {
    $sql .= " AND c.assigned_admin_id = ?";
    $params[] = $assignee;
}
if ($status !== '') {
    $sql .= " AND c.conversation_status = ?";
    $params[] = $status;
}
if ($branch !== '') {
    $sql .= " AND c.branch_name = ?";
    $params[] = $branch;
}

$sql .= " ORDER BY c.last_message_at DESC LIMIT 200";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$admins = db()->query("SELECT id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();

admin_header('Advanced WhatsApp Inbox');
?>

<div class="card">
    <h3 style="margin-top:0;">Advanced Filters</h3>

    <form method="get">
        <div class="form-grid">
            <div class="field">
                <label>Department</label>
                <input type="text" name="department" value="<?= h($department) ?>">
            </div>
            <div class="field">
                <label>Assignee</label>
                <select name="assignee">
                    <option value="0">All</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= (int)$admin['id'] ?>" <?= $assignee === (int)$admin['id'] ? 'selected' : '' ?>>
                            <?= h($admin['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Status</label>
                <input type="text" name="status" value="<?= h($status) ?>">
            </div>
            <div class="field">
                <label>Branch</label>
                <input type="text" name="branch" value="<?= h($branch) ?>">
            </div>
            <div class="field full">
                <button type="submit" class="btn">Apply Filters</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Filtered Conversations</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Branch</th>
                    <th>Assignee</th>
                    <th>Status</th>
                    <th>State</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">No conversations found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h($r['assigned_department']) ?></td>
                        <td><?= h($r['branch_name']) ?></td>
                        <td><?= h($r['assigned_name']) ?></td>
                        <td><?= h($r['conversation_status']) ?></td>
                        <td><?= h($r['current_state']) ?></td>
                        <td><a href="/admin/whatsapp_conversation.php?id=<?= (int)$r['conversation_id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>