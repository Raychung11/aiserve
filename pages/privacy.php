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
<title>Privacy Policy | ESG gen</title>
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= APP_URL ?>/privacy">
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
         onerror="this.style.display='none';document.getElementById('lgNavFallback1').style.display='flex'">
    <span id="lgNavFallback1" style="display:none;align-items:center;gap:8px">
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
  <h1>Privacy Policy</h1>
  <div class="lg-hero-meta">Last updated: <strong>31 May 2026</strong> &nbsp;&middot;&nbsp; Adcellent Biz Sdn Bhd (1511714-V)</div>
</div>

<!-- CONTENT -->
<div class="lg-wrap">

  <!-- Table of Contents -->
  <div class="lg-toc">
    <div class="lg-toc-title">Table of Contents</div>
    <ol>
      <li><a href="#s1">Introduction</a></li>
      <li><a href="#s2">Data We Collect</a></li>
      <li><a href="#s3">How We Use Your Data</a></li>
      <li><a href="#s4">Legal Basis for Processing</a></li>
      <li><a href="#s5">Data Sharing and Disclosure</a></li>
      <li><a href="#s6">Data Retention</a></li>
      <li><a href="#s7">Data Security</a></li>
      <li><a href="#s8">Your Rights</a></li>
      <li><a href="#s9">Cookies</a></li>
      <li><a href="#s10">Changes to This Policy</a></li>
      <li><a href="#s11">Contact Us</a></li>
    </ol>
  </div>

  <!-- Section 1 -->
  <div class="lg-section" id="s1">
    <h2>1. Introduction</h2>
    <p>ESG gen is a Software-as-a-Service platform operated by Adcellent Biz Sdn Bhd (Company No. 1511714-V). This Privacy Policy explains how we collect, use, store, and protect your personal data when you use our platform. By registering or using ESG gen, you agree to the practices described in this Policy.</p>
    <p>We are committed to handling your personal data responsibly and in compliance with the Malaysian Personal Data Protection Act 2010 (Act 709). Please read this Policy carefully. If you have questions, contact us using the details in Section 11.</p>
  </div>

  <!-- Section 2 -->
  <div class="lg-section" id="s2">
    <h2>2. Data We Collect</h2>
    <p>We collect the following categories of data when you use the ESG gen platform:</p>

    <h3>Account Data</h3>
    <ul>
      <li>Full name and email address</li>
      <li>Hashed password (your password is never stored in plaintext)</li>
      <li>User role (e.g., SME owner, consultant, manager)</li>
      <li>Account creation date and last login timestamp</li>
    </ul>

    <h3>Company Data</h3>
    <ul>
      <li>Company name and registration number</li>
      <li>Industry sector and Bursa Malaysia sector classification</li>
      <li>Employee count and revenue tier</li>
      <li>ESG framework selected and reporting year</li>
    </ul>

    <h3>ESG &amp; Operational Data</h3>
    <ul>
      <li>ESG indicator values entered via the platform</li>
      <li>Carbon emission figures (Scope 1, 2, and 3)</li>
      <li>Gap analysis results and action plans</li>
      <li>KPI data, benchmarking results, and consultant notes</li>
    </ul>

    <h3>Usage Data</h3>
    <ul>
      <li>Activity logs: page accessed, action performed, and timestamp</li>
      <li>Session identifiers</li>
      <li>IP address and browser user-agent</li>
    </ul>
  </div>

  <!-- Section 3 -->
  <div class="lg-section" id="s3">
    <h2>3. How We Use Your Data</h2>
    <ul>
      <li>To provide, operate, and maintain the ESG gen platform</li>
      <li>To process ESG data and generate compliance reports aligned with supported frameworks</li>
      <li>To send transactional communications including account setup confirmations, subscription notices, and password resets</li>
      <li>To monitor platform security, detect abuse, and prevent unauthorised access</li>
      <li>To aggregate and anonymise data for platform analytics and continuous improvement</li>
    </ul>
    <div class="lg-callout">
      <strong>We do NOT sell your personal data.</strong> We do NOT use your data for advertising or share it with any advertising network.
    </div>
  </div>

  <!-- Section 4 -->
  <div class="lg-section" id="s4">
    <h2>4. Legal Basis for Processing</h2>
    <ul>
      <li><strong>Contract performance</strong> &mdash; Processing necessary to operate your account and deliver the services you have subscribed to.</li>
      <li><strong>Legitimate interest</strong> &mdash; Processing for platform security, fraud prevention, and improving service quality, where such interests are not overridden by your data protection rights.</li>
      <li><strong>Legal obligation</strong> &mdash; Maintaining audit trails and complying with regulatory requirements under Malaysian law.</li>
    </ul>
  </div>

  <!-- Section 5 -->
  <div class="lg-section" id="s5">
    <h2>5. Data Sharing and Disclosure</h2>

    <h3>Within Your Organisation</h3>
    <p>ESG data is shared between users linked to the same company account, as configured by the account owner. This includes the account owner, editors, and viewers assigned within the platform.</p>

    <h3>Service Providers</h3>
    <ul>
      <li><strong>Hostinger International Ltd.</strong> &mdash; our web hosting and infrastructure provider. Data is stored on servers operated by Hostinger.</li>
      <li><strong>CDN providers (jsDelivr)</strong> &mdash; Bootstrap and Chart.js are loaded from jsDelivr CDN. No personal data is transmitted to jsDelivr.</li>
    </ul>

    <h3>Legal Disclosure</h3>
    <p>We will disclose personal data if required by Malaysian law, a valid court order, or a lawful request from a regulatory authority. We will take reasonable steps to notify you before disclosure where permitted by law.</p>

    <div class="lg-callout">
      <strong>We will never sell or rent your data to third parties</strong> for commercial, marketing, or any other purposes.
    </div>
  </div>

  <!-- Section 6 -->
  <div class="lg-section" id="s6">
    <h2>6. Data Retention</h2>
    <table>
      <thead>
        <tr>
          <th>Data Type</th>
          <th>Retention Period</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Active account data</td>
          <td>Retained while the account is active</td>
        </tr>
        <tr>
          <td>Deleted account data</td>
          <td>Retained for 6 months for recovery and audit purposes, then permanently deleted upon verified request</td>
        </tr>
        <tr>
          <td>Activity logs</td>
          <td>Retained for 24 months</td>
        </tr>
        <tr>
          <td>ESG data and reports</td>
          <td>Retained for up to 7 years to support regulatory audit trail requirements</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Section 7 -->
  <div class="lg-section" id="s7">
    <h2>7. Data Security</h2>
    <p>We implement the following technical and administrative security measures to protect your personal data:</p>
    <ul>
      <li>Passwords are hashed using <strong>bcrypt</strong> &mdash; your original password is never stored in plaintext</li>
      <li><strong>HTTPS (TLS) encryption</strong> is enforced for all data in transit</li>
      <li>Session-based authentication with <strong>CSRF token protection</strong> on all state-changing requests</li>
      <li>Session data is stored server-side; only a random session identifier is stored client-side</li>
      <li>Regular access control reviews and principle of least privilege</li>
    </ul>
    <p>No system is completely secure. If you suspect a security breach involving your account or our platform, contact us immediately at <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>.</p>
  </div>

  <!-- Section 8 -->
  <div class="lg-section" id="s8">
    <h2>8. Your Rights</h2>
    <p>Under the Malaysian Personal Data Protection Act 2010 and this Policy, you have the following rights:</p>
    <ul>
      <li><strong>Right to access</strong> &mdash; Request a copy of the personal data we hold about you</li>
      <li><strong>Right to correction</strong> &mdash; Request correction of inaccurate, incomplete, or out-of-date personal data</li>
      <li><strong>Right to deletion</strong> &mdash; Request deletion of your personal data, subject to legal retention obligations</li>
      <li><strong>Right to withdraw consent</strong> &mdash; Withdraw consent to data processing at any time, subject to contractual and legal obligations</li>
    </ul>
    <p>Please see our <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a> for full details of your rights under Act 709. To submit a request, contact us at <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>.</p>
  </div>

  <!-- Section 9 -->
  <div class="lg-section" id="s9">
    <h2>9. Cookies</h2>
    <p>We use a single session cookie (<code>adcellent_session</code>) to maintain your authenticated login state between page requests. This cookie is:</p>
    <ul>
      <li><strong>Strictly necessary</strong> &mdash; the Platform cannot function without it</li>
      <li><strong>First-party</strong> &mdash; set by ESG gen, not by any third party</li>
      <li><strong>Session-scoped</strong> &mdash; deleted when you close your browser or log out</li>
    </ul>
    <p>No personal data is stored inside the cookie itself &mdash; it contains only a random identifier that references your server-side session data. Please see our <a href="<?= APP_URL ?>/cookies">Cookie Policy</a> for full details.</p>
  </div>

  <!-- Section 10 -->
  <div class="lg-section" id="s10">
    <h2>10. Changes to This Policy</h2>
    <p>We may update this Privacy Policy periodically to reflect changes in our practices, technology, or legal obligations. The &ldquo;Last updated&rdquo; date at the top of this page indicates when the Policy was last revised.</p>
    <p>Material changes will be communicated via email notification or an in-platform notice. Continued use of the Platform after a material change constitutes your acceptance of the revised Policy.</p>
  </div>

  <!-- Section 11 -->
  <div class="lg-section" id="s11">
    <h2>11. Contact Us</h2>
    <p>For data access requests, correction requests, deletion requests, or privacy-related complaints, please contact us:</p>
    <ul>
      <li><strong>Organisation:</strong> Adcellent Biz Sdn Bhd (1511714-V)</li>
      <li><strong>Address:</strong> D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi, 59200 Kuala Lumpur, Malaysia</li>
      <li><strong>Email:</strong> <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a></li>
      <li><strong>Phone:</strong> <a href="tel:+60113318460">+6011-3318 4600</a></li>
    </ul>
    <p>You may also lodge a complaint with the Personal Data Protection Commissioner of Malaysia if you believe your personal data rights have been violated.</p>
  </div>

</div>

<!-- FOOTER -->
<footer class="lg-footer">
  <div class="lg-footer-links">
    <a href="<?= APP_URL ?>/privacy" class="lg-active">Privacy Policy</a>
    <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a>
    <a href="<?= APP_URL ?>/terms">Terms of Use</a>
    <a href="<?= APP_URL ?>/cookies">Cookie Policy</a>
  </div>
  <div class="lg-footer-copy">&copy; <?= date('Y') ?> Adcellent Biz Sdn Bhd (1511714-V). All rights reserved.</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
