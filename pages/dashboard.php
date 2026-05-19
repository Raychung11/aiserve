<?php
require_once __DIR__ . '/../includes/auth_check.php';
$pageTitle = 'Dashboard';
$role      = $currentUser['role'];

// ── Admin: send straight to admin panel ──────────────────────────
if ($role === 'admin') {
    header('Location: ' . APP_URL . '/admin');
    exit;
}

// ── Hierarchy roles: no active-company required ──────────────────
if (in_array($role, ['principal', 'associate', 'manager'])) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="app-layout">';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<div class="main-content">';

    switch ($role) {
        case 'principal': include __DIR__ . '/dashboard_principal.php'; break;
        case 'associate': include __DIR__ . '/dashboard_associate.php'; break;
        case 'manager':   include __DIR__ . '/dashboard_manager.php';   break;
    }

    echo '</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Legacy roles: sme_owner / consultant / admin ─────────────────
if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$framework  = $activeCompany['framework'];
$period     = $activeCompany['reporting_year'];
$stats      = ESGDataManager::getCompletionStats($activeCompanyId, $framework, $period);
$overall    = ESGDataManager::calcOverallScore($stats);
$scoreInfo  = ESGDataManager::scoreLabel($overall);

$gapResult  = GapAnalyzer::loadCached($activeCompanyId, $framework, $period);
if (!$gapResult) {
    $gapResult = GapAnalyzer::analyze($activeCompanyId, $framework, $period);
}

$criticalGaps = count(array_filter($gapResult['gaps'] ?? [], fn($g) => $g['priority'] === 'critical'));
$highGaps     = count(array_filter($gapResult['gaps'] ?? [], fn($g) => $g['priority'] === 'high'));
$quickWins    = array_slice($gapResult['quick_wins'] ?? [], 0, 5);
$finOps       = $gapResult['financing_ops'] ?? [];

$recentReports = Database::fetchAll(
    'SELECT * FROM reports WHERE company_id = ? ORDER BY generated_at DESC LIMIT 3',
    [$activeCompanyId]
);

$apStats   = ActionPlanManager::getStats($activeCompanyId);
$kpiTrend  = KPITracker::getTrendOldestFirst($activeCompanyId, 6);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><?= htmlspecialchars($activeCompany['name']) ?></h1>
        <span class="topbar-subtitle">ESG Dashboard — <?= $period ?></span>
      </div>
      <div class="topbar-actions">
        <span class="framework-pill"><?= $framework === 'BURSA_SEDG' ? 'Bursa SEDG' : ($framework === 'GRI' ? 'GRI' : 'SEDG + GRI') ?></span>
        <a href="<?= url('reports') ?>?action=generate" class="btn btn-primary btn-sm">
          <i class="bi bi-file-earmark-plus me-1"></i>Generate Report
        </a>
      </div>
    </div>

    <?php if (isset($_GET['welcome'])): ?>
    <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
      <i class="bi bi-party-popper-fill me-2"></i>
      <strong>Welcome to Adcellent ESG OS!</strong> Your dashboard is ready.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="content-body">

      <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
          <div class="score-card score-overall" style="--score-color: <?= $scoreInfo['color'] ?>">
            <div class="score-card-icon"><i class="bi bi-award"></i></div>
            <div class="score-card-body">
              <div class="score-num"><?= $overall ?>%</div>
              <div class="score-label">Overall ESG Score</div>
              <div class="score-rating"><?= $scoreInfo['label'] ?></div>
            </div>
            <div class="score-donut">
              <canvas id="overallDonut" width="60" height="60"></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="score-card score-env">
            <div class="score-card-icon"><i class="bi bi-tree"></i></div>
            <div class="score-card-body">
              <div class="score-num text-success"><?= $stats['ENVIRONMENT']['score'] ?>%</div>
              <div class="score-label">Environment</div>
              <div class="score-sub"><?= $stats['ENVIRONMENT']['completed'] ?>/<?= $stats['ENVIRONMENT']['total'] ?> indicators</div>
            </div>
            <div class="score-bar-wrap">
              <div class="score-bar"><div class="score-bar-fill bg-success" style="width:<?= $stats['ENVIRONMENT']['score'] ?>%"></div></div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="score-card score-social">
            <div class="score-card-icon"><i class="bi bi-people"></i></div>
            <div class="score-card-body">
              <div class="score-num text-info"><?= $stats['SOCIAL']['score'] ?>%</div>
              <div class="score-label">Social</div>
              <div class="score-sub"><?= $stats['SOCIAL']['completed'] ?>/<?= $stats['SOCIAL']['total'] ?> indicators</div>
            </div>
            <div class="score-bar-wrap">
              <div class="score-bar"><div class="score-bar-fill bg-info" style="width:<?= $stats['SOCIAL']['score'] ?>%"></div></div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="score-card score-gov">
            <div class="score-card-icon"><i class="bi bi-shield-check"></i></div>
            <div class="score-card-body">
              <div class="score-num text-purple"><?= $stats['GOVERNANCE']['score'] ?>%</div>
              <div class="score-label">Governance</div>
              <div class="score-sub"><?= $stats['GOVERNANCE']['completed'] ?>/<?= $stats['GOVERNANCE']['total'] ?> indicators</div>
            </div>
            <div class="score-bar-wrap">
              <div class="score-bar"><div class="score-bar-fill bg-purple" style="width:<?= $stats['GOVERNANCE']['score'] ?>%"></div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-radar me-2 text-success"></i>ESG Profile</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
              <canvas id="esgRadar" width="280" height="280"></canvas>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Gap Summary</h5>
              <a href="<?= url('gap-analysis') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
              <div class="gap-stat gap-critical">
                <div class="gap-count"><?= $criticalGaps ?></div>
                <div class="gap-label">Critical Gaps</div>
              </div>
              <div class="gap-stat gap-high">
                <div class="gap-count"><?= $highGaps ?></div>
                <div class="gap-label">High Priority</div>
              </div>
              <div class="gap-stat gap-total">
                <div class="gap-count"><?= $gapResult['gaps_count'] ?? 0 ?></div>
                <div class="gap-label">Total Gaps</div>
              </div>
              <div class="mt-3">
                <div class="d-flex justify-content-between small mb-1">
                  <span>Completion Progress</span>
                  <strong><?= $gapResult['completed'] ?? 0 ?>/<?= $gapResult['total_indicators'] ?? 0 ?></strong>
                </div>
                <div class="progress" style="height:12px">
                  <div class="progress-bar bg-success" style="width:<?= $overall ?>%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-lightning-fill me-2 text-warning"></i>Quick Wins</h5>
            </div>
            <div class="card-body p-0">
              <?php if (empty($quickWins)): ?>
              <div class="text-center py-4 text-success">
                <i class="bi bi-check-circle-fill fs-2"></i>
                <p class="mt-2 mb-0">All quick wins completed!</p>
              </div>
              <?php else: ?>
              <ul class="quick-win-list">
                <?php foreach ($quickWins as $qw): ?>
                <li class="quick-win-item">
                  <div class="qw-code"><?= htmlspecialchars($qw['indicator']['code']) ?></div>
                  <div class="qw-body">
                    <div class="qw-name"><?= htmlspecialchars($qw['indicator']['name']) ?></div>
                    <div class="qw-effort"><i class="bi bi-clock me-1"></i><?= $qw['effort'] ?></div>
                  </div>
                  <a href="<?= url('data-entry') ?>?cat=<?= strtolower($qw['indicator']['category']) ?>&focus=<?= $qw['indicator']['indicator_id'] ?>"
                     class="btn btn-xs btn-outline-success">Fix</a>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>ESG Score Trend</h5>
              <a href="<?= url('kpi-trends') ?>" class="btn btn-sm btn-outline-primary">Full Report</a>
            </div>
            <div class="card-body">
              <?php if (count($kpiTrend) < 2): ?>
              <div class="text-center text-muted py-4">
                <i class="bi bi-bar-chart fs-2"></i>
                <p class="mt-2 mb-0 small">Not enough data yet. Score trend will appear after 2+ monthly snapshots.</p>
                <a href="<?= url('kpi-trends') ?>" class="btn btn-sm btn-outline-primary mt-2">Take First Snapshot</a>
              </div>
              <?php else: ?>
              <canvas id="kpiTrendChart" height="120"></canvas>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0"><i class="bi bi-kanban me-2 text-warning"></i>Action Plans</h5>
              <a href="<?= url('action-plans') ?>" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="text-center p-2 rounded" style="background:#fff7ed">
                    <div class="fw-700 fs-4 text-warning"><?= $apStats['open'] ?></div>
                    <div class="small text-muted">Open</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-2 rounded" style="background:#eff6ff">
                    <div class="fw-700 fs-4 text-primary"><?= $apStats['in_progress'] ?></div>
                    <div class="small text-muted">In Progress</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-2 rounded" style="background:#f0fdf4">
                    <div class="fw-700 fs-4 text-success"><?= $apStats['completed'] ?></div>
                    <div class="small text-muted">Completed</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-2 rounded" style="background:#fef2f2">
                    <div class="fw-700 fs-4 text-danger"><?= $apStats['overdue'] ?></div>
                    <div class="small text-muted">Overdue</div>
                  </div>
                </div>
              </div>
              <a href="<?= url('action-plans') ?>?action=new" class="btn btn-sm btn-warning w-100">
                <i class="bi bi-plus-circle me-1"></i>Create Action Plan
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-currency-dollar me-2 text-success"></i>Green Financing Opportunities</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr><th>Programme</th><th>Benefit</th><th>Status</th><th>Action Required</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($finOps as $op): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($op['name']) ?></strong></td>
                      <td><?= htmlspecialchars($op['benefit']) ?></td>
                      <td>
                        <?php if ($op['status'] === 'eligible'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Eligible Now</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Action Required</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted small"><?= htmlspecialchars($op['requirement']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12">
          <h6 class="text-muted fw-semibold mb-3">CONTINUE DATA ENTRY</h6>
        </div>
        <?php
        $cats = [
            'environment' => ['label' => 'Environment', 'icon' => 'bi-tree',         'color' => 'success', 'stat' => $stats['ENVIRONMENT']],
            'social'      => ['label' => 'Social',       'icon' => 'bi-people',       'color' => 'info',    'stat' => $stats['SOCIAL']],
            'governance'  => ['label' => 'Governance',   'icon' => 'bi-shield-check', 'color' => 'purple',  'stat' => $stats['GOVERNANCE']],
        ];
        foreach ($cats as $catKey => $cat):
            $pct = $cat['stat']['score'];
        ?>
        <div class="col-md-4">
          <a href="<?= url('data-entry') ?>?cat=<?= $catKey ?>" class="data-entry-card">
            <div class="de-icon text-<?= $cat['color'] ?>"><i class="bi <?= $cat['icon'] ?>"></i></div>
            <div class="de-body">
              <div class="de-title"><?= $cat['label'] ?></div>
              <div class="de-progress">
                <div class="progress" style="height:6px">
                  <div class="progress-bar bg-<?= $cat['color'] ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <span><?= $cat['stat']['completed'] ?>/<?= $cat['stat']['total'] ?> done</span>
              </div>
            </div>
            <i class="bi bi-chevron-right de-arrow"></i>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const radarCtx = document.getElementById('esgRadar').getContext('2d');
new Chart(radarCtx, {
  type: 'radar',
  data: {
    labels: ['Environment', 'Social', 'Governance'],
    datasets: [{
      label: 'ESG Score (%)',
      data: [<?= $stats['ENVIRONMENT']['score'] ?>, <?= $stats['SOCIAL']['score'] ?>, <?= $stats['GOVERNANCE']['score'] ?>],
      backgroundColor: 'rgba(22,163,74,0.15)', borderColor: '#16a34a',
      pointBackgroundColor: '#16a34a', borderWidth: 2, pointRadius: 5,
    }]
  },
  options: {
    scales: { r: { min: 0, max: 100, ticks: { stepSize: 25, font: { size: 10 } } } },
    plugins: { legend: { display: false } }, animation: { duration: 800 }
  }
});
const donutCtx = document.getElementById('overallDonut').getContext('2d');
new Chart(donutCtx, {
  type: 'doughnut',
  data: {
    datasets: [{ data: [<?= $overall ?>, <?= 100 - $overall ?>],
      backgroundColor: ['<?= $scoreInfo['color'] ?>', '#e2e8f0'], borderWidth: 0 }]
  },
  options: {
    cutout: '72%',
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    animation: { duration: 1000 }
  }
});
<?php if (count($kpiTrend) >= 2): ?>
const kpiLabels = <?= json_encode(array_map(fn($r) => date('M Y', mktime(0,0,0,$r['month'],1,$r['year'])), $kpiTrend)) ?>;
const kpiOverall = <?= json_encode(array_map(fn($r) => (float)$r['overall_score'], $kpiTrend)) ?>;
const kpiE = <?= json_encode(array_map(fn($r) => (float)$r['e_score'], $kpiTrend)) ?>;
const kpiS = <?= json_encode(array_map(fn($r) => (float)$r['s_score'], $kpiTrend)) ?>;
const kpiG = <?= json_encode(array_map(fn($r) => (float)$r['g_score'], $kpiTrend)) ?>;
new Chart(document.getElementById('kpiTrendChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: kpiLabels,
    datasets: [
      { label: 'Overall', data: kpiOverall, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', tension: 0.3, borderWidth: 2, pointRadius: 3, fill: true },
      { label: 'E',       data: kpiE,       borderColor: '#16a34a', backgroundColor: 'transparent', tension: 0.3, borderWidth: 1.5, pointRadius: 2, borderDash: [4,3] },
      { label: 'S',       data: kpiS,       borderColor: '#0ea5e9', backgroundColor: 'transparent', tension: 0.3, borderWidth: 1.5, pointRadius: 2, borderDash: [4,3] },
      { label: 'G',       data: kpiG,       borderColor: '#f59e0b', backgroundColor: 'transparent', tension: 0.3, borderWidth: 1.5, pointRadius: 2, borderDash: [4,3] },
    ]
  },
  options: {
    responsive: true, interaction: { mode: 'index', intersect: false },
    scales: { y: { min: 0, max: 100, ticks: { stepSize: 25, font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } },
    plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
    animation: { duration: 800 }
  }
});
<?php endif; ?>
</script>
