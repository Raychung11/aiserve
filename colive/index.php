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
@media(max-width:480px){ .footer-grid{grid-template-columns:1fr;} }
.footer-col h6 { color:#e2e8f0;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:1rem; }
.footer-col a { display:block;color:#64748b;font-size:.83rem;text-decoration:none;margin-bottom:.5rem; }
.footer-col a:hover { color:#a78bfa; }
.footer-login-link { display:flex;align-items:center;gap:.5rem;color:#94a3b8;font-size:.83rem;text-decoration:none;margin-bottom:.65rem; }
.footer-login-link i { width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0; }
.footer-login-link:hover { color:#a78bfa; }
.footer-bottom { display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;padding-top:1.5rem;font-size:.78rem; }

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
      <i class="bi bi-search"></i> Browse Available Rooms
    </a>
    <a href="/colive/app/login.php" class="btn-hero-outline">Operator Login</a>
  </div>
</section>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
