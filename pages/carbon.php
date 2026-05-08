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
$efJS      = json_encode(CarbonCalculator::FACTORS);

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
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <!-- ── Period selector ───────────────────────────────── -->
      <form method="POST" action="" id="carbonForm">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <div class="carbon-period-bar mb-4">
        <label class="fw-semibold mb-0">Reporting Period:</label>
        <select class="form-select form-select-sm w-auto" name="period">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>" <?= $period == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <span class="text-muted small">Annual totals for the full year</span>
        <div class="ms-auto d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calculator me-1"></i>Calculate
          </button>
          <button type="submit" name="save_to_esg" value="1" class="btn btn-primary btn-sm">
            <i class="bi bi-floppy me-1"></i>Save to ESG
          </button>
        </div>
      </div>

      <div class="row g-4">

        <!-- ══════════════════════════════════════════════════════
             LEFT: Input form
             ══════════════════════════════════════════════════════ -->
        <div class="col-xl-7 col-lg-12">

          <!-- SCOPE 2 -->
          <div class="scope-section scope-2-section mb-3">
            <div class="scope-section-header">
              <span class="scope-badge scope-badge-2">Scope 2</span>
              <span class="scope-title">Purchased Electricity</span>
              <span class="scope-ef-note">TNB Peninsular grid &bull; 0.694 kgCO₂e/kWh</span>
            </div>
            <div class="scope-section-body">
              <div class="row g-3 align-items-end">
                <div class="col-sm-8">
                  <label class="form-label">Annual Electricity Consumption <span class="text-muted">(kWh)</span></label>
                  <input type="number" class="form-control calc-input" name="electricity_kwh"
                         data-scope="scope2" data-ef="0.000694" data-group="elec"
                         min="0" step="100" value="<?= $inputs['electricity_kwh'] ?>" placeholder="e.g. 240 000">
                  <div class="form-text">From TNB bills — sum all monthly kWh for the year.</div>
                </div>
                <div class="col-sm-4">
                  <div class="inline-result">
                    <span class="ir-value" id="res-electricity">0.00</span>
                    <span class="ir-unit">tCO₂e</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SCOPE 1 -->
          <div class="scope-section scope-1-section mb-3">
            <div class="scope-section-header">
              <span class="scope-badge scope-badge-1">Scope 1</span>
              <span class="scope-title">Direct Emissions</span>
              <span class="scope-ef-note">Fuels burned on-site and in company-owned vehicles</span>
            </div>
            <div class="scope-section-body">

              <div class="subsection-label">Stationary Combustion — generators, boilers, furnaces</div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label">Diesel <span class="text-muted">(litres)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="diesel_litre"
                           data-scope="scope1" data-ef="0.0026914" data-group="stationary"
                           min="0" step="1" value="<?= $inputs['diesel_litre'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-diesel">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label">Petrol / RON95 <span class="text-muted">(litres)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="petrol_litre"
                           data-scope="scope1" data-ef="0.0023098" data-group="stationary"
                           min="0" step="1" value="<?= $inputs['petrol_litre'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-petrol">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label">Natural Gas <span class="text-muted">(m³)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="natural_gas_m3"
                           data-scope="scope1" data-ef="0.0020160" data-group="stationary"
                           min="0" step="1" value="<?= $inputs['natural_gas_m3'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-gas">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label">LPG <span class="text-muted">(kg)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="lpg_kg"
                           data-scope="scope1" data-ef="0.0015100" data-group="stationary"
                           min="0" step="1" value="<?= $inputs['lpg_kg'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-lpg">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label">CNG <span class="text-muted">(m³)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="cng_m3"
                           data-scope="scope1" data-ef="0.0021570" data-group="stationary"
                           min="0" step="1" value="<?= $inputs['cng_m3'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-cng">0.00</span>
                  </div>
                </div>
              </div>

              <div class="subsection-label">Mobile Combustion — company-owned fleet (annual km)</div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Diesel fleet <span class="text-muted">(km/year)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="fleet_diesel_km"
                           data-scope="scope1" data-ef="0.00016844" data-group="fleet"
                           min="0" step="100" value="<?= $inputs['fleet_diesel_km'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-fleet-diesel">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Petrol fleet <span class="text-muted">(km/year)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="fleet_petrol_km"
                           data-scope="scope1" data-ef="0.00015302" data-group="fleet"
                           min="0" step="100" value="<?= $inputs['fleet_petrol_km'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-fleet-petrol">0.00</span>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- SCOPE 3 -->
          <div class="scope-section scope-3-section mb-3">
            <div class="scope-section-header">
              <span class="scope-badge scope-badge-3">Scope 3</span>
              <span class="scope-title">Value Chain Emissions</span>
              <span class="scope-ef-note">Indirect emissions from business activities</span>
            </div>
            <div class="scope-section-body">

              <div class="subsection-label">Business Travel — annual passenger-km</div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label">Domestic flights <span class="text-muted">(pax-km)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="flight_domestic_km"
                           data-scope="scope3" data-ef="0.000255" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_domestic_km'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-dom-flight">0.00</span>
                  </div>
                  <div class="form-text">KL↔Penang ≈ 310 km × no. of passengers</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">International flights <span class="text-muted">(pax-km)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="flight_intl_km"
                           data-scope="scope3" data-ef="0.00019085" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_intl_km'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-intl-flight">0.00</span>
                  </div>
                  <div class="form-text">KL↔London ≈ 10 540 km × no. of passengers</div>
                </div>
              </div>

              <div class="subsection-label">Waste — annual</div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label">General waste to landfill <span class="text-muted">(kg)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="waste_landfill_kg"
                           data-scope="scope3" data-ef="0.00058685" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_landfill_kg'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-landfill">0.00</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Recycled waste <span class="text-muted">(kg)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="waste_recycled_kg"
                           data-scope="scope3" data-ef="0.00001467" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_recycled_kg'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-recycled">0.00</span>
                  </div>
                </div>
              </div>

              <div class="subsection-label">Water</div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Water consumption <span class="text-muted">(m³)</span></label>
                  <div class="input-with-result">
                    <input type="number" class="form-control calc-input" name="water_m3"
                           data-scope="scope3" data-ef="0.000149" data-group="water"
                           min="0" step="1" value="<?= $inputs['water_m3'] ?>" placeholder="0">
                    <span class="iwr-val" id="res-water">0.00</span>
                  </div>
                  <div class="form-text">1 000 litres = 1 m³</div>
                </div>
              </div>

            </div>
          </div>

        </div><!-- /col left -->

        <!-- ══════════════════════════════════════════════════════
             RIGHT: Live results
             ══════════════════════════════════════════════════════ -->
        <div class="col-xl-5 col-lg-12">
          <div class="carbon-results-panel">

            <!-- Total card -->
            <div class="carbon-total-card" id="totalCard">
              <div class="carbon-total-label">Total Annual Emissions</div>
              <div class="d-flex align-items-end gap-2">
                <div class="carbon-total-value" id="liveTotal">0.00</div>
                <div class="carbon-total-unit mb-2">tCO₂e</div>
              </div>
              <div class="carbon-intensity" id="liveIntensity">0.00 tCO₂e per employee</div>
            </div>

            <!-- Scope breakdown row -->
            <div class="carbon-scope-row mt-3">
              <div class="carbon-scope-item scope-1-bg">
                <div class="cs-badge scope-badge-1">Scope 1</div>
                <div class="cs-value" id="liveScope1">0.00</div>
                <div class="cs-unit">tCO₂e — Direct</div>
              </div>
              <div class="carbon-scope-item scope-2-bg">
                <div class="cs-badge scope-badge-2">Scope 2</div>
                <div class="cs-value" id="liveScope2">0.00</div>
                <div class="cs-unit">tCO₂e — Electricity</div>
              </div>
              <div class="carbon-scope-item scope-3-bg">
                <div class="cs-badge scope-badge-3">Scope 3</div>
                <div class="cs-value" id="liveScope3">0.00</div>
                <div class="cs-unit">tCO₂e — Value Chain</div>
              </div>
            </div>

            <!-- Emission breakdown bars -->
            <div class="card mt-3">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Emission Breakdown</span>
                <span class="text-muted small">tCO₂e</span>
              </div>
              <div class="p-3">
                <?php
                $bdItems = [
                    ['id' => 'liveElec',       'icon' => 'bi-lightning-charge-fill', 'label' => 'Electricity',          'color' => '#0891b2'],
                    ['id' => 'liveStationary', 'icon' => 'bi-fire',                  'label' => 'Stationary Combustion', 'color' => '#dc2626'],
                    ['id' => 'liveFleet',      'icon' => 'bi-truck-front-fill',      'label' => 'Company Fleet',         'color' => '#d97706'],
                    ['id' => 'liveTravel',     'icon' => 'bi-airplane-fill',         'label' => 'Business Travel',       'color' => '#7c3aed'],
                    ['id' => 'liveWaste',      'icon' => 'bi-trash3-fill',           'label' => 'Waste',                 'color' => '#64748b'],
                    ['id' => 'liveWater',      'icon' => 'bi-droplet-fill',          'label' => 'Water',                 'color' => '#0d9488'],
                ];
                foreach ($bdItems as $item): ?>
                <div class="breakdown-row">
                  <i class="bi <?= $item['icon'] ?>" style="color:<?= $item['color'] ?>"></i>
                  <span class="bd-label"><?= $item['label'] ?></span>
                  <div class="breakdown-bar-wrap">
                    <div class="breakdown-bar" id="bar-<?= $item['id'] ?>"
                         style="background:<?= $item['color'] ?>;width:0%"></div>
                  </div>
                  <span class="breakdown-val" id="<?= $item['id'] ?>">0.00</span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Industry benchmark -->
            <div class="card mt-3">
              <div class="card-header">
                <span class="fw-semibold">Industry Benchmark</span>
                <span class="text-muted small ms-1">(<?= htmlspecialchars($activeCompany['industry']) ?>)</span>
              </div>
              <div class="p-3">
                <p class="text-muted small mb-2">Average tCO₂e per employee in your industry:</p>
                <div class="row g-2 mb-2">
                  <?php foreach (['scope1' => ['Scope 1','#fed7aa'], 'scope2' => ['Scope 2','#bfdbfe'], 'scope3' => ['Scope 3','#e9d5ff']] as $sk => [$sl,$bg]): ?>
                  <div class="col-4">
                    <div class="benchmark-chip" style="background:<?= $bg ?>">
                      <div class="bm-label"><?= $sl ?></div>
                      <div class="bm-val"><?= $benchmark[$sk] ?></div>
                      <div class="bm-unit">tCO₂e/emp</div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="alert alert-info mb-0 py-2 small">
                  <i class="bi bi-info-circle me-1"></i>
                  <?= $empCount ?> employees · Target &lt; <?= round(($benchmark['scope1']+$benchmark['scope2']+$benchmark['scope3']) * $empCount, 1) ?> tCO₂e total
                </div>
              </div>
            </div>

            <?php if ($result): ?>
            <div class="alert alert-success mt-3 mb-0">
              <strong><i class="bi bi-check-circle me-1"></i>Saved Result</strong><br>
              Scope 1: <strong><?= $result['scope1'] ?></strong> &bull;
              Scope 2: <strong><?= $result['scope2'] ?></strong> &bull;
              Scope 3: <strong><?= $result['scope3'] ?></strong> tCO₂e<br>
              <strong>Total: <?= $result['total'] ?> tCO₂e</strong>
            </div>
            <?php endif; ?>

          </div>
        </div><!-- /col right -->

      </div><!-- /row -->
      </form>

      <!-- ══════════════════════════════════════════════════════════
           EMISSION FACTORS REFERENCE TABLE
           ══════════════════════════════════════════════════════════ -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center"
             style="cursor:pointer" onclick="toggleEFTable()">
          <span class="fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>Emission Factors Used (Backend Reference)
          </span>
          <span class="text-muted small">
            <i class="bi bi-chevron-down" id="ef-table-icon"></i> Click to expand
          </span>
        </div>
        <div id="efTableBody" style="display:none">
          <div class="p-3 bg-light border-bottom">
            <div class="row g-2 small text-muted">
              <div class="col-md-4"><i class="bi bi-check-circle-fill text-success me-1"></i><strong>Malaysia MyGHG 2nd Edition</strong> — DOE 2023 (Scope 1 fuels, Scope 2 grid)</div>
              <div class="col-md-4"><i class="bi bi-check-circle-fill text-primary me-1"></i><strong>DEFRA 2023</strong> — UK DESNZ/DBET Conversion Factors (Scope 3 travel, waste, water)</div>
              <div class="col-md-4"><i class="bi bi-check-circle-fill text-warning me-1"></i><strong>TNB 2023</strong> — Peninsular Malaysia grid factor 0.694 kgCO₂e/kWh</div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Scope</th>
                  <th>Activity / Fuel</th>
                  <th>Input Unit</th>
                  <th class="text-end">Factor (kgCO₂e/unit)</th>
                  <th class="text-end">Factor (tCO₂e/unit)</th>
                  <th>Source</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $efRows = [
                  // scope, activity, unit, factor_kg, source
                  ['2','Electricity — Peninsular Malaysia (TNB)','kWh',        0.694,     'MyGHG 2023 / TNB'],
                  ['2','Electricity — Sabah grid',                'kWh',        0.829,     'MyGHG 2023'],
                  ['2','Electricity — Sarawak grid',              'kWh',        0.512,     'MyGHG 2023'],
                  ['1','Diesel (stationary combustion)',           'litre',      2.6914,    'MyGHG 2023'],
                  ['1','Petrol / RON95 (stationary)',             'litre',      2.3098,    'MyGHG 2023'],
                  ['1','Natural Gas',                              'm³',         2.0160,    'MyGHG 2023'],
                  ['1','LPG',                                      'kg',         1.5100,    'MyGHG 2023'],
                  ['1','CNG',                                      'm³',         2.1570,    'MyGHG 2023'],
                  ['1','Fleet — diesel vehicle',                  'km',         0.16844,   'MyGHG 2023'],
                  ['1','Fleet — petrol vehicle',                  'km',         0.15302,   'MyGHG 2023'],
                  ['3','Flights — domestic (< 3 h)',              'pax-km',     0.255,     'DEFRA 2023'],
                  ['3','Flights — international (long-haul)',     'pax-km',     0.19085,   'DEFRA 2023'],
                  ['3','Waste — landfill',                        'kg',         0.58685,   'DEFRA 2023'],
                  ['3','Waste — recycled',                        'kg',         0.01467,   'DEFRA 2023'],
                  ['3','Water — supply & treatment',              'm³',         0.149,     'DEFRA 2023'],
                ];
                $scopeColors = ['1' => 'table-warning', '2' => 'table-info', '3' => 'table-secondary'];
                foreach ($efRows as [$scope, $activity, $unit, $kgFactor, $source]):
                ?>
                <tr class="<?= $scopeColors[$scope] ?? '' ?>">
                  <td><span class="scope-badge scope-badge-<?= $scope ?>">Scope <?= $scope ?></span></td>
                  <td><?= htmlspecialchars($activity) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($unit) ?></td>
                  <td class="text-end font-monospace fw-semibold"><?= number_format($kgFactor, 5) ?></td>
                  <td class="text-end font-monospace text-muted"><?= number_format($kgFactor / 1000, 8) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($source) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="p-3 text-muted small border-top">
            <i class="bi bi-info-circle me-1"></i>
            All factors expressed as CO₂-equivalent (CO₂e), incorporating CO₂, CH₄, and N₂O using IPCC AR5 Global Warming Potentials.
            Fleet factors assume average light vehicle (car/van ≤ 3.5t). For heavy vehicles, apply a multiplier.
          </div>
        </div>
      </div>

    </div><!-- /content-body -->
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const empCount = <?= $empCount ?>;

// ── Per-field result IDs ───────────────────────────────────────────
const fieldResultMap = {
  electricity_kwh:    'res-electricity',
  diesel_litre:       'res-diesel',
  petrol_litre:       'res-petrol',
  natural_gas_m3:     'res-gas',
  lpg_kg:             'res-lpg',
  cng_m3:             'res-cng',
  fleet_diesel_km:    'res-fleet-diesel',
  fleet_petrol_km:    'res-fleet-petrol',
  flight_domestic_km: 'res-dom-flight',
  flight_intl_km:     'res-intl-flight',
  waste_landfill_kg:  'res-landfill',
  waste_recycled_kg:  'res-recycled',
  water_m3:           'res-water',
};

function calcAll() {
  let scope1 = 0, scope2 = 0, scope3 = 0;
  const bd = {elec:0, stationary:0, fleet:0, travel:0, waste:0, water:0};

  document.querySelectorAll('.calc-input').forEach(el => {
    const val   = parseFloat(el.value) || 0;
    const ef    = parseFloat(el.dataset.ef) || 0;
    const scope = el.dataset.scope;
    const group = el.dataset.group;
    const emit  = val * ef;

    if (scope === 'scope1') scope1 += emit;
    else if (scope === 'scope2') scope2 += emit;
    else if (scope === 'scope3') scope3 += emit;

    if (group) bd[group] = (bd[group] || 0) + emit;

    // per-field inline result
    const resEl = document.getElementById(fieldResultMap[el.name]);
    if (resEl) resEl.textContent = emit.toFixed(3);
  });

  const total = scope1 + scope2 + scope3;

  document.getElementById('liveScope1').textContent   = scope1.toFixed(2);
  document.getElementById('liveScope2').textContent   = scope2.toFixed(2);
  document.getElementById('liveScope3').textContent   = scope3.toFixed(2);
  document.getElementById('liveTotal').textContent    = total.toFixed(2);
  document.getElementById('liveIntensity').textContent =
    (total / empCount).toFixed(2) + ' tCO₂e per employee';

  const maxVal = Math.max(...Object.values(bd), 0.0001);
  const idMap  = {
    elec:'liveElec', stationary:'liveStationary', fleet:'liveFleet',
    travel:'liveTravel', waste:'liveWaste', water:'liveWater'
  };
  Object.entries(idMap).forEach(([k, id]) => {
    const v = bd[k] || 0;
    const el = document.getElementById(id);
    if (el) el.textContent = v.toFixed(2);
    const bar = document.getElementById('bar-' + id);
    if (bar) bar.style.width = ((v / maxVal) * 100).toFixed(1) + '%';
  });

  const card = document.getElementById('totalCard');
  card.classList.remove('total-low','total-medium','total-high');
  if (total > 500)     card.classList.add('total-high');
  else if (total > 50) card.classList.add('total-medium');
  else                 card.classList.add('total-low');
}

document.querySelectorAll('.calc-input').forEach(el => el.addEventListener('input', calcAll));
calcAll();

function toggleEFTable() {
  const body = document.getElementById('efTableBody');
  const icon = document.getElementById('ef-table-icon');
  const open = body.style.display === 'none';
  body.style.display = open ? 'block' : 'none';
  icon.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
}
</script>
