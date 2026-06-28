<?php
// CoLive OS — Operator app shared layout
// Usage: set $pageTitle and $activePage before including this file.
// Requires: $db = getDB() and requireOperatorLogin() already called.
// Close with: include __DIR__ . '/layout_end.php';

$company = $db->prepare('SELECT * FROM companies WHERE id=?');
$company->execute([currentCompanyId()]);
$company = $company->fetch() ?: [];
$brandClr = brandColor($company);

$navUser = $db->prepare('SELECT name, role FROM users WHERE id=?');
$navUser->execute([$_SESSION['user_id'] ?? 0]);
$navUser = $navUser->fetch() ?: [];

$nav = [
    'overview' => [
        'label' => 'Overview',
        'items' => [
            ['href'=>'dashboard.php',   'icon'=>'bi-speedometer2',       'label'=>'Dashboard',       'key'=>'dashboard'],
        ],
    ],
    'inventory' => [
        'label' => 'Inventory',
        'items' => [
            ['href'=>'buildings.php',   'icon'=>'bi-building',            'label'=>'Buildings',       'key'=>'buildings'],
            ['href'=>'units.php',       'icon'=>'bi-door-open-fill',      'label'=>'Units',           'key'=>'units'],
            ['href'=>'rooms.php',       'icon'=>'bi-grid-3x3-gap-fill',   'label'=>'Rooms &amp; Beds','key'=>'rooms'],
            ['href'=>'owners.php',      'icon'=>'bi-person-vcard-fill',   'label'=>'Owners',          'key'=>'owners'],
        ],
    ],
    'residents' => [
        'label' => 'Residents',
        'items' => [
            ['href'=>'residents.php',   'icon'=>'bi-people-fill',         'label'=>'All Residents',   'key'=>'residents'],
            ['href'=>'bookings.php',    'icon'=>'bi-calendar-check-fill', 'label'=>'Bookings',        'key'=>'bookings'],
            ['href'=>'tenancies.php',   'icon'=>'bi-file-earmark-text-fill','label'=>'Tenancies',     'key'=>'tenancies'],
        ],
    ],
    'billing' => [
        'label' => 'Billing',
        'items' => [
            ['href'=>'invoices.php',    'icon'=>'bi-receipt-cutoff',      'label'=>'Invoices',        'key'=>'invoices'],
            ['href'=>'payments.php',    'icon'=>'bi-cash-coin',           'label'=>'Payments',        'key'=>'payments'],
            ['href'=>'utilities.php',   'icon'=>'bi-lightning-charge-fill','label'=>'Utility Readings','key'=>'utilities'],
        ],
    ],
    'finance' => [
        'label' => 'Finance',
        'items' => [
            ['href'=>'payouts.php',     'icon'=>'bi-wallet2',             'label'=>'Owner Payouts',   'key'=>'payouts'],
            ['href'=>'reports.php',     'icon'=>'bi-bar-chart-fill',      'label'=>'Reports',         'key'=>'reports'],
        ],
    ],
    'ops' => [
        'label' => 'Operations',
        'items' => [
            ['href'=>'maintenance.php', 'icon'=>'bi-tools',               'label'=>'Maintenance',     'key'=>'maintenance'],
            ['href'=>'smart_locks.php', 'icon'=>'bi-lock-fill',           'label'=>'Smart Locks',     'key'=>'smart_locks'],
            ['href'=>'support.php',     'icon'=>'bi-chat-dots-fill',      'label'=>'Support Chat',    'key'=>'support'],
        ],
    ],
    'settings' => [
        'label' => 'Settings',
        'items' => [
            ['href'=>'staff.php',       'icon'=>'bi-person-gear',         'label'=>'Staff &amp; Roles','key'=>'staff'],
            ['href'=>'settings.php',    'icon'=>'bi-gear-fill',           'label'=>'Company Settings','key'=>'settings'],
        ],
    ],
];

$stmt = $db->prepare("SELECT COUNT(*) FROM maintenance_tickets WHERE 1=1" . companyWhere() . " AND status='open'");
$stmt->execute([companyId()]);
$openTickets = (int)$stmt->fetchColumn();

$stmt2 = $db->prepare("SELECT COUNT(*) FROM bookings WHERE 1=1" . companyWhere() . " AND status='pending'");
$stmt2->execute([companyId()]);
$pendingBookings = (int)$stmt2->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> &mdash; <?= e($company['name'] ?? APP_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --brand: <?= e($brandClr) ?>;
  --brand-dim: <?= e($brandClr) ?>22;
  --sidebar-bg: #0f172a;
  --sidebar-w: 248px;
  --topbar-h: 56px;
}
*, *::before, *::after { box-sizing: border-box; }
body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; }

/* ── Sidebar ── */
.sidebar {
  position: fixed; top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--sidebar-bg);
  display: flex; flex-direction: column;
  z-index: 100; overflow: hidden;
}
.sb-brand {
  padding: 1.1rem 1.25rem .85rem;
  border-bottom: 1px solid rgba(255,255,255,.07);
  flex-shrink: 0;
}
.sb-brand-name { color: #fff; font-weight: 800; font-size: 1rem; }
.sb-company { color: #64748b; font-size: .72rem; margin-top: .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-nav { flex: 1; overflow-y: auto; padding: .5rem 0 1rem; }
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }
.sb-section { color: #475569; font-size: .62rem; font-weight: 700; text-transform: uppercase;
              letter-spacing: .09em; padding: .9rem 1.25rem .3rem; }
.sb-link {
  display: flex; align-items: center; gap: .55rem;
  padding: .42rem .85rem; margin: .05rem .5rem;
  border-radius: 8px; font-size: .83rem; color: #94a3b8;
  text-decoration: none; transition: background .12s, color .12s;
  position: relative;
}
.sb-link i { font-size: .95rem; width: 1.1rem; flex-shrink: 0; }
.sb-link:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
.sb-link.active { background: var(--brand-dim); color: var(--brand); }
.sb-link.active i { color: var(--brand); }
.sb-badge {
  margin-left: auto; background: #dc2626; color: #fff;
  font-size: .6rem; font-weight: 700; border-radius: 99px;
  min-width: 18px; height: 18px; display: flex; align-items: center;
  justify-content: center; padding: 0 4px;
}
.sb-footer {
  border-top: 1px solid rgba(255,255,255,.07);
  padding: .85rem 1.1rem; flex-shrink: 0;
}
.sb-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: var(--brand); color: #fff; font-size: .75rem;
  font-weight: 700; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── Main ── */
.main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
.topbar {
  position: sticky; top: 0; z-index: 50;
  height: var(--topbar-h); background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 1.5rem;
  display: flex; align-items: center; justify-content: space-between;
}
.page-content { padding: 1.5rem; flex: 1; }
.page-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; }
.page-sub   { color: #64748b; font-size: .82rem; margin: 0; }

/* ── Cards ── */
.card-box { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.25rem; }
.kpi-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.25rem 1.4rem; }
.kpi-val  { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.kpi-lbl  { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; }
.kpi-ico  { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; }
.kpi-sub  { font-size: .75rem; color: #64748b; margin-top: .25rem; }

/* ── Table ── */
.tbl { font-size: .83rem; }
.tbl th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; border-bottom: 2px solid #f1f5f9; padding: .55rem .75rem; }
.tbl td { padding: .6rem .75rem; vertical-align: middle; border-bottom: 1px solid #f8fafc; color: #334155; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: #f8fafc; }

/* ── Status badges ── */
.badge-active    { background:#dcfce7;color:#15803d; }
.badge-pending   { background:#fef9c3;color:#a16207; }
.badge-overdue   { background:#fee2e2;color:#b91c1c; }
.badge-paid      { background:#dcfce7;color:#15803d; }
.badge-open      { background:#dbeafe;color:#1d4ed8; }
.badge-resolved  { background:#dcfce7;color:#15803d; }
.badge-urgent    { background:#fee2e2;color:#b91c1c; }
.badge-medium    { background:#fef9c3;color:#a16207; }
.s-badge { display:inline-block;padding:.2rem .55rem;border-radius:20px;font-size:.7rem;font-weight:700; }

/* ── Utilities ── */
.btn-brand { background: var(--brand); border: none; color: #fff; font-weight: 600; }
.btn-brand:hover { background: #7c3aed; color: #fff; }
.text-brand { color: var(--brand) !important; }
.progress-thin { height: 6px; border-radius: 3px; }

@media(max-width:768px) {
  .sidebar { display: none; }
  .main-wrap { margin-left: 0; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="sb-brand">
    <div class="sb-brand-name">
      <i class="bi bi-house-heart-fill me-2" style="color:var(--brand);"></i>CoLive OS
    </div>
    <div class="sb-company"><?= e($company['name'] ?? 'Operator') ?></div>
  </div>

  <div class="sb-nav">
    <?php foreach ($nav as $section): ?>
    <div class="sb-section"><?= $section['label'] ?></div>
    <?php foreach ($section['items'] as $item):
      $isActive = ($activePage ?? '') === $item['key'];
      $badge = '';
      if ($item['key'] === 'maintenance' && $openTickets > 0)
          $badge = '<span class="sb-badge">' . $openTickets . '</span>';
      if ($item['key'] === 'bookings' && $pendingBookings > 0)
          $badge = '<span class="sb-badge">' . $pendingBookings . '</span>';
    ?>
    <a href="<?= e(APP_URL . '/app/' . $item['href']) ?>" class="sb-link <?= $isActive ? 'active' : '' ?>">
      <i class="bi <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
      <?= $badge ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <div class="sb-footer">
    <div class="d-flex align-items-center gap-2 mb-2">
      <div class="sb-avatar"><?= strtoupper(substr($navUser['name'] ?? 'U', 0, 1)) ?></div>
      <div style="overflow:hidden;flex:1;">
        <div style="color:#e2e8f0;font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($navUser['name'] ?? '') ?></div>
        <div style="color:#475569;font-size:.68rem;"><?= e(ucfirst(str_replace('_',' ',$navUser['role'] ?? ''))) ?></div>
      </div>
    </div>
    <form action="<?= APP_URL ?>/app/logout.php" method="POST">
      <?= csrfField() ?>
      <button class="btn btn-sm w-100" style="background:rgba(255,255,255,.06);color:#64748b;border:1px solid rgba(255,255,255,.08);font-size:.75rem;">
        <i class="bi bi-box-arrow-left me-1"></i>Sign Out
      </button>
    </form>
  </div>
</nav>

<!-- Main -->
<div class="main-wrap">
  <!-- Topbar -->
  <div class="topbar">
    <div>
      <div class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <?php if (!empty($company['trial_ends_at']) && $company['status'] === 'trial'): ?>
      <span style="background:#fef9c3;color:#a16207;font-size:.72rem;padding:.25rem .7rem;border-radius:20px;font-weight:600;">
        <i class="bi bi-hourglass-split me-1"></i>Trial ends <?= dateDisplay($company['trial_ends_at']) ?>
      </span>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/app/maintenance.php?action=create" class="btn btn-sm btn-brand" style="font-size:.78rem;">
        <i class="bi bi-plus-lg me-1"></i>Quick Add
      </a>
    </div>
  </div>
  <div class="page-content">
    <?= flashHtml() ?>
