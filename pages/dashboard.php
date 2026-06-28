<?php
require_once __DIR__ . '/../includes/auth_check.php';
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Portfolio overview for ' . date('F Y');
$activePage   = 'dashboard';
$period       = date('Y-m');

// KPIs
$totalRevenue  = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="income"',  [$_tenantId, $period])['s'] ?? 0);
$totalExpenses = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="expense"', [$_tenantId, $period])['s'] ?? 0);
$netProfit     = $totalRevenue - $totalExpenses;

$properties      = Database::fetchAll('SELECT * FROM properties WHERE tenant_id=? AND deleted_at IS NULL', [$_tenantId]);
$activeProps     = count(array_filter($properties, fn($p) => $p['listing_status'] === 'active'));

$complianceSummary = ['green'=>0,'amber'=>0,'red'=>0];
foreach ($properties as $p) { $complianceSummary[$p['compliance_status']] = ($complianceSummary[$p['compliance_status']] ?? 0) + 1; }

// Expiring leases (within 30 days)
$expiringLeases = Database::fetchAll(
    'SELECT t.*, p.name AS property_name FROM str_tenancies t
     LEFT JOIN properties p ON p.id = t.property_id
     WHERE t.tenant_id=? AND t.status="active"
       AND t.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       AND t.deleted_at IS NULL
     ORDER BY t.end_date ASC LIMIT 5',
    [$_tenantId]
);

// Recent entries
$recentEntries = Database::fetchAll(
    'SELECT r.*, p.name AS property_name FROM revenue_entries r
     LEFT JOIN properties p ON p.id = r.property_id
     WHERE r.tenant_id=? ORDER BY r.created_at DESC LIMIT 5',
    [$_tenantId]
);

// 6-month chart data
$chartLabels = $chartIncome = $chartExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $p   = date('Y-m', strtotime("-$i months"));
    $lbl = date('M Y', strtotime("-$i months"));
    $inc = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="income"',  [$_tenantId,$p])['s'] ?? 0);
    $exp = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="expense"', [$_tenantId,$p])['s'] ?? 0);
    $chartLabels[]  = $lbl;
    $chartIncome[]  = $inc;
    $chartExpense[] = $exp;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
  <?php foreach([
    ['Monthly Revenue','RM '.number_format($totalRevenue,0),'bi-cash-stack','#dbeafe','#2563eb',''],
    ['Net Profit','RM '.number_format(abs($netProfit),0),'bi-graph-up-arrow',$netProfit>=0?'#dcfce7':'#fee2e2',$netProfit>=0?'#16a34a':'#dc2626',$netProfit<0?' text-danger':' text-success'],
    ['Active Units',$activeProps.' / '.count($properties),'bi-buildings','#ede9fe','#7c3aed',''],
    ['Expiry Alerts',count($expiringLeases),'bi-bell-fill','#fef9c3','#ca8a04',count($expiringLeases)>0?' text-warning':''],
  ] as [$lbl,$val,$icon,$bg,$color,$cls]): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="stat-label"><?= $lbl ?></span>
        <div style="width:42px;height:42px;border-radius:10px;background:<?= $bg ?>;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
          <i class="bi <?= $icon ?>"></i>
        </div>
      </div>
      <div class="stat-value<?= $cls ?>"><?= $val ?></div>
      <div class="text-muted" style="font-size:.75rem;"><?= $lbl === 'Monthly Revenue' ? $period : '' ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-box">
      <h6 class="fw-semibold mb-3">Revenue vs Expenses (Last 6 Months)</h6>
      <canvas id="revenueChart" height="100"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-box h-100">
      <h6 class="fw-semibold mb-3">Compliance Status</h6>
      <?php foreach([['green','🟢 STR Allowed'],['amber','🟡 Verify Required'],['red','🔴 Not Suitable']] as [$s,$l]): ?>
      <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
        <span style="font-size:.875rem;"><?= $l ?></span>
        <strong><?= $complianceSummary[$s] ?></strong>
      </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between align-items-center py-2">
        <span class="text-muted" style="font-size:.875rem;">Total</span>
        <strong><?= count($properties) ?></strong>
      </div>
      <a href="<?= APP_URL ?>/properties" class="btn btn-sm btn-outline-secondary w-100 mt-2">View All Properties</a>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Expiring Leases</h6>
        <a href="<?= APP_URL ?>/tenancies" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <?php if(!$expiringLeases): ?>
        <div class="text-center text-muted py-4"><i class="bi bi-check-circle" style="font-size:1.5rem;"></i><div class="mt-1" style="font-size:.85rem;">No leases expiring soon</div></div>
      <?php else: foreach($expiringLeases as $l): ?>
        <?php $daysLeft = (int)((strtotime($l['end_date']) - time()) / 86400); ?>
        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
          <div>
            <div class="fw-semibold" style="font-size:.875rem;"><?= htmlspecialchars($l['tenant_name']) ?></div>
            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($l['property_name']??'—') ?></div>
          </div>
          <div class="text-end">
            <span class="badge-amber"><?= $daysLeft ?>d left</span>
            <div class="text-muted" style="font-size:.7rem;"><?= date('d M Y', strtotime($l['end_date'])) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Recent Transactions</h6>
        <a href="<?= APP_URL ?>/revenue?action=create" class="btn btn-sm btn-primary">+ Add</a>
      </div>
      <?php if(!$recentEntries): ?>
        <div class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:1.5rem;"></i><div class="mt-1" style="font-size:.85rem;">No entries yet</div></div>
      <?php else: foreach($recentEntries as $e): ?>
        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
          <div>
            <div class="fw-semibold" style="font-size:.875rem;"><?= ucfirst(str_replace('_',' ',$e['category'])) ?></div>
            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($e['property_name']??'—') ?> · <?= $e['period'] ?></div>
          </div>
          <span class="fw-semibold <?= $e['type']==='income'?'text-success':'text-danger' ?>">
            <?= $e['type']==='income'?'+':'-' ?>RM <?= number_format($e['amount'],0) ?>
          </span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php
$extraJs = '
new Chart(document.getElementById("revenueChart"),{
  type:"bar",
  data:{
    labels:' . json_encode($chartLabels) . ',
    datasets:[
      {label:"Income",data:' . json_encode($chartIncome) . ',backgroundColor:"#6366f1",borderRadius:4},
      {label:"Expenses",data:' . json_encode($chartExpense) . ',backgroundColor:"#e2e8f0",borderRadius:4}
    ]
  },
  options:{responsive:true,plugins:{legend:{position:"top"}},
    scales:{y:{beginAtZero:true,ticks:{callback:v=>"RM "+v.toLocaleString()}}}}
});
';
require_once __DIR__ . '/../includes/footer.php';
