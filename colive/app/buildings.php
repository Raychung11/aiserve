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
        $name    = trim($_POST['name']     ?? '');
        $address = trim($_POST['address']  ?? '');
        $city    = trim($_POST['city']     ?? '');
        $state   = trim($_POST['state']    ?? '');
        $post    = trim($_POST['postcode'] ?? '');
        $notes   = trim($_POST['notes']    ?? '');
        $bid     = (int)($_POST['id']      ?? 0);

        if ($name === '') {
            flashSet('danger', 'Building name is required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        if ($bid) {
            $chk = $db->prepare('SELECT id FROM buildings WHERE id=? AND company_id=?');
            $chk->execute([$bid, $cid]);
            if (!$chk->fetch()) { flashSet('danger', 'Not found.'); header('Location: buildings.php'); exit; }
            $old = $db->prepare('SELECT * FROM buildings WHERE id=?');
            $old->execute([$bid]);
            $oldData = (array)$old->fetch();
            $db->prepare('UPDATE buildings SET name=?,address=?,city=?,state=?,postcode=?,notes=? WHERE id=? AND company_id=?')
               ->execute([$name, $address, $city, $state, $post, $notes, $bid, $cid]);
            auditLog($db, 'update', 'buildings', $bid, $oldData, ['name' => $name]);
            flashSet('success', 'Building updated.');
        } else {
            $db->prepare('INSERT INTO buildings (company_id,name,address,city,state,postcode,notes) VALUES (?,?,?,?,?,?,?)')
               ->execute([$cid, $name, $address, $city, $state, $post, $notes]);
            $bid = (int)$db->lastInsertId();
            auditLog($db, 'create', 'buildings', $bid, [], ['name' => $name]);
            flashSet('success', 'Building created.');
        }
        header('Location: buildings.php'); exit;
    }

    if ($act === 'delete') {
        $bid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare('SELECT COUNT(*) FROM units WHERE building_id=? AND company_id=?');
        $chk->execute([$bid, $cid]);
        if ((int)$chk->fetchColumn() > 0) {
            flashSet('danger', 'Cannot delete: remove all units in this building first.');
            header('Location: buildings.php'); exit;
        }
        $db->prepare('DELETE FROM buildings WHERE id=? AND company_id=?')->execute([$bid, $cid]);
        auditLog($db, 'delete', 'buildings', $bid, [], []);
        flashSet('success', 'Building deleted.');
        header('Location: buildings.php'); exit;
    }
}

// ── Edit fetch ────────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM buildings WHERE id=? AND company_id=?');
    $stmt->execute([$id, $cid]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger', 'Building not found.'); header('Location: buildings.php'); exit; }
}

// ── List ──────────────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT b.*, COUNT(u.id) AS unit_count
     FROM buildings b
     LEFT JOIN units u ON u.building_id = b.id AND u.company_id = ?
     WHERE b.company_id = ?
     GROUP BY b.id
     ORDER BY b.name ASC'
);
$stmt->execute([$cid, $cid]);
$buildings = $stmt->fetchAll();

$pageTitle  = 'Buildings';
$activePage = 'buildings';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0"><?= count($buildings) ?> building<?= count($buildings) !== 1 ? 's' : '' ?></p>
  <a href="buildings.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Add Building
  </a>
</div>

<?php if ($action === 'create' || $editing): ?>
<div class="card-box mb-4" style="max-width:640px;">
  <h6 class="fw-bold mb-3"><?= $editing ? 'Edit Building' : 'New Building' ?></h6>
  <form method="POST" action="buildings.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Building Name *</label>
      <input type="text" name="name" class="form-control form-control-sm"
             value="<?= e($editing['name'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Street Address</label>
      <input type="text" name="address" class="form-control form-control-sm"
             value="<?= e($editing['address'] ?? '') ?>">
    </div>
    <div class="row g-2 mb-3">
      <div class="col-5">
        <label class="form-label fw-semibold" style="font-size:.85rem;">City</label>
        <input type="text" name="city" class="form-control form-control-sm"
               value="<?= e($editing['city'] ?? '') ?>">
      </div>
      <div class="col-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">State</label>
        <input type="text" name="state" class="form-control form-control-sm"
               value="<?= e($editing['state'] ?? '') ?>">
      </div>
      <div class="col-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Postcode</label>
        <input type="text" name="postcode" class="form-control form-control-sm"
               maxlength="10" value="<?= e($editing['postcode'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= e($editing['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">
        <?= $editing ? 'Save Changes' : 'Create Building' ?>
      </button>
      <a href="buildings.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($buildings): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr>
          <th>Building</th>
          <th>Location</th>
          <th>Units</th>
          <th>Added</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($buildings as $b): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($b['name']) ?></div>
            <?php if ($b['address']): ?>
            <div style="color:#94a3b8;font-size:.75rem;"><?= e($b['address']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.83rem;">
            <?= ($b['city'] || $b['state']) ? e(trim($b['city'] . ', ' . $b['state'], ', ')) : '&mdash;' ?>
            <?php if ($b['postcode']): ?>
            <span style="color:#94a3b8;"><?= e($b['postcode']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <a href="units.php?building_id=<?= $b['id'] ?>" class="fw-semibold text-brand">
              <?= (int)$b['unit_count'] ?> unit<?= (int)$b['unit_count'] !== 1 ? 's' : '' ?>
            </a>
          </td>
          <td style="color:#94a3b8;font-size:.8rem;"><?= dateDisplay($b['created_at']) ?></td>
          <td class="text-end">
            <a href="buildings.php?action=edit&id=<?= $b['id'] ?>"
               class="btn btn-sm btn-outline-secondary me-1" style="font-size:.75rem;">Edit</a>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Delete &quot;<?= e(addslashes($b['name'])) ?>&quot;? This cannot be undone.')">
              <?= csrfField() ?>
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="id"      value="<?= $b['id'] ?>">
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
    <i class="bi bi-building" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No buildings yet. Add your first property.</p>
    <a href="buildings.php?action=create" class="btn btn-brand btn-sm">Add Building</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
