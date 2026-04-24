<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'STRHub AI') ?> — STRHub AI</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --sidebar-bg:#0f172a; --sidebar-w:260px; --accent:#6366f1; }
body  { background:#f8fafc; font-family:'Segoe UI',sans-serif; }
.sidebar { width:var(--sidebar-w); background:var(--sidebar-bg); min-height:100vh;
           position:fixed; top:0; left:0; z-index:100; overflow-y:auto; }
.sidebar-brand { padding:1.5rem 1.25rem 1rem; border-bottom:1px solid rgba(255,255,255,.08); }
.sidebar-brand h5 { color:#fff; font-weight:700; margin:0; font-size:1.1rem; }
.sidebar-brand small { color:#94a3b8; font-size:.72rem; }
.nav-section { color:#64748b; font-size:.65rem; font-weight:600; text-transform:uppercase;
               letter-spacing:.08em; padding:.75rem 1.25rem .25rem; }
.nav-item { margin:.1rem .5rem; }
.nav-link { color:#cbd5e1; border-radius:8px; padding:.5rem .85rem; font-size:.875rem;
            display:flex; align-items:center; gap:.6rem; text-decoration:none; transition:background .15s,color .15s; }
.nav-link:hover,.nav-link.active { background:rgba(99,102,241,.18); color:#fff; }
.nav-link.active { color:#a5b4fc; }
.nav-link i { font-size:1rem; width:1.1rem; }
.main-wrapper { margin-left:var(--sidebar-w); min-height:100vh; }
.topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:.75rem 1.5rem;
          position:sticky; top:0; z-index:50; }
.page-content { padding:1.75rem 1.5rem; }
.card-box { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:1.25rem; }
.stat-value { font-size:1.6rem; font-weight:700; color:#0f172a; }
.stat-label { color:#64748b; font-size:.8rem; }
.badge-green  { background:#dcfce7; color:#15803d; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-amber  { background:#fef9c3; color:#b45309; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-red    { background:#fee2e2; color:#b91c1c; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-str    { background:#dbeafe; color:#1e40af; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-mid    { background:#ede9fe; color:#5b21b6; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-sub    { background:#fce7f3; color:#9d174d; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-corp   { background:#f0fdf4; color:#166534; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; font-weight:600; }
.badge-secondary { background:#f1f5f9; color:#475569; padding:.3rem .6rem; border-radius:6px; font-size:.75rem; }
.btn-primary { background:var(--accent); border-color:var(--accent); }
.btn-primary:hover { background:#4f46e5; border-color:#4f46e5; }
.card-hover { transition:box-shadow .2s; }
.card-hover:hover { box-shadow:0 4px 20px rgba(0,0,0,.08); }
.sidebar-footer { padding:1rem; position:absolute; bottom:0; width:100%; border-top:1px solid rgba(255,255,255,.08); }
@media(max-width:768px){ .sidebar{display:none;} .main-wrapper{margin-left:0;} }
</style>
</head>
<body>
<div class="d-flex">
<nav class="sidebar">
  <div class="sidebar-brand">
    <h5><i class="bi bi-house-heart-fill" style="color:#6366f1"></i> STRHub AI</h5>
    <small><?= htmlspecialchars($_user['tenant_name'] ?? 'Platform') ?></small>
  </div>
  <ul class="list-unstyled mb-0 pb-5">
    <li class="nav-section">Overview</li>
    <li class="nav-item"><a href="<?= APP_URL ?>/dashboard" class="nav-link <?= ($activePage??'')==='dashboard'?'active':'' ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
    <li class="nav-section">Portfolio</li>
    <li class="nav-item"><a href="<?= APP_URL ?>/properties" class="nav-link <?= ($activePage??'')==='properties'?'active':'' ?>"><i class="bi bi-buildings"></i> Properties</a></li>
    <li class="nav-item"><a href="<?= APP_URL ?>/tenancies" class="nav-link <?= ($activePage??'')==='tenancies'?'active':'' ?>"><i class="bi bi-people-fill"></i> Tenancies</a></li>
    <li class="nav-section">Finance</li>
    <li class="nav-item"><a href="<?= APP_URL ?>/revenue" class="nav-link <?= ($activePage??'')==='revenue'?'active':'' ?>"><i class="bi bi-cash-stack"></i> Revenue &amp; Expenses</a></li>
    <li class="nav-item"><a href="<?= APP_URL ?>/roi-calculator" class="nav-link <?= ($activePage??'')==='roi'?'active':'' ?>"><i class="bi bi-calculator-fill"></i> ROI Calculator</a></li>
    <li class="nav-item"><a href="<?= APP_URL ?>/revenue?report=1" class="nav-link"><i class="bi bi-bar-chart-fill"></i> Annual Report</a></li>
    <li class="nav-section">Network</li>
    <li class="nav-item"><a href="<?= APP_URL ?>/agents" class="nav-link <?= ($activePage??'')==='agents'?'active':'' ?>"><i class="bi bi-person-badge-fill"></i> Agents</a></li>
    <li class="nav-item"><a href="<?= APP_URL ?>/agents?leaderboard=1" class="nav-link"><i class="bi bi-trophy-fill"></i> Leaderboard</a></li>
    <li class="nav-section">Account</li>
    <li class="nav-item"><a href="<?= APP_URL ?>/subscription" class="nav-link <?= ($activePage??'')==='subscription'?'active':'' ?>"><i class="bi bi-credit-card-fill"></i> Subscription</a></li>
  </ul>
  <div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2 mb-2">
      <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center"
           style="width:32px;height:32px;background:var(--accent);font-size:.8rem;flex-shrink:0;">
        <?= strtoupper(substr($_user['name'],0,1)) ?>
      </div>
      <div style="overflow:hidden;">
        <div class="text-white text-truncate" style="font-size:.8rem;"><?= htmlspecialchars($_user['name']) ?></div>
        <div style="color:#64748b;font-size:.7rem;"><?= ucfirst($_user['role']) ?></div>
      </div>
    </div>
    <form action="<?= APP_URL ?>/logout" method="POST">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <button class="btn btn-sm w-100" style="background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.1);">
        <i class="bi bi-box-arrow-left"></i> Sign Out
      </button>
    </form>
  </div>
</nav>
<div class="main-wrapper flex-grow-1">
  <div class="topbar d-flex align-items-center justify-content-between">
    <div>
      <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($pageTitle ?? '') ?></h6>
      <?php if(!empty($pageSubtitle)): ?><small class="text-muted"><?= htmlspecialchars($pageSubtitle) ?></small><?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-3">
      <?php if(($_user['tenant_status']??'')  === 'trial'): ?>
        <?php $daysLeft = max(0,(int)((strtotime($_user['trial_ends_at']??'now') - time()) / 86400)); ?>
        <a href="<?= APP_URL ?>/subscription" class="text-decoration-none" style="background:#fef3c7;color:#92400e;font-size:.75rem;padding:.35rem .75rem;border-radius:20px;">
          <i class="bi bi-clock"></i> Trial: <?= $daysLeft ?>d left
        </a>
      <?php endif; ?>
      <span class="text-muted" style="font-size:.8rem;"><?= date('d M Y') ?></span>
    </div>
  </div>
  <div class="page-content">
<?php if(!empty($flash)): foreach($flash as $type=>$msg): ?>
<div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') ?> alert-dismissible fade show">
  <i class="bi bi-<?= $type === 'error' ? 'exclamation-triangle' : 'check-circle' ?>-fill me-2"></i><?= htmlspecialchars($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endforeach; endif; ?>
