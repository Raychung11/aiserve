<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ActivityLog.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::start();
if (Auth::check()) { header('Location: ' . APP_URL . '/dashboard'); exit; }

$errors = [];
$old    = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    if (empty($_POST['company_name'])) $errors[] = 'Company name is required.';
    if (empty($_POST['name']))         $errors[] = 'Your name is required.';
    if (!filter_var($_POST['email']??'', FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($_POST['password']??'') < 8) $errors[] = 'Password must be at least 8 characters.';
    if (($_POST['password']??'') !== ($_POST['password_confirm']??'')) $errors[] = 'Passwords do not match.';
    if (empty($_POST['phone'])) $errors[] = 'Phone number is required.';

    if (!$errors) {
        $result = Auth::register($_POST);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        $errors[] = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — STRHub AI</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>body{background:#f1f5f9;}</style>
</head>
<body>
<div class="d-flex align-items-center justify-content-center py-5 px-3" style="min-height:100vh;">
  <div style="max-width:520px;width:100%;">
    <div class="text-center mb-4">
      <a href="<?= APP_URL ?>/login" class="text-muted text-decoration-none" style="font-size:.875rem;"><i class="bi bi-arrow-left"></i> Back to login</a>
      <h4 class="fw-bold mt-3">Start your free trial</h4>
      <p class="text-muted mb-0">14 days free · No credit card required</p>
    </div>
    <?php if($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form action="<?= APP_URL ?>/register" method="POST" class="bg-white p-4 rounded-4 shadow-sm border">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <div class="mb-3">
        <label class="form-label fw-semibold">Company / Agency Name</label>
        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($old['company_name']??'') ?>" placeholder="e.g. SLV Group" required>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Your Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($old['name']??'') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($old['phone']??'') ?>" placeholder="+60 12-345 6789" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']??'') ?>" required>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Confirm Password</label>
          <input type="password" name="password_confirm" class="form-control" required>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Plan</label>
        <?php foreach(PLAN_LIMITS as $key=>$p): ?>
        <label class="d-flex align-items-center gap-3 border rounded-3 px-3 py-2 mb-2" style="cursor:pointer;">
          <input type="radio" name="plan" value="<?= $key ?>" <?= ($old['plan']??'starter')===$key?'checked':'' ?>>
          <div>
            <div class="fw-semibold" style="font-size:.875rem;"><?= ucfirst($key) ?></div>
            <div class="text-muted" style="font-size:.75rem;"><?= $p['properties'] === 9999 ? 'Unlimited' : $p['properties'] ?> properties · RM <?= number_format($p['price_monthly']) ?>/mo</div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2">Create Account &amp; Start Trial</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
