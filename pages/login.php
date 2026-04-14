<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::startSession();

// Already logged in
if (Auth::check()) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result   = Auth::login($email, $password);
    if ($result['success']) {
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
  <!-- Left: Branding -->
  <div class="auth-brand">
    <div class="auth-brand-content">
      <div class="auth-logo">
        <i class="bi bi-leaf-fill"></i>
      </div>
      <h1>AiServe ESG OS</h1>
      <p class="auth-tagline">Malaysia's AI-powered ESG Operating System for SMEs, Consultants, and Pre-IPO Companies.</p>
      <div class="auth-features">
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Bursa SEDG &amp; GRI Compliance</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Automated Gap Analysis</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Green Financing Eligibility</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Carbon Tax Readiness</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Audit-Ready Reports</div>
      </div>
      <p class="auth-cta-text">Automate compliance, secure supply chains, and unlock financing — at 1/10th consultant cost.</p>
    </div>
  </div>

  <!-- Right: Login form -->
  <div class="auth-form-panel">
    <div class="auth-form-box">
      <h2>Welcome back</h2>
      <p class="text-muted mb-4">Sign in to your ESG dashboard</p>

      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="you@company.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="••••••••" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>

      <div class="text-center">
        <span class="text-muted">Don't have an account?</span>
        <a href="<?= APP_URL ?>/register" class="ms-1 fw-semibold text-primary">Register now</a>
      </div>

      <!-- Demo credentials hint -->
      <div class="demo-hint mt-4">
        <small class="text-muted">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Demo:</strong> Register as Consultant to manage multiple client companies.
        </small>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
  const pw = document.getElementById('password');
  const icon = this.querySelector('i');
  if (pw.type === 'password') { pw.type = 'text'; icon.className = 'bi bi-eye-slash'; }
  else { pw.type = 'password'; icon.className = 'bi bi-eye'; }
});
</script>
</body>
</html>
