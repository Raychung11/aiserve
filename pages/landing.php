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
  <title>ESG gen | Free ESG Reporting Platform for Malaysian SMEs</title>
  <meta name="description" content="Malaysia's ESG reporting platform for SMEs. Start free with 15 mandatory Bursa SEDG indicators. Full data collection OS at RM 1,500/yr. Expert ESG consultation available.">
  <meta name="keywords" content="ESG reporting Malaysia, Bursa SEDG, SME ESG platform, ESG compliance Malaysia, Bursa SEDG indicators, Malaysia ESG software, sustainability reporting Malaysia, ESG report generator">
  <meta name="author" content="ESG gen — Adcellent Biz Sdn Bhd">
  <meta name="robots" content="index, follow">
  <meta name="geo.region" content="MY-14">
  <meta name="geo.placename" content="Kuala Lumpur, Malaysia">
  <meta name="geo.position" content="3.1390;101.6869">
  <meta name="ICBM" content="3.1390, 101.6869">
  <link rel="canonical" href="<?= APP_URL ?>/">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= APP_URL ?>/">
  <meta property="og:site_name" content="ESG gen">
  <meta property="og:locale" content="en_MY">
  <meta property="og:title" content="ESG gen | Free ESG Reporting Platform for Malaysian SMEs">
  <meta property="og:description" content="Start free with 15 mandatory Bursa SEDG indicators. Full ESG data collection OS at RM 1,500/yr. Professional ESG consultation at RM 8,000/report.">
  <meta property="og:image" content="<?= APP_URL ?>/assets/img/og-esggen.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="ESG gen | Free ESG Reporting for Malaysian SMEs">
  <meta name="twitter:description" content="Start free with 15 mandatory Bursa SEDG indicators. RM 1,500/yr for full ESG OS.">
  <meta name="twitter:image" content="<?= APP_URL ?>/assets/img/og-esggen.png">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
  <script type="application/ld+json">
  [
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "ESG gen",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web",
      "url": "<?= APP_URL ?>",
      "description": "Malaysia's ESG reporting platform for SMEs. Start free with 15 mandatory Bursa SEDG indicators.",
      "inLanguage": "en-MY",
      "offers": [
        {"@type":"Offer","name":"Starter","price":"0","priceCurrency":"MYR","description":"Free forever — 15 mandatory Bursa SEDG indicators"},
        {"@type":"Offer","name":"Platform","price":"1500","priceCurrency":"MYR","priceSpecification":{"@type":"UnitPriceSpecification","price":"1500","priceCurrency":"MYR","unitCode":"ANN"}},
        {"@type":"Offer","name":"Consultation","price":"8000","priceCurrency":"MYR","description":"Professional ESG report review per report"}
      ],
      "provider": {"@type":"Organization","name":"Adcellent Biz Sdn Bhd","url":"<?= APP_URL ?>"}
    },
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "ESG gen",
      "url": "<?= APP_URL ?>",
      "description": "Malaysia's ESG reporting platform for SMEs.",
      "inLanguage": "en-MY"
    },
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Adcellent Biz Sdn Bhd",
      "legalName": "Adcellent Biz Sdn Bhd (1511714-V)",
      "url": "<?= APP_URL ?>",
      "logo": "<?= APP_URL ?>/assets/img/esggen-logo.png",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+60113318460",
        "contactType": "customer support",
        "email": "hello@adcellent.com.my",
        "availableLanguage": ["English","Malay"]
      },
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "D13-07, Menara Suezcap 1, KL Gateway, Jalan Kerinchi",
        "addressLocality": "Kuala Lumpur",
        "postalCode": "59200",
        "addressCountry": "MY"
      },
      "sameAs": []
    }
  ]
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:#f8fafc;color:#1e293b;overflow-x:hidden}
a{text-decoration:none}

/* ── Scroll reveal ── */
.reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease}
.reveal.visible{opacity:1;transform:none}
.d1{transition-delay:.1s}.d2{transition-delay:.2s}.d3{transition-delay:.3s}.d4{transition-delay:.4s}

/* ── NAV ── */
.lp-nav{position:sticky;top:0;z-index:200;background:rgba(15,23,42,.96);backdrop-filter:blur(12px);
  display:flex;align-items:center;justify-content:space-between;padding:0 32px;height:66px;
  border-bottom:1px solid rgba(255,255,255,.07);transition:background .3s}
.lp-nav.scrolled{background:rgba(15,23,42,1)}
.nav-brand{display:flex;align-items:center;gap:10px}
.nav-logo{background:#16a34a;color:#fff;width:32px;height:32px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.nav-name{font-size:16px;font-weight:800;color:#fff;letter-spacing:-.3px}
.nav-name span{color:#4ade80}
.nav-logo-img{height:50px;width:auto;display:block}
.nav-links{display:flex;gap:28px}
.nav-links a{color:rgba(255,255,255,.65);font-size:13px;font-weight:500;transition:color .15s;position:relative}
.nav-links a:hover,.nav-links a.nav-active{color:#fff}
.nav-links a.nav-active::after{content:'';position:absolute;bottom:-4px;left:0;right:0;height:2px;background:#4ade80;border-radius:2px}
.nav-actions{display:flex;gap:10px;align-items:center}
.btn-nav-login{padding:7px 16px;border-radius:8px;border:1px solid rgba(255,255,255,.25);color:#fff;font-size:13px;font-weight:600;background:transparent;transition:background .15s}
.btn-nav-login:hover{background:rgba(255,255,255,.1);color:#fff}
.btn-nav-cta{padding:7px 18px;border-radius:8px;background:#16a34a;color:#fff;font-size:13px;font-weight:700;transition:background .15s,box-shadow .15s}
.btn-nav-cta:hover{background:#15803d;color:#fff;box-shadow:0 0 20px rgba(22,163,74,.4)}
.hamburger{display:none;background:transparent;border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.8);border-radius:6px;padding:5px 10px;cursor:pointer;font-size:18px;line-height:1}
@media(max-width:820px){
  .hamburger{display:flex;align-items:center;justify-content:center}
  .nav-links{position:fixed;top:66px;left:0;right:0;background:rgba(9,14,27,.98);backdrop-filter:blur(12px);flex-direction:column;gap:0;max-height:0;overflow:hidden;transition:max-height .3s ease;z-index:199;border-bottom:1px solid rgba(255,255,255,.08)}
  .nav-links.open{max-height:280px;padding:8px 0}
  .nav-links a{padding:13px 28px;font-size:14px;border-bottom:1px solid rgba(255,255,255,.04)}
  .nav-links a.nav-active::after{display:none}
}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#0f2d1a 100%);
  padding:110px 20px 90px;text-align:center;position:relative;overflow:hidden}
.hero-grid{position:absolute;inset:0;
  background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
  background-size:44px 44px;pointer-events:none}
.hero-glow{position:absolute;inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(22,163,74,.2) 0%,transparent 70%);pointer-events:none}
.orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
.orb-1{width:400px;height:400px;background:rgba(22,163,74,.12);top:-100px;right:5%;animation:floatOrb 9s ease-in-out infinite}
.orb-2{width:260px;height:260px;background:rgba(14,165,233,.09);bottom:-60px;left:6%;animation:floatOrb 13s ease-in-out infinite reverse}
.orb-3{width:180px;height:180px;background:rgba(139,92,246,.07);top:35%;right:28%;animation:floatOrb 7s ease-in-out infinite;animation-delay:-4s}
@keyframes floatOrb{0%,100%{transform:translateY(0)}50%{transform:translateY(-28px)}}
.hero-eyebrow{display:inline-flex;align-items:center;gap:8px;background:rgba(22,163,74,.15);border:1px solid rgba(74,222,128,.3);
  color:#4ade80;padding:6px 18px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;
  margin-bottom:26px;position:relative}
.eyebrow-fw{display:inline-block;min-width:90px;text-align:left;transition:opacity .3s}
.hero h1{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;color:#fff;line-height:1.12;margin-bottom:22px;position:relative}
.hero h1 .accent{color:#4ade80}
.hero-sub{font-size:1.1rem;color:rgba(255,255,255,.7);max-width:600px;margin:0 auto 38px;line-height:1.75;position:relative}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative;margin-bottom:36px}
.btn-hero-primary{padding:15px 34px;border-radius:10px;background:#16a34a;color:#fff;font-size:15px;font-weight:700;
  transition:background .15s,transform .15s,box-shadow .2s;display:inline-flex;align-items:center;gap:8px}
.btn-hero-primary:hover{background:#15803d;color:#fff;transform:translateY(-2px);box-shadow:0 8px 30px rgba(22,163,74,.35)}
.btn-hero-secondary{padding:15px 34px;border-radius:10px;border:1.5px solid rgba(255,255,255,.3);color:#fff;
  font-size:15px;font-weight:600;background:transparent;transition:background .15s;display:inline-flex;align-items:center;gap:8px}
.btn-hero-secondary:hover{background:rgba(255,255,255,.08);color:#fff}
.hero-trust{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;position:relative}
.hero-trust span{color:rgba(255,255,255,.55);font-size:13px;display:flex;align-items:center;gap:6px}
.hero-trust i{color:#4ade80}

/* ── PREVIEW CARD ── */
.hero-card-wrap{position:relative;margin-top:60px;padding-bottom:0}
.hero-card{max-width:680px;margin:0 auto;background:#1e293b;border-radius:16px 16px 0 0;
  border:1px solid rgba(255,255,255,.1);border-bottom:none;overflow:hidden;
  box-shadow:0 -16px 60px rgba(0,0,0,.4);animation:cardRise .8s ease .4s both}
@keyframes cardRise{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
.card-titlebar{background:#0f172a;padding:10px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid rgba(255,255,255,.07)}
.dot{width:11px;height:11px;border-radius:50%}
.card-url{flex:1;background:rgba(255,255,255,.06);border-radius:5px;padding:3px 10px;font-size:11px;color:#64748b;max-width:240px;margin:0 auto}
.card-body{padding:20px 24px;display:grid;grid-template-columns:140px 1fr;gap:20px;align-items:start}
.card-score-block{text-align:center;background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.2);border-radius:12px;padding:20px 14px}
.card-score-num{font-size:2.8rem;font-weight:900;color:#4ade80;line-height:1}
.card-score-lbl{font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.card-score-sub{font-size:11px;color:#475569;margin-top:8px}
.card-right{display:flex;flex-direction:column;gap:12px}
.card-company{font-size:13px;font-weight:700;color:#f1f5f9;margin-bottom:4px}
.card-bar-row{display:flex;align-items:center;gap:10px}
.card-bar-label{font-size:11px;color:#94a3b8;width:82px;flex-shrink:0}
.card-bar-track{flex:1;height:8px;background:rgba(255,255,255,.08);border-radius:4px;overflow:hidden}
.card-bar-fill{height:8px;border-radius:4px;animation:barGrow 1.2s ease .8s both}
@keyframes barGrow{from{width:0}to{width:var(--w)}}
.card-bar-val{font-size:11px;font-weight:700;width:28px;text-align:right}
.card-pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.card-pill{font-size:10px;font-weight:700;padding:3px 10px;border-radius:6px}

/* ── STATS ── */
.stats-bar{background:#fff;border-bottom:1px solid #e2e8f0;padding:28px 20px}
.stats-inner{max-width:900px;margin:0 auto;display:flex;justify-content:center;align-items:center;flex-wrap:wrap}
.stat-item{text-align:center;padding:0 40px}
.stat-num{font-size:2rem;font-weight:900;color:#0f172a;line-height:1}
.stat-num span{color:#16a34a}
.stat-lbl{font-size:12px;color:#94a3b8;font-weight:600;margin-top:4px}
.stat-div{width:1px;height:40px;background:#e2e8f0;flex-shrink:0}

/* ── JOURNEY ── */
.journey-section{padding:80px 20px;background:#f8fafc}
.section-eyebrow{text-align:center;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#16a34a;margin-bottom:10px}
.section-title{text-align:center;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:#0f172a;margin-bottom:12px}
.section-sub{text-align:center;color:#64748b;font-size:1rem;max-width:520px;margin:0 auto 52px}
.journey-steps{display:flex;justify-content:center;align-items:stretch;max-width:980px;margin:0 auto;flex-wrap:wrap;gap:22px}
.journey-card{flex:1;min-width:260px;max-width:310px;background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;
  padding:28px 24px;position:relative;transition:box-shadow .25s,transform .25s}
.journey-card:hover{box-shadow:0 12px 40px rgba(0,0,0,.1);transform:translateY(-4px)}
.journey-card.j-free{border-top:4px solid #16a34a}
.journey-card.j-platform{border-top:4px solid #0ea5e9}
.journey-card.j-consult{border-top:4px solid #8b5cf6}
.j-step-num{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;margin-bottom:16px}
.j-free .j-step-num{background:#f0fdf4;color:#16a34a}
.j-platform .j-step-num{background:#f0f9ff;color:#0369a1}
.j-consult .j-step-num{background:#faf5ff;color:#7c3aed}
.j-title{font-size:16px;font-weight:800;color:#0f172a;margin-bottom:6px}
.j-price{font-size:22px;font-weight:900;margin-bottom:10px}
.j-free .j-price{color:#16a34a}.j-platform .j-price{color:#0ea5e9}.j-consult .j-price{color:#8b5cf6}
.j-desc{font-size:13px;color:#64748b;line-height:1.6;margin-bottom:16px}
.j-features{list-style:none;padding:0;margin:0}
.j-features li{font-size:12px;color:#374151;padding:4px 0;display:flex;align-items:flex-start;gap:8px}
.j-features li i{font-size:13px;margin-top:1px;flex-shrink:0}
.j-cta{display:block;text-align:center;margin-top:20px;padding:10px;border-radius:8px;font-size:13px;font-weight:700;transition:opacity .15s,transform .15s}
.j-cta:hover{opacity:.85;transform:translateY(-1px)}
.j-free .j-cta{background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0}
.j-platform .j-cta{background:#0ea5e9;color:#fff}
.j-consult .j-cta{background:#8b5cf6;color:#fff}

/* ── QUIZ ── */
.quiz-section{background:linear-gradient(135deg,#0f172a 0%,#0d1f0f 100%);padding:84px 20px;position:relative;overflow:hidden}
.quiz-section::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.quiz-inner{max-width:620px;margin:0 auto;position:relative}
.quiz-eyebrow{text-align:center;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#4ade80;margin-bottom:10px}
.quiz-headline{text-align:center;font-size:clamp(1.6rem,3vw,2rem);font-weight:900;color:#fff;margin-bottom:10px}
.quiz-sub-text{text-align:center;color:rgba(255,255,255,.55);font-size:.95rem;margin-bottom:36px}
.quiz-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:36px 32px}
.quiz-progress-bar{background:rgba(255,255,255,.1);border-radius:4px;height:4px;margin-bottom:28px}
.quiz-progress-fill{height:4px;border-radius:4px;background:#16a34a;transition:width .4s ease}
.quiz-step{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#4ade80;margin-bottom:10px}
.quiz-question{font-size:1.15rem;font-weight:700;color:#fff;margin-bottom:22px;line-height:1.4}
.quiz-options{display:flex;flex-direction:column;gap:10px}
.quiz-option{background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:10px;
  color:#e2e8f0;padding:14px 18px;text-align:left;cursor:pointer;font-size:14px;transition:all .15s;width:100%}
.quiz-option:hover:not(:disabled){background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff}
.quiz-option.selected{background:rgba(22,163,74,.2);border-color:#4ade80;color:#4ade80}
.quiz-option:disabled{cursor:default;opacity:.7}
.quiz-result{text-align:center}
.quiz-score-ring{width:150px;height:150px;position:relative;margin:0 auto 14px}
.quiz-score-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
.quiz-score-num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  flex-direction:column;font-size:2.4rem;font-weight:900;color:#fff;line-height:1}
.quiz-score-num small{font-size:.7rem;color:#64748b;font-weight:500}
.quiz-level{font-size:1rem;font-weight:800;margin-bottom:20px}
.quiz-sub-scores{margin:20px 0;display:flex;flex-direction:column;gap:10px;text-align:left}
.quiz-sub{display:flex;align-items:center;gap:10px;font-size:12px;color:#94a3b8}
.quiz-sub-label{width:96px;flex-shrink:0}
.quiz-sub-bar{flex:1;background:rgba(255,255,255,.08);border-radius:4px;height:7px;overflow:hidden}
.quiz-sub-bar-fill{height:7px;border-radius:4px;width:0;transition:width 1.1s ease}
.quiz-sub-val{width:28px;text-align:right;font-weight:700;color:#e2e8f0}
.quiz-rec{font-size:13px;color:rgba(255,255,255,.65);line-height:1.65;margin:18px 0;
  background:rgba(255,255,255,.05);border-radius:10px;padding:16px 18px;text-align:left}
.quiz-start-btn{background:#16a34a;color:#fff;border:none;border-radius:10px;padding:14px 32px;
  font-size:15px;font-weight:700;cursor:pointer;transition:background .15s,transform .15s;display:inline-flex;align-items:center;gap:8px}
.quiz-start-btn:hover{background:#15803d;transform:translateY(-1px)}

/* ── FEATURES ── */
.features-section{padding:80px 20px;background:#fff}
.feat-tabs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}
.feat-tab{padding:8px 22px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;
  border:1.5px solid #e2e8f0;background:transparent;color:#64748b;transition:all .15s}
.feat-tab.active,.feat-tab:hover{background:#0f172a;color:#fff;border-color:#0f172a}
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;max-width:1060px;margin:0 auto}
.feat-card{padding:24px;border:1px solid #e2e8f0;border-radius:14px;background:#fafafa;
  transition:box-shadow .2s,transform .2s,background .2s;cursor:default}
.feat-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.09);transform:translateY(-3px);background:#fff}
.feat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:14px}
.feat-title{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:6px}
.feat-desc{font-size:12px;color:#64748b;line-height:1.6}
.feat-card[style*="none"]{display:none!important}

/* ── FRAMEWORKS MARQUEE ── */
.marquee-wrap{background:#fff;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:18px 0;overflow:hidden}
.marquee-track{display:flex;gap:32px;width:max-content;animation:marqueeScroll 22s linear infinite}
.marquee-wrap:hover .marquee-track{animation-play-state:paused}
@keyframes marqueeScroll{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.marquee-item{display:flex;align-items:center;gap:8px;padding:7px 16px;border:1px solid #e2e8f0;border-radius:8px;
  font-size:12px;font-weight:700;color:#374151;white-space:nowrap;background:#f8fafc}
.marquee-item i{font-size:14px}

/* ── FRAMEWORKS SECTION ── */
.frameworks-section{padding:80px 20px;background:#f8fafc}
.fw-grid{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;max-width:920px;margin:0 auto}
.fw-pill{display:flex;align-items:center;gap:10px;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
  padding:12px 18px;transition:border-color .2s,box-shadow .2s,transform .2s;cursor:default}
.fw-pill:hover{border-color:var(--c);box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-2px)}
.fw-pill-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.fw-pill-name{font-size:13px;font-weight:700;color:#0f172a}
.fw-pill-desc{font-size:11px;color:#94a3b8}

/* ── ROLES ── */
.roles-section{padding:80px 20px;background:#fff}
.roles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px;max-width:1060px;margin:0 auto}
.role-card{border-radius:14px;padding:24px;border:1.5px solid #e2e8f0;transition:box-shadow .2s,transform .2s}
.role-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px)}
.role-icon{font-size:26px;margin-bottom:12px}
.role-card h5{font-size:15px;font-weight:800;margin-bottom:8px;color:#0f172a}
.role-card p{font-size:12px;color:#64748b;line-height:1.6;margin-bottom:12px}
.role-list{list-style:none;padding:0;margin:0}
.role-list li{font-size:12px;padding:3px 0;display:flex;align-items:center;gap:7px;color:#374151}
.role-list li::before{content:'✓';font-weight:800;font-size:11px;flex-shrink:0}
.role-sme{background:#f0fdf4;border-color:#bbf7d0}.role-sme .role-icon{color:#16a34a}.role-sme .role-list li::before{color:#16a34a}
.role-assoc{background:#f0f9ff;border-color:#bae6fd}.role-assoc .role-icon{color:#0369a1}.role-assoc .role-list li::before{color:#0369a1}
.role-consult{background:#faf5ff;border-color:#ddd6fe}.role-consult .role-icon{color:#7c3aed}.role-consult .role-list li::before{color:#7c3aed}
.role-ref{background:#fffbeb;border-color:#fde68a}.role-ref .role-icon{color:#d97706}.role-ref .role-list li::before{color:#d97706}

/* ── FAQ ── */
.faq-section{padding:80px 20px;background:#f8fafc}
.faq-inner{max-width:760px;margin:0 auto}
.faq-item{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;overflow:hidden;transition:box-shadow .2s}
.faq-item:hover{box-shadow:0 2px 12px rgba(0,0,0,.06)}
.faq-q{padding:18px 20px;font-size:15px;font-weight:600;color:#0f172a;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;user-select:none}
.faq-q i{font-size:16px;color:#64748b;transition:transform .3s;flex-shrink:0}
.faq-item.open .faq-q{color:#16a34a}
.faq-item.open .faq-q i{transform:rotate(180deg);color:#16a34a}
.faq-a{max-height:0;overflow:hidden;transition:max-height .4s ease}
.faq-item.open .faq-a{max-height:400px}
.faq-a p{padding:0 20px 18px;margin:0;font-size:14px;color:#64748b;line-height:1.7}

/* ── CTA ── */
.cta-section{background:linear-gradient(135deg,#0f172a 0%,#064e3b 100%);padding:90px 20px;text-align:center;position:relative;overflow:hidden}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 50% 50%,rgba(22,163,74,.15) 0%,transparent 70%);pointer-events:none}
.cta-section h2{font-size:2.1rem;font-weight:900;color:#fff;margin-bottom:14px;position:relative}
.cta-section p{color:rgba(255,255,255,.65);max-width:520px;margin:0 auto 32px;position:relative}
.cta-leaf{font-size:42px;color:#4ade80;margin-bottom:16px;animation:pulse 2.5s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.7;transform:scale(1.08)}}

/* ── FOOTER ── */
.lp-footer{background:#0f172a;padding:48px 20px 24px}
.footer-inner{max-width:1060px;margin:0 auto}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1.5fr;gap:28px;margin-bottom:32px}
@media(max-width:900px){.footer-top{grid-template-columns:1fr 1fr 1fr}}
@media(max-width:600px){.footer-top{grid-template-columns:1fr 1fr}}
.footer-brand-name{font-size:16px;font-weight:800;color:#fff;margin-bottom:8px}
.footer-brand-desc{font-size:12px;color:#64748b;line-height:1.6}
.footer-heading{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px}
.footer-link{display:block;color:#64748b;font-size:13px;margin-bottom:8px;transition:color .15s}
.footer-link:hover{color:#fff}
.fw-badge{display:inline-block;background:rgba(255,255,255,.07);color:#94a3b8;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:600;margin:3px}
.footer-bottom{border-top:1px solid rgba(255,255,255,.07);padding-top:20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px}
.footer-bottom span{font-size:12px;color:#475569}

/* ── Utility ── */
.container-lg{max-width:1060px;margin:0 auto}
@media(max-width:600px){.hero{padding:80px 16px 70px}.stat-item{padding:0 20px}}

/* ── MOBILE: carousels & compact grids ── */
@media(max-width:640px){
  /* Journey carousel */
  .journey-steps{flex-wrap:nowrap;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;
    padding:0 16px 4px;gap:14px;justify-content:flex-start;scrollbar-width:none;margin:0 -16px}
  .journey-steps::-webkit-scrollbar{display:none}
  .journey-card{min-width:82vw;max-width:82vw;flex-shrink:0;scroll-snap-align:center}
  /* Frameworks: compact 2-column grid */
  .fw-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .fw-pill{flex-direction:column;align-items:flex-start;gap:6px;padding:12px 10px}
  .fw-pill-icon{width:28px;height:28px;font-size:13px}
  .fw-pill-desc{font-size:10px}
  /* Roles carousel */
  .roles-grid{display:flex;flex-wrap:nowrap;overflow-x:auto;scroll-snap-type:x mandatory;
    -webkit-overflow-scrolling:touch;padding:4px 20px;gap:14px;max-width:100%;
    margin:0 -20px;scrollbar-width:none}
  .roles-grid::-webkit-scrollbar{display:none}
  .role-card{min-width:78vw;max-width:78vw;flex-shrink:0;scroll-snap-align:start}
}
/* Dot indicators — hidden on desktop, shown on mobile */
.j-dots,.r-dots{display:none}
@media(max-width:640px){
  .j-dots,.r-dots{display:flex;justify-content:center;gap:7px;margin-top:18px}
  .j-dot,.r-dot{width:7px;height:7px;border-radius:50%;background:#e2e8f0;transition:width .3s,background .3s}
  .j-dot.active,.r-dot.active{background:#16a34a;width:20px;border-radius:4px}
}
</style>
</head>
<body>

<!-- ── NAV ── -->
<nav class="lp-nav" id="lpNav">
  <div class="nav-brand">
    <img class="nav-logo-img" src="<?= APP_URL ?>/assets/img/esggen-logo.png" alt="ESG gen"
         onerror="this.style.display='none';document.getElementById('navBrandFallback').style.display='flex';">
    <div id="navBrandFallback" style="display:none;align-items:center;gap:10px">
      <div class="nav-logo"><i class="bi bi-leaf-fill"></i></div>
      <span class="nav-name">ESG <span>gen</span></span>
    </div>
  </div>
  <div class="nav-links" id="navLinks">
    <a href="#journey">How It Works</a>
    <a href="#features">Features</a>
    <a href="#frameworks">Standards</a>
    <a href="#roles">Who It's For</a>
    <a href="#faq">FAQ</a>
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
      <a href="<?= APP_URL ?>/dashboard" class="btn-nav-cta"><i class="bi bi-speedometer2 me-1"></i>My Dashboard</a>
      <?php else: ?>
      <a href="<?= APP_URL ?>/login"    class="btn-nav-login">Login</a>
      <a href="<?= APP_URL ?>/register" class="btn-nav-cta"><i class="bi bi-rocket-takeoff me-1"></i>Start Free</a>
      <?php endif; ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu"><i class="bi bi-list"></i></button>
  </div>
</nav>

<!-- ── HERO ── -->
<section class="hero" id="home">
  <div class="hero-grid"></div>
  <div class="hero-glow"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="hero-eyebrow">
    <i class="bi bi-patch-check-fill"></i>
    Now supporting: <span class="eyebrow-fw" id="eyebrowFw">Bursa SEDG</span>
  </div>
  <h1>
    ESG Compliance,<br>
    <span class="accent">Made Simple.</span>
  </h1>
  <p class="hero-sub">
    The complete ESG platform for Malaysian SMEs and consultants.
    Start free — no credit card. Subscribe for full data collection and report generation.
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
    <span><i class="bi bi-check-circle-fill"></i>15 mandatory Bursa SEDG indicators — free</span>
    <span><i class="bi bi-check-circle-fill"></i>14-day Platform trial</span>
    <span><i class="bi bi-check-circle-fill"></i>No credit card required</span>
  </div>

  <!-- Dashboard preview mockup -->
  <div class="hero-card-wrap">
    <div class="hero-card">
      <div class="card-titlebar">
        <div class="dot" style="background:#ef4444"></div>
        <div class="dot" style="background:#f59e0b"></div>
        <div class="dot" style="background:#22c55e"></div>
        <div class="card-url">esggen.com/dashboard</div>
      </div>
      <div class="card-body">
        <div class="card-score-block">
          <div class="card-score-num">74</div>
          <div class="card-score-lbl">ESG Score</div>
          <div class="card-score-sub">+8 pts this quarter</div>
        </div>
        <div class="card-right">
          <div class="card-company">Demo Manufacturing Sdn Bhd</div>
          <div class="card-bar-row">
            <div class="card-bar-label" style="color:#4ade80">Environment</div>
            <div class="card-bar-track"><div class="card-bar-fill" style="--w:82%;background:#16a34a"></div></div>
            <div class="card-bar-val" style="color:#4ade80">82</div>
          </div>
          <div class="card-bar-row">
            <div class="card-bar-label" style="color:#60a5fa">Social</div>
            <div class="card-bar-track"><div class="card-bar-fill" style="--w:71%;background:#3b82f6"></div></div>
            <div class="card-bar-val" style="color:#60a5fa">71</div>
          </div>
          <div class="card-bar-row">
            <div class="card-bar-label" style="color:#c084fc">Governance</div>
            <div class="card-bar-track"><div class="card-bar-fill" style="--w:65%;background:#8b5cf6"></div></div>
            <div class="card-bar-val" style="color:#c084fc">65</div>
          </div>
          <div class="card-pills">
            <span class="card-pill" style="background:rgba(239,68,68,.15);color:#f87171">3 Critical Gaps</span>
            <span class="card-pill" style="background:rgba(22,163,74,.15);color:#4ade80">Bursa SEDG</span>
            <span class="card-pill" style="background:rgba(59,130,246,.15);color:#60a5fa">12 Actions</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── STATS ── -->
<section class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num"><span class="counter" data-target="200">0</span><span>+</span></div>
      <div class="stat-lbl">ESG Indicators</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num"><span class="counter" data-target="10">0</span></div>
      <div class="stat-lbl">Reporting Frameworks</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num">RM<span>0</span></div>
      <div class="stat-lbl">to Get Started</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item">
      <div class="stat-num"><span class="counter" data-target="3">0</span></div>
      <div class="stat-lbl">Simple Steps</div>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS ── -->
<section class="journey-section" id="journey">
  <div class="section-eyebrow reveal">How It Works</div>
  <h2 class="section-title reveal d1">Your ESG Journey in 3 Steps</h2>
  <p class="section-sub reveal d2">From free setup to professional submission — everything in one platform.</p>
  <div class="journey-steps">
    <div class="journey-card j-free reveal d1">
      <div class="j-step-num">1</div>
      <div class="j-title">Sign Up Free</div>
      <div class="j-price">Free Forever</div>
      <div class="j-desc">Register your company and start entering ESG data immediately. All 15 mandatory Bursa SEDG indicators at no cost.</div>
      <ul class="j-features">
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>All 15 mandatory Bursa SEDG indicators</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>ESG score dashboard</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>Basic report generation</li>
        <li><i class="bi bi-check-circle-fill" style="color:#16a34a"></i>No credit card needed</li>
      </ul>
      <a href="<?= APP_URL ?>/register" class="j-cta"><i class="bi bi-rocket-takeoff me-1"></i>Create Free Account</a>
    </div>
    <div class="journey-card j-platform reveal d2">
      <div class="j-step-num">2</div>
      <div class="j-title">Subscribe to Platform</div>
      <div class="j-price">RM 1,500 <span style="font-size:14px;font-weight:600;color:#64748b">/ year</span></div>
      <div class="j-desc">Unlock the full ESG data OS. All indicators, all frameworks, gap analysis, and carbon calculator.</div>
      <ul class="j-features">
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>All indicators — all frameworks</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Full report generation & PDF export</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Gap analysis & action plan</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Carbon calculator (Scope 1, 2 & 3)</li>
        <li><i class="bi bi-check-circle-fill" style="color:#0ea5e9"></i>Industry benchmarking</li>
      </ul>
      <a href="<?= APP_URL ?>/register" class="j-cta"><i class="bi bi-lightning-charge-fill me-1"></i>Start 14-Day Trial</a>
    </div>
    <div class="journey-card j-consult reveal d3">
      <div class="j-step-num">3</div>
      <div class="j-title">Engage a Consultant</div>
      <div class="j-price">RM 8,000 <span style="font-size:14px;font-weight:600;color:#64748b">/ report</span></div>
      <div class="j-desc">Have a certified ESG associate professionally review your report, validate your data, and guide regulatory submission.</div>
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
  <div class="j-dots" id="jDots">
    <div class="j-dot active"></div>
    <div class="j-dot"></div>
    <div class="j-dot"></div>
  </div>
</section>

<!-- ── ESG READINESS QUIZ ── -->
<section class="quiz-section" id="quiz">
  <div class="quiz-inner reveal">
    <div class="quiz-eyebrow">Interactive Tool</div>
    <h2 class="quiz-headline">Check Your ESG Readiness</h2>
    <p class="quiz-sub-text">Answer 4 quick questions and get your estimated ESG score instantly.</p>
    <div class="quiz-card" id="quizCard">
      <div style="text-align:center;padding:20px 0">
        <div style="font-size:40px;margin-bottom:16px">🌿</div>
        <p style="color:rgba(255,255,255,.7);font-size:14px;margin-bottom:24px;line-height:1.6">Takes 30 seconds. See where your company stands and what to prioritise first.</p>
        <button class="quiz-start-btn" onclick="startQuiz()"><i class="bi bi-play-fill"></i>Start the Quiz</button>
      </div>
    </div>
  </div>
</section>

<!-- ── FEATURES ── -->
<section class="features-section" id="features">
  <div class="section-eyebrow reveal">Platform Features</div>
  <h2 class="section-title reveal d1">Everything you need for ESG compliance</h2>
  <p class="section-sub reveal d2">From data entry to board-ready reports — all in one place.</p>
  <div class="feat-tabs reveal d3">
    <button class="feat-tab active" data-filter="all">All Features</button>
    <button class="feat-tab" data-filter="tracking">Data & Tracking</button>
    <button class="feat-tab" data-filter="analysis">Analysis</button>
    <button class="feat-tab" data-filter="reporting">Reporting</button>
  </div>
  <div class="features-grid container-lg">
    <?php
    $features = [
      ['bi-list-check',        '#f0f9ff','#0ea5e9', 'Full Indicator Library',  'All 15 mandatory Bursa SEDG indicators free. Subscribe for 200+ indicators across GRI, ISSB, ESRS, CDP, TCFD, and SASB.', 'tracking'],
      ['bi-bar-chart-steps',   '#f0fdf4','#16a34a', 'Gap Analysis',            'Instantly see which mandatory disclosures are missing. Priority-ranked by critical / high / medium impact with a clear action plan.', 'analysis'],
      ['bi-calculator',        '#fff7ed','#f97316', 'Carbon Calculator',       'Scope 1, 2 & 3 calculations using Malaysia MyGHG 2023 factors. Auto-saves results directly to your GHG indicators.', 'tracking'],
      ['bi-file-earmark-text', '#faf5ff','#8b5cf6', 'Report Generation',       'Generate full ESG reports mapped to Bursa SEDG, GRI, or TCFD templates. Export to PDF for submission or investor disclosure.', 'reporting'],
      ['bi-bar-chart-line',    '#fffbeb','#d97706', 'Industry Benchmarking',   'Compare your ESG scores against Malaysian industry peers. See where you rank and what to prioritise first.', 'analysis'],
      ['bi-shield-check',      '#f0fdf4','#16a34a', 'Audit Trail',             'Every data entry is timestamped, sourced, and verifiable. Full activity log for compliance auditors and board review.', 'reporting'],
      ['bi-people-fill',       '#f0f9ff','#0369a1', 'Consultant Hierarchy',    'Principal → Associate → Manager structure for consulting firms. Each role sees exactly the right companies and data.', 'analysis'],
      ['bi-currency-dollar',   '#f0fdf4','#16a34a', 'Green Financing',         'Automatically identify BNM, Khazanah, and commercial green financing your company may qualify for based on ESG score.', 'reporting'],
    ];
    foreach ($features as [$icon, $bg, $color, $title, $desc, $cat]):
    ?>
    <div class="feat-card reveal" data-cat="<?= $cat ?>">
      <div class="feat-icon" style="background:<?= $bg ?>;color:<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div class="feat-title"><?= $title ?></div>
      <p class="feat-desc"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── FRAMEWORKS MARQUEE ── -->
<div class="marquee-wrap">
  <?php
  $marqueeItems = [
    ['bi-flag-fill','#dc2626','Bursa SEDG'],
    ['bi-globe','#0891b2','GRI Standards'],
    ['bi-cloud-sun-fill','#0d9488','ISSB / IFRS S1'],
    ['bi-droplet-fill','#0284c7','CDP'],
    ['bi-building-fill','#7c3aed','ESRS'],
    ['bi-briefcase-fill','#ca8a04','SASB'],
    ['bi-bullseye','#16a34a','UN SDGs'],
    ['bi-diagram-3','#475569','TCFD'],
    ['bi-graph-up','#e11d48','MyGHG 2023'],
    ['bi-award','#0f766e','DEFRA 2023'],
  ];
  $doubled = array_merge($marqueeItems, $marqueeItems);
  ?>
  <div class="marquee-track">
    <?php foreach ($doubled as [$icon, $color, $name]): ?>
    <div class="marquee-item"><i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i><?= $name ?></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── FRAMEWORKS GRID ── -->
<section class="frameworks-section" id="frameworks">
  <div class="section-eyebrow reveal">Supported Standards</div>
  <h2 class="section-title reveal d1">All major ESG reporting frameworks</h2>
  <p class="section-sub reveal d2">One platform for every framework your clients or regulators require.</p>
  <div class="fw-grid reveal d3">
    <?php
    $fws = [
      ['bi-flag-fill',       '#dc2626', 'Bursa SEDG',    'Mandatory for Malaysian-listed & pre-IPO companies'],
      ['bi-globe',           '#0891b2', 'GRI Standards',  'Global standard — voluntary & regulated ESG'],
      ['bi-cloud-sun-fill',  '#0d9488', 'ISSB / TCFD',   'Climate financial disclosures — IFRS S1 & S2'],
      ['bi-droplet-fill',    '#0284c7', 'CDP',            'Carbon Disclosure Project for investors & supply chains'],
      ['bi-building-fill',   '#7c3aed', 'ESRS',           'EU CSRD — for export-facing companies'],
      ['bi-briefcase-fill',  '#ca8a04', 'SASB',           'Industry-specific packs — Manufacturing, Tech, F&B'],
      ['bi-bullseye',        '#16a34a', 'UN SDGs',        'Sustainable Development Goals alignment'],
      ['bi-diagram-3',       '#475569', 'TCFD',           'Task Force on Climate-related Financial Disclosures'],
    ];
    foreach ($fws as [$icon, $color, $name, $desc]):
    ?>
    <div class="fw-pill" style="--c:<?= $color ?>">
      <div class="fw-pill-icon" style="background:<?= $color ?>18;color:<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div><div class="fw-pill-name"><?= $name ?></div><div class="fw-pill-desc"><?= $desc ?></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── WHO IT'S FOR ── -->
<section class="roles-section" id="roles">
  <div class="section-eyebrow reveal">Who It's For</div>
  <h2 class="section-title reveal d1">Built for the full ESG ecosystem</h2>
  <p class="section-sub reveal d2">Every role in the ESG value chain — from business owner to certified consultant.</p>
  <div class="roles-grid container-lg">
    <div class="role-card role-sme reveal d1">
      <div class="role-icon"><i class="bi bi-building"></i></div>
      <h5>SME Owner</h5>
      <p>Track your company's ESG data, close gaps, and generate reports for banks, investors, buyers, or Bursa submission.</p>
      <ul class="role-list">
        <li>Start free — 15 mandatory indicators</li>
        <li>Subscribe for full platform access</li>
        <li>Self-service at RM 1,500/year</li>
      </ul>
    </div>
    <div class="role-card role-assoc reveal d2">
      <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
      <h5>ESG Consultant / Associate</h5>
      <p>Deliver professional ESG report review and consultation services to SME clients at RM 8,000 per report.</p>
      <ul class="role-list">
        <li>Manage multiple client companies</li>
        <li>Review & validate client reports</li>
        <li>Submission guidance & sign-off</li>
      </ul>
    </div>
    <div class="role-card role-ref reveal d3">
      <div class="role-icon"><i class="bi bi-people-fill"></i></div>
      <h5>Referral Partner</h5>
      <p>Accountants, lawyers, company secretaries, and bankers who refer SME clients earn recurring referral fees.</p>
      <ul class="role-list">
        <li>15% referral fee per subscription</li>
        <li>Recurring annually on renewals</li>
        <li>No platform management needed</li>
      </ul>
    </div>
    <div class="role-card role-consult reveal d4">
      <div class="role-icon"><i class="bi bi-diagram-3-fill"></i></div>
      <h5>Consulting Firm</h5>
      <p>Principal → Associate → Manager hierarchy gives your team the right access. Portfolio dashboard shows all clients.</p>
      <ul class="role-list">
        <li>Full org tree management</li>
        <li>Portfolio ESG score overview</li>
        <li>Assign managers to clients</li>
      </ul>
    </div>
  </div>
  <div class="r-dots" id="rDots">
    <div class="r-dot active"></div>
    <div class="r-dot"></div>
    <div class="r-dot"></div>
    <div class="r-dot"></div>
  </div>
</section>

<!-- ── FAQ ── -->
<section class="faq-section" id="faq">
  <div class="faq-inner">
    <div class="section-eyebrow reveal">FAQ</div>
    <h2 class="section-title reveal d1">Common Questions</h2>
    <p class="section-sub reveal d2" style="margin-bottom:40px">Everything you need to know before you start.</p>
    <?php
    $faqs = [
      ['Is ESG gen really free?',
       'Yes. The Starter plan is free forever and includes all 15 mandatory Bursa SEDG indicators, an ESG score dashboard, and basic report generation. No credit card required. Every new account also receives a 14-day Professional trial with full access to all 200+ indicators.'],
      ['What is Bursa SEDG and do I need to comply?',
       'Bursa SEDG (Sustainability Enhanced Disclosure Guidance) is Bursa Malaysia\'s sustainability reporting framework. It is mandatory for Main Market and ACE Market listed companies, and strongly recommended for pre-IPO companies seeking green financing or investor confidence. ESG gen covers all mandatory Bursa SEDG indicators in the free plan.'],
      ['Which ESG frameworks does ESG gen support?',
       'ESG gen supports Bursa SEDG, GRI Standards, ISSB (IFRS S1 & S2), CDP, ESRS (EU CSRD), SASB, TCFD, and UN SDGs. The free plan covers Bursa SEDG. The Platform subscription (RM 1,500/year) unlocks all 200+ indicators across all frameworks.'],
      ['How is the Platform subscription billed?',
       'The Platform plan is billed annually at RM 1,500/year in Malaysian Ringgit (MYR). Every new signup gets a 14-day Professional trial. Payment is by invoice — no credit card is required upfront. Subscriptions auto-renew annually unless cancelled at least 7 days before renewal.'],
      ['Can I manage multiple companies from one account?',
       'Yes. Consultants and consulting firms can be linked to multiple client companies. ESG gen\'s Principal → Associate → Manager hierarchy allows firm-wide portfolio management with role-based access control — each role sees exactly the companies and data they should.'],
      ['Is my ESG data secure?',
       'Yes. All passwords are hashed with bcrypt (never stored in plaintext). Data is transmitted over HTTPS (TLS). Session-based authentication with CSRF protection prevents cross-site attacks. Data is hosted on Hostinger\'s infrastructure. See our Privacy Policy and PDPA Notice for full details.'],
    ];
    foreach ($faqs as $i => [$q, $a]):
    ?>
    <div class="faq-item reveal" id="faq<?= $i ?>">
      <div class="faq-q" onclick="toggleFaq(<?= $i ?>)">
        <?= htmlspecialchars($q) ?>
        <i class="bi bi-chevron-down"></i>
      </div>
      <div class="faq-a"><p><?= htmlspecialchars($a) ?></p></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section">
  <div class="cta-leaf"><i class="bi bi-leaf-fill"></i></div>
  <h2>Start your ESG journey today</h2>
  <p>Join Malaysian SMEs and consulting firms using ESG gen to meet Bursa SEDG requirements, secure green financing, and deliver investor-grade sustainability reports.</p>
  <?php if ($isLoggedIn): ?>
  <a href="<?= APP_URL ?>/dashboard" class="btn-hero-primary" style="display:inline-flex">
    <i class="bi bi-speedometer2"></i>Go to Dashboard
  </a>
  <?php else: ?>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative">
    <a href="<?= APP_URL ?>/register" class="btn-hero-primary">
      <i class="bi bi-rocket-takeoff"></i>Create Free Account
    </a>
    <a href="<?= APP_URL ?>/pricing" class="btn-hero-secondary">
      <i class="bi bi-grid-3x3-gap"></i>View Full Pricing
    </a>
  </div>
  <?php endif; ?>
</section>

<!-- ── FOOTER ── -->
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
const appUrl = '<?= APP_URL ?>';

// ── Nav scroll + scrolled class ──
const nav = document.getElementById('lpNav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 40), {passive:true});

// ── Smooth scroll ──
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({behavior:'smooth',block:'start'}); }
  });
});

// ── Mobile nav ──
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('navLinks');
hamburger.addEventListener('click', () => {
  navLinks.classList.toggle('open');
  hamburger.innerHTML = navLinks.classList.contains('open')
    ? '<i class="bi bi-x-lg"></i>' : '<i class="bi bi-list"></i>';
});
navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
  navLinks.classList.remove('open');
  hamburger.innerHTML = '<i class="bi bi-list"></i>';
}));

// ── Scroll reveal (Intersection Observer) ──
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); } });
}, {threshold: 0.1});
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── Nav scrollspy ──
const spySections = document.querySelectorAll('section[id]');
const spyLinks    = document.querySelectorAll('.nav-links a[href^="#"]');
const spyObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      spyLinks.forEach(l => l.classList.remove('nav-active'));
      const lk = document.querySelector(`.nav-links a[href="#${e.target.id}"]`);
      if (lk) lk.classList.add('nav-active');
    }
  });
}, {threshold: 0.45});
spySections.forEach(s => spyObs.observe(s));

// ── Counter animation ──
function animateCount(el) {
  const target = +el.dataset.target;
  const dur = 1400;
  let start = null;
  const step = ts => {
    if (!start) start = ts;
    const p = Math.min((ts - start) / dur, 1);
    el.textContent = Math.floor(p * target);
    if (p < 1) requestAnimationFrame(step);
    else el.textContent = target;
  };
  requestAnimationFrame(step);
}
const counterObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { animateCount(e.target); counterObs.unobserve(e.target); } });
}, {threshold: 0.6});
document.querySelectorAll('.counter').forEach(el => counterObs.observe(el));

// ── Cycling eyebrow framework text ──
const frameworks = ['Bursa SEDG','GRI Standards','ISSB / IFRS S1','CDP','ESRS','SASB','TCFD','UN SDGs'];
let fwIdx = 0;
const eyebrowEl = document.getElementById('eyebrowFw');
setInterval(() => {
  eyebrowEl.style.opacity = '0';
  setTimeout(() => {
    fwIdx = (fwIdx + 1) % frameworks.length;
    eyebrowEl.textContent = frameworks[fwIdx];
    eyebrowEl.style.opacity = '1';
  }, 300);
}, 2200);

// ── Feature tabs ──
document.querySelectorAll('.feat-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.feat-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const filter = tab.dataset.filter;
    document.querySelectorAll('.feat-card').forEach(card => {
      const show = filter === 'all' || card.dataset.cat === filter;
      card.style.display = show ? '' : 'none';
    });
  });
});

// ── FAQ accordion ──
function toggleFaq(idx) {
  const item = document.getElementById('faq' + idx);
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
}

// ── Quiz ──
const quizData = [
  { q: "What industry is your company in?",
    opts: ["Manufacturing & Industrial","Technology & Services","F&B & Agriculture","Finance & Insurance","Other"],
    w: [0, 5, 0, 5, 0] },
  { q: "How many employees does your company have?",
    opts: ["Fewer than 50","50 – 200","200 – 500","More than 500"],
    w: [-5, 0, 5, 10] },
  { q: "How would you describe your current ESG efforts?",
    opts: ["Haven't started yet","We track some data manually","We have policies but no formal reporting","We already report on ESG metrics"],
    w: [0, 28, 52, 76] },
  { q: "What is your primary ESG goal?",
    opts: ["Bursa SEDG compliance","Access to green financing","Supply chain requirements","Investor / buyer due diligence"],
    w: [0, 0, 0, 0] }
];
let qIdx = 0, answers = [], totalW = 0;

function startQuiz() {
  qIdx = 0; answers = []; totalW = 0;
  renderQuestion();
}

function renderQuestion() {
  const d = quizData[qIdx];
  const pct = (qIdx / quizData.length) * 100;
  document.getElementById('quizCard').innerHTML = `
    <div class="quiz-progress-bar"><div class="quiz-progress-fill" style="width:${pct}%"></div></div>
    <div class="quiz-step">Question ${qIdx + 1} of ${quizData.length}</div>
    <div class="quiz-question">${d.q}</div>
    <div class="quiz-options">
      ${d.opts.map((o,i) => `<button class="quiz-option" onclick="pickAnswer(${i})">${o}</button>`).join('')}
    </div>`;
}

function pickAnswer(i) {
  document.querySelectorAll('.quiz-option').forEach((b,j) => {
    b.disabled = true;
    if (j === i) b.classList.add('selected');
  });
  totalW += quizData[qIdx].w[i];
  answers.push(i);
  setTimeout(() => {
    qIdx++;
    if (qIdx < quizData.length) renderQuestion();
    else showResult();
  }, 420);
}

function showResult() {
  const base   = [5, 30, 58, 82][answers[2]];
  const bonus  = quizData[0].w[answers[0]] + quizData[1].w[answers[1]];
  const score  = Math.min(95, Math.max(5, base + bonus));
  const eBias  = [10,-5, 5,-10,0][answers[0]];
  const sBias  = [-10,-5, 5,10][answers[1]];
  const gBias  = [0, 5, 0, 10][answers[3]];
  const eScore = Math.min(100, Math.max(5, score + eBias));
  const sScore = Math.min(100, Math.max(5, score + sBias));
  const gScore = Math.min(100, Math.max(5, score + gBias));

  let level, levelColor, rec;
  if (score < 25) {
    level='Getting Started'; levelColor='#ef4444';
    rec='You\'re at the very start of your ESG journey. ESG gen\'s free Starter plan is the perfect first step — begin with the 15 mandatory Bursa SEDG indicators today, at zero cost.';
  } else if (score < 50) {
    level='Developing'; levelColor='#f97316';
    rec='You\'re building a foundation. The Platform plan unlocks gap analysis to show exactly what\'s missing, and an action plan to close those gaps — systematically.';
  } else if (score < 75) {
    level='Established'; levelColor='#eab308';
    rec='Good progress! A Platform subscription will formalise your data collection, generate board-ready reports, and benchmark your scores against Malaysian industry peers.';
  } else {
    level='Advanced'; levelColor='#16a34a';
    rec='You\'re ahead of most Malaysian SMEs. Consider engaging a certified ESG consultant to validate and sign off your report for Bursa SEDG regulatory submission.';
  }

  const circ = 2 * Math.PI * 42;
  document.getElementById('quizCard').innerHTML = `
    <div class="quiz-result">
      <div class="quiz-score-ring">
        <svg viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="8"/>
          <circle id="scoreArc" cx="50" cy="50" r="42" fill="none" stroke="${levelColor}" stroke-width="8"
            stroke-dasharray="0 ${circ}" stroke-dashoffset="${circ * 0.25}" stroke-linecap="round"/>
        </svg>
        <div class="quiz-score-num">${Math.round(score)}<small>/100</small></div>
      </div>
      <div class="quiz-level" style="color:${levelColor}">${level}</div>
      <div class="quiz-sub-scores">
        <div class="quiz-sub">
          <span class="quiz-sub-label">Environment</span>
          <div class="quiz-sub-bar"><div class="quiz-sub-bar-fill" id="eBar" style="background:#16a34a"></div></div>
          <span class="quiz-sub-val">${Math.round(eScore)}</span>
        </div>
        <div class="quiz-sub">
          <span class="quiz-sub-label">Social</span>
          <div class="quiz-sub-bar"><div class="quiz-sub-bar-fill" id="sBar" style="background:#3b82f6"></div></div>
          <span class="quiz-sub-val">${Math.round(sScore)}</span>
        </div>
        <div class="quiz-sub">
          <span class="quiz-sub-label">Governance</span>
          <div class="quiz-sub-bar"><div class="quiz-sub-bar-fill" id="gBar" style="background:#8b5cf6"></div></div>
          <span class="quiz-sub-val">${Math.round(gScore)}</span>
        </div>
      </div>
      <p class="quiz-rec">${rec}</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:16px">
        <a href="${appUrl}/register" class="quiz-start-btn" style="font-size:14px;padding:12px 24px;text-decoration:none">
          <i class="bi bi-rocket-takeoff"></i>Get My Full Score — Free
        </a>
        <button onclick="startQuiz()" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);color:#e2e8f0;border-radius:10px;padding:12px 20px;cursor:pointer;font-size:13px;font-weight:600">
          <i class="bi bi-arrow-repeat"></i> Retake
        </button>
      </div>
    </div>`;

  // Animate arc and bars
  setTimeout(() => {
    const arc = document.getElementById('scoreArc');
    if (arc) arc.style.strokeDasharray = `${circ * score / 100} ${circ * (1 - score/100)}`;
    const animBar = (id, val) => { const el = document.getElementById(id); if(el) el.style.width = val+'%'; };
    animBar('eBar', eScore); animBar('sBar', sScore); animBar('gBar', gScore);
  }, 80);
}

// ── Carousel dot tracker (reusable) ──
function initCarouselDots(trackSel, dotSel) {
  const track = document.querySelector(trackSel);
  const dots  = document.querySelectorAll(dotSel);
  if (!track || !dots.length) return;
  track.addEventListener('scroll', () => {
    const idx = Math.round(track.scrollLeft / (track.scrollWidth / dots.length));
    dots.forEach((d,i) => d.classList.toggle('active', i === Math.min(idx, dots.length-1)));
  }, {passive:true});
}
initCarouselDots('.journey-steps', '.j-dot');
initCarouselDots('.roles-grid',    '.r-dot');
</script>
</body>
</html>
