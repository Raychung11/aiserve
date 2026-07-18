<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/permissions.php';

require_role(['super_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('/admin/admin_users.php');

$stmt = db()->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) redirect('/admin/admin_users.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'editor');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = (string)($_POST['password'] ?? '');

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare("
            UPDATE admin_users
            SET full_name = ?, email = ?, role = ?, is_active = ?, password_hash = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $email, $role, $isActive, $hash, $id]);
    } else {
        $stmt = db()->prepare("
            UPDATE admin_users
            SET full_name = ?, email = ?, role = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $email, $role, $isActive, $id]);
    }

    redirect('/admin/admin_user_edit.php?id=' . $id);
}

admin_header('Edit Admin User');
?>

<div class="card">
    <h3 style="margin-top:0;">Edit User #<?= (int)$row['id'] ?></h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= h($row['full_name']) ?>" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($row['email']) ?>" required>
            </div>
            <div class="field">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password">
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role">
                    <option value="editor" <?= $row['role']==='editor' ? 'selected' : '' ?>>editor</option>
                    <option value="marketing" <?= $row['role']==='marketing' ? 'selected' : '' ?>>marketing</option>
                    <option value="super_admin" <?= $row['role']==='super_admin' ? 'selected' : '' ?>>super_admin</option>
                </select>
            </div>
            <div class="field full">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_active" value="1" <?= (int)$row['is_active'] === 1 ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    User is active
                </label>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Update User</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>