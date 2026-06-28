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

$CATEGORIES = ['plumbing','electrical','aircon','furniture','lock','cleaning','pest','other'];
$PRIORITIES = ['low','medium','high','urgent'];
$STATUSES   = ['open','in_progress','resolved','closed'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $tid   = (int)($_POST['id']          ?? 0);
        $title = trim($_POST['title']        ?? '');
        $desc  = trim($_POST['description']  ?? '');
        $cat   = $_POST['category']          ?? 'other';
        $pri   = $_POST['priority']          ?? 'medium';
        $stat  = $_POST['status']            ?? 'open';
        $roomId = (int)($_POST['room_id']    ?? 0) ?: null;
        $unitId = (int)($_POST['unit_id']    ?? 0) ?: null;
        $assignId = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $notes = trim($_POST['notes']        ?? '');

        if ($title === '') {
            flashSet('danger', 'Title is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($cat,  $CATEGORIES, true)) $cat  = 'other';
        if (!in_array($pri,  $PRIORITIES, true)) $pri  = 'medium';
        if (!in_array($stat, $STATUSES,   true)) $stat = 'open';

        // Verify room/unit belong to company
        if ($roomId) {
            $rc = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
            $rc->execute([$roomId, $cid]);
            if (!$rc->fetch()) $roomId = null;
        }
        if ($unitId) {
            $uc = $db->prepare('SELECT id FROM units WHERE id=? AND company_id=?');
            $uc->execute([$unitId, $cid]);
            if (!$uc->fetch()) $unitId = null;
        }

        $resolvedAt = ($stat === 'resolved' || $stat === 'closed') ? 'NOW()' : 'NULL';

        if ($tid) {
            $chk = $db->prepare('SELECT id FROM maintenance_tickets WHERE id=? AND company_id=?');
            $chk->execute([$tid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: maintenance.php'); exit; }
            $old = $db->prepare('SELECT * FROM maintenance_tickets WHERE id=?');
            $old->execute([$tid]); $oldData = (array)$old->fetch();
            $db->prepare(
                "UPDATE maintenance_tickets
                 SET title=?,description=?,category=?,priority=?,status=?,room_id=?,unit_id=?,
                     assigned_to=?,notes=?,resolved_at=IF(status!=? AND ?=1,$resolvedAt,resolved_at)
                 WHERE id=? AND company_id=?"
            );
            // Simpler approach without dynamic SQL:
            $db->prepare(
                'UPDATE maintenance_tickets
                 SET title=?,description=?,category=?,priority=?,status=?,room_id=?,unit_id=?,assigned_to=?,notes=?
                 WHERE id=? AND company_id=?'
            )->execute([$title,$desc,$cat,$pri,$stat,$roomId,$unitId,$assignId,$notes,$tid,$cid]);
            if (in_array($stat, ['resolved','closed'], true) && !$oldData['resolved_at']) {
                $db->prepare('UPDATE maintenance_tickets SET resolved_at=NOW() WHERE id=? AND company_id=?')
                   ->execute([$tid, $cid]);
            }
            auditLog($db,'update','maintenance_tickets',$tid,$oldData,['status'=>$stat,'priority'=>$pri]);
            flashSet('success','Ticket updated.');
            header('Location: maintenance.php?action=edit&id='.$tid); exit;
        } else {
            $db->prepare(
                'INSERT INTO maintenance_tickets (company_id,title,description,category,priority,status,room_id,unit_id,assigned_to,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid,$title,$desc,$cat,$pri,'open',$roomId,$unitId,$assignId,$notes]);
            $tid = (int)$db->lastInsertId();
            auditLog($db,'create','maintenance_tickets',$tid,[],['title'=>$title,'priority'=>$pri]);
            flashSet('success','Ticket created.');
            header('Location: maintenance.php'); exit;
        }
    }

    if ($act === 'close') {
        $tid = (int)($_POST['id'] ?? 0);
        $db->prepare(
            "UPDATE maintenance_tickets SET status='closed',resolved_at=IFNULL(resolved_at,NOW()) WHERE id=? AND company_id=?"
        )->execute([$tid, $cid]);
        flashSet('success','Ticket closed.');
        header('Location: maintenance.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare(
        'SELECT t.*, r.room_no, u.unit_no, b.name AS building_name, usr.name AS assignee_name
         FROM maintenance_tickets t
         LEFT JOIN rooms r ON r.id = t.room_id
         LEFT JOIN units u ON u.id = t.unit_id
         LEFT JOIN buildings b ON b.id = u.building_id
         LEFT JOIN users usr ON usr.id = t.assigned_to
         WHERE t.id=? AND t.company_id=?'
    );
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Not found.'); header('Location: maintenance.php'); exit; }
}

// ── Dropdown options ──────────────────────────────────────────────────────────
$roomOpts = $db->prepare(
    'SELECT r.id, r.room_no, u.unit_no, b.name AS building_name
     FROM rooms r
     JOIN units u ON u.id = r.unit_id
     JOIN buildings b ON b.id = u.building_id
     WHERE r.company_id=? ORDER BY b.name, u.unit_no, r.room_no'
);
$roomOpts->execute([$cid]);
$roomOpts = $roomOpts->fetchAll();

$staffOpts = $db->prepare(
    "SELECT id, name, role FROM users WHERE company_id=? AND is_active=1
     AND role IN ('admin','manager','maintenance') ORDER BY name"
);
$staffOpts->execute([$cid]);
$staffOpts = $staffOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$sql    = "SELECT t.*, r.room_no, u.unit_no, b.name AS building_name, usr.name AS assignee_name
           FROM maintenance_tickets t
           LEFT JOIN rooms r ON r.id = t.room_id
           LEFT JOIN units u ON u.id = COALESCE(r.unit_id, t.unit_id)
           LEFT JOIN buildings b ON b.id = u.building_id
           LEFT JOIN users usr ON usr.id = t.assigned_to
           WHERE t.company_id=?";
$params = [$cid];
if ($filterStatus && in_array($filterStatus, $STATUSES, true)) {
    $sql .= ' AND t.status=?';
    $params[] = $filterStatus;
} elseif (!$filterStatus) {
    $sql .= " AND t.status IN ('open','in_progress')";
}
$sql .= ' ORDER BY FIELD(t.priority,"urgent","high","medium","low"), t.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Counts for tabs
$counts = $db->prepare(
    "SELECT status, COUNT(*) AS n FROM maintenance_tickets WHERE company_id=? GROUP BY status"
);
$counts->execute([$cid]);
$tabCounts = [];
foreach ($counts->fetchAll() as $row) $tabCounts[$row['status']] = (int)$row['n'];
$tabCounts['open_active'] = ($tabCounts['open'] ?? 0) + ($tabCounts['in_progress'] ?? 0);

$pageTitle  = 'Maintenance';
$activePage = 'maintenance';
include __DIR__ . '/layout.php';

$priBadge  = ['low'=>'badge-active','medium'=>'badge-medium','high'=>'badge-overdue','urgent'=>'badge-overdue'];
$priStyle  = ['urgent'=>'background:#fee2e2;color:#b91c1c;','high'=>'background:#fef3c7;color:#92400e;','medium'=>'background:#fef9c3;color:#a16207;','low'=>'background:#dcfce7;color:#15803d;'];
$statBadge = ['open'=>'badge-open','in_progress'=>'badge-pending','resolved'=>'badge-resolved','closed'=>'s-badge'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <p class="page-sub mb-0">Track and manage property maintenance requests</p>
  </div>
  <a href="maintenance.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>New Ticket
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:680px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Ticket #' . $editing['id'] : 'New Maintenance Ticket' ?></h6>
  <form method="POST" action="maintenance.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Title *</label>
      <input type="text" name="title" class="form-control form-control-sm"
             value="<?= e($editing['title'] ?? '') ?>" required autofocus
             placeholder="Brief description of the issue">
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Category</label>
        <select name="category" class="form-select form-select-sm">
          <?php foreach ($CATEGORIES as $c): ?>
          <option value="<?= $c ?>" <?= ($editing['category'] ?? 'other') === $c ? 'selected' : '' ?>>
            <?= ucfirst($c) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Priority</label>
        <select name="priority" class="form-select form-select-sm">
          <?php foreach ($PRIORITIES as $p): ?>
          <option value="<?= $p ?>" <?= ($editing['priority'] ?? 'medium') === $p ? 'selected' : '' ?>>
            <?= ucfirst($p) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($editing): ?>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Status</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach ($STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $editing['status'] === $s ? 'selected' : '' ?>>
            <?= ucwords(str_replace('_',' ',$s)) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Room (optional)</label>
        <select name="room_id" class="form-select form-select-sm">
          <option value="">None</option>
          <?php foreach ($roomOpts as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($editing['room_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
            <?= e($r['building_name'] . ' — Unit ' . $r['unit_no'] . ' / ' . $r['room_no']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Assign To</label>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">Unassigned</option>
          <?php foreach ($staffOpts as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($editing['assigned_to'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
            <?= e($s['name']) ?> (<?= ucfirst($s['role']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Description</label>
      <textarea name="description" class="form-control form-control-sm" rows="3"
                placeholder="Detailed description of the issue"><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Internal Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Ticket' ?>
      </button>
      <a href="maintenance.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
      <?php if ($editing): ?>
      <div class="ms-auto">
        <span style="font-size:.75rem;color:#94a3b8;">
          Created <?= datetimeDisplay($editing['created_at']) ?>
          <?= $editing['resolved_at'] ? ' &mdash; Resolved ' . datetimeDisplay($editing['resolved_at']) : '' ?>
        </span>
      </div>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php else: ?>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3" style="flex-wrap:wrap;">
  <?php
  $tabs = [
      ''          => 'Active (' . $tabCounts['open_active'] . ')',
      'open'      => 'Open (' . ($tabCounts['open'] ?? 0) . ')',
      'in_progress'=>'In Progress (' . ($tabCounts['in_progress'] ?? 0) . ')',
      'resolved'  => 'Resolved (' . ($tabCounts['resolved'] ?? 0) . ')',
      'all'       => 'All',
  ];
  foreach ($tabs as $val => $label):
    $active = ($filterStatus === $val) || ($val === '' && !$filterStatus);
  ?>
  <a href="maintenance.php<?= $val !== '' ? '?status=' . $val : '' ?>"
     class="btn btn-sm <?= $active ? 'btn-brand' : 'btn-outline-secondary' ?>"
     style="font-size:.78rem;"><?= $label ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($tickets): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Ticket</th>
          <th>Location</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Opened</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
          <td style="color:#94a3b8;font-size:.78rem;">#<?= $t['id'] ?></td>
          <td>
            <div class="fw-semibold" style="font-size:.85rem;"><?= e($t['title']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= ucfirst($t['category']) ?></div>
          </td>
          <td style="font-size:.82rem;">
            <?php if ($t['room_no']): ?>
            <div><?= e($t['room_no']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($t['building_name'] ?? '') ?></div>
            <?php elseif ($t['unit_no']): ?>
            <div>Unit <?= e($t['unit_no']) ?></div>
            <?php else: ?>
            <span style="color:#cbd5e1;">&mdash;</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="s-badge" style="<?= $priStyle[$t['priority']] ?? '' ?>;font-size:.7rem;">
              <?= ucfirst($t['priority']) ?>
            </span>
          </td>
          <td>
            <span class="s-badge <?= $statBadge[$t['status']] ?? '' ?>">
              <?= ucwords(str_replace('_',' ',$t['status'])) ?>
            </span>
          </td>
          <td style="font-size:.82rem;">
            <?= $t['assignee_name'] ? e($t['assignee_name']) : '<span style="color:#cbd5e1;">Unassigned</span>' ?>
          </td>
          <td style="color:#94a3b8;font-size:.78rem;"><?= dateDisplay($t['created_at']) ?></td>
          <td class="text-end">
            <a href="maintenance.php?action=edit&id=<?= $t['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <?php if (!in_array($t['status'], ['closed'], true)): ?>
            <form method="POST" class="d-inline">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="close">
              <input type="hidden" name="id"      value="<?= $t['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Close</button>
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
    <i class="bi bi-tools" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No maintenance tickets.</p>
    <a href="maintenance.php?action=create" class="btn btn-brand btn-sm">Log First Ticket</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
