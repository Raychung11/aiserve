<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db     = getDB();
$cid    = companyId();
$action = $_GET['action'] ?? 'list';

$UTIL_TYPES = ['electric','water','gas'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save_reading') {
        $rid    = (int)($_POST['id']            ?? 0);
        $roomId = (int)($_POST['room_id']       ?? 0);
        $type   = $_POST['utility_type']        ?? 'electric';
        $period = trim($_POST['period']         ?? '');
        $open   = (float)($_POST['reading_open']  ?? 0);
        $close  = (float)($_POST['reading_close'] ?? 0);
        $date   = $_POST['read_date']           ?? '';
        $source = $_POST['source']              ?? 'manual';

        if (!$roomId || !$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            flashSet('danger','Room and period (YYYY-MM) are required.');
            header('Location: utilities.php'); exit;
        }
        if (!in_array($type, $UTIL_TYPES, true)) $type = 'electric';
        if ($close < $open) {
            flashSet('danger','Closing reading cannot be less than opening reading.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        $rChk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
        $rChk->execute([$roomId, $cid]);
        if (!$rChk->fetch()) { flashSet('danger','Invalid room.'); header('Location: utilities.php'); exit; }

        if ($rid) {
            $db->prepare(
                'UPDATE utility_readings SET reading_open=?,reading_close=?,read_date=?,source=?
                 WHERE id=? AND company_id=?'
            )->execute([$open, $close, $date ?: null, $source, $rid, $cid]);
            flashSet('success','Reading updated.');
        } else {
            try {
                $db->prepare(
                    'INSERT INTO utility_readings (company_id,room_id,utility_type,period,reading_open,reading_close,read_date,source)
                     VALUES (?,?,?,?,?,?,?,?)'
                )->execute([$cid, $roomId, $type, $period, $open, $close, $date ?: null, $source]);
                flashSet('success','Reading saved.');
            } catch (\PDOException $e) {
                flashSet('danger','A reading for this room/period/type already exists.');
            }
        }
        header('Location: utilities.php'); exit;
    }

    if ($act === 'save_rate') {
        $type    = $_POST['utility_type']  ?? 'electric';
        $rate    = (float)($_POST['rate_per_unit'] ?? 0);
        $unit    = trim($_POST['unit_label']       ?? 'kWh');
        $effFrom = $_POST['effective_from']        ?? date('Y-m-d');

        if (!in_array($type, $UTIL_TYPES, true) || $rate <= 0) {
            flashSet('danger','Valid utility type and rate required.'); header('Location: utilities.php'); exit;
        }
        $db->prepare('INSERT INTO utility_rates (company_id,utility_type,rate_per_unit,unit_label,effective_from) VALUES (?,?,?,?,?)')
           ->execute([$cid, $type, $rate, $unit ?: 'kWh', $effFrom]);
        flashSet('success','Rate saved.');
        header('Location: utilities.php'); exit;
    }
}

// ── Room dropdown ─────────────────────────────────────────────────────────────
$roomOpts = $db->prepare(
    'SELECT r.id, r.room_no, u.unit_no, b.name AS building_name
     FROM rooms r JOIN units u ON u.id=r.unit_id JOIN buildings b ON b.id=u.building_id
     WHERE r.company_id=? AND r.is_active=1 ORDER BY b.name, u.unit_no, r.room_no'
);
$roomOpts->execute([$cid]);
$roomOpts = $roomOpts->fetchAll();

// ── List readings ─────────────────────────────────────────────────────────────
$filterPeriod = $_GET['period'] ?? date('Y-m');
$stmt = $db->prepare(
    'SELECT ur.*, r.room_no, u.unit_no, b.name AS building_name
     FROM utility_readings ur
     JOIN rooms r ON r.id=ur.room_id
     JOIN units u ON u.id=r.unit_id
     JOIN buildings b ON b.id=u.building_id
     WHERE ur.company_id=? AND ur.period=?
     ORDER BY ur.utility_type, b.name, u.unit_no, r.room_no'
);
$stmt->execute([$cid, $filterPeriod]);
$readings = $stmt->fetchAll();

// ── Latest rates ──────────────────────────────────────────────────────────────
$rateStmt = $db->prepare(
    'SELECT * FROM utility_rates WHERE company_id=?
     ORDER BY utility_type, effective_from DESC'
);
$rateStmt->execute([$cid]);
$allRates  = $rateStmt->fetchAll();
$latestRate = [];
foreach ($allRates as $r) {
    if (!isset($latestRate[$r['utility_type']])) $latestRate[$r['utility_type']] = $r;
}

$pageTitle  = 'Utility Readings';
$activePage = 'utilities';
include __DIR__ . '/layout.php';
?>

<div class="row g-4">
  <!-- Reading entry form -->
  <div class="col-lg-5">
    <div class="card-box mb-4">
      <h6 class="fw-bold mb-3">Record Meter Reading</h6>
      <form method="POST" action="utilities.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="save_reading">
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Room *</label>
          <select name="room_id" class="form-select form-select-sm" required>
            <option value="">Select room&hellip;</option>
            <?php foreach ($roomOpts as $r): ?>
            <option value="<?= $r['id'] ?>"><?= e($r['building_name'] . ' &mdash; Unit ' . $r['unit_no'] . ' / ' . $r['room_no']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Utility Type</label>
            <select name="utility_type" class="form-select form-select-sm">
              <?php foreach ($UTIL_TYPES as $t): ?>
              <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Period</label>
            <input type="text" name="period" class="form-control form-control-sm"
                   pattern="\d{4}-\d{2}" placeholder="YYYY-MM" value="<?= $filterPeriod ?>">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Opening Reading</label>
            <input type="number" name="reading_open" class="form-control form-control-sm" min="0" step="0.001" value="0">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Closing Reading</label>
            <input type="number" name="reading_close" class="form-control form-control-sm" min="0" step="0.001" value="0">
          </div>
        </div>
        <div class="row g-2 mb-4">
          <div class="col-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Read Date</label>
            <input type="date" name="read_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-brand btn-sm">Save Reading</button>
      </form>
    </div>

    <!-- Rate configuration -->
    <div class="card-box">
      <h6 class="fw-bold mb-3">Utility Rates</h6>
      <?php if ($latestRate): ?>
      <div class="mb-3">
        <?php foreach ($latestRate as $type => $rate): ?>
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f1f5f9;font-size:.83rem;">
          <div>
            <div class="fw-semibold"><?= ucfirst($type) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;">From <?= dateDisplay($rate['effective_from']) ?></div>
          </div>
          <div class="fw-bold">RM <?= number_format((float)$rate['rate_per_unit'], 4) ?>/<?= e($rate['unit_label']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="POST" action="utilities.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="save_rate">
        <div class="row g-2 mb-3">
          <div class="col-5">
            <label class="form-label fw-semibold" style="font-size:.82rem;">Type</label>
            <select name="utility_type" class="form-select form-select-sm">
              <?php foreach ($UTIL_TYPES as $t): ?><option value="<?= $t ?>"><?= ucfirst($t) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-4">
            <label class="form-label fw-semibold" style="font-size:.82rem;">Rate/Unit</label>
            <input type="number" name="rate_per_unit" class="form-control form-control-sm" min="0.0001" step="0.0001">
          </div>
          <div class="col-3">
            <label class="form-label fw-semibold" style="font-size:.82rem;">Unit</label>
            <input type="text" name="unit_label" class="form-control form-control-sm" value="kWh" maxlength="10">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.82rem;">Effective From</label>
          <input type="date" name="effective_from" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Set Rate</button>
      </form>
    </div>
  </div>

  <!-- Readings table -->
  <div class="col-lg-7">
    <div class="card-box">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Readings for <?= e($filterPeriod) ?></h6>
        <form method="GET" action="utilities.php" class="d-flex gap-2">
          <input type="text" name="period" class="form-control form-control-sm" style="width:100px;"
                 value="<?= e($filterPeriod) ?>" pattern="\d{4}-\d{2}">
          <button class="btn btn-sm btn-outline-secondary" style="font-size:.8rem;">Filter</button>
        </form>
      </div>

      <?php if ($readings): ?>
      <div class="table-responsive">
        <table class="table tbl mb-0">
          <thead>
            <tr><th>Room</th><th>Type</th><th class="text-end">Open</th><th class="text-end">Close</th><th class="text-end">Units Used</th><th class="text-end">Charge</th></tr>
          </thead>
          <tbody>
            <?php foreach ($readings as $r):
              $rate    = $latestRate[$r['utility_type']]['rate_per_unit'] ?? 0;
              $charge  = round((float)$r['units_used'] * (float)$rate, 2);
            ?>
            <tr>
              <td style="font-size:.82rem;">
                <div><?= e($r['room_no']) ?></div>
                <div style="color:#94a3b8;font-size:.75rem;"><?= e($r['building_name'] . ' &mdash; ' . $r['unit_no']) ?></div>
              </td>
              <td style="font-size:.78rem;">
                <span class="s-badge badge-open"><?= ucfirst($r['utility_type']) ?></span>
              </td>
              <td class="text-end" style="font-size:.82rem;"><?= number_format((float)$r['reading_open'], 3) ?></td>
              <td class="text-end" style="font-size:.82rem;"><?= number_format((float)$r['reading_close'], 3) ?></td>
              <td class="text-end fw-semibold" style="font-size:.82rem;"><?= number_format((float)$r['units_used'], 3) ?></td>
              <td class="text-end fw-semibold" style="font-size:.82rem;"><?= $rate ? money($charge) : '&mdash;' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="text-center py-4">
        <i class="bi bi-lightning-charge-fill" style="font-size:2rem;color:#cbd5e1;"></i>
        <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">No readings for <?= e($filterPeriod) ?>.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
