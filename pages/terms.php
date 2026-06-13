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
<title>Terms of Use | ESG gen</title>
<meta name="description" content="ESG gen terms of use — subscription terms, acceptable use policy, liability limitations, and governing law. Adcellent Biz Sdn Bhd (1511714-V), Kuala Lumpur, Malaysia.">
<meta name="keywords" content="ESG gen terms of use, ESG platform terms Malaysia, acceptable use policy, Adcellent terms, SME ESG terms">
<meta name="author" content="ESG gen — Adcellent Biz Sdn Bhd">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= APP_URL ?>/terms">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= APP_URL ?>/terms">
<meta property="og:site_name" content="ESG gen">
<meta property="og:locale" content="en_MY">
<meta property="og:title" content="Terms of Use | ESG gen">
<meta property="og:description" content="Terms of use for ESG gen — subscription plans, acceptable use, data ownership, and governing law (Malaysia).">
<meta property="og:image" content="<?= APP_URL ?>/assets/img/og-esggen.png">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Terms of Use | ESG gen">
<meta name="twitter:description" content="ESG gen platform terms — subscription, acceptable use, liability, Malaysian law.">
<link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Terms of Use","url":"<?= APP_URL ?>/terms","description":"Terms of Use for ESG gen — Malaysia's ESG reporting platform by Adcellent Biz Sdn Bhd.","inLanguage":"en-MY","isPartOf":{"@type":"WebSite","name":"ESG gen","url":"<?= APP_URL ?>"},"publisher":{"@type":"Organization","name":"Adcellent Biz Sdn Bhd","url":"<?= APP_URL ?>"}}
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
         onerror="this.style.display='none';document.getElementById('lgNavFallback3').style.display='flex'">
    <span id="lgNavFallback3" style="display:none;align-items:center;gap:8px">
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
  <h1>Terms of Use</h1>
  <div class="lg-hero-meta">Last updated: <strong>31 May 2026</strong> &nbsp;&middot;&nbsp; Adcellent Biz Sdn Bhd (1511714-V)</div>
</div>

<!-- CONTENT -->
<div class="lg-wrap">

  <!-- Table of Contents -->
  <div class="lg-toc">
    <div class="lg-toc-title">Table of Contents</div>
    <ol>
      <li><a href="#s1">Acceptance of Terms</a></li>
      <li><a href="#s2">Description of Service</a></li>
      <li><a href="#s3">Account Registration and Security</a></li>
      <li><a href="#s4">Subscription Plans and Billing</a></li>
      <li><a href="#s5">Acceptable Use Policy</a></li>
      <li><a href="#s6">Intellectual Property</a></li>
      <li><a href="#s7">Your Data</a></li>
      <li><a href="#s8">Disclaimer of Warranties</a></li>
      <li><a href="#s9">Limitation of Liability</a></li>
      <li><a href="#s10">Termination</a></li>
      <li><a href="#s11">Amendments</a></li>
      <li><a href="#s12">Governing Law and Dispute Resolution</a></li>
      <li><a href="#s13">Contact</a></li>
    </ol>
  </div>

  <!-- Section 1 -->
  <div class="lg-section" id="s1">
    <h2>1. Acceptance of Terms</h2>
    <p>By accessing or using the ESG gen platform (&ldquo;Platform&rdquo;), you agree to be bound by these Terms of Use (&ldquo;Terms&rdquo;) and our <a href="<?= APP_URL ?>/privacy">Privacy Policy</a> and <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a>, which are incorporated herein by reference. If you do not agree to these Terms, do not access or use the Platform.</p>
    <p>These Terms form a legally binding agreement between you (&ldquo;User&rdquo;) and <strong>Adcellent Biz Sdn Bhd (1511714-V)</strong> (&ldquo;we&rdquo;, &ldquo;us&rdquo;, or &ldquo;our&rdquo;). Your continued use of the Platform constitutes your ongoing acceptance of these Terms as amended from time to time.</p>
  </div>

  <!-- Section 2 -->
  <div class="lg-section" id="s2">
    <h2>2. Description of Service</h2>
    <p>ESG gen is a cloud-based ESG (Environmental, Social, and Governance) reporting platform designed for Malaysian SMEs, consulting firms, and ESG practitioners. The Platform provides tools for:</p>
    <ul>
      <li>ESG data collection and indicator tracking</li>
      <li>Gap analysis against regulatory and voluntary ESG frameworks</li>
      <li>Carbon footprint calculation (Scope 1, 2, and 3)</li>
      <li>Industry benchmarking and peer comparison</li>
      <li>ESG report generation</li>
    </ul>
    <p>The Platform supports alignment with Bursa Malaysia SEDG, GRI, ISSB, CDP, TCFD, ESRS, and other recognised frameworks. The scope and features available to you depend on your subscription plan.</p>
  </div>

  <!-- Section 3 -->
  <div class="lg-section" id="s3">
    <h2>3. Account Registration and Security</h2>
    <ul>
      <li>You must provide accurate, current, and complete information during registration and keep it updated.</li>
      <li>You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account.</li>
      <li>You must notify us immediately of any suspected unauthorised access to your account at <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>.</li>
      <li>You may not share your credentials or permit another person to use your account.</li>
      <li>Each user account is for a single individual. Organisation-level access is managed through the multi-user company account features within the Platform.</li>
      <li>We reserve the right to suspend or terminate accounts that violate these Terms or that we reasonably suspect have been compromised.</li>
    </ul>
  </div>

  <!-- Section 4 -->
  <div class="lg-section" id="s4">
    <h2>4. Subscription Plans and Billing</h2>
    <p>ESG gen offers the following subscription plans:</p>
    <table>
      <thead>
        <tr>
          <th>Plan</th>
          <th>Price</th>
          <th>Includes</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Starter (Free)</strong></td>
          <td>Free</td>
          <td>Access to the 15 mandatory Bursa SEDG indicators. No credit card required.</td>
        </tr>
        <tr>
          <td><strong>Standard Platform</strong></td>
          <td>RM 1,500 / year</td>
          <td>Full access to 200+ indicators across all supported frameworks; full report generation; gap analysis; carbon calculator; and industry benchmarking.</td>
        </tr>
        <tr>
          <td><strong>Professional Consultation</strong></td>
          <td>RM 8,000 / report</td>
          <td>Expert ESG associate review, data validation, and regulatory submission guidance.</td>
        </tr>
      </tbody>
    </table>
    <ul>
      <li>Subscription fees are billed annually in Malaysian Ringgit (MYR). Payment must be made within 14 days of invoice.</li>
      <li>We reserve the right to adjust pricing with at least 30 days&rsquo; prior written notice.</li>
      <li>Fees paid are non-refundable except as required by applicable Malaysian consumer law.</li>
      <li>Subscriptions auto-renew unless cancelled at least 7 days before the renewal date.</li>
      <li>We reserve the right to suspend access where payment is overdue by more than 14 days.</li>
    </ul>
    <div class="lg-callout">All prices are exclusive of applicable taxes, including Goods and Services Tax (GST) or Sales and Services Tax (SST) if and when applicable.</div>
  </div>

  <!-- Section 5 -->
  <div class="lg-section" id="s5">
    <h2>5. Acceptable Use Policy</h2>
    <p>You agree <strong>NOT</strong> to:</p>
    <ol type="a">
      <li>Use the Platform for any unlawful purpose or in violation of any applicable law or regulation;</li>
      <li>Reverse-engineer, decompile, disassemble, or otherwise attempt to derive the source code of any part of the Platform;</li>
      <li>Resell, sublicense, or commercially exploit the Platform or its outputs without prior written consent from Adcellent Biz Sdn Bhd;</li>
      <li>Upload, transmit, or introduce malicious code, viruses, trojans, worms, or any harmful content to the Platform;</li>
      <li>Attempt to gain unauthorised access to any part of the Platform, its infrastructure, or other users&rsquo; accounts;</li>
      <li>Submit false, misleading, or fraudulent ESG data, or use the Platform to produce misleading ESG disclosures;</li>
      <li>Use the Platform in any manner that could damage, overburden, impair, or disrupt its normal operation.</li>
    </ol>
    <p>We reserve the right to investigate suspected violations and to suspend or permanently terminate access for violation of this Acceptable Use Policy, without prior notice where necessary to protect the Platform or other users.</p>
  </div>

  <!-- Section 6 -->
  <div class="lg-section" id="s6">
    <h2>6. Intellectual Property</h2>
    <p>The Platform &mdash; including all software, source code, user interface designs, content, ESG framework configurations, indicator libraries, scoring algorithms, and branding &mdash; is owned by or licensed to Adcellent Biz Sdn Bhd and is protected by Malaysian and international intellectual property laws.</p>
    <p>You are granted a limited, non-exclusive, non-transferable, revocable licence to use the Platform solely for its intended purpose in accordance with these Terms. This licence does not include the right to:</p>
    <ul>
      <li>Copy, reproduce, or distribute any part of the Platform;</li>
      <li>Create derivative works based on the Platform;</li>
      <li>Remove or obscure any proprietary notices or labels.</li>
    </ul>
    <p>Nothing in these Terms transfers or assigns any intellectual property rights to you.</p>
  </div>

  <!-- Section 7 -->
  <div class="lg-section" id="s7">
    <h2>7. Your Data</h2>
    <p>You retain full ownership of all ESG data, company data, and reports you create or upload to the Platform (&ldquo;Your Data&rdquo;). You grant us a limited, non-exclusive licence to process Your Data solely for the purpose of providing and improving the Platform services.</p>
    <ul>
      <li>We do not claim ownership of Your Data.</li>
      <li>You are responsible for ensuring Your Data is accurate, complete, and lawfully obtained.</li>
      <li>Upon account termination, you may request an export of Your Data within 30 days of termination. After this period, data may be deleted in accordance with our <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>.</li>
    </ul>
    <div class="lg-callout"><strong>We do not use Your Data for advertising, and we do not sell it to third parties.</strong></div>
  </div>

  <!-- Section 8 -->
  <div class="lg-section" id="s8">
    <h2>8. Disclaimer of Warranties</h2>
    <p>The Platform is provided &ldquo;as is&rdquo; and &ldquo;as available&rdquo; without warranty of any kind, express or implied. To the maximum extent permitted by Malaysian law, we make no warranties that:</p>
    <ol type="a">
      <li>The Platform will be uninterrupted, timely, secure, or error-free;</li>
      <li>Results, reports, or calculations generated via the Platform will meet all regulatory requirements without independent professional review;</li>
      <li>The indicator data, benchmarks, or framework configurations are exhaustive, complete, or current.</li>
    </ol>
    <div class="lg-callout"><strong>Important:</strong> ESG reports generated via the Platform should be reviewed by a qualified ESG practitioner before submission to Bursa Malaysia or any other regulatory body. ESG gen is a tool to support reporting — it does not constitute professional advice.</div>
  </div>

  <!-- Section 9 -->
  <div class="lg-section" id="s9">
    <h2>9. Limitation of Liability</h2>
    <p>To the maximum extent permitted by Malaysian law, Adcellent Biz Sdn Bhd and its directors, employees, and agents shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from or related to your use of or inability to use the Platform, even if we have been advised of the possibility of such damages.</p>
    <p>Our total aggregate liability to you for all claims arising from these Terms or your use of the Platform shall not exceed the total amount paid by you for the Platform in the <strong>12 months immediately preceding the claim</strong>.</p>
    <p>Nothing in these Terms excludes or limits liability for:</p>
    <ul>
      <li>Death or personal injury caused by our negligence;</li>
      <li>Fraud or fraudulent misrepresentation;</li>
      <li>Any liability that cannot be excluded under applicable Malaysian law.</li>
    </ul>
  </div>

  <!-- Section 10 -->
  <div class="lg-section" id="s10">
    <h2>10. Termination</h2>
    <p>You may terminate your account at any time by contacting us at <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a>. We will process your request within a reasonable time.</p>
    <p>We may suspend or terminate your access to the Platform immediately, with or without notice, if:</p>
    <ul>
      <li>You breach any provision of these Terms;</li>
      <li>Subscription fees remain unpaid beyond the 14-day payment period;</li>
      <li>We reasonably believe your use of the Platform is harmful to the Platform, other users, or third parties;</li>
      <li>We are required to do so by law or regulatory authority.</li>
    </ul>
    <p>Upon termination for any reason, your licence to use the Platform ceases immediately. Provisions of these Terms that by their nature should survive termination (including Sections 6, 7, 8, 9, 12) shall survive.</p>
  </div>

  <!-- Section 11 -->
  <div class="lg-section" id="s11">
    <h2>11. Amendments</h2>
    <p>We reserve the right to modify these Terms at any time. Material changes will be communicated via email notification to your registered address or via an in-platform notice, at least <strong>14 days before</strong> the changes take effect.</p>
    <p>Continued use of the Platform after the effective date of any change constitutes your acceptance of the revised Terms. If you do not agree with a material change, you may terminate your account before the effective date.</p>
  </div>

  <!-- Section 12 -->
  <div class="lg-section" id="s12">
    <h2>12. Governing Law and Dispute Resolution</h2>
    <p>These Terms shall be governed by and construed in accordance with the laws of <strong>Malaysia</strong>, without regard to conflict of law principles.</p>
    <p>Any dispute, controversy, or claim arising out of or in connection with these Terms, or the breach, termination, or invalidity thereof, shall first be attempted to be resolved through good-faith negotiation between the parties. If the dispute is not resolved within <strong>30 days</strong> of written notice, the dispute shall be submitted to the exclusive jurisdiction of the <strong>courts of Kuala Lumpur, Malaysia</strong>.</p>
  </div>

  <!-- Section 13 -->
  <div class="lg-section" id="s13">
    <h2>13. Contact</h2>
    <p>For questions about these Terms or to contact us regarding your account:</p>
    <ul>
      <li><strong>Organisation:</strong> Adcellent Biz Sdn Bhd (1511714-V)</li>
      <li><strong>Address:</strong> D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi, 59200 Kuala Lumpur, Malaysia</li>
      <li><strong>Email:</strong> <a href="mailto:hello@adcellent.com.my">hello@adcellent.com.my</a></li>
      <li><strong>Phone:</strong> <a href="tel:+60113318460">+6011-3318 4600</a></li>
    </ul>
  </div>

</div>

<!-- FOOTER -->
<footer class="lg-footer">
  <div class="lg-footer-links">
    <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>
    <a href="<?= APP_URL ?>/pdpa">PDPA Notice</a>
    <a href="<?= APP_URL ?>/terms" class="lg-active">Terms of Use</a>
    <a href="<?= APP_URL ?>/cookies">Cookie Policy</a>
  </div>
  <div class="lg-footer-copy">&copy; <?= date('Y') ?> Adcellent Biz Sdn Bhd (1511714-V). All rights reserved.</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
