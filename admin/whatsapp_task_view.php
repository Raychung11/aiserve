<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/wa_sla_helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('/admin/whatsapp_tasks.php');

$stmt = db()->prepare("
    SELECT t.*, wc.push_name, wc.phone
    FROM wa_sales_tasks t
    INNER JOIN wa_contacts wc ON wc.id = t.wa_contact_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) redirect('/admin/whatsapp_tasks.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = trim($_POST['task_status'] ?? 'open');
    $priority = trim($_POST['priority_level'] ?? 'normal');
    $description = trim($_POST['task_description'] ?? '');
    $dueAt = trim($_POST['due_at'] ?? '');

    $allowedStatus = ['open','in_progress','waiting_customer','completed','cancelled'];
    $allowedPriority = ['low','normal','high'];

    if (!in_array($status, $allowedStatus, true)) $status = 'open';
    if (!in_array($priority, $allowedPriority, true)) $priority = 'normal';

    $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;

    $upd = db()->prepare("
        UPDATE wa_sales_tasks
        SET task_status = ?, priority_level = ?, task_description = ?, due_at = ?, completed_at = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$status, $priority, $description, $dueAt !== '' ? $dueAt : null, $completedAt, $id]);

    if ($status === 'completed') {
        wa_sla_close((int)$row['conversation_id'], (int)$row['wa_contact_id'], date('Y-m-d H:i:s'));
    }

    redirect('/admin/whatsapp_task_view.php?id=' . $id);
}

admin_header('WhatsApp Task Detail');
?>

<div class="card">
    <h3 style="margin-top:0;"><?= h($row['task_title']) ?></h3>
    <p class="muted"><?= h($row['push_name']) ?> • <?= h($row['phone']) ?></p>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Status</label>
                <select name="task_status">
                    <option value="open" <?= $row['task_status']==='open' ? 'selected' : '' ?>>open</option>
                    <option value="in_progress" <?= $row['task_status']==='in_progress' ? 'selected' : '' ?>>in_progress</option>
                    <option value="waiting_customer" <?= $row['task_status']==='waiting_customer' ? 'selected' : '' ?>>waiting_customer</option>
                    <option value="completed" <?= $row['task_status']==='completed' ? 'selected' : '' ?>>completed</option>
                    <option value="cancelled" <?= $row['task_status']==='cancelled' ? 'selected' : '' ?>>cancelled</option>
                </select>
            </div>

            <div class="field">
                <label>Priority</label>
                <select name="priority_level">
                    <option value="low" <?= $row['priority_level']==='low' ? 'selected' : '' ?>>low</option>
                    <option value="normal" <?= $row['priority_level']==='normal' ? 'selected' : '' ?>>normal</option>
                    <option value="high" <?= $row['priority_level']==='high' ? 'selected' : '' ?>>high</option>
                </select>
            </div>

            <div class="field">
                <label>Due At</label>
                <input type="datetime-local" name="due_at" value="<?= !empty($row['due_at']) ? date('Y-m-d\TH:i', strtotime($row['due_at'])) : '' ?>">
            </div>

            <div class="field full">
                <label>Description</label>
                <textarea name="task_description"><?= h($row['task_description']) ?></textarea>
            </div>

            <div class="field full" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Update Task</button>
                <a href="/admin/whatsapp_conversation.php?id=<?= (int)$row['conversation_id'] ?>" class="btn-secondary">Open Conversation</a>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>