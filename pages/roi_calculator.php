<?php
require_once __DIR__.'/../includes/auth_check.php';

$pageTitle = 'ROI Calculator';

// Pre-fill from URL params (e.g. when linked from a property)
$prefill = [
    'purchase_price'  => (float)($_GET['purchase_price'] ?? 0),
    'total_invest'    => (float)($_GET['total_invest'] ?? 0),
    'monthly_revenue' => (float)($_GET['monthly_revenue'] ?? 0),
    'monthly_expense' => (float)($_GET['monthly_expense'] ?? 0),
    'monthly_loan'    => (float)($_GET['monthly_loan'] ?? 0),
    'occ_rate'        => (float)($_GET['occ_rate'] ?? 75),
    'annual_growth'   => (float)($_GET['annual_growth'] ?? 3),
];

$extraJs = <<<'JS'
function fmt(n) {
  return 'RM ' + Math.round(n).toLocaleString('en-MY');
}

function calcROI() {
  const purchase    = parseFloat(document.getElementById('purchase_price').value) || 0;
  const setupCosts  = parseFloat(document.getElementById('setup_costs').value) || 0;
  const monthlyRev  = parseFloat(document.getElementById('monthly_revenue').value) || 0;
  const monthlyExp  = parseFloat(document.getElementById('monthly_expense').value) || 0;
  const monthlyLoan = parseFloat(document.getElementById('monthly_loan').value) || 0;
  const occRate     = parseFloat(document.getElementById('occ_rate').value) || 75;
  const annualGrowth = parseFloat(document.getElementById('annual_growth').value) || 3;

  const totalInvest   = purchase + setupCosts;
  const adjMonthlyRev = monthlyRev * (occRate / 100);
  const monthlyNet    = adjMonthlyRev - monthlyExp - monthlyLoan;
  const annualNet     = monthlyNet * 12;
  const roiPct        = totalInvest > 0 ? (annualNet / totalInvest * 100) : 0;
  const payback       = annualNet > 0 ? (totalInvest / annualNet) : null;

  document.getElementById('res_total_invest').textContent   = fmt(totalInvest);
  document.getElementById('res_adj_revenue').textContent    = fmt(adjMonthlyRev) + '/mo';
  document.getElementById('res_monthly_net').textContent    = fmt(monthlyNet) + '/mo';
  document.getElementById('res_annual_net').textContent     = fmt(annualNet) + '/yr';
  document.getElementById('res_roi_pct').textContent        = roiPct.toFixed(1) + '%';
  document.getElementById('res_payback').textContent        = payback ? payback.toFixed(1) + ' years' : '—';

  const roiEl = document.getElementById('res_roi_pct');
  roiEl.className = 'stat-value ' + (roiPct >= 10 ? 'text-success' : roiPct >= 6 ? 'text-warning' : 'text-danger');
  const netEl = document.getElementById('res_monthly_net');
  netEl.className = 'stat-value ' + (monthlyNet >= 0 ? 'text-success' : 'text-danger');
  const annEl = document.getElementById('res_annual_net');
  annEl.className = 'stat-value ' + (annualNet >= 0 ? 'text-success' : 'text-danger');

  let rateLabel = 'Poor';
  if (roiPct >= 15) rateLabel = 'Excellent';
  else if (roiPct >= 10) rateLabel = 'Good';
  else if (roiPct >= 6) rateLabel = 'Moderate';
  else if (roiPct >= 3) rateLabel = 'Low';
  document.getElementById('res_roi_label').textContent = rateLabel;

  // 10-year projection chart
  const years   = [];
  const cumNet  = [];
  const cumInvest = [];
  let cumulative = -totalInvest;
  for (let y = 0; y <= 10; y++) {
    years.push('Year ' + y);
    cumInvest.push(-totalInvest);
    const yearlyNet = annualNet * Math.pow(1 + annualGrowth/100, y);
    if (y > 0) cumulative += yearlyNet;
    cumNet.push(Math.round(cumulative));
  }

  if (window.projChart) window.projChart.destroy();
  const ctx = document.getElementById('projectionChart').getContext('2d');
  window.projChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: years,
      datasets: [
        {
          label: 'Cumulative Net Position (RM)',
          data: cumNet,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 4,
        },
        {
          label: 'Initial Investment (RM)',
          data: cumInvest,
          borderColor: '#ef4444',
          borderDash: [6,3],
          fill: false,
          pointRadius: 0,
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: ctx => ctx.dataset.label + ': RM ' + Math.round(ctx.raw).toLocaleString('en-MY')
          }
        }
      },
      scales: {
        y: {
          ticks: {
            callback: v => 'RM ' + (v/1000).toFixed(0) + 'k'
          }
        }
      }
    }
  });

  // Breakeven year highlight
  let breakevenYear = null;
  for (let i = 1; i < cumNet.length; i++) {
    if (cumNet[i] >= 0 && cumNet[i-1] < 0) { breakevenYear = i; break; }
  }
  const beEl = document.getElementById('res_breakeven');
  if (beEl) beEl.textContent = breakevenYear ? 'Year ' + breakevenYear : (cumNet[10] >= 0 ? '< 1 Year' : '> 10 Years');
}

// Trigger on all input changes
document.querySelectorAll('.calc-input').forEach(el => el.addEventListener('input', calcROI));

// Run on load
calcROI();
JS;

include __DIR__.'/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">ROI Calculator</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Estimate returns before you invest — no property data required</p>
  </div>
  <a href="<?= APP_URL ?>/investment" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart me-1"></i>My Investments</a>
</div>

<div class="row g-4">
  <!-- Inputs -->
  <div class="col-lg-5">
    <div class="card-box">
      <h6 class="fw-semibold mb-3 pb-2 border-bottom">Property & Investment</h6>
      <div class="row g-3 mb-4">
        <div class="col-12">
          <label class="form-label fw-semibold">Purchase Price (RM)</label>
          <input type="number" id="purchase_price" class="form-control calc-input" value="<?= $prefill['purchase_price'] ?: 400000 ?>" min="0" step="1000">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Setup Costs (RM) <span class="text-muted fw-normal" style="font-size:.75rem;">reno + furniture + legal</span></label>
          <input type="number" id="setup_costs" class="form-control calc-input" value="<?= $prefill['total_invest'] ?: 50000 ?>" min="0" step="1000">
        </div>
      </div>

      <h6 class="fw-semibold mb-3 pb-2 border-bottom">Monthly Cashflow</h6>
      <div class="row g-3 mb-4">
        <div class="col-12">
          <label class="form-label fw-semibold">Expected Monthly Revenue (RM) <span class="text-muted fw-normal" style="font-size:.75rem;">at 100% occ.</span></label>
          <input type="number" id="monthly_revenue" class="form-control calc-input" value="<?= $prefill['monthly_revenue'] ?: 4500 ?>" min="0" step="100">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Monthly Operating Expenses (RM)</label>
          <input type="number" id="monthly_expense" class="form-control calc-input" value="<?= $prefill['monthly_expense'] ?: 800 ?>" min="0" step="100">
          <div class="form-text">Utilities, management, maintenance, quit rent, etc.</div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Monthly Loan Repayment (RM) <span class="text-muted fw-normal" style="font-size:.75rem;">0 if freehold / cash</span></label>
          <input type="number" id="monthly_loan" class="form-control calc-input" value="<?= $prefill['monthly_loan'] ?: 1800 ?>" min="0" step="50">
        </div>
      </div>

      <h6 class="fw-semibold mb-3 pb-2 border-bottom">Assumptions</h6>
      <div class="row g-3">
        <div class="col-6">
          <label class="form-label fw-semibold">Occupancy Rate (%)</label>
          <input type="number" id="occ_rate" class="form-control calc-input" value="<?= $prefill['occ_rate'] ?: 75 ?>" min="0" max="100" step="5">
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">Annual Growth (%)</label>
          <input type="number" id="annual_growth" class="form-control calc-input" value="<?= $prefill['annual_growth'] ?: 3 ?>" min="0" step="0.5">
          <div class="form-text">Revenue growth per year</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Results -->
  <div class="col-lg-7">
    <div class="row g-3 mb-3">
      <div class="col-6">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Total Capital</div>
          <div class="stat-value" id="res_total_invest">RM 0</div>
          <div class="stat-label">invested</div>
        </div>
      </div>
      <div class="col-6">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Adj. Monthly Revenue</div>
          <div class="stat-value text-success" id="res_adj_revenue">RM 0/mo</div>
          <div class="stat-label">at occupancy rate</div>
        </div>
      </div>
      <div class="col-6">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Monthly Net Profit</div>
          <div class="stat-value text-success" id="res_monthly_net">RM 0/mo</div>
          <div class="stat-label">after expenses & loan</div>
        </div>
      </div>
      <div class="col-6">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Annual Net Profit</div>
          <div class="stat-value text-success" id="res_annual_net">RM 0/yr</div>
          <div class="stat-label">per year</div>
        </div>
      </div>
      <div class="col-4">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Annual ROI</div>
          <div class="stat-value text-success" id="res_roi_pct">0%</div>
          <div class="stat-label" id="res_roi_label">—</div>
        </div>
      </div>
      <div class="col-4">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Payback Period</div>
          <div class="stat-value" id="res_payback">—</div>
          <div class="stat-label">to recoup capital</div>
        </div>
      </div>
      <div class="col-4">
        <div class="card-box text-center">
          <div class="text-muted mb-1" style="font-size:.75rem;">Breakeven</div>
          <div class="stat-value" id="res_breakeven">—</div>
          <div class="stat-label">cumulative positive</div>
        </div>
      </div>
    </div>

    <!-- 10-Year Projection Chart -->
    <div class="card-box">
      <h6 class="fw-semibold mb-3">10-Year Net Position Projection</h6>
      <canvas id="projectionChart" height="200"></canvas>
      <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">
        Assumes constant monthly cashflow with annual revenue growth applied. Does not account for property appreciation or tax.
      </p>
    </div>
  </div>
</div>

<!-- Quick Presets -->
<div class="card-box mt-4">
  <h6 class="fw-semibold mb-3">Quick Presets</h6>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-outline-secondary btn-sm" onclick="loadPreset('kl_condo')">KL Condo STR</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="loadPreset('penang_str')">Penang STR</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="loadPreset('mid_term')">Mid-Term Rental</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="loadPreset('sublet')">Room Sublet</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="loadPreset('corporate')">Corporate Lease</button>
  </div>
</div>

<?php
$extraJs .= <<<'JS'

const presets = {
  kl_condo:  { purchase_price: 550000, setup_costs: 65000, monthly_revenue: 5500, monthly_expense: 900, monthly_loan: 2200, occ_rate: 72, annual_growth: 3 },
  penang_str:{ purchase_price: 380000, setup_costs: 50000, monthly_revenue: 4200, monthly_expense: 700, monthly_loan: 1500, occ_rate: 68, annual_growth: 4 },
  mid_term:  { purchase_price: 420000, setup_costs: 40000, monthly_revenue: 3200, monthly_expense: 400, monthly_loan: 1700, occ_rate: 90, annual_growth: 2 },
  sublet:    { purchase_price: 300000, setup_costs: 30000, monthly_revenue: 2800, monthly_expense: 300, monthly_loan: 1200, occ_rate: 95, annual_growth: 2 },
  corporate: { purchase_price: 700000, setup_costs: 80000, monthly_revenue: 6000, monthly_expense: 800, monthly_loan: 2800, occ_rate: 95, annual_growth: 3 },
};
function loadPreset(key) {
  const p = presets[key];
  if (!p) return;
  Object.keys(p).forEach(f => {
    const el = document.getElementById(f);
    if (el) el.value = p[f];
  });
  calcROI();
}
JS;

include __DIR__.'/../includes/footer.php';
