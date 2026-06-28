<?php
require_once __DIR__.'/../includes/owner_auth_check.php';

$tab = $_GET['tab'] ?? 'dashboard';

// Load owner's properties
$properties = Database::fetchAll(
    "SELECT p.*, o.name AS owner_name FROM properties p
     LEFT JOIN str_owners o ON o.id = p.owner_id
     WHERE p.owner_id=? AND p.tenant_id=? AND p.deleted_at IS NULL
     ORDER BY p.name",
    [$_ownerId, $_tenantId]
);

$propIds = array_column($properties, 'id');

// ── DOCUMENTS TAB ─────────────────────────────────────────────────────────────
if ($tab === 'documents') {
    $pageTitle = 'My Documents — Owner Portal';
    include __DIR__.'/../includes/owner_header.php';

    $docs = [];
    if ($propIds) {
        $ph   = implode(',', array_fill(0, count($propIds), '?'));
        $docs = Database::fetchAll(
            "SELECT d.*, p.name AS property_name
             FROM owner_documents d
             JOIN properties p ON p.id = d.property_id
             WHERE d.tenant_id=? AND d.property_id IN ($ph)
             ORDER BY d.created_at DESC",
            array_merge([$_tenantId], $propIds)
        );
    }
    ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">My Documents</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;"><?= count($docs) ?> document<?= count($docs)!==1?'s':'' ?></p>
  </div>
</div>

<?php if ($docs): ?>
<div class="card-box">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="font-size:.75rem;">Document</th>
          <th style="font-size:.75rem;">Property</th>
          <th style="font-size:.75rem;">Description</th>
          <th style="font-size:.75rem;">Date</th>
          <th style="font-size:.75rem;">Download</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($docs as $doc): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-<?= str_contains($doc['mime_type']??'','image')?'image':'pdf' ?> text-muted"></i>
              <span class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($doc['title']) ?></span>
            </div>
          </td>
          <td style="font-size:.8rem;" class="text-muted"><?= htmlspecialchars($doc['property_name']) ?></td>
          <td style="font-size:.8rem;" class="text-muted"><?= htmlspecialchars($doc['description'] ?: '—') ?></td>
          <td style="font-size:.8rem;"><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
          <td>
            <a href="<?= APP_URL ?>/owner-document-download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
              <i class="bi bi-download me-1"></i>Download
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="card-box text-center py-5">
  <div style="font-size:3rem;">📁</div>
  <h5 class="mt-3 mb-2">No Documents Yet</h5>
  <p class="text-muted">Your property manager will upload documents here for you to view and download.</p>
</div>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; exit; }

// ── DASHBOARD TAB ─────────────────────────────────────────────────────────────
$pageTitle = 'Owner Portal';
include __DIR__.'/../includes/owner_header.php';

// Current month
$currentPeriod = date('Y-m');
$prevPeriod    = date('Y-m', strtotime('-1 month'));

// Aggregate revenue/expenses for all owned properties
$monthRevenue = 0; $monthExpense = 0;
$prevRevenue  = 0; $prevExpense  = 0;
$propertyStats = [];

if ($propIds) {
    $ph = implode(',', array_fill(0, count($propIds), '?'));

    $rows = Database::fetchAll(
        "SELECT property_id, type, period, SUM(amount) AS total
         FROM revenue_entries
         WHERE tenant_id=? AND property_id IN ($ph) AND period IN (?,?)
         GROUP BY property_id, type, period",
        array_merge([$_tenantId], $propIds, [$currentPeriod, $prevPeriod])
    );

    foreach ($rows as $r) {
        if ($r['type'] === 'income' && $r['period'] === $currentPeriod) $monthRevenue  += $r['total'];
        if ($r['type'] === 'expense' && $r['period'] === $currentPeriod) $monthExpense += $r['total'];
        if ($r['type'] === 'income' && $r['period'] === $prevPeriod)     $prevRevenue  += $r['total'];
        if ($r['type'] === 'expense' && $r['period'] === $prevPeriod)    $prevExpense  += $r['total'];
        $propertyStats[$r['property_id']][$r['type']][$r['period']] = (float)$r['total'];
    }

    // 6-month chart data
    $chartLabels  = [];
    $chartRevenue = [];
    $chartExpense = [];
    for ($i = 5; $i >= 0; $i--) {
        $p = date('Y-m', strtotime("-$i months"));
        $chartLabels[] = date('M y', strtotime("$p-01"));

        $rev = 0; $exp = 0;
        $revRows = Database::fetchAll(
            "SELECT SUM(amount) AS t FROM revenue_entries WHERE tenant_id=? AND property_id IN ($ph) AND type='income' AND period=?",
            array_merge([$_tenantId], $propIds, [$p])
        );
        $expRows = Database::fetchAll(
            "SELECT SUM(amount) AS t FROM revenue_entries WHERE tenant_id=? AND property_id IN ($ph) AND type='expense' AND period=?",
            array_merge([$_tenantId], $propIds, [$p])
        );
        $chartRevenue[] = (float)($revRows[0]['t'] ?? 0);
        $chartExpense[] = (float)($expRows[0]['t'] ?? 0);
    }

    // Document count
    $docCount = 0;
    $dcRow = Database::fetchOne(
        "SELECT COUNT(*) AS c FROM owner_documents WHERE tenant_id=? AND property_id IN ($ph)",
        array_merge([$_tenantId], $propIds)
    );
    $docCount = (int)($dcRow['c'] ?? 0);

    // Active tenancies
    $activeTenancies = Database::fetchAll(
        "SELECT t.*, p.name AS property_name FROM str_tenancies t
         JOIN properties p ON p.id = t.property_id
         WHERE t.tenant_id=? AND t.property_id IN ($ph) AND t.status='active' AND t.end_date >= CURDATE()
         ORDER BY t.end_date ASC",
        array_merge([$_tenantId], $propIds)
    );
} else {
    $chartLabels = $chartRevenue = $chartExpense = [];
    $docCount = 0;
    $activeTenancies = [];
}

$monthNet = $monthRevenue - $monthExpense;
$prevNet  = $prevRevenue  - $prevExpense;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Welcome, <?= htmlspecialchars(explode(' ', $_user['name'])[0]) ?></h4>
    <p class="text-muted mb-0" style="font-size:.875rem;"><?= date('F Y') ?> overview — <?= count($properties) ?> propert<?= count($properties)===1?'y':'ies' ?></p>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">This Month Revenue</div>
      <div class="stat-value text-success">RM <?= number_format($monthRevenue,0) ?></div>
      <div class="stat-label">gross income</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">This Month Expenses</div>
      <div class="stat-value text-danger">RM <?= number_format($monthExpense,0) ?></div>
      <div class="stat-label">total costs</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Net Profit</div>
      <div class="stat-value <?= $monthNet>=0?'text-success':'text-danger' ?>">RM <?= number_format(abs($monthNet),0) ?></div>
      <div class="stat-label"><?= $monthNet>=0?'profit':'loss' ?> this month</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Documents</div>
      <div class="stat-value"><?= $docCount ?></div>
      <div class="stat-label"><a href="<?= APP_URL ?>/owner-portal?tab=documents" style="font-size:.75rem;">view all</a></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- 6-Month Chart -->
  <div class="col-lg-8">
    <div class="card-box">
      <h6 class="fw-semibold mb-3">6-Month Revenue vs Expenses</h6>
      <canvas id="ownerChart" height="120"></canvas>
    </div>
  </div>
  <!-- Active Tenancies -->
  <div class="col-lg-4">
    <div class="card-box">
      <h6 class="fw-semibold mb-3">Active Tenancies</h6>
      <?php if ($activeTenancies): ?>
        <?php foreach ($activeTenancies as $t):
          $daysLeft = (int)((strtotime($t['end_date']) - time()) / 86400); ?>
        <div class="mb-3 pb-3 border-bottom">
          <div class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($t['property_name']) ?></div>
          <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($t['tenant_name']) ?></div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <span class="fw-bold text-success" style="font-size:.85rem;">RM <?= number_format($t['monthly_rent'],0) ?>/mo</span>
            <span class="<?= $daysLeft<=30?'badge-amber':'badge-green' ?>"><?= $daysLeft ?>d left</span>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted mb-0" style="font-size:.875rem;">No active tenancies.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Property Breakdown -->
<?php if ($properties): ?>
<div class="card-box mb-3">
  <h6 class="fw-semibold mb-3">Property Breakdown — <?= date('F Y') ?></h6>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="font-size:.75rem;">Property</th>
          <th style="font-size:.75rem;">City</th>
          <th style="font-size:.75rem;">Strategy</th>
          <th style="font-size:.75rem;">Status</th>
          <th style="font-size:.75rem;" class="text-end">Revenue</th>
          <th style="font-size:.75rem;" class="text-end">Expenses</th>
          <th style="font-size:.75rem;" class="text-end">Net</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($properties as $p):
          $pRev = (float)($propertyStats[$p['id']]['income'][$currentPeriod] ?? 0);
          $pExp = (float)($propertyStats[$p['id']]['expense'][$currentPeriod] ?? 0);
          $pNet = $pRev - $pExp;
        ?>
        <tr>
          <td class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($p['name']) ?></td>
          <td style="font-size:.8rem;" class="text-muted"><?= htmlspecialchars($p['city']) ?></td>
          <td><?= StrategyEngine::badge($p['strategy_mode']) ?></td>
          <td><span class="badge bg-<?= $p['listing_status']==='active'?'success':'secondary' ?> text-white" style="font-size:.7rem;"><?= ucfirst($p['listing_status']) ?></span></td>
          <td class="text-end text-success" style="font-size:.85rem;" class="fw-semibold">RM <?= number_format($pRev,0) ?></td>
          <td class="text-end text-danger" style="font-size:.85rem;">RM <?= number_format($pExp,0) ?></td>
          <td class="text-end fw-bold <?= $pNet>=0?'text-success':'text-danger' ?>" style="font-size:.85rem;">RM <?= number_format(abs($pNet),0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="card-box text-center py-4">
  <div style="font-size:3rem;">🏠</div>
  <h5 class="mt-3 mb-2">No Properties Assigned</h5>
  <p class="text-muted">Your property manager hasn't linked any properties to your account yet.</p>
</div>
<?php endif; ?>

<?php
$labelsJson  = json_encode($chartLabels);
$revJson     = json_encode($chartRevenue);
$expJson     = json_encode($chartExpense);
$extraJs = <<<JS
const ctx = document.getElementById('ownerChart');
if (ctx) {
  new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: $labelsJson,
      datasets: [
        { label: 'Revenue', data: $revJson, backgroundColor: 'rgba(34,197,94,.7)', borderRadius: 4 },
        { label: 'Expenses', data: $expJson, backgroundColor: 'rgba(239,68,68,.6)', borderRadius: 4 },
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: { y: { ticks: { callback: v => 'RM ' + v.toLocaleString('en-MY') } } }
    }
  });
}
JS;

include __DIR__.'/../includes/footer.php';
