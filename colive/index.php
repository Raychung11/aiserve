<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$db = getDB();

// Featured rooms: up to 6 vacant rooms, pick variety of types
$featStmt = $db->prepare("
    SELECT r.*, u.unit_no, b.name AS building_name, b.city,
           c.brand_color
    FROM rooms r
    JOIN units u ON u.id = r.unit_id
    JOIN buildings b ON b.id = u.building_id
    JOIN companies c ON c.id = r.company_id AND c.status IN ('trial','active')
    LEFT JOIN tenancies t ON t.room_id = r.id AND t.status = 'active'
    WHERE r.is_active = 1 AND t.id IS NULL
    ORDER BY r.base_rent ASC
    LIMIT 6
");
$featStmt->execute();
$featRooms = $featStmt->fetchAll();

$typeLabels = [
    'single'=>'Single','twin'=>'Twin','master'=>'Master',
    'studio'=>'Studio','common'=>'Common Room',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CoLive OS &mdash; Smart Co-Living Management Platform</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --brand:#7c3aed; --brand-dark:#5b21b6; --brand-light:#ede9fe; }
* { box-sizing:border-box; }
body { font-family:'Segoe UI',system-ui,sans-serif; color:#0f172a; background:#fff; margin:0; }

/* NAV */
.top-nav { position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid #f1f5f9;padding:.9rem 0; }
.nav-inner { max-width:1140px;margin:auto;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between; }
.nav-logo { display:flex;align-items:center;gap:.5rem;font-weight:800;font-size:1.1rem;color:#0f172a;text-decoration:none; }
.nav-logo i { color:var(--brand);font-size:1.3rem; }
.nav-cta { background:var(--brand);color:#fff;border:none;border-radius:8px;padding:.45rem 1.1rem;font-size:.85rem;font-weight:600;text-decoration:none; }
.nav-cta:hover { background:var(--brand-dark);color:#fff; }

/* HERO */
.hero { background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#2e1065 100%);padding:10rem 1.5rem 6rem;text-align:center;position:relative;overflow:hidden; }
.hero::before { content:'';position:absolute;top:-40%;left:50%;transform:translateX(-50%);width:800px;height:800px;background:radial-gradient(circle,rgba(124,58,237,.25) 0%,transparent 70%);pointer-events:none; }
.hero-badge { display:inline-flex;align-items:center;gap:.4rem;background:rgba(124,58,237,.2);border:1px solid rgba(124,58,237,.4);color:#c4b5fd;border-radius:20px;padding:.3rem .9rem;font-size:.78rem;font-weight:600;margin-bottom:1.5rem; }
.hero h1 { font-size:clamp(2rem,5vw,3.5rem);font-weight:800;color:#fff;line-height:1.15;margin-bottom:1.25rem; }
.hero h1 span { color:#a78bfa; }
.hero p { color:#94a3b8;font-size:1.05rem;max-width:560px;margin:0 auto 2.25rem; }
.hero-btns { display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap; }
.btn-hero-primary { background:var(--brand);color:#fff;border:none;border-radius:10px;padding:.75rem 2rem;font-weight:700;font-size:.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem; }
.btn-hero-primary:hover { background:var(--brand-dark);color:#fff; }
.btn-hero-outline { background:transparent;color:#c4b5fd;border:1px solid rgba(124,58,237,.4);border-radius:10px;padding:.75rem 2rem;font-weight:600;font-size:.95rem;text-decoration:none; }
.btn-hero-outline:hover { background:rgba(124,58,237,.15);color:#e9d5ff; }

/* STATS BAR */
.stats-bar { background:#fff;border-bottom:1px solid #f1f5f9;padding:1.75rem 1.5rem; }
.stats-inner { max-width:900px;margin:auto;display:flex;justify-content:space-around;flex-wrap:wrap;gap:1rem; }
.stat-item { text-align:center; }
.stat-num { font-size:1.8rem;font-weight:800;color:var(--brand); }
.stat-lbl { font-size:.75rem;color:#94a3b8;font-weight:500;margin-top:.1rem; }

/* SECTION */
.section { padding:5rem 1.5rem; }
.section-center { max-width:1100px;margin:auto; }
.section-tag { display:inline-block;background:var(--brand-light);color:var(--brand);border-radius:20px;padding:.25rem .85rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem; }
.section-title { font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;margin-bottom:.5rem; }
.section-sub { color:#64748b;font-size:.95rem;max-width:500px; }

/* ROOM LISTING CARDS */
.rooms-section { background:#f8fafc;padding:5rem 1.5rem; }
.room-card { background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;
             transition:box-shadow .2s,transform .2s;height:100%;display:flex;flex-direction:column; }
.room-card:hover { box-shadow:0 8px 32px rgba(124,58,237,.13);transform:translateY(-4px); }
.room-card-img { height:150px;display:flex;align-items:center;justify-content:center;position:relative; }
.room-type-pill { position:absolute;top:.65rem;left:.65rem;color:#fff;font-size:.68rem;font-weight:700;
                  padding:.2rem .6rem;border-radius:20px;text-transform:capitalize; }
.room-card-body { padding:1.1rem;display:flex;flex-direction:column;flex:1; }
.room-price { font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1; }
.room-price span { font-size:.75rem;font-weight:400;color:#94a3b8; }
.room-loc { font-size:.75rem;color:#64748b;margin:.3rem 0 .65rem; }
.room-chips { display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:auto; }
.room-chip { background:#f1f5f9;color:#475569;font-size:.68rem;padding:.18rem .5rem;border-radius:20px; }
.room-chip.green { background:#f0fdf4;color:#166534; }
.room-chip.purple { background:#faf5ff;color:#6b21a8; }
.room-inquire { margin-top:1rem;display:block;text-align:center;border-radius:8px;padding:.5rem;
                font-size:.83rem;font-weight:700;text-decoration:none;transition:background .15s; }
@media(max-width:600px){ .rooms-section{padding:3.5rem 1.25rem;} }

/* FEATURE GRID */
.feat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;margin-top:3rem; }
.feat-card { background:#fafafa;border:1px solid #f1f5f9;border-radius:16px;padding:1.75rem;transition:box-shadow .2s,border-color .2s; }
.feat-card:hover { box-shadow:0 8px 32px rgba(124,58,237,.1);border-color:#ddd6fe; }
.feat-icon { width:48px;height:48px;background:var(--brand-light);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem; }
.feat-icon i { color:var(--brand);font-size:1.25rem; }
.feat-card h5 { font-weight:700;font-size:1rem;margin-bottom:.4rem; }
.feat-card p { color:#64748b;font-size:.85rem;margin:0;line-height:1.6; }

/* PORTAL CARDS */
.portal-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-top:2.5rem; }
.portal-card { border-radius:16px;padding:1.75rem;text-decoration:none;display:block;transition:transform .2s,box-shadow .2s;border:2px solid transparent; }
.portal-card:hover { transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.12); }
.portal-card .pc-icon { font-size:2rem;margin-bottom:.75rem; }
.portal-card h5 { font-weight:700;font-size:.95rem;margin-bottom:.25rem; }
.portal-card p { font-size:.78rem;margin:0;opacity:.75; }
.portal-card .pc-link { display:inline-flex;align-items:center;gap:.3rem;font-size:.78rem;font-weight:600;margin-top:.75rem; }

.pc-admin  { background:#0f172a;color:#fff; }
.pc-platform { background:#1e1b4b;color:#fff; }
.pc-resident { background:var(--brand-light);color:#1e293b;border-color:#ddd6fe; }
.pc-owner   { background:#ecfdf5;color:#1e293b;border-color:#a7f3d0; }
.pc-resident .pc-link { color:var(--brand); }
.pc-owner   .pc-link { color:#059669; }
.pc-admin   .pc-link, .pc-platform .pc-link { color:#a78bfa; }

/* HOW IT WORKS */
.step-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;margin-top:2.5rem;text-align:center; }
.step-num { width:44px;height:44px;background:var(--brand);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;margin:0 auto .75rem; }
.step-grid h6 { font-weight:700;font-size:.9rem;margin-bottom:.25rem; }
.step-grid p { color:#64748b;font-size:.82rem;margin:0; }

/* CTA SECTION */
.cta-section { background:linear-gradient(135deg,var(--brand) 0%,#5b21b6 100%);padding:5rem 1.5rem;text-align:center; }
.cta-section h2 { color:#fff;font-size:clamp(1.5rem,3vw,2.25rem);font-weight:800;margin-bottom:.75rem; }
.cta-section p { color:#ddd6fe;font-size:.95rem;margin-bottom:2rem; }
.btn-cta-white { background:#fff;color:var(--brand);border:none;border-radius:10px;padding:.75rem 2.25rem;font-weight:700;font-size:.95rem;text-decoration:none;display:inline-block; }
.btn-cta-white:hover { background:#f5f3ff;color:var(--brand-dark); }

/* FOOTER */
footer { background:#0f172a;color:#94a3b8;padding:3.5rem 1.5rem 2rem; }
.footer-inner { max-width:1100px;margin:auto; }
.footer-logo { display:flex;align-items:center;gap:.5rem;font-weight:800;font-size:1.1rem;color:#fff;margin-bottom:.5rem; }
.footer-logo i { color:var(--brand); }
.footer-tagline { font-size:.82rem;color:#475569;margin-bottom:2rem; }
.footer-grid { display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:2rem;padding-bottom:2rem;border-bottom:1px solid #1e293b; }
@media(max-width:768px){ .footer-grid{grid-template-columns:1fr 1fr;} }
@media(max-width:480px){
  .footer-grid{grid-template-columns:1fr;}
  .room-card-body { padding:.6rem; }
  .room-price { font-size:1rem; }
  .room-loc { font-size:.65rem; }
  .room-chips { gap:.25rem; }
  .room-chip { font-size:.62rem; padding:.2rem .4rem; }
  .room-inquire { font-size:.75rem; padding:.45rem; }
  .room-type-pill { font-size:.62rem; padding:.2rem .5rem; }
}
.footer-col h6 { color:#e2e8f0;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:1rem; }
.footer-col a { display:block;color:#64748b;font-size:.83rem;text-decoration:none;margin-bottom:.5rem; }
.footer-col a:hover { color:#a78bfa; }
.footer-login-link { display:flex;align-items:center;gap:.5rem;color:#94a3b8;font-size:.83rem;text-decoration:none;margin-bottom:.65rem; }
.footer-login-link i { width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0; }
.footer-login-link:hover { color:#a78bfa; }
.footer-bottom { display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;padding-top:1.5rem;font-size:.78rem; }

/* AUDIENCE SPLIT */
.split-strip { background:#fff;border-bottom:1px solid #f1f5f9;padding:2.25rem 1.5rem; }
.split-inner { max-width:860px;margin:auto;display:grid;grid-template-columns:1fr 1fr;gap:1.25rem; }
.split-card { border-radius:14px;padding:1.5rem 1.75rem;display:flex;align-items:center;gap:1.1rem; }
.split-card-tenant { background:linear-gradient(135deg,#7c3aed 0%,#5b21b6 100%);color:#fff; }
.split-card-operator { background:#f8fafc;border:1.5px solid #e2e8f0;color:#0f172a; }
.split-icon { font-size:2rem;flex-shrink:0; }
.split-card h6 { font-weight:700;font-size:.95rem;margin-bottom:.2rem; }
.split-card p { font-size:.78rem;margin:0;opacity:.75;line-height:1.4; }
.split-btn-white { display:inline-flex;align-items:center;gap:.4rem;margin-top:.85rem;background:#fff;
                   color:#7c3aed;border-radius:8px;padding:.42rem 1.1rem;font-size:.82rem;font-weight:700;
                   text-decoration:none;transition:background .15s; }
.split-btn-white:hover { background:#f5f3ff;color:#5b21b6; }
.split-btn-brand { display:inline-flex;align-items:center;gap:.4rem;margin-top:.85rem;background:var(--brand);
                   color:#fff;border-radius:8px;padding:.42rem 1.1rem;font-size:.82rem;font-weight:700;
                   text-decoration:none;transition:background .15s; }
.split-btn-brand:hover { background:var(--brand-dark);color:#fff; }
@media(max-width:640px){
  .split-inner { grid-template-columns:1fr; }
  .split-card  { flex-direction:column;align-items:flex-start;gap:.6rem; }
}

/* STICKY MOBILE RENT CTA */
.sticky-rent-btn { display:none; }
@media(max-width:768px){
  .sticky-rent-btn {
    display:flex;align-items:center;justify-content:center;gap:.5rem;
    position:fixed;bottom:1rem;left:1rem;right:1rem;z-index:200;
    background:var(--brand);color:#fff;border-radius:12px;padding:.85rem;
    font-size:.95rem;font-weight:700;text-decoration:none;
    box-shadow:0 8px 24px rgba(124,58,237,.45);
  }
}

/* MOBILE NAV */
@media(max-width:600px){ .hero{padding:8rem 1.25rem 4rem;} }
</style>
</head>
<body>

<!-- NAV -->
<nav class="top-nav">
  <div class="nav-inner">
    <a href="/" class="nav-logo">
      <i class="bi bi-house-heart-fill"></i>
      CoLive OS
    </a>
    <a href="/colive/app/login.php" class="nav-cta">
      <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">
    <i class="bi bi-stars"></i> Built for Malaysian Co-Living Operators
  </div>
  <h1>Run your co-living<br>operation <span>smarter</span>.</h1>
  <p>From rooms and residents to billing, maintenance and owner payouts &mdash; everything in one clean dashboard.</p>
  <div class="hero-btns">
    <a href="/colive/listings.php" class="btn-hero-primary">
      <i class="bi bi-house-heart-fill"></i> I Want to Rent
    </a>
    <a href="/colive/app/login.php" class="btn-hero-outline">
      <i class="bi bi-grid-fill"></i> Operator Login
    </a>
  </div>
</section>

<!-- AUDIENCE SPLIT -->
<div class="split-strip">
  <div class="split-inner">
    <div class="split-card split-card-tenant">
      <div class="split-icon"><i class="bi bi-house-heart-fill"></i></div>
      <div>
        <h6>Looking for a room?</h6>
        <p>Browse vacant rooms, check prices and inquire directly &mdash; no agent fee.</p>
        <a href="/colive/listings.php" class="split-btn-white">
          <i class="bi bi-search"></i> I Want to Rent
        </a>
      </div>
    </div>
    <div class="split-card split-card-operator">
      <div class="split-icon" style="color:var(--brand);"><i class="bi bi-speedometer2"></i></div>
      <div>
        <h6>Managing a co-living?</h6>
        <p>Sign in to your dashboard to manage rooms, residents, billing and more.</p>
        <a href="/colive/app/login.php" class="split-btn-brand">
          <i class="bi bi-box-arrow-in-right"></i> Operator Login
        </a>
      </div>
    </div>
  </div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num">27+</div>
      <div class="stat-lbl">Rooms Managed</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">12</div>
      <div class="stat-lbl">Active Residents</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">3</div>
      <div class="stat-lbl">Buildings</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">RM 0</div>
      <div class="stat-lbl">Setup Cost</div>
    </div>
  </div>
</div>

<!-- FEATURED ROOMS -->
<section class="rooms-section" id="rooms">
  <div class="section-center">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
      <div>
        <span class="section-tag">Now Available</span>
        <h2 class="section-title mb-1">Featured Rooms</h2>
        <p class="section-sub mb-0">Vacant rooms you can move into. Inquire directly &mdash; no agent needed.</p>
      </div>
      <a href="/colive/listings.php" style="white-space:nowrap;background:var(--brand);color:#fff;border-radius:10px;padding:.55rem 1.4rem;font-size:.875rem;font-weight:700;text-decoration:none;">
        View All Rooms <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <?php if ($featRooms): ?>
    <div class="row g-3">
      <?php foreach ($featRooms as $rm):
        $brand = !empty($rm['brand_color']) ? $rm['brand_color'] : '#7c3aed';
        $beds  = 0; // lightweight — not sub-queried here
      ?>
      <div class="col-6 col-lg-4">
        <div class="room-card">
          <div class="room-card-img" style="background:linear-gradient(135deg,<?= htmlspecialchars($brand) ?>18 0%,<?= htmlspecialchars($brand) ?>38 100%);">
            <i class="bi bi-door-open-fill" style="font-size:3rem;color:<?= htmlspecialchars($brand) ?>;opacity:.55;"></i>
            <span class="room-type-pill" style="background:<?= htmlspecialchars($brand) ?>;">
              <?= htmlspecialchars($typeLabels[$rm['room_type']] ?? ucfirst($rm['room_type'])) ?>
            </span>
          </div>
          <div class="room-card-body">
            <div class="room-price">
              RM <?= number_format((float)$rm['base_rent'], 0) ?>
              <span>/ month</span>
            </div>
            <div class="room-loc">
              <i class="bi bi-geo-alt me-1"></i>
              <?= htmlspecialchars($rm['building_name']) ?>
              <?php if ($rm['city']): ?>&bull; <?= htmlspecialchars($rm['city']) ?><?php endif; ?>
              &bull; Unit <?= htmlspecialchars($rm['unit_no']) ?>
            </div>
            <div class="room-chips">
              <span class="room-chip"><i class="bi bi-person me-1"></i><?= (int)$rm['capacity'] ?> pax</span>
              <?php if ($rm['has_attached_bath']): ?>
              <span class="room-chip green"><i class="bi bi-droplet me-1"></i>En-suite</span>
              <?php endif; ?>
              <?php if ((int)$rm['deposit_months'] === 0): ?>
              <span class="room-chip green"><i class="bi bi-star me-1"></i>Zero deposit</span>
              <?php else: ?>
              <span class="room-chip"><?= (int)$rm['deposit_months'] ?>-month deposit</span>
              <?php endif; ?>
            </div>
            <a href="/colive/listings.php#room-<?= $rm['id'] ?>" class="room-inquire"
               style="background:<?= htmlspecialchars($brand) ?>;color:#fff;">
              <i class="bi bi-send me-1"></i>Inquire Now
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5" style="color:#94a3b8;">
      <i class="bi bi-house-slash" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
      <p style="margin:0;font-size:.9rem;">No rooms listed yet &mdash; check back soon.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- FEATURES -->
<section class="section" style="background:#fff;">
  <div class="section-center">
    <div class="text-center mb-2">
      <span class="section-tag">Features</span>
      <h2 class="section-title">Everything you need to run a co-living</h2>
      <p class="section-sub mx-auto">Replace spreadsheets and WhatsApp groups with a proper system built for co-living operators.</p>
    </div>
    <div class="feat-grid">
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-building"></i></div>
        <h5>Inventory Management</h5>
        <p>Buildings, units, rooms and beds. Track occupancy, room types and base rates in one place.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-people-fill"></i></div>
        <h5>Resident Management</h5>
        <p>Bookings, tenancy contracts, IC numbers, emergency contacts and full tenancy history.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-receipt-cutoff"></i></div>
        <h5>Automated Billing</h5>
        <p>Generate invoices for rent and utilities, record payments, track balances and overdue accounts.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
        <h5>Utility Meter Tracking</h5>
        <p>Record electric, water and gas readings by room. Auto-calculate charges from configured rates.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-tools"></i></div>
        <h5>Maintenance Tickets</h5>
        <p>Residents submit tickets via portal. Staff track priority, category and resolution status.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-wallet2"></i></div>
        <h5>Owner Payouts</h5>
        <p>Calculate gross rent, deductions and net payouts per unit per month. Owners view via portal.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-lock-fill"></i></div>
        <h5>Smart Lock Ready</h5>
        <p>Register TTLock, Igloohome or Yale devices. View access logs and trigger remote unlock.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-chat-dots-fill"></i></div>
        <h5>Support Chat</h5>
        <p>Residents message staff directly via portal. Threaded conversations with escalation flags.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon"><i class="bi bi-bar-chart-fill"></i></div>
        <h5>Reports Dashboard</h5>
        <p>Occupancy rates, revenue charts, expiring leases and overdue invoices at a glance.</p>
      </div>
    </div>
  </div>
</section>

<!-- PORTALS -->
<section class="section" id="portals" style="background:#f8fafc;">
  <div class="section-center">
    <div class="text-center mb-2">
      <span class="section-tag">Access Portals</span>
      <h2 class="section-title">One system, four portals</h2>
      <p class="section-sub mx-auto">Every stakeholder gets their own view &mdash; operators, platform admins, residents and owners.</p>
    </div>
    <div class="portal-grid">
      <a href="/colive/app/login.php" class="portal-card pc-admin">
        <div class="pc-icon"><i class="bi bi-speedometer2"></i></div>
        <h5>Operator Admin</h5>
        <p>Full dashboard for property managers and staff. Manage rooms, residents, billing and maintenance.</p>
        <div class="pc-link"><i class="bi bi-box-arrow-in-right"></i> Sign in as operator</div>
      </a>
      <a href="/colive/platform/login.php" class="portal-card pc-platform">
        <div class="pc-icon"><i class="bi bi-shield-fill-check"></i></div>
        <h5>Platform Admin</h5>
        <p>SLV super-admin panel. Manage companies, plans, subscriptions and platform-wide settings.</p>
        <div class="pc-link"><i class="bi bi-box-arrow-in-right"></i> Platform login</div>
      </a>
      <a href="/colive/portal/resident/login.php" class="portal-card pc-resident">
        <div class="pc-icon"><i class="bi bi-person-fill"></i></div>
        <h5>Resident Portal</h5>
        <p>Mobile-friendly. View invoices, submit maintenance tickets and chat with property staff.</p>
        <div class="pc-link"><i class="bi bi-box-arrow-in-right"></i> Resident login</div>
      </a>
      <a href="/colive/portal/owner/login.php" class="portal-card pc-owner">
        <div class="pc-icon"><i class="bi bi-person-vcard-fill"></i></div>
        <h5>Owner Portal</h5>
        <p>Property owners view unit occupancy, payout history and bank account details.</p>
        <div class="pc-link"><i class="bi bi-box-arrow-in-right"></i> Owner login</div>
      </a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" style="background:#fff;">
  <div class="section-center">
    <div class="text-center mb-2">
      <span class="section-tag">How it works</span>
      <h2 class="section-title">Up and running in minutes</h2>
    </div>
    <div class="step-grid">
      <div>
        <div class="step-num">1</div>
        <h6>Add your buildings</h6>
        <p>Enter your buildings, units and rooms. Set base rents and room types.</p>
      </div>
      <div>
        <div class="step-num">2</div>
        <h6>Register residents</h6>
        <p>Add residents and create tenancy contracts. Enable portal access with a password.</p>
      </div>
      <div>
        <div class="step-num">3</div>
        <h6>Generate invoices</h6>
        <p>Create rent invoices with line items. Record payments and track balances.</p>
      </div>
      <div>
        <div class="step-num">4</div>
        <h6>Run reports</h6>
        <p>Monitor occupancy, revenue trends and overdue accounts from the dashboard.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Ready to manage smarter?</h2>
  <p>Sign in to your operator account and start managing your co-living today.</p>
  <a href="/colive/app/login.php" class="btn-cta-white">
    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Operator Dashboard
  </a>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-logo">
      <i class="bi bi-house-heart-fill"></i> CoLive OS
    </div>
    <div class="footer-tagline">Smart co-living management platform by SLV Group Sdn. Bhd.</div>

    <div class="footer-grid">
      <div class="footer-col">
        <h6>About</h6>
        <p style="font-size:.82rem;line-height:1.7;color:#475569;margin:0;">
          CoLive OS is a complete property management system built for Malaysian co-living operators.
          Manage rooms, residents, billing, maintenance, owner payouts and smart locks from one dashboard.
        </p>
      </div>

      <div class="footer-col">
        <h6>Platform</h6>
        <a href="/colive/app/dashboard.php">Dashboard</a>
        <a href="/colive/app/buildings.php">Buildings</a>
        <a href="/colive/app/residents.php">Residents</a>
        <a href="/colive/app/invoices.php">Invoices</a>
        <a href="/colive/app/reports.php">Reports</a>
      </div>

      <div class="footer-col">
        <h6>Portals</h6>
        <a href="/colive/portal/resident/login.php">Resident Portal</a>
        <a href="/colive/portal/owner/login.php">Owner Portal</a>
      </div>

      <div class="footer-col">
        <h6>Admin Login</h6>
        <a href="/colive/app/login.php" class="footer-login-link">
          <i class="bi bi-grid-fill" style="background:rgba(124,58,237,.2);color:#a78bfa;"></i>
          Operator Admin
        </a>
        <a href="/colive/platform/login.php" class="footer-login-link">
          <i class="bi bi-shield-fill" style="background:rgba(30,27,75,.5);color:#818cf8;"></i>
          Platform Admin
        </a>
        <a href="/colive/portal/resident/login.php" class="footer-login-link">
          <i class="bi bi-person-fill" style="background:#ede9fe;color:#7c3aed;"></i>
          Resident Login
        </a>
        <a href="/colive/portal/owner/login.php" class="footer-login-link">
          <i class="bi bi-person-vcard-fill" style="background:#d1fae5;color:#059669;"></i>
          Owner Login
        </a>
      </div>
    </div>

    <div class="footer-bottom">
      <div>&copy; <?= date('Y') ?> SLV Group Sdn. Bhd. All rights reserved.</div>
      <div style="color:#334155;">CoLive OS v1.0</div>
    </div>
  </div>
</footer>

<a href="/colive/listings.php" class="sticky-rent-btn">
  <i class="bi bi-house-heart-fill"></i> I Want to Rent
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
