<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::startSession();

if (Auth::check()) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'sme_owner';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = Auth::register($name, $email, $password, $role);
        if ($result['success']) {
            // Auto-login
            Auth::login($email, $password);
            header('Location: ' . APP_URL . '/onboarding');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
  <!-- Left: Branding -->
  <div class="auth-brand">
    <div class="auth-brand-content">
      <div class="auth-logo"><i class="bi bi-leaf-fill"></i></div>
      <h1>AiServe ESG OS</h1>
      <p class="auth-tagline">Start your ESG journey today. Free setup, instant gap analysis.</p>
      <div class="auth-features">
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Set up in under 10 minutes</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Instant ESG readiness score</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Green financing eligibility check</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Consultants: manage unlimited clients</div>
      </div>
    </div>
  </div>

  <!-- Right: Register form -->
  <div class="auth-form-panel">
    <div class="auth-form-box">
      <h2>Create your account</h2>
      <p class="text-muted mb-4">Get your ESG dashboard in minutes</p>

      <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <!-- Role selection -->
        <div class="mb-3">
          <label class="form-label">I am a</label>
          <div class="role-selector role-selector-grid">
            <label class="role-option <?= in_array($_POST['role'] ?? '', ['sme_owner','']) ? 'selected' : '' ?>">
              <input type="radio" name="role" value="sme_owner" <?= ($_POST['role'] ?? 'sme_owner') === 'sme_owner' ? 'checked' : '' ?>>
              <i class="bi bi-building"></i>
              <div>
                <strong>SME Owner</strong>
                <small>Track your own company ESG</small>
              </div>
            </label>
            <label class="role-option <?= ($_POST['role'] ?? '') === 'principal' ? 'selected' : '' ?>">
              <input type="radio" name="role" value="principal" <?= ($_POST['role'] ?? '') === 'principal' ? 'checked' : '' ?>>
              <i class="bi bi-diagram-3-fill"></i>
              <div>
                <strong>Principal</strong>
                <small>Firm owner managing associates &amp; clients</small>
              </div>
            </label>
            <label class="role-option <?= ($_POST['role'] ?? '') === 'associate' ? 'selected' : '' ?>">
              <input type="radio" name="role" value="associate" <?= ($_POST['role'] ?? '') === 'associate' ? 'checked' : '' ?>>
              <i class="bi bi-briefcase-fill"></i>
              <div>
                <strong>Associate</strong>
                <small>Accounting / COSEC firm, client portfolio</small>
              </div>
            </label>
            <label class="role-option <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>">
              <input type="radio" name="role" value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'checked' : '' ?>>
              <i class="bi bi-person-workspace"></i>
              <div>
                <strong>Manager</strong>
                <small>Staff analyst handling assigned clients</small>
              </div>
            </label>
          </div>
        </div>

        <div class="mb-3">
          <label for="name" class="form-label">Full Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="name" name="name"
                   placeholder="Ahmad bin Ali" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Work Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="ahmad@company.com.my" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-6 mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="Min 8 characters" required minlength="8">
          </div>
          <div class="col-6 mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                   placeholder="Repeat password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
          <i class="bi bi-rocket-takeoff me-2"></i>Get Started
        </button>
      </form>

      <div class="text-center">
        <span class="text-muted">Already have an account?</span>
        <a href="<?= APP_URL ?>/login" class="ms-1 fw-semibold text-primary">Sign in</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Role selector highlight
document.querySelectorAll('.role-option input').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-option').forEach(el => el.classList.remove('selected'));
    this.closest('.role-option').classList.add('selected');
  });
});
</script>
</body>
</html>
