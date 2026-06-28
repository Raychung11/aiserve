<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/app/dashboard.php'); exit;
}

$error  = '';
$fields = ['company' => '', 'name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $company  = trim($_POST['company']  ?? '');
    $name     = trim($_POST['name']     ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone']    ?? '');
    $pass     = $_POST['password']         ?? '';
    $pass2    = $_POST['password_confirm'] ?? '';

    $fields = compact('company', 'name', 'email', 'phone');

    if (!$company || !$name || !$email || !$pass) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();

        // Check duplicate email
        $chk = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            try {
                $db->beginTransaction();

                // Create company
                $trialEnd = date('Y-m-d H:i:s', strtotime('+' . TRIAL_DAYS . ' days'));
                $stmt = $db->prepare(
                    'INSERT INTO companies (name, email, phone, status, trial_ends_at, created_at, updated_at)
                     VALUES (?,?,?,\'trial\',?,NOW(),NOW())'
                );
                $stmt->execute([$company, $email, $phone, $trialEnd]);
                $companyId = (int)$db->lastInsertId();

                // Create admin user
                $stmt2 = $db->prepare(
                    'INSERT INTO users (company_id, name, email, phone, password_hash, role, is_active, created_at, updated_at)
                     VALUES (?,?,?,?,?,\'admin\',1,NOW(),NOW())'
                );
                $stmt2->execute([
                    $companyId,
                    $name,
                    $email,
                    $phone,
                    password_hash($pass, PASSWORD_BCRYPT),
                ]);
                $userId = (int)$db->lastInsertId();

                $db->commit();

                // Auto-login
                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['company_id'] = $companyId;
                $_SESSION['role']       = 'admin';

                flashSet('success', 'Welcome to CoLive OS! Your ' . TRIAL_DAYS . '-day free trial has started.');
                header('Location: ' . APP_URL . '/app/dashboard.php'); exit;

            } catch (Throwable $e) {
                $db->rollBack();
                error_log('[CoLive register] ' . $e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
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
<title>Start Free Trial &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background:#f1f5f9; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; padding:1.5rem 0; }
.reg-wrap { width:100%; max-width:960px; display:flex; border-radius:20px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.12); }
.reg-left  { background:#0f172a; flex:0 0 340px; padding:3rem; display:flex; flex-direction:column; justify-content:center; }
.reg-right { background:#fff; flex:1; padding:3rem; display:flex; flex-direction:column; justify-content:center; }
.brand-name { font-weight:800; font-size:1.4rem; color:#fff; }
.perk-row { display:flex; align-items:center; gap:.7rem; margin-bottom:.75rem; color:#94a3b8; font-size:.85rem; }
.perk-dot { width:28px;height:28px;background:rgba(147,51,234,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.form-control:focus { border-color:<?= $brand ?>;box-shadow:0 0 0 3px <?= $brand ?>33; }
.btn-start { background:<?= $brand ?>;border:none;color:#fff;font-weight:700;padding:.65rem; }
.btn-start:hover { background:#7c3aed;color:#fff; }
.trial-badge { background:rgba(147,51,234,.12);color:<?= $brand ?>;font-size:.7rem;font-weight:700;
               padding:.25rem .75rem;border-radius:20px;letter-spacing:.05em;text-transform:uppercase; }
@media(max-width:640px){ .reg-left{display:none;} .reg-wrap{border-radius:12px;} }
</style>
</head>
<body>
<div class="reg-wrap">
  <div class="reg-left">
    <div class="d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-house-heart-fill" style="color:<?= $brand ?>;font-size:1.6rem;"></i>
      <span class="brand-name">CoLive OS</span>
    </div>
    <div class="mb-3"><span class="trial-badge">14-day free trial</span></div>
    <h3 class="text-white fw-bold mb-2" style="line-height:1.3;">Everything you need<br>to run co-living.</h3>
    <p style="color:#64748b;font-size:.875rem;margin-bottom:2rem;">No credit card required. Cancel any time.</p>
    <?php
    $perks = [
        ['bi-buildings',              'Unlimited buildings &amp; rooms'],
        ['bi-people-fill',            'Resident portal &amp; bookings'],
        ['bi-receipt-cutoff',         'Automated invoicing'],
        ['bi-tools',                  'Maintenance tracking'],
        ['bi-lightning-charge-fill',  'Smart lock &amp; utilities'],
        ['bi-graph-up-arrow',         'Owner payouts &amp; reports'],
    ];
    foreach ($perks as [$icon, $text]): ?>
    <div class="perk-row">
      <div class="perk-dot"><i class="bi <?= $icon ?>" style="color:<?= $brand ?>;font-size:.75rem;"></i></div>
      <?= $text ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="reg-right">
    <h4 class="fw-bold mb-1">Create your account</h4>
    <p class="text-muted mb-4" style="font-size:.875rem;">Start your <?= TRIAL_DAYS ?>-day free trial — no card needed</p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem;">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Company / Business Name <span class="text-danger">*</span></label>
        <input type="text" name="company" class="form-control" value="<?= e($fields['company']) ?>" placeholder="e.g. SLV Co-Living Sdn Bhd" required autofocus>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.875rem;">Your Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= e($fields['name']) ?>" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.875rem;">Phone</label>
          <input type="tel" name="phone" class="form-control" value="<?= e($fields['phone']) ?>" placeholder="01x-xxxxxxx">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="<?= e($fields['email']) ?>" required>
      </div>
      <div class="row g-3 mb-4">
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.875rem;">Password <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.875rem;">Confirm Password <span class="text-danger">*</span></label>
          <input type="password" name="password_confirm" class="form-control" required>
        </div>
      </div>
      <button type="submit" class="btn btn-start w-100">Start Free Trial &rarr;</button>
    </form>
    <p class="text-center text-muted mt-4 mb-0" style="font-size:.8rem;">
      Already have an account? <a href="<?= APP_URL ?>/app/login.php" style="color:<?= $brand ?>;">Sign in</a>
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
