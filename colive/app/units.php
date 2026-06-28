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

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $uid     = (int)($_POST['id']          ?? 0);
        $bldId   = (int)($_POST['building_id'] ?? 0);
        $ownId   = (int)($_POST['owner_id']    ?? 0) ?: null;
        $unitNo  = trim($_POST['unit_no']      ?? '');
        $floor   = trim($_POST['floor']        ?? '') !== '' ? (int)$_POST['floor'] : null;
        $rooms   = max(1, (int)($_POST['total_rooms'] ?? 1));
        $rent    = (float)($_POST['master_rent']  ?? 0);
        $pday    = min(28, max(1, (int)($_POST['payout_day'] ?? 5)));
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $notes   = trim($_POST['notes'] ?? '');

        if ($unitNo === '') {
            flashSet('danger', 'Unit number is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        // Verify building belongs to company
        $bChk = $db->prepare('SELECT id FROM buildings WHERE id=? AND company_id=?');
        $bChk->execute([$bldId, $cid]);
        if (!$bChk->fetch()) {
            flashSet('danger', 'Invalid building selected.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        // Verify owner belongs to company (if provided)
        if ($ownId !== null) {
            $oChk = $db->prepare('SELECT id FROM owners WHERE id=? AND company_id=?');
            $oChk->execute([$ownId, $cid]);
            if (!$oChk->fetch()) $ownId = null;
        }

        if ($uid) {
            $chk = $db->prepare('SELECT id FROM units WHERE id=? AND company_id=?');
            $chk->execute([$uid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: units.php'); exit; }
            $old = $db->prepare('SELECT * FROM units WHERE id=?'); $old->execute([$uid]); $oldData = (array)$old->fetch();
            $db->prepare(
                'UPDATE units SET building_id=?,owner_id=?,unit_no=?,floor=?,total_rooms=?,master_rent=?,payout_day=?,is_active=?,notes=?
                 WHERE id=? AND company_id=?'
            )->execute([$bldId, $ownId, $unitNo, $floor, $rooms, $rent, $pday, $active, $notes, $uid, $cid]);
            auditLog($db, 'update', 'units', $uid, $oldData, ['unit_no' => $unitNo]);
            flashSet('success', 'Unit updated.');
        } else {
            $db->prepare(
                'INSERT INTO units (company_id,building_id,owner_id,unit_no,floor,total_rooms,master_rent,payout_day,is_active,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid, $bldId, $ownId, $unitNo, $floor, $rooms, $rent, $pday, $active, $notes]);
            $uid = (int)$db->lastInsertId();
            auditLog($db, 'create', 'units', $uid, [], ['unit_no' => $unitNo, 'building_id' => $bldId]);
            flashSet('success', 'Unit created.');
        }
        header('Location: units.php'); exit;
    }

    if ($act === 'delete') {
        $uid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare('SELECT COUNT(*) FROM rooms WHERE unit_id=? AND company_id=?');
        $chk->execute([$uid, $cid]);
        if ((int)$chk->fetchColumn() > 0) {
            flashSet('danger', 'Cannot delete: remove all rooms in this unit first.');
            header('Location: units.php'); exit;
        }
        $db->prepare('DELETE FROM units WHERE id=? AND company_id=?')->execute([$uid, $cid]);
        auditLog($db, 'delete', 'units', $uid, [], []);
        flashSet('success', 'Unit deleted.');
        header('Location: units.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM units WHERE id=? AND company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger', 'Unit not found.'); header('Location: units.php'); exit; }
}

// ── Dropdown options ──────────────────────────────────────────────────────────
$bldOpts = $db->prepare('SELECT id, name FROM buildings WHERE company_id=? ORDER BY name');
$bldOpts->execute([$cid]);
$bldOpts = $bldOpts->fetchAll();

$ownOpts = $db->prepare('SELECT id, name FROM owners WHERE company_id=? AND is_active=1 ORDER BY name');
$ownOpts->execute([$cid]);
$ownOpts = $ownOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterBuilding = (int)($_GET['building_id'] ?? 0);
$sql    = 'SELECT u.*, b.name AS building_name, o.name AS owner_name,
                  COUNT(r.id) AS room_count
           FROM units u
           JOIN buildings b ON b.id = u.building_id
           LEFT JOIN owners o ON o.id = u.owner_id
           LEFT JOIN rooms r ON r.unit_id = u.id AND r.company_id = ?
           WHERE u.company_id = ?';
$params = [$cid, $cid];
if ($filterBuilding) {
    $sql .= ' AND u.building_id = ?';
    $params[] = $filterBuilding;
}
$sql .= ' GROUP BY u.id ORDER BY b.name ASC, u.unit_no ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$units = $stmt->fetchAll();

// Filter label
$filterLabel = '';
if ($filterBuilding) {
    foreach ($bldOpts as $b) {
        if ($b['id'] == $filterBuilding) { $filterLabel = $b['name']; break; }
    }
}

$pageTitle  = 'Units';
$activePage = 'units';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <p class="page-sub mb-0">
      <?= count($units) ?> unit<?= count($units) !== 1 ? 's' : '' ?>
      <?php if ($filterLabel): ?>
      &mdash; <a href="units.php" style="font-size:.8rem;color:#64748b;">
        <?= e($filterLabel) ?> &times; clear
      </a>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($bldOpts): ?>
    <form method="GET" action="units.php" class="d-flex gap-2">
      <select name="building_id" class="form-select form-select-sm" style="width:auto;font-size:.8rem;"
              onchange="this.form.submit()">
        <option value="">All Buildings</option>
        <?php foreach ($bldOpts as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $filterBuilding == $b['id'] ? 'selected' : '' ?>>
          <?= e($b['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    <a href="units.php?action=create<?= $filterBuilding ? '&building_id='.$filterBuilding : '' ?>"
       class="btn btn-brand btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Unit
    </a>
  </div>
</div>

<?php if (!$bldOpts): ?>
<div class="alert alert-warning" style="font-size:.87rem;">
  <i class="bi bi-info-circle me-2"></i>
  Add at least one <a href="buildings.php">building</a> before creating units.
</div>
<?php endif; ?>

<?php if (($action === 'create' || $editing) && $bldOpts): ?>
<div class="card-box mb-4" style="max-width:680px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Unit' : 'New Unit' ?></h6>
  <form method="POST" action="units.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Building *</label>
        <select name="building_id" class="form-select form-select-sm" required>
          <option value="">Select building&hellip;</option>
          <?php
          $preBuilding = $editing['building_id'] ?? $filterBuilding;
          foreach ($bldOpts as $b):
          ?>
          <option value="<?= $b['id'] ?>" <?= $preBuilding == $b['id'] ? 'selected' : '' ?>>
            <?= e($b['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Unit No. *</label>
        <input type="text" name="unit_no" class="form-control form-control-sm"
               placeholder="e.g. A-12-3" value="<?= e($editing['unit_no'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Floor</label>
        <input type="number" name="floor" class="form-control form-control-sm" min="0" max="200"
               value="<?= $editing['floor'] ?? '' ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Owner</label>
        <select name="owner_id" class="form-select form-select-sm">
          <option value="">No owner assigned</option>
          <?php foreach ($ownOpts as $o): ?>
          <option value="<?= $o['id'] ?>" <?= ($editing['owner_id'] ?? 0) == $o['id'] ? 'selected' : '' ?>>
            <?= e($o['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Total Rooms</label>
        <input type="number" name="total_rooms" class="form-control form-control-sm" min="1" max="50"
               value="<?= (int)($editing['total_rooms'] ?? 1) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Payout Day</label>
        <input type="number" name="payout_day" class="form-control form-control-sm" min="1" max="28"
               value="<?= (int)($editing['payout_day'] ?? 5) ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Master Rent (RM/month)</label>
        <input type="number" name="master_rent" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['master_rent'] ?? 0), 2) ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="is_active" id="chkActive"
             <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="chkActive" style="font-size:.85rem;">Active</label>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Unit' ?>
      </button>
      <a href="units.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($units): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>Unit</th>
          <th>Building</th>
          <th>Owner</th>
          <th>Rooms</th>
          <th>Master Rent</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($units as $u): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($u['unit_no']) ?></div>
            <?php if ($u['floor'] !== null): ?>
            <div style="color:#94a3b8;font-size:.75rem;">Floor <?= (int)$u['floor'] ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.83rem;"><?= e($u['building_name']) ?></td>
          <td style="font-size:.83rem;"><?= $u['owner_name'] ? e($u['owner_name']) : '<span style="color:#cbd5e1;">&mdash;</span>' ?></td>
          <td>
            <a href="rooms.php?unit_id=<?= $u['id'] ?>" class="fw-semibold text-brand">
              <?= (int)$u['room_count'] ?> / <?= (int)$u['total_rooms'] ?>
            </a>
          </td>
          <td style="font-size:.83rem;">
            <?= $u['master_rent'] > 0 ? money((float)$u['master_rent']) : '<span style="color:#cbd5e1;">&mdash;</span>' ?>
          </td>
          <td>
            <?= $u['is_active']
                ? '<span class="s-badge badge-active">Active</span>'
                : '<span class="s-badge badge-overdue">Inactive</span>' ?>
          </td>
          <td class="text-end">
            <a href="units.php?action=edit&id=<?= $u['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Delete unit <?= e(addslashes($u['unit_no'])) ?>?')">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="id"      value="<?= $u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" style="font-size:.75rem;">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-door-open-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">
      <?= $filterLabel ? 'No units in ' . e($filterLabel) . '.' : 'No units yet.' ?>
    </p>
    <?php if ($bldOpts): ?>
    <a href="units.php?action=create" class="btn btn-brand btn-sm">Add Unit</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
