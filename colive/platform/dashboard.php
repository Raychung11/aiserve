<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requirePlatformLogin();

$db = getDB();

// KPIs
$kpi = $db->query("
    SELECT
        COUNT(*) AS total_companies,
        SUM(status='trial')  AS trials,
        SUM(status='active') AS active,
        SUM(status='suspended') AS suspended
    FROM companies
")->fetch();

$mrr = $db->query("
    SELECT COALESCE(SUM(p.price_monthly),0) AS mrr
    FROM companies c
    JOIN plans p ON p.id = c.plan_id
    WHERE c.status = 'active'
")->fetchColumn();

$newThisMonth = $db->query("
    SELECT COUNT(*) FROM companies
    WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')
")->fetchColumn();

// Recent companies
$recent = $db->query("
    SELECT c.*, p.name AS plan_name
    FROM companies c
    LEFT JOIN plans p ON p.id = c.plan_id
    ORDER BY c.created_at DESC LIMIT 15
")->fetchAll();

// Trials expiring in 3 days
$expiring = $db->query("
    SELECT * FROM companies
    WHERE status='trial' AND trial_ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    ORDER BY trial_ends_at ASC
")->fetchAll();

$adminName = $_SESSION['platform_admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Platform Dashboard &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --p:#9333ea; --dark:#0f172a; }
body { background:#0f172a; font-family:'Segoe UI',sans-serif; color:#e2e8f0; min-height:100vh; }
.topbar { background:#1e293b; border-bottom:1px solid #334155; padding:.75rem 1.5rem; position:sticky; top:0; z-index:50; }
.page { padding:1.75rem 1.5rem; }
.kpi { background:#1e293b; border:1px solid #334155; border-radius:12px; padding:1.25rem 1.5rem; }
.kpi-value { font-size:2rem; font-weight:800; color:#e2e8f0; }
.kpi-label { color:#64748b; font-size:.78rem; text-transform:uppercase; letter-spacing:.07em; }
.kpi-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
.card-dark { background:#1e293b; border:1px solid #334155; border-radius:12px; }
.badge-trial { background:#7c3aed22; color:#a78bfa; font-size:.7rem; padding:.2rem .5rem; border-radius:20px; }
.badge-active { background:#05966922; color:#34d399; font-size:.7rem; padding:.2rem .5rem; border-radius:20px; }
.badge-suspended { background:#dc262622; color:#f87171; font-size:.7rem; padding:.2rem .5rem; border-radius:20px; }
.text-accent { color:#a78bfa; }
a { color:#a78bfa; }
a:hover { color:#c4b5fd; }
</style>
</head>
<body>

<div class="topbar d-flex align-items-center justify-content-between">
  <div class="d-flex align-items-center gap-3">
    <span style="font-weight:800;color:#a78bfa;font-size:1.1rem;"><i class="bi bi-shield-lock-fill me-2"></i>CoLive OS</span>
    <span style="color:#475569;font-size:.8rem;">Platform Admin</span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span style="color:#64748b;font-size:.82rem;"><?= e($adminName) ?></span>
    <a href="<?= APP_URL ?>/platform/companies.php" class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;">Companies</a>
    <a href="<?= APP_URL ?>/platform/logout.php" style="color:#64748b;font-size:.82rem;text-decoration:none;">Sign out</a>
  </div>
</div>

<div class="page">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0" style="color:#e2e8f0;">Platform Dashboard</h4>
      <p style="color:#64748b;font-size:.85rem;margin:0;">Good day, <?= e($adminName) ?>. Here&rsquo;s the network overview.</p>
    </div>
    <a href="<?= APP_URL ?>/platform/companies.php?action=create" class="btn btn-sm" style="background:#9333ea;color:#fff;border:none;font-weight:600;">
      <i class="bi bi-plus-lg me-1"></i>New Operator
    </a>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <?php
    $kpis = [
      ['icon'=>'bi-buildings','bg'=>'#7c3aed22','ic'=>'#a78bfa','label'=>'Total Operators','val'=>$kpi['total_companies'],'sub'=>$newThisMonth.' new this month'],
      ['icon'=>'bi-hourglass-split','bg'=>'#d9770622','ic'=>'#fbbf24','label'=>'On Trial','val'=>$kpi['trials'],'sub'=>count($expiring).' expiring soon'],
      ['icon'=>'bi-check-circle-fill','bg'=>'#05966922','ic'=>'#34d399','label'=>'Active Paid','val'=>$kpi['active'],'sub'=>'paying operators'],
      ['icon'=>'bi-currency-dollar','bg'=>'#0284c722','ic'=>'#38bdf8','label'=>'MRR','val'=>'RM '.number_format((float)$mrr,0),'sub'=>'monthly recurring'],
    ];
    foreach ($kpis as $k): ?>
    <div class="col-6 col-md-3">
      <div class="kpi">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="kpi-label"><?= $k['label'] ?></div>
          <div class="kpi-icon" style="background:<?= $k['bg'] ?>;color:<?= $k['ic'] ?>"><i class="bi <?= $k['icon'] ?>"></i></div>
        </div>
        <div class="kpi-value"><?= $k['val'] ?></div>
        <div style="color:#475569;font-size:.75rem;margin-top:.2rem;"><?= $k['sub'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <!-- Recent operators -->
    <div class="col-lg-8">
      <div class="card-dark p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="fw-bold mb-0" style="color:#e2e8f0;">All Operators</h6>
          <a href="<?= APP_URL ?>/platform/companies.php" style="font-size:.8rem;">View all</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0" style="color:#e2e8f0;font-size:.83rem;">
            <thead style="color:#64748b;">
              <tr>
                <th>Company</th><th>Plan</th><th>Status</th><th>Joined</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $c): ?>
              <tr style="border-color:#334155;">
                <td>
                  <div class="fw-semibold"><?= e($c['name']) ?></div>
                  <div style="color:#64748b;font-size:.75rem;"><?= e($c['email']) ?></div>
                </td>
                <td style="color:#94a3b8;"><?= e($c['plan_name'] ?? '&mdash;') ?></td>
                <td>
                  <?php if ($c['status']==='trial'): ?><span class="badge-trial">Trial</span>
                  <?php elseif ($c['status']==='active'): ?><span class="badge-active">Active</span>
                  <?php else: ?><span class="badge-suspended"><?= ucfirst($c['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td style="color:#64748b;"><?= dateDisplay($c['created_at']) ?></td>
                <td><a href="<?= APP_URL ?>/platform/companies.php?view=<?= $c['id'] ?>" style="font-size:.75rem;">Edit</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$recent): ?>
              <tr><td colspan="5" class="text-center py-3" style="color:#475569;">No operators yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Trials expiring -->
    <div class="col-lg-4">
      <div class="card-dark p-3">
        <h6 class="fw-bold mb-3" style="color:#e2e8f0;"><i class="bi bi-exclamation-triangle-fill me-2" style="color:#fbbf24;"></i>Trials Expiring</h6>
        <?php if ($expiring): ?>
          <?php foreach ($expiring as $co): ?>
          <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #334155;">
            <div>
              <div style="font-size:.83rem;font-weight:600;"><?= e($co['name']) ?></div>
              <div style="color:#64748b;font-size:.75rem;">Ends <?= dateDisplay($co['trial_ends_at']) ?></div>
            </div>
            <a href="<?= APP_URL ?>/platform/companies.php?view=<?= $co['id'] ?>" class="btn btn-sm" style="background:#7c3aed22;color:#a78bfa;border:1px solid #7c3aed33;font-size:.72rem;">Manage</a>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#475569;font-size:.83rem;margin:0;">No trials expiring in the next 3 days.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
