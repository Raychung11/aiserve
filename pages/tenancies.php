<?php
require_once __DIR__ . '/../includes/auth_check.php';
$activePage = 'tenancies';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

// ── POST actions ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $data = [
            'tenant_id'      => $_tenantId,
            'property_id'    => (int)$_POST['property_id'],
            'renter_id'      => $_POST['renter_id'] ?: null,
            'tenant_name'    => trim($_POST['tenant_name']),
            'tenant_phone'   => trim($_POST['tenant_phone'] ?? ''),
            'tenant_email'   => trim($_POST['tenant_email'] ?? ''),
            'tenant_ic'      => trim($_POST['tenant_ic'] ?? ''),
            'tenant_company' => trim($_POST['tenant_company'] ?? ''),
            'start_date'     => $_POST['start_date'],
            'end_date'       => $_POST['end_date'],
            'monthly_rent'   => (float)$_POST['monthly_rent'],
            'deposit'        => (float)($_POST['deposit'] ?? 0),
            'deposit_paid'   => isset($_POST['deposit_paid']) ? 1 : 0,
            'type'           => $_POST['type'] ?? 'MID_TERM',
            'status'         => $_POST['status'] ?? 'active',
            'agent_id'       => $_POST['agent_id'] ?: null,
            'notes'          => trim($_POST['notes'] ?? ''),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        // Auto-fill name/phone from renter profile if linked
        if ($data['renter_id']) {
            $rp = Database::fetchOne("SELECT name, phone, email, ic_number FROM renter_profiles WHERE id=? AND tenant_id=?", [$data['renter_id'], $_tenantId]);
            if ($rp) {
                $data['tenant_name']  = $rp['name'];
                $data['tenant_phone'] = $rp['phone'] ?? $data['tenant_phone'];
                $data['tenant_email'] = $rp['email'] ?? $data['tenant_email'];
                $data['tenant_ic']    = $rp['ic_number'] ?? $data['tenant_ic'];
            }
        }

        if ($action === 'create') {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = Database::insert('str_tenancies', $data);
            ActivityLog::record('tenancy.created', 'Tenancy: ' . $data['tenant_name'], $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Tenancy created.'];
        } else {
            $id = (int)$_POST['id'];
            Database::update('str_tenancies', $data, 'id=? AND tenant_id=?', [$id, $_tenantId]);
            ActivityLog::record('tenancy.updated', 'Tenancy updated: ' . $data['tenant_name'], $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Tenancy updated.'];
        }
        header('Location: ' . APP_URL . '/tenancies?view=' . $id); exit;
    }

    if ($action === 'delete') {
        Database::update('str_tenancies', ['deleted_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [(int)$_POST['id'], $_tenantId]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Tenancy removed.'];
        header('Location: ' . APP_URL . '/tenancies'); exit;
    }
}

// ── View single ────────────────────────────────────────────────────────────────
if (isset($_GET['view'])) {
    $tenancy = Database::fetchOne(
        'SELECT t.*, p.name AS property_name, u.name AS agent_name
         FROM str_tenancies t
         LEFT JOIN properties p ON p.id = t.property_id
         LEFT JOIN str_users u ON u.id = t.agent_id
         WHERE t.id=? AND t.tenant_id=? AND t.deleted_at IS NULL',
        [(int)$_GET['view'], $_tenantId]
    );
    if (!$tenancy) { header('Location: ' . APP_URL . '/tenancies'); exit; }

    $daysLeft = (int)((strtotime($tenancy['end_date']) - time()) / 86400);

    // Linked renter profile
    $renter = $tenancy['renter_id']
        ? Database::fetchOne("SELECT * FROM renter_profiles WHERE id=? AND tenant_id=?", [$tenancy['renter_id'], $_tenantId])
        : null;

    // Recent payments
    $payments = Database::fetchAll(
        "SELECT * FROM rent_payments WHERE tenancy_id=? AND tenant_id=? ORDER BY period DESC LIMIT 6",
        [$tenancy['id'], $_tenantId]
    );
    $paymentSummary = Database::fetchOne(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) AS overdue_count,
                SUM(amount_paid) AS total_paid,
                SUM(amount_due)  AS total_due
         FROM rent_payments WHERE tenancy_id=? AND tenant_id=?",
        [$tenancy['id'], $_tenantId]
    );

    // Documents
    $docs = Database::fetchAll(
        "SELECT * FROM owner_documents WHERE tenant_id=? AND property_id=? ORDER BY created_at DESC LIMIT 5",
        [$_tenantId, $tenancy['property_id']]
    );

    $pageTitle    = $tenancy['tenant_name'];
    $pageSubtitle = $tenancy['property_name'] ?? '';
    require_once __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/tenancies" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Tenancies</a>
  <div>
    <h4 class="fw-bold mb-0"><?= htmlspecialchars($tenancy['tenant_name']) ?></h4>
    <p class="text-muted mb-0" style="font-size:.875rem;"><?= htmlspecialchars($tenancy['property_name'] ?? '') ?></p>
  </div>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= APP_URL ?>/rent-payments?tenancy_id=<?= $tenancy['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-cash me-1"></i>Payments</a>
    <a href="<?= APP_URL ?>/tenancies?action=edit&id=<?= $tenancy['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
    <form method="POST" action="<?= APP_URL ?>/tenancies" onsubmit="return confirm('Remove this tenancy?')">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $tenancy['id'] ?>">
      <button class="btn btn-sm btn-outline-danger">Remove</button>
    </form>
  </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg'] ?? '') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Status banner -->
<?php if ($tenancy['status'] === 'active' && $daysLeft <= 60): ?>
<div class="alert alert-<?= $daysLeft <= 30 ? 'danger' : 'warning' ?> d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-clock-fill"></i>
  Lease expires in <strong><?= $daysLeft ?> days</strong> on <?= date('d M Y', strtotime($tenancy['end_date'])) ?>.
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <!-- Lease Details -->
  <div class="col-lg-4">
    <div class="card-box h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Lease Details</h6>
        <span class="badge bg-<?= match($tenancy['status']) { 'active' => 'success', 'pending' => 'warning', 'expired','terminated' => 'secondary', default => 'secondary' } ?> text-white">
          <?= ucfirst($tenancy['status']) ?>
        </span>
      </div>
      <table class="table table-sm table-borderless mb-0">
        <?php foreach ([
          ['Type',     $tenancy['type']],
          ['Start',    date('d M Y', strtotime($tenancy['start_date']))],
          ['End',      date('d M Y', strtotime($tenancy['end_date']))],
          ['Duration', ceil((strtotime($tenancy['end_date']) - strtotime($tenancy['start_date'])) / 2592000) . ' months'],
          ['Agent',    $tenancy['agent_name'] ?: '—'],
        ] as [$k, $v]): ?>
        <tr><td class="text-muted" style="font-size:.8rem;width:42%"><?= $k ?></td><td style="font-size:.875rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>

      <div class="mt-3 pt-3 border-top">
        <div class="row g-2 text-center">
          <div class="col-6">
            <div class="text-muted" style="font-size:.7rem;">Monthly Rent</div>
            <div class="fw-bold text-success" style="font-size:1.2rem;">RM <?= number_format($tenancy['monthly_rent'], 0) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted" style="font-size:.7rem;">Deposit</div>
            <div class="fw-bold" style="font-size:1.2rem;">RM <?= number_format($tenancy['deposit'], 0) ?></div>
            <span style="font-size:.7rem;padding:1px 7px;border-radius:6px;background:<?= $tenancy['deposit_paid'] ? '#dcfce7' : '#fef9c3' ?>;color:<?= $tenancy['deposit_paid'] ? '#15803d' : '#b45309' ?>">
              <?= $tenancy['deposit_paid'] ? 'Paid' : 'Unpaid' ?>
            </span>
          </div>
        </div>
      </div>

      <?php if ($tenancy['notes']): ?>
      <div class="mt-3 pt-3 border-top">
        <div class="text-muted" style="font-size:.75rem;">Notes</div>
        <div style="font-size:.82rem;"><?= nl2br(htmlspecialchars($tenancy['notes'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tenant / Renter Profile -->
  <div class="col-lg-4">
    <div class="card-box h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Tenant Info</h6>
        <?php if ($renter): ?>
          <a href="<?= APP_URL ?>/renters?view=<?= $renter['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:2px 8px;">Full Profile</a>
        <?php else: ?>
          <a href="<?= APP_URL ?>/renters?action=create" class="btn btn-xs btn-outline-primary" style="font-size:.75rem;padding:2px 8px;">+ Create Profile</a>
        <?php endif; ?>
      </div>

      <?php if ($renter): ?>
      <div class="d-flex align-items-center gap-2 mb-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
             style="width:40px;height:40px;background:#6366f1;flex-shrink:0;font-size:.9rem;">
          <?= strtoupper(substr($renter['name'], 0, 1)) ?>
        </div>
        <div>
          <div class="fw-semibold"><?= htmlspecialchars($renter['name']) ?></div>
          <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($renter['nationality'] ?? '') ?></div>
        </div>
      </div>
      <table class="table table-sm table-borderless mb-0">
        <?php foreach ([
          ['IC',           $renter['ic_number'] ?: '—'],
          ['Phone',        $renter['phone'] ?: '—'],
          ['WhatsApp',     $renter['whatsapp'] ?: '—'],
          ['Email',        $renter['email'] ?: '—'],
          ['Employer',     $renter['employer_name'] ?: '—'],
          ['Income',       $renter['monthly_income'] ? 'RM '.number_format($renter['monthly_income'],0).'/mo' : '—'],
        ] as [$k, $v]): ?>
        <tr><td class="text-muted" style="font-size:.78rem;width:42%"><?=$k?></td><td style="font-size:.82rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php if ($renter['emergency_name']): ?>
      <div class="mt-3 pt-2 border-top">
        <div class="text-muted" style="font-size:.72rem;">Emergency: <?= htmlspecialchars($renter['emergency_name']) ?> (<?= htmlspecialchars($renter['emergency_relation'] ?? '') ?>)</div>
        <div style="font-size:.8rem;"><?= htmlspecialchars($renter['emergency_phone'] ?? '') ?></div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- Inline info only (no linked profile) -->
      <table class="table table-sm table-borderless mb-0">
        <?php foreach ([
          ['Name',    $tenancy['tenant_name']],
          ['IC',      $tenancy['tenant_ic'] ?: '—'],
          ['Phone',   $tenancy['tenant_phone'] ?: '—'],
          ['Email',   $tenancy['tenant_email'] ?: '—'],
          ['Company', $tenancy['tenant_company'] ?: '—'],
        ] as [$k, $v]): ?>
        <tr><td class="text-muted" style="font-size:.8rem;width:42%"><?=$k?></td><td style="font-size:.875rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <div class="mt-3 pt-2 border-top">
        <a href="<?= APP_URL ?>/renters?action=create" class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-person-plus me-1"></i>Create Full Renter Profile
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Payment Summary -->
  <div class="col-lg-4">
    <div class="card-box h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Payment Summary</h6>
        <a href="<?= APP_URL ?>/rent-payments?tenancy_id=<?= $tenancy['id'] ?>" class="btn btn-xs btn-outline-success" style="font-size:.75rem;padding:2px 8px;">All Payments</a>
      </div>

      <?php if ($paymentSummary['total'] > 0): ?>
      <div class="row g-2 mb-3 text-center">
        <div class="col-6">
          <div class="text-muted" style="font-size:.7rem;">Collected</div>
          <div class="fw-bold text-success" style="font-size:1.1rem;">RM <?= number_format($paymentSummary['total_paid'], 0) ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:.7rem;">Outstanding</div>
          <div class="fw-bold text-danger" style="font-size:1.1rem;">RM <?= number_format(max(0, $paymentSummary['total_due'] - $paymentSummary['total_paid']), 0) ?></div>
        </div>
      </div>
      <div class="d-flex gap-2 mb-3">
        <span class="badge-green" style="font-size:.72rem;"><?= $paymentSummary['paid_count'] ?> Paid</span>
        <?php if ($paymentSummary['overdue_count']): ?>
        <span class="badge-red" style="font-size:.72rem;"><?= $paymentSummary['overdue_count'] ?> Overdue</span>
        <?php endif; ?>
      </div>

      <!-- Recent payments -->
      <?php foreach ($payments as $p):
        $sc = match($p['status']) { 'paid' => 'badge-green', 'partial' => 'badge-amber', 'overdue' => 'badge-red', default => 'badge bg-light text-secondary border' };
      ?>
      <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
        <span style="font-size:.78rem;"><?= date('M Y', strtotime($p['period'].'-01')) ?></span>
        <div class="d-flex align-items-center gap-2">
          <span class="text-success fw-semibold" style="font-size:.78rem;">RM <?= number_format($p['amount_paid'], 0) ?></span>
          <span class="<?= $sc ?>" style="font-size:.68rem;"><?= ucfirst($p['status']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>

      <?php else: ?>
      <p class="text-muted mb-3" style="font-size:.875rem;">No payment records yet.</p>
      <?php endif; ?>

      <a href="<?= APP_URL ?>/rent-payments?tenancy_id=<?= $tenancy['id'] ?>" class="btn btn-sm btn-outline-success w-100 mt-2">
        <i class="bi bi-cash-coin me-1"></i>Record / View Payments
      </a>
    </div>
  </div>
</div>

<!-- Documents -->
<?php if ($docs): ?>
<div class="card-box mt-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-semibold mb-0">Property Documents</h6>
    <a href="<?= APP_URL ?>/owner-documents?property_id=<?= $tenancy['property_id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($docs as $doc): ?>
    <a href="<?= APP_URL ?>/owner-document-download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-file-earmark-text me-1"></i><?= htmlspecialchars($doc['title']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Form ───────────────────────────────────────────────────────────────────────
if (!empty($_GET['action']) && in_array($_GET['action'], ['create', 'edit'])) {
    $tenancy = null;
    if ($_GET['action'] === 'edit') {
        $tenancy = Database::fetchOne('SELECT * FROM str_tenancies WHERE id=? AND tenant_id=? AND deleted_at IS NULL', [(int)($_GET['id'] ?? 0), $_tenantId]);
    }
    $pageTitle    = $tenancy ? 'Edit Tenancy' : 'New Tenancy';
    $pageSubtitle = '';
    $properties   = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name', [$_tenantId]);
    $agents       = Database::fetchAll('SELECT id,name FROM str_users WHERE tenant_id=? AND role="agent" AND is_active=1', [$_tenantId]);
    $renters      = Database::fetchAll('SELECT id,name,phone FROM renter_profiles WHERE tenant_id=? ORDER BY name', [$_tenantId]);

    // Pre-select renter if coming from renter profile page
    $preRenterId = (int)($_GET['renter_id'] ?? 0);

    require_once __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/tenancies" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Tenancies</a>
  <h4 class="fw-bold mb-0"><?= $tenancy ? 'Edit Tenancy' : 'New Tenancy' ?></h4>
</div>

<div class="row justify-content-center"><div class="col-lg-9"><div class="card-box">
<form action="<?= APP_URL ?>/tenancies" method="POST">
<input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
<input type="hidden" name="action" value="<?= $tenancy ? 'edit' : 'create' ?>">
<?php if ($tenancy): ?><input type="hidden" name="id" value="<?= $tenancy['id'] ?>"><?php endif; ?>

<p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">TENANT</p>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Renter Profile
      <a href="<?= APP_URL ?>/renters?action=create" class="ms-2 text-primary" style="font-size:.75rem;" target="_blank"><i class="bi bi-plus-circle me-1"></i>New</a>
    </label>
    <select name="renter_id" class="form-select" id="renterSelect" onchange="fillFromRenter(this)">
      <option value="">— Select existing renter or enter manually —</option>
      <?php foreach ($renters as $r): ?>
      <option value="<?= $r['id'] ?>"
        data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
        data-phone="<?= htmlspecialchars($r['phone'] ?? '', ENT_QUOTES) ?>"
        <?= ($tenancy['renter_id'] ?? $preRenterId) == $r['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($r['name']) ?><?= $r['phone'] ? ' — '.$r['phone'] : '' ?>
      </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Selecting a renter auto-fills name and phone below.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Full Name</label>
    <input type="text" name="tenant_name" id="tenant_name" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_name'] ?? '') ?>" required>
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold">Phone</label>
    <input type="text" name="tenant_phone" id="tenant_phone" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_phone'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold">Email</label>
    <input type="email" name="tenant_email" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_email'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold">IC / Passport</label>
    <input type="text" name="tenant_ic" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_ic'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Company <span class="text-muted fw-normal">(if corporate)</span></label>
    <input type="text" name="tenant_company" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_company'] ?? '') ?>">
  </div>
</div>

<p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">LEASE TERMS</p>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Property</label>
    <select name="property_id" class="form-select" required>
      <option value="">Select property</option>
      <?php foreach ($properties as $p): ?>
      <option value="<?= $p['id'] ?>" <?= (($tenancy['property_id'] ?? $_GET['property_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($p['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Type</label>
    <select name="type" class="form-select">
      <?php foreach (['STR', 'MID_TERM', 'SUBLET', 'CORPORATE'] as $t): ?>
      <option value="<?= $t ?>" <?= ($tenancy['type'] ?? 'MID_TERM') === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Status</label>
    <select name="status" class="form-select">
      <?php foreach (['active', 'pending', 'expired', 'terminated'] as $s): ?>
      <option value="<?= $s ?>" <?= ($tenancy['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Start Date</label>
    <input type="date" name="start_date" class="form-control" value="<?= $tenancy['start_date'] ?? date('Y-m-d') ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">End Date</label>
    <input type="date" name="end_date" class="form-control" value="<?= $tenancy['end_date'] ?? '' ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Monthly Rent (RM)</label>
    <input type="number" name="monthly_rent" class="form-control" value="<?= $tenancy['monthly_rent'] ?? '' ?>" step="50" min="0" required>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Deposit (RM)</label>
    <input type="number" name="deposit" class="form-control" value="<?= $tenancy['deposit'] ?? 0 ?>" step="50" min="0">
  </div>
  <div class="col-md-3 d-flex align-items-end pb-1">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="deposit_paid" <?= !empty($tenancy['deposit_paid']) ? 'checked' : '' ?>>
      <label class="form-check-label fw-semibold">Deposit Paid</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Assigned Agent</label>
    <select name="agent_id" class="form-select">
      <option value="">No agent</option>
      <?php foreach ($agents as $a): ?>
      <option value="<?= $a['id'] ?>" <?= ($tenancy['agent_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold">Notes</label>
    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($tenancy['notes'] ?? '') ?></textarea>
  </div>
</div>

<div class="d-flex gap-2 justify-content-end">
  <a href="<?= APP_URL ?>/tenancies" class="btn btn-outline-secondary">Cancel</a>
  <button type="submit" class="btn btn-primary px-4"><?= $tenancy ? 'Save Changes' : 'Create Tenancy' ?></button>
</div>
</form>
</div></div></div>

<?php
$extraJs = <<<'JS'
function fillFromRenter(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.value) {
    document.getElementById('tenant_name').value  = opt.dataset.name  || '';
    document.getElementById('tenant_phone').value = opt.dataset.phone || '';
  }
}
// Auto-fill on page load if renter pre-selected
window.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('renterSelect');
  if (sel && sel.value) fillFromRenter(sel);
});
JS;

require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── List ───────────────────────────────────────────────────────────────────────
$pageTitle    = 'Tenancies';
$pageSubtitle = 'Lease and rental records';

$where  = 't.tenant_id=? AND t.deleted_at IS NULL';
$params = [$_tenantId];
if (!empty($_GET['status']))      { $where .= ' AND t.status=?';      $params[] = $_GET['status']; }
if (!empty($_GET['property_id'])) { $where .= ' AND t.property_id=?'; $params[] = (int)$_GET['property_id']; }

$tenancies  = Database::fetchAll("SELECT t.*, p.name AS property_name FROM str_tenancies t LEFT JOIN properties p ON p.id=t.property_id WHERE $where ORDER BY t.start_date DESC LIMIT 100", $params);
$expiring   = Database::count('str_tenancies', 'tenant_id=? AND status="active" AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND deleted_at IS NULL', [$_tenantId]);
$overdueCount = Database::count('rent_payments', 'tenant_id=? AND status="overdue"', [$_tenantId]);
$properties = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name', [$_tenantId]);

require_once __DIR__ . '/../includes/header.php';

if ($expiring): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <strong><?= $expiring ?> lease<?= $expiring > 1 ? 's' : '' ?></strong>&nbsp;expiring within 30 days.
  <a href="<?= APP_URL ?>/tenancies?status=active" class="ms-auto btn btn-sm btn-warning">View</a>
</div>
<?php endif;

if ($overdueCount): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
  <i class="bi bi-cash-stack"></i>
  <strong><?= $overdueCount ?> overdue payment<?= $overdueCount > 1 ? 's' : '' ?></strong>&nbsp;need attention.
  <a href="<?= APP_URL ?>/rent-payments" class="ms-auto btn btn-sm btn-danger">View Overdue</a>
</div>
<?php endif; ?>

<div class="d-flex gap-2 align-items-center justify-content-between mb-3 flex-wrap">
  <form class="d-flex gap-2 flex-wrap" method="GET" action="<?= APP_URL ?>/tenancies">
    <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach (['active', 'pending', 'expired', 'terminated'] as $s): ?>
      <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="property_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Properties</option>
      <?php foreach ($properties as $p): ?>
      <option value="<?= $p['id'] ?>" <?= ($_GET['property_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/rent-payments" class="btn btn-sm btn-outline-success"><i class="bi bi-cash me-1"></i>Payments</a>
    <a href="<?= APP_URL ?>/tenancies?action=create" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>New Tenancy</a>
  </div>
</div>

<div class="card-box">
  <table class="table table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th style="font-size:.8rem;">Tenant</th>
        <th style="font-size:.8rem;">Property</th>
        <th style="font-size:.8rem;">Type</th>
        <th style="font-size:.8rem;">Monthly Rent</th>
        <th style="font-size:.8rem;">End Date</th>
        <th style="font-size:.8rem;">Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$tenancies): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">No tenancy records.</td></tr>
      <?php else: foreach ($tenancies as $t):
        $daysLeft = (int)((strtotime($t['end_date']) - time()) / 86400);
        $sc = match($t['status']) { 'active' => 'success', 'pending' => 'warning', 'expired','terminated' => 'secondary', default => 'secondary' };
      ?>
      <tr>
        <td>
          <div class="fw-semibold" style="font-size:.875rem;"><?= htmlspecialchars($t['tenant_name']) ?></div>
          <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['tenant_phone'] ?? '') ?></div>
        </td>
        <td style="font-size:.85rem;"><?= htmlspecialchars($t['property_name'] ?? '—') ?></td>
        <td><?= StrategyEngine::badge($t['type']) ?></td>
        <td class="fw-semibold" style="font-size:.875rem;">RM <?= number_format($t['monthly_rent'], 0) ?></td>
        <td>
          <div style="font-size:.8rem;"><?= date('d M Y', strtotime($t['end_date'])) ?></div>
          <?php if ($t['status'] === 'active' && $daysLeft <= 30): ?>
          <span class="badge-amber" style="font-size:.7rem;"><?= $daysLeft ?>d left</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge bg-<?= $sc ?> text-white" style="font-size:.72rem;"><?= ucfirst($t['status']) ?></span>
        </td>
        <td>
          <a href="<?= APP_URL ?>/tenancies?view=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php';
