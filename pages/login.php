<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ActivityLog.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::start();
if (Auth::check()) {
    $dest = Auth::isOwner() ? '/owner-portal' : '/dashboard';
    header('Location: ' . APP_URL . $dest); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        header('Location: ' . APP_URL . ($result['redirect'] ?? '/dashboard'));
        exit;
    }
    $error = $result['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Roomee</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{background:#f1f5f9;}
.brand-panel{background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);min-height:100vh;}
</style>
</head>
<body>
<div class="row g-0" style="min-height:100vh;">
  <div class="col-lg-6 brand-panel d-none d-lg-flex flex-column align-items-center justify-content-center text-white p-5">
    <i class="bi bi-house-heart-fill" style="font-size:4rem;margin-bottom:1.5rem;opacity:.9;"></i>
    <h2 class="fw-bold mb-3">Roomee</h2>
    <p class="text-center opacity-75 mb-0" style="max-width:320px;line-height:1.7;">Malaysia's intelligent rental OS — manage STR, mid-term, sublet, and corporate leasing from one platform.</p>
    <div class="row g-3 mt-4 w-100 text-center" style="max-width:340px;">
      <?php foreach([['bi-buildings','Multi-Property'],['bi-shield-check','Compliance AI'],['bi-graph-up','ROI Engine']] as [$icon,$label]): ?>
      <div class="col-4"><div style="background:rgba(255,255,255,.12);border-radius:12px;padding:.75rem .5rem;">
        <i class="bi <?= $icon ?>" style="font-size:1.3rem;"></i>
        <div style="font-size:.7rem;margin-top:.25rem;opacity:.85;"><?= $label ?></div>
      </div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
    <div style="max-width:400px;width:100%;">
      <div class="text-center mb-4">
        <h4 class="fw-bold">Welcome back</h4>
        <p class="text-muted mb-0">Sign in to your Roomee account</p>
      </div>
      <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form action="<?= APP_URL ?>/login" method="POST" class="bg-white p-4 rounded-4 shadow-sm border">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
      </form>
      <p class="text-center mt-3 text-muted" style="font-size:.875rem;">
        No account? <a href="<?= APP_URL ?>/register" class="text-primary fw-semibold">Start free trial</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
