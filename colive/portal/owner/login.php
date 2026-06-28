<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

startSecureSession();

// Already logged in — go straight to dashboard
if (!empty($_SESSION['owner_id'])) {
    header('Location: ' . APP_URL . '/portal/owner/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $db    = getDB();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    $stmt = $db->prepare(
        'SELECT * FROM owners WHERE email = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$email]);
    $owner = $stmt->fetch();

    if (!$owner || empty($owner['portal_password'])) {
        flashSet('danger', 'Invalid email or portal access not enabled.');
        $error = 'Invalid email or portal access not enabled.';
    } elseif (!password_verify($pass, $owner['portal_password'])) {
        flashSet('danger', 'Invalid email or portal access not enabled.');
        $error = 'Invalid email or portal access not enabled.';
    } else {
        // Fetch company for brand color
        $stmtC = $db->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $stmtC->execute([(int)$owner['company_id']]);
        $loginCompany = $stmtC->fetch() ?: [];

        session_regenerate_id(true);
        $_SESSION['owner_id']         = (int)$owner['id'];
        $_SESSION['owner_company_id'] = (int)$owner['company_id'];
        $_SESSION['owner_name']       = $owner['name'];
        $_SESSION['owner_email']      = $owner['email'];

        header('Location: ' . APP_URL . '/portal/owner/dashboard.php');
        exit;
    }
}

// Use platform default brand color for the login page
$brand = BRAND_COLOR;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Sign In &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --brand: <?= e($brand) ?>; }
body {
  background: #f5f3ff;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  padding: 1rem;
}
.login-card {
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 8px 40px rgba(147,51,234,.12);
  overflow: hidden;
}
.login-header {
  background: var(--brand);
  padding: 2rem 2rem 1.6rem;
  text-align: center;
}
.login-logo-wrap {
  width: 56px; height: 56px;
  background: rgba(255,255,255,.2);
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  font-size: 1.6rem;
  color: #fff;
}
.login-app-name {
  font-weight: 800;
  font-size: 1.15rem;
  color: #fff;
  margin-bottom: .2rem;
}
.login-subtitle {
  font-size: .8rem;
  color: rgba(255,255,255,.72);
  letter-spacing: .02em;
}
.login-body {
  padding: 1.75rem 2rem 2rem;
}
.form-label {
  font-size: .82rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: .35rem;
}
.form-control {
  border-radius: 9px;
  border: 1.5px solid #e5e7eb;
  font-size: .9rem;
  padding: .6rem .85rem;
  transition: border-color .15s, box-shadow .15s;
}
.form-control:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px <?= e($brand) ?>33;
  outline: none;
}
.btn-login {
  background: var(--brand);
  border: none;
  color: #fff;
  font-weight: 700;
  font-size: .92rem;
  padding: .65rem;
  border-radius: 10px;
  width: 100%;
  transition: opacity .15s;
}
.btn-login:hover { opacity: .88; color: #fff; }
.login-footer {
  text-align: center;
  padding-bottom: 1.5rem;
  font-size: .78rem;
  color: #9ca3af;
}
.login-footer a { color: var(--brand); text-decoration: none; }
.login-footer a:hover { text-decoration: underline; }
.alert { border-radius: 9px; font-size: .84rem; }
.input-group-text {
  background: #f9fafb;
  border: 1.5px solid #e5e7eb;
  border-right: none;
  color: #9ca3af;
  border-radius: 9px 0 0 9px;
}
.input-group .form-control {
  border-left: none;
  border-radius: 0 9px 9px 0;
}
.input-group .form-control:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px <?= e($brand) ?>33;
}
.input-group:focus-within .input-group-text {
  border-color: var(--brand);
}
</style>
</head>
<body>

<div class="login-card">
  <!-- Header -->
  <div class="login-header">
    <div class="login-logo-wrap">
      <i class="bi bi-house-heart-fill"></i>
    </div>
    <div class="login-app-name">CoLive OS</div>
    <div class="login-subtitle">Owner Portal</div>
  </div>

  <!-- Body -->
  <div class="login-body">
    <h5 class="fw-bold mb-1" style="color:#1a1035;">Welcome back</h5>
    <p class="text-muted mb-4" style="font-size:.82rem;">Sign in to view your units and payouts.</p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            value="<?= e($_POST['email'] ?? '') ?>"
            autocomplete="email"
            required
            autofocus
            placeholder="you@example.com"
          >
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            autocomplete="current-password"
            required
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
          >
        </div>
      </div>

      <button type="submit" class="btn-login mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>
  </div>

  <div class="login-footer">
    <p class="mb-0">
      Trouble signing in? Contact your property manager.
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
