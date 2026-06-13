<?php
/**
 * Associate dashboard — client portfolio + team view
 * Loaded by dashboard.php; $currentUser is already in scope
 */
$managers  = Hierarchy::getManagersWithStats($currentUser['id']);
$companies = Hierarchy::getAssociateCompanies($currentUser['id']);
$portfolio = Hierarchy::getPortfolioSummary($companies);

$totalManagers = count($managers);
$totalClients  = count($companies);

$avgEsg = 0;
if (!empty($portfolio)) {
    $avgEsg = (int) round(array_sum(array_column($portfolio, 'overall_score')) / count($portfolio));
}

$criticalCount = array_sum(array_column($portfolio, 'critical_gaps'));
?>

<!-- Topbar -->
<div class="topbar">
  <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
  <div class="topbar-title">
    <h1><i class="bi bi-briefcase-fill me-2 text-info"></i>Portfolio Dashboard</h1>
    <span class="topbar-subtitle">Client ESG portfolio — <?= htmlspecialchars($currentUser['name']) ?></span>
  </div>
  <div class="topbar-actions">
    <a href="<?= url('team') ?>" class="btn btn-outline-info btn-sm">
      <i class="bi bi-person-plus me-1"></i>Manage Team
    </a>
    <a href="<?= url('onboarding') ?>" class="btn btn-primary btn-sm ms-1">
      <i class="bi bi-plus me-1"></i>New Client
    </a>
  </div>
</div>

<div class="content-body">

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-info-soft"><i class="bi bi-buildings text-info"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalClients ?></div>
          <div class="hier-kpi-label">Client Companies</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-secondary-soft"><i class="bi bi-person-workspace text-secondary"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalManagers ?></div>
          <div class="hier-kpi-label">My Managers</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-success-soft"><i class="bi bi-bar-chart-fill text-success"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $avgEsg ?>%</div>
          <div class="hier-kpi-label">Portfolio Avg ESG</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-danger-soft"><i class="bi bi-exclamation-triangle-fill text-danger"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $criticalCount ?></div>
          <div class="hier-kpi-label">Critical Gaps</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">

    <!-- Client portfolio grid -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bi bi-grid-3x3-gap me-2 text-info"></i>Client Portfolio</h5>
          <a href="<?= url('companies') ?>" class="btn btn-sm btn-outline-info">All Companies</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($portfolio)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-buildings fs-2"></i>
            <p class="mt-2 mb-3">No client companies yet.</p>
            <a href="<?= url('onboarding') ?>" class="btn btn-sm btn-primary">Add First Client</a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Company</th>
                  <th class="text-center">E</th>
                  <th class="text-center">S</th>
                  <th class="text-center">G</th>
                  <th class="text-center">Overall</th>
                  <th class="text-center">Gaps</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($portfolio as $co): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($co['industry'] ?? '') ?> &bull; <?= htmlspecialchars($co['owner_name'] ?? '') ?></div>
                  </td>
                  <td class="text-center"><div class="esg-mini-bar"><div class="emb-fill bg-success" style="width:<?= $co['env_score'] ?>%"></div></div><small><?= $co['env_score'] ?>%</small></td>
                  <td class="text-center"><div class="esg-mini-bar"><div class="emb-fill bg-info"    style="width:<?= $co['soc_score'] ?>%"></div></div><small><?= $co['soc_score'] ?>%</small></td>
                  <td class="text-center"><div class="esg-mini-bar"><div class="emb-fill bg-purple"  style="width:<?= $co['gov_score'] ?>%"></div></div><small><?= $co['gov_score'] ?>%</small></td>
                  <td class="text-center">
                    <?php $oc = $co['overall_score']; $ocCls = $oc >= 70 ? 'bg-success' : ($oc >= 40 ? 'bg-warning' : 'bg-danger'); ?>
                    <span class="badge <?= $ocCls ?>"><?= $oc ?>%</span>
                  </td>
                  <td class="text-center">
                    <?php if ($co['critical_gaps'] > 0): ?>
                    <span class="badge bg-danger"><?= $co['critical_gaps'] ?> crit</span>
                    <?php else: ?>
                    <span class="text-success small"><i class="bi bi-check-circle"></i></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?= url('companies') ?>?select=<?= $co['id'] ?>" class="btn btn-xs btn-outline-primary">Open</a>
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

    <!-- My Managers -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bi bi-person-gear me-2 text-secondary"></i>My Managers</h5>
          <a href="<?= url('team') ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($managers)): ?>
          <div class="text-center py-4 text-muted small">
            <i class="bi bi-person-plus-fill fs-2 d-block mb-2"></i>
            No managers yet.
            <a href="<?= url('team') ?>" class="d-block mt-2">Add a manager</a>
          </div>
          <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($managers as $mgr): ?>
            <li class="d-flex align-items-center gap-3 p-3 border-bottom">
              <div class="hier-avatar hier-avatar-manager">
                <?= strtoupper(substr($mgr['name'], 0, 1)) ?>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold small"><?= htmlspecialchars($mgr['name']) ?></div>
                <div class="text-muted" style="font-size:0.75rem"><?= $mgr['company_count'] ?> clients &bull; avg <?= $mgr['avg_esg'] ?>% ESG</div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>
