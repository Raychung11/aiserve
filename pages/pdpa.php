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
<title>PDPA Notice | ESG gen</title>
<meta name="description" content="ESG gen's Personal Data Protection Act (PDPA) Notice under Malaysia Act 709. Understand your data subject rights — access, correction, and withdrawal of consent.">
<meta name="keywords" content="PDPA Malaysia, Personal Data Protection Act 709, ESG gen PDPA, data subject rights Malaysia, Adcellent PDPA notice">
<meta name="author" content="ESG gen — Adcellent Biz Sdn Bhd">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= APP_URL ?>/pdpa">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= APP_URL ?>/pdpa">
<meta property="og:site_name" content="ESG gen">
<meta property="og:locale" content="en_MY">
<meta property="og:title" content="PDPA Notice | ESG gen">
<meta property="og:description" content="ESG gen PDPA Notice — your rights under Malaysia's Personal Data Protection Act 2010 (Act 709).">
<meta property="og:image" content="<?= APP_URL ?>/assets/img/og-esggen.png">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="PDPA Notice | ESG gen">
<meta name="twitter:description" content="Your rights under Malaysia's PDPA Act 709 when using ESG gen.">
<link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"PDPA Notice","url":"<?= APP_URL ?>/pdpa","description":"Personal Data Protection Act (PDPA) Notice for ESG gen under Malaysia Act 709.","inLanguage":"en-MY","isPartOf":{"@type":"WebSite","name":"ESG gen","url":"<?= APP_URL ?>"},"publisher":{"@type":"Organization","name":"Adcellent Biz Sdn Bhd","url":"<?= APP_URL ?>"}}
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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
.lg-hero-sub{font-size:14px;color:rgba(255,255,255,.55);margin-bottom:10px}
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

<!-- Nav -->
<nav class="lg-nav">
  <a href="<?= APP_URL ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none">
    <img src="<?= APP_URL ?>/assets/img/esggen-logo.png" height="44" alt="ESG gen"
         onerror="this.style.display='none';document.getElementById('lgNavFallback2').style.display='flex'">
    <span id="lgNavFallback2" style="display:none;align-items:center;gap:8px">
      <span class="lg-nav-icon"><i class="bi bi-leaf-fill"></i></span>
      <span class="lg-nav-name">ESG <span>gen</span></span>
    </span>
  </a>
  <div class="lg-nav-actions">
    <?php if ($isLoggedIn): ?>
      <a href="<?= APP_URL ?>/dashboard" class="btn-nav-cta">Dashboard</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login"    class="btn-nav-login">Log in</a>
      <a href="<?= APP_URL ?>/register" class="btn-nav-cta">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<div class="lg-hero">
  <div class="lg-hero-tag">Legal</div>
  <h1>Personal Data Protection Notice</h1>
  <div class="lg-hero-sub">Under the Personal Data Protection Act 2010 (Act 709), Malaysia</div>
  <div class="lg-hero-meta"><strong>Last updated:</strong> 31 May 2026</div>
</div>

<!-- Content -->
<div class="lg-wrap">

  <!-- TOC -->
  <div class="lg-toc">
    <div class="lg-toc-title">Table of Contents</div>
    <ol>
      <li><a href="#s1">About This Notice</a></li>
      <li><a href="#s2">Data Controller</a></li>
      <li><a href="#s3">Personal Data We Process</a></li>
      <li><a href="#s4">Purposes of Processing</a></li>
      <li><a href="#s5">Consent</a></li>
      <li><a href="#s6">Mandatory or Optional Provision</a></li>
      <li><a href="#s7">Consequences of Not Providing Data</a></li>
      <li><a href="#s8">Disclosure</a></li>
      <li><a href="#s9">Data Transfer</a></li>
      <li><a href="#s10">Security and Retention</a></li>
      <li><a href="#s11">Your Rights Under the PDPA</a></li>
      <li><a href="#s12">How to Exercise Your Rights</a></li>
      <li><a href="#s13">Contact the Data Controller</a></li>
    </ol>
  </div>

  <!-- Section 1 -->
  <div class="lg-section" id="s1">
    <h2>1. About This Notice</h2>
    <p>This Personal Data Protection Notice ("Notice") is issued pursuant to <strong>Section 7 of the Personal Data Protection Act 2010 ("PDPA 2010" or "Act 709")</strong> of Malaysia. It describes how Adcellent Biz Sdn Bhd processes personal data collected in connection with the ESG gen platform (<a href="<?= APP_URL ?>"><?= APP_URL ?></a>).</p>
    <p>By registering or using ESG gen, you acknowledge that you have read and understood this Notice. Please also read our <a href="<?= APP_URL ?>/privacy">Privacy Policy</a> for further details on data handling practices.</p>
  </div>

  <!-- Section 2 -->
  <div class="lg-section" id="s2">
    <h2>2. Data Controller</h2>
    <p>
      <strong>Adcellent Biz Sdn Bhd</strong> (Company No. 1511714-V)<br>
      D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi<br>
      59200 Kuala Lumpur, Malaysia<br>
      Tel: <a href="tel:+60113318460">+6011-3318 4600</a><br>
      Email: <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>
    </p>
  </div>

  <!-- Section 3 -->
  <div class="lg-section" id="s3">
    <h2>3. Personal Data We Process</h2>
    <table>
      <thead>
        <tr><th>Category</th><th>Examples</th></tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Identity &amp; Contact</strong></td>
          <td>Full name, email address, phone number (optional)</td>
        </tr>
        <tr>
          <td><strong>Authentication</strong></td>
          <td>Password (stored as a one-way bcrypt hash — your original password is never stored)</td>
        </tr>
        <tr>
          <td><strong>Company Information</strong></td>
          <td>Company name, SSM registration number, industry sector, employee count, revenue tier, Bursa Malaysia sector</td>
        </tr>
        <tr>
          <td><strong>ESG &amp; Sustainability Data</strong></td>
          <td>Environmental, social and governance indicator values; carbon emission data; gap analysis results; action plans; KPI snapshots</td>
        </tr>
        <tr>
          <td><strong>System &amp; Usage</strong></td>
          <td>Login timestamps, activity logs (action + timestamp), IP address, session identifier, browser user-agent</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Section 4 -->
  <div class="lg-section" id="s4">
    <h2>4. Purposes of Processing</h2>
    <p>Under <strong>Section 6 of the PDPA 2010</strong>, we process your personal data for the following purposes:</p>
    <ol type="a">
      <li>To register and maintain your account on the ESG gen platform;</li>
      <li>To deliver ESG reporting, gap analysis, carbon calculator, industry benchmarking, and report generation services;</li>
      <li>To generate ESG compliance reports for submission to Bursa Malaysia or other regulatory bodies;</li>
      <li>To send transactional communications (account activation, password reset, subscription notices);</li>
      <li>To maintain an audit trail for compliance and regulatory purposes;</li>
      <li>To monitor platform security, detect fraud, and prevent unauthorised access;</li>
      <li>To improve the platform through aggregated and anonymised analytics.</li>
    </ol>
    <div class="lg-callout"><strong>We do not use your personal data for direct marketing without your explicit consent, and we do not sell or rent your personal data to any third party.</strong></div>
  </div>

  <!-- Section 5 -->
  <div class="lg-section" id="s5">
    <h2>5. Consent</h2>
    <p>By registering an account on ESG gen, you expressly consent to the collection, use, and disclosure of your personal data for the purposes stated in Section 4 above, in accordance with the PDPA 2010.</p>
    <p>You may withdraw your consent at any time by contacting us at <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>. Please note that withdrawal of consent may affect our ability to provide some or all of the Platform services to you, and may be subject to applicable legal or contractual retention obligations.</p>
  </div>

  <!-- Section 6 -->
  <div class="lg-section" id="s6">
    <h2>6. Mandatory or Optional Provision</h2>
    <h3>Mandatory</h3>
    <p>The following data is required to create and use your account:</p>
    <ul>
      <li>Full name</li>
      <li>Email address</li>
      <li>Password</li>
    </ul>
    <h3>Optional</h3>
    <p>The following data is collected only when you choose to provide it:</p>
    <ul>
      <li>Phone number</li>
      <li>Bursa Malaysia sector</li>
      <li>Company revenue tier and employee count</li>
    </ul>
  </div>

  <!-- Section 7 -->
  <div class="lg-section" id="s7">
    <h2>7. Consequences of Not Providing Data</h2>
    <p>If you do not provide the mandatory personal data listed in Section 6, we will be unable to create your account or deliver the Platform services described in this Notice. Optional data, if not provided, may result in reduced functionality (e.g., sector-specific benchmarking will not be available without a Bursa sector selection).</p>
  </div>

  <!-- Section 8 -->
  <div class="lg-section" id="s8">
    <h2>8. Disclosure</h2>
    <p>Your personal data may be disclosed to:</p>
    <ol type="a">
      <li><strong>Other authorised users</strong> within your company account, as configured by the account owner (e.g., a consultant assigned to your company will be able to view your ESG data);</li>
      <li><strong>Hostinger International Ltd.</strong>, our hosting provider, solely for the purpose of hosting and maintaining the Platform;</li>
      <li><strong>Malaysian regulatory authorities, courts, or law enforcement</strong> where required by applicable law, court order, or government regulation.</li>
    </ol>
    <p>We will never sell, rent, trade, or otherwise disclose your personal data to any other third party without your explicit consent.</p>
  </div>

  <!-- Section 9 -->
  <div class="lg-section" id="s9">
    <h2>9. Data Transfer</h2>
    <p>Your personal data is stored on servers operated by Hostinger International Ltd. Hostinger maintains data centres that comply with applicable data protection standards. Where data is processed outside Malaysia, we take reasonable contractual and technical steps to ensure adequate protection consistent with the requirements of the PDPA 2010.</p>
  </div>

  <!-- Section 10 -->
  <div class="lg-section" id="s10">
    <h2>10. Security and Retention</h2>
    <p>We implement administrative, technical, and physical security measures appropriate to the nature of the personal data processed, including:</p>
    <ul>
      <li>Passwords stored using bcrypt hashing (one-way; original passwords are never stored);</li>
      <li>HTTPS (TLS) encryption for all data transmitted between your browser and our servers;</li>
      <li>Session-based authentication with CSRF token protection;</li>
      <li>Role-based access control ensuring users can only access data they are authorised to view.</li>
    </ul>
    <h3>Retention Periods</h3>
    <table>
      <thead>
        <tr><th>Data Type</th><th>Retention Period</th></tr>
      </thead>
      <tbody>
        <tr><td>Account and company data</td><td>While account is active; 6 months after deletion request, then permanently deleted</td></tr>
        <tr><td>Activity logs</td><td>24 months</td></tr>
        <tr><td>ESG data and generated reports</td><td>Up to 7 years (to support regulatory audit trails)</td></tr>
        <tr><td>Session data</td><td>24 hours (or until logout)</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Section 11 -->
  <div class="lg-section" id="s11">
    <h2>11. Your Rights Under the PDPA</h2>
    <p>Under the PDPA 2010, you have the following rights:</p>
    <ul>
      <li><strong>Right to Access (Section 30):</strong> Request access to your personal data held by us. We will provide a copy within 21 working days.</li>
      <li><strong>Right to Correction (Section 34):</strong> Request correction of inaccurate, incomplete, misleading, or out-of-date personal data.</li>
      <li><strong>Right to Withdraw Consent:</strong> Withdraw consent to the processing of your personal data, subject to legal obligations and contractual terms. Withdrawal may limit our ability to provide the Platform services.</li>
      <li><strong>Right to Limit Processing:</strong> Request that we cease or limit processing of your personal data where processing is no longer necessary for the stated purposes.</li>
    </ul>
  </div>

  <!-- Section 12 -->
  <div class="lg-section" id="s12">
    <h2>12. How to Exercise Your Rights</h2>
    <p>Submit a written request to <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a> or by post to the address in Section 2. Please include your full name and the email address associated with your account so we can verify your identity.</p>
    <ul>
      <li>We will respond within <strong>21 working days</strong> of receiving your request.</li>
      <li>We may require proof of identity before processing your request.</li>
      <li>Access or correction requests may be subject to a prescribed fee under the PDPA 2010.</li>
      <li>If your request is refused, we will provide written reasons as required by the Act.</li>
    </ul>
    <p>If you are dissatisfied with our response, you may lodge a complaint with the <strong>Personal Data Protection Commissioner of Malaysia</strong>.</p>
  </div>

  <!-- Section 13 -->
  <div class="lg-section" id="s13">
    <h2>13. Contact the Data Controller</h2>
    <p>For any queries, access/correction requests, complaints, or consent withdrawal regarding your personal data:</p>
    <p>
      <strong>Adcellent Biz Sdn Bhd (1511714-V)</strong><br>
      D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi<br>
      59200 Kuala Lumpur, Malaysia<br>
      Email: <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a><br>
      Phone: <a href="tel:+60113318460">+6011-3318 4600</a>
    </p>
  </div>

</div>

<!-- Footer -->
<footer class="lg-footer">
  <div class="lg-footer-links">
    <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>
    <a href="<?= APP_URL ?>/pdpa" class="lg-active">PDPA Notice</a>
    <a href="<?= APP_URL ?>/terms">Terms of Use</a>
    <a href="<?= APP_URL ?>/cookies">Cookie Policy</a>
  </div>
  <div class="lg-footer-copy">&copy; <?= date('Y') ?> Adcellent Biz Sdn Bhd (1511714-V). All rights reserved.</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
