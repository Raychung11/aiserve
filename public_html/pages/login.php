<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

if (auth_check()) {
    redirect(is_admin() ? 'admin/dashboard' : (is_partner() ? 'partner/dashboard' : 'member/dashboard'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = login_user($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        $role = $result['role'];
        if (in_array($role, ['super_admin', 'admin'], true)) {
            redirect('admin/dashboard');
        } elseif (in_array($role, ['agent', 'property_partner', 'bank_partner', 'biz_partner', 'affiliate'], true)) {
            redirect('partner/dashboard');
        } else {
            redirect('member/dashboard');
        }
    } else {
        $error = $result['error'];
    }
}

$page_title  = t('login_title');
$body_class  = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="brand-icon" style="width:56px;height:56px;font-size:1.6rem;margin:0 auto .75rem;">管</div>
      <h4 class="auth-title"><?= t('login_title') ?></h4>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?= h($error) ?></div>
    <?php endif; ?>

    <?php render_flash(); ?>

    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label"><?= t('email_label') ?></label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= h($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>
      <div class="mb-4">
        <label class="form-label"><?= t('password_label') ?></label>
        <div class="input-group">
          <input type="password" name="password" id="loginPass" class="form-control"
                 placeholder="••••••••" required autocomplete="current-password">
          <button class="btn btn-outline-secondary" type="button" id="togglePass"
                  onclick="var p=document.getElementById('loginPass');p.type=p.type==='password'?'text':'password';">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-gold w-100 py-2"><?= t('login_btn') ?></button>
    </form>

    <p class="text-center mt-4 mb-0 small">
      <?= t('no_account') ?>
      <a href="<?= APP_URL ?>/register" class="text-gold fw-bold"><?= t('nav_register') ?></a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
