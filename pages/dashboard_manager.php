<?php
/**
 * Manager dashboard — assigned companies task view
 * Loaded by dashboard.php; $currentUser is already in scope
 */
$companies = Hierarchy::getDirectCompanies($currentUser['id']);
$portfolio = Hierarchy::getPortfolioSummary($companies);

$totalCompanies  = count($companies);
$totalGaps       = array_sum(array_column($portfolio, 'critical_gaps'));
$completedAll    = array_filter($portfolio, fn($c) => $c['overall_score'] >= 80);
$pendingCount    = $totalCompanies - count($completedAll);

// Build priority task list: companies with critical gaps, sorted by score asc
$taskList = array_filter($portfolio, fn($c) => $c['critical_gaps'] > 0 || $c['overall_score'] < 50);
usort($taskList, fn($a, $b) => $a['overall_score'] <=> $b['overall_score']);
?>

<!-- Topbar -->
<div class="topbar">
  <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
  <div class="topbar-title">
    <h1><i class="bi bi-clipboard2-check-fill me-2 text-success"></i>My Assignments</h1>
    <span class="topbar-subtitle">Assigned companies — <?= htmlspecialchars($currentUser['name']) ?></span>
  </div>
  <div class="topbar-actions">
    <a href="<?= url('onboarding') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus me-1"></i>New Company
    </a>
  </div>
</div>

<div class="content-body">

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-success-soft"><i class="bi bi-buildings text-success"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalCompanies ?></div>
          <div class="hier-kpi-label">Assigned Companies</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-warning-soft"><i class="bi bi-hourglass-split text-warning"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $pendingCount ?></div>
          <div class="hier-kpi-label">Need Attention</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-danger-soft"><i class="bi bi-exclamation-diamond-fill text-danger"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= $totalGaps ?></div>
          <div class="hier-kpi-label">Critical Gaps</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="hier-kpi-card">
        <div class="hier-kpi-icon bg-success-soft"><i class="bi bi-check-circle-fill text-success"></i></div>
        <div class="hier-kpi-body">
          <div class="hier-kpi-num"><?= count($completedAll) ?></div>
          <div class="hier-kpi-label">Completed (&ge;80%)</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">

    <!-- Priority tasks -->
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title"><i class="bi bi-list-check me-2 text-warning"></i>Priority Actions</h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($taskList)): ?>
          <div class="text-center py-5 text-success">
            <i class="bi bi-trophy-fill fs-2"></i>
            <p class="mt-2 mb-0 fw-semibold">All companies on track!</p>
          </div>
          <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($taskList, 0, 8) as $co):
              $urgency = $co['critical_gaps'] > 0 ? 'danger' : 'warning';
            ?>
            <li class="p-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold small"><?= htmlspecialchars($co['name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem">
                    <?php if ($co['critical_gaps'] > 0): ?>
                    <span class="text-danger"><i class="bi bi-exclamation-diamond-fill me-1"></i><?= $co['critical_gaps'] ?> critical gaps</span>
                    <?php else: ?>
                    <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Below 50% completion</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="d-flex gap-1">
                  <span class="badge bg-<?= $urgency ?>"><?= $co['overall_score'] ?>%</span>
                  <a href="<?= url('companies') ?>?select=<?= $co['id'] ?>" class="btn btn-xs btn-outline-primary">Open</a>
                </div>
              </div>
              <!-- E/S/G mini bars -->
              <div class="d-flex gap-2 mt-2">
                <div class="flex-fill">
                  <div class="d-flex justify-content-between" style="font-size:0.7rem"><span class="text-success">E</span><span><?= $co['env_score'] ?>%</span></div>
                  <div class="esg-mini-bar"><div class="emb-fill bg-success" style="width:<?= $co['env_score'] ?>%"></div></div>
                </div>
                <div class="flex-fill">
                  <div class="d-flex justify-content-between" style="font-size:0.7rem"><span class="text-info">S</span><span><?= $co['soc_score'] ?>%</span></div>
                  <div class="esg-mini-bar"><div class="emb-fill bg-info" style="width:<?= $co['soc_score'] ?>%"></div></div>
                </div>
                <div class="flex-fill">
                  <div class="d-flex justify-content-between" style="font-size:0.7rem"><span class="text-purple">G</span><span><?= $co['gov_score'] ?>%</span></div>
                  <div class="esg-mini-bar"><div class="emb-fill bg-purple" style="width:<?= $co['gov_score'] ?>%"></div></div>
                </div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- All assigned companies -->
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bi bi-table me-2 text-muted"></i>All Assignments (<?= $totalCompanies ?>)</h5>
          <a href="<?= url('companies') ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($portfolio)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-buildings fs-2"></i>
            <p class="mt-2 mb-3">No companies assigned yet.</p>
            <a href="<?= url('onboarding') ?>" class="btn btn-sm btn-primary">Set Up Company</a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle small">
              <thead>
                <tr>
                  <th>Company</th>
                  <th class="text-center">E</th>
                  <th class="text-center">S</th>
                  <th class="text-center">G</th>
                  <th class="text-center">Score</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($portfolio as $co):
                  $oc  = $co['overall_score'];
                  $cls = $oc >= 70 ? 'bg-success' : ($oc >= 40 ? 'bg-warning' : 'bg-danger');
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($co['industry'] ?? '') ?></div>
                  </td>
                  <td class="text-center">
                    <div class="esg-mini-bar"><div class="emb-fill bg-success" style="width:<?= $co['env_score'] ?>%"></div></div>
                    <span style="font-size:0.7rem"><?= $co['env_score'] ?>%</span>
                  </td>
                  <td class="text-center">
                    <div class="esg-mini-bar"><div class="emb-fill bg-info" style="width:<?= $co['soc_score'] ?>%"></div></div>
                    <span style="font-size:0.7rem"><?= $co['soc_score'] ?>%</span>
                  </td>
                  <td class="text-center">
                    <div class="esg-mini-bar"><div class="emb-fill bg-purple" style="width:<?= $co['gov_score'] ?>%"></div></div>
                    <span style="font-size:0.7rem"><?= $co['gov_score'] ?>%</span>
                  </td>
                  <td class="text-center"><span class="badge <?= $cls ?>"><?= $oc ?>%</span></td>
                  <td>
                    <a href="<?= url('companies') ?>?select=<?= $co['id'] ?>" class="btn btn-xs btn-outline-primary">Open</a>
                    <a href="<?= url('data-entry') ?>?cat=environment&company=<?= $co['id'] ?>" class="btn btn-xs btn-outline-success ms-1">Entry</a>
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
  </div>
</div>
