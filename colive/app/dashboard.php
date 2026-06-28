<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db  = getDB();
$cid = companyId();
$cw  = companyWhere();

// ── KPI queries ───────────────────────────────────────────────────────────────
// Units & rooms
$inv = $db->prepare("
    SELECT
        (SELECT COUNT(*) FROM units  WHERE 1=1 $cw AND is_active=1) AS total_units,
        (SELECT COUNT(*) FROM rooms  WHERE 1=1 $cw AND is_active=1) AS total_rooms,
        (SELECT COUNT(*) FROM beds   WHERE 1=1 $cw AND is_active=1) AS total_beds
");
$inv->execute([$cid,$cid,$cid]);
$inv = $inv->fetch();

// Occupied rooms (has active tenancy)
$occupied = $db->prepare("
    SELECT COUNT(DISTINCT room_id) FROM tenancies WHERE 1=1 $cw AND status='active'
");
$occupied->execute([$cid]);
$occupiedRooms = (int)$occupied->fetchColumn();
$occupancyPct  = $inv['total_rooms'] > 0
    ? round($occupiedRooms / $inv['total_rooms'] * 100)
    : 0;

// Active residents
$stmt = $db->prepare("SELECT COUNT(*) FROM residents WHERE 1=1 $cw AND is_active=1");
$stmt->execute([$cid]);
$totalResidents = (int)$stmt->fetchColumn();

// Revenue this month
$stmt = $db->prepare("
    SELECT COALESCE(SUM(amount),0) FROM payments
    WHERE 1=1 $cw AND DATE_FORMAT(payment_date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')
");
$stmt->execute([$cid]);
$revenueThisMonth = (float)$stmt->fetchColumn();

// Revenue last month
$stmt = $db->prepare("
    SELECT COALESCE(SUM(amount),0) FROM payments
    WHERE 1=1 $cw AND DATE_FORMAT(payment_date,'%Y-%m')=DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 MONTH),'%Y-%m')
");
$stmt->execute([$cid]);
$revenueLastMonth = (float)$stmt->fetchColumn();

// Outstanding invoices
$stmt = $db->prepare("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(balance),0) AS amt
    FROM invoices WHERE 1=1 $cw AND status IN ('issued','partial','overdue')
");
$stmt->execute([$cid]);
$outstanding = $stmt->fetch();

// Overdue invoices
$stmt = $db->prepare("
    SELECT COUNT(*) FROM invoices WHERE 1=1 $cw AND status='overdue'
");
$stmt->execute([$cid]);
$overdueCount = (int)$stmt->fetchColumn();

// Maintenance
$stmt = $db->prepare("
    SELECT
        SUM(status='open')        AS open_count,
        SUM(status='in_progress') AS inprog,
        SUM(priority='urgent' AND status NOT IN ('resolved','closed')) AS urgent
    FROM maintenance_tickets WHERE 1=1 $cw
");
$stmt->execute([$cid]);
$maint = $stmt->fetch();

// Pending bookings
$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE 1=1 $cw AND status='pending'");
$stmt->execute([$cid]);
$pendingBk = (int)$stmt->fetchColumn();

// ── Occupancy by building ─────────────────────────────────────────────────────
$buildingOcc = $db->prepare("
    SELECT b.name,
           COUNT(DISTINCT r.id) AS total_rooms,
           COUNT(DISTINCT t.room_id) AS occupied
    FROM buildings b
    LEFT JOIN units u   ON u.building_id=b.id AND u.company_id=$cid
    LEFT JOIN rooms r   ON r.unit_id=u.id     AND r.company_id=$cid AND r.is_active=1
    LEFT JOIN tenancies t ON t.room_id=r.id   AND t.company_id=$cid AND t.status='active'
    WHERE b.company_id=$cid
    GROUP BY b.id
    ORDER BY b.name
");
$buildingOcc->execute();
$buildings = $buildingOcc->fetchAll();

// ── Revenue last 6 months ─────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT DATE_FORMAT(payment_date,'%b %Y') AS mo,
           DATE_FORMAT(payment_date,'%Y-%m') AS period,
           SUM(amount) AS total
    FROM payments WHERE 1=1 $cw
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY period ORDER BY period ASC
");
$stmt->execute([$cid]);
$revChart = $stmt->fetchAll();
$chartLabels = array_column($revChart,'mo');
$chartData   = array_column($revChart,'total');

// ── Overdue invoices list ─────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT i.*, r.name AS resident_name
    FROM invoices i
    JOIN residents r ON r.id=i.resident_id
    WHERE i.status='overdue' AND i.company_id=$cid
    ORDER BY i.due_date ASC LIMIT 8
");
$stmt->execute();
$overdueList = $stmt->fetchAll();

// ── Recent bookings ───────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT bk.*, rm.room_no, un.unit_no, bd.name AS building_name
    FROM bookings bk
    LEFT JOIN rooms r     ON r.id=bk.room_id
    LEFT JOIN rooms rm    ON rm.id=bk.room_id
    LEFT JOIN units un    ON un.id=rm.unit_id
    LEFT JOIN buildings bd ON bd.id=un.building_id
    WHERE bk.company_id=$cid
    ORDER BY bk.created_at DESC LIMIT 6
");
$stmt->execute();
$recentBookings = $stmt->fetchAll();

// ── Recent maintenance ────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT mt.*, r.name AS resident_name
    FROM maintenance_tickets mt
    LEFT JOIN residents r ON r.id=mt.resident_id
    WHERE mt.company_id=$cid
    ORDER BY mt.created_at DESC LIMIT 6
");
$stmt->execute();
$recentMaint = $stmt->fetchAll();

// ── Activity feed ─────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT al.*, u.name AS staff_name
    FROM audit_logs al
    LEFT JOIN users u ON u.id=al.user_id
    WHERE al.company_id=$cid
    ORDER BY al.created_at DESC LIMIT 10
");
$stmt->execute();
$activity = $stmt->fetchAll();

$revGrowth = $revenueLastMonth > 0
    ? round(($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100, 1)
    : 0;

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/layout.php';
?>

<!-- KPI cards row 1 -->
<div class="row g-3 mb-3">
  <?php
  $kpis = [
    ['label'=>'Occupancy','val'=>$occupancyPct.'%','sub'=>$occupiedRooms.'/'.$inv['total_rooms'].' rooms occupied',
     'icon'=>'bi-house-check-fill','bg'=>'#f5f3ff','ic'=>'#9333ea'],
    ['label'=>'Active Residents','val'=>$totalResidents,'sub'=>$pendingBk.' pending bookings',
     'icon'=>'bi-people-fill','bg'=>'#eff6ff','ic'=>'#3b82f6'],
    ['label'=>'Revenue This Month','val'=>money($revenueThisMonth),'sub'=>($revGrowth>=0?'+':'').$revGrowth.'% vs last month',
     'icon'=>'bi-cash-stack','bg'=>'#f0fdf4','ic'=>'#22c55e'],
    ['label'=>'Outstanding','val'=>money((float)$outstanding['amt']),'sub'=>$outstanding['cnt'].' invoices unpaid',
     'icon'=>'bi-receipt','bg'=>'#fff7ed','ic'=>'#f97316'],
  ];
  foreach ($kpis as $k): ?>
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="kpi-lbl"><?= $k['label'] ?></div>
        <div class="kpi-ico" style="background:<?= $k['bg'] ?>;color:<?= $k['ic'] ?>;"><i class="bi <?= $k['icon'] ?>"></i></div>
      </div>
      <div class="kpi-val"><?= $k['val'] ?></div>
      <div class="kpi-sub"><?= $k['sub'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- KPI cards row 2 -->
<div class="row g-3 mb-4">
  <?php
  $kpis2 = [
    ['label'=>'Total Units','val'=>$inv['total_units'],'sub'=>$inv['total_rooms'].' rooms &bull; '.$inv['total_beds'].' beds',
     'icon'=>'bi-door-open-fill','bg'=>'#faf5ff','ic'=>'#a855f7'],
    ['label'=>'Overdue Invoices','val'=>$overdueCount,'sub'=>'require immediate follow-up',
     'icon'=>'bi-exclamation-triangle-fill','bg'=>'#fff1f2','ic'=>'#f43f5e'],
    ['label'=>'Open Tickets','val'=>(int)$maint['open_count'],'sub'=>(int)$maint['urgent'].' urgent, '.(int)$maint['inprog'].' in progress',
     'icon'=>'bi-tools','bg'=>'#fff7ed','ic'=>'#f59e0b'],
    ['label'=>'Pending Bookings','val'=>$pendingBk,'sub'=>'awaiting approval',
     'icon'=>'bi-calendar-check-fill','bg'=>'#eff6ff','ic'=>'#6366f1'],
  ];
  foreach ($kpis2 as $k): ?>
  <div class="col-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="kpi-lbl"><?= $k['label'] ?></div>
        <div class="kpi-ico" style="background:<?= $k['bg'] ?>;color:<?= $k['ic'] ?>;"><i class="bi <?= $k['icon'] ?>"></i></div>
      </div>
      <div class="kpi-val"><?= $k['val'] ?></div>
      <div class="kpi-sub"><?= $k['sub'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <!-- Revenue chart -->
  <div class="col-lg-7">
    <div class="card-box h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Revenue &mdash; Last 6 Months</h6>
        <a href="<?= APP_URL ?>/app/reports.php" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Full Report</a>
      </div>
      <?php if ($revChart): ?>
      <canvas id="revChart" height="90"></canvas>
      <?php else: ?>
      <div class="text-center py-5 text-muted" style="font-size:.875rem;">No payment data yet.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Occupancy by building -->
  <div class="col-lg-5">
    <div class="card-box h-100">
      <h6 class="fw-bold mb-3">Occupancy by Building</h6>
      <?php if ($buildings): ?>
        <?php foreach ($buildings as $bg):
          $pct = $bg['total_rooms'] > 0 ? round($bg['occupied'] / $bg['total_rooms'] * 100) : 0;
          $clr = $pct >= 80 ? '#22c55e' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span style="font-size:.83rem;font-weight:600;"><?= e($bg['name']) ?></span>
            <span style="font-size:.78rem;color:#64748b;"><?= $bg['occupied'] ?>/<?= $bg['total_rooms'] ?> &nbsp; <strong style="color:<?= $clr ?>;"><?= $pct ?>%</strong></span>
          </div>
          <div class="progress progress-thin">
            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $clr ?>;border-radius:3px;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center py-4 text-muted" style="font-size:.875rem;">
          No buildings yet. <a href="<?= APP_URL ?>/app/buildings.php">Add one.</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- Overdue invoices -->
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-circle-fill me-2 text-danger"></i>Overdue Invoices</h6>
        <a href="<?= APP_URL ?>/app/invoices.php?status=overdue" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;">View All (<?= $overdueCount ?>)</a>
      </div>
      <?php if ($overdueList): ?>
      <table class="table tbl mb-0">
        <thead><tr><th>Resident</th><th>Invoice</th><th>Due</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
          <?php foreach ($overdueList as $inv2): ?>
          <tr>
            <td><?= e($inv2['resident_name']) ?></td>
            <td><a href="<?= APP_URL ?>/app/invoices.php?view=<?= $inv2['id'] ?>" style="font-size:.8rem;"><?= e($inv2['invoice_no']) ?></a></td>
            <td style="color:#ef4444;"><?= dateDisplay($inv2['due_date']) ?></td>
            <td class="text-end fw-semibold" style="color:#ef4444;"><?= money((float)$inv2['balance']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="text-center py-4" style="color:#22c55e;"><i class="bi bi-check-circle-fill me-2"></i>No overdue invoices. Great work!</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent bookings -->
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Recent Bookings</h6>
        <a href="<?= APP_URL ?>/app/bookings.php" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;">View All</a>
      </div>
      <?php if ($recentBookings): ?>
      <table class="table tbl mb-0">
        <thead><tr><th>Name</th><th>Room</th><th>Move-in</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentBookings as $bk):
            $bkcls = match($bk['status']){
                'pending'=>'badge-pending','approved'=>'badge-active',
                'rejected'=>'badge-overdue',default=>'badge-paid'};
          ?>
          <tr>
            <td><?= e($bk['name']) ?></td>
            <td style="color:#64748b;"><?= e(($bk['building_name']??'').($bk['unit_no']??'').'-'.($bk['room_no']??'')) ?></td>
            <td><?= dateDisplay($bk['move_in_date']) ?></td>
            <td><span class="s-badge <?= $bkcls ?>"><?= ucfirst($bk['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="text-center py-4 text-muted" style="font-size:.875rem;">No bookings yet.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Maintenance tickets -->
  <div class="col-lg-7">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Maintenance Tickets</h6>
        <div class="d-flex gap-2">
          <a href="<?= APP_URL ?>/app/maintenance.php?action=create" class="btn btn-sm btn-brand" style="font-size:.72rem;"><i class="bi bi-plus-lg me-1"></i>New</a>
          <a href="<?= APP_URL ?>/app/maintenance.php" class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;">View All</a>
        </div>
      </div>
      <?php if ($recentMaint): ?>
      <table class="table tbl mb-0">
        <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Resident</th></tr></thead>
        <tbody>
          <?php foreach ($recentMaint as $mt):
            $priCls = match($mt['priority']){'urgent'=>'badge-urgent','high'=>'badge-overdue','medium'=>'badge-medium',default=>''};
            $staCls = match($mt['status']){'open'=>'badge-open','in_progress'=>'badge-pending','resolved'=>'badge-resolved',default=>''};
          ?>
          <tr>
            <td><a href="<?= APP_URL ?>/app/maintenance.php?view=<?= $mt['id'] ?>" style="font-size:.83rem;"><?= e($mt['title']) ?></a></td>
            <td style="color:#64748b;"><?= ucfirst($mt['category']) ?></td>
            <td><span class="s-badge <?= $priCls ?>"><?= ucfirst($mt['priority']) ?></span></td>
            <td><span class="s-badge <?= $staCls ?>"><?= ucfirst(str_replace('_',' ',$mt['status'])) ?></span></td>
            <td style="color:#64748b;"><?= e($mt['resident_name'] ?? '&mdash;') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="text-center py-4 text-muted" style="font-size:.875rem;">No tickets yet.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Activity feed -->
  <div class="col-lg-5">
    <div class="card-box h-100">
      <h6 class="fw-bold mb-3">Recent Activity</h6>
      <?php if ($activity): ?>
        <div style="font-size:.8rem;">
          <?php foreach ($activity as $act): ?>
          <div class="d-flex gap-2 mb-3">
            <div style="width:28px;height:28px;background:#f5f3ff;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi bi-clock-history" style="color:#9333ea;font-size:.75rem;"></i>
            </div>
            <div>
              <div style="color:#334155;">
                <strong><?= e($act['staff_name'] ?? 'System') ?></strong>
                &mdash; <?= e(str_replace('_',' ',$act['action'])) ?>
                <?php if ($act['target_table']): ?>
                <span style="color:#94a3b8;"> on <?= e($act['target_table']) ?></span>
                <?php endif; ?>
              </div>
              <div style="color:#94a3b8;font-size:.73rem;"><?= datetimeDisplay($act['created_at']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted" style="font-size:.875rem;">No activity yet.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$brandHex = e($brandClr);
$extraJs  = '';
if ($revChart) {
    $chartLabelsJson = json_encode($chartLabels);
    $chartDataJson   = json_encode(array_map('floatval', $chartData));
    $extraJs = <<<JS
(function(){
  const ctx = document.getElementById('revChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: {$chartLabelsJson},
      datasets: [{
        label: 'Revenue (RM)',
        data: {$chartDataJson},
        backgroundColor: '{$brandHex}33',
        borderColor: '{$brandHex}',
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero:true, ticks:{ callback: v => 'RM'+v.toLocaleString() }, grid:{color:'#f1f5f9'} },
        x: { grid:{ display:false } }
      }
    }
  });
})();
JS;
}
include __DIR__ . '/layout_end.php';
?>
