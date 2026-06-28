<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();
requireRole('admin');

$db  = getDB();
$cid = companyId();

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'save_company') {
        $name    = trim($_POST['name']           ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $phone   = trim($_POST['phone']          ?? '');
        $address = trim($_POST['address']        ?? '');
        $ssm     = trim($_POST['ssm_reg']        ?? '');
        $brand   = trim($_POST['brand_color']    ?? '#9333ea');
        $invPfx  = strtoupper(trim($_POST['invoice_prefix'] ?? 'INV')) ?: 'INV';
        $bEmail  = strtolower(trim($_POST['billing_email']  ?? ''));

        if ($name === '') {
            flashSet('danger', 'Company name is required.');
            header('Location: settings.php'); exit;
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $brand)) $brand = '#9333ea';

        $old = $db->prepare('SELECT * FROM companies WHERE id=?');
        $old->execute([$cid]); $oldData = (array)$old->fetch();

        $db->prepare(
            'UPDATE companies SET name=?,email=?,phone=?,address=?,ssm_reg=?,brand_color=?,invoice_prefix=?,billing_email=?
             WHERE id=?'
        )->execute([$name, $email, $phone, $address, $ssm, $brand, $invPfx, $bEmail ?: null, $cid]);
        auditLog($db, 'update', 'companies', $cid, $oldData, ['name'=>$name,'brand_color'=>$brand]);
        flashSet('success', 'Settings saved.');
        header('Location: settings.php'); exit;
    }

    if ($act === 'change_password') {
        $uid     = (int)($_SESSION['user_id'] ?? 0);
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 6) {
            flashSet('danger', 'New password must be at least 6 characters.');
            header('Location: settings.php'); exit;
        }
        if ($newPass !== $confirm) {
            flashSet('danger', 'New passwords do not match.');
            header('Location: settings.php'); exit;
        }
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id=? AND company_id=?');
        $stmt->execute([$uid, $cid]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            flashSet('danger', 'Current password is incorrect.');
            header('Location: settings.php'); exit;
        }
        $db->prepare('UPDATE users SET password_hash=? WHERE id=? AND company_id=?')
           ->execute([password_hash($newPass, PASSWORD_DEFAULT), $uid, $cid]);
        flashSet('success', 'Password updated.');
        header('Location: settings.php'); exit;
    }
}

// ── Load company ──────────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT c.*, p.name AS plan_name, p.max_units, p.max_staff
                      FROM companies c LEFT JOIN plans p ON p.id = c.plan_id WHERE c.id=?');
$stmt->execute([$cid]);
$company = $stmt->fetch() ?: [];

$pageTitle  = 'Company Settings';
$activePage = 'settings';
include __DIR__ . '/layout.php';
?>

<div class="row g-4">
  <!-- Company info -->
  <div class="col-lg-7">
    <div class="card-box mb-4">
      <h6 class="fw-bold mb-4">Company Information</h6>
      <form method="POST" action="settings.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="save_company">
        <div class="row g-3 mb-3">
          <div class="col-md-7">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Company Name *</label>
            <input type="text" name="name" class="form-control form-control-sm"
                   value="<?= e($company['name'] ?? '') ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold" style="font-size:.85rem;">SSM Reg. No.</label>
            <input type="text" name="ssm_reg" class="form-control form-control-sm"
                   value="<?= e($company['ssm_reg'] ?? '') ?>">
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Primary Email</label>
            <input type="email" name="email" class="form-control form-control-sm"
                   value="<?= e($company['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Billing Email</label>
            <input type="email" name="billing_email" class="form-control form-control-sm"
                   placeholder="Where invoices are sent"
                   value="<?= e($company['billing_email'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Phone</label>
          <input type="text" name="phone" class="form-control form-control-sm"
                 value="<?= e($company['phone'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Address</label>
          <textarea name="address" class="form-control form-control-sm" rows="3"><?= e($company['address'] ?? '') ?></textarea>
        </div>

        <hr style="border-color:#f1f5f9;margin-bottom:1.25rem;">
        <h6 class="fw-bold mb-3" style="font-size:.9rem;">Branding &amp; Billing</h6>
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Brand Color</label>
            <div class="d-flex align-items-center gap-2">
              <input type="color" name="brand_color" class="form-control form-control-color form-control-sm"
                     value="<?= e($company['brand_color'] ?? '#9333ea') ?>" id="clrPicker">
              <input type="text" id="clrHex" class="form-control form-control-sm" style="max-width:90px;"
                     value="<?= e($company['brand_color'] ?? '#9333ea') ?>" maxlength="7"
                     pattern="^#[0-9a-fA-F]{6}$">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Invoice Prefix</label>
            <input type="text" name="invoice_prefix" class="form-control form-control-sm"
                   maxlength="10" placeholder="INV"
                   value="<?= e($company['invoice_prefix'] ?? 'INV') ?>">
            <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">
              e.g. INV-00001
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-brand btn-sm">Save Settings</button>
      </form>
    </div>
  </div>

  <!-- Sidebar: plan info + password change -->
  <div class="col-lg-5">
    <!-- Current plan -->
    <div class="card-box mb-4">
      <h6 class="fw-bold mb-3">Current Plan</h6>
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:44px;height:44px;border-radius:10px;background:var(--brand-dim);display:flex;align-items:center;justify-content:center;">
          <i class="bi bi-award-fill" style="color:var(--brand);font-size:1.1rem;"></i>
        </div>
        <div>
          <div class="fw-bold" style="font-size:.95rem;"><?= e($company['plan_name'] ?? 'No Plan') ?></div>
          <div style="font-size:.75rem;color:#64748b;">
            <?= $company['status'] === 'trial'
                ? 'Trial &mdash; ends ' . dateDisplay($company['trial_ends_at'] ?? '')
                : ucfirst($company['status'] ?? '') ?>
          </div>
        </div>
      </div>
      <?php if ($company['plan_name']): ?>
      <div class="row g-2">
        <div class="col-6">
          <div style="background:#f8fafc;border-radius:8px;padding:.6rem .75rem;">
            <div style="font-size:1.2rem;font-weight:800;color:#0f172a;"><?= (int)$company['max_units'] ?></div>
            <div style="font-size:.7rem;color:#94a3b8;">Max Units</div>
          </div>
        </div>
        <div class="col-6">
          <div style="background:#f8fafc;border-radius:8px;padding:.6rem .75rem;">
            <div style="font-size:1.2rem;font-weight:800;color:#0f172a;"><?= (int)$company['max_staff'] ?></div>
            <div style="font-size:.7rem;color:#94a3b8;">Max Staff</div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <p style="font-size:.78rem;color:#94a3b8;margin-top:.75rem;margin-bottom:0;">
        To upgrade your plan, contact <a href="mailto:support@slvgroup.my" style="color:var(--brand);">support@slvgroup.my</a>
      </p>
    </div>

    <!-- Change password -->
    <div class="card-box">
      <h6 class="fw-bold mb-3">Change Your Password</h6>
      <form method="POST" action="settings.php">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="change_password">
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Current Password</label>
          <input type="password" name="current_password" class="form-control form-control-sm" required autocomplete="current-password">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">New Password (min 6)</label>
          <input type="password" name="new_password" class="form-control form-control-sm" required minlength="6" autocomplete="new-password">
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control form-control-sm" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
<script>
const picker = document.getElementById('clrPicker');
const hex    = document.getElementById('clrHex');
if (picker && hex) {
  picker.addEventListener('input', () => hex.value = picker.value);
  hex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) picker.value = hex.value; });
}
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
