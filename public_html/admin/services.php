<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$page_title = 'Services Overview — Admin';
require_once __DIR__ . '/../includes/header.php';
$pdo = db();

$service_stats = [
  ['Property Matches',     $pdo->query("SELECT COUNT(*) FROM property_recommendations")->fetchColumn(),    'bi-buildings',    '#C8A03C'],
  ['Bank Support Cases',   $pdo->query("SELECT COUNT(*) FROM bank_support_cases")->fetchColumn(),          'bi-bank2',        '#1B4965'],
  ['Business Interests',   $pdo->query("SELECT COUNT(*) FROM business_interests")->fetchColumn(),          'bi-briefcase',    '#10b981'],
  ['Business Matches',     $pdo->query("SELECT COUNT(*) FROM business_matches")->fetchColumn(),            'bi-people-fill',  '#6366f1'],
];
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-grid me-2 text-gold"></i>Services Overview</h1></div>
    <div class="row g-4 mb-4">
      <?php foreach ($service_stats as [$label, $count, $icon, $color]): ?>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?=$color?>18;color:<?=$color?>">
            <i class="bi <?=$icon?>"></i>
          </div>
          <div>
            <div class="stat-value"><?= (int)$count ?></div>
            <div class="stat-label"><?= h($label) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="mm2h-form-card">
          <h5 class="mb-3"><i class="bi bi-buildings me-2 text-gold"></i>Recent Property Recommendations</h5>
          <?php
          $recs = $pdo->query(
            "SELECT pr.*, u.full_name, p.property_name FROM property_recommendations pr
             JOIN users u ON u.id=pr.member_id
             JOIN properties p ON p.id=pr.property_id
             ORDER BY pr.created_at DESC LIMIT 5"
          )->fetchAll();
          ?>
          <table class="table table-sm mb-0">
            <thead><tr><th>Member</th><th>Property</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recs as $r): ?>
              <tr>
                <td><?= h($r['full_name']) ?></td>
                <td><?= h($r['property_name']) ?></td>
                <td class="text-muted small"><?= time_ago($r['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$recs): ?><tr><td colspan="3" class="text-muted text-center py-2">None yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="mm2h-form-card">
          <h5 class="mb-3"><i class="bi bi-briefcase me-2 text-gold"></i>Recent Business Interests</h5>
          <?php
          $interests = $pdo->query(
            "SELECT bi.*, u.full_name FROM business_interests bi
             JOIN users u ON u.id=bi.member_id
             ORDER BY bi.created_at DESC LIMIT 5"
          )->fetchAll();
          ?>
          <table class="table table-sm mb-0">
            <thead><tr><th>Member</th><th>Sector</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($interests as $i): ?>
              <tr>
                <td><?= h($i['full_name']) ?></td>
                <td><?= h($i['sector'] ?? '—') ?></td>
                <td><?= status_badge($i['status']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$interests): ?><tr><td colspan="3" class="text-muted text-center py-2">None yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
