<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

// Fetch stats
$pdo = db();
$stats = [];
$stats['total_leads']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$stats['new_today']        = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member' AND DATE(created_at)=CURDATE()")->fetchColumn();
$stats['active_cases']     = $pdo->query("SELECT COUNT(*) FROM mm2h_cases WHERE current_status NOT IN ('completed','rejected')")->fetchColumn();
$stats['completed_cases']  = $pdo->query("SELECT COUNT(*) FROM mm2h_cases WHERE current_status='completed'")->fetchColumn();
$stats['pending_docs']     = $pdo->query("SELECT COUNT(*) FROM documents WHERE status='pending'")->fetchColumn();
$stats['commission_total'] = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM commissions WHERE status='pending'")->fetchColumn();
$stats['total_partners']   = $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();

// Recent cases
$recent_cases = $pdo->query(
  "SELECT c.*, u.full_name, u.email
   FROM mm2h_cases c
   JOIN users u ON u.id = c.applicant_id
   ORDER BY c.updated_at DESC LIMIT 8"
)->fetchAll();

// Recent leads
$recent_leads = $pdo->query(
  "SELECT u.id, u.full_name, u.email, u.created_at, mp.purpose, mp.nationality
   FROM users u
   LEFT JOIN member_profiles mp ON mp.user_id = u.id
   WHERE u.role = 'member'
   ORDER BY u.created_at DESC LIMIT 8"
)->fetchAll();

$page_title = t('dashboard_title') . ' — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <div class="main-content">
    <div class="page-header d-flex align-items-center justify-content-between">
      <div>
        <h1><?= t('dashboard_title') ?></h1>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Admin Overview</li>
          </ol>
        </nav>
      </div>
      <div class="text-muted small"><?= date('l, d F Y') ?></div>
    </div>

    <?php render_flash(); ?>

    <!-- Stats row -->
    <div class="row g-3 mb-4">
      <?php
      $stat_cards = [
        [$stats['total_leads'],      'Total Members',      'bi-people-fill',     'rgba(200,160,60,.1)',  'var(--secondary)'],
        [$stats['new_today'],        'New Today',          'bi-person-plus-fill','rgba(16,185,129,.1)',  '#10b981'],
        [$stats['active_cases'],     'Active Cases',       'bi-folder2-open',    'rgba(59,130,246,.1)',  '#3b82f6'],
        [$stats['completed_cases'],  'Completed',          'bi-check-circle-fill','rgba(25,135,84,.1)', 'var(--success)'],
        [$stats['pending_docs'],     'Pending Docs',       'bi-file-earmark-text','rgba(255,193,7,.1)',  '#ffc107'],
        [format_money((float)$stats['commission_total']), 'Commission Due', 'bi-cash-stack', 'rgba(220,53,69,.1)', 'var(--danger)'],
        [$stats['total_partners'],   'Partners',           'bi-diagram-3-fill',  'rgba(139,92,246,.1)', '#8b5cf6'],
      ];
      foreach ($stat_cards as [$val, $label, $icon, $bg, $color]):
      ?>
      <div class="col-6 col-md-4 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div class="stat-value"><?= h((string)$val) ?></div>
            <div class="stat-label"><?= h($label) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-4">
      <!-- Recent Cases -->
      <div class="col-lg-7">
        <div class="mm2h-form-card p-0 overflow-hidden">
          <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2 text-gold"></i>Recent Cases</h6>
            <a href="<?= APP_URL ?>/admin/cases" class="btn btn-outline-gold btn-sm">View All</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Case #</th>
                  <th>Applicant</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_cases as $case): ?>
                <tr>
                  <td><a href="<?= APP_URL ?>/admin/cases?id=<?= $case['id'] ?>" class="text-gold fw-bold"><?= h($case['case_number']) ?></a></td>
                  <td>
                    <div class="fw-semibold"><?= h($case['full_name']) ?></div>
                    <small class="text-muted"><?= h($case['email']) ?></small>
                  </td>
                  <td><?= status_badge($case['current_status']) ?></td>
                  <td class="text-muted small"><?= time_ago($case['updated_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$recent_cases): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No cases yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Recent Leads -->
      <div class="col-lg-5">
        <div class="mm2h-form-card p-0 overflow-hidden">
          <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2 text-gold"></i>Recent Leads</h6>
            <a href="<?= APP_URL ?>/admin/leads" class="btn btn-outline-gold btn-sm">View All</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr><th>Name</th><th>Purpose</th><th>Joined</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recent_leads as $lead): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= h($lead['full_name']) ?></div>
                    <small class="text-muted"><?= h($lead['nationality'] ?? '—') ?></small>
                  </td>
                  <td><small><?= h(ucfirst($lead['purpose'] ?? '—')) ?></small></td>
                  <td class="text-muted small"><?= time_ago($lead['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$recent_leads): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No leads yet</td></tr>
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
