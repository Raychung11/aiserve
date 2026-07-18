<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';

if (admin_logged_in()) {
    redirect('/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $stmt = db()->prepare("SELECT * FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role'] = $user['role'];
        redirect('/admin/index.php');
    } else {
        $error = 'Invalid login credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Login | AiServe.io</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include __DIR__ . '/inc/public_header_css.php'; ?>
</head>
<body>
<div class="container" style="padding:80px 0;max-width:520px;">
    <div class="form-box">
        <div style="text-align:center;margin-bottom:20px;">
            <div class="brand" style="justify-content:center;">
                <span class="brand-mark">A</span>
                <span>AiServe.io Admin</span>
            </div>
            <div class="small" style="margin-top:8px;"><?= h(COMPANY_NAME) ?></div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="field" style="margin-top:14px;">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div style="margin-top:18px;">
                <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>