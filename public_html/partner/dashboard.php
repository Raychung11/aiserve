<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin())  redirect('admin/dashboard');
if (is_member()) redirect('member/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Ensure partner record exists
$partner_stmt = $pdo->prepare('SELECT * FROM partners WHERE user_id=?');
$partner_stmt->execute([$uid]);
$partner = $partner_stmt->fetch();
if (!$partner) {
    $pdo->prepare('INSERT INTO partners (user_id, commission_rate) VALUES (?,?)')->execute([$uid, 20]);
    $partner_stmt->execute([$uid]);
    $partner = $partner_stmt->fetch();
}

// Stats
$total_refs     = (int)$pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id=?')->execute([$uid]) ?
                  $pdo->query("SELECT COUNT(*) FROM referrals WHERE referrer_id=$uid")->fetchColumn() : 0;
$total_refs     = (int)$pdo->query("SELECT COUNT(*) FROM referrals WHERE referrer_id=$uid")->fetchColumn();
$pending_comm   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM commissions WHERE partner_id=$uid AND status='pending'")->fetchColumn();
$paid_comm      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM commissions WHERE partner_id=$uid AND status='paid'")->fetchColumn();

// Recent referrals
$refs_stmt = $pdo->prepare(
    'SELECT r.*, u.full_name, u.email, u.created_at AS joined FROM referrals r
     JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_id=? ORDER BY r.created_at DESC LIMIT 8'
);
$refs_stmt->execute([$uid]);
$recent_refs = $refs_stmt->fetchAll();

$user = current_user();
$page_title = 'Partner Dashboard — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/partner_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
      <div>
        <h1>Partner Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, <?= h(explode(' ', $user['full_name'])[0]) ?>!</p>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <?php if ($partner['verified']): ?>
        <span class="badge bg-success px-3 py-2"><i class="bi bi-patch-check me-1"></i>Verified Partner</span>
        <?php else: ?>
        <span class="badge bg-secondary px-3 py-2">Pending Verification</span>
        <?php endif; ?>
        <span class="badge bg-warning text-dark px-3 py-2"><?= h($partner['commission_rate']) ?>% Commission</span>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <?php
      $stats = [
        [$total_refs,                 'Total Referrals',     'bi-people-fill',   'rgba(200,160,60,.1)', 'var(--secondary)'],
        [format_money($pending_comm), 'Pending Commission',  'bi-clock-fill',    'rgba(255,193,7,.1)',  '#ffc107'],
        [format_money($paid_comm),    'Total Paid',          'bi-cash-stack',    'rgba(25,135,84,.1)',  'var(--success)'],
        [$partner['commission_rate'] . '%', 'Commission Rate','bi-percent',      'rgba(59,130,246,.1)', '#3b82f6'],
      ];
      foreach ($stats as [$val, $label, $icon, $bg, $color]):
      ?>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div class="stat-value small"><?= h((string)$val) ?></div>
            <div class="stat-label"><?= h($label) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-4">
      <!-- Referral code -->
      <div class="col-lg-4">
        <div class="mm2h-form-card mb-4">
          <h6 class="mb-3"><i class="bi bi-link-45deg me-2 text-gold"></i>Your Referral Link</h6>
          <p class="small text-muted">Share your referral link to earn <?= h($partner['commission_rate']) ?>% commission on successful MM2H cases.</p>
          <div class="d-flex gap-2 mb-2">
            <input type="text" class="form-control form-control-sm" readonly
                   value="<?= h(APP_URL . '/register?ref=' . ($user['referral_code'] ?? '')) ?>"
                   id="refLink">
            <button class="btn btn-outline-gold btn-sm flex-shrink-0 copy-referral"
                    data-code="<?= h(APP_URL . '/register?ref=' . ($user['referral_code'] ?? '')) ?>">
              <i class="bi bi-copy"></i>
            </button>
          </div>
          <div class="small text-muted">Code: <strong class="text-gold"><?= h($user['referral_code'] ?? '—') ?></strong></div>
        </div>

        <!-- Quick tip -->
        <div class="mm2h-form-card" style="border-left:4px solid var(--secondary);">
          <h6 class="mb-2">Earning Tips</h6>
          <ul class="list-unstyled small text-muted mb-0">
            <li class="mb-1"><i class="bi bi-check-circle-fill text-gold me-2"></i>Share your link on WeChat, LINE, and WhatsApp</li>
            <li class="mb-1"><i class="bi bi-check-circle-fill text-gold me-2"></i>Refer investors and business owners</li>
            <li class="mb-1"><i class="bi bi-check-circle-fill text-gold me-2"></i>Commission paid on completed MM2H cases</li>
          </ul>
        </div>
      </div>

      <!-- Recent referrals -->
      <div class="col-lg-8">
        <div class="mm2h-form-card p-0 overflow-hidden">
          <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-gold"></i>Recent Referrals</h6>
            <a href="<?= APP_URL ?>/partner/referrals" class="btn btn-outline-gold btn-sm">View All</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr><th>Name</th><th>Lead Status</th><th>Deal</th><th>Commission</th><th>Joined</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recent_refs as $ref): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= h($ref['full_name']) ?></div>
                    <small class="text-muted"><?= h($ref['email']) ?></small>
                  </td>
                  <td><?= status_badge($ref['lead_status']) ?></td>
                  <td><?= status_badge($ref['deal_status']) ?></td>
                  <td>
                    <?php if ($ref['commission_amount'] > 0): ?>
                    <span class="fw-bold text-gold"><?= format_money((float)$ref['commission_amount']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= time_ago($ref['joined']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$recent_refs): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No referrals yet. Share your link to get started!</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
