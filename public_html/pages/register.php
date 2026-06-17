<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

if (auth_check()) {
    redirect('member/dashboard');
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        $result = register_user($_POST);
        if ($result['success']) {
            flash('success', 'Account created! Please sign in.');
            redirect('login');
        } else {
            $error = $result['error'];
        }
    }
}

$page_title = t('register_title');
$body_class = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card" style="max-width:500px;">
    <div class="auth-logo">
      <div class="brand-icon" style="width:56px;height:56px;font-size:1.6rem;margin:0 auto .75rem;">管</div>
      <h4 class="auth-title"><?= t('register_title') ?></h4>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label"><?= t('full_name_label') ?></label>
        <input type="text" name="full_name" class="form-control"
               value="<?= h($_POST['full_name'] ?? '') ?>" placeholder="Your Full Name" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><?= t('email_label') ?></label>
        <input type="email" name="email" class="form-control"
               value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><?= t('phone_label') ?></label>
        <input type="tel" name="phone" class="form-control"
               value="<?= h($_POST['phone'] ?? '') ?>" placeholder="+60 12-XXX XXXX">
      </div>
      <div class="mb-3">
        <label class="form-label"><?= t('password_label') ?></label>
        <input type="password" name="password" class="form-control"
               placeholder="Minimum 8 characters" required minlength="8">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirm" class="form-control"
               placeholder="Repeat password" required minlength="8">
      </div>
      <div class="mb-4">
        <label class="form-label"><?= t('referral_label') ?></label>
        <input type="text" name="referral_code" class="form-control"
               value="<?= h($_GET['ref'] ?? $_POST['referral_code'] ?? '') ?>"
               placeholder="e.g. ABC123" style="text-transform:uppercase;">
      </div>
      <div class="mb-4 form-check">
        <input type="checkbox" class="form-check-input" id="agreeTerms" required>
        <label class="form-check-label small" for="agreeTerms">
          I agree to the <a href="#" class="text-gold">Terms of Service</a> and
          <a href="#" class="text-gold">Privacy Policy</a>.
          I understand MM2H 管家 is a support platform, not a licensed agent.
        </label>
      </div>
      <button type="submit" class="btn btn-gold w-100 py-2"><?= t('register_btn') ?></button>
    </form>

    <p class="text-center mt-4 mb-0 small">
      <?= t('have_account') ?>
      <a href="<?= APP_URL ?>/login" class="text-gold fw-bold"><?= t('nav_login') ?></a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
