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

$leads_stmt = $pdo->prepare(
    'SELECT r.*, u.full_name, u.email, u.phone, u.created_at AS joined,
            mp.nationality, mp.purpose, mp.subscription_plan,
            mc.case_number, mc.current_status AS case_status
     FROM referrals r
     JOIN users u ON u.id = r.referred_user_id
     LEFT JOIN member_profiles mp ON mp.user_id = u.id
     LEFT JOIN mm2h_cases mc ON mc.applicant_id = u.id
     WHERE r.referrer_id = ?
     ORDER BY r.created_at DESC'
);
$leads_stmt->execute([$uid]);
$leads = $leads_stmt->fetchAll();

$page_title = 'My Leads — Partner Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/partner_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h1><i class="bi bi-funnel me-2 text-gold"></i>My Leads</h1>
      <a href="<?= APP_URL ?>/partner/referrals" class="btn btn-gold btn-sm">
        <i class="bi bi-plus me-1"></i>Add Lead
      </a>
    </div>

    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th><th>Contact</th><th>Nationality</th><th>Purpose</th>
            <th>Case Status</th><th>Deal</th><th>Commission</th><th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $lead): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= h($lead['full_name']) ?></div>
              <?php if ($lead['subscription_plan'] === 'premium'): ?>
              <span class="badge bg-warning text-dark" style="font-size:.6rem;">Premium</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="small"><?= h($lead['email']) ?></div>
              <small class="text-muted"><?= h($lead['phone'] ?? '') ?></small>
            </td>
            <td class="small"><?= h($lead['nationality'] ?? '—') ?></td>
            <td class="small"><?= h(ucfirst($lead['purpose'] ?? '—')) ?></td>
            <td>
              <?php if ($lead['case_number']): ?>
              <div class="small fw-semibold text-gold"><?= h($lead['case_number']) ?></div>
              <?= status_badge($lead['case_status'] ?? 'new_lead') ?>
              <?php else: ?>
              <span class="text-muted small">No case</span>
              <?php endif; ?>
            </td>
            <td><?= status_badge($lead['deal_status']) ?></td>
            <td>
              <?php if ($lead['commission_amount'] > 0): ?>
              <div class="fw-bold text-gold small"><?= format_money((float)$lead['commission_amount']) ?></div>
              <div><?= status_badge($lead['commission_status']) ?></div>
              <?php else: ?>
              <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?= time_ago($lead['joined']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$leads): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">
            No leads yet. <a href="<?= APP_URL ?>/partner/referrals">Submit a referral</a> or share your referral link.
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
