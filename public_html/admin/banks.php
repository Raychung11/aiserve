<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            trim($_POST['bank_name'] ?? ''),
            trim($_POST['contact_person'] ?? ''),
            trim($_POST['contact_email'] ?? ''),
            trim($_POST['contact_phone'] ?? ''),
            (float)str_replace(',','',$_POST['fd_min_amount'] ?? 0),
            trim($_POST['notes'] ?? ''),
            $_POST['status'] ?? 'active',
        ];
        if ($id) {
            $pdo->prepare('UPDATE banks SET bank_name=?,contact_person=?,contact_email=?,contact_phone=?,fd_min_amount=?,notes=?,status=? WHERE id=?')
                ->execute(array_merge($data,[$id]));
            flash('success','Bank updated.');
        } else {
            $pdo->prepare('INSERT INTO banks (bank_name,contact_person,contact_email,contact_phone,fd_min_amount,notes,status) VALUES (?,?,?,?,?,?,?)')
                ->execute($data);
            flash('success','Bank added.');
        }
    }
    redirect('admin/banks');
}

$edit_bank = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM banks WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit_bank = $s->fetch();
}

$banks = $pdo->query('SELECT * FROM banks ORDER BY bank_name')->fetchAll();
$page_title = 'Banks — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h1><i class="bi bi-bank2 me-2 text-gold"></i>Banking Partners</h1>
      <a href="?add=1" class="btn btn-gold"><i class="bi bi-plus me-1"></i>Add Bank</a>
    </div>
    <?php render_flash(); ?>

    <?php if (isset($_GET['add']) || $edit_bank): ?>
    <div class="mm2h-form-card mb-4">
      <h5 class="mb-4"><?= $edit_bank ? 'Edit' : 'Add' ?> Bank</h5>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit_bank['id'] ?? 0) ?>">
        <?php $b = $edit_bank ?? []; ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Bank Name *</label><input type="text" name="bank_name" class="form-control" value="<?= h($b['bank_name'] ?? '') ?>" required></div>
          <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control" value="<?= h($b['contact_person'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?= h($b['contact_email'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Contact Phone</label><input type="tel" name="contact_phone" class="form-control" value="<?= h($b['contact_phone'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Min. Fixed Deposit (MYR)</label><input type="number" name="fd_min_amount" class="form-control" step="1000" value="<?= h($b['fd_min_amount'] ?? '500000') ?>"></div>
          <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?=($b['status']??'')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($b['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></div>
          <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?= h($b['notes'] ?? '') ?></textarea></div>
          <div class="col-12">
            <button type="submit" class="btn btn-gold"><?= t('save') ?></button>
            <a href="<?= APP_URL ?>/admin/banks" class="btn btn-outline-secondary ms-2"><?= t('cancel') ?></a>
          </div>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <?php foreach ($banks as $bank): ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="mb-0"><?= h($bank['bank_name']) ?></h5>
            <?= status_badge($bank['status']) ?>
          </div>
          <p class="small text-muted mb-1"><i class="bi bi-person me-1"></i><?= h($bank['contact_person'] ?? '—') ?></p>
          <p class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i><?= h($bank['contact_email'] ?? '—') ?></p>
          <p class="small text-muted mb-3"><i class="bi bi-telephone me-1"></i><?= h($bank['contact_phone'] ?? '—') ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="small text-muted">Min. FD</div>
              <div class="fw-bold text-gold"><?= format_money((float)$bank['fd_min_amount']) ?></div>
            </div>
            <a href="?edit=<?= $bank['id'] ?>" class="btn btn-sm btn-outline-gold"><i class="bi bi-pencil me-1"></i>Edit</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
