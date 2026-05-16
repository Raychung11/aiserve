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
$saved     = false;
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
            $success = 'GHG emission data saved to your ESG indicators (Scope 1, 2 & 3).';
            $saved = true;
        }
    }
}

$benchmark = CarbonCalculator::getIntensityBenchmark($activeCompany['industry']);
$empCount  = max(1, (int)($activeCompany['employee_count'] ?? 1));

include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Carbon Calculator — self-contained styles ──────────────────────── */
.cc-period-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: nowrap;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 12px 20px; margin-bottom: 24px;
}
.cc-period-label { font-size: 13px; font-weight: 700; color: #475569; white-space: nowrap; }
.cc-year-select  { width: 90px; border: 1px solid #e2e8f0; border-radius: 8px;
                   font-size: 13px; font-weight: 700; padding: 5px 8px; color: #0f172a; }
.cc-period-note  { font-size: 12px; color: #94a3b8; flex: 1; }
.cc-period-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* Scope section cards */
.cc-scope { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
            border-left: 4px solid #e2e8f0; margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
.cc-scope.s1 { border-left-color: #f97316; }
.cc-scope.s2 { border-left-color: #0ea5e9; }
.cc-scope.s3 { border-left-color: #8b5cf6; }

.cc-scope-hd { display: flex; align-items: center; gap: 12px;
               padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.cc-spill { display: inline-flex; align-items: center; justify-content: center;
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800;
            white-space: nowrap; flex-shrink: 0; }
.s1 .cc-spill { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.s2 .cc-spill { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
.s3 .cc-spill { background: #faf5ff; color: #7c3aed; border: 1px solid #ddd6fe; }

.cc-scope-hd-body { flex: 1; min-width: 0; }
.cc-scope-hd-title { font-size: 14px; font-weight: 800; color: #0f172a; }
.cc-scope-hd-desc  { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.cc-scope-subtotal { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; }
.cc-st-val  { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1; }
.cc-st-unit { font-size: 10px; color: #94a3b8; font-weight: 600; }

.cc-scope-body { padding: 16px 20px; }
.cc-sub-label {
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
  color: #94a3b8; margin-bottom: 12px; margin-top: 4px;
  display: flex; align-items: center; gap: 6px;
}
.cc-sub-label::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }
.cc-sub-label + .cc-fields-grid { margin-top: 0; }

.cc-fields-grid { display: grid; gap: 12px; margin-bottom: 16px; }
.cc-fields-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.cc-fields-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.cc-fields-grid.cols-1 { grid-template-columns: 1fr 1fr; } /* electricity: half-width */
@media (max-width: 768px) {
  .cc-fields-grid.cols-3,
  .cc-fields-grid.cols-2 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
  .cc-fields-grid.cols-3,
  .cc-fields-grid.cols-2,
  .cc-fields-grid.cols-1 { grid-template-columns: 1fr; }
}

.cc-field { display: flex; flex-direction: column; gap: 5px; }
.cc-field-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 700; color: #334155;
}
.cc-field-unit { font-size: 11px; color: #94a3b8; font-weight: 400; }
.cc-field-ef   {
  font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 4px;
  background: #f1f5f9; color: #64748b; font-family: monospace; margin-left: auto;
}
.cc-field-inp { display: flex; align-items: stretch; border: 1.5px solid #e2e8f0;
                border-radius: 9px; overflow: hidden; transition: border-color 0.15s; }
.cc-field-inp:focus-within { border-color: #0ea5e9; }
.s1 .cc-field-inp:focus-within { border-color: #f97316; }
.s2 .cc-field-inp:focus-within { border-color: #0ea5e9; }
.s3 .cc-field-inp:focus-within { border-color: #8b5cf6; }

.cc-field-inp input {
  flex: 1; border: none; outline: none; padding: 8px 10px;
  font-size: 14px; font-weight: 600; color: #0f172a; background: #f8fafc;
  min-width: 0;
}
.cc-field-inp input:focus { background: #fff; }
.cc-res-tag {
  display: flex; align-items: center; padding: 0 10px;
  background: #f1f5f9; border-left: 1px solid #e2e8f0;
  font-size: 11px; font-weight: 700; color: #475569;
  white-space: nowrap; font-family: monospace;
}
.s1 .cc-res-tag { background: #fff7ed; color: #c2410c; border-left-color: #fed7aa; }
.s2 .cc-res-tag { background: #f0f9ff; color: #0369a1; border-left-color: #bae6fd; }
.s3 .cc-res-tag { background: #faf5ff; color: #6d28d9; border-left-color: #ddd6fe; }
.cc-field-hint { font-size: 10px; color: #94a3b8; }

/* ── Right Panel ──────────────────────────────────────────────── */
.cc-results-frame {
  position: sticky; top: 80px;
  background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden;
}
.cc-rf-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  padding: 14px 20px; display: flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 700; color: #e2e8f0;
}
.cc-rf-header .pulse-dot {
  width: 8px; height: 8px; border-radius: 50%; background: #22c55e;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%,100% { opacity: 1; } 50% { opacity: 0.4; }
}

.cc-total-block {
  padding: 20px; border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}
.cc-total-block.total-low    { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
.cc-total-block.total-medium { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }
.cc-total-block.total-high   { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }
.cc-total-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase;
                letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 6px; }
.cc-total-row { display: flex; align-items: baseline; gap: 6px; }
.cc-total-num { font-size: 40px; font-weight: 900; color: #0f172a; line-height: 1; }
.cc-total-unit-big { font-size: 14px; font-weight: 700; color: #64748b; }
.cc-total-intensity { font-size: 12px; color: #64748b; margin-top: 6px; }

.cc-scope-trio { display: grid; grid-template-columns: 1fr 1fr 1fr;
                  border-bottom: 1px solid #f1f5f9; }
.cc-trio-cell { padding: 14px 12px; text-align: center; }
.cc-trio-cell:not(:last-child) { border-right: 1px solid #f1f5f9; }
.cc-trio-pill { display: inline-block; padding: 2px 8px; border-radius: 10px;
                font-size: 10px; font-weight: 800; margin-bottom: 6px; }
.ct-s1 { background: #fff7ed; color: #c2410c; }
.ct-s2 { background: #f0f9ff; color: #0369a1; }
.ct-s3 { background: #faf5ff; color: #7c3aed; }
.cc-trio-val  { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1; }
.cc-trio-unit { font-size: 10px; color: #94a3b8; font-weight: 600; }
.cc-trio-name { font-size: 10px; color: #94a3b8; margin-top: 3px; }

.cc-breakdown { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.cc-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase;
                     letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 12px;
                     display: flex; align-items: center; justify-content: space-between; }
.cc-bd-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.cc-bd-icon { width: 18px; text-align: center; font-size: 13px; flex-shrink: 0; }
.cc-bd-label { font-size: 12px; color: #475569; width: 120px; flex-shrink: 0; }
.cc-bd-bar-wrap { flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.cc-bd-bar { height: 100%; border-radius: 3px; transition: width 0.4s ease; width: 0; }
.cc-bd-val { font-size: 11px; font-weight: 700; color: #334155;
             font-family: monospace; width: 42px; text-align: right; flex-shrink: 0; }

.cc-benchmark { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.cc-bm-trio { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 10px; }
.cc-bm-chip { padding: 10px 8px; border-radius: 8px; text-align: center; }
.cc-bm-chip-lbl  { font-size: 10px; font-weight: 800; opacity: 0.7; }
.cc-bm-chip-val  { font-size: 16px; font-weight: 900; line-height: 1.2; }
.cc-bm-chip-unit { font-size: 9px; opacity: 0.6; }
.cc-bm-note { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px; }

.cc-source { padding: 10px 20px; font-size: 10px; color: #94a3b8;
             display: flex; align-items: center; gap: 6px; }

.cc-saved-banner {
  margin: 12px 20px; background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;
  font-size: 12px; color: #15803d;
}

/* EF reference table */
.cc-ef-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
              overflow: hidden; margin-top: 20px; }
.cc-ef-toggle { display: flex; align-items: center; justify-content: space-between;
                padding: 14px 20px; cursor: pointer; user-select: none; }
.cc-ef-toggle:hover { background: #f8fafc; }
.cc-ef-title { font-size: 13px; font-weight: 700; color: #334155;
               display: flex; align-items: center; gap: 8px; }
</style>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-calculator-fill me-2" style="color:#0ea5e9"></i>Carbon Calculator</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars($activeCompany['name']) ?> &bull; GHG Scope 1, 2 &amp; 3</span>
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

      <!-- ── Period bar ───────────────────────────────────────────── -->
      <div class="cc-period-bar">
        <i class="bi bi-calendar3" style="color:#64748b"></i>
        <span class="cc-period-label">Reporting Period</span>
        <select class="cc-year-select" name="period">
          <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>" <?= $period == $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <span class="cc-period-note">Annual totals for the full year</span>
        <div class="cc-period-actions">
          <button type="submit" class="btn btn-outline-primary btn-sm fw-700">
            <i class="bi bi-calculator me-1"></i>Calculate
          </button>
          <button type="submit" name="save_to_esg" value="1" class="btn btn-success btn-sm fw-700">
            <i class="bi bi-floppy me-1"></i>Save to ESG
          </button>
        </div>
      </div>

      <div class="row g-4 align-items-start">

        <!-- ══ LEFT: input sections ══════════════════════════════════ -->
        <div class="col-xl-7">

          <!-- ── SCOPE 1 ──────────────────────────────────── -->
          <div class="cc-scope s1">
            <div class="cc-scope-hd">
              <span class="cc-spill">Scope 1</span>
              <div class="cc-scope-hd-body">
                <div class="cc-scope-hd-title">Direct Emissions</div>
                <div class="cc-scope-hd-desc">Fuels burned on-site and in company-owned vehicles</div>
              </div>
              <div class="cc-scope-subtotal">
                <span class="cc-st-val" id="st-scope1">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">

              <div class="cc-sub-label"><i class="bi bi-fire"></i>Stationary Combustion — generators, boilers, furnaces</div>
              <div class="cc-fields-grid cols-3">
                <?php
                $s1stat = [
                  ['diesel_litre',   '0.0026914', 'Diesel',       'litres', 'res-diesel',  '2.691 kg/ℓ'],
                  ['petrol_litre',   '0.0023098', 'Petrol/RON95', 'litres', 'res-petrol',  '2.310 kg/ℓ'],
                  ['natural_gas_m3', '0.0020160', 'Natural Gas',  'm³',     'res-gas',     '2.016 kg/m³'],
                  ['lpg_kg',         '0.0015100', 'LPG',          'kg',     'res-lpg',     '1.510 kg/kg'],
                  ['cng_m3',         '0.0021570', 'CNG',          'm³',     'res-cng',     '2.157 kg/m³'],
                ];
                foreach ($s1stat as [$fname, $ef, $label, $unit, $rid, $efLabel]):
                ?>
                <div class="cc-field">
                  <div class="cc-field-label">
                    <?= $label ?>
                    <span class="cc-field-unit">(<?= $unit ?>)</span>
                    <span class="cc-field-ef"><?= $efLabel ?></span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="<?= $fname ?>"
                           data-scope="scope1" data-ef="<?= $ef ?>" data-group="stationary"
                           min="0" step="1" value="<?= $inputs[$fname] ?>" placeholder="0">
                    <span class="cc-res-tag" id="<?= $rid ?>">0.000</span>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="cc-sub-label"><i class="bi bi-truck-front-fill"></i>Mobile Combustion — company-owned vehicles (annual km)</div>
              <div class="cc-fields-grid cols-2">
                <?php
                $s1fleet = [
                  ['fleet_diesel_km', '0.00016844', 'Diesel Fleet',  'km/year', 'res-fleet-diesel', '0.168 kg/km'],
                  ['fleet_petrol_km', '0.00015302', 'Petrol Fleet',  'km/year', 'res-fleet-petrol', '0.153 kg/km'],
                ];
                foreach ($s1fleet as [$fname, $ef, $label, $unit, $rid, $efLabel]):
                ?>
                <div class="cc-field">
                  <div class="cc-field-label">
                    <?= $label ?>
                    <span class="cc-field-unit">(<?= $unit ?>)</span>
                    <span class="cc-field-ef"><?= $efLabel ?></span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="<?= $fname ?>"
                           data-scope="scope1" data-ef="<?= $ef ?>" data-group="fleet"
                           min="0" step="100" value="<?= $inputs[$fname] ?>" placeholder="0">
                    <span class="cc-res-tag" id="<?= $rid ?>">0.000</span>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

            </div>
          </div>

          <!-- ── SCOPE 2 ──────────────────────────────────── -->
          <div class="cc-scope s2">
            <div class="cc-scope-hd">
              <span class="cc-spill">Scope 2</span>
              <div class="cc-scope-hd-body">
                <div class="cc-scope-hd-title">Purchased Electricity</div>
                <div class="cc-scope-hd-desc">TNB Peninsular grid &bull; 0.694 kgCO₂e/kWh (MyGHG 2023)</div>
              </div>
              <div class="cc-scope-subtotal">
                <span class="cc-st-val" id="st-scope2">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">
              <div class="cc-sub-label"><i class="bi bi-lightning-charge-fill"></i>Annual electricity consumption</div>
              <div class="cc-fields-grid cols-1">
                <div class="cc-field">
                  <div class="cc-field-label">
                    Annual Electricity Consumption
                    <span class="cc-field-unit">(kWh)</span>
                    <span class="cc-field-ef">0.694 kg/kWh</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="electricity_kwh"
                           data-scope="scope2" data-ef="0.000694" data-group="elec"
                           min="0" step="100" value="<?= $inputs['electricity_kwh'] ?>" placeholder="e.g. 240 000">
                    <span class="cc-res-tag" id="res-electricity">0.000 tCO₂e</span>
                  </div>
                  <div class="cc-field-hint">Sum all monthly kWh from TNB electricity bills for the year</div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── SCOPE 3 ──────────────────────────────────── -->
          <div class="cc-scope s3">
            <div class="cc-scope-hd">
              <span class="cc-spill">Scope 3</span>
              <div class="cc-scope-hd-body">
                <div class="cc-scope-hd-title">Value Chain Emissions</div>
                <div class="cc-scope-hd-desc">Indirect emissions from business activities</div>
              </div>
              <div class="cc-scope-subtotal">
                <span class="cc-st-val" id="st-scope3">0.00</span>
                <span class="cc-st-unit">tCO₂e</span>
              </div>
            </div>
            <div class="cc-scope-body">

              <div class="cc-sub-label"><i class="bi bi-airplane-fill"></i>Business Travel — annual passenger-km</div>
              <div class="cc-fields-grid cols-2">
                <div class="cc-field">
                  <div class="cc-field-label">
                    Domestic Flights
                    <span class="cc-field-unit">(pax-km)</span>
                    <span class="cc-field-ef">0.255 kg/pax-km</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="flight_domestic_km"
                           data-scope="scope3" data-ef="0.000255" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_domestic_km'] ?>" placeholder="0">
                    <span class="cc-res-tag" id="res-dom-flight">0.000</span>
                  </div>
                  <div class="cc-field-hint">KL↔Penang ≈ 310 km × no. of passengers</div>
                </div>
                <div class="cc-field">
                  <div class="cc-field-label">
                    International Flights
                    <span class="cc-field-unit">(pax-km)</span>
                    <span class="cc-field-ef">0.191 kg/pax-km</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="flight_intl_km"
                           data-scope="scope3" data-ef="0.00019085" data-group="travel"
                           min="0" step="100" value="<?= $inputs['flight_intl_km'] ?>" placeholder="0">
                    <span class="cc-res-tag" id="res-intl-flight">0.000</span>
                  </div>
                  <div class="cc-field-hint">KL↔London ≈ 10 540 km × no. of passengers</div>
                </div>
              </div>

              <div class="cc-sub-label"><i class="bi bi-trash3-fill"></i>Waste — annual disposal</div>
              <div class="cc-fields-grid cols-2">
                <div class="cc-field">
                  <div class="cc-field-label">
                    Landfill Waste
                    <span class="cc-field-unit">(kg)</span>
                    <span class="cc-field-ef">0.587 kg/kg</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="waste_landfill_kg"
                           data-scope="scope3" data-ef="0.00058685" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_landfill_kg'] ?>" placeholder="0">
                    <span class="cc-res-tag" id="res-landfill">0.000</span>
                  </div>
                </div>
                <div class="cc-field">
                  <div class="cc-field-label">
                    Recycled Waste
                    <span class="cc-field-unit">(kg)</span>
                    <span class="cc-field-ef">0.015 kg/kg</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="waste_recycled_kg"
                           data-scope="scope3" data-ef="0.00001467" data-group="waste"
                           min="0" step="1" value="<?= $inputs['waste_recycled_kg'] ?>" placeholder="0">
                    <span class="cc-res-tag" id="res-recycled">0.000</span>
                  </div>
                </div>
              </div>

              <div class="cc-sub-label"><i class="bi bi-droplet-fill"></i>Water consumption</div>
              <div class="cc-fields-grid cols-1">
                <div class="cc-field">
                  <div class="cc-field-label">
                    Water Consumption
                    <span class="cc-field-unit">(m³)</span>
                    <span class="cc-field-ef">0.149 kg/m³</span>
                  </div>
                  <div class="cc-field-inp">
                    <input type="number" class="calc-input" name="water_m3"
                           data-scope="scope3" data-ef="0.000149" data-group="water"
                           min="0" step="1" value="<?= $inputs['water_m3'] ?>" placeholder="0">
                    <span class="cc-res-tag" id="res-water">0.000</span>
                  </div>
                  <div class="cc-field-hint">1 000 litres = 1 m³</div>
                </div>
              </div>

            </div>
          </div>

        </div><!-- /col-left -->

        <!-- ══ RIGHT: results panel ═══════════════════════════════════ -->
        <div class="col-xl-5">
          <div class="cc-results-frame">

            <div class="cc-rf-header">
              <span class="pulse-dot"></span>
              Live Results
              <span style="margin-left:auto;font-size:11px;opacity:0.5"><?= $period ?></span>
            </div>

            <!-- Total -->
            <div class="cc-total-block" id="totalCard">
              <div class="cc-total-lbl">Total Annual Emissions</div>
              <div class="cc-total-row">
                <span class="cc-total-num" id="liveTotal">0.00</span>
                <span class="cc-total-unit-big">tCO₂e</span>
              </div>
              <div class="cc-total-intensity" id="liveIntensity">
                0.00 tCO₂e per employee
              </div>
            </div>

            <!-- Scope trio -->
            <div class="cc-scope-trio">
              <div class="cc-trio-cell">
                <div class="cc-trio-pill ct-s1">Scope 1</div>
                <div class="cc-trio-val" id="liveScope1">0.00</div>
                <div class="cc-trio-unit">tCO₂e</div>
                <div class="cc-trio-name">Direct</div>
              </div>
              <div class="cc-trio-cell">
                <div class="cc-trio-pill ct-s2">Scope 2</div>
                <div class="cc-trio-val" id="liveScope2">0.00</div>
                <div class="cc-trio-unit">tCO₂e</div>
                <div class="cc-trio-name">Electricity</div>
              </div>
              <div class="cc-trio-cell">
                <div class="cc-trio-pill ct-s3">Scope 3</div>
                <div class="cc-trio-val" id="liveScope3">0.00</div>
                <div class="cc-trio-unit">tCO₂e</div>
                <div class="cc-trio-name">Value Chain</div>
              </div>
            </div>

            <!-- Breakdown bars -->
            <div class="cc-breakdown">
              <div class="cc-section-title">
                Emission Breakdown
                <span style="font-weight:400">tCO₂e</span>
              </div>
              <?php
              $bdRows = [
                ['liveElec',       'bi-lightning-charge-fill', 'Electricity',      '#0891b2'],
                ['liveStationary', 'bi-fire',                  'Stationary',       '#f97316'],
                ['liveFleet',      'bi-truck-front-fill',      'Company Fleet',    '#d97706'],
                ['liveTravel',     'bi-airplane-fill',         'Business Travel',  '#7c3aed'],
                ['liveWaste',      'bi-trash3-fill',           'Waste',            '#64748b'],
                ['liveWater',      'bi-droplet-fill',          'Water',            '#0d9488'],
              ];
              foreach ($bdRows as [$id, $icon, $label, $color]):
              ?>
              <div class="cc-bd-row">
                <i class="bi <?= $icon ?> cc-bd-icon" style="color:<?= $color ?>"></i>
                <span class="cc-bd-label"><?= $label ?></span>
                <div class="cc-bd-bar-wrap">
                  <div class="cc-bd-bar" id="bar-<?= $id ?>" style="background:<?= $color ?>"></div>
                </div>
                <span class="cc-bd-val" id="<?= $id ?>">0.00</span>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Benchmark -->
            <div class="cc-benchmark">
              <div class="cc-section-title">
                Industry Benchmark
                <span style="font-weight:400"><?= htmlspecialchars($activeCompany['industry']) ?></span>
              </div>
              <div class="cc-bm-trio">
                <div class="cc-bm-chip" style="background:#fff7ed;color:#9a3412">
                  <div class="cc-bm-chip-lbl">Scope 1</div>
                  <div class="cc-bm-chip-val"><?= $benchmark['scope1'] ?></div>
                  <div class="cc-bm-chip-unit">tCO₂e/emp</div>
                </div>
                <div class="cc-bm-chip" style="background:#f0f9ff;color:#1e40af">
                  <div class="cc-bm-chip-lbl">Scope 2</div>
                  <div class="cc-bm-chip-val"><?= $benchmark['scope2'] ?></div>
                  <div class="cc-bm-chip-unit">tCO₂e/emp</div>
                </div>
                <div class="cc-bm-chip" style="background:#faf5ff;color:#6b21a8">
                  <div class="cc-bm-chip-lbl">Scope 3</div>
                  <div class="cc-bm-chip-val"><?= $benchmark['scope3'] ?></div>
                  <div class="cc-bm-chip-unit">tCO₂e/emp</div>
                </div>
              </div>
              <div class="cc-bm-note">
                <i class="bi bi-bullseye" style="color:#0ea5e9"></i>
                <?= $empCount ?> employees &bull;
                Target &lt; <strong><?= round(($benchmark['scope1']+$benchmark['scope2']+$benchmark['scope3']) * $empCount, 1) ?> tCO₂e</strong>
              </div>
            </div>

            <?php if ($saved && $result): ?>
            <div class="cc-saved-banner">
              <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:18px"></i>
              <div>
                <div style="font-weight:700">Saved to ESG indicators</div>
                <div style="font-size:11px;opacity:0.8">
                  S1: <?= $result['scope1'] ?> &bull;
                  S2: <?= $result['scope2'] ?> &bull;
                  S3: <?= $result['scope3'] ?> &bull;
                  <strong>Total: <?= $result['total'] ?> tCO₂e</strong>
                </div>
              </div>
            </div>
            <?php elseif ($result && !$saved): ?>
            <div style="margin:12px 20px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;font-size:12px;color:#0369a1;display:flex;align-items:center;gap:10px">
              <i class="bi bi-info-circle-fill" style="font-size:16px"></i>
              <div>Results calculated. Click <strong>Save to ESG</strong> to push Scope 1, 2 &amp; 3 values to your indicators.</div>
            </div>
            <?php endif; ?>

            <div class="cc-source">
              <i class="bi bi-info-circle"></i>
              Factors: Malaysia MyGHG 2nd Ed. (DOE 2023) &bull; DEFRA 2023 &bull; TNB 2023
            </div>

          </div>
        </div><!-- /col-right -->

      </div>
      </form>

      <!-- Emission Factors Reference -->
      <div class="cc-ef-card">
        <div class="cc-ef-toggle" onclick="toggleEF(this)">
          <span class="cc-ef-title">
            <i class="bi bi-table" style="color:#0ea5e9"></i>
            Emission Factors Reference Table
          </span>
          <i class="bi bi-chevron-down"></i>
        </div>
        <div class="cc-ef-body" style="display:none">
          <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:12px">
              <thead style="background:#f8fafc">
                <tr>
                  <th style="padding:10px 16px">Scope</th>
                  <th style="padding:10px 16px">Activity</th>
                  <th style="padding:10px 16px">Unit</th>
                  <th style="padding:10px 16px;text-align:right">kgCO₂e/unit</th>
                  <th style="padding:10px 16px">Source</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $efRows = [
                  ['2','Electricity — Peninsular Malaysia (TNB)', 'kWh',     0.694,    'MyGHG 2023 / TNB'],
                  ['2','Electricity — Sabah grid',                'kWh',     0.829,    'MyGHG 2023'],
                  ['2','Electricity — Sarawak grid',              'kWh',     0.512,    'MyGHG 2023'],
                  ['1','Diesel (stationary combustion)',           'litre',   2.6914,   'MyGHG 2023'],
                  ['1','Petrol / RON95 (stationary)',             'litre',   2.3098,   'MyGHG 2023'],
                  ['1','Natural Gas',                             'm³',      2.0160,   'MyGHG 2023'],
                  ['1','LPG',                                     'kg',      1.5100,   'MyGHG 2023'],
                  ['1','CNG',                                     'm³',      2.1570,   'MyGHG 2023'],
                  ['1','Fleet — diesel vehicle',                  'km',      0.16844,  'MyGHG 2023'],
                  ['1','Fleet — petrol vehicle',                  'km',      0.15302,  'MyGHG 2023'],
                  ['3','Flights — domestic (< 3 h)',              'pax-km',  0.255,    'DEFRA 2023'],
                  ['3','Flights — international (long-haul)',     'pax-km',  0.19085,  'DEFRA 2023'],
                  ['3','Waste — landfill',                        'kg',      0.58685,  'DEFRA 2023'],
                  ['3','Waste — recycled',                        'kg',      0.01467,  'DEFRA 2023'],
                  ['3','Water — supply & treatment',              'm³',      0.149,    'DEFRA 2023'],
                ];
                $sBg = ['1'=>'#fff7ed','2'=>'#f0f9ff','3'=>'#faf5ff'];
                $sColor = ['1'=>'#c2410c','2'=>'#0369a1','3'=>'#7c3aed'];
                foreach ($efRows as [$scope,$act,$unit,$kg,$src]):
                ?>
                <tr style="border-bottom:1px solid #f1f5f9">
                  <td style="padding:8px 16px">
                    <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:800;background:<?= $sBg[$scope] ?>;color:<?= $sColor[$scope] ?>">S<?= $scope ?></span>
                  </td>
                  <td style="padding:8px 16px;color:#334155"><?= htmlspecialchars($act) ?></td>
                  <td style="padding:8px 16px;color:#94a3b8"><?= $unit ?></td>
                  <td style="padding:8px 16px;text-align:right;font-family:monospace;font-weight:700;color:#0f172a"><?= number_format($kg, 5) ?></td>
                  <td style="padding:8px 16px;color:#94a3b8;font-size:11px"><?= $src ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div style="padding:12px 16px;font-size:11px;color:#94a3b8;border-top:1px solid #f1f5f9">
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
  electricity_kwh:    ['res-electricity', ' tCO₂e'],
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

    const map = fieldResMap[el.name];
    if (map) {
      const tag = document.getElementById(map[0]);
      if (tag) tag.textContent = emit.toFixed(3) + map[1];
    }
  });

  const total = scope1 + scope2 + scope3;

  setText('st-scope1', scope1.toFixed(2));
  setText('st-scope2', scope2.toFixed(2));
  setText('st-scope3', scope3.toFixed(2));
  setText('liveScope1', scope1.toFixed(2));
  setText('liveScope2', scope2.toFixed(2));
  setText('liveScope3', scope3.toFixed(2));
  setText('liveTotal',  total.toFixed(2));
  setText('liveIntensity', (total / empCount).toFixed(2) + ' tCO₂e per employee');

  const maxVal = Math.max(...Object.values(bd), 0.001);
  const idMap = {elec:'liveElec', stationary:'liveStationary', fleet:'liveFleet',
                 travel:'liveTravel', waste:'liveWaste', water:'liveWater'};
  Object.entries(idMap).forEach(([k, id]) => {
    const v = bd[k] || 0;
    setText(id, v.toFixed(2));
    const bar = document.getElementById('bar-' + id);
    if (bar) bar.style.width = ((v / maxVal) * 100).toFixed(1) + '%';
  });

  const card = document.getElementById('totalCard');
  card.className = 'cc-total-block' + (total > 500 ? ' total-high' : total > 50 ? ' total-medium' : ' total-low');
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

document.querySelectorAll('.calc-input').forEach(el => el.addEventListener('input', calcAll));
calcAll();

function toggleEF(hdr) {
  const body = hdr.nextElementSibling;
  const icon = hdr.querySelector('i.bi-chevron-down, i.bi-chevron-up');
  const open = body.style.display === 'none';
  body.style.display = open ? 'block' : 'none';
  if (icon) icon.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
}
</script>
