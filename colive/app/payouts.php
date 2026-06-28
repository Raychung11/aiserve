<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();
requireRole('admin','manager','finance');

$db     = getDB();
$cid    = companyId();
$action = $_GET['action'] ?? 'list';

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $pid      = (int)($_POST['id']         ?? 0);
        $ownId    = (int)($_POST['owner_id']   ?? 0);
        $unitId   = (int)($_POST['unit_id']    ?? 0);
        $period   = trim($_POST['period']      ?? '');
        $gross    = (float)($_POST['gross_rent']   ?? 0);
        $ded      = (float)($_POST['deductions']   ?? 0);
        $net      = round($gross - $ded, 2);
        $status   = $_POST['status']           ?? 'draft';
        $paidDate = $_POST['paid_date']        ?? '';
        $bankRef  = trim($_POST['bank_ref']    ?? '');
        $notes    = trim($_POST['notes']       ?? '');

        if (!$ownId || !$unitId || !$period) {
            flashSet('danger','Owner, unit and period are required.'); header('Location: payouts.php'); exit;
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            flashSet('danger','Period must be YYYY-MM.'); header('Location: payouts.php'); exit;
        }

        $oChk = $db->prepare('SELECT id FROM owners WHERE id=? AND company_id=?'); $oChk->execute([$ownId,$cid]);
        if (!$oChk->fetch()) { flashSet('danger','Invalid owner.'); header('Location: payouts.php'); exit; }
        $uChk = $db->prepare('SELECT id FROM units WHERE id=? AND company_id=?'); $uChk->execute([$unitId,$cid]);
        if (!$uChk->fetch()) { flashSet('danger','Invalid unit.'); header('Location: payouts.php'); exit; }

        $validStatuses = ['draft','approved','paid'];
        if (!in_array($status, $validStatuses, true)) $status = 'draft';

        if ($pid) {
            $db->prepare(
                'UPDATE owner_payouts SET owner_id=?,unit_id=?,period=?,gross_rent=?,deductions=?,net_payout=?,
                 status=?,paid_date=?,bank_ref=?,notes=? WHERE id=? AND company_id=?'
            )->execute([$ownId,$unitId,$period,$gross,$ded,$net,$status,
                        $paidDate?:null,$bankRef,$notes,$pid,$cid]);
            flashSet('success','Payout updated.');
        } else {
            try {
                $db->prepare(
                    'INSERT INTO owner_payouts (company_id,owner_id,unit_id,period,gross_rent,deductions,net_payout,status,paid_date,bank_ref,notes)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([$cid,$ownId,$unitId,$period,$gross,$ded,$net,$status,
                            $paidDate?:null,$bankRef,$notes]);
                flashSet('success','Payout created.');
            } catch (\PDOException $e) {
                flashSet('danger','A payout for this unit and period already exists.');
            }
        }
        header('Location: payouts.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
$editId  = (int)($_GET['id'] ?? 0);
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT * FROM owner_payouts WHERE id=? AND company_id=?');
    $stmt->execute([$editId, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Not found.'); header('Location: payouts.php'); exit; }
}

// ── Dropdown options ──────────────────────────────────────────────────────────
$ownOpts = $db->prepare('SELECT id, name FROM owners WHERE company_id=? AND is_active=1 ORDER BY name');
$ownOpts->execute([$cid]);
$ownOpts = $ownOpts->fetchAll();

$unitOpts = $db->prepare(
    'SELECT u.id, u.unit_no, u.master_rent, b.name AS building_name, o.name AS owner_name
     FROM units u JOIN buildings b ON b.id=u.building_id
     LEFT JOIN owners o ON o.id=u.owner_id
     WHERE u.company_id=? AND u.is_active=1 ORDER BY b.name, u.unit_no'
);
$unitOpts->execute([$cid]);
$unitOpts = $unitOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterPeriod = $_GET['period'] ?? date('Y-m');
$stmt = $db->prepare(
    'SELECT p.*, o.name AS owner_name, u.unit_no, b.name AS building_name
     FROM owner_payouts p
     JOIN owners o ON o.id=p.owner_id
     JOIN units u ON u.id=p.unit_id
     JOIN buildings b ON b.id=u.building_id
     WHERE p.company_id=? AND p.period=?
     ORDER BY o.name, u.unit_no'
);
$stmt->execute([$cid, $filterPeriod]);
$payouts = $stmt->fetchAll();

$totals = ['gross'=>0,'ded'=>0,'net'=>0];
foreach ($payouts as $p) {
    $totals['gross'] += (float)$p['gross_rent'];
    $totals['ded']   += (float)$p['deductions'];
    $totals['net']   += (float)$p['net_payout'];
}

$pageTitle  = 'Owner Payouts';
$activePage = 'payouts';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <p class="page-sub mb-0">Owner payout management</p>
  </div>
  <a href="payouts.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>New Payout
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:640px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Payout' : 'New Payout' ?></h6>
  <form method="POST" action="payouts.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Owner *</label>
        <select name="owner_id" class="form-select form-select-sm" required>
          <option value="">Select owner&hellip;</option>
          <?php foreach ($ownOpts as $o): ?>
          <option value="<?= $o['id'] ?>" <?= ($editing['owner_id'] ?? 0) == $o['id'] ? 'selected' : '' ?>>
            <?= e($o['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Unit *</label>
        <select name="unit_id" class="form-select form-select-sm" required id="selUnit">
          <option value="">Select unit&hellip;</option>
          <?php foreach ($unitOpts as $u): ?>
          <option value="<?= $u['id'] ?>" data-rent="<?= $u['master_rent'] ?>"
                  <?= ($editing['unit_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
            <?= e($u['building_name'] . ' &mdash; Unit ' . $u['unit_no']) ?>
            <?= $u['owner_name'] ? ' (' . e($u['owner_name']) . ')' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Period (YYYY-MM) *</label>
        <input type="text" name="period" class="form-control form-control-sm"
               pattern="\d{4}-\d{2}" value="<?= e($editing['period'] ?? $filterPeriod) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Gross Rent (RM)</label>
        <input type="number" name="gross_rent" id="inpGross" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['gross_rent'] ?? 0), 2) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Deductions (RM)</label>
        <input type="number" name="deductions" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['deductions'] ?? 0), 2) ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Status</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach (['draft','approved','paid'] as $s): ?>
          <option value="<?= $s ?>" <?= ($editing['status'] ?? 'draft') === $s ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Paid Date</label>
        <input type="date" name="paid_date" class="form-control form-control-sm"
               value="<?= $editing['paid_date'] ?? '' ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Bank Ref</label>
        <input type="text" name="bank_ref" class="form-control form-control-sm"
               value="<?= e($editing['bank_ref'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm"><?= $editing ? 'Save Changes' : 'Create Payout' ?></button>
      <a href="payouts.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Period filter + summary -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="d-flex gap-3">
    <?php
    for ($i = 0; $i < 6; $i++) {
        $mo = date('Y-m', strtotime("-$i months"));
        $isAct = $filterPeriod === $mo;
    ?>
    <a href="payouts.php?period=<?= $mo ?>"
       class="btn btn-sm <?= $isAct ? 'btn-brand' : 'btn-outline-secondary' ?>"
       style="font-size:.78rem;"><?= $mo ?></a>
    <?php } ?>
  </div>
</div>

<?php if ($payouts): ?>
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-lbl">Gross Rent</div>
      <div class="kpi-val"><?= money($totals['gross']) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-lbl">Total Deductions</div>
      <div class="kpi-val"><?= money($totals['ded']) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-lbl">Net Payouts</div>
      <div class="kpi-val" style="color:var(--brand);"><?= money($totals['net']) ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($payouts): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Owner</th><th>Unit</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th>Status</th><th>Paid</th><th></th></tr>
      </thead>
      <tbody>
        <?php
        $stBadge = ['draft'=>'badge-pending','approved'=>'badge-open','paid'=>'badge-paid'];
        foreach ($payouts as $p):
        ?>
        <tr>
          <td class="fw-semibold" style="font-size:.85rem;"><?= e($p['owner_name']) ?></td>
          <td style="font-size:.82rem;"><?= e($p['building_name'] . ' &mdash; ' . $p['unit_no']) ?></td>
          <td class="text-end" style="font-size:.82rem;"><?= money((float)$p['gross_rent']) ?></td>
          <td class="text-end" style="font-size:.82rem;color:#dc2626;"><?= $p['deductions'] > 0 ? '&minus; ' . money((float)$p['deductions']) : '&mdash;' ?></td>
          <td class="text-end fw-bold"><?= money((float)$p['net_payout']) ?></td>
          <td><span class="s-badge <?= $stBadge[$p['status']] ?? '' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td style="font-size:.8rem;color:#64748b;"><?= $p['paid_date'] ? dateDisplay($p['paid_date']) : '&mdash;' ?></td>
          <td class="text-end">
            <a href="payouts.php?action=edit&id=<?= $p['id'] ?>"
               class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-wallet2" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No payouts for <?= e($filterPeriod) ?>.</p>
    <a href="payouts.php?action=create" class="btn btn-brand btn-sm">Create Payout</a>
  </div>
  <?php endif; ?>
</div>

<?php
$extraJs = <<<JS
<script>
document.getElementById('selUnit')?.addEventListener('change', function() {
  const rent = this.options[this.selectedIndex].dataset.rent || '0';
  const inp  = document.getElementById('inpGross');
  if (inp && parseFloat(inp.value) === 0) inp.value = parseFloat(rent).toFixed(2);
});
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
