<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'str-report';

// ── Period selector ───────────────────────────────────────────────────────────
$selYear  = (int)($_GET['year']  ?? date('Y'));
$selMonth = (int)($_GET['month'] ?? date('n'));
$period   = sprintf('%d-%02d', $selYear, $selMonth);
$periodLabel = date('F Y', mktime(0,0,0,$selMonth,1,$selYear));

// ── Building group helper ─────────────────────────────────────────────────────
function extractBuilding(string $name): string {
    // Strip parenthetical notes like "(START JAN26)" "(NOT YET START)"
    $clean = trim(preg_replace('/\s*\(.*?\)/', '', $name));
    // Unit code starts at a single uppercase letter followed by a hyphen + digits
    if (preg_match('/^(.*?)\s+[A-Z]-\d/', $clean, $m)) {
        return strtoupper(trim($m[1]));
    }
    return strtoupper(explode(' ', $clean)[0]);
}

// Detect row style for color coding
function rowStyle(array $stat, array $prop, string $period): array {
    $platformSales = (float)($stat['platform_sales'] ?? 0);
    $daysBooked    = (int)($stat['days_booked'] ?? 0);
    $name          = strtoupper($prop['name']);

    // Parse "(START MMM YY)" from property name
    $reportYM = date('MY', mktime(0,0,0,(int)substr($period,5,2),1,(int)substr($period,0,4)));
    $reportYM = strtoupper(date('MY', mktime(0,0,0,(int)substr($period,5,2),1,(int)substr($period,0,4))));

    // Green bg = started exactly this report month
    if (preg_match('/START\s+([A-Z]{3}\'?\d{2})/i', $name, $m)) {
        $startTag = strtoupper(str_replace("'",'',$m[1])); // e.g. "JAN26"
        $repTag   = strtoupper(date('Mj', mktime(0,0,0,$selMonth,1,$selYear)));
        $repTag   = strtoupper(date('M', mktime(0,0,0,$selMonth,1,$selYear)).substr($selYear,2));
        if ($startTag === $repTag) {
            return ['tr'=>'background:#d1fae5;','td'=>''];  // green bg
        }
        // Started an earlier month → red text
        return ['tr'=>'','td'=>'color:#dc2626;font-weight:700;'];
    }

    // Not yet started
    if (str_contains($name, 'NOT YET START')) {
        return ['tr'=>'','td'=>'color:#94a3b8;'];
    }

    // Zero bookings
    if ($daysBooked === 0 && $platformSales == 0) {
        return ['tr'=>'','td'=>'color:#dc2626;'];
    }

    return ['tr'=>'','td'=>''];
}

// ── POST: bulk save ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $postPeriod = $_POST['period'] ?? $period;
    $sales   = $_POST['platform_sales'] ?? [];
    $cleaning= $_POST['cleaning_fee']   ?? [];
    $days    = $_POST['days_booked']    ?? [];
    $notes   = $_POST['notes']          ?? [];

    foreach ($sales as $propId => $sale) {
        $propId  = (int)$propId;
        // Verify property belongs to this tenant
        if (!Database::count('properties','id=? AND tenant_id=?',[$propId,$_tenantId])) continue;

        $data = [
            'tenant_id'      => $_tenantId,
            'property_id'    => $propId,
            'period'         => $postPeriod,
            'platform_sales' => (float)str_replace(',','',$sale),
            'cleaning_fee'   => (float)str_replace(',','',$cleaning[$propId] ?? 0),
            'days_booked'    => (int)($days[$propId] ?? 0),
            'notes'          => trim($notes[$propId] ?? '') ?: null,
            'updated_by'     => $_user['id'],
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if (Database::count('str_monthly_stats','property_id=? AND period=?',[$propId,$postPeriod])) {
            Database::update('str_monthly_stats', $data, 'property_id=? AND period=?', [$propId,$postPeriod]);
        } else {
            Database::insert('str_monthly_stats', $data);
        }
    }
    ActivityLog::record('str.report_saved',"STR report saved for $postPeriod",$_tenantId,$_user['id']);
    $_SESSION['flash'] = ['success'=>"Report for $periodLabel saved."];
    header('Location: '.APP_URL.'/str-report?year='.$selYear.'&month='.$selMonth.'&view=1'); exit;
}

// ── Load all STR properties (strategy_mode = STR) ────────────────────────────
$properties = Database::fetchAll(
    "SELECT id, name, created_at, listing_status FROM properties
     WHERE tenant_id=? AND deleted_at IS NULL AND strategy_mode='STR'
     ORDER BY name",
    [$_tenantId]
);

// If no STR properties, fall back to all properties
if (!$properties) {
    $properties = Database::fetchAll(
        "SELECT id, name, created_at, listing_status FROM properties
         WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name",
        [$_tenantId]
    );
}

// Load stats for selected period
$statsRows = Database::fetchAll(
    "SELECT * FROM str_monthly_stats WHERE tenant_id=? AND period=?",
    [$_tenantId, $period]
);
$stats = array_column($statsRows, null, 'property_id');

// Group properties by building
$grouped = [];
foreach ($properties as $p) {
    $building = extractBuilding($p['name']);
    $grouped[$building][] = $p;
}
ksort($grouped);

// ── PRINT MODE ────────────────────────────────────────────────────────────────
if (isset($_GET['print'])) {
    $grandSales = $grandCleaning = $grandNet = $grandDays = 0;
    foreach ($stats as $s) {
        $grandSales    += $s['platform_sales'];
        $grandCleaning += $s['cleaning_fee'];
        $grandNet      += ($s['platform_sales'] - $s['cleaning_fee']);
        $grandDays     += $s['days_booked'];
    }
    ?>
    <!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>STR Report — <?= $periodLabel ?></title>
    <style>
    @page { margin:1.5cm; }
    @media print { .no-print{display:none!important;} body{-webkit-print-color-adjust:exact;print-color-adjust:exact;} }
    * { box-sizing:border-box; }
    body { font-family:Arial,sans-serif; font-size:11px; background:#fff; color:#000; margin:0; padding:1rem; }
    h2 { font-size:16px; font-weight:900; text-decoration:underline; margin:0 0 .5rem; }
    table { width:100%; border-collapse:collapse; border:2px solid #000; }
    th, td { border:1px solid #999; padding:5px 8px; }
    th { background:#FFD700; font-weight:700; text-align:center; font-size:11px; }
    td { font-size:11px; }
    td.unit { font-weight:700; }
    td.num { text-align:right; font-weight:700; }
    td.days { text-align:center; font-weight:700; }
    tr.group-header td { background:#f0f0f0; font-weight:900; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-top:2px solid #000; }
    tr.subtotal td { background:#fffbe6; font-weight:700; font-size:11px; border-top:2px solid #999; }
    tr.grand-total td { background:#FFD700; font-weight:900; font-size:12px; border-top:3px solid #000; }
    .red  { color:#dc2626; }
    .green-bg { background:#d1fae5!important; }
    .grey { color:#94a3b8; }
    .no-print { margin-bottom:1rem; }
    </style></head><body>
    <div class="no-print" style="display:flex;gap:.5rem;margin-bottom:1rem;">
      <button onclick="window.print()" style="background:#6366f1;color:#fff;border:none;padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:13px;">🖨 Print / Save PDF</button>
      <a href="<?= APP_URL ?>/str-report?year=<?=$selYear?>&month=<?=$selMonth?>&view=1" style="background:#f1f5f9;color:#333;border:1px solid #ccc;padding:.4rem 1rem;border-radius:6px;text-decoration:none;font-size:13px;">← Back</a>
    </div>

    <h2>STR <?= strtoupper($periodLabel) ?></h2>
    <p style="font-size:10px;color:#666;margin:.25rem 0 .75rem;">Generated <?= date('d M Y H:i') ?> · Roomee</p>

    <table>
      <thead>
        <tr>
          <th style="text-align:left;width:35%;">UNIT</th>
          <th>Platform Sales</th>
          <th>Cleaning Fee</th>
          <th>Gross Platform Sales</th>
          <th>Days Booked</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $grandSales = $grandCleaning = $grandNet = $grandDays = 0;
      foreach ($grouped as $building => $props):
          $bSales = $bCleaning = $bNet = $bDays = 0;
          foreach ($props as $p) {
              $s = $stats[$p['id']] ?? [];
              $bSales    += (float)($s['platform_sales'] ?? 0);
              $bCleaning += (float)($s['cleaning_fee'] ?? 0);
              $bNet      += ((float)($s['platform_sales'] ?? 0) - (float)($s['cleaning_fee'] ?? 0));
              $bDays     += (int)($s['days_booked'] ?? 0);
          }
          $grandSales    += $bSales;
          $grandCleaning += $bCleaning;
          $grandNet      += $bNet;
          $grandDays     += $bDays;
      ?>
        <tr class="group-header"><td colspan="5"><?= htmlspecialchars($building) ?></td></tr>
        <?php foreach ($props as $p):
            $s = $stats[$p['id']] ?? [];
            $style = rowStyle($s, $p, $period);
            $sale  = (float)($s['platform_sales'] ?? 0);
            $clean = (float)($s['cleaning_fee'] ?? 0);
            $net   = $sale - $clean;
            $days  = (int)($s['days_booked'] ?? 0);
            $tdStyle = $style['td'];
            $trClass = $style['tr'] ? 'class="green-bg"' : '';
            $colorClass = str_contains($tdStyle,'dc2626') ? 'red' : (str_contains($tdStyle,'94a3b8') ? 'grey' : '');
        ?>
        <tr <?= $trClass ?>>
          <td class="unit <?= $colorClass ?>"><?= htmlspecialchars($p['name']) ?></td>
          <td class="num <?= $colorClass ?>">RM<?= number_format($sale,2) ?></td>
          <td class="num <?= $colorClass ?>">RM<?= number_format($clean,2) ?></td>
          <td class="num <?= $colorClass ?>">RM<?= number_format($net,2) ?></td>
          <td class="days <?= $colorClass ?>"><?= $days ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="subtotal">
          <td style="text-align:right;">↳ <?= htmlspecialchars($building) ?> Subtotal</td>
          <td class="num">RM<?= number_format($bSales,2) ?></td>
          <td class="num">RM<?= number_format($bCleaning,2) ?></td>
          <td class="num">RM<?= number_format($bNet,2) ?></td>
          <td class="days"><?= $bDays ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="grand-total">
          <td>GRAND TOTAL</td>
          <td class="num">RM<?= number_format($grandSales,2) ?></td>
          <td class="num">RM<?= number_format($grandCleaning,2) ?></td>
          <td class="num">RM<?= number_format($grandNet,2) ?></td>
          <td class="days"><?= $grandDays ?></td>
        </tr>
      </tfoot>
    </table>
    </body></html>
    <?php exit;
}

// ── Admin shell view/edit ─────────────────────────────────────────────────────
$flash     = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);
$viewMode  = isset($_GET['view']);
$pageTitle = 'STR Monthly Report';
$pageSubtitle = $periodLabel;

// Totals
$totalSales = $totalCleaning = $totalNet = $totalDays = 0;
foreach ($stats as $s) {
    $totalSales    += $s['platform_sales'];
    $totalCleaning += $s['cleaning_fee'];
    $totalNet      += ($s['platform_sales'] - $s['cleaning_fee']);
    $totalDays     += $s['days_booked'];
}

include __DIR__.'/../includes/header.php'; ?>

<style>
.str-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; background:#f8fafc; }
.str-table td { font-size:.82rem; vertical-align:middle; }
.str-table tr.building-header td { background:#0f172a; color:#fff; font-weight:700; font-size:.75rem; letter-spacing:.06em; text-transform:uppercase; padding:.4rem .75rem; }
.str-table tr.subtotal-row td { background:#fefce8; font-weight:700; font-size:.8rem; border-top:2px solid #e2e8f0; }
.str-table tr.grand-row td { background:#fffbeb; font-weight:900; font-size:.85rem; border-top:3px solid #0f172a; }
.str-table tr.row-green { background:#d1fae5!important; }
.str-num { text-align:right!important; font-weight:600; }
.str-days { text-align:center!important; font-weight:700; }
.input-sm-str { width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:.25rem .5rem;font-size:.8rem;text-align:right; }
.input-sm-str:focus { outline:none;border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.15); }
.period-picker select { background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:.35rem .65rem;font-size:.875rem; }
</style>

<!-- Header bar -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h4 class="fw-bold mb-0">STR Monthly Report</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Platform sales, cleaning fees and occupancy per unit</p>
  </div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <!-- Period picker -->
    <form method="GET" action="<?= APP_URL ?>/str-report" class="d-flex gap-2 period-picker align-items-center" id="periodForm">
      <select name="month" onchange="this.form.submit()">
        <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=$m===$selMonth?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option>
        <?php endfor; ?>
      </select>
      <select name="year" onchange="this.form.submit()">
        <?php for($y=date('Y')+1;$y>=date('Y')-3;$y--): ?>
        <option value="<?=$y?>" <?=$y===$selYear?'selected':''?>><?=$y?></option>
        <?php endfor; ?>
      </select>
      <?php if($viewMode): ?><input type="hidden" name="view" value="1"><?php endif; ?>
    </form>
    <?php if($viewMode): ?>
    <a href="<?= APP_URL ?>/str-report?year=<?=$selYear?>&month=<?=$selMonth?>&print=1" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</a>
    <a href="<?= APP_URL ?>/str-report?year=<?=$selYear?>&month=<?=$selMonth?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit Data</a>
    <?php else: ?>
    <?php if($totalSales > 0 || $totalDays > 0): ?>
    <a href="<?= APP_URL ?>/str-report?year=<?=$selYear?>&month=<?=$selMonth?>&view=1" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View Report</a>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if($flash): ?>
<div class="alert alert-<?= isset($flash['success'])?'success':'danger' ?> alert-dismissible fade show">
  <?= htmlspecialchars($flash['success'] ?? $flash['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPI cards -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Platform Sales','RM '.number_format($totalSales,2),'bi-cash-stack','#6366f1'],
    ['Cleaning Fees','RM '.number_format($totalCleaning,2),'bi-bucket','#ef4444'],
    ['Gross (Net)','RM '.number_format($totalNet,2),'bi-graph-up-arrow','#10b981'],
    ['Days Booked',(string)$totalDays.' days','bi-calendar-check','#f59e0b'],
    ['Properties',(string)count($properties).' units','bi-buildings','#64748b'],
  ];
  foreach($cards as [$lbl,$val,$icon,$col]): ?>
  <div class="col-6 col-md col-lg">
    <div class="card-box text-center">
      <div style="color:<?=$col?>;font-size:1.1rem;margin-bottom:.3rem;"><i class="bi <?=$icon?>"></i></div>
      <div class="fw-bold" style="font-size:1rem;color:<?=$col?>;"><?=$val?></div>
      <div style="font-size:.7rem;color:#94a3b8;"><?=$lbl?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$properties): ?>
<div class="card-box text-center py-5">
  <i class="bi bi-buildings" style="font-size:3rem;color:#cbd5e1;"></i>
  <h5 class="mt-3 mb-2">No STR properties found</h5>
  <p class="text-muted">Add properties with strategy mode "STR" to use this report.</p>
  <a href="<?= APP_URL ?>/properties?action=create" class="btn btn-primary btn-sm">Add Property</a>
</div>
<?php else: ?>

<!-- Legend -->
<div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.75rem;">
  <span><span style="display:inline-block;width:12px;height:12px;background:#d1fae5;border:1px solid #6ee7b7;border-radius:2px;margin-right:4px;"></span>Started this month</span>
  <span><span style="display:inline-block;width:12px;height:12px;background:#fff;border:1px solid #fca5a5;border-radius:2px;margin-right:4px;"></span><span style="color:#dc2626;">Red</span> = New start (prev month) or 0 bookings</span>
  <span><span style="display:inline-block;width:12px;height:12px;background:#fff;border:1px solid #e2e8f0;border-radius:2px;margin-right:4px;"></span>Normal = active property</span>
</div>

<?php if ($viewMode): ?>
<!-- ══════════════ VIEW MODE ══════════════ -->
<div class="card-box p-0 overflow-hidden">
  <table class="table table-bordered mb-0 str-table">
    <thead>
      <tr>
        <th style="width:38%;">Unit</th>
        <th class="str-num">Platform Sales</th>
        <th class="str-num">Cleaning Fee</th>
        <th class="str-num">Gross Sales</th>
        <th class="str-days">Days Booked</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $gSales=$gClean=$gNet=$gDays=0;
    foreach ($grouped as $building => $props):
        $bSales=$bClean=$bNet=$bDays=0;
        foreach ($props as $p) {
            $s = $stats[$p['id']] ?? [];
            $bSales += (float)($s['platform_sales']??0);
            $bClean += (float)($s['cleaning_fee']??0);
            $bNet   += ((float)($s['platform_sales']??0)-(float)($s['cleaning_fee']??0));
            $bDays  += (int)($s['days_booked']??0);
        }
        $gSales+=$bSales; $gClean+=$bClean; $gNet+=$bNet; $gDays+=$bDays;
    ?>
      <tr class="building-header"><td colspan="5"><i class="bi bi-building me-2" style="color:#a5b4fc;"></i><?= htmlspecialchars($building) ?></td></tr>
      <?php foreach ($props as $p):
          $s     = $stats[$p['id']] ?? [];
          $style = rowStyle($s, $p, $period);
          $sale  = (float)($s['platform_sales']??0);
          $clean = (float)($s['cleaning_fee']??0);
          $net   = $sale-$clean;
          $days  = (int)($s['days_booked']??0);
          $trClass = $style['tr'] ? 'row-green' : '';
          $tdStyle = $style['td'];
      ?>
      <tr class="<?= $trClass ?>">
        <td style="<?= $tdStyle ?>"><?= htmlspecialchars($p['name']) ?></td>
        <td class="str-num" style="<?= $tdStyle ?>">RM <?= number_format($sale,2) ?></td>
        <td class="str-num" style="<?= $tdStyle ?>">RM <?= number_format($clean,2) ?></td>
        <td class="str-num" style="<?= $tdStyle ?>">RM <?= number_format($net,2) ?></td>
        <td class="str-days" style="<?= $tdStyle ?>"><?= $days ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="subtotal-row">
        <td class="text-end text-muted" style="font-size:.75rem;">↳ <?= htmlspecialchars($building) ?> Subtotal</td>
        <td class="str-num text-primary">RM <?= number_format($bSales,2) ?></td>
        <td class="str-num text-danger">RM <?= number_format($bClean,2) ?></td>
        <td class="str-num text-success">RM <?= number_format($bNet,2) ?></td>
        <td class="str-days"><?= $bDays ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="grand-row">
        <td>GRAND TOTAL — <?= strtoupper($periodLabel) ?></td>
        <td class="str-num" style="color:#6366f1;">RM <?= number_format($gSales,2) ?></td>
        <td class="str-num text-danger">RM <?= number_format($gClean,2) ?></td>
        <td class="str-num text-success">RM <?= number_format($gNet,2) ?></td>
        <td class="str-days"><?= $gDays ?></td>
      </tr>
    </tfoot>
  </table>
</div>

<?php else: ?>
<!-- ══════════════ EDIT / DATA ENTRY MODE ══════════════ -->
<form method="POST" action="<?= APP_URL ?>/str-report">
  <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="period" value="<?= $period ?>">

  <div class="card-box p-0 overflow-hidden mb-3">
    <table class="table table-bordered mb-0 str-table">
      <thead>
        <tr>
          <th style="width:36%;">Unit</th>
          <th>Platform Sales (RM)</th>
          <th>Cleaning Fee (RM)</th>
          <th class="str-num" style="min-width:110px;">Gross</th>
          <th>Days Booked</th>
          <th style="width:130px;">Notes</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($grouped as $building => $props): ?>
        <tr class="building-header"><td colspan="6"><i class="bi bi-building me-2" style="color:#a5b4fc;"></i><?= htmlspecialchars($building) ?></td></tr>
        <?php foreach ($props as $p):
            $s     = $stats[$p['id']] ?? [];
            $style = rowStyle($s, $p, $period);
            $sale  = (float)($s['platform_sales']??0);
            $clean = (float)($s['cleaning_fee']??0);
            $days  = (int)($s['days_booked']??0);
            $note  = $s['notes']??'';
            $trClass = $style['tr'] ? 'row-green' : '';
        ?>
        <tr class="<?= $trClass ?>" id="row_<?= $p['id'] ?>">
          <td style="<?= $style['td'] ?>"><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <input type="number" name="platform_sales[<?= $p['id'] ?>]"
                   class="input-sm-str ps-field" data-pid="<?= $p['id'] ?>"
                   value="<?= number_format($sale,2,'.','') ?>"
                   min="0" step="0.01" onchange="calcGross(<?= $p['id'] ?>)">
          </td>
          <td>
            <input type="number" name="cleaning_fee[<?= $p['id'] ?>]"
                   class="input-sm-str cf-field" data-pid="<?= $p['id'] ?>"
                   value="<?= number_format($clean,2,'.','') ?>"
                   min="0" step="0.01" onchange="calcGross(<?= $p['id'] ?>)">
          </td>
          <td class="str-num fw-bold" id="gross_<?= $p['id'] ?>" style="<?= $style['td'] ?>">
            RM <?= number_format($sale-$clean,2) ?>
          </td>
          <td>
            <input type="number" name="days_booked[<?= $p['id'] ?>]"
                   class="input-sm-str" style="text-align:center;"
                   value="<?= $days ?>" min="0" max="31">
          </td>
          <td>
            <input type="text" name="notes[<?= $p['id'] ?>]"
                   class="input-sm-str" style="text-align:left;"
                   value="<?= htmlspecialchars($note) ?>" placeholder="optional">
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Save Report — <?= $periodLabel ?></button>
  </div>
</form>

<script>
function calcGross(pid) {
  const sale  = parseFloat(document.querySelector('[name="platform_sales['+pid+']"]').value)||0;
  const clean = parseFloat(document.querySelector('[name="cleaning_fee['+pid+']"]').value)||0;
  document.getElementById('gross_'+pid).textContent = 'RM ' + (sale-clean).toFixed(2);
}
</script>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
