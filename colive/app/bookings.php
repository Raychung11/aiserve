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

$STATUSES = ['pending','approved','rejected','converted','cancelled'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $bid      = (int)($_POST['id']             ?? 0);
        $roomId   = (int)($_POST['room_id']        ?? 0);
        $bedId    = (int)($_POST['bed_id']         ?? 0) ?: null;
        $name     = trim($_POST['name']            ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone']           ?? '');
        $moveIn   = $_POST['move_in_date']         ?? '';
        $dur      = max(1,(int)($_POST['duration_months'] ?? 1));
        $rent     = (float)($_POST['quoted_rent']  ?? 0);
        $status   = $_POST['status']               ?? 'pending';
        $notes    = trim($_POST['notes']           ?? '');

        if ($name === '' || !$moveIn) {
            flashSet('danger', 'Name and move-in date are required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($status, $STATUSES, true)) $status = 'pending';

        // Verify room belongs to company
        $rChk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
        $rChk->execute([$roomId, $cid]);
        if (!$rChk->fetch()) {
            flashSet('danger', 'Invalid room selected.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        // Verify bed (if selected) belongs to room
        if ($bedId) {
            $bChk = $db->prepare('SELECT id FROM beds WHERE id=? AND room_id=? AND company_id=?');
            $bChk->execute([$bedId, $roomId, $cid]);
            if (!$bChk->fetch()) $bedId = null;
        }

        if ($bid) {
            $chk = $db->prepare('SELECT id FROM bookings WHERE id=? AND company_id=?');
            $chk->execute([$bid, $cid]);
            if (!$chk->fetch()) { flashSet('danger','Not found.'); header('Location: bookings.php'); exit; }
            $old = $db->prepare('SELECT * FROM bookings WHERE id=?');
            $old->execute([$bid]); $oldData = (array)$old->fetch();
            $db->prepare(
                'UPDATE bookings SET room_id=?,bed_id=?,name=?,email=?,phone=?,move_in_date=?,
                 duration_months=?,quoted_rent=?,status=?,notes=? WHERE id=? AND company_id=?'
            )->execute([$roomId,$bedId,$name,$email?:null,$phone,$moveIn,$dur,$rent,$status,$notes,$bid,$cid]);
            auditLog($db,'update','bookings',$bid,$oldData,['status'=>$status]);
            flashSet('success','Booking updated.');
            header('Location: bookings.php?action=edit&id='.$bid); exit;
        } else {
            $db->prepare(
                'INSERT INTO bookings (company_id,room_id,bed_id,name,email,phone,move_in_date,duration_months,quoted_rent,status,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid,$roomId,$bedId,$name,$email?:null,$phone,$moveIn,$dur,$rent,'pending',$notes]);
            $bid = (int)$db->lastInsertId();
            auditLog($db,'create','bookings',$bid,[],['name'=>$name,'room_id'=>$roomId]);
            flashSet('success','Booking created.');
            header('Location: bookings.php'); exit;
        }
    }

    if ($act === 'status') {
        $bid    = (int)($_POST['id']     ?? 0);
        $status = $_POST['new_status']   ?? '';
        if (!in_array($status, $STATUSES, true)) { header('Location: bookings.php'); exit; }
        $chk = $db->prepare('SELECT id FROM bookings WHERE id=? AND company_id=?');
        $chk->execute([$bid, $cid]);
        if ($chk->fetch()) {
            $db->prepare('UPDATE bookings SET status=? WHERE id=? AND company_id=?')
               ->execute([$status, $bid, $cid]);
            auditLog($db,'update','bookings',$bid,[],['status'=>$status]);
            flashSet('success', 'Status updated to ' . ucfirst($status) . '.');
        }
        header('Location: bookings.php'); exit;
    }
}

// ── Edit fetch + bed options ───────────────────────────────────────────────────
$editing  = null;
$bedOpts  = [];
if ($action === 'edit' && $id) {
    $stmt = $db->prepare(
        'SELECT bk.*, r.room_no, u.unit_no, b.name AS building_name
         FROM bookings bk
         JOIN rooms r ON r.id = bk.room_id
         JOIN units u ON u.id = r.unit_id
         JOIN buildings b ON b.id = u.building_id
         WHERE bk.id=? AND bk.company_id=?'
    );
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Not found.'); header('Location: bookings.php'); exit; }

    $bs = $db->prepare('SELECT * FROM beds WHERE room_id=? AND company_id=? ORDER BY bed_label');
    $bs->execute([$editing['room_id'], $cid]);
    $bedOpts = $bs->fetchAll();
}

// ── Room dropdown ─────────────────────────────────────────────────────────────
$roomOpts = $db->prepare(
    'SELECT r.id, r.room_no, r.base_rent, u.unit_no, b.name AS building_name
     FROM rooms r
     JOIN units u ON u.id = r.unit_id
     JOIN buildings b ON b.id = u.building_id
     WHERE r.company_id=? AND r.is_active=1
     ORDER BY b.name, u.unit_no, r.room_no'
);
$roomOpts->execute([$cid]);
$roomOpts = $roomOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'pending';
$sql    = 'SELECT bk.*, r.room_no, u.unit_no, b.name AS building_name
           FROM bookings bk
           JOIN rooms r ON r.id = bk.room_id
           JOIN units u ON u.id = r.unit_id
           JOIN buildings b ON b.id = u.building_id
           WHERE bk.company_id=?';
$params = [$cid];
if ($filterStatus && in_array($filterStatus, $STATUSES, true)) {
    $sql .= ' AND bk.status=?'; $params[] = $filterStatus;
} elseif ($filterStatus === 'all') {
    // no extra filter
}
$sql .= ' ORDER BY bk.move_in_date ASC, bk.created_at DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Tab counts
$cnts = $db->prepare('SELECT status, COUNT(*) AS n FROM bookings WHERE company_id=? GROUP BY status');
$cnts->execute([$cid]);
$tabCounts = [];
foreach ($cnts->fetchAll() as $row) $tabCounts[$row['status']] = (int)$row['n'];

$pageTitle  = 'Bookings';
$activePage = 'bookings';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0">Manage room booking requests</p>
  <a href="bookings.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>New Booking
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Booking #' . $editing['id'] : 'New Booking' ?></h6>
  <form method="POST" action="bookings.php" id="bkForm">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Room *</label>
        <select name="room_id" class="form-select form-select-sm" id="selRoom" required>
          <option value="">Select room&hellip;</option>
          <?php foreach ($roomOpts as $r): ?>
          <option value="<?= $r['id'] ?>" data-rent="<?= $r['base_rent'] ?>"
                  <?= ($editing['room_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['building_name'] . ' &mdash; ' . $r['unit_no'] . ' / ' . $r['room_no']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($bedOpts): ?>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Bed</label>
        <select name="bed_id" class="form-select form-select-sm">
          <option value="">Any bed</option>
          <?php foreach ($bedOpts as $b): ?>
          <option value="<?= $b['id'] ?>" <?= ($editing['bed_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>>
            Bed <?= e($b['bed_label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Applicant Name *</label>
        <input type="text" name="name" class="form-control form-control-sm"
               value="<?= e($editing['name'] ?? '') ?>" required autofocus>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Email</label>
        <input type="email" name="email" class="form-control form-control-sm"
               value="<?= e($editing['email'] ?? '') ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Phone</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="<?= e($editing['phone'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Move-In Date *</label>
        <input type="date" name="move_in_date" class="form-control form-control-sm"
               value="<?= e($editing['move_in_date'] ?? '') ?>" required>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Months</label>
        <input type="number" name="duration_months" class="form-control form-control-sm" min="1" max="24"
               value="<?= (int)($editing['duration_months'] ?? 1) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Quoted Rent</label>
        <input type="number" name="quoted_rent" id="inpRent" class="form-control form-control-sm" min="0" step="0.01"
               value="<?= number_format((float)($editing['quoted_rent'] ?? 0), 2) ?>">
      </div>
    </div>
    <?php if ($editing): ?>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Status</label>
      <select name="status" class="form-select form-select-sm" style="max-width:200px;">
        <?php foreach ($STATUSES as $s): ?>
        <option value="<?= $s ?>" <?= $editing['status'] === $s ? 'selected' : '' ?>>
          <?= ucfirst($s) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Booking' ?>
      </button>
      <a href="bookings.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php else: ?>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3" style="flex-wrap:wrap;">
  <?php
  $tabs = [
      'pending'   => 'Pending ('   . ($tabCounts['pending']   ?? 0) . ')',
      'approved'  => 'Approved ('  . ($tabCounts['approved']  ?? 0) . ')',
      'rejected'  => 'Rejected ('  . ($tabCounts['rejected']  ?? 0) . ')',
      'converted' => 'Converted (' . ($tabCounts['converted'] ?? 0) . ')',
      'all'       => 'All',
  ];
  foreach ($tabs as $val => $label):
    $isActive = ($filterStatus === $val);
  ?>
  <a href="bookings.php?status=<?= $val ?>"
     class="btn btn-sm <?= $isActive ? 'btn-brand' : 'btn-outline-secondary' ?>"
     style="font-size:.78rem;"><?= $label ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($bookings): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Applicant</th>
          <th>Room</th>
          <th>Move-In</th>
          <th>Duration</th>
          <th>Rent</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $statBadge = [
            'pending'   => 'badge-pending',
            'approved'  => 'badge-active',
            'rejected'  => 'badge-overdue',
            'converted' => 'badge-open',
            'cancelled' => '',
        ];
        foreach ($bookings as $bk):
        ?>
        <tr>
          <td style="color:#94a3b8;font-size:.78rem;">#<?= $bk['id'] ?></td>
          <td>
            <div class="fw-semibold" style="font-size:.85rem;"><?= e($bk['name']) ?></div>
            <?php if ($bk['phone']): ?>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($bk['phone']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;">
            <div><?= e($bk['room_no']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($bk['building_name'] . ' &mdash; ' . $bk['unit_no']) ?></div>
          </td>
          <td style="font-size:.82rem;"><?= dateDisplay($bk['move_in_date']) ?></td>
          <td style="font-size:.82rem;"><?= (int)$bk['duration_months'] ?> mo</td>
          <td style="font-size:.82rem;"><?= money((float)$bk['quoted_rent']) ?></td>
          <td>
            <span class="s-badge <?= $statBadge[$bk['status']] ?? '' ?>">
              <?= ucfirst($bk['status']) ?>
            </span>
          </td>
          <td class="text-end">
            <a href="bookings.php?action=edit&id=<?= $bk['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <?php if ($bk['status'] === 'pending'): ?>
            <form method="POST" class="d-inline">
              <?= csrfField() ?>
              <input type="hidden" name="_action"    value="status">
              <input type="hidden" name="id"         value="<?= $bk['id'] ?>">
              <input type="hidden" name="new_status" value="approved">
              <button class="btn btn-sm btn-outline-success" style="font-size:.75rem;">Approve</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-calendar-check-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No bookings in this status.</p>
    <a href="bookings.php?action=create" class="btn btn-brand btn-sm">Create Booking</a>
  </div>
  <?php endif; ?>
</div>

<?php
$extraJs = <<<JS
<script>
document.getElementById('selRoom')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const rent = opt.dataset.rent || '0';
  const inp  = document.getElementById('inpRent');
  if (inp && inp.value == '0') inp.value = parseFloat(rent).toFixed(2);
});
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
