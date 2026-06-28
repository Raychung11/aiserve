<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db     = getDB();
$cid    = companyId();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$STATUSES = ['pending','active','terminated','expired'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $tid     = (int)($_POST['id']            ?? 0);
        $roomId  = (int)($_POST['room_id']       ?? 0);
        $bedId   = (int)($_POST['bed_id']        ?? 0) ?: null;
        $resId   = (int)($_POST['resident_id']   ?? 0);
        $bkId    = (int)($_POST['booking_id']    ?? 0) ?: null;
        $start   = $_POST['start_date']          ?? '';
        $end     = $_POST['end_date']            ?? '';
        $rent    = (float)($_POST['monthly_rent'] ?? 0);
        $dep     = (float)($_POST['deposit']      ?? 0);
        $bday    = min(28, max(1, (int)($_POST['billing_day'] ?? 1)));
        $status  = $_POST['status']              ?? 'pending';
        $notes   = trim($_POST['notes']          ?? '');

        if (!$roomId || !$resId || !$start || !$end) {
            flashSet('danger', 'Room, resident, and dates are required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($status, $STATUSES, true)) $status = 'pending';

        // Verify room and resident belong to company
        $rChk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
        $rChk->execute([$roomId, $cid]);
        if (!$rChk->fetch()) { flashSet('danger','Invalid room.'); header('Location: tenancies.php'); exit; }
        $pChk = $db->prepare('SELECT id FROM residents WHERE id=? AND company_id=?');
        $pChk->execute([$resId, $cid]);
        if (!$pChk->fetch()) { flashSet('danger','Invalid resident.'); header('Location: tenancies.php'); exit; }

        if ($bedId) {
            $bChk = $db->prepare('SELECT id FROM beds WHERE id=? AND room_id=? AND company_id=?');
            $bChk->execute([$bedId, $roomId, $cid]);
            if (!$bChk->fetch()) $bedId = null;
        }

        if ($tid) {
            $chk = $db->prepare('SELECT id FROM tenancies WHERE id=? AND company_id=?');
            $chk->execute([$tid, $cid]);
            if (!$chk->fetch()) { flashSet('danger','Not found.'); header('Location: tenancies.php'); exit; }
            $old = $db->prepare('SELECT * FROM tenancies WHERE id=?'); $old->execute([$tid]); $oldData = (array)$old->fetch();
            $db->prepare(
                'UPDATE tenancies SET room_id=?,bed_id=?,resident_id=?,start_date=?,end_date=?,
                 monthly_rent=?,deposit=?,billing_day=?,status=?,notes=?
                 WHERE id=? AND company_id=?'
            )->execute([$roomId,$bedId,$resId,$start,$end,$rent,$dep,$bday,$status,$notes,$tid,$cid]);
            auditLog($db,'update','tenancies',$tid,$oldData,['status'=>$status]);
            flashSet('success','Tenancy updated.');
            header('Location: tenancies.php?action=edit&id='.$tid); exit;
        } else {
            $db->prepare(
                'INSERT INTO tenancies (company_id,room_id,bed_id,resident_id,booking_id,start_date,end_date,
                 monthly_rent,deposit,billing_day,status,notes,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $cid,$roomId,$bedId,$resId,$bkId,$start,$end,
                $rent,$dep,$bday,$status,$notes,
                $_SESSION['user_id'] ?? null
            ]);
            $tid = (int)$db->lastInsertId();
            // Mark booking as converted if linked
            if ($bkId) {
                $db->prepare("UPDATE bookings SET status='converted',resident_id=? WHERE id=? AND company_id=?")
                   ->execute([$resId, $bkId, $cid]);
            }
            auditLog($db,'create','tenancies',$tid,[],['room_id'=>$roomId,'resident_id'=>$resId]);
            flashSet('success','Tenancy created.');
            header('Location: tenancies.php?action=edit&id='.$tid); exit;
        }
    }

    if ($act === 'terminate') {
        $tid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare('SELECT id FROM tenancies WHERE id=? AND company_id=?');
        $chk->execute([$tid, $cid]);
        if ($chk->fetch()) {
            $db->prepare("UPDATE tenancies SET status='terminated',end_date=CURDATE() WHERE id=? AND company_id=?")
               ->execute([$tid, $cid]);
            auditLog($db,'terminate','tenancies',$tid,[],[]);
            flashSet('success','Tenancy terminated.');
        }
        header('Location: tenancies.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
$invoices = [];
if ($action === 'edit' && $id) {
    $stmt = $db->prepare(
        'SELECT t.*, r.room_no, u.unit_no, b.name AS building_name,
                res.name AS resident_name, res.phone AS resident_phone
         FROM tenancies t
         JOIN rooms r ON r.id = t.room_id
         JOIN units u ON u.id = r.unit_id
         JOIN buildings b ON b.id = u.building_id
         JOIN residents res ON res.id = t.resident_id
         WHERE t.id=? AND t.company_id=?'
    );
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Tenancy not found.'); header('Location: tenancies.php'); exit; }

    $istmt = $db->prepare(
        'SELECT * FROM invoices WHERE tenancy_id=? AND company_id=? ORDER BY period DESC LIMIT 12'
    );
    $istmt->execute([$id, $cid]);
    $invoices = $istmt->fetchAll();
}

// ── Dropdown options ──────────────────────────────────────────────────────────
$roomOpts = $db->prepare(
    'SELECT r.id, r.room_no, r.base_rent, u.unit_no, b.name AS building_name
     FROM rooms r
     JOIN units u ON u.id = r.unit_id
     JOIN buildings b ON b.id = u.building_id
     WHERE r.company_id=? AND r.is_active=1 ORDER BY b.name, u.unit_no, r.room_no'
);
$roomOpts->execute([$cid]);
$roomOpts = $roomOpts->fetchAll();

$resOpts = $db->prepare('SELECT id, name, phone FROM residents WHERE company_id=? AND is_active=1 ORDER BY name');
$resOpts->execute([$cid]);
$resOpts = $resOpts->fetchAll();

$bkId = (int)($_GET['booking_id'] ?? 0);
$bkData = null;
if ($bkId && $action === 'create') {
    $bs = $db->prepare('SELECT * FROM bookings WHERE id=? AND company_id=? AND status="approved"');
    $bs->execute([$bkId, $cid]);
    $bkData = $bs->fetch() ?: null;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'active';
$sql    = 'SELECT t.*, r.room_no, u.unit_no, b.name AS building_name, res.name AS resident_name
           FROM tenancies t
           JOIN rooms r ON r.id = t.room_id
           JOIN units u ON u.id = r.unit_id
           JOIN buildings b ON b.id = u.building_id
           JOIN residents res ON res.id = t.resident_id
           WHERE t.company_id=?';
$params = [$cid];
if ($filterStatus && in_array($filterStatus, $STATUSES, true)) {
    $sql .= ' AND t.status=?'; $params[] = $filterStatus;
} elseif ($filterStatus === 'all') { /* no filter */ }
$sql .= ' ORDER BY t.start_date DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tenancies = $stmt->fetchAll();

$cnts = $db->prepare('SELECT status, COUNT(*) AS n FROM tenancies WHERE company_id=? GROUP BY status');
$cnts->execute([$cid]);
$tabCounts = [];
foreach ($cnts->fetchAll() as $row) $tabCounts[$row['status']] = (int)$row['n'];

$pageTitle  = 'Tenancies';
$activePage = 'tenancies';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0">Manage lease agreements and tenancy records</p>
  <a href="tenancies.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>New Tenancy
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <h6 class="fw-bold mb-3">
    <?= $editing
        ? 'Tenancy #' . $editing['id'] . ' &mdash; ' . e($editing['resident_name'])
        : 'New Tenancy' ?>
  </h6>
  <form method="POST" action="tenancies.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action"    value="save">
    <input type="hidden" name="id"         value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <input type="hidden" name="booking_id" value="<?= $bkId ?>">

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Resident *</label>
        <select name="resident_id" class="form-select form-select-sm" required>
          <option value="">Select resident&hellip;</option>
          <?php
          $preRes = $editing['resident_id'] ?? $bkData['resident_id'] ?? 0;
          foreach ($resOpts as $r):
          ?>
          <option value="<?= $r['id'] ?>" <?= $preRes == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['name']) ?> <?= $r['phone'] ? '(' . e($r['phone']) . ')' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Room *</label>
        <select name="room_id" class="form-select form-select-sm" required id="selTRoom">
          <option value="">Select room&hellip;</option>
          <?php
          $preRoom = $editing['room_id'] ?? $bkData['room_id'] ?? 0;
          foreach ($roomOpts as $r):
          ?>
          <option value="<?= $r['id'] ?>" data-rent="<?= $r['base_rent'] ?>"
                  <?= $preRoom == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['building_name'] . ' &mdash; Unit ' . $r['unit_no'] . ' / ' . $r['room_no']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Start Date *</label>
        <input type="date" name="start_date" class="form-control form-control-sm"
               value="<?= e($editing['start_date'] ?? $bkData['move_in_date'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">End Date *</label>
        <input type="date" name="end_date" class="form-control form-control-sm"
               value="<?= e($editing['end_date'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Monthly Rent (RM)</label>
        <input type="number" name="monthly_rent" id="inpTRent" class="form-control form-control-sm"
               min="0" step="0.01"
               value="<?= number_format((float)($editing['monthly_rent'] ?? $bkData['quoted_rent'] ?? 0), 2) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Deposit (RM)</label>
        <input type="number" name="deposit" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['deposit'] ?? 0), 2) ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Billing Day</label>
        <input type="number" name="billing_day" class="form-control form-control-sm" min="1" max="28"
               value="<?= (int)($editing['billing_day'] ?? 1) ?>">
      </div>
      <?php if ($editing): ?>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Status</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach ($STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $editing['status'] === $s ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Tenancy' ?>
      </button>
      <a href="tenancies.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
      <?php if ($editing && $editing['status'] === 'active'): ?>
      <form method="POST" class="ms-auto" onsubmit="return confirm('Terminate this tenancy?')">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="terminate">
        <input type="hidden" name="id"      value="<?= $editing['id'] ?>">
        <button class="btn btn-sm btn-outline-danger">Terminate</button>
      </form>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($editing && $invoices): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Invoices</h6>
    <a href="invoices.php?tenancy_id=<?= $editing['id'] ?>" class="btn btn-sm btn-outline-secondary"
       style="font-size:.75rem;">View All</a>
  </div>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead><tr><th>Invoice</th><th>Period</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td><a href="invoices.php?action=view&id=<?= $inv['id'] ?>" class="fw-semibold text-brand"><?= e($inv['invoice_no']) ?></a></td>
          <td style="font-size:.82rem;"><?= e($inv['period']) ?></td>
          <td style="font-size:.82rem;"><?= money((float)$inv['total_amount']) ?></td>
          <td>
            <span class="s-badge <?= [
              'draft'=>'badge-pending','issued'=>'badge-open','paid'=>'badge-paid',
              'partial'=>'badge-pending','overdue'=>'badge-overdue','void'=>''
            ][$inv['status']] ?? '' ?>">
              <?= ucfirst($inv['status']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif ($editing): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <p style="color:#94a3b8;font-size:.85rem;margin:0;">
    No invoices yet. <a href="invoices.php?action=create&tenancy_id=<?= $editing['id'] ?>">Generate first invoice</a>
  </p>
</div>
<?php endif; ?>

<?php else: ?>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3">
  <?php
  $tabs = ['active'=>'Active','pending'=>'Pending','terminated'=>'Terminated','expired'=>'Expired','all'=>'All'];
  foreach ($tabs as $val => $label):
    $n = $tabCounts[$val] ?? null;
    $isAct = $filterStatus === $val;
  ?>
  <a href="tenancies.php?status=<?= $val ?>"
     class="btn btn-sm <?= $isAct ? 'btn-brand' : 'btn-outline-secondary' ?>"
     style="font-size:.78rem;"><?= $label ?><?= $n !== null ? ' (' . $n . ')' : '' ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($tenancies): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Resident</th><th>Room</th><th>Period</th><th>Rent</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php
        $stBadge = ['active'=>'badge-active','pending'=>'badge-pending','terminated'=>'badge-overdue','expired'=>''];
        foreach ($tenancies as $t):
        ?>
        <tr>
          <td class="fw-semibold" style="font-size:.85rem;"><?= e($t['resident_name']) ?></td>
          <td style="font-size:.82rem;">
            <div><?= e($t['room_no']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($t['building_name'] . ' &mdash; ' . $t['unit_no']) ?></div>
          </td>
          <td style="font-size:.82rem;"><?= dateDisplay($t['start_date']) ?> &rarr; <?= dateDisplay($t['end_date']) ?></td>
          <td style="font-size:.82rem;"><?= money((float)$t['monthly_rent']) ?></td>
          <td><span class="s-badge <?= $stBadge[$t['status']] ?? '' ?>"><?= ucfirst($t['status']) ?></span></td>
          <td class="text-end">
            <a href="tenancies.php?action=edit&id=<?= $t['id'] ?>"
               class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-file-earmark-text-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No tenancies in this status.</p>
    <a href="tenancies.php?action=create" class="btn btn-brand btn-sm">Create Tenancy</a>
  </div>
  <?php endif; ?>
</div>

<?php
$extraJs = <<<JS
<script>
document.getElementById('selTRoom')?.addEventListener('change', function() {
  const rent = this.options[this.selectedIndex].dataset.rent || '0';
  const inp  = document.getElementById('inpTRent');
  if (inp && parseFloat(inp.value) === 0) inp.value = parseFloat(rent).toFixed(2);
});
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
