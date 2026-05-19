<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/KPITracker.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding'); exit;
}

$pageTitle = 'KPI Trends';
$companyId = $activeCompanyId;

// Snap current month on page load (lightweight upsert)
KPITracker::snapshot($companyId, $activeCompany['framework'], (int)date('Y'), (int)date('n'));

$trend     = KPITracker::getTrendOldestFirst($companyId, 12);
$latest    = KPITracker::getLatest($companyId);
$momChange = KPITracker::getMoMChange($companyId);
$chartData = KPITracker::chartData($trend);

include __DIR__ . '/../includes/header.php';
?>
<style>
.kpi-stat-row { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:28px; }
.kpi-stat { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:18px 22px;
            flex:1; min-width:130px; }
.kpi-stat-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase;
                  letter-spacing:.05em; margin-bottom:6px; }
.kpi-stat-value { font-size:28px; font-weight:900; color:#0f172a; line-height:1; }
.kpi-stat-value.green  { color:#16a34a; }
.kpi-stat-value.blue   { color:#0ea5e9; }
.kpi-stat-value.purple { color:#7c3aed; }
.kpi-stat-value.amber  { color:#d97706; }
.kpi-mom { font-size:12px; margin-top:6px; font-weight:600; }
.kpi-mom.up   { color:#16a34a; }
.kpi-mom.down { color:#dc2626; }
.kpi-mom.flat { color:#94a3b8; }

.chart-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:22px; margin-bottom:20px; }
.chart-card h5 { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:18px; }

.snapshot-table th { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase;
                     background:#f8fafc; padding:10px 14px; border-bottom:1.5px solid #e2e8f0; }
.snapshot-table td { font-size:13px; padding:10px 14px; border-bottom:1px solid #f1f5f9; }
</style>

<div class="app-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title">KPI Trends</div>
      <div class="topbar-sub"><?= htmlspecialchars($activeCompany['name']) ?> — last 12 months</div>
    </div>
  </div>
  <div class="content-body">

    <?php if (empty($trend)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-graph-up" style="font-size:3rem;opacity:.3"></i>
      <p class="mt-3">No trend data yet. Enter some ESG data and revisit this page monthly.</p>
    </div>
    <?php else: ?>

    <!-- KPI stat cards -->
    <div class="kpi-stat-row">
      <div class="kpi-stat">
        <div class="kpi-stat-label">Overall ESG Score</div>
        <div class="kpi-stat-value"><?= number_format((float)($latest['overall_score'] ?? 0), 1) ?><small style="font-size:16px">%</small></div>
        <?php if ($momChange !== null): ?>
        <div class="kpi-mom <?= $momChange > 0 ? 'up' : ($momChange < 0 ? 'down' : 'flat') ?>">
          <i class="bi bi-arrow-<?= $momChange > 0 ? 'up' : ($momChange < 0 ? 'down' : 'right') ?>-short"></i>
          <?= $momChange > 0 ? '+' : '' ?><?= $momChange ?>% vs last month
        </div>
        <?php endif; ?>
      </div>
      <div class="kpi-stat">
        <div class="kpi-stat-label">Environment</div>
        <div class="kpi-stat-value green"><?= number_format((float)($latest['e_score'] ?? 0), 1) ?>%</div>
      </div>
      <div class="kpi-stat">
        <div class="kpi-stat-label">Social</div>
        <div class="kpi-stat-value blue"><?= number_format((float)($latest['s_score'] ?? 0), 1) ?>%</div>
      </div>
      <div class="kpi-stat">
        <div class="kpi-stat-label">Governance</div>
        <div class="kpi-stat-value purple"><?= number_format((float)($latest['g_score'] ?? 0), 1) ?>%</div>
      </div>
      <div class="kpi-stat">
        <div class="kpi-stat-label">Data Completion</div>
        <div class="kpi-stat-value amber"><?= number_format((float)($latest['data_completion'] ?? 0), 1) ?>%</div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px">
          <?= (int)($latest['indicators_filled'] ?? 0) ?> / <?= (int)($latest['indicators_total'] ?? 0) ?> indicators
        </div>
      </div>
    </div>

    <!-- ESG Score trend chart -->
    <div class="chart-card">
      <h5><i class="bi bi-graph-up-arrow me-2 text-success"></i>ESG Score Trend</h5>
      <canvas id="scoreChart" height="80"></canvas>
    </div>

    <!-- Carbon trend chart -->
    <?php
    $hasCarbon = array_sum(array_column($trend, 'carbon_scope1')) +
                 array_sum(array_column($trend, 'carbon_scope2')) > 0;
    ?>
    <?php if ($hasCarbon): ?>
    <div class="chart-card">
      <h5><i class="bi bi-cloud me-2 text-warning"></i>Carbon Emissions Trend (tCO₂e)</h5>
      <canvas id="carbonChart" height="80"></canvas>
    </div>
    <?php endif; ?>

    <!-- Data table -->
    <div class="chart-card">
      <h5><i class="bi bi-table me-2"></i>Monthly Snapshot History</h5>
      <div class="table-responsive">
        <table class="table snapshot-table">
          <thead>
            <tr>
              <th>Month</th>
              <th>Overall</th>
              <th>Environment</th>
              <th>Social</th>
              <th>Governance</th>
              <th>Completion</th>
              <th>Carbon (tCO₂e)</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach (array_reverse($trend) as $row): ?>
            <tr>
              <td><?= date('M Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])) ?></td>
              <td><strong><?= number_format($row['overall_score'], 1) ?>%</strong></td>
              <td><?= number_format($row['e_score'], 1) ?>%</td>
              <td><?= number_format($row['s_score'], 1) ?>%</td>
              <td><?= number_format($row['g_score'], 1) ?>%</td>
              <td><?= number_format($row['data_completion'], 1) ?>%</td>
              <td><?= number_format($row['carbon_scope1'] + $row['carbon_scope2'] + $row['carbon_scope3'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>

  </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if (!empty($trend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const labels  = <?= json_encode($chartData['labels']) ?>;
const overall = <?= json_encode($chartData['overall']) ?>;
const eScores = <?= json_encode($chartData['eScores']) ?>;
const sScores = <?= json_encode($chartData['sScores']) ?>;
const gScores = <?= json_encode($chartData['gScores']) ?>;
const carbon  = <?= json_encode($chartData['carbon']) ?>;

new Chart(document.getElementById('scoreChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Overall', data: overall, borderColor: '#0f172a', backgroundColor: 'rgba(15,23,42,.05)',
        borderWidth: 3, pointRadius: 4, tension: .35, fill: true },
      { label: 'Environment', data: eScores, borderColor: '#16a34a', borderWidth: 2, pointRadius: 3, tension: .35 },
      { label: 'Social',      data: sScores, borderColor: '#0ea5e9', borderWidth: 2, pointRadius: 3, tension: .35 },
      { label: 'Governance',  data: gScores, borderColor: '#7c3aed', borderWidth: 2, pointRadius: 3, tension: .35 },
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } }
  }
});

<?php if ($hasCarbon): ?>
new Chart(document.getElementById('carbonChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{ label: 'Total tCO₂e', data: carbon, backgroundColor: '#fbbf24', borderRadius: 6 }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
<?php endif; ?>
</script>
<?php endif; ?>
