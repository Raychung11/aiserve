<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin())  redirect('admin/dashboard');
if (is_member()) redirect('member/dashboard');

$pdo  = db();
$uid  = (int)$_SESSION['user_id'];
$user = current_user();

$partner_stmt = $pdo->prepare('SELECT * FROM partners WHERE user_id=?');
$partner_stmt->execute([$uid]);
$partner = $partner_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $full_name    = trim($_POST['full_name'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $sector       = trim($_POST['sector'] ?? '');
        $website      = trim($_POST['website'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        if ($full_name) {
            $pdo->prepare('UPDATE users SET full_name=?, phone=? WHERE id=?')->execute([$full_name, $phone, $uid]);
            $_SESSION['user_name'] = $full_name;
        }
        if ($partner) {
            $pdo->prepare('UPDATE partners SET company_name=?, sector=?, website=?, description=?, updated_at=NOW() WHERE user_id=?')
                ->execute([$company_name, $sector, $website, $description, $uid]);
        } else {
            $pdo->prepare('INSERT INTO partners (user_id, company_name, sector, website, description) VALUES (?,?,?,?,?)')
                ->execute([$uid, $company_name, $sector, $website, $description]);
        }
        flash('success', 'Profile updated.');
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            flash('danger', 'Current password incorrect.');
        } elseif (strlen($new) < 8 || $new !== $confirm) {
            flash('danger', 'Passwords must match and be at least 8 characters.');
        } else {
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $uid]);
            flash('success', 'Password changed.');
        }
    }
    log_activity("partner_profile_$action");
    redirect('partner/profile');
}

// Reload
$partner_stmt->execute([$uid]);
$partner = $partner_stmt->fetch() ?: [];

$page_title = 'Partner Profile — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/partner_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-person-circle me-2 text-gold"></i>Partner Profile</h1></div>
    <?php render_flash(); ?>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="mm2h-form-card mb-4">
          <h5 class="mb-4">Business Information</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= h($user['full_name'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= h($user['phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Company / Agency Name</label>
                <input type="text" name="company_name" class="form-control" value="<?= h($partner['company_name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Sector</label>
                <select name="sector" class="form-select">
                  <option value="">Select…</option>
                  <?php foreach (['MM2H Agency','Property','Banking','Legal','Accounting','Business Consulting','Investment','Education','Healthcare','Other'] as $s): ?>
                  <option value="<?=$s?>" <?=($partner['sector']??'')===$s?'selected':''?>><?=$s?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Website</label>
                <input type="url" name="website" class="form-control" value="<?= h($partner['website'] ?? '') ?>" placeholder="https://…">
              </div>
              <div class="col-12">
                <label class="form-label">Company Description</label>
                <textarea name="description" class="form-control" rows="4"><?= h($partner['description'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold"><?= t('save') ?></button>
              </div>
            </div>
          </form>
        </div>

        <div class="mm2h-form-card">
          <h5 class="mb-4">Change Password</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
              <div class="col-md-6"><label class="form-label">Confirm</label><input type="password" name="confirm_password" class="form-control" minlength="8" required></div>
              <div class="col-12"><button type="submit" class="btn btn-navy"><?= t('save') ?></button></div>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="mm2h-form-card mb-4">
          <h6 class="mb-3">Account Summary</h6>
          <?php $details = [
            ['Email',       $user['email'] ?? ''],
            ['Role',        ucwords(str_replace('_',' ',$user['role'] ?? ''))],
            ['Verified',    !empty($partner['verified']) ? '✅ Yes' : '⏳ Pending'],
            ['Commission',  ($partner['commission_rate'] ?? 20) . '%'],
            ['Referral Code', $user['referral_code'] ?? '—'],
            ['Member Since', format_date($user['created_at'] ?? '')],
          ];
          foreach ($details as [$l,$v]): ?>
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="small text-muted"><?= $l ?></span>
            <span class="small fw-semibold"><?= h($v) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mm2h-form-card">
          <h6 class="mb-2">Your Referral Link</h6>
          <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" readonly
                   value="<?= h(APP_URL . '/register?ref=' . ($user['referral_code'] ?? '')) ?>">
            <button class="btn btn-outline-gold btn-sm copy-referral"
                    data-code="<?= h(APP_URL . '/register?ref=' . ($user['referral_code'] ?? '')) ?>">
              <i class="bi bi-copy"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
