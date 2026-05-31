<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::startSession();
$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html lang="en-MY">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Primary SEO -->
  <title>ESG gen | Free ESG Reporting Platform for Malaysian SMEs</title>
  <meta name="description" content="Malaysia's ESG reporting platform for SMEs. Start free with 15 mandatory Bursa SEDG indicators. Full data collection OS at RM 1,500/yr. Expert ESG consultation available.">
  <meta name="keywords" content="ESG reporting Malaysia, Bursa SEDG, SME ESG platform, ESG compliance Malaysia, Bursa SEDG indicators, Malaysia ESG software, sustainability reporting Malaysia, ESG report generator">
  <meta name="author" content="ESG gen">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= APP_URL ?>/">

  <!-- Open Graph / Facebook -->
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= APP_URL ?>/">
  <meta property="og:site_name"   content="ESG gen">
  <meta property="og:locale"      content="en_MY">
  <meta property="og:title"       content="ESG gen | Free ESG Reporting Platform for Malaysian SMEs">
  <meta property="og:description" content="Start free with 15 mandatory Bursa SEDG indicators. Full ESG data collection OS at RM 1,500/yr. Professional ESG consultation at RM 8,000/report.">
  <meta property="og:image"       content="<?= APP_URL ?>/assets/img/og-esggen.png">

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="ESG gen | Free ESG Reporting for Malaysian SMEs">
  <meta name="twitter:description" content="Start free with 15 mandatory Bursa SEDG indicators. RM 1,500/yr for full ESG OS. RM 8,000/report for expert consultation.">
  <meta name="twitter:image"       content="<?= APP_URL ?>/assets/img/og-esggen.png">

  <!-- Favicon -->
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">

  <!-- Schema.org structured data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "ESG gen",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "url": "<?= APP_URL ?>",
    "description": "Malaysia's ESG reporting platform for SMEs. Covers Bursa SEDG, GRI, ISSB, CDP, ESRS and more.",
    "offers": [
      { "@type": "Offer", "name": "Free", "price": "0", "priceCurrency": "MYR" },
      { "@type": "Offer", "name": "Platform", "price": "1500", "priceCurrency": "MYR" }
    ],
    "provider": { "@type": "Organization", "name": "Adcellent Biz Sdn Bhd" }
  }
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* ── Reset & base ── */
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #f8fafc; color: #1e293b; }
a { text-decoration: none; }

/* ── NAV ── */
.lp-nav {
  position: sticky; top: 0; z-index: 100;
  background: rgba(15,23,42,0.96); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 32px; height: 66px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  transition: background .3s;
}
.lp-nav.scrolled { background: rgba(15,23,42,1); }
.nav-brand { display: flex; align-items: center; gap: 10px; }
.nav-logo  { background: #16a34a; color: #fff; width: 32px; height: 32px; border-radius: 8px;
             display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.nav-name  { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -.3px; }
.nav-name span { color: #4ade80; }
.nav-logo-img { height: 50px; width: auto; display: block; }
.nav-links { display: flex; gap: 28px; }
.nav-links a { color: rgba(255,255,255,.7); font-size: 13px; font-weight: 500; transition: color .15s; }
.nav-links a:hover { color: #fff; }
.nav-actions { display: flex; gap: 10px; align-items: center; }
.btn-nav-login { padding: 7px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,.25);
                 color: #fff; font-size: 13px; font-weight: 600; background: transparent; transition: background .15s; }
.btn-nav-login:hover { background: rgba(255,255,255,.1); color: #fff; }
.btn-nav-cta   { padding: 7px 18px; border-radius: 8px; background: #16a34a; color: #fff;
                 font-size: 13px; font-weight: 700; transition: background .15s; }
.btn-nav-cta:hover { background: #15803d; color: #fff; }

/* ── HERO ── */
.hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f2d1a 100%);
  padding: 100px 20px 80px; text-align: center; position: relative; overflow: hidden;
}
.hero::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(22,163,74,.18) 0%, transparent 70%);
}
.hero-eyebrow {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(22,163,74,.15); border: 1px solid rgba(74,222,128,.3);
  color: #4ade80; padding: 5px 16px; border-radius: 20px;
  font-size: 12px; font-weight: 700; letter-spacing: .04em;
  margin-bottom: 24px; position: relative;
}
.hero h1 {
  font-size: clamp(2.2rem, 5vw, 3.6rem); font-weight: 900; color: #fff;
  line-height: 1.15; margin-bottom: 20px; position: relative;
}
.hero h1 .accent { color: #4ade80; }
.hero-sub {
  font-size: 1.1rem; color: rgba(255,255,255,.7); max-width: 600px;
  margin: 0 auto 36px; line-height: 1.7; position: relative;
}
.hero-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; position: relative; margin-bottom: 36px; }
.btn-hero-primary {
  padding: 14px 32px; border-radius: 10px; background: #16a34a; color: #fff;
  font-size: 15px; font-weight: 700; transition: background .15s, transform .15s;
  display: inline-flex; align-items: center; gap: 8px;
}
.btn-hero-primary:hover { background: #15803d; color: #fff; transform: translateY(-1px); }
.btn-hero-secondary {
  padding: 14px 32px; border-radius: 10px; border: 1.5px solid rgba(255,255,255,.3);
  color: #fff; font-size: 15px; font-weight: 600; background: transparent;
  transition: background .15s; display: inline-flex; align-items: center; gap: 8px;
}
.btn-hero-secondary:hover { background: rgba(255,255,255,.08); color: #fff; }
.hero-trust { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; position: relative; }
.hero-trust span { color: rgba(255,255,255,.6); font-size: 13px; display: flex; align-items: center; gap: 6px; }
.hero-trust i { color: #4ade80; }

/* ── STATS BAR ── */
.stats-bar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 28px 20px; }
.stats-inner { max-width: 900px; margin: 0 auto; display: flex; justify-content: center;
               align-items: center; gap: 0; flex-wrap: wrap; }
.stat-item { text-align: center; padding: 0 40px; }
.stat-num  { font-size: 2rem; font-weight: 900; color: #0f172a; line-height: 1; }
.stat-num span { color: #16a34a; }
.stat-lbl  { font-size: 12px; color: #94a3b8; font-weight: 600; margin-top: 4px; }
.stat-div  { width: 1px; height: 40px; background: #e2e8f0; flex-shrink: 0; }

/* ── JOURNEY ── */
.journey-section { padding: 72px 20px; background: #f8fafc; }
.section-eyebrow { text-align: center; font-size: 11px; font-weight: 800; letter-spacing: .1em;
                   text-transform: uppercase; color: #16a34a; margin-bottom: 10px; }
.section-title   { text-align: center; font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800;
                   color: #0f172a; margin-bottom: 12px; }
.section-sub     { text-align: center; color: #64748b; font-size: 1rem; max-width: 520px;
                   margin: 0 auto 48px; }
.journey-steps { display: flex; gap: 0; justify-content: center; align-items: stretch;
                 max-width: 960px; margin: 0 auto; flex-wrap: wrap; gap: 20px; }
.journey-card {
  flex: 1; min-width: 260px; max-width: 300px;
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px;
  padding: 28px 24px; position: relative;
}
.journey-card.j-free    { border-top: 4px solid #16a34a; }
.journey-card.j-platform { border-top: 4px solid #0ea5e9; }
.journey-card.j-consult { border-top: 4px solid #8b5cf6; }
.j-step-num {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800; margin-bottom: 16px;
}
.j-free    .j-step-num { background: #f0fdf4; color: #16a34a; }
.j-platform .j-step-num { background: #f0f9ff; color: #0369a1; }
.j-consult .j-step-num { background: #faf5ff; color: #7c3aed; }
.j-title    { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
.j-price    { font-size: 22px; font-weight: 900; margin-bottom: 10px; }
.j-free    .j-price { color: #16a34a; }
.j-platform .j-price { color: #0ea5e9; }
.j-consult .j-price { color: #8b5cf6; }
.j-desc     { font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 16px; }
.j-features { list-style: none; padding: 0; margin: 0; }
.j-features li { font-size: 12px; color: #374151; padding: 4px 0;
                 display: flex; align-items: flex-start; gap: 8px; }
.j-features li i { font-size: 13px; margin-top: 1px; flex-shrink: 0; }
.j-cta {
  display: block; text-align: center; margin-top: 20px; padding: 10px;
  border-radius: 8px; font-size: 13px; font-weight: 700; transition: opacity .15s;
}
.j-cta:hover { opacity: .85; }
.j-free    .j-cta { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
.j-platform .j-cta { background: #0ea5e9; color: #fff; }
.j-consult .j-cta { background: #8b5cf6; color: #fff; }

/* ── FEATURES ── */
.features-section { padding: 72px 20px; background: #fff; }
.features-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px; max-width: 1060px; margin: 0 auto;
}
.feat-card {
  padding: 24px; border: 1px solid #e2e8f0; border-radius: 14px;
  background: #fafafa; transition: box-shadow .2s, transform .2s;
}
.feat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); transform: translateY(-2px); background: #fff; }
.feat-icon {
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; margin-bottom: 14px;
}
.feat-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.feat-desc  { font-size: 12px; color: #64748b; line-height: 1.6; }

/* ── FRAMEWORKS ── */
.frameworks-section { padding: 72px 20px; background: #f8fafc; }
.fw-grid { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; max-width: 900px; margin: 0 auto; }
.fw-pill {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
  padding: 12px 18px; transition: border-color .15s, box-shadow .15s;
}
.fw-pill:hover { border-color: var(--c); box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.fw-pill-icon { width: 32px; height: 32px; border-radius: 8px;
                display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.fw-pill-name { font-size: 13px; font-weight: 700; color: #0f172a; }
.fw-pill-desc { font-size: 11px; color: #94a3b8; }

/* ── ROLES ── */
.roles-section { padding: 72px 20px; background: #fff; }
.roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
              gap: 16px; max-width: 1060px; margin: 0 auto; }
.role-card {
  border-radius: 14px; padding: 24px; border: 1.5px solid #e2e8f0;
  transition: box-shadow .2s, transform .2s;
}
.role-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
.role-icon { font-size: 26px; margin-bottom: 12px; }
.role-card h5 { font-size: 15px; font-weight: 800; margin-bottom: 8px; color: #0f172a; }
.role-card p  { font-size: 12px; color: #64748b; line-height: 1.6; margin-bottom: 12px; }
.role-list { list-style: none; padding: 0; margin: 0; }
.role-list li { font-size: 12px; padding: 3px 0; display: flex; align-items: center; gap: 7px; color: #374151; }
.role-list li::before { content: '✓'; font-weight: 800; font-size: 11px; flex-shrink: 0; }
.role-sme      { background: #f0fdf4; border-color: #bbf7d0; }
.role-sme .role-icon { color: #16a34a; }
.role-sme .role-list li::before { color: #16a34a; }
.role-assoc    { background: #f0f9ff; border-color: #bae6fd; }
.role-assoc .role-icon { color: #0369a1; }
.role-assoc .role-list li::before { color: #0369a1; }
.role-consult  { background: #faf5ff; border-color: #ddd6fe; }
.role-consult .role-icon { color: #7c3aed; }
.role-consult .role-list li::before { color: #7c3aed; }
.role-ref      { background: #fffbeb; border-color: #fde68a; }
.role-ref .role-icon { color: #d97706; }
.role-ref .role-list li::before { color: #d97706; }

/* ── CTA BANNER ── */
.cta-section {
  background: linear-gradient(135deg, #0f172a 0%, #064e3b 100%);
  padding: 80px 20px; text-align: center;
}
.cta-section h2 { font-size: 2rem; font-weight: 900; color: #fff; margin-bottom: 14px; }
.cta-section p  { color: rgba(255,255,255,.7); max-width: 520px; margin: 0 auto 32px; }
.cta-leaf { font-size: 40px; color: #4ade80; margin-bottom: 16px; }

/* ── FOOTER ── */
.lp-footer { background: #0f172a; padding: 48px 20px 24px; }
.footer-inner { max-width: 1060px; margin: 0 auto; }
.footer-top { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr; gap: 28px; margin-bottom: 32px; }
@media(max-width:900px) { .footer-top { grid-template-columns: 1fr 1fr 1fr; } }
@media(max-width:600px) { .footer-top { grid-template-columns: 1fr 1fr; } }
.footer-brand-name { font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 8px; }
.footer-brand-desc { font-size: 12px; color: #64748b; line-height: 1.6; }
.footer-heading { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;
                  letter-spacing: .08em; margin-bottom: 12px; }
.footer-link { display: block; color: #64748b; font-size: 13px; margin-bottom: 8px; transition: color .15s; }
.footer-link:hover { color: #fff; }
.fw-badge { display: inline-block; background: rgba(255,255,255,.07); color: #94a3b8;
            border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 600; margin: 3px; }
.footer-bottom { border-top: 1px solid rgba(255,255,255,.07); padding-top: 20px;
                 display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.footer-bottom span { font-size: 12px; color: #475569; }

/* ── Utility ── */
.container-lg { max-width: 1060px; margin: 0 auto; }
@media(max-width:640px) {
  .nav-links { display: none; }
  .hero { padding: 72px 16px 60px; }
  .stat-item { padding: 0 20px; }
}
</style>
</head>
<body>

<!-- ── NAV ─────────────────────────────────────────── -->
<nav class="lp-nav" id="lpNav">
  <div class="nav-brand">
    <img class="nav-logo-img"
         src="<?= APP_URL ?>/assets/img/esggen-logo.png"
         alt="ESG gen"
         onerror="this.style.display='none';document.getElementById('navBrandFallback').style.display='flex';">
    <div id="navBrandFallback" style="display:none;align-items:center;gap:10px">
      <div class="nav-logo"><i class="bi bi-leaf-fill"></i></div>
      <span class="nav-name">ESG <span>gen</span></span>
    </div>
  </div>
  <div class="nav-links">
    <a href="#journey">How It Works</a>
    <a href="#features">Features</a>
    <a href="#frameworks">Standards</a>
    <a href="#roles">Who It's For</a>
  </div>
  <div class="nav-actions">
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn-nav-cta"><i class="bi bi-speedometer2 me-1"></i>My Dashboard</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/login"    class="btn-nav-login">Login</a>
    <a href="<?= APP_URL ?>/register" class="btn-nav-cta"><i class="bi bi-rocket-takeoff me-1"></i>Start Free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── HERO ─────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-eyebrow">
    <i class="bi bi-patch-check-fill"></i>
    Built for Malaysia — Bursa SEDG · GRI · ISSB · CDP · ESRS · TCFD
  </div>
  <h1>
    ESG Compliance,<br>
    <span class="accent">Made Simple.</span>
  </h1>
  <p class="hero-sub">
    The complete ESG operating system for Malaysian SMEs and consultants.
    Start free with all mandatory indicators. Subscribe for full data collection and report generation.
    Engage a certified consultant for professional review.
  </p>
  <div class="hero-btns">
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn-hero-primary">
      <i class="bi bi-speedometer2"></i>Go to My Dashboard
    </a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/register" class="btn-hero-primary">
      <i class="bi bi-rocket-takeoff"></i>Start Free — No Credit Card
    </a>
    <a href="<?= APP_URL ?>/login" class="btn-hero-secondary">
      <i class="bi bi-box-arrow-in-right"></i>Login to Platform
    </a>
    <?php endif; ?>
  </div>
  <div class="hero-trust">
    <span><i class="bi bi-check-circle-fill"></i>All 15 mandatory Bursa SEDG indicators — free</span>
    <span><i class="bi bi-check-circle-fill"></i>14-day Platform trial</span>
    <span><i class="bi bi-check-circle-fill"></i>No credit card required</span>
  </div>
</section>

<!-- ── STATS BAR ─────────────────────────────────────────── -->
<section class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num">200<span>+</span></div>
      <div class="stat-lbl">ESG Indicators</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num">10</div>
      <div class="stat-lbl">Reporting Frameworks</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num">RM<span>0</span></div>
      <div class="stat-lbl">to Get Started</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num">3</div>
      <div class="stat-lbl">Simple Steps</div>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS / JOURNEY ─────────────────────────────────────────── -->
<section class="journey-section" id="journey">
  <div class="section-eyebrow">How It Works</div>
  <h2 class="section-title">Your ESG Journey in 3 Steps</h2>
  <p class="section-sub">From free setup to professional submission — everything in one platform.</p>

  <div class="journey-steps">

    <!-- Step 1 -->
    <div class="journey-card j-free">
      <div class="j-step-num">1</div>
      <div class="j-title">Sign Up Free</div>
      <div class="j-price">Free Forever</div>
      <div class="j-desc">Register your company and start entering ESG data immediately. All 15 mandatory Bursa SEDG indicators are included at no cost.</div>
      <ul class="j-features">
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>All 15 mandatory Bursa SEDG indicators</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>ESG score dashboard</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>Basic report generation</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>No credit card needed</li>
      </ul>
      <a href="<?= APP_URL ?>/register" class="j-cta"><i class="bi bi-rocket-takeoff me-1"></i>Create Free Account</a>
    </div>

    <!-- Step 2 -->
    <div class="journey-card j-platform">
      <div class="j-step-num">2</div>
      <div class="j-title">Subscribe to Platform</div>
      <div class="j-price">RM 1,500 <span style="font-size:14px;font-weight:600;color:#64748b">/ year</span></div>
      <div class="j-desc">Unlock the full ESG data collection OS. Access all indicators across all frameworks, full report generation, gap analysis, and carbon calculator.</div>
      <ul class="j-features">
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>All indicators — all frameworks</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Full report generation & PDF export</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Gap analysis & action plan</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Carbon calculator (Scope 1, 2 & 3)</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Industry benchmarking</li>
      </ul>
      <a href="<?= APP_URL ?>/register" class="j-cta"><i class="bi bi-lightning-charge-fill me-1"></i>Start 14-Day Trial</a>
    </div>

    <!-- Step 3 -->
    <div class="journey-card j-consult">
      <div class="j-step-num">3</div>
      <div class="j-title">Engage a Consultant</div>
      <div class="j-price">RM 8,000 <span style="font-size:14px;font-weight:600;color:#64748b">/ report</span></div>
      <div class="j-desc">Have a certified ESG associate professionally review your generated report, validate your data, and guide you through regulatory submission.</div>
      <ul class="j-features">
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6"></i>Professional report review & validation</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6"></i>Regulatory submission guidance</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6"></i>Gap remediation recommendations</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6"></i>1-on-1 consultation session</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6"></i>Report sign-off by certified consultant</li>
      </ul>
      <a href="mailto:hello@adcellent.com.my?subject=Consultation Enquiry" class="j-cta"><i class="bi bi-envelope-fill me-1"></i>Enquire Now</a>
    </div>

  </div>
</section>

<!-- ── FEATURES ─────────────────────────────────────────── -->
<section class="features-section" id="features">
  <div class="section-eyebrow">Platform Features</div>
  <h2 class="section-title">Everything you need for ESG compliance</h2>
  <p class="section-sub">From data entry to board-ready reports — all in one place.</p>

  <div class="features-grid container-lg">
    <?php
    $features = [
      ['bi-list-check',        '#f0f9ff','#0ea5e9', 'Full Indicator Library',  'All 15 mandatory Bursa SEDG indicators free. Subscribe for 200+ indicators across GRI, ISSB, ESRS, CDP, TCFD, and SASB.'],
      ['bi-bar-chart-steps',   '#f0fdf4','#16a34a', 'Gap Analysis',            'Instantly see which mandatory disclosures are missing. Priority-ranked by critical / high / medium impact with a clear action plan.'],
      ['bi-calculator',        '#fff7ed','#f97316', 'Carbon Calculator',       'Scope 1, 2 & 3 calculations using Malaysia MyGHG 2023 factors. Auto-saves results directly to your GHG indicators.'],
      ['bi-file-earmark-text', '#faf5ff','#8b5cf6', 'Report Generation',       'Generate full ESG reports mapped to Bursa SEDG, GRI, or TCFD templates. Export to PDF for submission or investor disclosure.'],
      ['bi-bar-chart-line',    '#fffbeb','#d97706', 'Industry Benchmarking',   'Compare your ESG scores against Malaysian industry peers. See where you rank and what to prioritise first.'],
      ['bi-shield-check',      '#f0fdf4','#16a34a', 'Audit Trail',             'Every data entry is timestamped, sourced, and verifiable. Full activity log for compliance auditors and board review.'],
      ['bi-people-fill',       '#f0f9ff','#0369a1', 'Consultant Hierarchy',    'Principal → Associate → Manager org structure for consulting firms. Each role sees exactly the right companies and data.'],
      ['bi-currency-dollar',   '#f0fdf4','#16a34a', 'Green Financing',         'Automatically identify BNM, Khazanah, and commercial green financing your company may qualify for based on ESG score.'],
    ];
    foreach ($features as [$icon, $bg, $color, $title, $desc]):
    ?>
    <div class="feat-card">
      <div class="feat-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div class="feat-title"><?= $title ?></div>
      <p class="feat-desc"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── FRAMEWORKS ─────────────────────────────────────────── -->
<section class="frameworks-section" id="frameworks">
  <div class="section-eyebrow">Supported Standards</div>
  <h2 class="section-title">All major ESG reporting frameworks</h2>
  <p class="section-sub">One platform for every framework your clients or regulators require.</p>

  <div class="fw-grid">
    <?php
    $fws = [
      ['bi-flag-fill',       '#dc2626', 'Bursa SEDG',   'Mandatory for Malaysian-listed & pre-IPO companies'],
      ['bi-globe',           '#0891b2', 'GRI Standards', 'Global standard — voluntary & regulated ESG'],
      ['bi-cloud-sun-fill',  '#0d9488', 'ISSB / TCFD',  'Climate financial disclosures — IFRS S1 & S2'],
      ['bi-droplet-fill',    '#0284c7', 'CDP',           'Carbon Disclosure Project for investors & supply chains'],
      ['bi-building-fill',   '#7c3aed', 'ESRS',          'EU CSRD — for export-facing companies'],
      ['bi-briefcase-fill',  '#ca8a04', 'SASB',          'Industry-specific packs — Manufacturing, Tech, F&B'],
      ['bi-bullseye',        '#16a34a', 'UN SDGs',       'Sustainable Development Goals alignment'],
      ['bi-diagram-3',       '#475569', 'TCFD',          'Task Force on Climate-related Financial Disclosures'],
    ];
    foreach ($fws as [$icon, $color, $name, $desc]):
    ?>
    <div class="fw-pill" style="--c:<?= $color ?>">
      <div class="fw-pill-icon" style="background:<?= $color ?>18;color:<?= $color ?>">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div>
        <div class="fw-pill-name"><?= $name ?></div>
        <div class="fw-pill-desc"><?= $desc ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── WHO IT'S FOR ─────────────────────────────────────────── -->
<section class="roles-section" id="roles">
  <div class="section-eyebrow">Who It's For</div>
  <h2 class="section-title">Built for the full ESG ecosystem</h2>
  <p class="section-sub">Every role in the ESG value chain — from business owner to certified consultant.</p>

  <div class="roles-grid container-lg">

    <div class="role-card role-sme">
      <div class="role-icon"><i class="bi bi-building"></i></div>
      <h5>SME Owner</h5>
      <p>Track your company's ESG data, close gaps, and generate reports for banks, investors, buyers, or Bursa submission.</p>
      <ul class="role-list">
        <li>Start free — 15 mandatory indicators</li>
        <li>Subscribe for full platform access</li>
        <li>Self-service at RM 1,500/year</li>
      </ul>
    </div>

    <div class="role-card role-assoc">
      <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
      <h5>ESG Consultant / Associate</h5>
      <p>Deliver professional ESG report review and consultation services to SME clients at RM 8,000 per report.</p>
      <ul class="role-list">
        <li>Manage multiple client companies</li>
        <li>Review & validate client reports</li>
        <li>Submission guidance & sign-off</li>
      </ul>
    </div>

    <div class="role-card role-ref">
      <div class="role-icon"><i class="bi bi-people-fill"></i></div>
      <h5>Referral Partner</h5>
      <p>Accountants, lawyers, company secretaries, and bankers who refer SME clients to the platform earn recurring referral fees.</p>
      <ul class="role-list">
        <li>15% referral fee per subscription</li>
        <li>Recurring annually on renewals</li>
        <li>No platform management needed</li>
      </ul>
    </div>

    <div class="role-card role-consult">
      <div class="role-icon"><i class="bi bi-diagram-3-fill"></i></div>
      <h5>Consulting Firm</h5>
      <p>Principal → Associate → Manager hierarchy gives your team the right access. Portfolio dashboard shows all clients in one view.</p>
      <ul class="role-list">
        <li>Full org tree management</li>
        <li>Portfolio ESG score overview</li>
        <li>Assign managers to clients</li>
      </ul>
    </div>

  </div>
</section>

<!-- ── CTA ─────────────────────────────────────────── -->
<section class="cta-section">
  <div class="cta-leaf"><i class="bi bi-leaf-fill"></i></div>
  <h2>Start your ESG journey today</h2>
  <p>Join Malaysian SMEs and consulting firms already using ESG gen to meet Bursa SEDG requirements, secure green financing, and deliver investor-grade sustainability reports.</p>
  <?php if ($isLoggedIn): ?>
  <a href="<?= APP_URL ?>/dashboard" class="btn-hero-primary" style="display:inline-flex">
    <i class="bi bi-speedometer2"></i>Go to Dashboard
  </a>
  <?php else: ?>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a href="<?= APP_URL ?>/register" class="btn-hero-primary">
      <i class="bi bi-rocket-takeoff"></i>Create Free Account
    </a>
    <a href="<?= APP_URL ?>/pricing" class="btn-hero-secondary">
      <i class="bi bi-grid-3x3-gap"></i>View Full Pricing
    </a>
  </div>
  <?php endif; ?>
</section>

<!-- ── FOOTER ─────────────────────────────────────────── -->
<footer class="lp-footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div>
        <div style="margin-bottom:10px">
          <img src="<?= APP_URL ?>/assets/img/esggen-logo.png" alt="ESG gen" height="40"
               style="display:block;filter:brightness(0)invert(1)"
               onerror="this.style.display='none';document.getElementById('footerBrandFallback').style.display='flex';">
          <div id="footerBrandFallback" style="display:none;align-items:center;gap:10px">
            <div class="nav-logo" style="width:30px;height:30px;font-size:14px"><i class="bi bi-leaf-fill"></i></div>
            <div class="footer-brand-name">ESG gen</div>
          </div>
        </div>
        <div class="footer-brand-desc">Malaysia's ESG platform for SMEs, accounting firms, and certified ESG consultants. Built for Bursa SEDG compliance and beyond.</div>
      </div>
      <div>
        <div class="footer-heading">Platform</div>
        <a href="#journey" class="footer-link">How It Works</a>
        <a href="#features" class="footer-link">Features</a>
        <a href="#frameworks" class="footer-link">Standards</a>
        <a href="<?= APP_URL ?>/pricing" class="footer-link">Pricing</a>
      </div>
      <div>
        <div class="footer-heading">Account</div>
        <a href="<?= APP_URL ?>/login"    class="footer-link">Login</a>
        <a href="<?= APP_URL ?>/register" class="footer-link">Register Free</a>
        <a href="mailto:hello@adcellent.com.my" class="footer-link">Contact Us</a>
      </div>
      <div>
        <div class="footer-heading">Legal</div>
        <a href="<?= APP_URL ?>/privacy"  class="footer-link">Privacy Policy</a>
        <a href="<?= APP_URL ?>/pdpa"     class="footer-link">PDPA Notice</a>
        <a href="<?= APP_URL ?>/terms"    class="footer-link">Terms of Use</a>
        <a href="<?= APP_URL ?>/cookies"  class="footer-link">Cookie Policy</a>
      </div>
      <div>
        <div class="footer-heading">Compliance Frameworks</div>
        <div style="margin-top:8px">
          <?php foreach (['Bursa SEDG','GRI','ISSB','TCFD','CDP','ESRS','SASB','UN SDGs'] as $fw): ?>
          <span class="fw-badge"><?= $fw ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Adcellent Biz Sdn Bhd (1511714-V) &bull; hello@adcellent.com.my &bull; +6011-3318 4600 &bull; All prices in MYR</span>
      <span style="display:flex;gap:14px;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/privacy" style="color:#475569;font-size:12px;text-decoration:none">Privacy</a>
        <a href="<?= APP_URL ?>/pdpa"    style="color:#475569;font-size:12px;text-decoration:none">PDPA</a>
        <a href="<?= APP_URL ?>/terms"   style="color:#475569;font-size:12px;text-decoration:none">Terms</a>
        <a href="<?= APP_URL ?>/cookies" style="color:#475569;font-size:12px;text-decoration:none">Cookies</a>
      </span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const nav = document.getElementById('lpNav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 40));

document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});
</script>
</body>
</html>
