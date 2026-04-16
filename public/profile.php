<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
$user_id = auth_id();
$user    = auth_user();
$db      = getDB();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = sanitize_string($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $full_name = sanitize_string($_POST['full_name'] ?? '');
        $phone     = sanitize_string($_POST['phone'] ?? '');
        $req = validate_required($_POST, ['full_name']);
        $errors = array_merge($errors, $req);
        if (!empty($phone) && !validate_phone($phone)) $errors['phone'] = 'Format telefon tidak sah.';
        if (empty($errors)) {
            $old = ['full_name' => $user['full_name'], 'phone' => $user['phone']];
            $db->prepare("UPDATE users SET full_name=?, phone=?, updated_at=NOW() WHERE id=?")->execute([$full_name,$phone,$user_id]);
            audit_log($user_id, $user['role'], 'profile_updated', 'users', $user_id, $old, ['full_name'=>$full_name,'phone'=>$phone]);
            flash_set('main','Profil berjaya dikemaskini.','success');
            redirect(APP_URL . '/profile');
        }
    } elseif ($action === 'change_password') {
        $old_pass  = $_POST['old_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $new_pass2 = $_POST['new_password2'] ?? '';
        if (!password_verify($old_pass, $user['password_hash'])) $errors['old_password'] = 'Kata laluan semasa tidak betul.';
        if (!validate_password_strength($new_pass)) $errors['new_password'] = 'Kata laluan baharu tidak memenuhi syarat.';
        if ($new_pass !== $new_pass2) $errors['new_password2'] = 'Pengesahan kata laluan tidak sepadan.';
        if (empty($errors)) {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost'=>10]);
            $db->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$hash,$user_id]);
            audit_log($user_id,$user['role'],'password_changed','users',$user_id,null,null);
            flash_set('main','Kata laluan berjaya ditukar.','success');
            redirect(APP_URL . '/profile');
        }
    }
}

// Refresh user
$fresh = $db->prepare("SELECT * FROM users WHERE id=?");
$fresh->execute([$user_id]);
$user  = $fresh->fetch();

layout_begin_user('Profil Saya');
?>
<div class="page-title">👤 Profil Saya</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="md:grid-cols-2 grid-cols-1">
  <!-- Profile form -->
  <div class="card-kasih card-gold">
    <div class="section-title">Maklumat Peribadi</div>
    <?= flash_html('main') ?>
    <?= validation_errors($errors) ?>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div class="form-group">
        <label class="label-kasih">Nama Penuh</label>
        <input type="text" name="full_name" class="input-kasih" value="<?= h($user['full_name']) ?>" required>
        <?php if (isset($errors['full_name'])): ?><span class="error-text"><?= h($errors['full_name']) ?></span><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Alamat E-mel</label>
        <input type="email" class="input-kasih" value="<?= h($user['email']) ?>" disabled>
        <span class="help-text">E-mel tidak boleh diubah.</span>
      </div>
      <div class="form-group">
        <label class="label-kasih">Nombor Telefon</label>
        <input type="tel" name="phone" class="input-kasih" value="<?= h($user['phone'] ?? '') ?>" placeholder="0123456789">
        <?php if (isset($errors['phone'])): ?><span class="error-text"><?= h($errors['phone']) ?></span><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Kod Rujukan</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="text" class="input-kasih" value="<?= h($user['referral_code']) ?>" disabled>
          <button type="button" class="btn-gold-outline btn-sm" data-copy="<?= h(APP_URL . '/register?ref=' . $user['referral_code']) ?>">Salin</button>
        </div>
      </div>
      <button type="submit" class="btn-gold btn-block">Simpan Perubahan</button>
    </form>
  </div>

  <!-- Password change -->
  <div class="card-kasih">
    <div class="section-title">Tukar Kata Laluan</div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label class="label-kasih">Kata Laluan Semasa</label>
        <input type="password" name="old_password" class="input-kasih" required>
        <?php if (isset($errors['old_password'])): ?><span class="error-text"><?= h($errors['old_password']) ?></span><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Kata Laluan Baharu</label>
        <input type="password" name="new_password" class="input-kasih" placeholder="Min 8 aksara, huruf besar & nombor" required>
        <?php if (isset($errors['new_password'])): ?><span class="error-text"><?= h($errors['new_password']) ?></span><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Sahkan Kata Laluan Baharu</label>
        <input type="password" name="new_password2" class="input-kasih" required>
        <?php if (isset($errors['new_password2'])): ?><span class="error-text"><?= h($errors['new_password2']) ?></span><?php endif; ?>
      </div>
      <button type="submit" class="btn-dark btn-block">Tukar Kata Laluan</button>
    </form>

    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #F3F4F6;">
      <div style="font-size:0.8rem;color:#6B7280;"><strong>Peranan:</strong> <?= status_badge($user['role']) ?></div>
      <div style="font-size:0.8rem;color:#6B7280;margin-top:4px;"><strong>Status:</strong> <?= status_badge($user['status']) ?></div>
      <div style="font-size:0.8rem;color:#6B7280;margin-top:4px;"><strong>Ahli sejak:</strong> <?= format_date($user['created_at'], 'd M Y') ?></div>
    </div>
  </div>
</div>
<?php layout_end_user(); ?>
