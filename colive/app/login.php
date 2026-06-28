<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/app/dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $db    = getDB();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    $stmt = $db->prepare(
        'SELECT u.*, c.status AS company_status, c.brand_color, c.name AS company_name
         FROM users u
         JOIN companies c ON c.id = u.company_id
         WHERE u.email=? AND u.is_active=1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        if (!in_array($user['company_status'], ['active','trial'], true)) {
            $error = 'Your subscription is inactive. Contact your administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['company_id']   = $user['company_id'];
            $_SESSION['role']         = $user['role'];
            $db->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
            header('Location: ' . APP_URL . '/app/dashboard.php'); exit;
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
$brand = BRAND_COLOR;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background:#f1f5f9; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
.login-wrap { width:100%; max-width:900px; min-height:500px; display:flex; border-radius:20px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.12); }
.login-left { background:#0f172a; flex:1; padding:3rem; display:flex; flex-direction:column; justify-content:center; }
.login-right { background:#fff; flex:1; padding:3rem; display:flex; flex-direction:column; justify-content:center; }
.brand-name { font-weight:800; font-size:1.4rem; color:#fff; }
.feature-row { display:flex; align-items:center; gap:.7rem; margin-bottom:.75rem; color:#94a3b8; font-size:.85rem; }
.feature-dot { width:28px;height:28px;background:rgba(147,51,234,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.form-control:focus { border-color:<?= $brand ?>;box-shadow:0 0 0 3px <?= $brand ?>33; }
.btn-signin { background:<?= $brand ?>;border:none;color:#fff;font-weight:700;padding:.65rem; }
.btn-signin:hover { background:#7c3aed;color:#fff; }
@media(max-width:640px){ .login-left{display:none;} .login-wrap{border-radius:12px;} }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-left">
    <div class="d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-house-heart-fill" style="color:<?= $brand ?>;font-size:1.6rem;"></i>
      <span class="brand-name">CoLive OS</span>
    </div>
    <h3 class="text-white fw-bold mb-2" style="line-height:1.3;">Run your co-living<br>operation smarter.</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:2rem;">Complete management for co-living operators &mdash; rooms, billing, residents and owners in one place.</p>
    <?php
    $features = [
        ['bi-buildings',          'Buildings, units, rooms &amp; beds'],
        ['bi-people-fill',        'Resident management &amp; bookings'],
        ['bi-receipt-cutoff',     'Automated billing &amp; invoicing'],
        ['bi-tools',              'Maintenance ticket tracking'],
        ['bi-lightning-charge-fill','Smart lock &amp; utility meter IOT'],
    ];
    foreach ($features as [$icon, $text]):
    ?>
    <div class="feature-row">
      <div class="feature-dot"><i class="bi <?= $icon ?>" style="color:<?= $brand ?>;font-size:.75rem;"></i></div>
      <?= $text ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="login-right">
    <h4 class="fw-bold mb-1">Welcome back</h4>
    <p class="text-muted mb-4" style="font-size:.875rem;">Sign in to your operator account</p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem;">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-signin w-100">Sign In</button>
    </form>
    <p class="text-center text-muted mt-4 mb-0" style="font-size:.8rem;">
      New to CoLive OS? <a href="<?= APP_URL ?>/register.php" style="color:<?= $brand ?>;">Start free trial</a>
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
