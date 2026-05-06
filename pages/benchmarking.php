<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/Benchmarker.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle = 'Benchmarking';
$period    = (string)($activeCompany['reporting_year'] ?? date('Y'));

$cmp = Benchmarker::buildComparison($activeCompanyId, $activeCompany, $period);

$revLabel = Benchmarker::revenueTierLabel($cmp['revenue_tier']);

// Chart data
$chartLabels = json_encode(['Overall', 'Environment', 'Social', 'Governance']);
$myData      = json_encode([$cmp['my']['overall'], $cmp['my']['env'], $cmp['my']['social'], $cmp['my']['gov']]);
$indData     = json_encode([$cmp['industry_avg']['overall'], $cmp['industry_avg']['env'], $cmp['industry_avg']['social'], $cmp['industry_avg']['gov']]);
$peerData    = $cmp['peer_avg']
    ? json_encode([$cmp['peer_avg']['overall'], $cmp['peer_avg']['env'], $cmp['peer_avg']['social'], $cmp['peer_avg']['gov']])
    : 'null';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-bar-chart-line me-2 text-primary"></i>ESG Benchmarking</h1>
        <span class="topbar-subtitle">
          <?= htmlspecialchars($activeCompany['name']) ?> vs
          <?= htmlspecialchars($cmp['industry']) ?> (<?= $revLabel ?>) &bull; <?= $period ?>
        </span>
      </div>
      <div class="topbar-actions">
        <span class="badge bg-secondary"><?= $cmp['peer_count'] ?> platform peers</span>
      </div>
    </div>

    <div class="content-body">

      <!-- Rank header -->
      <div class="bench-rank-banner mb-4">
        <div class="bench-rank-left">
          <div class="bench-rank-label">Your Industry Ranking</div>
          <?php
          $rankColors = [
              'Top 10%'    => '#16a34a',
              'Top 25%'    => '#0891b2',
              'Average'    => '#d97706',
              'Bottom 25%' => '#ea580c',
              'Bottom 10%' => '#dc2626',
              'N/A'        => '#64748b',
          ];
          $rankColor = $rankColors[$cmp['rank']] ?? '#64748b';
          ?>
          <div class="bench-rank-value" style="color:<?= $rankColor ?>"><?= $cmp['rank'] ?></div>
          <div class="bench-rank-context">
            <?= htmlspecialchars($cmp['industry']) ?> &bull; <?= $revLabel ?> &bull; <?= $activeCompany['framework'] ?>
          </div>
        </div>
        <div class="bench-rank-right">
          <div class="bench-score-big"><?= $cmp['my']['overall'] ?><span class="bench-score-pct">%</span></div>
          <div class="text-muted small">Your ESG Score</div>
        </div>
      </div>

      <div class="row g-4">

        <!-- Left: Comparison bars -->
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header">Score Comparison</div>
            <div class="p-4">

              <?php
              $dimensions = [
                  ['key' => 'overall', 'label' => 'Overall ESG', 'icon' => 'bi-award',       'color' => '#16a34a'],
                  ['key' => 'env',     'label' => 'Environment', 'icon' => 'bi-tree',         'color' => '#0891b2'],
                  ['key' => 'social',  'label' => 'Social',      'icon' => 'bi-people',       'color' => '#7c3aed'],
                  ['key' => 'gov',     'label' => 'Governance',  'icon' => 'bi-shield-check', 'color' => '#d97706'],
              ];
              foreach ($dimensions as $dim):
                $myScore  = $cmp['my'][$dim['key']];
                $indScore = $cmp['industry_avg'][$dim['key']];
                $peerScore= $cmp['peer_avg'][$dim['key']] ?? null;
                $diff     = $myScore - $indScore;
                $diffStr  = ($diff >= 0 ? '+' : '') . round($diff, 1);
                $diffClass= $diff >= 0 ? 'text-success' : 'text-danger';
              ?>
              <div class="bench-dim mb-4">
                <div class="bench-dim-header">
                  <i class="bi <?= $dim['icon'] ?>" style="color:<?= $dim['color'] ?>"></i>
                  <strong><?= $dim['label'] ?></strong>
                  <span class="ms-auto <?= $diffClass ?> small fw-semibold"><?= $diffStr ?>pp vs industry</span>
                </div>

                <div class="bench-bar-group mt-2">
                  <!-- Yours -->
                  <div class="bench-bar-row">
                    <span class="bench-bar-label">You</span>
                    <div class="bench-bar-track">
                      <div class="bench-bar-fill" style="width:<?= $myScore ?>%;background:<?= $dim['color'] ?>"></div>
                    </div>
                    <span class="bench-bar-val fw-bold" style="color:<?= $dim['color'] ?>"><?= $myScore ?>%</span>
                  </div>
                  <!-- Industry avg -->
                  <div class="bench-bar-row">
                    <span class="bench-bar-label text-muted">Industry avg</span>
                    <div class="bench-bar-track">
                      <div class="bench-bar-fill" style="width:<?= $indScore ?>%;background:#94a3b8"></div>
                    </div>
                    <span class="bench-bar-val text-muted"><?= $indScore ?>%</span>
                  </div>
                  <!-- Platform peers -->
                  <?php if ($peerScore !== null): ?>
                  <div class="bench-bar-row">
                    <span class="bench-bar-label text-muted">Platform peers</span>
                    <div class="bench-bar-track">
                      <div class="bench-bar-fill" style="width:<?= $peerScore ?>%;background:#e2e8f0;border:1px solid #94a3b8"></div>
                    </div>
                    <span class="bench-bar-val text-muted"><?= $peerScore ?>%</span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right: Chart + peers table -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header">Radar Comparison</div>
            <div class="p-3" style="height:280px">
              <canvas id="benchChart"></canvas>
            </div>
          </div>

          <?php if ($cmp['peer_count'] > 0): ?>
          <div class="card">
            <div class="card-header">Platform Peers (<?= $cmp['peer_count'] ?> companies, anonymised)</div>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Peer</th>
                    <th class="text-center">Overall</th>
                    <th class="text-center">Env</th>
                    <th class="text-center">Social</th>
                    <th class="text-center">Gov</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($cmp['peers'] as $peer): ?>
                  <tr>
                    <td><?= $peer['label'] ?></td>
                    <td class="text-center fw-semibold"><?= $peer['overall'] ?>%</td>
                    <td class="text-center"><?= $peer['env'] ?>%</td>
                    <td class="text-center"><?= $peer['social'] ?>%</td>
                    <td class="text-center"><?= $peer['gov'] ?>%</td>
                  </tr>
                <?php endforeach; ?>
                  <tr class="table-secondary fw-semibold">
                    <td>Peer Avg</td>
                    <td class="text-center"><?= $cmp['peer_avg']['overall'] ?>%</td>
                    <td class="text-center"><?= $cmp['peer_avg']['env'] ?>%</td>
                    <td class="text-center"><?= $cmp['peer_avg']['social'] ?>%</td>
                    <td class="text-center"><?= $cmp['peer_avg']['gov'] ?>%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="card">
            <div class="card-body text-center text-muted py-4">
              <i class="bi bi-people fs-2 mb-2"></i>
              <p class="mb-1"><strong>No platform peers yet</strong></p>
              <p class="small">Comparison is against published industry averages.<br>Peers appear as other companies on the platform complete their data.</p>
            </div>
          </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- Industry average table -->
      <div class="card mt-4">
        <div class="card-header">
          <i class="bi bi-table me-2"></i>
          Malaysia Industry ESG Averages — <?= htmlspecialchars($cmp['industry']) ?> sector
          <span class="text-muted small ms-2">Source: Bursa Sustainability Report 2023 + market research</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Revenue Tier</th>
                <th class="text-center">Overall</th>
                <th class="text-center">Environment</th>
                <th class="text-center">Social</th>
                <th class="text-center">Governance</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $allTiers = ['below_10M' => '< RM10M', '10M_to_50M' => 'RM10M – RM50M', 'above_50M' => '> RM50M'];
            $indData2 = Benchmarker::INDUSTRY_BENCHMARKS[$cmp['industry']] ?? Benchmarker::INDUSTRY_BENCHMARKS['Other'];
            foreach ($allTiers as $tierKey => $tierLabel):
                $row    = $indData2[$tierKey];
                $isMyTier = $tierKey === $cmp['revenue_tier'];
            ?>
            <tr <?= $isMyTier ? 'class="table-success fw-semibold"' : '' ?>>
              <td>
                <?= $tierLabel ?>
                <?= $isMyTier ? '<span class="badge bg-success ms-1">You</span>' : '' ?>
              </td>
              <td class="text-center"><?= $row['overall'] ?>%</td>
              <td class="text-center"><?= $row['env'] ?>%</td>
              <td class="text-center"><?= $row['social'] ?>%</td>
              <td class="text-center"><?= $row['gov'] ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function() {
  const labels  = <?= $chartLabels ?>;
  const myData  = <?= $myData ?>;
  const indData = <?= $indData ?>;
  const peerData= <?= $peerData ?>;

  const datasets = [
    {
      label: 'Your Score',
      data: myData,
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22,163,74,0.15)',
      pointBackgroundColor: '#16a34a',
      borderWidth: 2,
    },
    {
      label: 'Industry Average',
      data: indData,
      borderColor: '#94a3b8',
      backgroundColor: 'rgba(148,163,184,0.1)',
      pointBackgroundColor: '#94a3b8',
      borderWidth: 1.5,
      borderDash: [4,3],
    },
  ];

  if (peerData) {
    datasets.push({
      label: 'Platform Peers',
      data: peerData,
      borderColor: '#7c3aed',
      backgroundColor: 'rgba(124,58,237,0.08)',
      pointBackgroundColor: '#7c3aed',
      borderWidth: 1.5,
      borderDash: [2,2],
    });
  }

  new Chart(document.getElementById('benchChart'), {
    type: 'bar',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
      },
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
  });
})();
</script>
