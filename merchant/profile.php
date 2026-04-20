<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$user_id = auth_id();
$user    = auth_user();

$m_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$m_stmt->execute([$user_id]);
$merchant = $m_stmt->fetch();
if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }

$errors = [];

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name   = sanitize_string($_POST['full_name'] ?? '');
        $phone       = sanitize_string($_POST['phone'] ?? '');
        $biz_name    = sanitize_string($_POST['business_name'] ?? '');
        $biz_reg     = sanitize_string($_POST['business_registration'] ?? '');
        $biz_address = sanitize_string($_POST['business_address'] ?? '');
        $biz_desc    = sanitize_string($_POST['business_description'] ?? '');

        if (!$full_name) $errors[] = 'Nama penuh diperlukan.';

        if (empty($errors)) {
            $db->prepare("UPDATE users SET full_name=?,phone=?,updated_at=NOW() WHERE id=?")->execute([$full_name,$phone,$user_id]);
            $db->prepare("UPDATE merchants SET business_name=?,business_registration=?,business_address=?,business_description=?,updated_at=NOW() WHERE user_id=?")->execute([$biz_name,$biz_reg,$biz_address,$biz_desc,$user_id]);

            // Handle logo upload
            if (!empty($_FILES['business_logo']['name'])) {
                $logo = upload_file('business_logo', ['image/jpeg','image/png','image/webp'], __DIR__.'/../uploads/merchants/', 2097152);
                if ($logo) $db->prepare("UPDATE merchants SET business_logo=? WHERE user_id=?")->execute([$logo, $user_id]);
                else $errors[] = 'Logo tidak sah (PNG/JPG/WebP, maks 2MB).';
            }

            if (empty($errors)) {
                audit_log($user_id,'merchant','profile_updated','merchants',(int)$merchant['id'],['biz_name'=>$merchant['business_name']],['biz_name'=>$biz_name]);
                flash_set('main','Profil berjaya dikemaskini.','success');
                redirect(APP_URL.'/merchant/profile');
            }
        }
    } elseif ($action === 'change_password') {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw     = $_POST['new_password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        $u_stmt = $db->prepare("SELECT password_hash FROM users WHERE id=?"); $u_stmt->execute([$user_id]); $u = $u_stmt->fetch();
        if (!$u || !password_verify($current_pw, $u['password_hash'])) {
            $errors[] = 'Kata laluan semasa tidak betul.';
        } elseif ($new_pw !== $confirm_pw) {
            $errors[] = 'Pengesahan kata laluan tidak sepadan.';
        } else {
            $pw_errs = validate_password_strength($new_pw);
            $errors  = array_merge($errors, $pw_errs);
        }

        if (empty($errors)) {
            $db->prepare("UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?")->execute([password_hash($new_pw, PASSWORD_BCRYPT, ['cost'=>12]),$user_id]);
            flash_set('main','Kata laluan berjaya ditukar.','success');
            redirect(APP_URL.'/merchant/profile');
        }
    }

    // Reload merchant data after update
    $m_stmt->execute([$user_id]); $merchant = $m_stmt->fetch();
    $user = $db->prepare("SELECT * FROM users WHERE id=?")->execute([$user_id]) ? $user : $user;
    $u2   = $db->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$user_id]); $user = $u2->fetch() ?: $user;
}

layout_begin_merchant('Profil Pedagang');
?>
<div class="page-title">👤 Profil Pedagang</div>
<?= flash_html('main') ?>

<!-- Merchant Status Badge -->
<div style="margin-bottom:16px;">
  Status Akaun: <?= status_badge($merchant['status']) ?>
  <?php if ($merchant['verification_notes']): ?>
    <span style="font-size:0.8rem;color:#6B7280;margin-left:8px;"><?= h($merchant['verification_notes']) ?></span>
  <?php endif; ?>
</div>

<?php if (!empty($errors)): ?><div class="alert alert-error"><?= implode('<br>',$errors) ?></div><?php endif; ?>

<!-- Profile Form -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">🏪 Maklumat Perniagaan</div>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update_profile">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label class="form-label">Nama Penuh *</label>
        <input type="text" name="full_name" class="form-input" required value="<?= h($user['full_name'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Nombor Telefon</label>
        <input type="text" name="phone" class="form-input" value="<?= h($user['phone'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Nama Perniagaan</label>
        <input type="text" name="business_name" class="form-input" value="<?= h($merchant['business_name'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">No. Pendaftaran Perniagaan</label>
        <input type="text" name="business_registration" class="form-input" value="<?= h($merchant['business_registration'] ?? '') ?>">
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Alamat Perniagaan</label>
        <textarea name="business_address" class="form-input" rows="2"><?= h($merchant['business_address'] ?? '') ?></textarea>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Penerangan Perniagaan</label>
        <textarea name="business_description" class="form-input" rows="3"><?= h($merchant['business_description'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="form-label">Logo Perniagaan (PNG/JPG/WebP, maks 2MB)</label>
        <?php if ($merchant['business_logo']): ?>
          <img src="<?= APP_URL ?>/uploads/merchants/<?= h($merchant['business_logo']) ?>" style="height:60px;border-radius:8px;display:block;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" name="business_logo" class="form-input" accept="image/jpeg,image/png,image/webp">
      </div>
    </div>
    <button type="submit" class="btn-gold" style="margin-top:16px;">Simpan Profil</button>
  </form>
</div>

<!-- Account Info (read-only) -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">📧 Maklumat Akaun</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
    <div>
      <div class="form-label">E-mel</div>
      <div style="font-size:0.9rem;color:#374151;"><?= h($user['email'] ?? '') ?></div>
    </div>
    <div>
      <div class="form-label">Kod Rujukan</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <code style="background:#F3F4F6;padding:4px 10px;border-radius:6px;font-size:0.9rem;"><?= h($user['referral_code'] ?? '') ?></code>
        <button class="btn-gold-outline btn-sm" data-copy="<?= h(APP_URL.'/register?ref='.($user['referral_code']??'')) ?>">Salin Pautan</button>
      </div>
    </div>
    <div>
      <div class="form-label">Tarikh Daftar</div>
      <div style="font-size:0.85rem;color:#6B7280;"><?= format_date($user['created_at'] ?? '') ?></div>
    </div>
    <div>
      <div class="form-label">Log Masuk Terakhir</div>
      <div style="font-size:0.85rem;color:#6B7280;"><?= format_date($user['last_login_at'] ?? '') ?></div>
    </div>
  </div>
</div>

<!-- Change Password -->
<div class="card-kasih">
  <div class="section-title">🔒 Tukar Kata Laluan</div>
  <form method="post" style="max-width:420px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="change_password">
    <div style="margin-bottom:12px;">
      <label class="form-label">Kata Laluan Semasa *</label>
      <input type="password" name="current_password" class="form-input" required>
    </div>
    <div style="margin-bottom:12px;">
      <label class="form-label">Kata Laluan Baharu *</label>
      <input type="password" name="new_password" class="form-input" required>
      <small style="color:#9CA3AF;">Min 8 karakter, huruf besar, huruf kecil & nombor.</small>
    </div>
    <div style="margin-bottom:16px;">
      <label class="form-label">Sahkan Kata Laluan Baharu *</label>
      <input type="password" name="confirm_password" class="form-input" required>
    </div>
    <button type="submit" class="btn-gold-outline">Tukar Kata Laluan</button>
  </form>
</div>
<?php layout_end_merchant(); ?>
