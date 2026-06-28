<?php
// CoLive OS — Owner portal shared layout
// Usage: set $pageTitle and $activePage before including this file.
// Requires: $db = getDB() and requireOwnerLogin() already called.
// Close with: include __DIR__ . '/layout_end.php';

$stmt = $db->prepare('SELECT * FROM companies WHERE id=?');
$stmt->execute([(int)($_SESSION['owner_company_id'] ?? 0)]);
$company = $stmt->fetch() ?: [];
$brandClr = brandColor($company);

$stmt = $db->prepare('SELECT * FROM owners WHERE id=?');
$stmt->execute([(int)($_SESSION['owner_id'] ?? 0)]);
$ownerRow = $stmt->fetch() ?: [];

$ownerName  = $ownerRow['name']  ?? ($_SESSION['owner_name']  ?? 'Owner');
$ownerEmail = $ownerRow['email'] ?? ($_SESSION['owner_email'] ?? '');

$nav = [
    ['href' => 'dashboard.php', 'icon' => 'bi-house-fill',  'label' => 'Dashboard', 'key' => 'dashboard'],
    ['href' => 'payouts.php',   'icon' => 'bi-wallet2',      'label' => 'Payouts',   'key' => 'payouts'],
    ['href' => 'units.php',     'icon' => 'bi-building',     'label' => 'My Units',  'key' => 'units'],
];

$avatarLetter = strtoupper(substr($ownerName, 0, 1) ?: 'O');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Owner Portal') ?> &mdash; <?= e($company['name'] ?? APP_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --brand:        <?= e($brandClr) ?>;
  --brand-dim:    <?= e($brandClr) ?>22;
  --brand-soft:   <?= e($brandClr) ?>18;
  --sidebar-w:    260px;
  --topbar-h:     56px;
  --bottomtab-h:  60px;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  background: #f8f7ff;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  margin: 0;
  padding-bottom: var(--bottomtab-h); /* space for bottom tab on mobile */
}

/* ── Sidebar ──────────────────────────────────────────────── */
.owner-sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--brand);
  display: flex;
  flex-direction: column;
  z-index: 200;
  box-shadow: 4px 0 20px rgba(0,0,0,.12);
}

.sb-header {
  padding: 1.4rem 1.25rem 1rem;
  border-bottom: 1px solid rgba(255,255,255,.18);
  flex-shrink: 0;
}
.sb-app-name {
  font-weight: 800;
  font-size: .78rem;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(255,255,255,.6);
  margin-bottom: .25rem;
}
.sb-company-name {
  font-weight: 700;
  font-size: 1.05rem;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sb-owner-card {
  margin: 1rem .85rem .5rem;
  background: rgba(255,255,255,.14);
  border-radius: 12px;
  padding: .8rem 1rem;
  flex-shrink: 0;
}
.sb-owner-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(255,255,255,.25);
  color: #fff;
  font-size: .9rem;
  font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.35);
}
.sb-owner-name {
  font-weight: 700;
  font-size: .85rem;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sb-owner-email {
  font-size: .7rem;
  color: rgba(255,255,255,.65);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sb-nav {
  flex: 1;
  overflow-y: auto;
  padding: .75rem 0 1rem;
}
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 2px; }

.sb-link {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .55rem 1rem;
  margin: .1rem .75rem;
  border-radius: 10px;
  font-size: .88rem;
  font-weight: 500;
  color: rgba(255,255,255,.75);
  text-decoration: none;
  transition: background .13s, color .13s;
}
.sb-link i { font-size: 1.05rem; width: 1.2rem; flex-shrink: 0; }
.sb-link:hover {
  background: rgba(255,255,255,.15);
  color: #fff;
}
.sb-link.active {
  background: rgba(255,255,255,.22);
  color: #fff;
  font-weight: 700;
}
.sb-link.active i { color: #fff; }

.sb-footer {
  border-top: 1px solid rgba(255,255,255,.18);
  padding: 1rem 1.1rem;
  flex-shrink: 0;
}
.btn-sb-logout {
  display: flex; align-items: center; justify-content: center; gap: .45rem;
  width: 100%;
  padding: .45rem .75rem;
  border-radius: 8px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  color: rgba(255,255,255,.8);
  font-size: .8rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background .13s;
}
.btn-sb-logout:hover { background: rgba(255,255,255,.2); color: #fff; }

/* ── Main wrap ────────────────────────────────────────────── */
.main-wrap {
  margin-left: var(--sidebar-w);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* ── Topbar ───────────────────────────────────────────────── */
.topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  height: var(--topbar-h);
  background: #fff;
  border-bottom: 1px solid #e8e3f8;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.topbar-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a1035;
}

/* hamburger btn shown on mobile */
.btn-hamburger {
  background: none;
  border: none;
  padding: .25rem .5rem;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
  display: none;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.topbar-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--brand);
  color: #fff;
  font-size: .8rem;
  font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}

/* ── Page content ─────────────────────────────────────────── */
.page-content {
  padding: 1.5rem;
  flex: 1;
}

/* ── Card helpers ─────────────────────────────────────────── */
.card-box {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #ede9fb;
  padding: 1.25rem;
}
.kpi-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #ede9fb;
  padding: 1.25rem 1.4rem;
}
.kpi-val  { font-size: 1.75rem; font-weight: 800; color: #1a1035; line-height: 1.1; }
.kpi-lbl  { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; }
.kpi-ico  { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--brand-soft); color: var(--brand); }
.kpi-sub  { font-size: .75rem; color: #64748b; margin-top: .3rem; }

/* ── Table ────────────────────────────────────────────────── */
.tbl { font-size: .84rem; }
.tbl th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; border-bottom: 2px solid #f5f3ff; padding: .6rem .75rem; }
.tbl td { padding: .65rem .75rem; vertical-align: middle; border-bottom: 1px solid #faf8ff; color: #334155; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: #faf8ff; }

/* ── Status badges ────────────────────────────────────────── */
.s-badge { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .7rem; font-weight: 700; }
.badge-paid     { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef9c3; color: #a16207; }
.badge-overdue  { background: #fee2e2; color: #b91c1c; }
.badge-active   { background: #ede9fb; color: var(--brand); }
.badge-inactive { background: #f1f5f9; color: #64748b; }

/* ── Utilities ────────────────────────────────────────────── */
.btn-brand { background: var(--brand); border: none; color: #fff; font-weight: 600; }
.btn-brand:hover { opacity: .9; color: #fff; }
.text-brand { color: var(--brand) !important; }

/* ── Mobile offcanvas sidebar ─────────────────────────────── */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 190;
}
.sidebar-overlay.show { display: block; }

/* ── Bottom tab nav (mobile only) ────────────────────────── */
.bottom-tabs {
  display: none;
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: var(--bottomtab-h);
  background: #fff;
  border-top: 1px solid #e8e3f8;
  z-index: 150;
  justify-content: space-around;
  align-items: center;
}
.btab {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  text-decoration: none;
  color: #94a3b8;
  font-size: .62rem;
  font-weight: 600;
  flex: 1;
  padding: .5rem 0;
  transition: color .12s;
}
.btab i { font-size: 1.3rem; }
.btab.active { color: var(--brand); }
.btab:hover  { color: var(--brand); }

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 767.98px) {
  body { padding-bottom: var(--bottomtab-h); }

  .owner-sidebar {
    transform: translateX(-100%);
    transition: transform .23s cubic-bezier(.4,0,.2,1);
  }
  .owner-sidebar.open {
    transform: translateX(0);
  }

  .main-wrap { margin-left: 0; }

  .btn-hamburger { display: inline-flex; align-items: center; }

  .topbar-right .dropdown-owner-name { display: none; }

  .bottom-tabs { display: flex; }
}
</style>
</head>
<body>

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── Sidebar ── -->
<nav class="owner-sidebar" id="ownerSidebar">

  <div class="sb-header">
    <div class="sb-app-name">Owner Portal</div>
    <div class="sb-company-name"><?= e($company['name'] ?? APP_NAME) ?></div>
  </div>

  <div class="sb-owner-card">
    <div class="d-flex align-items-center gap-2">
      <div class="sb-owner-avatar"><?= e($avatarLetter) ?></div>
      <div style="overflow:hidden;flex:1;">
        <div class="sb-owner-name"><?= e($ownerName) ?></div>
        <div class="sb-owner-email"><?= e($ownerEmail) ?></div>
      </div>
    </div>
  </div>

  <div class="sb-nav">
    <?php foreach ($nav as $item):
      $isActive = ($activePage ?? '') === $item['key'];
    ?>
    <a href="<?= e(APP_URL . '/portal/owner/' . $item['href']) ?>"
       class="sb-link <?= $isActive ? 'active' : '' ?>">
      <i class="bi <?= e($item['icon']) ?>"></i>
      <?= e($item['label']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sb-footer">
    <form action="<?= e(APP_URL) ?>/portal/owner/logout.php" method="POST">
      <?= csrfField() ?>
      <button type="submit" class="btn-sb-logout">
        <i class="bi bi-box-arrow-left"></i>Sign Out
      </button>
    </form>
  </div>
</nav>

<!-- ── Main wrap ── -->
<div class="main-wrap">

  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn-hamburger" id="hamburgerBtn" onclick="openSidebar()" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
      <span class="topbar-title"><?= e($company['name'] ?? APP_NAME) ?></span>
    </div>

    <div class="topbar-right">
      <div class="dropdown">
        <button class="d-flex align-items-center gap-2 btn p-0 border-0 bg-transparent"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="topbar-avatar"><?= e($avatarLetter) ?></div>
          <span class="dropdown-owner-name text-dark fw-semibold" style="font-size:.85rem;">
            <?= e($ownerName) ?>
          </span>
          <i class="bi bi-chevron-down text-muted" style="font-size:.7rem;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width:200px;border-radius:12px;">
          <li>
            <div class="px-3 py-2" style="border-bottom:1px solid #f1f5f9;">
              <div style="font-size:.8rem;font-weight:700;color:#1a1035;"><?= e($ownerName) ?></div>
              <div style="font-size:.72rem;color:#94a3b8;"><?= e($ownerEmail) ?></div>
            </div>
          </li>
          <li>
            <form action="<?= e(APP_URL) ?>/portal/owner/logout.php" method="POST" class="m-0">
              <?= csrfField() ?>
              <button type="submit" class="dropdown-item text-danger py-2" style="font-size:.85rem;">
                <i class="bi bi-box-arrow-left me-2"></i>Sign Out
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Page content -->
  <div class="page-content">
    <?= flashHtml() ?>
