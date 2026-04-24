<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$types = ['condo','landed','commercial','serviced_apartment'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            $_POST['property_name'] ?? '',
            $_POST['location'] ?? '',
            $_POST['state'] ?? '',
            $_POST['type'] ?? 'condo',
            (float)str_replace(',','',$_POST['price'] ?? 0),
            $_POST['developer'] ?? '',
            $_POST['agent_name'] ?? '',
            $_POST['agent_contact'] ?? '',
            isset($_POST['mm2h_suitability']) ? 1 : 0,
            (float)($_POST['rental_roi_estimate'] ?? 0),
            $_POST['investment_notes'] ?? '',
            (int)isset($_POST['featured']),
            $_POST['status'] ?? 'available',
        ];
        if ($id) {
            $sql = 'UPDATE properties SET property_name=?,location=?,state=?,type=?,price=?,developer=?,agent_name=?,agent_contact=?,mm2h_suitability=?,rental_roi_estimate=?,investment_notes=?,featured=?,status=?,updated_at=NOW() WHERE id=?';
            $pdo->prepare($sql)->execute(array_merge($data,[$id]));
            flash('success','Property updated.');
        } else {
            $sql = 'INSERT INTO properties (property_name,location,state,type,price,developer,agent_name,agent_contact,mm2h_suitability,rental_roi_estimate,investment_notes,featured,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute($data);
            flash('success','Property added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM properties WHERE id=?')->execute([$id]);
        flash('success','Property deleted.');
    }
    redirect('admin/properties');
}

$edit_prop = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM properties WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit_prop = $s->fetch();
}

$props = $pdo->query('SELECT * FROM properties ORDER BY featured DESC, created_at DESC')->fetchAll();

$page_title = 'Properties — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h1><i class="bi bi-buildings me-2 text-gold"></i>Properties</h1>
      <a href="?add=1" class="btn btn-gold"><i class="bi bi-plus me-1"></i>Add Property</a>
    </div>
    <?php render_flash(); ?>

    <?php if (isset($_GET['add']) || $edit_prop): ?>
    <div class="mm2h-form-card mb-4">
      <h5 class="mb-4"><?= $edit_prop ? 'Edit' : 'Add' ?> Property</h5>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit_prop['id'] ?? 0) ?>">
        <div class="row g-3">
          <?php $p = $edit_prop ?? []; ?>
          <div class="col-md-8"><label class="form-label">Property Name *</label><input type="text" name="property_name" class="form-control" value="<?= h($p['property_name'] ?? '') ?>" required></div>
          <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select"><?php foreach ($types as $t): ?><option value="<?=$t?>" <?=($p['type']??'')===$t?'selected':''?>><?= ucwords(str_replace('_',' ',$t)) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?= h($p['location'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">State</label><select name="state" class="form-select"><option value="">Select…</option><?php foreach (['Kuala Lumpur','Selangor','Penang','Johor','Sabah','Sarawak','Pahang','Perak','Negeri Sembilan','Malacca','Other'] as $st): ?><option value="<?=$st?>" <?=($p['state']??'')===$st?'selected':''?>><?=$st?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label">Price (MYR)</label><input type="number" name="price" class="form-control" step="1000" value="<?= h($p['price'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Developer</label><input type="text" name="developer" class="form-control" value="<?= h($p['developer'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Rental ROI %</label><input type="number" name="rental_roi_estimate" class="form-control" step="0.1" value="<?= h($p['rental_roi_estimate'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Agent Name</label><input type="text" name="agent_name" class="form-control" value="<?= h($p['agent_name'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Agent Contact</label><input type="text" name="agent_contact" class="form-control" value="<?= h($p['agent_contact'] ?? '') ?>"></div>
          <div class="col-12"><label class="form-label">Investment Notes</label><textarea name="investment_notes" class="form-control" rows="3"><?= h($p['investment_notes'] ?? '') ?></textarea></div>
          <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="available" <?=($p['status']??'')==='available'?'selected':''?>>Available</option><option value="sold" <?=($p['status']??'')==='sold'?'selected':''?>>Sold</option><option value="reserved" <?=($p['status']??'')==='reserved'?'selected':''?>>Reserved</option></select></div>
          <div class="col-md-4 d-flex align-items-end gap-3">
            <div class="form-check"><input type="checkbox" class="form-check-input" name="mm2h_suitability" id="mm2h_suit" <?=!empty($p['mm2h_suitability'])?'checked':''?>><label class="form-check-label" for="mm2h_suit">MM2H Suitable</label></div>
            <div class="form-check"><input type="checkbox" class="form-check-input" name="featured" id="featured" <?=!empty($p['featured'])?'checked':''?>><label class="form-check-label" for="featured">Featured</label></div>
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-gold"><?= t('save') ?></button>
            <a href="<?= APP_URL ?>/admin/properties" class="btn btn-outline-secondary"><?= t('cancel') ?></a>
          </div>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="mm2h-table">
      <table class="table">
        <thead><tr><th>Property</th><th>Location</th><th>Type</th><th>Price</th><th>ROI</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($props as $prop): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= h($prop['property_name']) ?></div>
              <small class="text-muted"><?= h($prop['developer'] ?? '') ?></small>
              <?php if ($prop['featured']): ?><span class="badge bg-warning text-dark ms-1">Featured</span><?php endif; ?>
            </td>
            <td><?= h($prop['location'] ?? '—') ?></td>
            <td><?= h(ucwords(str_replace('_',' ',$prop['type'] ?? ''))) ?></td>
            <td class="fw-bold"><?= format_money((float)$prop['price']) ?></td>
            <td><?= $prop['rental_roi_estimate'] ? h($prop['rental_roi_estimate']).'%' : '—' ?></td>
            <td><?= status_badge($prop['status']) ?></td>
            <td>
              <a href="?edit=<?= $prop['id'] ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-pencil"></i></a>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete this property?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $prop['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$props): ?><tr><td colspan="7" class="text-center py-4 text-muted">No properties yet. Add one above.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
