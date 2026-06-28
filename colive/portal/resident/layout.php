<?php
// CoLive OS — Resident portal shared layout
// Usage: set $pageTitle and $activePage before including this file.
// Requires: requireResidentLogin() already called, $db = getDB() available.
// Close with: include __DIR__ . '/layout_end.php';

$stmt = $db->prepare('SELECT * FROM companies WHERE id=?');
$stmt->execute([(int)$_SESSION['resident_company_id']]);
$company  = $stmt->fetch() ?: [];
$brandClr = brandColor($company);

$stmt2 = $db->prepare('SELECT * FROM residents WHERE id=?');
$stmt2->execute([(int)$_SESSION['resident_id']]);
$resident = $stmt2->fetch() ?: [];

$residentInitial = strtoupper(substr($resident['name'] ?? $_SESSION['resident_name'] ?? 'R', 0, 1));
$residentName    = $resident['name'] ?? $_SESSION['resident_name'] ?? 'Resident';

$nav = [
    ['href' => 'dashboard.php',    'icon' => 'bi-house-fill',       'label' => 'Home',        'key' => 'dashboard'],
    ['href' => 'invoices.php',     'icon' => 'bi-receipt-cutoff',   'label' => 'Invoices',    'key' => 'invoices'],
    ['href' => 'maintenance.php',  'icon' => 'bi-tools',            'label' => 'Maintenance', 'key' => 'maintenance'],
    ['href' => 'support.php',      'icon' => 'bi-chat-dots-fill',   'label' => 'Support',     'key' => 'support'],
];
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
  --brand:      <?= e($brandClr) ?>;
  --brand-dim:  <?= e($brandClr) ?>22;
  --brand-rgb:  <?= implode(',', sscanf($brandClr, '#%02x%02x%02x') ?: [147,51,234]) ?>;
  --sidebar-w:  260px;
  --topbar-h:   56px;
  --bottom-tab: 60px;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  background: #f1f5f9;
  font-family: 'Segoe UI', system-ui, sans-serif;
  margin: 0;
}

/* ── Sidebar ── */
.res-sidebar {
  position: fixed; top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--brand);
  display: flex; flex-direction: column;
  z-index: 200; overflow: hidden;
  transition: transform .25s ease;
}
.res-sidebar-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 199;
}

/* Avatar / name card at top of sidebar */
.sb-res-card {
  padding: 1.5rem 1.25rem 1.1rem;
  border-bottom: 1px solid rgba(255,255,255,.18);
  flex-shrink: 0;
  text-align: center;
}
.sb-res-avatar {
  width: 56px; height: 56px; border-radius: 50%;
  background: rgba(255,255,255,.25);
  color: #fff; font-size: 1.4rem; font-weight: 800;
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: .65rem;
  border: 2px solid rgba(255,255,255,.5);
}
.sb-res-name {
  color: #fff; font-size: .92rem; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sb-res-label {
  color: rgba(255,255,255,.7); font-size: .72rem; margin-top: .1rem;
}

/* Nav links */
.sb-nav { flex: 1; overflow-y: auto; padding: .75rem 0 1rem; }
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 2px; }

.sb-link {
  display: flex; align-items: center; gap: .6rem;
  padding: .6rem 1.1rem; margin: .1rem .6rem;
  border-radius: 10px; font-size: .88rem;
  color: rgba(255,255,255,.82);
  text-decoration: none; transition: background .13s, color .13s;
}
.sb-link i { font-size: 1rem; width: 1.15rem; flex-shrink: 0; }
.sb-link:hover  { background: rgba(255,255,255,.15); color: #fff; }
.sb-link.active { background: rgba(255,255,255,.22); color: #fff; font-weight: 700; }
.sb-link.active i { color: #fff; }

/* Sidebar footer (logout) */
.sb-footer {
  border-top: 1px solid rgba(255,255,255,.18);
  padding: .9rem 1.1rem; flex-shrink: 0;
}

/* ── Top header ── */
.res-topbar {
  position: sticky; top: 0; z-index: 100;
  height: var(--topbar-h);
  background: var(--brand);
  padding: 0 1rem 0 1.25rem;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.res-topbar-left { display: flex; align-items: center; gap: .75rem; }
.topbar-company {
  color: #fff; font-weight: 800; font-size: 1rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 160px;
}
.topbar-logo {
  height: 34px; width: auto; max-width: 120px;
  object-fit: contain; border-radius: 4px;
}
.btn-hamburger {
  background: rgba(255,255,255,.15); border: none;
  width: 36px; height: 36px; border-radius: 8px;
  color: #fff; font-size: 1.15rem;
  display: none; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
}
.btn-hamburger:hover { background: rgba(255,255,255,.25); }

/* Resident dropdown in topbar */
.res-user-btn {
  display: flex; align-items: center; gap: .5rem;
  background: rgba(255,255,255,.15); border: none;
  border-radius: 8px; padding: .35rem .7rem;
  color: #fff; font-size: .82rem; font-weight: 600;
  cursor: pointer; transition: background .13s;
  white-space: nowrap;
}
.res-user-btn:hover, .res-user-btn:focus { background: rgba(255,255,255,.25); outline: none; }
.res-user-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(255,255,255,.3); color: #fff;
  font-size: .75rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.res-dropdown-menu {
  min-width: 180px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  padding: .35rem 0;
  overflow: hidden;
}
.res-dropdown-menu .dropdown-item {
  font-size: .84rem; padding: .5rem 1rem;
}
.res-dropdown-menu .dropdown-item:active { background: var(--brand-dim); color: var(--brand); }
.res-dropdown-divider { border-color: #f1f5f9; margin: .25rem 0; }

/* ── Main content ── */
.res-main {
  margin-left: var(--sidebar-w);
  min-height: 100vh;
  display: flex; flex-direction: column;
}
.res-content {
  padding: 1.5rem 1.5rem calc(1.5rem + var(--bottom-tab));
  flex: 1;
}

/* ── Bottom tab bar (mobile only) ── */
.res-bottom-tabs {
  position: fixed; bottom: 0; left: 0; right: 0;
  height: var(--bottom-tab);
  background: #fff;
  border-top: 1px solid #e2e8f0;
  display: none;
  z-index: 150;
  box-shadow: 0 -2px 12px rgba(0,0,0,.08);
}
.res-tab-item {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 2px;
  text-decoration: none;
  color: #94a3b8; font-size: .62rem; font-weight: 600;
  padding: .3rem .25rem;
  transition: color .13s;
  text-align: center; line-height: 1.2;
}
.res-tab-item i { font-size: 1.3rem; display: block; }
.res-tab-item.active { color: var(--brand); }
.res-tab-item:active { color: var(--brand); }

/* ── Utility classes ── */
.btn-brand { background: var(--brand); border: none; color: #fff; font-weight: 600; }
.btn-brand:hover { filter: brightness(.9); color: #fff; }
.text-brand { color: var(--brand) !important; }

.card-box {
  background: #fff; border-radius: 14px;
  border: 1px solid #e2e8f0; padding: 1.25rem;
}
.kpi-card {
  background: #fff; border-radius: 14px;
  border: 1px solid #e2e8f0; padding: 1.25rem 1.4rem;
}
.kpi-val { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.kpi-lbl { font-size: .7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; }
.kpi-sub { font-size: .76rem; color: #64748b; margin-top: .25rem; }

.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.page-sub   { color: #64748b; font-size: .82rem; margin: 0; }

.s-badge { display: inline-block; padding: .2rem .55rem; border-radius: 20px; font-size: .7rem; font-weight: 700; }
.badge-paid     { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef9c3; color: #a16207; }
.badge-overdue  { background: #fee2e2; color: #b91c1c; }
.badge-open     { background: #dbeafe; color: #1d4ed8; }
.badge-resolved { background: #dcfce7; color: #15803d; }

.tbl { font-size: .83rem; }
.tbl th { font-size: .69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; border-bottom: 2px solid #f1f5f9; padding: .5rem .75rem; }
.tbl td { padding: .6rem .75rem; vertical-align: middle; border-bottom: 1px solid #f8fafc; color: #334155; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: #f8fafc; }

/* ── Responsive ── */
@media (max-width: 767.98px) {
  .res-sidebar {
    transform: translateX(-100%);
    box-shadow: 4px 0 20px rgba(0,0,0,.2);
  }
  .res-sidebar.open { transform: translateX(0); }
  .res-sidebar-overlay.open { display: block; }
  .res-main { margin-left: 0; }
  .btn-hamburger { display: flex; }
  .res-bottom-tabs { display: flex; }
  .res-content { padding-bottom: calc(var(--bottom-tab) + 1rem); }
  .topbar-company { max-width: 120px; }
}
</style>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="res-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="res-sidebar" id="resSidebar" aria-label="Resident navigation">

  <!-- Resident avatar / name card -->
  <div class="sb-res-card">
    <div class="sb-res-avatar"><?= e($residentInitial) ?></div>
    <div class="sb-res-name"><?= e($residentName) ?></div>
    <div class="sb-res-label">Resident Portal</div>
  </div>

  <!-- Nav items -->
  <div class="sb-nav">
    <?php foreach ($nav as $item):
      $isActive = ($activePage ?? '') === $item['key'];
    ?>
    <a href="<?= e(APP_URL . '/portal/resident/' . $item['href']) ?>"
       class="sb-link <?= $isActive ? 'active' : '' ?>">
      <i class="bi <?= e($item['icon']) ?>"></i>
      <?= e($item['label']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Logout -->
  <div class="sb-footer">
    <form action="<?= e(APP_URL) ?>/portal/resident/logout.php" method="POST">
      <?= csrfField() ?>
      <button type="submit" class="btn w-100"
              style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.8rem;border-radius:8px;">
        <i class="bi bi-box-arrow-left me-1"></i>Sign Out
      </button>
    </form>
  </div>

</nav>

<!-- Main wrapper -->
<div class="res-main">

  <!-- Top header -->
  <header class="res-topbar">
    <div class="res-topbar-left">
      <button class="btn-hamburger" id="hamburgerBtn" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
      <?php if (!empty($company['logo_url'])): ?>
        <img src="<?= e($company['logo_url']) ?>" alt="<?= e($company['name'] ?? '') ?>" class="topbar-logo">
      <?php else: ?>
        <span class="topbar-company"><?= e($company['name'] ?? APP_NAME) ?></span>
      <?php endif; ?>
    </div>

    <!-- Resident dropdown -->
    <div class="dropdown">
      <button class="res-user-btn dropdown-toggle"
              id="resUserDrop" data-bs-toggle="dropdown"
              aria-expanded="false" type="button">
        <div class="res-user-avatar"><?= e($residentInitial) ?></div>
        <span class="d-none d-sm-inline"><?= e($residentName) ?></span>
        <i class="bi bi-chevron-down" style="font-size:.65rem;opacity:.7;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end res-dropdown-menu" aria-labelledby="resUserDrop">
        <li>
          <a class="dropdown-item" href="<?= e(APP_URL) ?>/portal/resident/profile.php">
            <i class="bi bi-person-circle me-2 text-muted"></i>My Profile
          </a>
        </li>
        <li><hr class="dropdown-divider res-dropdown-divider"></li>
        <li>
          <form action="<?= e(APP_URL) ?>/portal/resident/logout.php" method="POST">
            <?= csrfField() ?>
            <button type="submit" class="dropdown-item text-danger">
              <i class="bi bi-box-arrow-left me-2"></i>Sign Out
            </button>
          </form>
        </li>
      </ul>
    </div>
  </header>

  <!-- Page content -->
  <div class="res-content">
    <?= flashHtml() ?>
<?php
// NOTE: layout_end.php closes .res-content, .res-main, bottom tabs, scripts, body, html
?>
