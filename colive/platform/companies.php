<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requirePlatformLogin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save_company') {
        $cid      = (int)($_POST['id']           ?? 0);
        $name     = trim($_POST['name']          ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone']         ?? '');
        $address  = trim($_POST['address']       ?? '');
        $ssm      = trim($_POST['ssm_reg']       ?? '');
        $brand    = trim($_POST['brand_color']   ?? '#9333ea');
        $planId   = (int)($_POST['plan_id']      ?? 0) ?: null;
        $status   = $_POST['status']             ?? 'trial';
        $trialEnd = $_POST['trial_ends_at']      ?? '';
        $invPfx   = strtoupper(trim($_POST['invoice_prefix'] ?? 'INV')) ?: 'INV';

        if ($name === '' || $email === '') {
            flashSet('danger', 'Company name and email are required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        $validStatuses = ['trial','active','suspended','cancelled'];
        if (!in_array($status, $validStatuses, true)) $status = 'trial';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $brand)) $brand = '#9333ea';

        if ($cid) {
            $chk = $db->prepare('SELECT id FROM companies WHERE id=?');
            $chk->execute([$cid]);
            if (!$chk->fetch()) { flashSet('danger','Not found.'); header('Location: companies.php'); exit; }
            $db->prepare(
                'UPDATE companies SET name=?,email=?,phone=?,address=?,ssm_reg=?,brand_color=?,plan_id=?,
                 status=?,trial_ends_at=?,invoice_prefix=? WHERE id=?'
            )->execute([
                $name, $email, $phone, $address, $ssm, $brand, $planId,
                $status, $trialEnd ?: null, $invPfx, $cid
            ]);
            flashSet('success', 'Company updated.');
            header('Location: companies.php?view=' . $cid); exit;
        } else {
            // Create company + initial admin user
            $adminName  = trim($_POST['admin_name']  ?? '');
            $adminEmail = strtolower(trim($_POST['admin_email'] ?? ''));
            $adminPass  = $_POST['admin_password']   ?? '';

            if ($adminName === '' || $adminEmail === '' || strlen($adminPass) < 6) {
                flashSet('danger', 'Admin name, email and password (min 6 chars) are required for new operators.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }

            $trialEnd = $trialEnd ?: date('Y-m-d', strtotime('+' . TRIAL_DAYS . ' days'));

            $db->prepare(
                'INSERT INTO companies (name,email,phone,address,ssm_reg,brand_color,plan_id,status,trial_ends_at,invoice_prefix)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$name,$email,$phone,$address,$ssm,$brand,$planId,$status,$trialEnd,$invPfx]);
            $newCid = (int)$db->lastInsertId();

            $db->prepare(
                'INSERT INTO users (company_id,name,email,password_hash,role) VALUES (?,?,?,?,?)'
            )->execute([
                $newCid, $adminName, $adminEmail,
                password_hash($adminPass, PASSWORD_DEFAULT),
                'admin'
            ]);

            flashSet('success', 'Operator "' . htmlspecialchars($name) . '" created. Admin login: ' . htmlspecialchars($adminEmail));
            header('Location: companies.php?view=' . $newCid); exit;
        }
    }

    if ($act === 'set_status') {
        $cid    = (int)($_POST['id']     ?? 0);
        $status = $_POST['new_status']   ?? '';
        $valid  = ['trial','active','suspended','cancelled'];
        if (!in_array($status, $valid, true)) { header('Location: companies.php'); exit; }
        $db->prepare('UPDATE companies SET status=? WHERE id=?')->execute([$status, $cid]);
        flashSet('success', 'Status updated.');
        header('Location: companies.php?view=' . $cid); exit;
    }
}

// ── View single company ────────────────────────────────────────────────────────
$viewing = null;
if (($action === 'view' || isset($_GET['view'])) && ($id || (int)($_GET['view'] ?? 0))) {
    $vid = $id ?: (int)$_GET['view'];
    $stmt = $db->prepare(
        'SELECT c.*, p.name AS plan_name
         FROM companies c
         LEFT JOIN plans p ON p.id = c.plan_id
         WHERE c.id=?'
    );
    $stmt->execute([$vid]);
    $viewing = $stmt->fetch() ?: null;
    if (!$viewing) { flashSet('danger','Not found.'); header('Location: companies.php'); exit; }

    // Operator stats
    $stats = [];
    $stats['users']   = (int)$db->prepare('SELECT COUNT(*) FROM users   WHERE company_id=?')->execute([$vid]) ? (int)$db->query("SELECT COUNT(*) FROM users WHERE company_id=$vid")->fetchColumn() : 0;
    $stats['rooms']   = (int)$db->query("SELECT COUNT(*) FROM rooms    WHERE company_id=$vid")->fetchColumn();
    $stats['residents']= (int)$db->query("SELECT COUNT(*) FROM residents WHERE company_id=$vid")->fetchColumn();
    $stats['invoices'] = (int)$db->query("SELECT COUNT(*) FROM invoices  WHERE company_id=$vid")->fetchColumn();
    $action = 'view';
}

// ── Edit single company form ───────────────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare('SELECT * FROM companies WHERE id=?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) { flashSet('danger','Not found.'); header('Location: companies.php'); exit; }
}

// ── Plans dropdown ────────────────────────────────────────────────────────────
$plans = $db->query('SELECT id, name, price_monthly FROM plans WHERE is_active=1 ORDER BY price_monthly')->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$sql    = 'SELECT c.*, p.name AS plan_name FROM companies c LEFT JOIN plans p ON p.id = c.plan_id WHERE 1=1';
$params = [];
if ($filterStatus) { $sql .= ' AND c.status=?'; $params[] = $filterStatus; }
if ($search !== '') { $sql .= ' AND (c.name LIKE ? OR c.email LIKE ?)'; $like = '%'.$search.'%'; $params[] = $like; $params[] = $like; }
$sql .= ' ORDER BY c.created_at DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll();

$adminName = $_SESSION['platform_admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Operators &mdash; CoLive OS Platform</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --p:#9333ea; }
body { background:#0f172a; font-family:'Segoe UI',sans-serif; color:#e2e8f0; min-height:100vh; }
.topbar { background:#1e293b; border-bottom:1px solid #334155; padding:.75rem 1.5rem; position:sticky; top:0; z-index:50; }
.page   { padding:1.5rem; }
.card-dark { background:#1e293b; border:1px solid #334155; border-radius:12px; }
.badge-trial     { background:#7c3aed22;color:#a78bfa;font-size:.7rem;padding:.2rem .55rem;border-radius:20px;font-weight:700; }
.badge-active    { background:#05966922;color:#34d399;font-size:.7rem;padding:.2rem .55rem;border-radius:20px;font-weight:700; }
.badge-suspended { background:#dc262622;color:#f87171;font-size:.7rem;padding:.2rem .55rem;border-radius:20px;font-weight:700; }
.badge-cancelled { background:#33415522;color:#64748b;font-size:.7rem;padding:.2rem .55rem;border-radius:20px;font-weight:700; }
.form-control, .form-select { background:#0f172a; border-color:#334155; color:#e2e8f0; font-size:.85rem; }
.form-control:focus, .form-select:focus { background:#0f172a; color:#e2e8f0; border-color:#9333ea; box-shadow:0 0 0 3px #9333ea22; }
.form-label { color:#94a3b8; font-size:.82rem; }
.tbl th { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:1px solid #334155; padding:.55rem .75rem; }
.tbl td { font-size:.83rem; color:#e2e8f0; border-bottom:1px solid #1e293b; padding:.6rem .75rem; vertical-align:middle; }
.tbl tr:hover td { background:#243048; }
.stat-card { background:#243048; border-radius:8px; padding:.85rem 1rem; }
</style>
</head>
<body>

<div class="topbar d-flex align-items-center justify-content-between">
  <div class="d-flex align-items-center gap-3">
    <a href="<?= APP_URL ?>/platform/dashboard.php" style="font-weight:800;color:#a78bfa;font-size:1rem;text-decoration:none;">
      <i class="bi bi-shield-lock-fill me-2"></i>CoLive OS
    </a>
    <span style="color:#475569;font-size:.8rem;">/ Operators</span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span style="color:#64748b;font-size:.82rem;"><?= e($adminName) ?></span>
    <a href="<?= APP_URL ?>/platform/logout.php" style="color:#64748b;font-size:.82rem;text-decoration:none;">Sign out</a>
  </div>
</div>

<div class="page">
<?php
// Flash messages
$flash = flashGet();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> py-2 mb-3" style="font-size:.85rem;">
  <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<?php if ($action === 'view' && $viewing): ?>
<!-- ── Company detail view ─────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-4">
  <a href="companies.php" style="color:#64748b;font-size:.85rem;text-decoration:none;">&larr; Back</a>
  <h5 class="fw-bold mb-0" style="color:#e2e8f0;"><?= e($viewing['name']) ?></h5>
  <span class="badge-<?= $viewing['status'] ?>"><?= ucfirst($viewing['status']) ?></span>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['Users',     $stats['users'],    'bi-people-fill',   '#9333ea22','#a78bfa'],
      ['Rooms',     $stats['rooms'],    'bi-grid-3x3-gap',  '#0284c722','#38bdf8'],
      ['Residents', $stats['residents'],'bi-person-fill',   '#05966922','#34d399'],
      ['Invoices',  $stats['invoices'], 'bi-receipt-cutoff','#d9770622','#fbbf24'],
  ] as [$label, $val, $icon, $bg, $ic]): ?>
  <div class="col-6 col-md-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div style="width:36px;height:36px;border-radius:8px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;">
        <i class="bi <?= $icon ?>" style="color:<?= $ic ?>;font-size:.9rem;"></i>
      </div>
      <div>
        <div style="font-size:1.4rem;font-weight:800;color:#e2e8f0;"><?= $val ?></div>
        <div style="font-size:.72rem;color:#64748b;"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-md-7">
    <div class="card-dark p-4">
      <h6 class="fw-bold mb-3" style="color:#e2e8f0;">Company Details</h6>
      <table class="table mb-0" style="color:#e2e8f0;font-size:.85rem;">
        <tbody>
          <tr style="border-color:#334155;"><td style="color:#64748b;width:40%;">Email</td><td><?= e($viewing['email']) ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Phone</td><td><?= $viewing['phone'] ? e($viewing['phone']) : '&mdash;' ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">SSM Reg.</td><td><?= $viewing['ssm_reg'] ? e($viewing['ssm_reg']) : '&mdash;' ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Plan</td><td><?= $viewing['plan_name'] ? e($viewing['plan_name']) : '&mdash;' ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Status</td><td><?= ucfirst($viewing['status']) ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Trial Ends</td><td><?= $viewing['trial_ends_at'] ? dateDisplay($viewing['trial_ends_at']) : '&mdash;' ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Brand Color</td>
            <td><span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:<?= e($viewing['brand_color'] ?? '#9333ea') ?>;vertical-align:middle;margin-right:.5rem;"></span><?= e($viewing['brand_color'] ?? '#9333ea') ?></td>
          </tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Invoice Prefix</td><td><?= e($viewing['invoice_prefix']) ?></td></tr>
          <tr style="border-color:#334155;"><td style="color:#64748b;">Joined</td><td><?= dateDisplay($viewing['created_at']) ?></td></tr>
        </tbody>
      </table>
      <div class="d-flex gap-2 mt-3">
        <a href="companies.php?action=edit&id=<?= $viewing['id'] ?>" class="btn btn-sm"
           style="background:#9333ea;color:#fff;border:none;">Edit Details</a>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card-dark p-4">
      <h6 class="fw-bold mb-3" style="color:#e2e8f0;">Quick Actions</h6>
      <?php foreach (['active'=>'Activate','suspended'=>'Suspend','trial'=>'Set Trial','cancelled'=>'Cancel'] as $s => $label): ?>
      <?php if ($viewing['status'] !== $s): ?>
      <form method="POST" class="mb-2">
        <?= csrfField() ?>
        <input type="hidden" name="_action"    value="set_status">
        <input type="hidden" name="id"         value="<?= $viewing['id'] ?>">
        <input type="hidden" name="new_status" value="<?= $s ?>">
        <button class="btn btn-sm w-100" style="background:#243048;color:#94a3b8;border:1px solid #334155;font-size:.82rem;">
          <?= $label ?>
        </button>
      </form>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php elseif ($action === 'create' || $editing): ?>
<!-- ── Create / Edit form ─────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-4">
  <a href="companies.php" style="color:#64748b;font-size:.85rem;text-decoration:none;">&larr; Back</a>
  <h5 class="fw-bold mb-0" style="color:#e2e8f0;"><?= $editing ? 'Edit: ' . e($editing['name']) : 'New Operator' ?></h5>
</div>
<div class="card-dark p-4" style="max-width:680px;">
  <form method="POST" action="companies.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="save_company">
    <input type="hidden" name="id"      value="<?= $editing ? (int)$editing['id'] : 0 ?>">
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Company Name *</label>
        <input type="text" name="name" class="form-control form-control-sm"
               value="<?= e($editing['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control form-control-sm"
               value="<?= e($editing['email'] ?? '') ?>" required>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="<?= e($editing['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">SSM Reg. No.</label>
        <input type="text" name="ssm_reg" class="form-control form-control-sm"
               value="<?= e($editing['ssm_reg'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control form-control-sm" rows="2"><?= e($editing['address'] ?? '') ?></textarea>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Plan</label>
        <select name="plan_id" class="form-select form-select-sm">
          <option value="">No plan</option>
          <?php foreach ($plans as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($editing['plan_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
            <?= e($p['name']) ?> (RM<?= number_format((float)$p['price_monthly'],0) ?>/mo)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach (['trial','active','suspended','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= ($editing['status'] ?? 'trial') === $s ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Trial Ends</label>
        <input type="date" name="trial_ends_at" class="form-control form-control-sm"
               value="<?= $editing ? substr($editing['trial_ends_at'] ?? '', 0, 10) : date('Y-m-d', strtotime('+'.TRIAL_DAYS.' days')) ?>">
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Brand Color</label>
        <input type="color" name="brand_color" class="form-control form-control-sm form-control-color"
               value="<?= e($editing['brand_color'] ?? '#9333ea') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Invoice Prefix</label>
        <input type="text" name="invoice_prefix" class="form-control form-control-sm"
               maxlength="10" value="<?= e($editing['invoice_prefix'] ?? 'INV') ?>">
      </div>
    </div>

    <?php if (!$editing): ?>
    <hr style="border-color:#334155;">
    <p style="color:#a78bfa;font-size:.85rem;font-weight:600;margin-bottom:.75rem;">
      <i class="bi bi-person-gear me-1"></i>Initial Admin User
    </p>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Admin Name *</label>
        <input type="text" name="admin_name" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Admin Email *</label>
        <input type="email" name="admin_email" class="form-control form-control-sm" required>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Admin Password * (min 6 chars)</label>
      <input type="password" name="admin_password" class="form-control form-control-sm"
             minlength="6" required autocomplete="new-password">
    </div>
    <?php else: ?>
    <div style="height:1rem;"></div>
    <?php endif; ?>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-sm" style="background:#9333ea;color:#fff;border:none;">
        <?= $editing ? 'Save Changes' : 'Create Operator' ?>
      </button>
      <a href="companies.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ── List ────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0" style="color:#e2e8f0;">All Operators</h5>
  <a href="companies.php?action=create" class="btn btn-sm"
     style="background:#9333ea;color:#fff;border:none;font-weight:600;">
    <i class="bi bi-plus-lg me-1"></i>New Operator
  </a>
</div>

<div class="d-flex gap-2 mb-3" style="flex-wrap:wrap;">
  <?php
  $tabs = ['' => 'All', 'trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'];
  foreach ($tabs as $val => $label):
  ?>
  <a href="companies.php<?= $val ? '?status='.$val : '' ?>"
     class="btn btn-sm <?= $filterStatus === $val ? '' : '' ?>"
     style="<?= $filterStatus === $val ? 'background:#9333ea;color:#fff;border:none;' : 'background:#243048;color:#64748b;border:1px solid #334155;' ?> font-size:.78rem;">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
  <form method="GET" class="ms-auto d-flex gap-2">
    <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= e($filterStatus) ?>"><?php endif; ?>
    <input type="text" name="q" class="form-control form-control-sm" style="width:200px;"
           placeholder="Search..." value="<?= e($search) ?>">
    <button class="btn btn-sm btn-outline-secondary" style="font-size:.8rem;">Search</button>
  </form>
</div>

<div class="card-dark">
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Company</th><th>Plan</th><th>Status</th><th>Trial / Sub End</th><th>Joined</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $c): ?>
        <tr>
          <td>
            <div class="fw-semibold" style="color:#e2e8f0;"><?= e($c['name']) ?></div>
            <div style="color:#64748b;font-size:.75rem;"><?= e($c['email']) ?></div>
          </td>
          <td style="color:#94a3b8;"><?= $c['plan_name'] ? e($c['plan_name']) : '&mdash;' ?></td>
          <td><span class="badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
          <td style="color:#64748b;font-size:.8rem;">
            <?= $c['trial_ends_at'] ? dateDisplay($c['trial_ends_at']) : '&mdash;' ?>
          </td>
          <td style="color:#64748b;font-size:.8rem;"><?= dateDisplay($c['created_at']) ?></td>
          <td class="text-end">
            <a href="companies.php?view=<?= $c['id'] ?>" class="btn btn-sm"
               style="background:#243048;color:#a78bfa;border:1px solid #334155;font-size:.75rem;">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$companies): ?>
        <tr><td colspan="6" class="text-center py-4" style="color:#475569;">No operators found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
