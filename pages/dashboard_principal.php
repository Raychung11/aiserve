<?php
/**
 * Principal dashboard — firm-level portfolio view
 * Loaded by dashboard.php; $currentUser is already in scope
 */
$associates = Hierarchy::getAssociatesWithStats($currentUser['id']);
$allCompanies = Hierarchy::getPrincipalCompanies($currentUser['id']);

$totalAssociates = count($associates);
$totalClients    = count($allCompanies);

// Aggregate avg ESG across entire portfolio
$totalEsg = $totalEsgCount = 0;
foreach ($allCompanies as $co) {
    if (empty($co['framework'])) continue;
    $period = $co['reporting_year'] ?? date('Y');
    $stats  = ESGDataManager::getCompletionStats($co['id'], $co['framework'], $period);
    $totalEsg += ESGDataManager::calcOverallScore($stats);
    $totalEsgCount++;
}
$portfolioAvg = $totalEsgCount ? (int) round($totalEsg / $totalEsgCount) : 0;

// Plan distribution
$planDist = Database::fetchAll(
    "SELECT COALESCE(s.plan_code, 'starter') AS plan, COUNT(*) AS cnt
     FROM companies c
     LEFT JOIN subscriptions s ON s.company_id = c.id AND s.status IN ('active','trial')
     WHERE c.id IN (" . (empty($allCompanies) ? '0' :
         implode(',', array_column($allCompanies, 'id'))
     ) . ")
     GROUP BY plan",
    []
);
$planMap = [];
foreach ($planDist as $row) $planMap[$row['plan']] = (int)$row['cnt'];
?>

<!-- Topbar -->
<div class="topbar">
  <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
  <div class="topbar-title">
    <h1><i class="bi bi-diagram-3-fill me-2 text-primary"></i>Principal Dashboard</h1>
    <span class="topbar-subtitle">Firm-wide ESG portfolio overview</span>
  </div>
  <div class="topbar-actions">
    <a href="<?= url('team') ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-person-plus me-1"></i>Manage Team
    </a>
  </div>
</div>

<div class="content-body">

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-primary-soft"><i class="bi bi-people-fill text-primary"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalAssociates ?></div>
          <div class="hier-kpi-label">Associates</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-success-soft"><i class="bi bi-buildings text-success"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalClients ?></div>
          <div class="hier-kpi-label">Total Clients</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-warning-soft"><i class="bi bi-bar-chart-fill text-warning"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $portfolioAvg ?>%</div>
          <div class="hier-kpi-label">Portfolio Avg ESG</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-info-soft"><i class="bi bi-star-fill text-info"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= ($planMap['professional'] ?? 0) + ($planMap['standard'] ?? 0) ?></div>
          <div class="hier-kpi-label">Paid Plans</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <!-- Associates table -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>My Associates</h5>
          <a href="<?= url('team') ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($associates)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-2"></i>
            <p class="mt-2 mb-3">No associates yet.</p>
            <a href="<?= url('team') ?>" class="btn btn-sm btn-primary">
              <i class="bi bi-person-plus me-1"></i>Add Associate
            </a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Associate</th>
                  <th class="text-center">Managers</th>
                  <th class="text-center">Clients</th>
                  <th class="text-center">Avg ESG</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($associates as $assoc): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="hier-avatar hier-avatar-associate">
                        <?= strtoupper(substr($assoc['name'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($assoc['name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($assoc['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary"><?= $assoc['manager_count'] ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary"><?= $assoc['company_count'] ?></span>
                  </td>
                  <td class="text-center">
                    <?php
                    $c = $assoc['avg_esg'];
                    $cls = $c >= 70 ? 'bg-success' : ($c >= 40 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <span class="badge <?= $cls ?>"><?= $c ?>%</span>
                  </td>
                  <td>
                    <a href="<?= url('companies') ?>?filter_user=<?= $assoc['id'] ?>" class="btn btn-xs btn-outline-secondary">View</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Plan distribution chart -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Plan Distribution</h5>
        </div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="planDonut" width="180" height="180"></canvas>
          <div class="mt-3 w-100">
            <?php
            $plans = Subscription::getAllPlans();
            foreach (['professional','standard','starter'] as $pk):
                $cnt = $planMap[$pk] ?? 0;
                $pl  = $plans[$pk];
            ?>
            <div class="d-flex justify-content-between align-items-center small mb-1">
              <span><span class="legend-dot" style="background:<?= $pl['color'] ?>"></span> <?= $pl['name'] ?></span>
              <strong><?= $cnt ?> clients</strong>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- All client companies compact list -->
  <?php if (!empty($allCompanies)): ?>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0"><i class="bi bi-grid me-2 text-muted"></i>All Client Companies (<?= $totalClients ?>)</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
          <thead>
            <tr>
              <th>Company</th>
              <th>Industry</th>
              <th>Managed By</th>
              <th>Framework</th>
              <th class="text-center">ESG Score</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($allCompanies, 0, 20) as $co):
                $period = $co['reporting_year'] ?? date('Y');
                $cStats = ESGDataManager::getCompletionStats($co['id'], $co['framework'] ?? 'BURSA_SEDG', $period);
                $cScore = ESGDataManager::calcOverallScore($cStats);
                $sCls   = $cScore >= 70 ? 'bg-success' : ($cScore >= 40 ? 'bg-warning' : 'bg-danger');
            ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($co['name']) ?></td>
              <td class="text-muted"><?= htmlspecialchars($co['industry'] ?? '—') ?></td>
              <td class="text-muted"><?= htmlspecialchars($co['owner_name'] ?? '—') ?></td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($co['framework'] ?? '—') ?></span></td>
              <td class="text-center"><span class="badge <?= $sCls ?>"><?= $cScore ?>%</span></td>
              <td>
                <a href="<?= url('companies') ?>?select=<?= $co['id'] ?>" class="btn btn-xs btn-outline-secondary">Open</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if ($totalClients > 20): ?>
            <tr><td colspan="6" class="text-center text-muted py-2 small">
              + <?= $totalClients - 20 ?> more — <a href="<?= url('companies') ?>">View all</a>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
const plans = Subscription.getAllPlans ? Subscription.getAllPlans() : {};
const planData  = [<?= $planMap['professional'] ?? 0 ?>, <?= $planMap['standard'] ?? 0 ?>, <?= $planMap['starter'] ?? 0 ?>];
const planTotal = planData.reduce((a,b) => a+b, 0);
if (planTotal > 0) {
  new Chart(document.getElementById('planDonut').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: ['Professional','Standard','Starter'],
      datasets: [{ data: planData,
        backgroundColor: ['#7c3aed','#0ea5e9','#64748b'],
        borderWidth: 2, borderColor: '#fff'
      }]
    },
    options: { cutout: '65%', plugins: { legend: { display: false } }, animation: { duration: 800 } }
  });
}
</script>
