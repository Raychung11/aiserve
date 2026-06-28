<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

startSecureSession();
if (!empty($_SESSION['platform_admin_id'])) {
    header('Location: ' . APP_URL . '/platform/dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $db    = getDB();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    $stmt = $db->prepare('SELECT * FROM platform_admins WHERE email=? AND is_active=1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['platform_admin_id']   = $admin['id'];
        $_SESSION['platform_admin_name'] = $admin['name'];
        $db->prepare('UPDATE platform_admins SET last_login_at=NOW() WHERE id=?')
           ->execute([$admin['id']]);
        header('Location: ' . APP_URL . '/platform/dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Platform Admin &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
.login-card { background:#1e293b; border:1px solid #334155; border-radius:16px; padding:2.5rem; width:100%; max-width:420px; }
.brand { color:#a78bfa; font-size:1.5rem; font-weight:800; }
.badge-platform { background:#7c3aed22; color:#a78bfa; font-size:.7rem; padding:.25rem .6rem; border-radius:20px; font-weight:600; }
.form-control { background:#0f172a; border-color:#334155; color:#e2e8f0; }
.form-control:focus { background:#0f172a; border-color:#9333ea; color:#e2e8f0; box-shadow:0 0 0 3px #9333ea33; }
.form-label { color:#94a3b8; font-size:.85rem; font-weight:600; }
.btn-platform { background:#9333ea; border:none; font-weight:700; padding:.6rem; }
.btn-platform:hover { background:#7c3aed; }
</style>
</head>
<body>
<div class="login-card">
  <div class="text-center mb-4">
    <div class="brand mb-1"><i class="bi bi-shield-lock-fill me-2"></i>CoLive OS</div>
    <span class="badge-platform">Platform Administration</span>
    <p class="mt-3 mb-0" style="color:#64748b;font-size:.82rem;">SLV Group Sdn. Bhd. &mdash; Internal Portal</p>
  </div>
  <?php if ($error): ?>
  <div class="alert" style="background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d;font-size:.85rem;padding:.6rem 1rem;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
  </div>
  <?php endif; ?>
  <form method="POST">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-platform btn-primary w-100">Sign in to Platform</button>
  </form>
</div>
</body>
</html>
