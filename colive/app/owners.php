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
        $name    = trim($_POST['name']         ?? '');
        $ic      = trim($_POST['ic_number']    ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $phone   = trim($_POST['phone']        ?? '');
        $bank    = trim($_POST['bank_name']    ?? '');
        $acc     = trim($_POST['bank_account'] ?? '');
        $holder  = trim($_POST['bank_holder']  ?? '');
        $notes   = trim($_POST['notes']        ?? '');
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $oid     = (int)($_POST['id']          ?? 0);

        if ($name === '') {
            flashSet('danger', 'Owner name is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        if ($oid) {
            $chk = $db->prepare('SELECT id FROM owners WHERE id=? AND company_id=?');
            $chk->execute([$oid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: owners.php'); exit; }
            $old = $db->prepare('SELECT * FROM owners WHERE id=?');
            $old->execute([$oid]);
            $oldData = (array)$old->fetch();
            $db->prepare(
                'UPDATE owners SET name=?,ic_number=?,email=?,phone=?,bank_name=?,bank_account=?,bank_holder=?,notes=?,is_active=?
                 WHERE id=? AND company_id=?'
            )->execute([$name, $ic, $email ?: null, $phone, $bank, $acc, $holder, $notes, $active, $oid, $cid]);
            auditLog($db, 'update', 'owners', $oid, $oldData, ['name' => $name]);
            flashSet('success', 'Owner updated.');
        } else {
            $db->prepare(
                'INSERT INTO owners (company_id,name,ic_number,email,phone,bank_name,bank_account,bank_holder,notes,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$cid, $name, $ic, $email ?: null, $phone, $bank, $acc, $holder, $notes, $active]);
            $oid = (int)$db->lastInsertId();
            auditLog($db, 'create', 'owners', $oid, [], ['name' => $name]);
            flashSet('success', 'Owner added.');
        }
        header('Location: owners.php'); exit;
    }

    if ($act === 'toggle') {
        $oid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare('SELECT is_active FROM owners WHERE id=? AND company_id=?');
        $chk->execute([$oid, $cid]);
        $row = $chk->fetch();
        if ($row) {
            $newStatus = $row['is_active'] ? 0 : 1;
            $db->prepare('UPDATE owners SET is_active=? WHERE id=? AND company_id=?')
               ->execute([$newStatus, $oid, $cid]);
            flashSet('success', $newStatus ? 'Owner activated.' : 'Owner deactivated.');
        }
        header('Location: owners.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM owners WHERE id=? AND company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger', 'Owner not found.'); header('Location: owners.php'); exit; }
}

// ── List ──────────────────────────────────────────────────────────────────────
$showInactive = isset($_GET['inactive']);
$sql = 'SELECT o.*, COUNT(u.id) AS unit_count
        FROM owners o
        LEFT JOIN units u ON u.owner_id = o.id AND u.company_id = ?
        WHERE o.company_id = ?';
if (!$showInactive) $sql .= ' AND o.is_active = 1';
$sql .= ' GROUP BY o.id ORDER BY o.name ASC';
$stmt = $db->prepare($sql);
$stmt->execute([$cid, $cid]);
$owners = $stmt->fetchAll();

$pageTitle  = 'Owners';
$activePage = 'owners';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <p class="page-sub mb-0"><?= count($owners) ?> owner<?= count($owners) !== 1 ? 's' : '' ?></p>
    <a href="owners.php?<?= $showInactive ? '' : 'inactive' ?>"
       style="font-size:.78rem;color:#64748b;">
      <?= $showInactive ? 'Hide inactive' : 'Show inactive' ?>
    </a>
  </div>
  <a href="owners.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Add Owner
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:680px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Owner' : 'New Owner' ?></h6>
  <form method="POST" action="owners.php">
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
        <label class="form-label fw-semibold" style="font-size:.85rem;">IC Number</label>
        <input type="text" name="ic_number" class="form-control form-control-sm"
               placeholder="e.g. 850101-14-5678" value="<?= e($editing['ic_number'] ?? '') ?>">
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

    <hr style="border-color:#f1f5f9;">
    <p class="fw-semibold mb-2" style="font-size:.82rem;color:#64748b;">Bank Details (for payout)</p>
    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Bank Name</label>
        <input type="text" name="bank_name" class="form-control form-control-sm"
               placeholder="e.g. Maybank" value="<?= e($editing['bank_name'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Account No.</label>
        <input type="text" name="bank_account" class="form-control form-control-sm"
               value="<?= e($editing['bank_account'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Account Holder</label>
        <input type="text" name="bank_holder" class="form-control form-control-sm"
               value="<?= e($editing['bank_holder'] ?? '') ?>">
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
        <?= $editing ? 'Save Changes' : 'Add Owner' ?>
      </button>
      <a href="owners.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($owners): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>Owner</th>
          <th>Contact</th>
          <th>Bank</th>
          <th>Units</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($owners as $o): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($o['name']) ?></div>
            <?php if ($o['ic_number']): ?>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($o['ic_number']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;">
            <?php if ($o['email']): ?><div><?= e($o['email']) ?></div><?php endif; ?>
            <?php if ($o['phone']): ?><div style="color:#64748b;"><?= e($o['phone']) ?></div><?php endif; ?>
          </td>
          <td style="font-size:.82rem;">
            <?php if ($o['bank_name']): ?>
            <div><?= e($o['bank_name']) ?></div>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($o['bank_account'] ?? '') ?></div>
            <?php else: ?>
            <span style="color:#cbd5e1;">&mdash;</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="fw-semibold" style="color:#334155;"><?= (int)$o['unit_count'] ?></span>
          </td>
          <td>
            <?php if ($o['is_active']): ?>
            <span class="s-badge badge-active">Active</span>
            <?php else: ?>
            <span class="s-badge badge-overdue">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="owners.php?action=edit&id=<?= $o['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <form method="POST" class="d-inline">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="toggle">
              <input type="hidden" name="id"      value="<?= $o['id'] ?>">
              <button class="btn btn-sm <?= $o['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                      style="font-size:.75rem;">
                <?= $o['is_active'] ? 'Deactivate' : 'Activate' ?>
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
    <i class="bi bi-person-vcard-fill" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No owners yet.</p>
    <a href="owners.php?action=create" class="btn btn-brand btn-sm">Add First Owner</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
