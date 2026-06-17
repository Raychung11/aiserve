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

$summary_stmt = $pdo->prepare(
    'SELECT status, COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt
     FROM commissions WHERE partner_id=? GROUP BY status'
);
$summary_stmt->execute([$uid]);
$summary = [];
foreach ($summary_stmt->fetchAll() as $r) {
    $summary[$r['status']] = $r;
}

$comms_stmt = $pdo->prepare(
    'SELECT c.*, r.referred_user_id, u.full_name AS referred_name
     FROM commissions c
     LEFT JOIN referrals r ON r.id=c.referral_id
     LEFT JOIN users u ON u.id=r.referred_user_id
     WHERE c.partner_id=? ORDER BY c.created_at DESC'
);
$comms_stmt->execute([$uid]);
$commissions = $comms_stmt->fetchAll();

$page_title = 'Commissions — Partner Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/partner_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-cash-stack me-2 text-gold"></i>Commissions</h1></div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
      <?php
      $card_defs = [
        ['pending',  'Pending',  'bi-clock-fill',    '#ffc107'],
        ['approved', 'Approved', 'bi-check-circle',  '#10b981'],
        ['paid',     'Paid Out', 'bi-cash-coin',     'var(--secondary)'],
        ['rejected', 'Rejected', 'bi-x-circle-fill', 'var(--danger)'],
      ];
      foreach ($card_defs as [$key, $label, $icon, $color]):
        $row = $summary[$key] ?? ['total' => 0, 'cnt' => 0];
      ?>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $color ?>18;color:<?= $color ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div class="stat-value small"><?= format_money((float)$row['total']) ?></div>
            <div class="stat-label"><?= $label ?> (<?= (int)$row['cnt'] ?>)</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Referred Client</th><th>Amount</th><th>Type</th><th>Status</th><th>Notes</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($commissions as $i => $c): ?>
          <tr>
            <td class="text-muted small"><?= $i+1 ?></td>
            <td><?= h($c['referred_name'] ?? '—') ?></td>
            <td class="fw-bold text-gold"><?= format_money((float)$c['amount']) ?></td>
            <td class="small"><?= h(ucfirst($c['type'])) ?></td>
            <td><?= status_badge($c['status']) ?></td>
            <td class="small text-muted"><?= h($c['notes'] ?? '—') ?></td>
            <td class="text-muted small"><?= format_date($c['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$commissions): ?>
          <tr><td colspan="7" class="text-center py-5 text-muted">
            No commissions yet. Commissions are generated when your referrals complete their MM2H application.
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mm2h-form-card mt-4" style="border-left:4px solid var(--secondary);">
      <h6 class="mb-2">Commission Policy</h6>
      <ul class="list-unstyled small text-muted mb-0">
        <li class="mb-1"><i class="bi bi-info-circle me-2 text-gold"></i>Commission rate: <?= h(get_setting('default_commission','20')) ?>% (subject to agreement)</li>
        <li class="mb-1"><i class="bi bi-info-circle me-2 text-gold"></i>Commission is triggered when a referred client's MM2H case reaches <strong>Final Approval</strong></li>
        <li class="mb-1"><i class="bi bi-info-circle me-2 text-gold"></i>Payment processed within 30 days of case completion</li>
        <li><i class="bi bi-info-circle me-2 text-gold"></i>Contact your account manager for withdrawal instructions</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
