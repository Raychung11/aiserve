<?php
require_once __DIR__ . '/../includes/auth_check.php';
$activePage = 'tenancies';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

// ── POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'edit') {
        $data = [
            'tenant_id'      => $_tenantId,
            'property_id'    => (int)$_POST['property_id'],
            'tenant_name'    => trim($_POST['tenant_name']),
            'tenant_phone'   => trim($_POST['tenant_phone']??''),
            'tenant_email'   => trim($_POST['tenant_email']??''),
            'tenant_ic'      => trim($_POST['tenant_ic']??''),
            'tenant_company' => trim($_POST['tenant_company']??''),
            'start_date'     => $_POST['start_date'],
            'end_date'       => $_POST['end_date'],
            'monthly_rent'   => (float)$_POST['monthly_rent'],
            'deposit'        => (float)($_POST['deposit']??0),
            'deposit_paid'   => isset($_POST['deposit_paid'])?1:0,
            'type'           => $_POST['type'] ?? 'MID_TERM',
            'status'         => $_POST['status'] ?? 'active',
            'agent_id'       => $_POST['agent_id'] ?: null,
            'notes'          => trim($_POST['notes']??''),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if ($action === 'create') {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = Database::insert('tenancies', $data);
            ActivityLog::record('tenancy.created','Tenancy: '.$data['tenant_name']);
            $_SESSION['flash'] = ['success' => 'Tenancy created.'];
        } else {
            $id = (int)$_POST['id'];
            Database::update('tenancies', $data, 'id=? AND tenant_id=?', [$id, $_tenantId]);
            ActivityLog::record('tenancy.updated','Tenancy updated: '.$data['tenant_name']);
            $_SESSION['flash'] = ['success' => 'Tenancy updated.'];
        }
        header('Location: ' . APP_URL . '/tenancies?view='.$id); exit;
    }
    if ($action === 'delete') {
        Database::update('tenancies',['deleted_at'=>date('Y-m-d H:i:s')],'id=? AND tenant_id=?',[(int)$_POST['id'],$_tenantId]);
        $_SESSION['flash'] = ['success' => 'Tenancy removed.'];
        header('Location: ' . APP_URL . '/tenancies'); exit;
    }
}

// ── View single ────────────────────────────────────────────────────────
if (isset($_GET['view'])) {
    $tenancy = Database::fetchOne('SELECT t.*,p.name AS property_name,u.name AS agent_name FROM tenancies t LEFT JOIN properties p ON p.id=t.property_id LEFT JOIN users u ON u.id=t.agent_id WHERE t.id=? AND t.tenant_id=? AND t.deleted_at IS NULL', [(int)$_GET['view'],$_tenantId]);
    if (!$tenancy) { header('Location: ' . APP_URL . '/tenancies'); exit; }
    $pageTitle    = $tenancy['tenant_name'];
    $pageSubtitle = $tenancy['property_name'] ?? '';
    $daysLeft     = (int)((strtotime($tenancy['end_date'])-time())/86400);
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row g-3">
      <div class="col-lg-6"><div class="card-box">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-semibold mb-0">Lease Details</h6>
          <a href="<?= APP_URL ?>/tenancies?action=edit&id=<?= $tenancy['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        </div>
        <table class="table table-sm table-borderless mb-0">
          <?php foreach([['Tenant',$tenancy['tenant_name']],['IC/Passport',$tenancy['tenant_ic']??'—'],['Phone',$tenancy['tenant_phone']??'—'],['Email',$tenancy['tenant_email']??'—'],['Company',$tenancy['tenant_company']??'—'],['Property',$tenancy['property_name']??'—'],['Agent',$tenancy['agent_name']??'—'],['Type',$tenancy['type']],['Status',ucfirst($tenancy['status'])]] as [$k,$v]): ?>
          <tr><td class="text-muted" style="font-size:.8rem;width:40%"><?=$k?></td><td style="font-size:.875rem;"><?= htmlspecialchars($v) ?></td></tr>
          <?php endforeach; ?>
        </table>
      </div></div>
      <div class="col-lg-6"><div class="card-box">
        <h6 class="fw-semibold mb-3">Financial Terms</h6>
        <div class="row g-3">
          <div class="col-6"><div class="text-muted" style="font-size:.75rem;">Monthly Rent</div><div class="fw-bold text-success" style="font-size:1.3rem;">RM <?= number_format($tenancy['monthly_rent'],0) ?></div></div>
          <div class="col-6"><div class="text-muted" style="font-size:.75rem;">Deposit</div><div class="fw-bold" style="font-size:1.3rem;">RM <?= number_format($tenancy['deposit'],0) ?></div>
            <span style="font-size:.7rem;padding:.2rem .5rem;border-radius:6px;background:<?=$tenancy['deposit_paid']?'#dcfce7':'#fef9c3'?>;color:<?=$tenancy['deposit_paid']?'#15803d':'#b45309'?>"><?=$tenancy['deposit_paid']?'Paid':'Unpaid'?></span></div>
          <div class="col-6"><div class="text-muted" style="font-size:.75rem;">Start Date</div><div class="fw-semibold"><?=date('d M Y',strtotime($tenancy['start_date']))?></div></div>
          <div class="col-6"><div class="text-muted" style="font-size:.75rem;">End Date</div><div class="fw-semibold"><?=date('d M Y',strtotime($tenancy['end_date']))?></div>
            <?php if($tenancy['status']==='active'&&$daysLeft<=30): ?><span class="badge-amber"><?=$daysLeft?>d left</span><?php endif; ?></div>
        </div>
        <?php if($tenancy['notes']): ?><div class="mt-3 pt-3 border-top"><div class="text-muted" style="font-size:.8rem;"><?= nl2br(htmlspecialchars($tenancy['notes'])) ?></div></div><?php endif; ?>
      </div></div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <a href="<?= APP_URL ?>/tenancies" class="btn btn-outline-secondary btn-sm">← Back</a>
      <form action="<?= APP_URL ?>/tenancies" method="POST" onsubmit="return confirm('Remove?')">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $tenancy['id'] ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
      </form>
    </div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Form ───────────────────────────────────────────────────────────────
if (!empty($_GET['action']) && in_array($_GET['action'],['create','edit'])) {
    $tenancy = null;
    if ($_GET['action'] === 'edit') $tenancy = Database::fetchOne('SELECT * FROM tenancies WHERE id=? AND tenant_id=? AND deleted_at IS NULL',[(int)($_GET['id']??0),$_tenantId]);
    $pageTitle = $tenancy ? 'Edit Tenancy' : 'New Tenancy';
    $pageSubtitle = '';
    $properties = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL', [$_tenantId]);
    $agents = Database::fetchAll('SELECT id,name FROM users WHERE tenant_id=? AND role="agent" AND is_active=1', [$_tenantId]);
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row justify-content-center"><div class="col-lg-8"><div class="card-box">
    <form action="<?= APP_URL ?>/tenancies" method="POST">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="action" value="<?= $tenancy ? 'edit' : 'create' ?>">
    <?php if($tenancy): ?><input type="hidden" name="id" value="<?= $tenancy['id'] ?>"><?php endif; ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label><input type="text" name="tenant_name" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_name']??'') ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">IC / Passport</label><input type="text" name="tenant_ic" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_ic']??'') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="text" name="tenant_phone" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_phone']??'') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="tenant_email" class="form-control" value="<?= htmlspecialchars($tenancy['tenant_email']??'') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Property</label>
        <select name="property_id" class="form-select" required>
          <option value="">Select property</option>
          <?php foreach($properties as $p): ?><option value="<?=$p['id']?>" <?= (($tenancy['property_id']??$_GET['property_id']??''))==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Type</label>
        <select name="type" class="form-select" required>
          <?php foreach(['STR','MID_TERM','SUBLET','CORPORATE'] as $t): ?><option value="<?=$t?>" <?= ($tenancy['type']??'MID_TERM')===$t?'selected':'' ?>><?=$t?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
          <?php foreach(['active','pending','expired','terminated'] as $s): ?><option value="<?=$s?>" <?= ($tenancy['status']??'active')===$s?'selected':'' ?>><?=ucfirst($s)?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Start Date</label><input type="date" name="start_date" class="form-control" value="<?= $tenancy['start_date']??date('Y-m-d') ?>" required></div>
      <div class="col-md-3"><label class="form-label fw-semibold">End Date</label><input type="date" name="end_date" class="form-control" value="<?= $tenancy['end_date']??'' ?>" required></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Monthly Rent (RM)</label><input type="number" name="monthly_rent" class="form-control" value="<?= $tenancy['monthly_rent']??'' ?>" step="50" min="0" required></div>
      <div class="col-md-3"><label class="form-label fw-semibold">Deposit (RM)</label><input type="number" name="deposit" class="form-control" value="<?= $tenancy['deposit']??0 ?>" step="50" min="0"></div>
      <div class="col-md-3 d-flex align-items-end pb-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="deposit_paid" <?= !empty($tenancy['deposit_paid'])?'checked':'' ?>><label class="form-check-label">Deposit Paid</label></div></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Agent</label>
        <select name="agent_id" class="form-select"><option value="">No agent</option>
          <?php foreach($agents as $a): ?><option value="<?=$a['id']?>" <?= ($tenancy['agent_id']??'')==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-12"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($tenancy['notes']??'') ?></textarea></div>
      <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="<?= APP_URL ?>/tenancies" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><?= $tenancy ? 'Save Changes' : 'Create Tenancy' ?></button>
      </div>
    </div></form></div></div></div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── List ───────────────────────────────────────────────────────────────
$pageTitle    = 'Tenancies';
$pageSubtitle = 'Lease and rental records';
$where  = 't.tenant_id=? AND t.deleted_at IS NULL';
$params = [$_tenantId];
if (!empty($_GET['status']))      { $where .= ' AND t.status=?';      $params[] = $_GET['status']; }
if (!empty($_GET['property_id'])) { $where .= ' AND t.property_id=?'; $params[] = (int)$_GET['property_id']; }
$tenancies = Database::fetchAll("SELECT t.*,p.name AS property_name FROM tenancies t LEFT JOIN properties p ON p.id=t.property_id WHERE $where ORDER BY t.start_date DESC LIMIT 100", $params);
$expiring  = Database::count('tenancies','tenant_id=? AND status="active" AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND deleted_at IS NULL',[$_tenantId]);
$properties = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL',[$_tenantId]);

require_once __DIR__ . '/../includes/header.php';
if ($expiring): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <strong><?=$expiring?> lease<?=$expiring>1?'s':''?></strong>&nbsp;expiring within 30 days.
</div>
<?php endif; ?>
<div class="d-flex gap-2 align-items-center justify-content-between mb-3 flex-wrap">
  <form class="d-flex gap-2 flex-wrap" method="GET" action="<?= APP_URL ?>/tenancies">
    <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach(['active','pending','expired','terminated'] as $s): ?><option value="<?=$s?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?=ucfirst($s)?></option><?php endforeach; ?>
    </select>
    <select name="property_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Properties</option>
      <?php foreach($properties as $p): ?><option value="<?=$p['id']?>" <?= ($_GET['property_id']??'')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
    </select>
  </form>
  <a href="<?= APP_URL ?>/tenancies?action=create" class="btn btn-sm btn-primary">+ New Tenancy</a>
</div>
<div class="card-box">
  <table class="table table-hover mb-0">
    <thead class="table-light"><tr><th style="font-size:.8rem;">Tenant</th><th style="font-size:.8rem;">Property</th><th style="font-size:.8rem;">Type</th><th style="font-size:.8rem;">Monthly Rent</th><th style="font-size:.8rem;">End Date</th><th style="font-size:.8rem;">Status</th><th></th></tr></thead>
    <tbody>
      <?php if(!$tenancies): ?><tr><td colspan="7" class="text-center text-muted py-4">No tenancy records.</td></tr>
      <?php else: foreach($tenancies as $t):
        $daysLeft = (int)((strtotime($t['end_date'])-time())/86400);
        $sc = match($t['status']) {'active'=>'#dcfce7:#15803d','pending'=>'#fef9c3:#b45309','expired'=>'#f1f5f9:#475569','terminated'=>'#fee2e2:#b91c1c',default=>'#f1f5f9:#475569'};
        [$sbg,$sfg] = explode(':',$sc); ?>
      <tr>
        <td><div class="fw-semibold" style="font-size:.875rem;"><?= htmlspecialchars($t['tenant_name']) ?></div><div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['tenant_phone']??'') ?></div></td>
        <td style="font-size:.85rem;"><?= htmlspecialchars($t['property_name']??'—') ?></td>
        <td><?= StrategyEngine::badge($t['type']) ?></td>
        <td class="fw-semibold" style="font-size:.875rem;">RM <?= number_format($t['monthly_rent'],0) ?></td>
        <td><div style="font-size:.8rem;"><?=date('d M Y',strtotime($t['end_date']))?></div><?php if($t['status']==='active'&&$daysLeft<=30): ?><span class="badge-amber"><?=$daysLeft?>d</span><?php endif; ?></td>
        <td><span style="font-size:.75rem;padding:.25rem .6rem;border-radius:6px;background:<?=$sbg?>;color:<?=$sfg?>"><?=ucfirst($t['status'])?></span></td>
        <td><a href="<?= APP_URL ?>/tenancies?view=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php';
