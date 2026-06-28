<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();
requireRole('admin', 'manager');

$db     = getDB();
$cid    = companyId();
$myId   = (int)($_SESSION['user_id'] ?? 0);
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$ROLES = ['admin','manager','tenant_relations','finance','maintenance'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save') {
        $uid    = (int)($_POST['id']     ?? 0);
        $name   = trim($_POST['name']   ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $phone  = trim($_POST['phone']  ?? '');
        $role   = $_POST['role']        ?? 'manager';
        $active = isset($_POST['is_active']) ? 1 : 0;
        $pass   = $_POST['password']    ?? '';

        if ($name === '' || $email === '') {
            flashSet('danger', 'Name and email are required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($role, $ROLES, true)) $role = 'manager';

        if ($uid) {
            // Cannot change your own role or deactivate yourself
            if ($uid === $myId) { $role = currentRole(); $active = 1; }

            $chk = $db->prepare('SELECT id FROM users WHERE id=? AND company_id=?');
            $chk->execute([$uid, $cid]);
            if (!$chk->fetch()) { flashSet('danger','Not found.'); header('Location: staff.php'); exit; }

            $sql = 'UPDATE users SET name=?,email=?,phone=?,role=?,is_active=?';
            $params = [$name, $email, $phone, $role, $active];
            if ($pass !== '') {
                $sql .= ',password_hash=?';
                $params[] = password_hash($pass, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=? AND company_id=?';
            $params[] = $uid; $params[] = $cid;
            $db->prepare($sql)->execute($params);
            auditLog($db, 'update', 'users', $uid, [], ['role'=>$role,'is_active'=>$active]);
            flashSet('success', 'Staff updated.');
        } else {
            if (strlen($pass) < 6) {
                flashSet('danger', 'Password must be at least 6 characters.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }
            $chk = $db->prepare('SELECT id FROM users WHERE email=? AND company_id=?');
            $chk->execute([$email, $cid]);
            if ($chk->fetch()) {
                flashSet('danger', 'A staff member with this email already exists.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }
            $db->prepare(
                'INSERT INTO users (company_id,name,email,phone,password_hash,role,is_active) VALUES (?,?,?,?,?,?,?)'
            )->execute([$cid, $name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $role, 1]);
            $uid = (int)$db->lastInsertId();
            auditLog($db, 'create', 'users', $uid, [], ['name'=>$name,'role'=>$role]);
            flashSet('success', 'Staff member added.');
        }
        header('Location: staff.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id=? AND company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Not found.'); header('Location: staff.php'); exit; }
}

// ── List ──────────────────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM users WHERE company_id=? ORDER BY role, name');
$stmt->execute([$cid]);
$staff = $stmt->fetchAll();

$roleLabel = [
    'admin'            => 'Admin',
    'manager'          => 'Manager',
    'tenant_relations' => 'Tenant Relations',
    'finance'          => 'Finance',
    'maintenance'      => 'Maintenance',
];

$pageTitle  = 'Staff &amp; Roles';
$activePage = 'staff';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0"><?= count($staff) ?> staff member<?= count($staff) !== 1 ? 's' : '' ?></p>
  <a href="staff.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Add Staff
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:620px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Staff Member' : 'Add Staff Member' ?></h6>
  <form method="POST" action="staff.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Full Name *</label>
        <input type="text" name="name" class="form-control form-control-sm"
               value="<?= e($editing['name'] ?? '') ?>" required autofocus>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Email *</label>
        <input type="email" name="email" class="form-control form-control-sm"
               value="<?= e($editing['email'] ?? '') ?>" required
               <?= ($editing && $editing['id'] === $myId) ? 'readonly' : '' ?>>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Phone</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="<?= e($editing['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Role</label>
        <select name="role" class="form-select form-select-sm"
                <?= ($editing && $editing['id'] === $myId) ? 'disabled' : '' ?>>
          <?php foreach ($ROLES as $r): ?>
          <option value="<?= $r ?>" <?= ($editing['role'] ?? 'manager') === $r ? 'selected' : '' ?>>
            <?= $roleLabel[$r] ?? ucfirst($r) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">
        Password <?= $editing ? '(leave blank to keep current)' : '* (min 6 chars)' ?>
      </label>
      <input type="password" name="password" class="form-control form-control-sm"
             <?= !$editing ? 'required minlength="6"' : '' ?> autocomplete="new-password">
    </div>
    <?php if (!$editing || $editing['id'] !== $myId): ?>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="is_active" id="chkActive"
             <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="chkActive" style="font-size:.85rem;">Active</label>
    </div>
    <?php else: ?>
    <div style="height:.5rem;"></div>
    <?php endif; ?>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Add Staff Member' ?>
      </button>
      <a href="staff.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($staff): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td>
            <div class="fw-semibold" style="font-size:.85rem;">
              <?= e($s['name']) ?>
              <?php if ($s['id'] === $myId): ?>
              <span style="font-size:.7rem;color:#9333ea;font-weight:700;"> (you)</span>
              <?php endif; ?>
            </div>
            <?php if ($s['phone']): ?>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($s['phone']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;color:#64748b;"><?= e($s['email']) ?></td>
          <td>
            <span class="s-badge badge-open" style="font-size:.7rem;">
              <?= $roleLabel[$s['role']] ?? ucfirst($s['role']) ?>
            </span>
          </td>
          <td style="color:#94a3b8;font-size:.78rem;">
            <?= $s['last_login_at'] ? datetimeDisplay($s['last_login_at']) : 'Never' ?>
          </td>
          <td>
            <?= $s['is_active']
                ? '<span class="s-badge badge-active">Active</span>'
                : '<span class="s-badge badge-overdue">Inactive</span>' ?>
          </td>
          <td class="text-end">
            <a href="staff.php?action=edit&id=<?= $s['id'] ?>"
               class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-person-gear" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-0" style="font-size:.9rem;">No staff yet.</p>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
