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
        $rid     = (int)($_POST['id']               ?? 0);
        $name    = trim($_POST['name']              ?? '');
        $ic      = trim($_POST['ic_number']         ?? '');
        $nat     = trim($_POST['nationality']       ?? 'Malaysian');
        $email   = strtolower(trim($_POST['email']  ?? ''));
        $phone   = trim($_POST['phone']             ?? '');
        $ename   = trim($_POST['emergency_name']    ?? '');
        $ephone  = trim($_POST['emergency_phone']   ?? '');
        $emp     = trim($_POST['employer']          ?? '');
        $occ     = trim($_POST['occupation']        ?? '');
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $pass    = $_POST['portal_password']        ?? '';

        if ($name === '') {
            flashSet('danger', 'Resident name is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        if ($rid) {
            $chk = $db->prepare('SELECT id FROM residents WHERE id=? AND company_id=?');
            $chk->execute([$rid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: residents.php'); exit; }
            $old = $db->prepare('SELECT * FROM residents WHERE id=?');
            $old->execute([$rid]); $oldData = (array)$old->fetch();

            $sql = 'UPDATE residents SET name=?,ic_number=?,nationality=?,email=?,phone=?,
                    emergency_name=?,emergency_phone=?,employer=?,occupation=?,is_active=?';
            $params = [$name,$ic,$nat,$email?:null,$phone,$ename,$ephone,$emp,$occ,$active];
            if ($pass !== '') {
                $sql .= ',password_hash=?';
                $params[] = password_hash($pass, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=? AND company_id=?';
            $params[] = $rid; $params[] = $cid;
            $db->prepare($sql)->execute($params);
            auditLog($db,'update','residents',$rid,$oldData,['name'=>$name]);
            flashSet('success','Resident updated.');
        } else {
            $db->prepare(
                'INSERT INTO residents (company_id,name,ic_number,nationality,email,phone,
                 emergency_name,emergency_phone,employer,occupation,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid,$name,$ic,$nat,$email?:null,$phone,$ename,$ephone,$emp,$occ,$active]);
            $rid = (int)$db->lastInsertId();

            if ($pass !== '') {
                $db->prepare('UPDATE residents SET password_hash=? WHERE id=? AND company_id=?')
                   ->execute([password_hash($pass, PASSWORD_DEFAULT), $rid, $cid]);
            }
            auditLog($db,'create','residents',$rid,[],['name'=>$name]);
            flashSet('success','Resident created.');
        }
        header('Location: residents.php'); exit;
    }

    if ($act === 'toggle') {
        $rid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare('SELECT is_active FROM residents WHERE id=? AND company_id=?');
        $chk->execute([$rid, $cid]);
        $row = $chk->fetch();
        if ($row) {
            $new = $row['is_active'] ? 0 : 1;
            $db->prepare('UPDATE residents SET is_active=? WHERE id=? AND company_id=?')
               ->execute([$new, $rid, $cid]);
            flashSet('success', $new ? 'Resident activated.' : 'Resident deactivated.');
        }
        header('Location: residents.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
$tenancies = [];
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM residents WHERE id=? AND company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Resident not found.'); header('Location: residents.php'); exit; }

    $tstmt = $db->prepare(
        'SELECT t.*, r.room_no, u.unit_no, b.name AS building_name
         FROM tenancies t
         JOIN rooms r ON r.id = t.room_id
         JOIN units u ON u.id = r.unit_id
         JOIN buildings b ON b.id = u.building_id
         WHERE t.resident_id=? AND t.company_id=?
         ORDER BY t.start_date DESC LIMIT 5'
    );
    $tstmt->execute([$id, $cid]);
    $tenancies = $tstmt->fetchAll();
}

// ── Search ────────────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$showInactive = isset($_GET['inactive']);
$sql    = 'SELECT * FROM residents WHERE company_id=?';
$params = [$cid];
if (!$showInactive) { $sql .= ' AND is_active=1'; }
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR ic_number LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like,$like,$like,$like]);
}
$sql .= ' ORDER BY name ASC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

$pageTitle  = 'Residents';
$activePage = 'residents';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <p class="page-sub mb-0">
      <?= count($residents) ?> resident<?= count($residents) !== 1 ? 's' : '' ?>
      <?php if ($showInactive): ?>
        &mdash; <a href="residents.php" style="font-size:.8rem;color:#64748b;">hide inactive</a>
      <?php else: ?>
        &mdash; <a href="residents.php?inactive" style="font-size:.8rem;color:#64748b;">show inactive</a>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" action="residents.php" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-sm" style="width:200px;font-size:.82rem;"
             placeholder="Search name / IC / email..." value="<?= e($search) ?>">
      <button class="btn btn-sm btn-outline-secondary" style="font-size:.82rem;">Search</button>
    </form>
    <a href="residents.php?action=create" class="btn btn-brand btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Resident
    </a>
  </div>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Resident' : 'New Resident' ?></h6>
  <form method="POST" action="residents.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Full Name *</label>
        <input type="text" name="name" class="form-control form-control-sm"
               value="<?= e($editing['name'] ?? '') ?>" required autofocus>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">IC Number</label>
        <input type="text" name="ic_number" class="form-control form-control-sm"
               placeholder="850101-14-5678" value="<?= e($editing['ic_number'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Nationality</label>
        <input type="text" name="nationality" class="form-control form-control-sm"
               value="<?= e($editing['nationality'] ?? 'Malaysian') ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Email</label>
        <input type="email" name="email" class="form-control form-control-sm"
               value="<?= e($editing['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Phone</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="<?= e($editing['phone'] ?? '') ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Employer</label>
        <input type="text" name="employer" class="form-control form-control-sm"
               value="<?= e($editing['employer'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Occupation</label>
        <input type="text" name="occupation" class="form-control form-control-sm"
               value="<?= e($editing['occupation'] ?? '') ?>">
      </div>
    </div>

    <hr style="border-color:#f1f5f9;">
    <p class="fw-semibold mb-2" style="font-size:.82rem;color:#64748b;">Emergency Contact</p>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Name</label>
        <input type="text" name="emergency_name" class="form-control form-control-sm"
               value="<?= e($editing['emergency_name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Phone</label>
        <input type="text" name="emergency_phone" class="form-control form-control-sm"
               value="<?= e($editing['emergency_phone'] ?? '') ?>">
      </div>
    </div>

    <hr style="border-color:#f1f5f9;">
    <p class="fw-semibold mb-2" style="font-size:.82rem;color:#64748b;">
      Portal Access <?= $editing && $editing['password_hash'] ? '(password already set)' : '' ?>
    </p>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">
          Portal Password <?= $editing ? '(leave blank to keep current)' : '' ?>
        </label>
        <input type="password" name="portal_password" class="form-control form-control-sm"
               autocomplete="new-password" <?= !$editing ? 'placeholder="Set a portal login password"' : '' ?>>
      </div>
    </div>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="is_active" id="chkActive"
             <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="chkActive" style="font-size:.85rem;">Active</label>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Add Resident' ?>
      </button>
      <a href="residents.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php if ($editing && $tenancies): ?>
<div class="card-box mb-4" style="max-width:720px;">
  <h6 class="fw-bold mb-3">Tenancy History</h6>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Room</th><th>Period</th><th>Rent</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tenancies as $t): ?>
        <tr>
          <td>
            <div class="fw-semibold" style="font-size:.83rem;"><?= e($t['room_no']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($t['building_name']) ?> &mdash; <?= e($t['unit_no']) ?></div>
          </td>
          <td style="font-size:.82rem;"><?= dateDisplay($t['start_date']) ?> &rarr; <?= dateDisplay($t['end_date']) ?></td>
          <td style="font-size:.82rem;"><?= money((float)$t['monthly_rent']) ?></td>
          <td>
            <span class="s-badge <?= $t['status'] === 'active' ? 'badge-active' : ($t['status'] === 'expired' ? 'badge-overdue' : 'badge-pending') ?>">
              <?= ucfirst($t['status']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="card-box">
  <?php if ($residents): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>Resident</th>
          <th>Contact</th>
          <th>IC Number</th>
          <th>Portal</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($residents as $r): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($r['name']) ?></div>
            <?php if ($r['nationality'] !== 'Malaysian'): ?>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($r['nationality']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;">
            <?php if ($r['email']): ?><div><?= e($r['email']) ?></div><?php endif; ?>
            <?php if ($r['phone']): ?><div style="color:#64748b;"><?= e($r['phone']) ?></div><?php endif; ?>
          </td>
          <td style="font-size:.82rem;color:#64748b;"><?= $r['ic_number'] ? e($r['ic_number']) : '&mdash;' ?></td>
          <td>
            <?= $r['password_hash']
                ? '<span class="s-badge badge-active" style="font-size:.68rem;">Access set</span>'
                : '<span style="color:#cbd5e1;font-size:.8rem;">&mdash;</span>' ?>
          </td>
          <td>
            <?= $r['is_active']
                ? '<span class="s-badge badge-active">Active</span>'
                : '<span class="s-badge badge-overdue">Inactive</span>' ?>
          </td>
          <td class="text-end">
            <a href="residents.php?action=edit&id=<?= $r['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <form method="POST" class="d-inline">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="toggle">
              <input type="hidden" name="id"      value="<?= $r['id'] ?>">
              <button class="btn btn-sm <?= $r['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                      style="font-size:.75rem;">
                <?= $r['is_active'] ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-people-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">
      <?= $search ? 'No residents match your search.' : 'No residents yet.' ?>
    </p>
    <?php if (!$search): ?>
    <a href="residents.php?action=create" class="btn btn-brand btn-sm">Add First Resident</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
