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

      <form method="POST" id="carbonForm">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <!-- ── Period bar ─────────────────────────────────────── -->
      <div class="cc-period-bar mb-4">
        <i class="bi bi-calendar3 text-muted"></i>
        <label class="cc-period-label">Reporting Period</label>
        <select class="form-select form-select-sm cc-year-select" name="period">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>" <?= $period == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <span class="cc-period-note">Annual totals for the full year</span>
        <div class="cc-period-actions">
          <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calculator me-1"></i>Calculate
          </button>
          <button type="submit" name="save_to_esg" value="1" class="btn btn-success btn-sm">
            <i class="bi bi-floppy me-1"></i>Save to ESG
          </button>
        </div>
      </div>

      <div class="row g-4 align-items-start">

        <!-- ══ LEFT: inputs ════════════════════════════════════════ -->
        <div class="col-xl-7 col-lg-12">

          <!-- SCOPE 2 -->
          <div class="cc-scope-section cc-scope-2 mb-3">
            <div class="cc-scope-header">
              <span class="cc-scope-pill s2-pill">Scope 2</span>
              <div class="cc-scope-header-body">
                <span class="cc-scope-name">Purchased Electricity</span>
                <span class="cc-scope-ef">TNB Peninsular grid &bull; 0.694 kgCO₂e/kWh</span>
              </div>
              <div class="cc-scope-total">
                <span class="cc-st-val" id="st-scope2">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">
              <div class="row g-3 align-items-end">
                <div class="col-md-8">
                  <label class="form-label">Annual Electricity Consumption <span class="text-muted">(kWh)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="electricity_kwh"
                           data-scope="scope2" data-ef="0.000694" data-group="elec"
                           min="0" step="100" value="<?= $inputs['electricity_kwh'] ?>" placeholder="e.g. 240 000">
                    <span class="input-group-text cc-res-tag" id="res-electricity">0.000 tCO₂e</span>
                  </div>
                  <div class="form-text">From TNB bills — sum all monthly kWh for the year.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- SCOPE 1 -->
          <div class="cc-scope-section cc-scope-1 mb-3">
            <div class="cc-scope-header">
              <span class="cc-scope-pill s1-pill">Scope 1</span>
              <div class="cc-scope-header-body">
                <span class="cc-scope-name">Direct Emissions</span>
                <span class="cc-scope-ef">Fuels burned on-site and in company-owned vehicles</span>
              </div>
              <div class="cc-scope-total">
                <span class="cc-st-val" id="st-scope1">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">

              <div class="cc-sub-label">Stationary Combustion — generators, boilers, furnaces</div>
              <div class="row g-3 mb-4">
                <?php
                $s1fields = [
                  ['diesel_litre',   '0.0026914', 'Diesel',       'litres',  'res-diesel',  'stationary'],
                  ['petrol_litre',   '0.0023098', 'Petrol/RON95', 'litres',  'res-petrol',  'stationary'],
                  ['natural_gas_m3', '0.0020160', 'Natural Gas',  'm³',      'res-gas',     'stationary'],
                  ['lpg_kg',         '0.0015100', 'LPG',          'kg',      'res-lpg',     'stationary'],
                  ['cng_m3',         '0.0021570', 'CNG',          'm³',      'res-cng',     'stationary'],
                ];
                foreach ($s1fields as [$fname, $ef, $label, $unit, $rid, $grp]):
                ?>
                <div class="col-sm-6 col-lg-4">
                  <label class="form-label"><?= $label ?> <span class="text-muted">(<?= $unit ?>)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="<?= $fname ?>"
                           data-scope="scope1" data-ef="<?= $ef ?>" data-group="<?= $grp ?>"
                           min="0" step="1" value="<?= $inputs[$fname] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="<?= $rid ?>">0.000</span>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="cc-sub-label">Mobile Combustion — company-owned fleet (annual km)</div>
              <div class="row g-3">
                <?php
                $fleetFields = [
                  ['fleet_diesel_km', '0.00016844', 'Diesel fleet',  'km/year', 'res-fleet-diesel', 'fleet'],
                  ['fleet_petrol_km', '0.00015302', 'Petrol fleet',  'km/year', 'res-fleet-petrol', 'fleet'],
                ];
                foreach ($fleetFields as [$fname, $ef, $label, $unit, $rid, $grp]):
                ?>
                <div class="col-sm-6">
                  <label class="form-label"><?= $label ?> <span class="text-muted">(<?= $unit ?>)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="<?= $fname ?>"
                           data-scope="scope1" data-ef="<?= $ef ?>" data-group="<?= $grp ?>"
                           min="0" step="100" value="<?= $inputs[$fname] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="<?= $rid ?>">0.000</span>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

            </div>
          </div>

          <!-- SCOPE 3 -->
          <div class="cc-scope-section cc-scope-3 mb-3">
            <div class="cc-scope-header">
              <span class="cc-scope-pill s3-pill">Scope 3</span>
              <div class="cc-scope-header-body">
                <span class="cc-scope-name">Value Chain Emissions</span>
                <span class="cc-scope-ef">Indirect emissions from business activities</span>
              </div>
              <div class="cc-scope-total">
                <span class="cc-st-val" id="st-scope3">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">

              <div class="cc-sub-label">Business Travel — annual passenger-km</div>
              <div class="row g-3 mb-4">
                <div class="col-sm-6">
                  <label class="form-label">Domestic flights <span class="text-muted">(pax-km)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="flight_domestic_km"
                           data-scope="scope3" data-ef="0.000255" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_domestic_km'] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="res-dom-flight">0.000</span>
                  </div>
                  <div class="form-text">KL↔Penang ≈ 310 km × no. of passengers</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">International flights <span class="text-muted">(pax-km)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="flight_intl_km"
                           data-scope="scope3" data-ef="0.00019085" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_intl_km'] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="res-intl-flight">0.000</span>
                  </div>
                  <div class="form-text">KL↔London ≈ 10 540 km × no. of passengers</div>
                </div>
              </div>

              <div class="cc-sub-label">Waste — annual</div>
              <div class="row g-3 mb-4">
                <div class="col-sm-6">
                  <label class="form-label">General waste to landfill <span class="text-muted">(kg)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="waste_landfill_kg"
                           data-scope="scope3" data-ef="0.00058685" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_landfill_kg'] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="res-landfill">0.000</span>
                  </div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Recycled waste <span class="text-muted">(kg)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="waste_recycled_kg"
                           data-scope="scope3" data-ef="0.00001467" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_recycled_kg'] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="res-recycled">0.000</span>
                  </div>
                </div>
              </div>

              <div class="cc-sub-label">Water</div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Water consumption <span class="text-muted">(m³)</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control calc-input" name="water_m3"
                           data-scope="scope3" data-ef="0.000149" data-group="water"
                           min="0" step="1" value="<?= $inputs['water_m3'] ?>" placeholder="0">
                    <span class="input-group-text cc-res-tag" id="res-water">0.000</span>
                  </div>
                  <div class="form-text">1 000 litres = 1 m³</div>
                </div>
              </div>

            </div>
          </div>

        </div><!-- /col-left -->

        <!-- ══ RIGHT: results panel ════════════════════════════════ -->
        <div class="col-xl-5 col-lg-12">
          <div class="cc-results-frame">

            <!-- Header -->
            <div class="cc-results-header">
              <i class="bi bi-activity me-2"></i>Live Results
            </div>

            <!-- Total -->
            <div class="cc-total-block" id="totalCard">
              <div class="cc-total-label">Total Annual Emissions</div>
              <div class="cc-total-row">
                <span class="cc-total-val" id="liveTotal">0.00</span>
                <span class="cc-total-unit">tCO₂e</span>
              </div>
              <div class="cc-total-intensity" id="liveIntensity">0.00 tCO₂e per employee</div>
            </div>

            <!-- Scope 1 / 2 / 3 cards -->
            <div class="cc-scope-cards">
              <div class="cc-scope-card s1-card">
                <div class="cc-sc-pill s1-pill">Scope 1</div>
                <div class="cc-sc-val" id="liveScope1">0.00</div>
                <div class="cc-sc-unit">tCO₂e</div>
                <div class="cc-sc-name">Direct</div>
              </div>
              <div class="cc-scope-card s2-card">
                <div class="cc-sc-pill s2-pill">Scope 2</div>
                <div class="cc-sc-val" id="liveScope2">0.00</div>
                <div class="cc-sc-unit">tCO₂e</div>
                <div class="cc-sc-name">Electricity</div>
              </div>
              <div class="cc-scope-card s3-card">
                <div class="cc-sc-pill s3-pill">Scope 3</div>
                <div class="cc-sc-val" id="liveScope3">0.00</div>
                <div class="cc-sc-unit">tCO₂e</div>
                <div class="cc-sc-name">Value Chain</div>
              </div>
            </div>

            <!-- Breakdown -->
            <div class="cc-breakdown-block">
              <div class="cc-block-title">
                <i class="bi bi-bar-chart-fill me-1"></i>Emission Breakdown
                <span class="ms-auto text-muted" style="font-size:11px;font-weight:400">tCO₂e</span>
              </div>
              <?php
              $bdItems = [
                ['liveElec',       'bi-lightning-charge-fill', 'Electricity',           '#0891b2'],
                ['liveStationary', 'bi-fire',                  'Stationary Combustion', '#dc2626'],
                ['liveFleet',      'bi-truck-front-fill',      'Company Fleet',         '#d97706'],
                ['liveTravel',     'bi-airplane-fill',         'Business Travel',       '#7c3aed'],
                ['liveWaste',      'bi-trash3-fill',           'Waste',                 '#64748b'],
                ['liveWater',      'bi-droplet-fill',          'Water',                 '#0d9488'],
              ];
              foreach ($bdItems as [$id, $icon, $label, $color]):
              ?>
              <div class="cc-bd-row">
                <i class="bi <?= $icon ?>" style="color:<?= $color ?>;width:16px;flex-shrink:0"></i>
                <span class="cc-bd-label"><?= $label ?></span>
                <div class="cc-bd-bar-wrap">
                  <div class="cc-bd-bar" id="bar-<?= $id ?>" style="background:<?= $color ?>"></div>
                </div>
                <span class="cc-bd-val" id="<?= $id ?>">0.00</span>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Industry benchmark -->
            <div class="cc-benchmark-block">
              <div class="cc-block-title">
                <i class="bi bi-graph-up me-1"></i>Industry Benchmark
                <span class="ms-1 text-muted" style="font-size:11px;font-weight:400">(<?= htmlspecialchars($activeCompany['industry']) ?>)</span>
              </div>
              <div class="cc-bm-grid">
                <?php foreach (['scope1' => ['S1','#fed7aa','#9a3412'], 'scope2' => ['S2','#bfdbfe','#1e40af'], 'scope3' => ['S3','#e9d5ff','#6b21a8']] as $sk => [$sl,$bg,$color]): ?>
                <div class="cc-bm-chip" style="background:<?= $bg ?>;color:<?= $color ?>">
                  <div class="cc-bm-chip-label"><?= $sl ?></div>
                  <div class="cc-bm-chip-val"><?= $benchmark[$sk] ?></div>
                  <div class="cc-bm-chip-unit">tCO₂e/emp</div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="cc-bm-target">
                <i class="bi bi-bullseye me-1 text-primary"></i>
                <?= $empCount ?> employees &bull;
                Target &lt; <strong><?= round(($benchmark['scope1']+$benchmark['scope2']+$benchmark['scope3']) * $empCount, 1) ?> tCO₂e</strong> total
              </div>
            </div>

            <?php if ($result): ?>
            <div class="cc-saved-result">
              <i class="bi bi-check-circle-fill text-success me-2"></i>
              <div>
                <div class="fw-semibold">Saved to ESG indicators</div>
                <div class="small text-muted">
                  S1: <?= $result['scope1'] ?> &bull;
                  S2: <?= $result['scope2'] ?> &bull;
                  S3: <?= $result['scope3'] ?> &bull;
                  <strong>Total: <?= $result['total'] ?> tCO₂e</strong>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Source note -->
            <div class="cc-source-note">
              <i class="bi bi-info-circle me-1"></i>
              Factors: Malaysia MyGHG 2nd Ed. (DOE 2023), DEFRA 2023, TNB 2023
            </div>

          </div><!-- /cc-results-frame -->
        </div><!-- /col-right -->

      </div><!-- /row -->
      </form>

      <!-- Emission Factors Reference -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center"
             style="cursor:pointer" onclick="toggleEFTable()">
          <span class="fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>Emission Factors Reference
          </span>
          <i class="bi bi-chevron-down" id="ef-table-icon"></i>
        </div>
        <div id="efTableBody" style="display:none">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Scope</th><th>Activity</th><th>Unit</th>
                  <th class="text-end">kgCO₂e/unit</th>
                  <th class="text-end">tCO₂e/unit</th>
                  <th>Source</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $efRows = [
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
                $sCls = ['1' => 'table-warning', '2' => 'table-info', '3' => 'table-secondary'];
                foreach ($efRows as [$scope, $act, $unit, $kg, $src]):
                ?>
                <tr class="<?= $sCls[$scope] ?? '' ?>">
                  <td><span class="cc-scope-pill s<?= $scope ?>-pill" style="font-size:10px">Scope <?= $scope ?></span></td>
                  <td><?= htmlspecialchars($act) ?></td>
                  <td class="text-muted"><?= $unit ?></td>
                  <td class="text-end font-monospace fw-semibold"><?= number_format($kg, 5) ?></td>
                  <td class="text-end font-monospace text-muted"><?= number_format($kg/1000, 8) ?></td>
                  <td class="text-muted small"><?= $src ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="p-3 text-muted small border-top">
            <i class="bi bi-info-circle me-1"></i>
            CO₂-equivalent using IPCC AR5 GWPs (CO₂ + CH₄ + N₂O). Fleet = average light vehicle ≤ 3.5 t.
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const empCount = <?= $empCount ?>;

const fieldResMap = {
  electricity_kwh:    ['res-electricity', 'tCO₂e'],
  diesel_litre:       ['res-diesel',       ''],
  petrol_litre:       ['res-petrol',       ''],
  natural_gas_m3:     ['res-gas',          ''],
  lpg_kg:             ['res-lpg',          ''],
  cng_m3:             ['res-cng',          ''],
  fleet_diesel_km:    ['res-fleet-diesel', ''],
  fleet_petrol_km:    ['res-fleet-petrol', ''],
  flight_domestic_km: ['res-dom-flight',   ''],
  flight_intl_km:     ['res-intl-flight',  ''],
  waste_landfill_kg:  ['res-landfill',     ''],
  waste_recycled_kg:  ['res-recycled',     ''],
  water_m3:           ['res-water',        ''],
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

    // per-field result tag
    const map = fieldResMap[el.name];
    if (map) {
      const tag = document.getElementById(map[0]);
      if (tag) tag.textContent = emit.toFixed(3) + (map[1] ? ' ' + map[1] : '');
    }
  });

  const total = scope1 + scope2 + scope3;

  // Section totals in header
  setText('st-scope1', scope1.toFixed(2));
  setText('st-scope2', scope2.toFixed(2));
  setText('st-scope3', scope3.toFixed(2));

  // Main totals
  setText('liveScope1', scope1.toFixed(2));
  setText('liveScope2', scope2.toFixed(2));
  setText('liveScope3', scope3.toFixed(2));
  setText('liveTotal',  total.toFixed(2));
  setText('liveIntensity', (total / empCount).toFixed(2) + ' tCO₂e per employee');

  // Breakdown bars
  const maxVal = Math.max(...Object.values(bd), 0.0001);
  const idMap = {
    elec:'liveElec', stationary:'liveStationary', fleet:'liveFleet',
    travel:'liveTravel', waste:'liveWaste', water:'liveWater'
  };
  Object.entries(idMap).forEach(([k, id]) => {
    const v = bd[k] || 0;
    setText(id, v.toFixed(2));
    const bar = document.getElementById('bar-' + id);
    if (bar) bar.style.width = ((v / maxVal) * 100).toFixed(1) + '%';
  });

  // Total card colour
  const card = document.getElementById('totalCard');
  card.className = card.className.replace(/\btotal-\w+/g, '');
  if (total > 500)     card.classList.add('total-high');
  else if (total > 50) card.classList.add('total-medium');
  else                 card.classList.add('total-low');
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
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
