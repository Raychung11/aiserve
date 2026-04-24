<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo  = db();
$uid  = (int)$_SESSION['user_id'];
$user = current_user();

$profile_stmt = $pdo->prepare('SELECT * FROM member_profiles WHERE user_id=?');
$profile_stmt->execute([$uid]);
$profile = $profile_stmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        if ($full_name) {
            $pdo->prepare('UPDATE users SET full_name=?, phone=?, updated_at=NOW() WHERE id=?')
                ->execute([$full_name, $phone, $uid]);
            $_SESSION['user_name'] = $full_name;
            flash('success', 'Profile updated.');
        }
    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) {
            flash('danger', 'Current password is incorrect.');
        } elseif (strlen($new_pass) < 8) {
            flash('danger', 'New password must be at least 8 characters.');
        } elseif ($new_pass !== $confirm) {
            flash('danger', 'Passwords do not match.');
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $uid]);
            flash('success', 'Password changed successfully.');
        }
    }
    log_activity("profile_$action");
    redirect('member/profile');
}

$page_title = 'My Profile — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-person-circle me-2 text-gold"></i>My Profile</h1></div>
    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Profile info -->
      <div class="col-lg-6">
        <div class="mm2h-form-card mb-4">
          <h5 class="mb-4">Account Information</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= h($user['full_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?= h($user['email'] ?? '') ?>" disabled>
              <div class="form-text">Email cannot be changed. Contact support if needed.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control" value="<?= h($user['phone'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-gold"><?= t('save') ?></button>
          </form>
        </div>

        <!-- Change password -->
        <div class="mm2h-form-card">
          <h5 class="mb-4">Change Password</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="8" required>
            </div>
            <div class="mb-4">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-navy"><?= t('save') ?></button>
          </form>
        </div>
      </div>

      <!-- Account details sidebar -->
      <div class="col-lg-6">
        <div class="mm2h-form-card mb-4">
          <h5 class="mb-4">Account Summary</h5>
          <?php
          $details = [
            ['Role',       ucwords(str_replace('_',' ',$user['role'] ?? ''))],
            ['Plan',       ucfirst($profile['subscription_plan'] ?? 'free')],
            ['Joined',     format_date($user['created_at'] ?? '')],
            ['Last Login', format_date($user['last_login'] ?? '')],
          ];
          foreach ($details as [$label, $val]):
          ?>
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small"><?= $label ?></span>
            <span class="fw-semibold small"><?= h($val) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Referral section -->
        <div class="mm2h-form-card mb-4">
          <h5 class="mb-3">My Referral Code</h5>
          <p class="text-muted small">Share your referral code and earn when a friend joins MM2H 管家.</p>
          <div class="d-flex align-items-center gap-2">
            <div class="form-control fw-bold text-gold letter-spacing-1" style="background:var(--light);">
              <?= h($user['referral_code'] ?? 'N/A') ?>
            </div>
            <button class="btn btn-outline-gold flex-shrink-0 copy-referral"
                    data-code="<?= h($user['referral_code'] ?? '') ?>">
              <i class="bi bi-copy me-1"></i>Copy
            </button>
          </div>
          <?php
          $ref_count = (int)$pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id=?')
                                ->execute([$uid]) ? $pdo->query("SELECT COUNT(*) FROM referrals WHERE referrer_id=$uid")->fetchColumn() : 0;
          ?>
          <div class="small text-muted mt-2">Total referrals: <strong><?= $ref_count ?></strong></div>
        </div>

        <div class="mm2h-form-card">
          <h5 class="mb-3">Upgrade Plan</h5>
          <?php if (($profile['subscription_plan'] ?? 'free') === 'free'): ?>
          <p class="text-muted small mb-3">Upgrade to Premium for the full concierge experience including property matching, banking support, and business networking.</p>
          <a href="<?= APP_URL ?>/pricing" class="btn btn-gold w-100">
            <i class="bi bi-star me-2"></i>View Plans
          </a>
          <?php else: ?>
          <div class="alert alert-success mb-0">
            <i class="bi bi-check-circle-fill me-2"></i>
            You are on the <strong><?= h(ucfirst($profile['subscription_plan'])) ?></strong> plan.
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
