<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();
requireRole('admin','manager','finance');

$db  = getDB();
$cid = companyId();

// ── Revenue last 6 months ─────────────────────────────────────────────────────
$months = [];
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = $m;
    $s = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE company_id=? AND DATE_FORMAT(payment_date,"%Y-%m")=?');
    $s->execute([$cid, $m]);
    $revenueData[] = (float)$s->fetchColumn();
}

// ── Outstanding invoices ──────────────────────────────────────────────────────
$invSummary = $db->prepare(
    "SELECT status, COUNT(*) AS n, COALESCE(SUM(balance),0) AS total
     FROM invoices WHERE company_id=? AND status IN ('issued','partial','overdue')
     GROUP BY status"
);
$invSummary->execute([$cid]);
$invSummary = $invSummary->fetchAll();
$totalOutstanding = array_sum(array_column($invSummary, 'total'));
$totalInvCount    = array_sum(array_column($invSummary, 'n'));

// ── Occupancy ─────────────────────────────────────────────────────────────────
$totalRooms = (int)$db->prepare("SELECT COUNT(*) FROM rooms WHERE company_id=? AND is_active=1")->execute([$cid]) ?
    (int)$db->query("SELECT COUNT(*) FROM rooms WHERE company_id=$cid AND is_active=1")->fetchColumn() : 0;
$occStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE company_id=? AND is_active=1");
$occStmt->execute([$cid]);
$totalRooms = (int)$occStmt->fetchColumn();

$occStmt2 = $db->prepare("SELECT COUNT(DISTINCT room_id) FROM tenancies WHERE company_id=? AND status='active'");
$occStmt2->execute([$cid]);
$occupiedRooms = (int)$occStmt2->fetchColumn();
$occupancyRate = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100, 1) : 0;

// ── Expiring tenancies ────────────────────────────────────────────────────────
$expiring = $db->prepare(
    "SELECT t.*, res.name AS resident_name, r.room_no, u.unit_no, b.name AS building_name
     FROM tenancies t
     JOIN residents res ON res.id=t.resident_id
     JOIN rooms r ON r.id=t.room_id JOIN units u ON u.id=r.unit_id JOIN buildings b ON b.id=u.building_id
     WHERE t.company_id=? AND t.status='active'
     AND t.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY t.end_date ASC LIMIT 20"
);
$expiring->execute([$cid]);
$expiring = $expiring->fetchAll();

// ── Overdue invoices ──────────────────────────────────────────────────────────
$overdue = $db->prepare(
    "SELECT i.*, res.name AS resident_name FROM invoices i
     JOIN residents res ON res.id=i.resident_id
     WHERE i.company_id=? AND i.status IN ('issued','partial') AND i.due_date < CURDATE()
     ORDER BY i.due_date ASC LIMIT 15"
);
$overdue->execute([$cid]);
$overdueInv = $overdue->fetchAll();

// ── Maintenance summary ───────────────────────────────────────────────────────
$maint = $db->prepare(
    "SELECT priority, COUNT(*) AS n FROM maintenance_tickets
     WHERE company_id=? AND status IN ('open','in_progress') GROUP BY priority"
);
$maint->execute([$cid]);
$maintCounts = [];
foreach ($maint->fetchAll() as $row) $maintCounts[$row['priority']] = (int)$row['n'];

// ── Occupancy by building ─────────────────────────────────────────────────────
$bldOcc = $db->prepare(
    "SELECT b.name,
            COUNT(DISTINCT r.id) AS total_rooms,
            COUNT(DISTINCT CASE WHEN t.status='active' THEN t.room_id END) AS occupied
     FROM buildings b
     LEFT JOIN units u ON u.building_id=b.id AND u.company_id=?
     LEFT JOIN rooms r ON r.unit_id=u.id AND r.is_active=1 AND r.company_id=?
     LEFT JOIN tenancies t ON t.room_id=r.id AND t.status='active' AND t.company_id=?
     WHERE b.company_id=?
     GROUP BY b.id ORDER BY b.name"
);
$bldOcc->execute([$cid,$cid,$cid,$cid]);
$bldOcc = $bldOcc->fetchAll();

$pageTitle  = 'Reports';
$activePage = 'reports';
include __DIR__ . '/layout.php';
?>

<div class="row g-3 mb-4">
  <!-- Occupancy KPI -->
  <div class="col-md-3">
    <div class="kpi-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kpi-lbl">Occupancy Rate</div>
        <div class="kpi-ico" style="background:#dcfce7;color:#15803d;"><i class="bi bi-house-fill"></i></div>
      </div>
      <div class="kpi-val"><?= $occupancyRate ?>%</div>
      <div class="kpi-sub"><?= $occupiedRooms ?> / <?= $totalRooms ?> rooms occupied</div>
      <div class="progress progress-thin mt-2">
        <div class="progress-bar" style="width:<?= $occupancyRate ?>%;background:<?= $occupancyRate >= 80 ? '#22c55e' : ($occupancyRate >= 50 ? '#f59e0b' : '#ef4444') ?>;"></div>
      </div>
    </div>
  </div>
  <!-- Outstanding -->
  <div class="col-md-3">
    <div class="kpi-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kpi-lbl">Outstanding</div>
        <div class="kpi-ico" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-exclamation-circle-fill"></i></div>
      </div>
      <div class="kpi-val"><?= money($totalOutstanding) ?></div>
      <div class="kpi-sub"><?= $totalInvCount ?> unpaid invoice<?= $totalInvCount !== 1 ? 's' : '' ?></div>
    </div>
  </div>
  <!-- Revenue this month -->
  <div class="col-md-3">
    <div class="kpi-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kpi-lbl">Revenue This Month</div>
        <div class="kpi-ico" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-cash-coin"></i></div>
      </div>
      <div class="kpi-val"><?= money(end($revenueData)) ?></div>
      <div class="kpi-sub"><?= date('F Y') ?></div>
    </div>
  </div>
  <!-- Open tickets -->
  <div class="col-md-3">
    <div class="kpi-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kpi-lbl">Open Tickets</div>
        <div class="kpi-ico" style="background:#fef9c3;color:#a16207;"><i class="bi bi-tools"></i></div>
      </div>
      <div class="kpi-val"><?= array_sum($maintCounts) ?></div>
      <div class="kpi-sub">
        <?= isset($maintCounts['urgent']) ? $maintCounts['urgent'] . ' urgent' : 'None urgent' ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Revenue chart -->
  <div class="col-lg-7">
    <div class="card-box">
      <h6 class="fw-bold mb-3">Revenue &mdash; Last 6 Months</h6>
      <canvas id="revChart" height="180"></canvas>
    </div>
  </div>
  <!-- Occupancy by building -->
  <div class="col-lg-5">
    <div class="card-box">
      <h6 class="fw-bold mb-3">Occupancy by Building</h6>
      <?php if ($bldOcc): ?>
      <?php foreach ($bldOcc as $b):
        $pct = $b['total_rooms'] > 0 ? round($b['occupied'] / $b['total_rooms'] * 100) : 0;
        $barColor = $pct >= 80 ? '#22c55e' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
      ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
          <span class="fw-semibold"><?= e($b['name']) ?></span>
          <span style="color:#64748b;"><?= $b['occupied'] ?>/<?= $b['total_rooms'] ?> &mdash; <?= $pct ?>%</span>
        </div>
        <div class="progress progress-thin">
          <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <p class="text-muted" style="font-size:.85rem;">No buildings configured.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Expiring tenancies -->
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Leases Expiring (Next 60 Days)</h6>
        <span style="font-size:.78rem;color:#94a3b8;"><?= count($expiring) ?> lease<?= count($expiring)!==1?'s':'' ?></span>
      </div>
      <?php if ($expiring): ?>
      <?php foreach ($expiring as $t):
        $days = (int)floor((strtotime($t['end_date']) - time()) / 86400);
        $color = $days <= 14 ? '#b91c1c' : ($days <= 30 ? '#a16207' : '#334155');
      ?>
      <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f8fafc;font-size:.82rem;">
        <div>
          <div class="fw-semibold"><?= e($t['resident_name']) ?></div>
          <div style="color:#94a3b8;font-size:.75rem;"><?= e($t['building_name'] . ' &mdash; ' . $t['unit_no'] . ' / ' . $t['room_no']) ?></div>
        </div>
        <div class="text-end">
          <div style="color:<?= $color ?>;font-weight:600;"><?= dateDisplay($t['end_date']) ?></div>
          <div style="font-size:.72rem;color:<?= $color ?>;"><?= $days ?> day<?= $days!==1?'s':'' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.85rem;">No leases expiring in the next 60 days.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Overdue invoices -->
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Overdue Invoices</h6>
        <a href="invoices.php?status=overdue" style="font-size:.78rem;">View all</a>
      </div>
      <?php if ($overdueInv): ?>
      <?php foreach ($overdueInv as $inv): ?>
      <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f8fafc;font-size:.82rem;">
        <div>
          <a href="invoices.php?action=view&id=<?= $inv['id'] ?>" class="fw-semibold text-brand"><?= e($inv['invoice_no']) ?></a>
          <div style="color:#94a3b8;font-size:.75rem;"><?= e($inv['resident_name']) ?></div>
        </div>
        <div class="text-end">
          <div class="fw-bold" style="color:#b91c1c;"><?= money((float)$inv['balance']) ?></div>
          <div style="font-size:.72rem;color:#94a3b8;">Due <?= dateDisplay($inv['due_date']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <p class="text-muted mb-0" style="font-size:.85rem;">No overdue invoices.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$labels  = json_encode($months);
$dataset = json_encode($revenueData);
$brandColor = 'var(--brand)';
$extraJs = <<<JS
<script>
(function() {
  const ctx = document.getElementById('revChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: {$labels},
      datasets: [{
        label: 'Revenue (RM)',
        data: {$dataset},
        backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#9333ea',
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v.toLocaleString() } }
      }
    }
  });
})();
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
