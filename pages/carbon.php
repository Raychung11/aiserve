<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/CarbonCalculator.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle = 'Carbon Calculator';
$success   = $error = '';
$result    = null;
$period    = (string)($activeCompany['reporting_year'] ?? date('Y'));

$numericFields = [
    'electricity_kwh','diesel_litre','petrol_litre','natural_gas_m3',
    'lpg_kg','cng_m3','fleet_diesel_km','fleet_petrol_km',
    'flight_domestic_km','flight_intl_km','waste_landfill_kg',
    'waste_recycled_kg','water_m3',
];

$inputs = array_fill_keys($numericFields, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh.';
    } else {
        $period = $_POST['period'] ?? $period;
        foreach ($numericFields as $f) {
            $inputs[$f] = max(0, (float)($_POST[$f] ?? 0));
        }
        $result = CarbonCalculator::calculate($inputs);

        if (!empty($_POST['save_to_esg'])) {
            CarbonCalculator::saveToESG($activeCompanyId, $result, $activeCompany['framework'], $period, $currentUser['id']);
            $success = 'GHG emission data saved to your ESG indicators.';
        }
    }
}

$benchmark = CarbonCalculator::getIntensityBenchmark($activeCompany['industry']);
$empCount  = max(1, (int)($activeCompany['employee_count'] ?? 1));

// Emission factors for live JS calculation
$efJS = json_encode(CarbonCalculator::FACTORS);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-calculator me-2 text-primary"></i>Carbon Calculator</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars($activeCompany['name']) ?> &bull; Scope 1, 2 &amp; 3</span>
      </div>
      <div class="topbar-actions">
        <span class="framework-pill"><?= htmlspecialchars($activeCompany['framework']) ?></span>
      </div>
    </div>

    <div class="content-body">

      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <div class="row g-4">

        <!-- Left: Input form -->
        <div class="col-lg-7">
          <form method="POST" action="" id="carbonForm">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

            <!-- Reporting period -->
            <div class="d-flex align-items-center gap-2 mb-3">
              <label class="fw-semibold mb-0">Reporting Period:</label>
              <select class="form-select form-select-sm w-auto" name="period">
                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $period == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
              <span class="text-muted small">Annual totals for the full year</span>
            </div>

            <!-- SCOPE 2: Electricity (most important, shown first) -->
            <div class="scope-section scope-2-section mb-3">
              <div class="scope-section-header">
                <span class="scope-badge scope-badge-2">Scope 2</span>
                <span class="scope-title">Purchased Electricity</span>
                <span class="scope-ef-note">TNB Peninsular grid &bull; 0.694 kgCO₂e/kWh</span>
              </div>
              <div class="scope-section-body">
                <div class="row g-3">
                  <div class="col-md-8">
                    <label class="form-label">Annual Electricity Consumption <span class="text-muted">(kWh)</span></label>
                    <input type="number" class="form-control calc-input" name="electricity_kwh"
                           data-scope="scope2" data-factor="0.000694" min="0" step="100"
                           value="<?= $inputs['electricity_kwh'] ?>" placeholder="e.g. 240000">
                    <div class="form-text">From TNB bills. Sum all monthly kWh for the year.</div>
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <div class="ef-result w-100 text-end">
                      <div class="ef-value" id="res-electricity">—</div>
                      <div class="ef-unit">tCO₂e</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SCOPE 1: Direct Emissions -->
            <div class="scope-section scope-1-section mb-3">
              <div class="scope-section-header">
                <span class="scope-badge scope-badge-1">Scope 1</span>
                <span class="scope-title">Direct Emissions</span>
              </div>
              <div class="scope-section-body">
                <p class="text-muted small mb-3">Fuels burned on-site and in company-owned vehicles.</p>

                <div class="subsection-label">Stationary Combustion (generators, boilers, furnaces)</div>
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Diesel <span class="text-muted">(litres)</span></label>
                    <input type="number" class="form-control calc-input" name="diesel_litre"
                           data-scope="scope1" data-factor="0.0026914" min="0" step="1"
                           value="<?= $inputs['diesel_litre'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Petrol / RON95 <span class="text-muted">(litres)</span></label>
                    <input type="number" class="form-control calc-input" name="petrol_litre"
                           data-scope="scope1" data-factor="0.0023098" min="0" step="1"
                           value="<?= $inputs['petrol_litre'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Natural Gas <span class="text-muted">(m³)</span></label>
                    <input type="number" class="form-control calc-input" name="natural_gas_m3"
                           data-scope="scope1" data-factor="0.0020160" min="0" step="1"
                           value="<?= $inputs['natural_gas_m3'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">LPG <span class="text-muted">(kg)</span></label>
                    <input type="number" class="form-control calc-input" name="lpg_kg"
                           data-scope="scope1" data-factor="0.0015100" min="0" step="1"
                           value="<?= $inputs['lpg_kg'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">CNG <span class="text-muted">(m³)</span></label>
                    <input type="number" class="form-control calc-input" name="cng_m3"
                           data-scope="scope1" data-factor="0.0021570" min="0" step="1"
                           value="<?= $inputs['cng_m3'] ?>" placeholder="0">
                  </div>
                </div>

                <div class="subsection-label">Mobile Combustion (company-owned fleet, annual km)</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Diesel fleet <span class="text-muted">(km/year)</span></label>
                    <input type="number" class="form-control calc-input" name="fleet_diesel_km"
                           data-scope="scope1" data-factor="0.00016844" min="0" step="100"
                           value="<?= $inputs['fleet_diesel_km'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Petrol fleet <span class="text-muted">(km/year)</span></label>
                    <input type="number" class="form-control calc-input" name="fleet_petrol_km"
                           data-scope="scope1" data-factor="0.00015302" min="0" step="100"
                           value="<?= $inputs['fleet_petrol_km'] ?>" placeholder="0">
                  </div>
                </div>
              </div>
            </div>

            <!-- SCOPE 3: Value Chain -->
            <div class="scope-section scope-3-section mb-3">
              <div class="scope-section-header">
                <span class="scope-badge scope-badge-3">Scope 3</span>
                <span class="scope-title">Value Chain Emissions</span>
              </div>
              <div class="scope-section-body">
                <p class="text-muted small mb-3">Indirect emissions from business activities.</p>

                <div class="subsection-label">Business Travel (annual passenger-km)</div>
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Domestic flights <span class="text-muted">(passenger-km)</span></label>
                    <input type="number" class="form-control calc-input" name="flight_domestic_km"
                           data-scope="scope3" data-factor="0.000255" min="0" step="100"
                           value="<?= $inputs['flight_domestic_km'] ?>" placeholder="0">
                    <div class="form-text">e.g. KL↔Penang = 310 km × passengers</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">International flights <span class="text-muted">(passenger-km)</span></label>
                    <input type="number" class="form-control calc-input" name="flight_intl_km"
                           data-scope="scope3" data-factor="0.00019085" min="0" step="100"
                           value="<?= $inputs['flight_intl_km'] ?>" placeholder="0">
                    <div class="form-text">e.g. KL↔London = 10,540 km × passengers</div>
                  </div>
                </div>

                <div class="subsection-label">Waste (annual)</div>
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label">General waste to landfill <span class="text-muted">(kg)</span></label>
                    <input type="number" class="form-control calc-input" name="waste_landfill_kg"
                           data-scope="scope3" data-factor="0.00058685" min="0" step="1"
                           value="<?= $inputs['waste_landfill_kg'] ?>" placeholder="0">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Recycled waste <span class="text-muted">(kg)</span></label>
                    <input type="number" class="form-control calc-input" name="waste_recycled_kg"
                           data-scope="scope3" data-factor="0.00001467" min="0" step="1"
                           value="<?= $inputs['waste_recycled_kg'] ?>" placeholder="0">
                  </div>
                </div>

                <div class="subsection-label">Water</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Water consumption <span class="text-muted">(m³)</span></label>
                    <input type="number" class="form-control calc-input" name="water_m3"
                           data-scope="scope3" data-factor="0.000149" min="0" step="1"
                           value="<?= $inputs['water_m3'] ?>" placeholder="0">
                    <div class="form-text">From utility bills. 1,000 litres = 1 m³</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-3">
              <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-calculator me-1"></i>Calculate
              </button>
              <button type="submit" name="save_to_esg" value="1" class="btn btn-primary">
                <i class="bi bi-floppy me-1"></i>Calculate &amp; Save to ESG Data
              </button>
            </div>
          </form>
        </div>

        <!-- Right: Live results -->
        <div class="col-lg-5">
          <div class="carbon-results-panel">

            <div class="carbon-total-card" id="totalCard">
              <div class="carbon-total-label">Total Annual Emissions</div>
              <div class="carbon-total-value" id="liveTotal">0.00</div>
              <div class="carbon-total-unit">tCO₂e</div>
              <div class="carbon-intensity mt-2" id="liveIntensity">— tCO₂e per employee</div>
            </div>

            <div class="carbon-scope-row mt-3">
              <div class="carbon-scope-item scope-1-bg">
                <div class="cs-label"><span class="scope-badge scope-badge-1 me-1">1</span> Direct</div>
                <div class="cs-value" id="liveScope1">0.00</div>
                <div class="cs-unit">tCO₂e</div>
              </div>
              <div class="carbon-scope-item scope-2-bg">
                <div class="cs-label"><span class="scope-badge scope-badge-2 me-1">2</span> Electricity</div>
                <div class="cs-value" id="liveScope2">0.00</div>
                <div class="cs-unit">tCO₂e</div>
              </div>
              <div class="carbon-scope-item scope-3-bg">
                <div class="cs-label"><span class="scope-badge scope-badge-3 me-1">3</span> Value Chain</div>
                <div class="cs-value" id="liveScope3">0.00</div>
                <div class="cs-unit">tCO₂e</div>
              </div>
            </div>

            <!-- Breakdown -->
            <div class="card mt-3">
              <div class="card-header d-flex justify-content-between">
                <span>Emission Breakdown</span>
                <span class="text-muted small">tCO₂e</span>
              </div>
              <div class="p-3">
                <?php
                $breakdownItems = [
                    ['id' => 'liveElec',       'icon' => 'bi-lightning-charge', 'label' => 'Electricity',         'color' => '#0891b2'],
                    ['id' => 'liveStationary', 'icon' => 'bi-fire',             'label' => 'Stationary Combustion','color' => '#dc2626'],
                    ['id' => 'liveFleet',      'icon' => 'bi-truck',            'label' => 'Company Fleet',        'color' => '#d97706'],
                    ['id' => 'liveTravel',     'icon' => 'bi-airplane',         'label' => 'Business Travel',      'color' => '#7c3aed'],
                    ['id' => 'liveWaste',      'icon' => 'bi-trash',            'label' => 'Waste',                'color' => '#64748b'],
                    ['id' => 'liveWater',      'icon' => 'bi-droplet',          'label' => 'Water',                'color' => '#0d9488'],
                ];
                foreach ($breakdownItems as $item): ?>
                <div class="breakdown-row">
                  <i class="bi <?= $item['icon'] ?>" style="color:<?= $item['color'] ?>"></i>
                  <span><?= $item['label'] ?></span>
                  <div class="breakdown-bar-wrap">
                    <div class="breakdown-bar" id="bar-<?= $item['id'] ?>" style="background:<?= $item['color'] ?>20;width:0%"></div>
                  </div>
                  <span class="breakdown-val" id="<?= $item['id'] ?>">0.00</span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Industry benchmark -->
            <div class="card mt-3">
              <div class="card-header">Industry Benchmark (<?= htmlspecialchars($activeCompany['industry']) ?>)</div>
              <div class="p-3">
                <p class="text-muted small mb-2">Average tCO₂e per employee in your industry:</p>
                <?php foreach (['scope1' => 'Scope 1', 'scope2' => 'Scope 2', 'scope3' => 'Scope 3'] as $sk => $sl): ?>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small"><?= $sl ?></span>
                  <span class="fw-semibold"><?= $benchmark[$sk] ?> tCO₂e/emp</span>
                </div>
                <?php endforeach; ?>
                <div class="alert alert-info mt-2 mb-0 py-2 small">
                  <i class="bi bi-info-circle me-1"></i>
                  Your company: <?= $empCount ?> employees.
                  Target &lt; <?= round(($benchmark['scope1']+$benchmark['scope2']+$benchmark['scope3']) * $empCount, 1) ?> tCO₂e total.
                </div>
              </div>
            </div>

            <?php if ($result): ?>
            <!-- Server-calculated result (after submit) -->
            <div class="alert alert-success mt-3">
              <strong><i class="bi bi-check-circle me-1"></i>Calculated Result</strong><br>
              Scope 1: <strong><?= $result['scope1'] ?></strong> tCO₂e &bull;
              Scope 2: <strong><?= $result['scope2'] ?></strong> tCO₂e &bull;
              Scope 3: <strong><?= $result['scope3'] ?></strong> tCO₂e<br>
              <strong>Total: <?= $result['total'] ?> tCO₂e</strong>
            </div>
            <?php endif; ?>

            <div class="ef-source-note mt-3">
              <i class="bi bi-info-circle me-1"></i>
              Emission factors: Malaysia MyGHG 2nd Ed. (DOE 2023), DEFRA 2023.
              Scope 2 uses TNB Peninsular grid factor (0.694 kgCO₂e/kWh).
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const EF = <?= $efJS ?>;
const empCount = <?= $empCount ?>;

function calcAll() {
  let scope1 = 0, scope2 = 0, scope3 = 0;
  const breakdown = {elec:0, stationary:0, fleet:0, travel:0, waste:0, water:0};

  document.querySelectorAll('.calc-input').forEach(el => {
    const val    = parseFloat(el.value) || 0;
    const factor = parseFloat(el.dataset.factor) || 0;
    const scope  = el.dataset.scope;
    const name   = el.name;
    const emit   = val * factor;

    if (scope === 'scope1') scope1 += emit;
    else if (scope === 'scope2') scope2 += emit;
    else if (scope === 'scope3') scope3 += emit;

    if (name === 'electricity_kwh')       breakdown.elec       += emit;
    else if (['diesel_litre','petrol_litre','natural_gas_m3','lpg_kg','cng_m3'].includes(name))
                                           breakdown.stationary += emit;
    else if (name.startsWith('fleet_'))    breakdown.fleet      += emit;
    else if (name.startsWith('flight_'))   breakdown.travel     += emit;
    else if (name.startsWith('waste_'))    breakdown.waste      += emit;
    else if (name === 'water_m3')          breakdown.water      += emit;
  });

  const total = scope1 + scope2 + scope3;

  document.getElementById('liveScope1').textContent = scope1.toFixed(2);
  document.getElementById('liveScope2').textContent = scope2.toFixed(2);
  document.getElementById('liveScope3').textContent = scope3.toFixed(2);
  document.getElementById('liveTotal').textContent  = total.toFixed(2);
  document.getElementById('liveIntensity').textContent = empCount > 0
    ? (total / empCount).toFixed(2) + ' tCO₂e per employee'
    : '—';

  const maxVal = Math.max(...Object.values(breakdown), 0.001);
  const barMap = {
    elec:'liveElec', stationary:'liveStationary', fleet:'liveFleet',
    travel:'liveTravel', waste:'liveWaste', water:'liveWater'
  };
  Object.entries(barMap).forEach(([k, id]) => {
    const v = breakdown[k] || 0;
    document.getElementById(id).textContent = v.toFixed(2);
    const bar = document.getElementById('bar-' + id);
    if (bar) bar.style.width = ((v / maxVal) * 100).toFixed(1) + '%';
  });

  // Colour total card by magnitude
  const card = document.getElementById('totalCard');
  card.classList.remove('total-low','total-medium','total-high');
  if (total < 50)       card.classList.add('total-low');
  else if (total < 500) card.classList.add('total-medium');
  else                  card.classList.add('total-high');
}

document.querySelectorAll('.calc-input').forEach(el => el.addEventListener('input', calcAll));
calcAll(); // run on page load
</script>
