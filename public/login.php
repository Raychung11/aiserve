<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();

// Already logged in — redirect
if (auth_check()) {
    $role = auth_role();
    if ($role === 'super_admin') redirect(APP_URL . '/admin');
    elseif ($role === 'merchant') redirect(APP_URL . '/merchant');
    else redirect(APP_URL . '/dashboard');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email    = sanitize_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors['general'] = 'Sila masukkan e-mel dan kata laluan.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $errors['general'] = 'Akaun anda belum aktif atau telah digantung. Sila hubungi sokongan.';
            } else {
                auth_login($user);
                audit_log((int)$user['id'], $user['role'], 'user_login', 'users', (int)$user['id'], null, null);
                $role = $user['role'];
                if ($role === 'super_admin') redirect(APP_URL . '/admin');
                elseif ($role === 'merchant') redirect(APP_URL . '/merchant');
                else redirect(APP_URL . '/dashboard');
            }
        } else {
            $errors['general'] = 'E-mel atau kata laluan tidak sah. Sila cuba semula.';
        }
    }
}

layout_head('Log Masuk');
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:var(--kasih-bg);">
  <div style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:28px;">
      <a href="<?= APP_URL ?>/" style="font-size:1.6rem;font-weight:900;color:var(--gold-dark);text-decoration:none;">✦ Kasih Gold Easy</a>
      <p style="color:#6B7280;font-size:0.875rem;margin-top:6px;">Emas Mudah, Kaya Mudah.</p>
    </div>

    <div class="card-kasih card-gold">
      <h1 style="font-size:1.25rem;font-weight:700;color:var(--kasih-dark);margin-bottom:20px;">Log Masuk</h1>

      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= h($errors['general']) ?></div>
      <?php endif; ?>

      <?php echo flash_html('main'); ?>

      <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="label-kasih" for="email">Alamat E-mel <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="input-kasih <?= isset($errors['email']) ? 'border-red-400' : '' ?>"
                 value="<?= h($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required autocomplete="email">
          <?php if (isset($errors['email'])): ?><span class="error-text"><?= h($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="password">Kata Laluan <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="input-kasih"
                 placeholder="Masukkan kata laluan" required autocomplete="current-password">
        </div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
          <a href="<?= APP_URL ?>/forgot" style="font-size:0.82rem;color:var(--gold-dark);">Lupa kata laluan?</a>
        </div>
        <button type="submit" class="btn-gold btn-block btn-lg">Log Masuk</button>
      </form>
      <p style="text-align:center;margin-top:20px;font-size:0.875rem;color:#6B7280;">
        Belum ada akaun? <a href="<?= APP_URL ?>/register" style="color:var(--gold-dark);font-weight:600;">Daftar sekarang</a>
      </p>
    </div>

    <!-- Demo accounts notice (dev only) -->
    <?php if (APP_ENV === 'development'): ?>
    <div class="alert alert-info" style="margin-top:16px;font-size:0.8rem;">
      <strong>Demo:</strong> admin@kasihgold.my / user@kasihgold.my / merchant@kasihgold.my — password: <code>Admin@1234</code>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php layout_footer(); ?>
