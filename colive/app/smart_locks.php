<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();
requireRole('admin','manager');

$db  = getDB();
$cid = companyId();
$action = $_GET['action'] ?? 'list';

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $lid      = (int)($_POST['id']          ?? 0);
        $roomId   = (int)($_POST['room_id']      ?? 0);
        $deviceId = trim($_POST['device_id']     ?? '');
        $vendor   = trim($_POST['vendor']        ?? '');
        $label    = trim($_POST['label']         ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!$roomId || !$deviceId) {
            flashSet('danger', 'Room and device ID are required.');
            header('Location: smart_locks.php'); exit;
        }

        // Verify room belongs to this company
        $rChk = $db->prepare('SELECT id FROM rooms WHERE id=? AND company_id=?');
        $rChk->execute([$roomId, $cid]);
        if (!$rChk->fetch()) {
            flashSet('danger', 'Invalid room.');
            header('Location: smart_locks.php'); exit;
        }

        if ($lid) {
            $old = $db->prepare('SELECT * FROM smart_locks WHERE id=? AND company_id=?');
            $old->execute([$lid, $cid]);
            $oldRow = $old->fetch() ?: [];
            $db->prepare(
                'UPDATE smart_locks SET room_id=?,device_id=?,vendor=?,label=?,is_active=? WHERE id=? AND company_id=?'
            )->execute([$roomId, $deviceId, $vendor, $label, $isActive, $lid, $cid]);
            auditLog($db, 'update', 'smart_locks', $lid, $oldRow, ['room_id'=>$roomId,'device_id'=>$deviceId,'vendor'=>$vendor,'is_active'=>$isActive]);
            flashSet('success', 'Lock updated.');
        } else {
            $db->prepare(
                'INSERT INTO smart_locks (company_id,room_id,device_id,vendor,label,is_active) VALUES (?,?,?,?,?,?)'
            )->execute([$cid, $roomId, $deviceId, $vendor, $label, $isActive]);
            $newId = (int)$db->lastInsertId();
            auditLog($db, 'create', 'smart_locks', $newId, [], ['room_id'=>$roomId,'device_id'=>$deviceId,'vendor'=>$vendor]);
            flashSet('success', 'Lock registered.');
        }
        header('Location: smart_locks.php'); exit;
    }

    if ($act === 'delete') {
        $lid = (int)($_POST['id'] ?? 0);
        $old = $db->prepare('SELECT * FROM smart_locks WHERE id=? AND company_id=?');
        $old->execute([$lid, $cid]);
        $oldRow = $old->fetch();
        if ($oldRow) {
            $db->prepare('DELETE FROM smart_locks WHERE id=? AND company_id=?')->execute([$lid, $cid]);
            auditLog($db, 'delete', 'smart_locks', $lid, $oldRow, []);
            flashSet('success', 'Lock removed.');
        }
        header('Location: smart_locks.php'); exit;
    }

    if ($act === 'unlock') {
        $lid = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM smart_locks WHERE id=? AND company_id=? AND is_active=1');
        $stmt->execute([$lid, $cid]);
        $lock = $stmt->fetch();
        if ($lock) {
            // IoT stub — log the remote unlock attempt
            $uid = $_SESSION['user_id'] ?? null;
            $db->prepare(
                'INSERT INTO lock_access_logs (company_id,lock_id,triggered_by,method,result,note)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$cid, $lid, $uid, 'remote', 'success', 'Manual remote unlock via dashboard']);
            flashSet('success', 'Remote unlock command sent (stub).');
        } else {
            flashSet('danger', 'Lock not found or inactive.');
        }
        header('Location: smart_locks.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
$editId  = (int)($_GET['id'] ?? 0);
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT * FROM smart_locks WHERE id=? AND company_id=?');
    $stmt->execute([$editId, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger', 'Not found.'); header('Location: smart_locks.php'); exit; }
}

// ── Room dropdown ─────────────────────────────────────────────────────────────
$roomOpts = $db->prepare(
    'SELECT r.id, r.room_no, u.unit_no, b.name AS building_name
     FROM rooms r JOIN units u ON u.id=r.unit_id JOIN buildings b ON b.id=u.building_id
     WHERE r.company_id=? AND r.is_active=1 ORDER BY b.name, u.unit_no, r.room_no'
);
$roomOpts->execute([$cid]);
$roomOpts = $roomOpts->fetchAll();

// ── Lock list ─────────────────────────────────────────────────────────────────
$locks = $db->prepare(
    'SELECT sl.*, r.room_no, u.unit_no, b.name AS building_name,
            (SELECT COUNT(*) FROM lock_access_logs l WHERE l.lock_id=sl.id) AS log_count,
            (SELECT MAX(l.created_at) FROM lock_access_logs l WHERE l.lock_id=sl.id) AS last_access
     FROM smart_locks sl
     JOIN rooms r ON r.id=sl.room_id
     JOIN units u ON u.id=r.unit_id
     JOIN buildings b ON b.id=u.building_id
     WHERE sl.company_id=?
     ORDER BY b.name, u.unit_no, r.room_no'
);
$locks->execute([$cid]);
$locks = $locks->fetchAll();

// ── Recent access logs ────────────────────────────────────────────────────────
$filterLockId = (int)($_GET['lock_id'] ?? 0);
$logSql = 'SELECT l.*, sl.label AS lock_label, sl.device_id,
                  r.room_no, u.unit_no, b.name AS building_name,
                  us.full_name AS staff_name
           FROM lock_access_logs l
           JOIN smart_locks sl ON sl.id=l.lock_id
           JOIN rooms r ON r.id=sl.room_id
           JOIN units u ON u.id=r.unit_id
           JOIN buildings b ON b.id=u.building_id
           LEFT JOIN users us ON us.id=l.triggered_by
           WHERE l.company_id=?';
$logParams = [$cid];
if ($filterLockId) { $logSql .= ' AND l.lock_id=?'; $logParams[] = $filterLockId; }
$logSql .= ' ORDER BY l.created_at DESC LIMIT 50';
$logStmt = $db->prepare($logSql);
$logStmt->execute($logParams);
$accessLogs = $logStmt->fetchAll();

$pageTitle  = 'Smart Locks';
$activePage = 'smart_locks';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0">IoT smart lock management</p>
  <a href="smart_locks.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Register Lock
  </a>
</div>

<!-- IoT stub notice -->
<div class="alert" style="background:#fefce8;border:1px solid #fde68a;color:#92400e;border-radius:10px;font-size:.84rem;padding:.75rem 1rem;margin-bottom:1.25rem;">
  <i class="bi bi-info-circle me-2"></i>
  <strong>IoT Integration:</strong> Smart lock remote commands are stub-only. Connect your lock vendor API (TTLock, Igloohome, Yale, etc.) in <code>helpers.php</code> &mdash; the log table and remote unlock flow are ready.
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:560px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Lock' : 'Register Smart Lock' ?></h6>
  <form method="POST" action="smart_locks.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Room *</label>
      <select name="room_id" class="form-select form-select-sm" required>
        <option value="">Select room&hellip;</option>
        <?php foreach ($roomOpts as $r): ?>
        <option value="<?= $r['id'] ?>" <?= ($editing['room_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
          <?= e($r['building_name'] . ' &mdash; Unit ' . $r['unit_no'] . ' / ' . $r['room_no']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Device ID *</label>
        <input type="text" name="device_id" class="form-control form-control-sm"
               value="<?= e($editing['device_id'] ?? '') ?>" placeholder="e.g. TTL-00A1B2C3" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Vendor</label>
        <select name="vendor" class="form-select form-select-sm">
          <?php foreach (['','TTLock','Igloohome','Yale','Dormakaba','Other'] as $v): ?>
          <option value="<?= $v ?>" <?= ($editing['vendor'] ?? '') === $v ? 'selected' : '' ?>><?= $v ?: 'Select vendor&hellip;' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Display Label</label>
      <input type="text" name="label" class="form-control form-control-sm"
             value="<?= e($editing['label'] ?? '') ?>" placeholder="e.g. Room A1 Main Door">
    </div>
    <div class="mb-4 form-check">
      <input type="checkbox" name="is_active" id="chkActive" class="form-check-input"
             <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?> value="1">
      <label class="form-check-label" for="chkActive" style="font-size:.85rem;">Active</label>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm"><?= $editing ? 'Save Changes' : 'Register Lock' ?></button>
      <a href="smart_locks.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="row g-3">
  <!-- Lock list -->
  <div class="col-lg-6">
    <div class="card-box">
      <h6 class="fw-bold mb-3">Registered Locks</h6>
      <?php if ($locks): ?>
      <div class="table-responsive">
        <table class="table tbl mb-0">
          <thead>
            <tr><th>Room</th><th>Device</th><th>Vendor</th><th>Status</th><th>Logs</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($locks as $lk): ?>
            <tr>
              <td style="font-size:.82rem;">
                <div class="fw-semibold"><?= e($lk['room_no']) ?></div>
                <div style="color:#94a3b8;font-size:.75rem;"><?= e($lk['building_name'] . ' &mdash; ' . $lk['unit_no']) ?></div>
                <?php if ($lk['label']): ?>
                <div style="font-size:.72rem;color:#64748b;"><?= e($lk['label']) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:.78rem;font-family:monospace;color:#334155;"><?= e($lk['device_id']) ?></td>
              <td style="font-size:.78rem;color:#64748b;"><?= $lk['vendor'] ? e($lk['vendor']) : '&mdash;' ?></td>
              <td>
                <?php if ($lk['is_active']): ?>
                <span class="s-badge badge-paid">Online</span>
                <?php else: ?>
                <span class="s-badge badge-void">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="smart_locks.php?lock_id=<?= $lk['id'] ?>" style="font-size:.78rem;color:#64748b;">
                  <?= (int)$lk['log_count'] ?> logs
                </a>
              </td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <?php if ($lk['is_active']): ?>
                  <form method="POST" action="smart_locks.php" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="_action" value="unlock">
                    <input type="hidden" name="id" value="<?= $lk['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;"
                            title="Remote unlock">
                      <i class="bi bi-unlock-fill"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                  <a href="smart_locks.php?action=edit&id=<?= $lk['id'] ?>"
                     class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;">Edit</a>
                  <form method="POST" action="smart_locks.php" style="display:inline;"
                        onsubmit="return confirm('Remove this lock?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?= $lk['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="font-size:.72rem;background:#fee2e2;color:#b91c1c;border:none;">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-lock" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No smart locks registered yet.</p>
        <a href="smart_locks.php?action=create" class="btn btn-brand btn-sm">Register First Lock</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Access logs -->
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Access Logs</h6>
        <?php if ($filterLockId): ?>
        <a href="smart_locks.php" style="font-size:.78rem;">Show all</a>
        <?php endif; ?>
      </div>
      <?php if ($accessLogs): ?>
      <?php foreach ($accessLogs as $lg): ?>
      <div class="d-flex justify-content-between align-items-start py-2" style="border-bottom:1px solid #f8fafc;font-size:.82rem;">
        <div>
          <div class="fw-semibold">
            <?= e($lg['room_no']) ?>
            <?php if ($lg['lock_label']): ?>
            <span style="font-weight:400;color:#64748b;">(<?= e($lg['lock_label']) ?>)</span>
            <?php endif; ?>
          </div>
          <div style="color:#94a3b8;font-size:.75rem;"><?= e($lg['building_name'] . ' &mdash; ' . $lg['unit_no']) ?></div>
          <div style="font-size:.75rem;color:#64748b;">
            <?= ucfirst($lg['method']) ?>
            <?php if ($lg['staff_name']): ?>
            &mdash; <?= e($lg['staff_name']) ?>
            <?php endif; ?>
            <?php if ($lg['note']): ?>
            &mdash; <?= e($lg['note']) ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-end" style="flex-shrink:0;margin-left:.75rem;">
          <?php if ($lg['result'] === 'success'): ?>
          <span class="s-badge badge-paid" style="font-size:.7rem;">Success</span>
          <?php else: ?>
          <span class="s-badge badge-void" style="font-size:.7rem;">Failed</span>
          <?php endif; ?>
          <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem;"><?= datetimeDisplay($lg['created_at']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="text-center py-4">
        <i class="bi bi-journal-text" style="font-size:2rem;color:#cbd5e1;"></i>
        <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">No access logs yet.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
