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

$ROOM_TYPES = ['single', 'twin', 'master', 'studio', 'common'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $rid     = (int)($_POST['id']             ?? 0);
        $unitId  = (int)($_POST['unit_id']        ?? 0);
        $roomNo  = trim($_POST['room_no']         ?? '');
        $type    = $_POST['room_type']            ?? 'single';
        $cap     = max(1, (int)($_POST['capacity'] ?? 1));
        $rent    = (float)($_POST['base_rent']    ?? 0);
        $dep     = max(0, (int)($_POST['deposit_months'] ?? 0));
        $bath    = isset($_POST['has_attached_bath']) ? 1 : 0;
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $notes   = trim($_POST['notes'] ?? '');

        if ($roomNo === '') {
            flashSet('danger', 'Room number is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($type, $ROOM_TYPES, true)) $type = 'single';

        // Verify unit belongs to company
        $uChk = $db->prepare('SELECT id FROM units WHERE id=? AND company_id=?');
        $uChk->execute([$unitId, $cid]);
        if (!$uChk->fetch()) {
            flashSet('danger', 'Invalid unit selected.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        if ($rid) {
            $chk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
            $chk->execute([$rid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: rooms.php'); exit; }
            $old = $db->prepare('SELECT * FROM rooms WHERE id=?'); $old->execute([$rid]); $oldData = (array)$old->fetch();
            $db->prepare(
                'UPDATE rooms SET unit_id=?,room_no=?,room_type=?,capacity=?,base_rent=?,deposit_months=?,
                 has_attached_bath=?,is_active=?,notes=? WHERE id=? AND company_id=?'
            )->execute([$unitId, $roomNo, $type, $cap, $rent, $dep, $bath, $active, $notes, $rid, $cid]);
            auditLog($db, 'update', 'rooms', $rid, $oldData, ['room_no' => $roomNo]);
            flashSet('success', 'Room updated.');
            header('Location: rooms.php?action=edit&id=' . $rid); exit;
        } else {
            $db->prepare(
                'INSERT INTO rooms (company_id,unit_id,room_no,room_type,capacity,base_rent,deposit_months,has_attached_bath,is_active,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid, $unitId, $roomNo, $type, $cap, $rent, $dep, $bath, $active, $notes]);
            $rid = (int)$db->lastInsertId();
            auditLog($db, 'create', 'rooms', $rid, [], ['room_no' => $roomNo, 'unit_id' => $unitId]);
            flashSet('success', 'Room created. Add beds below.');
            header('Location: rooms.php?action=edit&id=' . $rid); exit;
        }
    }

    if ($act === 'add_bed') {
        $rid   = (int)($_POST['room_id']   ?? 0);
        $label = trim($_POST['bed_label']  ?? '');
        if ($rid && $label !== '') {
            $rChk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
            $rChk->execute([$rid, $cid]);
            if ($rChk->fetch()) {
                $db->prepare('INSERT INTO beds (company_id,room_id,bed_label) VALUES (?,?,?)')
                   ->execute([$cid, $rid, $label]);
                flashSet('success', 'Bed ' . htmlspecialchars($label) . ' added.');
            }
        }
        header('Location: rooms.php?action=edit&id=' . $rid); exit;
    }

    if ($act === 'remove_bed') {
        $bid = (int)($_POST['bed_id']  ?? 0);
        $rid = (int)($_POST['room_id'] ?? 0);
        $db->prepare('DELETE FROM beds WHERE id=? AND company_id=?')->execute([$bid, $cid]);
        flashSet('success', 'Bed removed.');
        header('Location: rooms.php?action=edit&id=' . $rid); exit;
    }

    if ($act === 'delete') {
        $rid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare("SELECT COUNT(*) FROM tenancies WHERE room_id=? AND status IN ('active','pending') AND company_id=?");
        $chk->execute([$rid, $cid]);
        if ((int)$chk->fetchColumn() > 0) {
            flashSet('danger', 'Cannot delete: room has active tenancies.');
            header('Location: rooms.php'); exit;
        }
        $db->prepare('DELETE FROM beds  WHERE room_id=? AND company_id=?')->execute([$rid, $cid]);
        $db->prepare('DELETE FROM rooms WHERE id=?     AND company_id=?')->execute([$rid, $cid]);
        auditLog($db, 'delete', 'rooms', $rid, [], []);
        flashSet('success', 'Room deleted.');
        header('Location: rooms.php'); exit;
    }
}

// ── Edit fetch + beds ─────────────────────────────────────────────────────────
$editing = null;
$beds    = [];
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT r.*, u.unit_no, b.name AS building_name
                          FROM rooms r
                          JOIN units u ON u.id = r.unit_id
                          JOIN buildings b ON b.id = u.building_id
                          WHERE r.id=? AND r.company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger', 'Room not found.'); header('Location: rooms.php'); exit; }

    $bStmt = $db->prepare('SELECT * FROM beds WHERE room_id=? AND company_id=? ORDER BY bed_label');
    $bStmt->execute([$id, $cid]);
    $beds = $bStmt->fetchAll();
}

// ── Unit dropdown ─────────────────────────────────────────────────────────────
$unitOpts = $db->prepare(
    'SELECT u.id, u.unit_no, b.name AS building_name
     FROM units u
     JOIN buildings b ON b.id = u.building_id
     WHERE u.company_id=? AND u.is_active=1
     ORDER BY b.name, u.unit_no'
);
$unitOpts->execute([$cid]);
$unitOpts = $unitOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterUnit = (int)($_GET['unit_id'] ?? 0);
$sql    = 'SELECT r.*, u.unit_no, b.name AS building_name, COUNT(bed.id) AS bed_count
           FROM rooms r
           JOIN units u ON u.id = r.unit_id
           JOIN buildings b ON b.id = u.building_id
           LEFT JOIN beds bed ON bed.room_id = r.id AND bed.company_id = ?
           WHERE r.company_id = ?';
$params = [$cid, $cid];
if ($filterUnit) {
    $sql .= ' AND r.unit_id = ?';
    $params[] = $filterUnit;
}
$sql .= ' GROUP BY r.id ORDER BY b.name, u.unit_no, r.room_no';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$pageTitle  = 'Rooms &amp; Beds';
$activePage = 'rooms';
include __DIR__ . '/layout.php';

$typeLabel = ['single' => 'Single', 'twin' => 'Twin', 'master' => 'Master', 'studio' => 'Studio', 'common' => 'Common'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <p class="page-sub mb-0">
      <?= count($rooms) ?> room<?= count($rooms) !== 1 ? 's' : '' ?>
      <?php if ($filterUnit): ?>
      &mdash; <a href="rooms.php" style="font-size:.8rem;color:#64748b;">clear filter &times;</a>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($unitOpts): ?>
    <form method="GET" action="rooms.php" class="d-flex gap-2">
      <select name="unit_id" class="form-select form-select-sm" style="width:auto;font-size:.8rem;"
              onchange="this.form.submit()">
        <option value="">All Units</option>
        <?php foreach ($unitOpts as $u): ?>
        <option value="<?= $u['id'] ?>" <?= $filterUnit == $u['id'] ? 'selected' : '' ?>>
          <?= e($u['building_name'] . ' &mdash; Unit ' . $u['unit_no']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    <a href="rooms.php?action=create<?= $filterUnit ? '&unit_id='.$filterUnit : '' ?>"
       class="btn btn-brand btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Room
    </a>
  </div>
</div>

<?php if (!$unitOpts): ?>
<div class="alert alert-warning" style="font-size:.87rem;">
  <i class="bi bi-info-circle me-2"></i>
  Add at least one <a href="buildings.php">building</a> and <a href="units.php">unit</a> before creating rooms.
</div>
<?php endif; ?>

<?php if (($action === 'create' || $editing) && $unitOpts): ?>
<div class="card-box mb-4" style="max-width:680px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Room' : 'New Room' ?></h6>
  <form method="POST" action="rooms.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Unit *</label>
        <select name="unit_id" class="form-select form-select-sm" required>
          <option value="">Select unit&hellip;</option>
          <?php
          $preUnit = $editing['unit_id'] ?? $filterUnit;
          foreach ($unitOpts as $u):
          ?>
          <option value="<?= $u['id'] ?>" <?= $preUnit == $u['id'] ? 'selected' : '' ?>>
            <?= e($u['building_name'] . ' &mdash; Unit ' . $u['unit_no']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Room No. *</label>
        <input type="text" name="room_no" class="form-control form-control-sm"
               placeholder="e.g. R1" value="<?= e($editing['room_no'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Type</label>
        <select name="room_type" class="form-select form-select-sm">
          <?php foreach ($ROOM_TYPES as $t): ?>
          <option value="<?= $t ?>" <?= ($editing['room_type'] ?? 'single') === $t ? 'selected' : '' ?>>
            <?= ucfirst($t) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Capacity</label>
        <input type="number" name="capacity" class="form-control form-control-sm" min="1" max="20"
               value="<?= (int)($editing['capacity'] ?? 1) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Base Rent (RM)</label>
        <input type="number" name="base_rent" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['base_rent'] ?? 0), 2) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Deposit Months</label>
        <input type="number" name="deposit_months" class="form-control form-control-sm" min="0" max="6"
               value="<?= (int)($editing['deposit_months'] ?? 0) ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-auto">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="has_attached_bath" id="chkBath"
                 <?= !empty($editing['has_attached_bath']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="chkBath" style="font-size:.85rem;">Attached bathroom</label>
        </div>
      </div>
      <div class="col-auto">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" id="chkActive"
                 <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="chkActive" style="font-size:.85rem;">Active</label>
        </div>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Room' ?>
      </button>
      <a href="rooms.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php if ($editing): ?>
<!-- Beds management -->
<div class="card-box mb-4" style="max-width:680px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Beds</h6>
    <span style="font-size:.78rem;color:#94a3b8;">
      <?= count($beds) ?> bed<?= count($beds) !== 1 ? 's' : '' ?> &mdash; capacity <?= (int)$editing['capacity'] ?>
    </span>
  </div>

  <?php if ($beds): ?>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($beds as $bed): ?>
    <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:.3rem .65rem;display:flex;align-items:center;gap:.5rem;font-size:.82rem;">
      <span class="fw-semibold">Bed <?= e($bed['bed_label']) ?></span>
      <form method="POST" style="margin:0;" onsubmit="return confirm('Remove bed <?= e(addslashes($bed['bed_label'])) ?>?')">
        <?= csrfField() ?>
        <input type="hidden" name="_action"  value="remove_bed">
        <input type="hidden" name="bed_id"   value="<?= $bed['id'] ?>">
        <input type="hidden" name="room_id"  value="<?= $editing['id'] ?>">
        <button style="background:none;border:none;color:#94a3b8;padding:0;cursor:pointer;font-size:.85rem;line-height:1;"
                title="Remove">&times;</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem;">No beds added. Add bed labels below (e.g. A, B, 1, 2).</p>
  <?php endif; ?>

  <form method="POST" action="rooms.php" class="d-flex gap-2">
    <?= csrfField() ?>
    <input type="hidden" name="_action"  value="add_bed">
    <input type="hidden" name="room_id"  value="<?= $editing['id'] ?>">
    <input type="text" name="bed_label" class="form-control form-control-sm" style="max-width:120px;"
           placeholder="Label e.g. A" required maxlength="10" autofocus>
    <button type="submit" class="btn btn-sm btn-brand">Add Bed</button>
  </form>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Room list -->
<div class="card-box">
  <?php if ($rooms): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>Room</th>
          <th>Unit / Building</th>
          <th>Type</th>
          <th>Rent</th>
          <th>Beds</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
        <tr>
          <td class="fw-semibold"><?= e($r['room_no']) ?></td>
          <td style="font-size:.82rem;">
            <div><?= e($r['unit_no']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($r['building_name']) ?></div>
          </td>
          <td style="font-size:.82rem;">
            <?= e($typeLabel[$r['room_type']] ?? ucfirst($r['room_type'])) ?>
            <?php if ($r['has_attached_bath']): ?>
            <span style="font-size:.7rem;color:#64748b;">&nbsp;+bath</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.83rem;"><?= money((float)$r['base_rent']) ?></td>
          <td>
            <span class="fw-semibold"><?= (int)$r['bed_count'] ?></span>
            <span style="color:#94a3b8;font-size:.75rem;">/ <?= (int)$r['capacity'] ?></span>
          </td>
          <td>
            <?= $r['is_active']
                ? '<span class="s-badge badge-active">Active</span>'
                : '<span class="s-badge badge-overdue">Inactive</span>' ?>
          </td>
          <td class="text-end">
            <a href="rooms.php?action=edit&id=<?= $r['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Delete room <?= e(addslashes($r['room_no'])) ?>?')">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="id"      value="<?= $r['id'] ?>">
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
    <i class="bi bi-grid-3x3-gap-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No rooms yet.</p>
    <?php if ($unitOpts): ?>
    <a href="rooms.php?action=create" class="btn btn-brand btn-sm">Add Room</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
