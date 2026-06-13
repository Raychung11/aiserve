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
<title>Cookie Policy | ESG gen</title>
<meta name="description" content="ESG gen uses only one strictly necessary session cookie — no tracking, no advertising, no analytics cookies. Learn what we use and how to manage it.">
<meta name="keywords" content="ESG gen cookie policy, session cookie, no tracking cookies, PDPA cookies Malaysia, Adcellent cookie notice">
<meta name="author" content="ESG gen — Adcellent Biz Sdn Bhd">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= APP_URL ?>/cookies">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= APP_URL ?>/cookies">
<meta property="og:site_name" content="ESG gen">
<meta property="og:locale" content="en_MY">
<meta property="og:title" content="Cookie Policy | ESG gen">
<meta property="og:description" content="ESG gen uses only one strictly necessary session cookie. No advertising or tracking cookies.">
<meta property="og:image" content="<?= APP_URL ?>/assets/img/og-esggen.png">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Cookie Policy | ESG gen">
<meta name="twitter:description" content="ESG gen uses one session cookie only — no tracking or advertising cookies.">
<link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Cookie Policy","url":"<?= APP_URL ?>/cookies","description":"Cookie Policy for ESG gen — one strictly necessary session cookie, no tracking or advertising.","inLanguage":"en-MY","isPartOf":{"@type":"WebSite","name":"ESG gen","url":"<?= APP_URL ?>"},"publisher":{"@type":"Organization","name":"Adcellent Biz Sdn Bhd","url":"<?= APP_URL ?>"}}
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#1e293b;line-height:1.7}
a{color:#16a34a}
.lg-nav{background:#0f172a;padding:0 32px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(255,255,255,.07)}
.lg-nav-icon{background:#16a34a;color:#fff;width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.lg-nav-name{font-size:15px;font-weight:800;color:#fff}.lg-nav-name span{color:#4ade80}
.lg-nav-actions{display:flex;gap:10px;align-items:center}
.btn-nav-login{padding:6px 14px;border-radius:7px;border:1px solid rgba(255,255,255,.25);color:#fff;font-size:13px;font-weight:600;background:transparent;text-decoration:none}
.btn-nav-cta{padding:6px 14px;border-radius:7px;background:#16a34a;color:#fff;font-size:13px;font-weight:700;text-decoration:none}
.lg-hero{background:linear-gradient(135deg,#0f172a,#1e293b);padding:52px 24px 40px;text-align:center}
.lg-hero-tag{display:inline-block;background:rgba(22,163,74,.15);border:1px solid rgba(74,222,128,.25);color:#4ade80;padding:4px 14px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px}
.lg-hero h1{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;color:#fff;margin-bottom:10px}
.lg-hero-meta{font-size:13px;color:#64748b}
.lg-hero-meta strong{color:#94a3b8}
.lg-wrap{max-width:820px;margin:0 auto;padding:48px 24px 80px}
.lg-toc{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;margin-bottom:40px}
.lg-toc-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:12px}
.lg-toc ol{margin:0;padding-left:20px}
.lg-toc li{font-size:13px;color:#374151;margin-bottom:6px}
.lg-toc a{color:#0ea5e9;text-decoration:none}.lg-toc a:hover{text-decoration:underline}
.lg-section{margin-bottom:44px;scroll-margin-top:80px}
.lg-section h2{font-size:1.1rem;font-weight:800;color:#0f172a;padding-bottom:10px;border-bottom:2px solid #16a34a;margin-bottom:16px}
.lg-section h3{font-size:.95rem;font-weight:700;color:#334155;margin:18px 0 8px}
.lg-section p,.lg-section li{font-size:14px;color:#374151}
.lg-section ul,.lg-section ol{padding-left:22px;margin-bottom:12px}
.lg-section li{margin-bottom:5px}
.lg-section table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px}
.lg-section th{background:#f1f5f9;padding:10px 14px;text-align:left;font-weight:700;color:#475569;border:1px solid #e2e8f0}
.lg-section td{padding:10px 14px;border:1px solid #e2e8f0;color:#374151;vertical-align:top}
.lg-callout{background:rgba(22,163,74,.06);border-left:3px solid #16a34a;border-radius:0 8px 8px 0;padding:14px 18px;margin:16px 0;font-size:13px;color:#374151}
.lg-callout strong{color:#16a34a}
.lg-footer{background:#0f172a;padding:28px 24px;text-align:center}
.lg-footer-links{display:flex;gap:20px;justify-content:center;flex-wrap:wrap;margin-bottom:10px}
.lg-footer-links a{color:#64748b;font-size:12px;text-decoration:none;transition:color .15s}
.lg-footer-links a:hover{color:#94a3b8}
.lg-footer-links a.lg-active{color:#4ade80}
.lg-footer-copy{font-size:12px;color:#334155}
</style>
</head>
<body>

<!-- NAV -->
<nav class="lg-nav">
  <a href="<?= APP_URL ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none">
    <img src="<?= APP_URL ?>/assets/img/esggen-logo.png" height="44" alt="ESG gen"
         onerror="this.style.display='none';document.getElementById('lgNavFallback4').style.display='flex'">
    <span id="lgNavFallback4" style="display:none;align-items:center;gap:8px">
      <span class="lg-nav-icon"><i class="bi bi-leaf-fill"></i></span>
      <span class="lg-nav-name">ESG <span>gen</span></span>
    </span>
  </a>
  <div class="lg-nav-actions">
    <?php if ($isLoggedIn): ?>
      <a href="<?= APP_URL ?>/dashboard" class="btn-nav-cta">Dashboard</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login" class="btn-nav-login">Log In</a>
      <a href="<?= APP_URL ?>/register" class="btn-nav-cta">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="lg-hero">
  <div class="lg-hero-tag">Legal</div>
  <h1>Cookie Policy</h1>
  <div class="lg-hero-meta">Last updated: <strong>31 May 2026</strong> &nbsp;&middot;&nbsp; Adcellent Biz Sdn Bhd (1511714-V)</div>
</div>

<!-- CONTENT -->
<div class="lg-wrap">

  <!-- Table of Contents -->
  <div class="lg-toc">
    <div class="lg-toc-title">Table of Contents</div>
    <ol>
      <li><a href="#s1">What Are Cookies</a></li>
      <li><a href="#s2">Cookies We Use</a></li>
      <li><a href="#s3">Third-Party Services and CDNs</a></li>
      <li><a href="#s4">Managing Cookies</a></li>
      <li><a href="#s5">Contact Us</a></li>
    </ol>
  </div>

  <!-- Section 1 -->
  <div class="lg-section" id="s1">
    <h2>1. What Are Cookies</h2>
    <p>Cookies are small text files that a website places on your device (computer, tablet, or smartphone) when you visit it. They enable the website to remember your actions and preferences &mdash; such as your login state &mdash; over a period of time, so you don&rsquo;t have to re-enter information every time you come back to the site or navigate between pages.</p>
    <p>Cookies can be:</p>
    <ul>
      <li><strong>Session cookies</strong> &mdash; temporary files that are deleted when you close your browser or log out.</li>
      <li><strong>Persistent cookies</strong> &mdash; files that remain on your device until a set expiry date or until you manually delete them.</li>
    </ul>
    <p>Cookies can also be categorised as <strong>first-party</strong> (set by the website you are visiting) or <strong>third-party</strong> (set by a different domain, typically for tracking or advertising purposes).</p>
  </div>

  <!-- Section 2 -->
  <div class="lg-section" id="s2">
    <h2>2. Cookies We Use</h2>
    <p>ESG gen uses only <strong>strictly necessary cookies</strong>. We do not use cookies for advertising, behavioural tracking, retargeting, or analytics.</p>
    <table>
      <thead>
        <tr>
          <th>Cookie Name</th>
          <th>Type</th>
          <th>Purpose</th>
          <th>Duration</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>adcellent_session</code></td>
          <td>Essential &mdash; first-party session cookie</td>
          <td>Maintains your authenticated login state between page requests. Without this cookie, you cannot remain logged in to ESG gen. No personal data is stored inside the cookie itself &mdash; it contains only a random session identifier that references encrypted server-side session data.</td>
          <td>Session (deleted when you close your browser or log out)</td>
        </tr>
      </tbody>
    </table>
    <div class="lg-callout"><strong>We do not set any tracking, advertising, or analytics cookies.</strong> The single session cookie listed above is the only cookie placed on your device by ESG gen.</div>
  </div>

  <!-- Section 3 -->
  <div class="lg-section" id="s3">
    <h2>3. Third-Party Services and CDNs</h2>
    <p>ESG gen loads certain static resources from third-party content delivery networks (CDNs) to improve page load performance:</p>
    <table>
      <thead>
        <tr>
          <th>Service</th>
          <th>Provider</th>
          <th>What Is Loaded</th>
          <th>Data Collected by Provider</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Bootstrap CSS &amp; JS</td>
          <td>jsDelivr CDN (<code>cdn.jsdelivr.net</code>)</td>
          <td>Bootstrap 5 stylesheet and JavaScript bundle</td>
          <td rowspan="3">jsDelivr may log your IP address, browser user-agent, and HTTP referrer for operational and security purposes. jsDelivr does not set tracking cookies. See <a href="https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net" target="_blank" rel="noopener">jsDelivr&rsquo;s Privacy Policy</a>.</td>
        </tr>
        <tr>
          <td>Bootstrap Icons</td>
          <td>jsDelivr CDN (<code>cdn.jsdelivr.net</code>)</td>
          <td>Bootstrap Icons font stylesheet</td>
        </tr>
        <tr>
          <td>Chart.js</td>
          <td>jsDelivr CDN (<code>cdn.jsdelivr.net</code>)</td>
          <td>Chart.js library for data visualisation</td>
        </tr>
      </tbody>
    </table>
    <p>We do <strong>not</strong> use Google Analytics, Google Tag Manager, Facebook Pixel, Hotjar, or any other third-party tracking or analytics scripts. No personal data from your ESG gen session is shared with these CDN providers.</p>
  </div>

  <!-- Section 4 -->
  <div class="lg-section" id="s4">
    <h2>4. Managing Cookies</h2>
    <p>You can control and delete cookies through your browser settings. Instructions for major browsers:</p>
    <ul>
      <li><strong>Chrome:</strong> Settings &rarr; Privacy and security &rarr; Cookies and other site data</li>
      <li><strong>Firefox:</strong> Settings &rarr; Privacy &amp; Security &rarr; Cookies and Site Data</li>
      <li><strong>Safari:</strong> Preferences &rarr; Privacy &rarr; Manage Website Data</li>
      <li><strong>Microsoft Edge:</strong> Settings &rarr; Cookies and site permissions &rarr; Manage and delete cookies and site data</li>
    </ul>
    <div class="lg-callout">
      <strong>Important:</strong> Because we only use strictly necessary cookies, blocking or deleting the <code>adcellent_session</code> cookie will log you out of ESG gen and prevent you from accessing authenticated features (data entry, reports, gap analysis, carbon calculator, etc.). The Platform cannot function without this cookie.
    </div>
    <p>Since we do not use advertising or tracking cookies, there is no cookie consent banner on ESG gen. The single session cookie is exempt from consent requirements under applicable privacy frameworks as it is strictly necessary for the service to function.</p>
  </div>

  <!-- Section 5 -->
  <div class="lg-section" id="s5">
    <h2>5. Contact Us</h2>
    <p>If you have questions about our use of cookies or this Cookie Policy, please contact us:</p>
    <ul>
      <li><strong>Organisation:</strong> Adcellent Biz Sdn Bhd (1511714-V)</li>
      <li><strong>Address:</strong> D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi, 59200 Kuala Lumpur, Malaysia</li>
      <li><strong>Email:</strong> <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a></li>
      <li><strong>Phone:</strong> <a href="tel:+60113318460">+6011-3318 4600</a></li>
    </ul>
    <p>For questions about how we handle your personal data more broadly, please see our <a href="<?= APP_URL ?>/privacy">Privacy Policy</a> and <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a>.</p>
  </div>

</div>

<!-- FOOTER -->
<footer class="lg-footer">
  <div class="lg-footer-links">
    <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>
    <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a>
    <a href="<?= APP_URL ?>/terms">Terms of Use</a>
    <a href="<?= APP_URL ?>/cookies" class="lg-active">Cookie Policy</a>
  </div>
  <div class="lg-footer-copy">&copy; <?= date('Y') ?> Adcellent Biz Sdn Bhd (1511714-V). All rights reserved.</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
