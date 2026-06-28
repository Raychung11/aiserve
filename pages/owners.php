<?php
require_once __DIR__.'/../includes/auth_check.php';

$flash = [];

// ── POST HANDLER ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── CREATE ────────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!$name) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Name is required.']; header('Location: '.APP_URL.'/owners?action=create'); exit; }

        $ownerId = Database::insert('str_owners', [
            'tenant_id'    => $_tenantId,
            'name'         => $name,
            'email'        => $email ?: null,
            'phone'        => trim($_POST['phone'] ?? '') ?: null,
            'ic_number'    => trim($_POST['ic_number'] ?? '') ?: null,
            'bank_name'    => trim($_POST['bank_name'] ?? '') ?: null,
            'bank_account' => trim($_POST['bank_account'] ?? '') ?: null,
            'bank_holder'  => trim($_POST['bank_holder'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
            'is_active'    => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        // Optionally create a login account for the owner
        $createLogin = !empty($_POST['create_login']) && $email;
        $password    = trim($_POST['portal_password'] ?? '');
        if ($createLogin && $password) {
            if (Database::count('str_users', 'email=?', [$email])) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'Owner created but login not set up — that email is already registered.'];
            } else {
                Database::insert('str_users', [
                    'tenant_id'     => $_tenantId,
                    'name'          => $name,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'role'          => 'owner',
                    'owner_id'      => $ownerId,
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
                if (!isset($_SESSION['flash'])) $_SESSION['flash'] = ['type'=>'success','msg'=>"Owner created with portal access. Login: $email"];
            }
        } else {
            if (!isset($_SESSION['flash'])) $_SESSION['flash'] = ['type'=>'success','msg'=>'Owner created.'];
        }
        ActivityLog::record('owner.create', "Created owner: $name", $_tenantId, $_user['id']);
        header('Location: '.APP_URL.'/owners?view='.$ownerId); exit;
    }

    // ── EDIT ─────────────────────────────────────────────────────────────────
    if ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $owner = Database::fetchOne("SELECT id FROM str_owners WHERE id=? AND tenant_id=?", [$id, $_tenantId]);
        if (!$owner) { header('Location: '.APP_URL.'/owners'); exit; }

        Database::update('str_owners', [
            'name'         => trim($_POST['name'] ?? ''),
            'email'        => strtolower(trim($_POST['email'] ?? '')) ?: null,
            'phone'        => trim($_POST['phone'] ?? '') ?: null,
            'ic_number'    => trim($_POST['ic_number'] ?? '') ?: null,
            'bank_name'    => trim($_POST['bank_name'] ?? '') ?: null,
            'bank_account' => trim($_POST['bank_account'] ?? '') ?: null,
            'bank_holder'  => trim($_POST['bank_holder'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id=?', [$id]);

        // Update linked user name if exists
        Database::query("UPDATE str_users SET name=?, updated_at=? WHERE owner_id=? AND tenant_id=?",
            [trim($_POST['name']), date('Y-m-d H:i:s'), $id, $_tenantId]);

        ActivityLog::record('owner.update', "Updated owner #$id", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Owner updated.'];
        header('Location: '.APP_URL.'/owners?view='.$id); exit;
    }

    // ── RESET PASSWORD ────────────────────────────────────────────────────────
    if ($action === 'reset_password') {
        $id  = (int)$_POST['id'];
        $pw  = trim($_POST['new_password'] ?? '');
        if (strlen($pw) < 6) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Password must be at least 6 characters.']; header('Location: '.APP_URL.'/owners?view='.$id); exit; }
        Database::query("UPDATE str_users SET password_hash=?, updated_at=? WHERE owner_id=? AND tenant_id=?",
            [password_hash($pw, PASSWORD_BCRYPT), date('Y-m-d H:i:s'), $id, $_tenantId]);
        Database::query("DELETE FROM str_users WHERE owner_id=? AND tenant_id=? AND id IN (SELECT id FROM (SELECT id FROM str_users WHERE owner_id=? ORDER BY id DESC LIMIT 99999) x)", [$id, $_tenantId, $id]);
        ActivityLog::record('owner.password_reset', "Reset portal password for owner #$id", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Portal password updated.'];
        header('Location: '.APP_URL.'/owners?view='.$id); exit;
    }

    // ── CREATE LOGIN (if not done at creation) ────────────────────────────────
    if ($action === 'create_login') {
        $id    = (int)$_POST['id'];
        $owner = Database::fetchOne("SELECT * FROM str_owners WHERE id=? AND tenant_id=?", [$id, $_tenantId]);
        $email = strtolower(trim($_POST['login_email'] ?? $owner['email'] ?? ''));
        $pw    = trim($_POST['login_password'] ?? '');
        if (!$owner || !$email || strlen($pw) < 6) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Email and password (min 6 chars) required.']; header('Location: '.APP_URL.'/owners?view='.$id); exit; }
        if (Database::count('str_users', 'email=?', [$email])) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'That email is already registered.']; header('Location: '.APP_URL.'/owners?view='.$id); exit; }
        Database::insert('str_users', [
            'tenant_id'     => $_tenantId,
            'name'          => $owner['name'],
            'email'         => $email,
            'password_hash' => password_hash($pw, PASSWORD_BCRYPT),
            'role'          => 'owner',
            'owner_id'      => $id,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::record('owner.login_created', "Portal login created for owner #$id ($email)", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Portal login created. Owner can sign in with: $email"];
        header('Location: '.APP_URL.'/owners?view='.$id); exit;
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Soft-delete: mark inactive and unlink from properties
        Database::update('str_owners', ['is_active'=>0, 'updated_at'=>date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [$id, $_tenantId]);
        Database::query("UPDATE properties SET owner_id=NULL WHERE owner_id=? AND tenant_id=?", [$id, $_tenantId]);
        Database::query("UPDATE str_users SET is_active=0, updated_at=? WHERE owner_id=? AND tenant_id=?", [date('Y-m-d H:i:s'), $id, $_tenantId]);
        ActivityLog::record('owner.delete', "Deactivated owner #$id", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Owner deactivated.'];
        header('Location: '.APP_URL.'/owners'); exit;
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// ── VIEW MODES ───────────────────────────────────────────────────────────────
$viewId  = (int)($_GET['view'] ?? 0);
$action  = $_GET['action'] ?? '';
$editId  = (int)($_GET['id'] ?? 0);

// ── VIEW: single owner profile ────────────────────────────────────────────────
if ($viewId) {
    $owner = Database::fetchOne("SELECT * FROM str_owners WHERE id=? AND tenant_id=?", [$viewId, $_tenantId]);
    if (!$owner) { header('Location: '.APP_URL.'/owners'); exit; }
    $ownerProperties = Database::fetchAll("SELECT * FROM properties WHERE owner_id=? AND tenant_id=? AND deleted_at IS NULL ORDER BY name", [$viewId, $_tenantId]);
    $ownerUser       = Database::fetchOne("SELECT id, email, is_active FROM str_users WHERE owner_id=? AND tenant_id=? ORDER BY id ASC LIMIT 1", [$viewId, $_tenantId]);
    $docCount        = Database::count('owner_documents', 'owner_documents.tenant_id=? AND properties.owner_id=?', [$_tenantId, $viewId]);

    $pageTitle = htmlspecialchars($owner['name']).' — Owner';
    include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/owners" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Owners</a>
  <div>
    <h4 class="fw-bold mb-0"><?= htmlspecialchars($owner['name']) ?></h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Owner Profile</p>
  </div>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= APP_URL ?>/owners?action=edit&id=<?= $owner['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
    <form method="POST" action="<?= APP_URL ?>/owners" onsubmit="return confirm('Deactivate this owner?')">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $owner['id'] ?>">
      <button class="btn btn-sm btn-outline-danger">Deactivate</button>
    </form>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-box mb-3">
      <h6 class="fw-semibold mb-3">Owner Details</h6>
      <table class="table table-sm table-borderless mb-0">
        <?php foreach([
          ['Name',          $owner['name']],
          ['Email',         $owner['email'] ?: '—'],
          ['Phone',         $owner['phone'] ?: '—'],
          ['IC / Passport', $owner['ic_number'] ?: '—'],
          ['Bank',          $owner['bank_name'] ?: '—'],
          ['Account No.',   $owner['bank_account'] ?: '—'],
          ['Account Name',  $owner['bank_holder'] ?: '—'],
        ] as [$k,$v]): ?>
        <tr><td class="text-muted" style="font-size:.8rem;width:40%"><?= $k ?></td><td style="font-size:.875rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($owner['notes']): ?>
        <tr><td class="text-muted" style="font-size:.8rem;">Notes</td><td style="font-size:.875rem;"><?= htmlspecialchars($owner['notes']) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- Portal Access -->
    <div class="card-box">
      <h6 class="fw-semibold mb-3">Portal Access</h6>
      <?php if ($ownerUser): ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <div style="font-size:.875rem;" class="fw-semibold"><?= htmlspecialchars($ownerUser['email']) ?></div>
            <div><span class="<?= $ownerUser['is_active']?'badge-green':'badge-red' ?>"><?= $ownerUser['is_active']?'Active':'Inactive' ?></span></div>
          </div>
        </div>
        <form method="POST" action="<?= APP_URL ?>/owners" class="row g-2">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="id" value="<?= $owner['id'] ?>">
          <div class="col-12"><label class="form-label fw-semibold" style="font-size:.8rem;">Reset Password</label>
            <input type="password" name="new_password" class="form-control form-control-sm" placeholder="New password (min 6 chars)" minlength="6"></div>
          <div class="col-12"><button class="btn btn-sm btn-outline-warning w-100">Reset Password</button></div>
        </form>
      <?php else: ?>
        <p class="text-muted mb-3" style="font-size:.875rem;">No portal login yet. Create one to give this owner access to the owner portal.</p>
        <form method="POST" action="<?= APP_URL ?>/owners" class="row g-2">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="create_login">
          <input type="hidden" name="id" value="<?= $owner['id'] ?>">
          <div class="col-12"><label class="form-label fw-semibold" style="font-size:.8rem;">Login Email</label>
            <input type="email" name="login_email" class="form-control form-control-sm" value="<?= htmlspecialchars($owner['email']??'') ?>" required></div>
          <div class="col-12"><label class="form-label fw-semibold" style="font-size:.8rem;">Password</label>
            <input type="password" name="login_password" class="form-control form-control-sm" placeholder="Min 6 characters" minlength="6" required></div>
          <div class="col-12"><button class="btn btn-sm btn-primary w-100">Create Portal Login</button></div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <!-- Properties -->
    <div class="card-box mb-3">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Properties (<?= count($ownerProperties) ?>)</h6>
        <a href="<?= APP_URL ?>/properties?action=create" class="btn btn-sm btn-outline-primary">+ Add Property</a>
      </div>
      <?php if ($ownerProperties): ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr>
            <th style="font-size:.75rem;">Property</th>
            <th style="font-size:.75rem;">City</th>
            <th style="font-size:.75rem;">Strategy</th>
            <th style="font-size:.75rem;">Status</th>
            <th style="font-size:.75rem;"></th>
          </tr></thead>
          <tbody>
            <?php foreach ($ownerProperties as $p): ?>
            <tr>
              <td style="font-size:.8rem;" class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
              <td style="font-size:.8rem;"><?= htmlspecialchars($p['city']) ?></td>
              <td><?= StrategyEngine::badge($p['strategy_mode']) ?></td>
              <td><span class="badge bg-<?= $p['listing_status']==='active'?'success':($p['listing_status']==='inactive'?'secondary':'warning') ?> text-white" style="font-size:.7rem;"><?= ucfirst($p['listing_status']) ?></span></td>
              <td><a href="<?= APP_URL ?>/properties?view=<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px;">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.875rem;">No properties assigned yet. Edit a property and select this owner.</p>
      <?php endif; ?>
    </div>

    <!-- Documents -->
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Documents</h6>
        <?php if ($ownerProperties): ?>
        <a href="<?= APP_URL ?>/owner-documents?owner_id=<?= $owner['id'] ?>" class="btn btn-sm btn-outline-primary">Manage Documents</a>
        <?php endif; ?>
      </div>
      <?php
      $docs = [];
      if ($ownerProperties) {
          $propIds = array_column($ownerProperties, 'id');
          $placeholders = implode(',', array_fill(0, count($propIds), '?'));
          $docs = Database::fetchAll(
              "SELECT d.*, p.name AS property_name FROM owner_documents d
               JOIN properties p ON p.id = d.property_id
               WHERE d.tenant_id=? AND d.property_id IN ($placeholders)
               ORDER BY d.created_at DESC LIMIT 10",
              array_merge([$_tenantId], $propIds)
          );
      }
      ?>
      <?php if ($docs): ?>
      <table class="table table-sm mb-0">
        <tbody>
          <?php foreach ($docs as $doc): ?>
          <tr>
            <td style="font-size:.8rem;"><i class="bi bi-file-earmark-text me-1 text-muted"></i><?= htmlspecialchars($doc['title']) ?></td>
            <td style="font-size:.75rem;" class="text-muted"><?= htmlspecialchars($doc['property_name']) ?></td>
            <td><a href="<?= APP_URL ?>/owner-document-download?id=<?= $doc['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 6px;"><i class="bi bi-download"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.875rem;">No documents uploaded yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php';
    exit;
}

// ── FORM: create / edit ────────────────────────────────────────────────────
if ($action === 'create' || ($action === 'edit' && $editId)) {
    $owner = null;
    if ($action === 'edit') {
        $owner = Database::fetchOne("SELECT * FROM str_owners WHERE id=? AND tenant_id=?", [$editId, $_tenantId]);
        if (!$owner) { header('Location: '.APP_URL.'/owners'); exit; }
    }
    $pageTitle = $owner ? 'Edit Owner' : 'New Owner';
    include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/owners" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Owners</a>
  <h4 class="fw-bold mb-0"><?= $owner ? 'Edit Owner' : 'Add New Owner' ?></h4>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card-box">
<form method="POST" action="<?= APP_URL ?>/owners">
  <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="<?= $owner ? 'edit' : 'create' ?>">
  <?php if($owner): ?><input type="hidden" name="id" value="<?= $owner['id'] ?>"><?php endif; ?>

  <h6 class="fw-semibold mb-3 pb-2 border-bottom">Personal Information</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label fw-semibold">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($owner['name']??'') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">IC / Passport No.</label>
      <input type="text" name="ic_number" class="form-control" value="<?= htmlspecialchars($owner['ic_number']??'') ?>" placeholder="e.g. 880101-14-1234">
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($owner['email']??'') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($owner['phone']??'') ?>" placeholder="e.g. 012-3456789">
    </div>
  </div>

  <h6 class="fw-semibold mb-3 pb-2 border-bottom">Bank Details</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label fw-semibold">Bank Name</label>
      <select name="bank_name" class="form-select">
        <option value="">Select bank</option>
        <?php foreach(['Maybank','CIMB Bank','Public Bank','RHB Bank','Hong Leong Bank','AmBank','Bank Islam','Bank Rakyat','BSN','OCBC','UOB','Standard Chartered','HSBC','Alliance Bank','Affin Bank','Other'] as $b): ?>
        <option value="<?=$b?>" <?= ($owner['bank_name']??'')===$b?'selected':'' ?>><?=$b?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Account Number</label>
      <input type="text" name="bank_account" class="form-control" value="<?= htmlspecialchars($owner['bank_account']??'') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Account Holder Name</label>
      <input type="text" name="bank_holder" class="form-control" value="<?= htmlspecialchars($owner['bank_holder']??'') ?>">
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12">
      <label class="form-label fw-semibold">Notes</label>
      <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this owner..."><?= htmlspecialchars($owner['notes']??'') ?></textarea>
    </div>
  </div>

  <?php if (!$owner): ?>
  <h6 class="fw-semibold mb-3 pb-2 border-bottom">Portal Access <span class="text-muted fw-normal" style="font-size:.8rem;">(optional)</span></h6>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="create_login" id="create_login" onchange="document.getElementById('portalPw').classList.toggle('d-none',!this.checked)">
        <label class="form-check-label fw-semibold" for="create_login">Create owner portal login account</label>
        <div class="form-text">Owner will be able to log in and view their properties, income/expenses, and documents.</div>
      </div>
    </div>
    <div class="col-md-6 d-none" id="portalPw">
      <label class="form-label fw-semibold">Portal Password</label>
      <input type="password" name="portal_password" class="form-control" placeholder="Min 6 characters">
      <div class="form-text">Email will be used as the login username.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= APP_URL ?>/owners" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4"><?= $owner ? 'Save Changes' : 'Add Owner' ?></button>
  </div>
</form>
</div>
</div>
</div>

<?php include __DIR__.'/../includes/footer.php';
    exit;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$owners = Database::fetchAll(
    "SELECT o.*, COUNT(p.id) AS property_count,
            u.email AS login_email, u.is_active AS login_active
     FROM str_owners o
     LEFT JOIN properties p ON p.owner_id = o.id AND p.deleted_at IS NULL
     LEFT JOIN str_users u ON u.owner_id = o.id AND u.tenant_id = o.tenant_id
     WHERE o.tenant_id=? AND o.is_active=1
     GROUP BY o.id
     ORDER BY o.name",
    [$_tenantId]
);

$pageTitle = 'Owners';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Property Owners</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;"><?= count($owners) ?> owner<?= count($owners)!==1?'s':'' ?> registered</p>
  </div>
  <a href="<?= APP_URL ?>/owners?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Owner</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($owners): ?>
<div class="row g-3">
  <?php foreach ($owners as $o): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card-box h-100">
      <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
          <div class="fw-bold"><?= htmlspecialchars($o['name']) ?></div>
          <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($o['phone'] ?: ($o['email'] ?: 'No contact')) ?></div>
        </div>
        <?php if ($o['login_email']): ?>
        <span class="<?= $o['login_active']?'badge-green':'badge-red' ?>" style="white-space:nowrap;">
          <i class="bi bi-person-check me-1"></i><?= $o['login_active']?'Portal':'Inactive' ?>
        </span>
        <?php else: ?>
        <span class="badge bg-light text-secondary border" style="font-size:.7rem;">No login</span>
        <?php endif; ?>
      </div>
      <div class="row g-2 mb-3 text-center">
        <div class="col-6">
          <div class="text-muted" style="font-size:.72rem;">Properties</div>
          <div class="fw-bold" style="font-size:1.1rem;"><?= $o['property_count'] ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:.72rem;">Bank</div>
          <div class="fw-semibold" style="font-size:.8rem;"><?= $o['bank_name'] ? htmlspecialchars($o['bank_name']) : '—' ?></div>
        </div>
      </div>
      <a href="<?= APP_URL ?>/owners?view=<?= $o['id'] ?>" class="btn btn-outline-primary btn-sm w-100">View Profile</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card-box text-center py-5">
  <div style="font-size:3rem;">👤</div>
  <h5 class="mt-3 mb-2">No Owners Yet</h5>
  <p class="text-muted">Add your property owners to give them portal access and link them to properties.</p>
  <a href="<?= APP_URL ?>/owners?action=create" class="btn btn-primary">Add First Owner</a>
</div>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
