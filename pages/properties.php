<?php
require_once __DIR__ . '/../includes/auth_check.php';
$activePage = 'properties';

// ── Handle POST actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $data = [
            'tenant_id'        => $_tenantId,
            'name'             => trim($_POST['name'] ?? ''),
            'address'          => trim($_POST['address'] ?? ''),
            'city'             => trim($_POST['city'] ?? ''),
            'state'            => trim($_POST['state'] ?? ''),
            'postcode'         => trim($_POST['postcode'] ?? ''),
            'property_type'    => $_POST['property_type'] ?? 'condo',
            'strata_building'  => trim($_POST['strata_building'] ?? ''),
            'is_strata'        => isset($_POST['is_strata']) ? 1 : 0,
            'bedrooms'         => (int)($_POST['bedrooms'] ?? 1),
            'bathrooms'        => (int)($_POST['bathrooms'] ?? 1),
            'area_sqft'        => $_POST['area_sqft'] ? (float)$_POST['area_sqft'] : null,
            'strategy_mode'    => $_POST['strategy_mode'] ?? 'STR',
            'listing_status'   => $_POST['listing_status'] ?? 'pending',
            'agent_id'         => $_POST['agent_id'] ?: null,
            'owner_id'         => $_POST['owner_id'] ?: null,
            'owner_name'       => trim($_POST['owner_name'] ?? ''),
            'owner_phone'      => trim($_POST['owner_phone'] ?? ''),
            'monthly_target'   => (float)($_POST['monthly_target'] ?? 0),
            'airbnb_url'       => trim($_POST['airbnb_url'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        // Auto-classify compliance
        $eval = ComplianceEngine::evaluate($data);
        $data['compliance_status'] = $eval['status'];
        $data['compliance_notes']  = implode("\n", array_merge($eval['flags'], $eval['recs']));

        if ($action === 'create') {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = Database::insert('properties', $data);
            ActivityLog::record('property.created', 'Property created: ' . $data['name']);
            $_SESSION['flash'] = ['success' => 'Property added successfully.'];
        } else {
            $id = (int)$_POST['id'];
            Database::update('properties', $data, 'id=? AND tenant_id=?', [$id, $_tenantId]);
            ActivityLog::record('property.updated', 'Property updated: ' . $data['name']);
            $_SESSION['flash'] = ['success' => 'Property updated.'];
        }
        header('Location: ' . APP_URL . '/properties?view=' . $id); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        Database::update('properties', ['deleted_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [$id, $_tenantId]);
        ActivityLog::record('property.deleted', 'Property removed');
        $_SESSION['flash'] = ['success' => 'Property removed.'];
        header('Location: ' . APP_URL . '/properties'); exit;
    }

    if ($action === 'apply_strategy') {
        $id   = (int)$_POST['id'];
        $prop = Database::fetchOne('SELECT * FROM properties WHERE id=? AND tenant_id=?', [$id, $_tenantId]);
        if ($prop) {
            $rec = StrategyEngine::recommend($prop);
            Database::update('properties', ['strategy_mode'=>$rec['recommended'],'updated_at'=>date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [$id, $_tenantId]);
        }
        $_SESSION['flash'] = ['success' => 'Strategy recommendation applied.'];
        header('Location: ' . APP_URL . '/properties?view=' . $id); exit;
    }
}

// ── Flash messages ─────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

// ── View single property ───────────────────────────────────────────────
if (isset($_GET['view'])) {
    $property = Database::fetchOne('SELECT * FROM properties WHERE id=? AND tenant_id=? AND deleted_at IS NULL', [(int)$_GET['view'], $_tenantId]);
    if (!$property) { header('Location: ' . APP_URL . '/properties'); exit; }

    $pageTitle    = $property['name'];
    $pageSubtitle = $property['address'] . ', ' . $property['city'];
    $compliance   = ComplianceEngine::evaluate($property);
    $strategy     = StrategyEngine::recommend($property);
    $investment   = Database::fetchOne('SELECT * FROM investments WHERE property_id=? AND tenant_id=?', [$property['id'], $_tenantId]);
    $activeTenancy = Database::fetchOne('SELECT * FROM str_tenancies WHERE property_id=? AND tenant_id=? AND status="active" AND deleted_at IS NULL ORDER BY id DESC LIMIT 1', [$property['id'], $_tenantId]);
    $period       = date('Y-m');
    $monthRevenue = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND property_id=? AND period=? AND type="income"',  [$_tenantId,$property['id'],$period])['s']??0);
    $monthExpense = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND property_id=? AND period=? AND type="expense"', [$_tenantId,$property['id'],$period])['s']??0);
    $agents       = Database::fetchAll('SELECT id,name FROM str_users WHERE tenant_id=? AND role="agent" AND is_active=1', [$_tenantId]);
    $propertyOwner = $property['owner_id'] ? Database::fetchOne('SELECT * FROM str_owners WHERE id=?', [$property['owner_id']]) : null;
    $propertyDocs = Database::fetchAll('SELECT * FROM owner_documents WHERE property_id=? AND tenant_id=? ORDER BY created_at DESC', [$property['id'], $_tenantId]);

    require_once __DIR__ . '/../includes/header.php';
    include __DIR__ . '/partials/property_view.php';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Show create/edit form ──────────────────────────────────────────────
if (isset($_GET['action']) && in_array($_GET['action'], ['create','edit'])) {
    $property = null;
    if ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $property = Database::fetchOne('SELECT * FROM properties WHERE id=? AND tenant_id=? AND deleted_at IS NULL', [(int)$_GET['id'], $_tenantId]);
        if (!$property) { header('Location: ' . APP_URL . '/properties'); exit; }
    }
    // Check plan limit for new property
    if ($_GET['action'] === 'create') {
        $count = Database::count('properties', 'tenant_id=? AND deleted_at IS NULL', [$_tenantId]);
        $limit = (int)($_user['max_properties'] ?? 5);
        if ($count >= $limit) {
            $_SESSION['flash'] = ['error' => "Property limit reached ($limit). Upgrade your plan to add more."];
            header('Location: ' . APP_URL . '/properties'); exit;
        }
    }
    $agents    = Database::fetchAll('SELECT id,name FROM str_users WHERE tenant_id=? AND role="agent" AND is_active=1', [$_tenantId]);
    $owners    = Database::fetchAll('SELECT id,name,phone FROM str_owners WHERE tenant_id=? AND is_active=1 ORDER BY name', [$_tenantId]);
    $pageTitle = $property ? 'Edit Property' : 'Add Property';
    $pageSubtitle = $property ? $property['name'] : 'Register a new portfolio unit';
    require_once __DIR__ . '/../includes/header.php';
    include __DIR__ . '/partials/property_form.php';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Property list ──────────────────────────────────────────────────────
$pageTitle    = 'Properties';
$pageSubtitle = 'Manage your portfolio units';

$where  = 'p.tenant_id=? AND p.deleted_at IS NULL';
$params = [$_tenantId];
if (!empty($_GET['compliance'])) { $where .= ' AND p.compliance_status=?'; $params[] = $_GET['compliance']; }
if (!empty($_GET['strategy']))   { $where .= ' AND p.strategy_mode=?';     $params[] = $_GET['strategy']; }
if (!empty($_GET['status']))     { $where .= ' AND p.listing_status=?';    $params[] = $_GET['status']; }

$properties = Database::fetchAll("SELECT p.*, u.name AS agent_name FROM properties p LEFT JOIN str_users u ON u.id=p.agent_id AND u.tenant_id=p.tenant_id WHERE $where ORDER BY p.created_at DESC", $params);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <form class="d-flex gap-2 flex-wrap" method="GET" action="<?= APP_URL ?>/properties">
    <select name="compliance" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Compliance</option>
      <?php foreach(['green'=>'🟢 Green','amber'=>'🟡 Amber','red'=>'🔴 Red'] as $v=>$l): ?>
      <option value="<?=$v?>" <?= ($_GET['compliance']??'')===$v?'selected':'' ?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
    <select name="strategy" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Strategies</option>
      <?php foreach(['STR','MID_TERM','SUBLET','CORPORATE'] as $s): ?>
      <option value="<?=$s?>" <?= ($_GET['strategy']??'')===$s?'selected':'' ?>><?=$s?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach(['active','inactive','pending','maintenance'] as $s): ?>
      <option value="<?=$s?>" <?= ($_GET['status']??'')===$s?'selected':'' ?>><?=ucfirst($s)?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <a href="<?= APP_URL ?>/properties?action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Property</a>
</div>

<?php if(!$properties): ?>
<div class="text-center py-5 card-box">
  <i class="bi bi-buildings" style="font-size:3rem;color:#cbd5e1;"></i>
  <h5 class="mt-3 text-muted">No properties yet</h5>
  <a href="<?= APP_URL ?>/properties?action=create" class="btn btn-primary mt-2">Add your first property</a>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach($properties as $p): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-box card-hover h-100">
      <div class="d-flex align-items-start justify-content-between mb-2">
        <div>
          <h6 class="fw-semibold mb-1"><?= htmlspecialchars($p['name']) ?></h6>
          <div class="text-muted" style="font-size:.78rem;"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p['city']) ?>, <?= htmlspecialchars($p['state']) ?></div>
        </div>
        <?= ComplianceEngine::badge($p['compliance_status']) ?>
      </div>
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <?= StrategyEngine::badge($p['strategy_mode']) ?>
        <span class="badge-secondary"><?= $p['bedrooms'] ?>BR · <?= $p['bathrooms'] ?>BA</span>
        <span class="badge-<?= $p['listing_status']==='active'?'green':'secondary' ?>"><?= ucfirst($p['listing_status']) ?></span>
      </div>
      <div class="d-flex align-items-center justify-content-between">
        <div class="text-muted" style="font-size:.75rem;"><?= $p['agent_name'] ? '<i class="bi bi-person"></i> '.htmlspecialchars($p['agent_name']) : '' ?></div>
        <a href="<?= APP_URL ?>/properties?view=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif;
require_once __DIR__ . '/../includes/footer.php';
