<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'renters';
$flash = [];

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $fields = [
            'tenant_id'          => $_tenantId,
            'name'               => trim($_POST['name'] ?? ''),
            'ic_number'          => trim($_POST['ic_number'] ?? '') ?: null,
            'date_of_birth'      => $_POST['date_of_birth'] ?: null,
            'nationality'        => trim($_POST['nationality'] ?? 'Malaysian'),
            'email'              => strtolower(trim($_POST['email'] ?? '')) ?: null,
            'phone'              => trim($_POST['phone'] ?? '') ?: null,
            'whatsapp'           => trim($_POST['whatsapp'] ?? '') ?: null,
            'emergency_name'     => trim($_POST['emergency_name'] ?? '') ?: null,
            'emergency_phone'    => trim($_POST['emergency_phone'] ?? '') ?: null,
            'emergency_relation' => trim($_POST['emergency_relation'] ?? '') ?: null,
            'employer_name'      => trim($_POST['employer_name'] ?? '') ?: null,
            'job_title'          => trim($_POST['job_title'] ?? '') ?: null,
            'monthly_income'     => $_POST['monthly_income'] ? (float)$_POST['monthly_income'] : null,
            'employment_type'    => $_POST['employment_type'] ?? 'employed',
            'previous_address'   => trim($_POST['previous_address'] ?? '') ?: null,
            'notes'              => trim($_POST['notes'] ?? '') ?: null,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        if (!$fields['name']) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Name is required.']; header('Location: '.APP_URL.'/renters?action=create'); exit; }

        if ($action === 'create') {
            $fields['created_at'] = date('Y-m-d H:i:s');
            $id = Database::insert('renter_profiles', $fields);
            ActivityLog::record('renter.create', 'Renter profile created: '.$fields['name'], $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Renter profile created.'];
        } else {
            $id = (int)$_POST['id'];
            Database::update('renter_profiles', $fields, 'id=? AND tenant_id=?', [$id, $_tenantId]);
            ActivityLog::record('renter.update', 'Renter updated: '.$fields['name'], $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Profile updated.'];
        }
        header('Location: '.APP_URL.'/renters?view='.$id); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Unlink from tenancies before deleting
        Database::query("UPDATE str_tenancies SET renter_id=NULL WHERE renter_id=? AND tenant_id=?", [$id, $_tenantId]);
        Database::delete('renter_profiles', 'id=? AND tenant_id=?', [$id, $_tenantId]);
        ActivityLog::record('renter.delete', "Deleted renter #$id", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Renter profile deleted.'];
        header('Location: '.APP_URL.'/renters'); exit;
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

$viewId = (int)($_GET['view'] ?? 0);
$action = $_GET['action'] ?? '';

// ── VIEW: single profile ──────────────────────────────────────────────────────
if ($viewId) {
    $renter = Database::fetchOne("SELECT * FROM renter_profiles WHERE id=? AND tenant_id=?", [$viewId, $_tenantId]);
    if (!$renter) { header('Location: '.APP_URL.'/renters'); exit; }

    // Tenancy history
    $tenancies = Database::fetchAll(
        "SELECT t.*, p.name AS property_name FROM str_tenancies t
         LEFT JOIN properties p ON p.id = t.property_id
         WHERE t.renter_id=? AND t.tenant_id=? AND t.deleted_at IS NULL
         ORDER BY t.start_date DESC",
        [$viewId, $_tenantId]
    );

    // Documents
    $docs = Database::fetchAll(
        "SELECT * FROM owner_documents WHERE tenant_id=? AND renter_id=? ORDER BY created_at DESC",
        [$_tenantId, $viewId]
    );

    // Payment summary across all tenancies
    $paymentStats = Database::fetchOne(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid,
                SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) AS overdue,
                SUM(amount_paid) AS total_paid
         FROM rent_payments rp
         JOIN str_tenancies t ON t.id = rp.tenancy_id
         WHERE rp.tenant_id=? AND t.renter_id=?",
        [$_tenantId, $viewId]
    );

    $pageTitle = htmlspecialchars($renter['name']).' — Renter';
    include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/renters" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Renters</a>
  <div>
    <h4 class="fw-bold mb-0"><?= htmlspecialchars($renter['name']) ?></h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Renter Profile</p>
  </div>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= APP_URL ?>/renters?action=edit&id=<?= $renter['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
    <form method="POST" action="<?= APP_URL ?>/renters" onsubmit="return confirm('Delete this renter profile?')">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $renter['id'] ?>">
      <button class="btn btn-sm btn-outline-danger">Delete</button>
    </form>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Tenancies</div>
      <div class="stat-value"><?= count($tenancies) ?></div>
      <div class="stat-label">total leases</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Total Paid</div>
      <div class="stat-value text-success">RM <?= number_format($paymentStats['total_paid']??0,0) ?></div>
      <div class="stat-label">all time</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Payments</div>
      <div class="stat-value"><?= $paymentStats['paid']??0 ?> / <?= $paymentStats['total']??0 ?></div>
      <div class="stat-label">paid on time</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Overdue</div>
      <div class="stat-value <?= ($paymentStats['overdue']??0)>0?'text-danger':'' ?>"><?= $paymentStats['overdue']??0 ?></div>
      <div class="stat-label">missed payments</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Left: Personal Info -->
  <div class="col-lg-4">
    <div class="card-box mb-3">
      <h6 class="fw-semibold mb-3">Personal Information</h6>
      <table class="table table-sm table-borderless mb-0">
        <?php
        $age = $renter['date_of_birth'] ? floor((time() - strtotime($renter['date_of_birth'])) / 31557600) : null;
        foreach ([
          ['IC / Passport',  $renter['ic_number'] ?: '—'],
          ['Date of Birth',  $renter['date_of_birth'] ? date('d M Y',strtotime($renter['date_of_birth'])).($age?" ($age yrs)":'') : '—'],
          ['Nationality',    $renter['nationality'] ?: '—'],
          ['Phone',          $renter['phone'] ?: '—'],
          ['WhatsApp',       $renter['whatsapp'] ?: '—'],
          ['Email',          $renter['email'] ?: '—'],
        ] as [$k,$v]): ?>
        <tr><td class="text-muted" style="font-size:.78rem;width:42%"><?=$k?></td><td style="font-size:.85rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="card-box mb-3">
      <h6 class="fw-semibold mb-3">Emergency Contact</h6>
      <?php if ($renter['emergency_name']): ?>
      <div class="fw-semibold mb-1"><?= htmlspecialchars($renter['emergency_name']) ?></div>
      <div class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($renter['emergency_relation'] ?: '') ?></div>
      <div style="font-size:.85rem;"><?= htmlspecialchars($renter['emergency_phone'] ?: '—') ?></div>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.85rem;">Not provided.</p>
      <?php endif; ?>
    </div>

    <div class="card-box">
      <h6 class="fw-semibold mb-3">Employment</h6>
      <table class="table table-sm table-borderless mb-0">
        <?php foreach ([
          ['Employer',    $renter['employer_name'] ?: '—'],
          ['Job Title',   $renter['job_title'] ?: '—'],
          ['Type',        ucfirst(str_replace('_',' ',$renter['employment_type']??'—'))],
          ['Monthly Income', $renter['monthly_income'] ? 'RM '.number_format($renter['monthly_income'],0) : '—'],
        ] as [$k,$v]): ?>
        <tr><td class="text-muted" style="font-size:.78rem;width:45%"><?=$k?></td><td style="font-size:.85rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php if ($renter['previous_address']): ?>
      <div class="mt-2 pt-2 border-top">
        <div class="text-muted" style="font-size:.75rem;">Previous Address</div>
        <div style="font-size:.82rem;"><?= nl2br(htmlspecialchars($renter['previous_address'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($renter['notes']): ?>
      <div class="mt-2 pt-2 border-top">
        <div class="text-muted" style="font-size:.75rem;">Notes</div>
        <div style="font-size:.82rem;"><?= nl2br(htmlspecialchars($renter['notes'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Tenancy History + Documents -->
  <div class="col-lg-8">
    <div class="card-box mb-3">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Tenancy History</h6>
        <a href="<?= APP_URL ?>/tenancies?action=create&renter_id=<?= $renter['id'] ?>" class="btn btn-sm btn-outline-primary">+ New Tenancy</a>
      </div>
      <?php if ($tenancies): ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr>
            <th style="font-size:.75rem;">Property</th>
            <th style="font-size:.75rem;">Type</th>
            <th style="font-size:.75rem;">Period</th>
            <th style="font-size:.75rem;" class="text-end">Rent</th>
            <th style="font-size:.75rem;">Status</th>
            <th style="font-size:.75rem;"></th>
          </tr></thead>
          <tbody>
            <?php foreach ($tenancies as $t):
              $sc = match($t['status']??'') { 'active'=>'badge-green', 'pending'=>'badge-amber', 'expired','terminated'=>'badge-red', default=>'badge-amber' };
            ?>
            <tr>
              <td class="fw-semibold" style="font-size:.82rem;"><?= htmlspecialchars($t['property_name']??'—') ?></td>
              <td><?= StrategyEngine::badge($t['type']) ?></td>
              <td style="font-size:.78rem;"><?= date('M Y',strtotime($t['start_date'])) ?> – <?= date('M Y',strtotime($t['end_date'])) ?></td>
              <td class="text-end fw-semibold" style="font-size:.82rem;">RM <?= number_format($t['monthly_rent'],0) ?></td>
              <td><span class="<?= $sc ?>"><?= ucfirst($t['status']) ?></span></td>
              <td>
                <a href="<?= APP_URL ?>/tenancies?view=<?= $t['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px;">View</a>
                <a href="<?= APP_URL ?>/rent-payments?tenancy_id=<?= $t['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px;">Payments</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.875rem;">No tenancies linked yet. <a href="<?= APP_URL ?>/tenancies?action=create&renter_id=<?= $renter['id'] ?>">Create one</a></p>
      <?php endif; ?>
    </div>

    <!-- Documents -->
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Documents (<?= count($docs) ?>)</h6>
        <a href="<?= APP_URL ?>/owner-documents?renter_id=<?= $renter['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload me-1"></i>Upload</a>
      </div>
      <?php if ($docs): ?>
      <table class="table table-sm mb-0">
        <tbody>
          <?php foreach ($docs as $doc): ?>
          <tr>
            <td style="font-size:.82rem;"><i class="bi bi-file-earmark-text me-1 text-muted"></i><?= htmlspecialchars($doc['title']) ?></td>
            <?php if ($doc['description']): ?><td style="font-size:.75rem;" class="text-muted"><?= htmlspecialchars($doc['description']) ?></td><?php else: ?><td></td><?php endif; ?>
            <td style="font-size:.75rem;" class="text-muted"><?= date('d M Y',strtotime($doc['created_at'])) ?></td>
            <td><a href="<?= APP_URL ?>/owner-document-download?id=<?= $doc['id'] ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 6px;"><i class="bi bi-download"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.875rem;">No documents yet. Upload IC copy, employment letter, or reference letters.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; exit;
}

// ── FORM ──────────────────────────────────────────────────────────────────────
if ($action === 'create' || ($action === 'edit' && isset($_GET['id']))) {
    $renter = null;
    if ($action === 'edit') {
        $renter = Database::fetchOne("SELECT * FROM renter_profiles WHERE id=? AND tenant_id=?", [(int)$_GET['id'], $_tenantId]);
        if (!$renter) { header('Location: '.APP_URL.'/renters'); exit; }
    }
    $pageTitle = $renter ? 'Edit Renter' : 'New Renter Profile';
    include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/renters" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Renters</a>
  <h4 class="fw-bold mb-0"><?= $renter ? 'Edit Renter' : 'New Renter Profile' ?></h4>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center"><div class="col-lg-9">
<div class="card-box">
<form method="POST" action="<?= APP_URL ?>/renters">
  <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="<?= $renter ? 'edit' : 'create' ?>">
  <?php if($renter): ?><input type="hidden" name="id" value="<?= $renter['id'] ?>"><?php endif; ?>

  <p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">PERSONAL INFORMATION</p>
  <div class="row g-3 mb-4">
    <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($renter['name']??'') ?>" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold">IC / Passport No.</label>
      <input type="text" name="ic_number" class="form-control" value="<?= htmlspecialchars($renter['ic_number']??'') ?>" placeholder="e.g. 880101-14-1234"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">Date of Birth</label>
      <input type="date" name="date_of_birth" class="form-control" value="<?= $renter['date_of_birth']??'' ?>"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">Nationality</label>
      <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($renter['nationality']??'Malaysian') ?>"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($renter['phone']??'') ?>" placeholder="012-3456789"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">WhatsApp</label>
      <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($renter['whatsapp']??'') ?>" placeholder="Same as phone?"></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($renter['email']??'') ?>"></div>
  </div>

  <p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">EMERGENCY CONTACT</p>
  <div class="row g-3 mb-4">
    <div class="col-md-5"><label class="form-label fw-semibold">Contact Name</label>
      <input type="text" name="emergency_name" class="form-control" value="<?= htmlspecialchars($renter['emergency_name']??'') ?>"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Contact Phone</label>
      <input type="text" name="emergency_phone" class="form-control" value="<?= htmlspecialchars($renter['emergency_phone']??'') ?>"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">Relationship</label>
      <select name="emergency_relation" class="form-select">
        <option value="">Select</option>
        <?php foreach(['Spouse','Parent','Sibling','Child','Friend','Colleague','Other'] as $r): ?>
        <option value="<?=$r?>" <?= ($renter['emergency_relation']??'')===$r?'selected':'' ?>><?=$r?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">EMPLOYMENT</p>
  <div class="row g-3 mb-4">
    <div class="col-md-5"><label class="form-label fw-semibold">Employer / Company</label>
      <input type="text" name="employer_name" class="form-control" value="<?= htmlspecialchars($renter['employer_name']??'') ?>"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Job Title</label>
      <input type="text" name="job_title" class="form-control" value="<?= htmlspecialchars($renter['job_title']??'') ?>"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">Employment Type</label>
      <select name="employment_type" class="form-select">
        <?php foreach(['employed'=>'Employed','self_employed'=>'Self-Employed','student'=>'Student','retired'=>'Retired','other'=>'Other'] as $v=>$l): ?>
        <option value="<?=$v?>" <?= ($renter['employment_type']??'employed')===$v?'selected':'' ?>><?=$l?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Monthly Income (RM)</label>
      <input type="number" name="monthly_income" class="form-control" value="<?= $renter['monthly_income']??'' ?>" min="0" step="100" placeholder="e.g. 5000"></div>
  </div>

  <p class="fw-semibold text-muted mb-2" style="font-size:.8rem;letter-spacing:.05em;">ADDITIONAL</p>
  <div class="row g-3 mb-4">
    <div class="col-12"><label class="form-label fw-semibold">Previous Address</label>
      <textarea name="previous_address" class="form-control" rows="2" placeholder="Previous residential address..."><?= htmlspecialchars($renter['previous_address']??'') ?></textarea></div>
    <div class="col-12"><label class="form-label fw-semibold">Internal Notes</label>
      <textarea name="notes" class="form-control" rows="2" placeholder="Any notes (not visible to renter)..."><?= htmlspecialchars($renter['notes']??'') ?></textarea></div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= APP_URL ?>/renters" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4"><?= $renter ? 'Save Changes' : 'Create Profile' ?></button>
  </div>
</form>
</div></div></div>

<?php include __DIR__.'/../includes/footer.php'; exit;
}

// ── LIST ──────────────────────────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$params  = [$_tenantId];
$where   = 'r.tenant_id=?';
if ($search) { $where .= ' AND (r.name LIKE ? OR r.phone LIKE ? OR r.ic_number LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$renters = Database::fetchAll(
    "SELECT r.*,
            COUNT(DISTINCT t.id) AS tenancy_count,
            SUM(CASE WHEN t.status='active' AND t.deleted_at IS NULL THEN 1 ELSE 0 END) AS active_count,
            MAX(t.monthly_rent) AS latest_rent
     FROM renter_profiles r
     LEFT JOIN str_tenancies t ON t.renter_id = r.id AND t.tenant_id = r.tenant_id
     WHERE $where
     GROUP BY r.id
     ORDER BY r.name",
    $params
);

$pageTitle = 'Renters';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Renter Profiles</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;"><?= count($renters) ?> renter<?= count($renters)!==1?'s':'' ?> on record</p>
  </div>
  <a href="<?= APP_URL ?>/renters?action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Renter</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Search -->
<form method="GET" action="<?= APP_URL ?>/renters" class="mb-3">
  <div class="input-group" style="max-width:360px;">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, phone, IC…" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    <?php if($search): ?><a href="<?= APP_URL ?>/renters" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
  </div>
</form>

<?php if ($renters): ?>
<div class="card-box">
  <table class="table table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th style="font-size:.75rem;">Name</th>
        <th style="font-size:.75rem;">Contact</th>
        <th style="font-size:.75rem;">IC / Passport</th>
        <th style="font-size:.75rem;">Employment</th>
        <th style="font-size:.75rem;" class="text-center">Tenancies</th>
        <th style="font-size:.75rem;" class="text-center">Active</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($renters as $r): ?>
      <tr>
        <td>
          <div class="fw-semibold" style="font-size:.875rem;"><?= htmlspecialchars($r['name']) ?></div>
          <?php if ($r['nationality'] && $r['nationality'] !== 'Malaysian'): ?>
          <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['nationality']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <div style="font-size:.82rem;"><?= htmlspecialchars($r['phone'] ?: '—') ?></div>
          <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['email'] ?: '') ?></div>
        </td>
        <td style="font-size:.82rem;"><?= htmlspecialchars($r['ic_number'] ?: '—') ?></td>
        <td>
          <div style="font-size:.82rem;"><?= htmlspecialchars($r['employer_name'] ?: '—') ?></div>
          <?php if ($r['monthly_income']): ?>
          <div class="text-muted" style="font-size:.72rem;">RM <?= number_format($r['monthly_income'],0) ?>/mo</div>
          <?php endif; ?>
        </td>
        <td class="text-center fw-semibold" style="font-size:.875rem;"><?= $r['tenancy_count'] ?></td>
        <td class="text-center">
          <?php if ($r['active_count']): ?>
          <span class="badge-green"><?= $r['active_count'] ?> Active</span>
          <?php else: ?>
          <span class="text-muted" style="font-size:.75rem;">—</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?= APP_URL ?>/renters?view=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card-box text-center py-5">
  <div style="font-size:3rem;">👥</div>
  <h5 class="mt-3 mb-2"><?= $search ? 'No results for "'.$search.'"' : 'No Renter Profiles Yet' ?></h5>
  <?php if (!$search): ?>
  <p class="text-muted">Create detailed renter profiles to track IC, employment, emergency contacts, and payment history.</p>
  <a href="<?= APP_URL ?>/renters?action=create" class="btn btn-primary">Add First Renter</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
