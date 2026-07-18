<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/permissions.php';

require_role(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'editor');

    if ($fullName !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare("
            INSERT INTO admin_users (full_name, email, password_hash, role, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$fullName, $email, $hash, $role]);
        redirect('/admin/admin_users.php');
    }
}

$stmt = db()->query("SELECT * FROM admin_users ORDER BY id DESC");
$rows = $stmt->fetchAll();

admin_header('Admin Users');
?>

<div class="card">
    <h3 style="margin-top:0;">Create Admin User</h3>
    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role">
                    <option value="editor">editor</option>
                    <option value="marketing">marketing</option>
                    <option value="super_admin">super_admin</option>
                </select>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Create User</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Admin User List</h3>
    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><?= h($r['full_name']) ?></td>
                    <td><?= h($r['email']) ?></td>
                    <td><span class="pill"><?= h($r['role']) ?></span></td>
                    <td><?= (int)$r['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                    <td><a href="/admin/admin_user_edit.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>