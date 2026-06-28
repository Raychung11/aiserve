<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';
registerDebugShutdown();

startSecureSession();

// Already logged in — go to dashboard
if (!empty($_SESSION['resident_id'])) {
    header('Location: ' . APP_URL . '/portal/resident/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $db    = getDB();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $db->prepare(
            'SELECT * FROM residents WHERE email = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $res = $stmt->fetch();

        if (!$res) {
            $error = 'No active account found for that email address.';
        } elseif (empty($res['password_hash'])) {
            $error = 'Portal access not enabled for your account. Please contact your property manager.';
        } elseif (!password_verify($pass, $res['password_hash'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            // Valid login
            session_regenerate_id(true);
            $_SESSION['resident_id']         = $res['id'];
            $_SESSION['resident_company_id'] = $res['company_id'];
            $_SESSION['resident_name']       = $res['name'];
            $_SESSION['resident_email']      = $res['email'];
            header('Location: ' . APP_URL . '/portal/resident/dashboard.php');
            exit;
        }
    }
}

$brand = BRAND_COLOR;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Sign In &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --brand: <?= e($brand) ?>; }
*, *::before, *::after { box-sizing: border-box; }
body {
  background: #f1f5f9;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Segoe UI', system-ui, sans-serif;
  padding: 1.5rem;
}

.login-shell {
  width: 100%; max-width: 420px;
}

/* Logo / brand area above card */
.login-brand {
  text-align: center;
  margin-bottom: 1.5rem;
}
.login-brand-icon {
  width: 64px; height: 64px; border-radius: 18px;
  background: var(--brand);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.7rem; color: #fff;
  margin-bottom: .75rem;
  box-shadow: 0 8px 20px <?= e($brand) ?>55;
}
.login-brand-name {
  font-size: 1.1rem; font-weight: 800; color: #0f172a;
}
.login-brand-sub {
  font-size: .8rem; color: #64748b; margin-top: .15rem;
}

/* Card */
.login-card {
  background: #fff;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  padding: 2rem 1.75rem;
  box-shadow: 0 8px 32px rgba(0,0,0,.07);
}
.login-card h5 {
  font-size: 1.05rem; font-weight: 800; color: #0f172a;
  margin-bottom: .25rem;
}
.login-card .sub {
  color: #64748b; font-size: .82rem; margin-bottom: 1.5rem;
}

.form-label { font-size: .83rem; font-weight: 600; color: #374151; }
.form-control {
  border-radius: 9px; font-size: .9rem;
  border: 1.5px solid #e2e8f0;
  padding: .55rem .85rem;
}
.form-control:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px <?= e($brand) ?>33;
}

.btn-signin {
  background: var(--brand); border: none; color: #fff;
  font-weight: 700; font-size: .92rem;
  padding: .65rem; border-radius: 10px;
  transition: filter .15s;
}
.btn-signin:hover { filter: brightness(.9); color: #fff; }

.login-footer {
  text-align: center; margin-top: 1.25rem;
  font-size: .78rem; color: #94a3b8;
}
</style>
</head>
<body>
<div class="login-shell">

  <!-- Brand area -->
  <div class="login-brand">
    <div class="login-brand-icon">
      <i class="bi bi-house-heart-fill"></i>
    </div>
    <div class="login-brand-name">CoLive OS</div>
    <div class="login-brand-sub">Resident Portal</div>
  </div>

  <!-- Login card -->
  <div class="login-card">
    <h5>Welcome back</h5>
    <p class="sub">Sign in with your registered email address.</p>

    <?php if ($error !== ''): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" style="font-size:.84rem;">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
      <span><?= e($error) ?></span>
    </div>
    <?php endif; ?>

    <?= flashHtml() ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>

      <div class="mb-3">
        <label class="form-label" for="loginEmail">Email address</label>
        <input
          type="email"
          id="loginEmail"
          name="email"
          class="form-control"
          value="<?= e($_POST['email'] ?? '') ?>"
          placeholder="you@example.com"
          required
          autofocus
          autocomplete="email"
        >
      </div>

      <div class="mb-4">
        <label class="form-label" for="loginPassword">Password</label>
        <div class="position-relative">
          <input
            type="password"
            id="loginPassword"
            name="password"
            class="form-control pe-5"
            required
            autocomplete="current-password"
          >
          <button
            type="button"
            id="togglePw"
            class="btn border-0 position-absolute top-50 end-0 translate-middle-y me-1 p-1 text-muted"
            tabindex="-1"
            aria-label="Show password"
            style="background:none;"
          >
            <i class="bi bi-eye" id="togglePwIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-signin w-100">
        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
      </button>
    </form>
  </div>

  <p class="login-footer">
    Having trouble? Contact your property manager for assistance.
  </p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var btn  = document.getElementById('togglePw');
  var inp  = document.getElementById('loginPassword');
  var icon = document.getElementById('togglePwIcon');
  if (btn && inp) {
    btn.addEventListener('click', function () {
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }
})();
</script>
</body>
</html>
