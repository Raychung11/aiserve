<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::startSession();
$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AiServe ESG OS — Malaysia's ESG Platform for SMEs &amp; Consultants</title>
  <meta name="description" content="Bursa SEDG, GRI, TCFD, CDP and ESRS compliance platform. Automated gap analysis, carbon calculator, and audit-ready ESG reports for Malaysian businesses.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/landing.css" rel="stylesheet">
</head>
<body class="landing-body">

<!-- ═══════════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════════ -->
<nav class="landing-nav" id="landingNav">
  <div class="ln-brand">
    <div class="ln-logo"><i class="bi bi-leaf-fill"></i></div>
    <span class="ln-name">AiServe <span class="text-success">ESG OS</span></span>
  </div>
  <div class="ln-links d-none d-md-flex">
    <a href="#features">Features</a>
    <a href="#frameworks">Standards</a>
    <a href="#pricing">Pricing</a>
    <a href="#roles">Who It's For</a>
  </div>
  <div class="ln-actions">
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-success btn-sm">
      <i class="bi bi-speedometer2 me-1"></i>My Dashboard
    </a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/login" class="btn btn-outline-light btn-sm me-2">Login</a>
    <a href="<?= APP_URL ?>/register" class="btn btn-success btn-sm">
      <i class="bi bi-rocket-takeoff me-1"></i>Get Started Free
    </a>
    <?php endif; ?>
  </div>
</nav>

<!-- ═══════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════ -->
<section class="hero-section">
  <div class="hero-bg-pattern"></div>
  <div class="container hero-container">
    <div class="hero-badge">
      <i class="bi bi-patch-check-fill me-1 text-success"></i>
      Built for Malaysia — Bursa SEDG · GRI · TCFD · CDP · ESRS
    </div>
    <h1 class="hero-headline">
      ESG Compliance,<br>
      <span class="hero-headline-accent">Done Right.</span>
    </h1>
    <p class="hero-sub">
      The all-in-one ESG operating system for Malaysian SMEs, accounting firms, and ESG consultants.
      Track disclosures, close gaps, calculate carbon, and generate audit-ready reports — in one platform.
    </p>
    <div class="hero-actions">
      <?php if ($isLoggedIn): ?>
      <a href="<?= APP_URL ?>/dashboard" class="btn btn-success btn-lg hero-btn-primary">
        <i class="bi bi-speedometer2 me-2"></i>Go to My Dashboard
      </a>
      <?php else: ?>
      <a href="<?= APP_URL ?>/register" class="btn btn-success btn-lg hero-btn-primary">
        <i class="bi bi-rocket-takeoff me-2"></i>Start Free Today
      </a>
      <a href="<?= APP_URL ?>/login" class="btn btn-outline-light btn-lg hero-btn-secondary">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Platform
      </a>
      <?php endif; ?>
    </div>
    <div class="hero-trust">
      <span><i class="bi bi-check-circle-fill text-success me-1"></i>Free Starter Plan</span>
      <span><i class="bi bi-check-circle-fill text-success me-1"></i>14-day Professional trial</span>
      <span><i class="bi bi-check-circle-fill text-success me-1"></i>No credit card required</span>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     STATS BAR
═══════════════════════════════════════════════════════ -->
<section class="stats-bar">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-num">200<span class="stat-plus">+</span></div>
        <div class="stat-label">ESG Indicators</div>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <div class="stat-num">6</div>
        <div class="stat-label">Reporting Frameworks</div>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <div class="stat-num">3</div>
        <div class="stat-label">Subscription Tiers</div>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <div class="stat-num">RM0</div>
        <div class="stat-label">to Get Started</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     FEATURES
═══════════════════════════════════════════════════════ -->
<section class="features-section" id="features">
  <div class="container">
    <div class="section-label">PLATFORM FEATURES</div>
    <h2 class="section-heading">Everything you need for ESG compliance</h2>
    <p class="section-sub">From data collection to board-ready reports — all in one place.</p>

    <div class="row g-4 mt-2">
      <?php
      $features = [
        ['bi-bar-chart-steps',   'success', 'Gap Analysis',       'Instantly see which mandatory Bursa SEDG or GRI disclosures are missing. Priority-ranked by critical / high / medium impact.'],
        ['bi-calculator',        'primary', 'Carbon Calculator',  'Scope 1, 2 & 3 calculations using Malaysia MyGHG 2023 factors. Auto-maps results to your ESG indicators.'],
        ['bi-file-earmark-text', 'info',    'Report Generator',   'Generate audit-ready PDF/Word ESG reports mapped to Bursa SEDG, GRI, or TCFD templates in minutes.'],
        ['bi-tree',              'success', 'E/S/G Data Entry',   '200+ indicators with guidance notes, data sources, and verification flags for every disclosure.'],
        ['bi-currency-dollar',   'warning', 'Green Financing',    'Automatically identifies BNM, Khazanah, and commercial green financing opportunities your company is eligible for.'],
        ['bi-bar-chart-line',    'purple',  'Benchmarking',       'Compare your ESG scores against Malaysian industry peers. See where you rank and what to improve first.'],
        ['bi-people-fill',       'primary', 'Multi-Role Access',  'Principal → Associate → Manager hierarchy for consulting firms. Each role sees the right data.'],
        ['bi-shield-lock',       'success', 'Audit Trail',        'Every data entry is timestamped, sourced, and verifiable. Full activity log for compliance auditors.'],
      ];
      foreach ($features as [$icon, $color, $title, $desc]):
      ?>
      <div class="col-lg-3 col-md-6">
        <div class="feature-card">
          <div class="feature-icon bg-<?= $color ?>-soft">
            <i class="bi <?= $icon ?> text-<?= $color ?>"></i>
          </div>
          <h5 class="feature-title"><?= $title ?></h5>
          <p class="feature-desc"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     FRAMEWORKS
═══════════════════════════════════════════════════════ -->
<section class="frameworks-section" id="frameworks">
  <div class="container">
    <div class="section-label">SUPPORTED STANDARDS</div>
    <h2 class="section-heading">All major ESG reporting frameworks</h2>
    <p class="section-sub">One platform for every framework your clients or regulators require.</p>

    <div class="frameworks-grid mt-4">
      <?php
      $frameworks = [
        ['bi-flag-fill',        '#dc2626', 'Bursa SEDG',  'Mandatory for Bursa Malaysia-listed and pre-IPO companies. All 41 SEDG indicators.'],
        ['bi-globe',            '#0891b2', 'GRI Standards','Global Reporting Initiative — widely accepted for voluntary and regulated ESG.'],
        ['bi-cloud-sun-fill',   '#0d9488', 'TCFD / ISSB', 'Climate-related financial disclosures. ISSB S1 & S2 aligned.'],
        ['bi-droplet-fill',     '#0284c7', 'CDP',         'Carbon Disclosure Project — investors and supply chains worldwide.'],
        ['bi-building-fill',    '#7c3aed', 'ESRS',        'EU Corporate Sustainability Reporting Directive (CSRD) for export-facing firms.'],
        ['bi-briefcase-fill',   '#ca8a04', 'SASB',        'Industry-specific: Manufacturing, Food & Beverage, Technology packs.'],
      ];
      foreach ($frameworks as [$icon, $color, $name, $desc]):
      ?>
      <div class="fw-card">
        <div class="fw-icon" style="background:<?= $color ?>22; color:<?= $color ?>">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div class="fw-name"><?= $name ?></div>
        <div class="fw-desc"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     WHO IT'S FOR (ROLES)
═══════════════════════════════════════════════════════ -->
<section class="roles-section" id="roles">
  <div class="container">
    <div class="section-label">WHO IT'S FOR</div>
    <h2 class="section-heading">Built for every stakeholder in the ESG ecosystem</h2>

    <div class="row g-4 mt-2">
      <div class="col-md-3">
        <div class="role-card role-sme">
          <div class="role-icon"><i class="bi bi-building"></i></div>
          <h5>SME Owner</h5>
          <p>Track your own company's ESG data, close gaps, and generate reports for banks, buyers, or regulators.</p>
          <ul class="role-list">
            <li>Self-service data entry</li>
            <li>Instant gap analysis</li>
            <li>Green financing check</li>
          </ul>
        </div>
      </div>
      <div class="col-md-3">
        <div class="role-card role-principal">
          <div class="role-icon"><i class="bi bi-diagram-3-fill"></i></div>
          <h5>Principal</h5>
          <p>Consulting firm owner. Manage your entire associate team and see portfolio-wide ESG metrics at a glance.</p>
          <ul class="role-list">
            <li>All associates &amp; clients</li>
            <li>Portfolio avg ESG score</li>
            <li>Plan &amp; revenue overview</li>
          </ul>
        </div>
      </div>
      <div class="col-md-3">
        <div class="role-card role-associate">
          <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
          <h5>Associate</h5>
          <p>Accounting firm, COSEC, or senior ESG consultant managing a portfolio of clients with a team of managers.</p>
          <ul class="role-list">
            <li>Client portfolio grid</li>
            <li>Manage your managers</li>
            <li>E/S/G mini-bar view</li>
          </ul>
        </div>
      </div>
      <div class="col-md-3">
        <div class="role-card role-manager">
          <div class="role-icon"><i class="bi bi-person-workspace"></i></div>
          <h5>Manager</h5>
          <p>Staff analyst or junior consultant. See only your assigned companies, pending gaps, and priority actions.</p>
          <ul class="role-list">
            <li>Assigned companies only</li>
            <li>Priority action list</li>
            <li>Quick data entry links</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     PRICING
═══════════════════════════════════════════════════════ -->
<section class="lp-pricing-section" id="pricing">
  <div class="container">
    <div class="section-label">PRICING</div>
    <h2 class="section-heading">Simple, transparent pricing</h2>
    <p class="section-sub">Start free. Upgrade when your reporting requirements grow.</p>

    <div class="row g-4 justify-content-center mt-2">
      <!-- Starter -->
      <div class="col-lg-4 col-md-6">
        <div class="lp-plan-card">
          <div class="lp-plan-name">Starter</div>
          <div class="lp-plan-price"><span class="lp-currency">RM</span>0<span class="lp-period">/year</span></div>
          <div class="lp-plan-desc">5 preview indicators. Try the platform risk-free.</div>
          <a href="<?= APP_URL ?>/register" class="btn btn-outline-primary w-100 mb-3">Get Started Free</a>
          <ul class="lp-plan-features">
            <li><i class="bi bi-check text-success"></i> 5 ESG indicators</li>
            <li><i class="bi bi-check text-success"></i> Basic dashboard</li>
            <li><i class="bi bi-check text-success"></i> Carbon calculator</li>
            <li><i class="bi bi-x text-muted"></i> <span class="text-muted">Full gap analysis</span></li>
            <li><i class="bi bi-x text-muted"></i> <span class="text-muted">Report generation</span></li>
          </ul>
        </div>
      </div>
      <!-- Standard -->
      <div class="col-lg-4 col-md-6">
        <div class="lp-plan-card lp-plan-popular">
          <div class="lp-popular-badge">Most Popular</div>
          <div class="lp-plan-name">Standard</div>
          <div class="lp-plan-price"><span class="lp-currency">RM</span>1,500<span class="lp-period">/year</span></div>
          <div class="lp-plan-desc">15 mandatory Bursa SEDG indicators. Regulatory baseline.</div>
          <a href="<?= APP_URL ?>/register" class="btn btn-success w-100 mb-3">Start 14-day Trial</a>
          <ul class="lp-plan-features">
            <li><i class="bi bi-check text-success"></i> 15 mandatory Bursa SEDG indicators</li>
            <li><i class="bi bi-check text-success"></i> Full gap analysis</li>
            <li><i class="bi bi-check text-success"></i> Report generation</li>
            <li><i class="bi bi-check text-success"></i> Carbon calculator</li>
            <li><i class="bi bi-check text-success"></i> Add-on collections available</li>
          </ul>
        </div>
      </div>
      <!-- Professional -->
      <div class="col-lg-4 col-md-6">
        <div class="lp-plan-card lp-plan-pro">
          <div class="lp-plan-name">Professional</div>
          <div class="lp-plan-price"><span class="lp-currency">RM</span>3,500<span class="lp-period">/year</span></div>
          <div class="lp-plan-desc">All 200+ indicators across all 6 frameworks. No limits.</div>
          <a href="<?= APP_URL ?>/register" class="btn btn-primary w-100 mb-3">Start 14-day Trial</a>
          <ul class="lp-plan-features">
            <li><i class="bi bi-check text-success"></i> All 200+ indicators</li>
            <li><i class="bi bi-check text-success"></i> All 6 frameworks</li>
            <li><i class="bi bi-check text-success"></i> Multi-company support</li>
            <li><i class="bi bi-check text-success"></i> Benchmarking</li>
            <li><i class="bi bi-check text-success"></i> Priority support</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="<?= APP_URL ?>/pricing" class="text-muted small">
        View full pricing &amp; add-on indicator collections <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     FINAL CTA
═══════════════════════════════════════════════════════ -->
<section class="cta-section">
  <div class="container text-center">
    <div class="cta-leaf"><i class="bi bi-leaf-fill"></i></div>
    <h2 class="cta-heading">Start your ESG journey today</h2>
    <p class="cta-sub">Join Malaysian SMEs and consulting firms already using AiServe ESG OS to meet Bursa SEDG requirements and secure green financing.</p>
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-success btn-lg px-5">
      <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
    </a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/register" class="btn btn-success btn-lg px-5 me-3">
      <i class="bi bi-rocket-takeoff me-2"></i>Create Free Account
    </a>
    <a href="<?= APP_URL ?>/login" class="btn btn-outline-light btn-lg px-5">
      Sign In
    </a>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════ -->
<footer class="landing-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="ln-logo ln-logo-sm"><i class="bi bi-leaf-fill"></i></div>
          <span class="fw-bold text-white">AiServe ESG OS</span>
        </div>
        <p class="text-muted small">Malaysia's AI-powered ESG operating system for SMEs, accounting firms, and ESG consultants.</p>
      </div>
      <div class="col-md-2">
        <div class="footer-heading">Platform</div>
        <a href="#features" class="footer-link">Features</a>
        <a href="#frameworks" class="footer-link">Standards</a>
        <a href="<?= APP_URL ?>/pricing" class="footer-link">Pricing</a>
      </div>
      <div class="col-md-2">
        <div class="footer-heading">Account</div>
        <a href="<?= APP_URL ?>/login" class="footer-link">Login</a>
        <a href="<?= APP_URL ?>/register" class="footer-link">Register</a>
      </div>
      <div class="col-md-4">
        <div class="footer-heading">Compliance Frameworks</div>
        <div class="d-flex flex-wrap gap-2 mt-1">
          <?php foreach (['Bursa SEDG','GRI','TCFD','ISSB','CDP','ESRS','SASB'] as $fw): ?>
          <span class="footer-fw-badge"><?= $fw ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span class="text-muted small">&copy; <?= date('Y') ?> AiServe. Built for Malaysia's ESG ecosystem.</span>
      <span class="text-muted small">MyGHG 2023 &bull; DEFRA 2023 &bull; Bursa SEDG 2022</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sticky nav on scroll
const nav = document.getElementById('landingNav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('nav-scrolled', window.scrollY > 40);
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
</script>
</body>
</html>
