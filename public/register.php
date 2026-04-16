<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
if (auth_check()) redirect(APP_URL . '/dashboard');

$errors = [];
$ref_code = sanitize_string($_GET['ref'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $full_name = sanitize_string($_POST['full_name'] ?? '');
    $email     = sanitize_string($_POST['email'] ?? '');
    $phone     = sanitize_string($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $ref_code  = sanitize_string($_POST['ref_code'] ?? '');
    $reg_type  = sanitize_string($_POST['reg_type'] ?? 'user'); // user or merchant

    // Validate
    $req_errors = validate_required($_POST, ['full_name', 'email', 'password', 'password2']);
    $errors = array_merge($errors, $req_errors);

    if (!empty($email) && !validate_email($email)) $errors['email'] = 'Format e-mel tidak sah.';
    if (!empty($phone) && !validate_phone($phone)) $errors['phone'] = 'Format nombor telefon tidak sah (contoh: 0123456789).';
    if (!validate_password_strength($password)) $errors['password'] = 'Kata laluan mestilah sekurang-kurangnya 8 aksara, mengandungi huruf besar, huruf kecil dan nombor.';
    if ($password !== $password2) $errors['password2'] = 'Pengesahan kata laluan tidak sepadan.';

    if (empty($errors)) {
        $db = getDB();
        // Check email unique
        $chk = $db->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors['email'] = 'E-mel ini telah didaftarkan. Sila gunakan e-mel lain atau log masuk.';
        }
    }

    if (empty($errors)) {
        $db = getDB();
        // Resolve referral
        $referred_by = null;
        if ($ref_code) {
            $ref_stmt = $db->prepare("SELECT id FROM users WHERE referral_code=? AND status='active'");
            $ref_stmt->execute([$ref_code]);
            $ref_user = $ref_stmt->fetch();
            if ($ref_user) $referred_by = (int)$ref_user['id'];
        }

        $role      = ($reg_type === 'merchant') ? 'merchant' : 'user';
        $status    = ($role === 'merchant') ? 'pending' : 'active';
        $my_code   = generate_referral_code();
        $pass_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        $db->prepare("INSERT INTO users (full_name,email,phone,password_hash,role,status,referral_code,referred_by_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())")
           ->execute([$full_name, $email, $phone, $pass_hash, $role, $status, $my_code, $referred_by]);

        $user_id = (int)$db->lastInsertId();

        // Create wallet
        ensure_wallet_exists($user_id);

        // Record referral link
        if ($referred_by) {
            // Find L1 referral
            $db->prepare("INSERT INTO referrals (referrer_user_id, referred_user_id, level, created_at) VALUES (?,?,1,NOW())")
               ->execute([$referred_by, $user_id]);
        }

        audit_log($user_id, $role, 'user_registered', 'users', $user_id, null, ['email' => $email, 'role' => $role]);

        if ($role === 'merchant') {
            // Create stub merchant record
            $db->prepare("INSERT INTO merchants (user_id,company_name,contact_name,status,created_at) VALUES (?,?,?,'pending',NOW())")
               ->execute([$user_id, $full_name, $full_name]);
            flash_set('main', 'Pendaftaran pedagang berjaya! Sila tunggu kelulusan dari admin sebelum boleh menjual.', 'info');
        } else {
            flash_set('main', 'Pendaftaran berjaya! Sila log masuk untuk meneruskan.', 'success');
        }

        redirect(APP_URL . '/login');
    }
}

layout_head('Daftar Akaun');
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:var(--kasih-bg);">
  <div style="width:100%;max-width:480px;">
    <div style="text-align:center;margin-bottom:24px;">
      <a href="<?= APP_URL ?>/" style="font-size:1.5rem;font-weight:900;color:var(--gold-dark);">✦ Kasih Gold Easy</a>
      <p style="color:#6B7280;font-size:0.875rem;margin-top:4px;">Mulakan perjalanan simpanan emas anda</p>
    </div>

    <div class="card-kasih card-gold">
      <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:18px;">Daftar Akaun Baharu</h1>

      <?= validation_errors($errors) ?>
      <?= flash_html('main') ?>

      <form method="POST">
        <?= csrf_field() ?>

        <!-- Account type tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;background:#F3F4F6;border-radius:8px;padding:4px;">
          <label style="flex:1;text-align:center;">
            <input type="radio" name="reg_type" value="user" <?= (($_POST['reg_type']??'user')==='user')?'checked':'' ?> style="display:none;" class="reg-type-radio">
            <span class="reg-type-btn" style="display:block;padding:8px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;<?= (($_POST['reg_type']??'user')==='user')?'background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.1);color:var(--gold-dark)':'color:#6B7280' ?>">👤 Pengguna</span>
          </label>
          <label style="flex:1;text-align:center;">
            <input type="radio" name="reg_type" value="merchant" <?= (($_POST['reg_type']??'')==='merchant')?'checked':'' ?> style="display:none;" class="reg-type-radio">
            <span class="reg-type-btn" style="display:block;padding:8px;border-radius:6px;font-size:0.85rem;font-weight:600;cursor:pointer;<?= (($_POST['reg_type']??'')==='merchant')?'background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.1);color:var(--gold-dark)':'color:#6B7280' ?>">🏪 Pedagang</span>
          </label>
        </div>

        <div class="form-group">
          <label class="label-kasih" for="full_name">Nama Penuh <span class="required">*</span></label>
          <input type="text" id="full_name" name="full_name" class="input-kasih"
                 value="<?= h($_POST['full_name'] ?? '') ?>" placeholder="Nama seperti dalam IC" required>
          <?php if (isset($errors['full_name'])): ?><span class="error-text"><?= h($errors['full_name']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="email">Alamat E-mel <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="input-kasih"
                 value="<?= h($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required>
          <?php if (isset($errors['email'])): ?><span class="error-text"><?= h($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="phone">Nombor Telefon</label>
          <input type="tel" id="phone" name="phone" class="input-kasih"
                 value="<?= h($_POST['phone'] ?? '') ?>" placeholder="0123456789">
          <?php if (isset($errors['phone'])): ?><span class="error-text"><?= h($errors['phone']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="password">Kata Laluan <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="input-kasih"
                 placeholder="Min. 8 aksara, huruf besar & nombor" required>
          <?php if (isset($errors['password'])): ?><span class="error-text"><?= h($errors['password']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="password2">Sahkan Kata Laluan <span class="required">*</span></label>
          <input type="password" id="password2" name="password2" class="input-kasih" placeholder="Ulang kata laluan" required>
          <?php if (isset($errors['password2'])): ?><span class="error-text"><?= h($errors['password2']) ?></span><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="label-kasih" for="ref_code">Kod Rujukan (Pilihan)</label>
          <input type="text" id="ref_code" name="ref_code" class="input-kasih"
                 value="<?= h($_POST['ref_code'] ?? $ref_code) ?>" placeholder="Kod rujukan rakan anda" style="text-transform:uppercase;">
          <span class="help-text">Jika rakan anda mengajak anda, masukkan kod rujukannya di sini.</span>
        </div>

        <div style="margin-bottom:16px;padding:12px;background:#F9FAFB;border-radius:8px;font-size:0.78rem;color:#6B7280;line-height:1.6;">
          Dengan mendaftar, anda bersetuju dengan <a href="/terms" style="color:var(--gold-dark)">Terma & Syarat</a> dan 
          <a href="/privacy" style="color:var(--gold-dark)">Dasar Privasi</a> kami.<br>
          <em><?= h(get_setting('shariah_disclaimer', '')) ?></em>
        </div>

        <button type="submit" class="btn-gold btn-block btn-lg">Daftar Sekarang</button>
      </form>
      <p style="text-align:center;margin-top:16px;font-size:0.875rem;color:#6B7280;">
        Sudah ada akaun? <a href="<?= APP_URL ?>/login" style="color:var(--gold-dark);font-weight:600;">Log masuk</a>
      </p>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.reg-type-radio').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.reg-type-radio').forEach(function(r) {
      var span = r.nextElementSibling;
      if (r.checked) {
        span.style.background='#fff'; span.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'; span.style.color='var(--gold-dark)';
      } else {
        span.style.background=''; span.style.boxShadow=''; span.style.color='#6B7280';
      }
    });
  });
});
</script>
<?php layout_footer(); ?>
