<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';

Auth::startSession();

$pageTitle  = 'Pricing';
$plans      = Subscription::getAllPlans();
$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html lang="en-MY">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Primary SEO -->
  <title>ESG Pricing Plans | Free, RM 1,500/yr, RM 8,000/report — AiServe ESG OS</title>
  <meta name="description" content="Compare AiServe ESG OS plans. Free Bursa SEDG reporting, RM 1,500/year for full ESG data collection OS, or RM 8,000 professional ESG consultation. No credit card required.">
  <meta name="keywords" content="ESG pricing Malaysia, Bursa SEDG subscription, ESG software cost Malaysia, ESG consultant Malaysia, SME ESG reporting price">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= APP_URL ?>/pricing">

  <!-- Open Graph -->
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= APP_URL ?>/pricing">
  <meta property="og:site_name"   content="AiServe ESG OS">
  <meta property="og:locale"      content="en_MY">
  <meta property="og:title"       content="ESG Pricing | Free, RM 1,500/yr, RM 8,000/report — AiServe ESG OS">
  <meta property="og:description" content="Free Bursa SEDG reporting for Malaysian SMEs. Upgrade to Platform for RM 1,500/year. Professional ESG consultation at RM 8,000/report.">
  <meta property="og:image"       content="<?= APP_URL ?>/assets/img/og-aiserve.png">

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="ESG Pricing | Free, RM 1,500/yr, RM 8,000/report — AiServe ESG OS">
  <meta name="twitter:description" content="Free Bursa SEDG reporting. Full ESG OS at RM 1,500/yr. Professional consultation at RM 8,000/report.">
  <meta name="twitter:image"       content="<?= APP_URL ?>/assets/img/og-aiserve.png">

  <!-- Favicon -->
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">

  <!-- Schema.org pricing structured data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "AiServe ESG OS Pricing",
    "url": "<?= APP_URL ?>/pricing",
    "description": "Pricing plans for AiServe ESG OS — Malaysia's ESG reporting platform for SMEs.",
    "mainEntity": {
      "@type": "ItemList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Free Plan", "description": "15 mandatory Bursa SEDG indicators at no cost" },
        { "@type": "ListItem", "position": 2, "name": "Platform — RM 1,500/year", "description": "Full ESG data collection OS across all frameworks" },
        { "@type": "ListItem", "position": 3, "name": "Consultation — RM 8,000/report", "description": "Professional ESG report review by certified associates" }
      ]
    }
  }
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }

/* ── Navbar ── */
.top-nav { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 12px 28px;
           display: flex; align-items: center; justify-content: space-between; }
.top-nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.top-nav-icon  { background: #16a34a; color: #fff; width: 34px; height: 34px; border-radius: 9px;
                 display: flex; align-items: center; justify-content: center; font-size: 17px; }
.top-nav-name  { font-size: 16px; font-weight: 700; color: #0f172a; }

/* ── Hero ── */
.pricing-hero { text-align: center; padding: 56px 20px 40px; }
.pricing-hero .eyebrow {
  display: inline-flex; align-items: center; gap: 6px;
  background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
  padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 18px;
}
.pricing-hero h1 { font-size: 2.6rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
.pricing-hero p  { font-size: 1.05rem; color: #64748b; max-width: 520px; margin: 0 auto; }

/* ── Journey strip ── */
.journey-strip {
  display: flex; align-items: center; justify-content: center; gap: 0;
  max-width: 780px; margin: 0 auto 48px; flex-wrap: wrap;
}
.journey-step {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
  padding: 12px 20px; font-size: 13px;
}
.journey-step .js-num {
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; flex-shrink: 0;
}
.journey-step .js-label { font-weight: 600; color: #1e293b; }
.journey-step .js-sub   { font-size: 11px; color: #94a3b8; }
.journey-arrow { font-size: 18px; color: #cbd5e1; padding: 0 8px; }

/* ── Plan cards ── */
.plans-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  max-width: 1020px;
  margin: 0 auto 60px;
  padding: 0 20px;
}
@media(max-width: 900px) { .plans-grid { grid-template-columns: 1fr; } }

.plan-card {
  background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px;
  overflow: hidden; display: flex; flex-direction: column;
  transition: box-shadow .2s, transform .2s;
}
.plan-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,.10); transform: translateY(-2px); }
.plan-card.popular { border-color: #0ea5e9; box-shadow: 0 4px 24px rgba(14,165,233,.15); }
.plan-card.consultation { border-color: #8b5cf6; }

.plan-card-top { padding: 24px 24px 20px; }
.popular-tag {
  display: inline-flex; align-items: center; gap: 5px;
  background: #0ea5e9; color: #fff;
  font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
  margin-bottom: 12px;
}
.consultation-tag {
  display: inline-flex; align-items: center; gap: 5px;
  background: #8b5cf6; color: #fff;
  font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
  margin-bottom: 12px;
}
.plan-name    { font-size: 20px; font-weight: 800; color: #0f172a; }
.plan-tagline { font-size: 12px; color: #94a3b8; margin-top: 2px; margin-bottom: 18px; }

.plan-price { display: flex; align-items: baseline; gap: 4px; margin-bottom: 4px; }
.plan-currency { font-size: 18px; font-weight: 700; color: #64748b; }
.plan-amount   { font-size: 40px; font-weight: 900; color: #0f172a; line-height: 1; }
.plan-period   { font-size: 14px; color: #94a3b8; }
.plan-monthly  { font-size: 12px; color: #94a3b8; margin-bottom: 20px; }

.plan-divider  { border: none; border-top: 1px solid #f1f5f9; margin: 0 0 18px; }

.plan-features { list-style: none; padding: 0; margin: 0 0 20px; flex: 1; }
.plan-features li {
  display: flex; align-items: flex-start; gap: 9px;
  font-size: 13px; color: #374151; padding: 5px 0;
}
.plan-features li i.ok   { color: #16a34a; margin-top: 1px; flex-shrink: 0; }
.plan-features li i.no   { color: #d1d5db; margin-top: 1px; flex-shrink: 0; }
.plan-features li.locked { color: #9ca3af; }

.plan-cta {
  margin: 0 24px 24px; padding: 11px; border-radius: 10px;
  font-size: 14px; font-weight: 700; text-align: center;
  text-decoration: none; display: block; transition: opacity .15s;
}
.plan-cta:hover { opacity: .88; }
.cta-free    { background: #f1f5f9; color: #334155; border: 1.5px solid #e2e8f0; }
.cta-platform { background: #0ea5e9; color: #fff; }
.cta-consult { background: #8b5cf6; color: #fff; }

.plan-trial  { text-align: center; font-size: 11px; color: #94a3b8; padding-bottom: 16px; }

/* Consultation card extras */
.consult-who {
  margin: 4px 24px 16px;
  background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px;
  padding: 10px 14px; font-size: 12px; color: #6b21a8;
}
.consult-who strong { display: block; margin-bottom: 4px; }

/* ── FAQ ── */
.faq-wrap   { max-width: 820px; margin: 0 auto 60px; padding: 0 20px; }
.faq-wrap h2 { text-align: center; font-size: 1.6rem; font-weight: 800; margin-bottom: 28px; }
.faq-card   { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
              padding: 18px 22px; margin-bottom: 12px; }
.faq-q { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.faq-a { font-size: 13px; color: #64748b; line-height: 1.6; }

/* ── Footer ── */
.pricing-footer { text-align: center; padding: 24px; font-size: 12px; color: #94a3b8;
                  border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="top-nav">
  <a class="top-nav-brand" href="<?= APP_URL ?>">
    <span class="top-nav-icon"><i class="bi bi-leaf-fill"></i></span>
    <span class="top-nav-name"><?= APP_NAME ?></span>
  </a>
  <div class="d-flex gap-2">
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-primary btn-sm">Dashboard</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/login"    class="btn btn-outline-secondary btn-sm">Login</a>
    <a href="<?= APP_URL ?>/register" class="btn btn-success btn-sm">Start Free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<div class="pricing-hero">
  <div class="eyebrow"><i class="bi bi-shield-check"></i> Malaysia ESG Compliance Platform</div>
  <h1>Transparent, simple pricing</h1>
  <p>Start free with all mandatory indicators. Subscribe for full data collection and report generation. Engage a consultant for professional review.</p>
</div>

<!-- Journey strip -->
<div class="journey-strip">
  <div class="journey-step">
    <div class="js-num" style="background:#f0fdf4;color:#16a34a">1</div>
    <div>
      <div class="js-label">Sign Up Free</div>
      <div class="js-sub">All 15 mandatory indicators</div>
    </div>
  </div>
  <div class="journey-arrow"><i class="bi bi-arrow-right"></i></div>
  <div class="journey-step">
    <div class="js-num" style="background:#f0f9ff;color:#0369a1">2</div>
    <div>
      <div class="js-label">Subscribe Platform</div>
      <div class="js-sub">Full OS + report generation</div>
    </div>
  </div>
  <div class="journey-arrow"><i class="bi bi-arrow-right"></i></div>
  <div class="journey-step">
    <div class="js-num" style="background:#faf5ff;color:#7c3aed">3</div>
    <div>
      <div class="js-label">Engage Consultant</div>
      <div class="js-sub">Expert review & submission</div>
    </div>
  </div>
</div>

<!-- Plan cards -->
<div class="plans-grid">

  <!-- FREE -->
  <div class="plan-card">
    <div class="plan-card-top">
      <div class="plan-name">Free</div>
      <div class="plan-tagline">Start your ESG journey — no credit card needed</div>
      <div class="plan-price">
        <span class="plan-amount" style="color:#16a34a">Free</span>
      </div>
      <div class="plan-monthly">Forever free for SMEs</div>
    </div>
    <hr class="plan-divider">
    <div style="padding: 0 24px; flex:1">
      <ul class="plan-features">
        <li><i class="bi bi-check-circle-fill ok"></i>All 15 mandatory Bursa SEDG indicators</li>
        <li><i class="bi bi-check-circle-fill ok"></i>ESG score dashboard</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Basic ESG report generation</li>
        <li><i class="bi bi-check-circle-fill ok"></i>1 company profile</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Carbon calculator (view only)</li>
        <li class="locked"><i class="bi bi-x-circle-fill no"></i>Full indicator data collection OS</li>
        <li class="locked"><i class="bi bi-x-circle-fill no"></i>Advanced PDF report export</li>
        <li class="locked"><i class="bi bi-x-circle-fill no"></i>Gap analysis & recommendations</li>
        <li class="locked"><i class="bi bi-x-circle-fill no"></i>GRI, ISSB, ESRS frameworks</li>
      </ul>
    </div>
    <a href="<?= $isLoggedIn ? APP_URL.'/dashboard' : APP_URL.'/register' ?>" class="plan-cta cta-free">
      <i class="bi bi-rocket-takeoff me-1"></i>Get Started Free
    </a>
  </div>

  <!-- PLATFORM -->
  <div class="plan-card popular">
    <div class="plan-card-top">
      <div class="popular-tag"><i class="bi bi-star-fill"></i> Most Popular</div>
      <div class="plan-name">Platform</div>
      <div class="plan-tagline">Full ESG data collection OS + report generation</div>
      <div class="plan-price">
        <span class="plan-currency">RM</span>
        <span class="plan-amount">1,500</span>
        <span class="plan-period">/ year</span>
      </div>
      <div class="plan-monthly">≈ RM 125 / month &bull; 14-day free trial</div>
    </div>
    <hr class="plan-divider">
    <div style="padding: 0 24px; flex:1">
      <ul class="plan-features">
        <li><i class="bi bi-check-circle-fill ok"></i>All indicators across all frameworks</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Full ESG data collection on the OS</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Full report generation & PDF export</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Gap analysis & prioritised action plan</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Carbon calculator (Scope 1, 2 & 3)</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Benchmarking vs industry peers</li>
        <li><i class="bi bi-check-circle-fill ok"></i>GRI, ISSB, ESRS, CDP, TCFD frameworks</li>
        <li><i class="bi bi-check-circle-fill ok"></i>1 company profile</li>
        <li><i class="bi bi-check-circle-fill ok"></i>Email support</li>
      </ul>
    </div>
    <a href="<?= $isLoggedIn ? APP_URL.'/billing' : APP_URL.'/register' ?>" class="plan-cta cta-platform">
      <i class="bi bi-lightning-charge-fill me-1"></i>Subscribe — RM 1,500/year
    </a>
    <div class="plan-trial"><i class="bi bi-gift me-1"></i>14-day free trial — cancel anytime</div>
  </div>

  <!-- CONSULTATION -->
  <div class="plan-card consultation">
    <div class="plan-card-top">
      <div class="consultation-tag"><i class="bi bi-person-check-fill"></i> Professional Service</div>
      <div class="plan-name">Consultation</div>
      <div class="plan-tagline">Expert review, validation & submission guidance</div>
      <div class="plan-price">
        <span class="plan-currency">RM</span>
        <span class="plan-amount" style="color:#7c3aed">8,000</span>
        <span class="plan-period">/ report</span>
      </div>
      <div class="plan-monthly">One-time per engagement &bull; Platform subscription required</div>
    </div>
    <hr class="plan-divider">
    <div style="padding: 0 24px; flex:1">
      <ul class="plan-features">
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Professional review of your ESG report</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Data validation & quality check</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Gap remediation recommendations</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Regulatory submission guidance</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>1-on-1 consultation session</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Delivered by certified ESG consultant</li>
        <li><i class="bi bi-check-circle-fill" style="color:#8b5cf6;margin-top:1px;flex-shrink:0"></i>Report sign-off & advisory letter</li>
      </ul>
    </div>
    <div class="consult-who">
      <strong><i class="bi bi-people-fill me-1"></i>Who delivers this?</strong>
      Our certified associate consultants — accountants, ESG advisers, and sustainability professionals trained on Bursa SEDG requirements.
    </div>
    <a href="mailto:hello@aiserve.my?subject=Consultation Enquiry" class="plan-cta cta-consult">
      <i class="bi bi-envelope-fill me-1"></i>Enquire Now
    </a>
  </div>

</div>

<!-- FAQ -->
<div class="faq-wrap">
  <h2>Frequently Asked Questions</h2>

  <?php
  $faqs = [
    [
      'q' => 'What is included in the Free plan?',
      'a' => 'The Free plan gives all Malaysian SMEs access to all 15 mandatory Bursa SEDG indicators, an ESG score dashboard, and basic report generation — completely free, forever. No credit card required. It is designed to help SMEs understand their ESG baseline before committing to a subscription.',
    ],
    [
      'q' => 'What does the Platform subscription (RM 1,500/year) add?',
      'a' => 'The Platform subscription unlocks the full ESG data collection OS — all indicators across all major frameworks (Bursa SEDG, GRI, ISSB, ESRS, CDP, TCFD), full PDF report generation, gap analysis with a prioritised action plan, carbon calculator (Scope 1, 2 & 3), and industry benchmarking. It is the complete ESG management system for your company.',
    ],
    [
      'q' => 'What is the Consultation service and who needs it?',
      'a' => 'The Consultation service (RM 8,000/report) is a professional service — not a software subscription. A certified ESG associate will review your generated report, validate your data, identify gaps, and guide you through regulatory submission. It is recommended for companies preparing a formal Bursa submission, seeking investor-grade reporting, or first-time reporters who want expert assurance.',
    ],
    [
      'q' => 'Do I need a Platform subscription to engage a Consultant?',
      'a' => 'Yes. The consultant reviews the report you generate on the platform. The Platform subscription (RM 1,500/year) must be active to access full report generation before engaging a consultant.',
    ],
    [
      'q' => 'How do I activate my subscription?',
      'a' => 'Contact us at hello@aiserve.my or speak to your assigned consultant. We issue an invoice and activate your account within 1 business day. Online self-serve payment is coming soon.',
    ],
    [
      'q' => 'What happens to my data if I do not renew?',
      'a' => 'Your company data is never deleted. If your subscription lapses, you return to the Free plan — your data remains intact and accessible with the 15 standard indicators. You can re-subscribe at any time to regain full access.',
    ],
  ];
  foreach ($faqs as $faq): ?>
  <div class="faq-card">
    <div class="faq-q"><i class="bi bi-question-circle-fill text-primary me-2"></i><?= $faq['q'] ?></div>
    <div class="faq-a"><?= $faq['a'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Footer -->
<div class="pricing-footer">
  © <?= date('Y') ?> AiServe Sdn Bhd &bull; hello@aiserve.my &bull; All prices in Malaysian Ringgit (MYR) &bull; SST may apply
</div>

</body>
</html>
