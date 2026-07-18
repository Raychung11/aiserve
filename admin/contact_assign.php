<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('/admin/contacts.php');
}

$stmt = db()->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$contact = $stmt->fetch();

if (!$contact) {
    redirect('/admin/contacts.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $assignedId = (int)($_POST['assigned_admin_id'] ?? 0);
    if ($assignedId <= 0) {
        $assignedId = null;
    }

    $stmt = db()->prepare("UPDATE contacts SET assigned_admin_id = ? WHERE id = ?");
    $stmt->execute([$assignedId, $id]);

    redirect('/admin/contact_assign.php?id=' . $id);
}

$admins = db()->query("SELECT id, full_name, email, role FROM admin_users WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();

admin_header('Assign Contact');
?>

<div class="card">
    <h3 style="margin-top:0;">Assign Lead</h3>
    <p><strong><?= h($contact['full_name']) ?></strong> • <?= h($contact['company_name']) ?></p>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Assign To</label>
                <select name="assigned_admin_id">
                    <option value="0">Unassigned</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= (int)$admin['id'] ?>" <?= (int)$contact['assigned_admin_id'] === (int)$admin['id'] ? 'selected' : '' ?>>
                            <?= h($admin['full_name']) ?> (<?= h($admin['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="btn">Save Assignment</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>