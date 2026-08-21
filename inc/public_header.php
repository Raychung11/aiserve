<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/seo.php';

$siteLogoUrl = get_setting('site_logo_url', '');
$siteLogoAlt = get_setting('site_logo_alt', 'AiServe.my');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= h($pageTitle ?? 'AiServe.my') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= h($pageDescription ?? '') ?>">
    <script>(function(){try{var t=localStorage.getItem('aiserve_theme');if(t&&t!=='violet'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    <?= seo_head_tags() ?>

    <style>
        :root{
            --bg:#f6f3ff;
            --bg2:#ffffff;
            --text:#1f1534;
            --muted:#6f6487;
            --line:#e7defc;
            --primary:#6d28d9;
            --primary2:#8b5cf6;
            --primary3:#ede9fe;
            --dark:#140f24;
            --success:#16a34a;
            --danger:#dc2626;
            --shadow:0 18px 45px rgba(109,40,217,.10);
            --shadow-lg:0 28px 70px rgba(109,40,217,.16);
            --radius:18px;
            --radius-lg:24px;
            --container:1180px;
        }

        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            margin:0;
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:
                radial-gradient(circle at top left, rgba(139,92,246,.10), transparent 30%),
                linear-gradient(180deg,#faf8ff 0%,#f6f3ff 100%);
            color:var(--text);
            line-height:1.6;
        }

        a{text-decoration:none;color:inherit}
        img{max-width:100%;display:block}
        .container{width:min(100% - 32px, var(--container));margin:auto}

        /* =========================
           Header / Nav
        ========================= */
        .topbar,
        .site-header{
            position:sticky;
            top:0;
            z-index:50;
            background:rgba(255,255,255,.88);
            backdrop-filter:blur(14px);
            border-bottom:1px solid var(--line);
        }

        .nav,
        .header-inner{
            min-height:78px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:12px;
            font-weight:800;
            color:var(--dark);
            min-width:0;
        }

        .brand-mark,
        .brand-fallback{
            width:42px;
            height:42px;
            border-radius:14px;
            background:linear-gradient(135deg,var(--primary),var(--primary2));
            color:#fff;
            display:grid;
            place-items:center;
            box-shadow:0 12px 28px rgba(109,40,217,.22);
            font-weight:900;
            flex:0 0 auto;
        }

        .brand-logo{
            max-height:46px;
            width:auto;
            object-fit:contain;
            flex:0 0 auto;
        }

        .brand-text{
            display:flex;
            flex-direction:column;
            min-width:0;
        }

        .brand-title{
            font-size:18px;
            font-weight:800;
            line-height:1.1;
            color:var(--dark);
        }

        .brand-subtitle{
            font-size:12px;
            color:var(--muted);
            line-height:1.2;
            margin-top:2px;
        }

        .nav-links{
            display:flex;
            gap:22px;
            align-items:center;
            color:var(--muted);
            font-size:14px;
            font-weight:600;
            flex-wrap:wrap;
        }

        .nav-links a:hover{
            color:var(--primary);
        }

        .btn-primary{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:46px;
            padding:0 18px;
            border-radius:999px;
            color:#fff;
            font-weight:700;
            background:linear-gradient(135deg,var(--primary),var(--primary2));
            box-shadow:0 14px 30px rgba(109,40,217,.18);
            transition:.2s ease;
        }

        .btn-primary:hover{
            transform:translateY(-1px);
        }

        .nav-toggle{
            display:none;
            min-height:42px;
            padding:0 14px;
            border-radius:12px;
            border:1px solid var(--line);
            background:#fff;
            color:var(--primary);
            font-weight:700;
            cursor:pointer;
        }

        .mobile-nav{
            display:none;
            padding:0 0 14px;
        }

        .mobile-nav.open{
            display:grid;
            gap:10px;
        }

        .mobile-nav a{
            display:block;
            padding:12px 14px;
            border:1px solid var(--line);
            border-radius:12px;
            background:#fff;
            color:var(--muted);
            font-weight:600;
        }

        .mobile-nav a:hover{
            color:var(--primary);
        }

        /* =========================
           General sections
        ========================= */
        .hero,
        .hero-section{
            padding:72px 0 48px;
        }

        .hero-grid{
            display:grid;
            grid-template-columns:1.1fr .9fr;
            gap:28px;
            align-items:center;
        }

        .eyebrow{
            display:inline-flex;
            padding:8px 14px;
            border-radius:999px;
            background:var(--primary3);
            color:var(--primary);
            font-weight:700;
            font-size:13px;
            margin-bottom:16px;
        }

        .hero h1,
        .hero-section h1{
            margin:0 0 18px;
            font-size:clamp(38px,6vw,64px);
            line-height:1.03;
            letter-spacing:-1.6px;
            color:var(--dark);
        }

        .hero p,
        .hero-section p{
            margin:0;
            font-size:18px;
            color:var(--muted);
        }

        .hero-actions{
            display:flex;
            gap:14px;
            flex-wrap:wrap;
            margin-top:26px;
        }

        .hero-card,
        .card{
            background:rgba(255,255,255,.86);
            border:1px solid var(--line);
            border-radius:var(--radius-lg);
            box-shadow:var(--shadow);
        }

        .hero-card{padding:24px}

        .section{padding:34px 0}

        .section-head{
            max-width:760px;
            margin-bottom:22px;
        }

        .section-head .label{
            color:var(--primary);
            text-transform:uppercase;
            letter-spacing:.12em;
            font-size:12px;
            font-weight:800;
            margin-bottom:8px;
        }

        .section-head h2{
            margin:0 0 10px;
            font-size:clamp(28px,4vw,44px);
            line-height:1.08;
            letter-spacing:-1px;
        }

        .section-head p{
            margin:0;
            color:var(--muted);
            font-size:17px;
        }

        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
        .grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}

        .card{
            padding:22px;
            transition:.2s ease;
        }

        .card:hover{
            transform:translateY(-2px);
            box-shadow:var(--shadow-lg);
        }

        .card h3{
            margin:0 0 10px;
            font-size:20px;
        }

        .card p{
            margin:0;
            color:var(--muted);
        }

        .icon{
            width:48px;
            height:48px;
            border-radius:14px;
            display:grid;
            place-items:center;
            background:linear-gradient(135deg,#ede9fe,#ddd6fe);
            color:var(--primary);
            font-weight:800;
            margin-bottom:14px;
        }

        .subscribe-wrap{
            display:grid;
            grid-template-columns:1fr .95fr;
            gap:22px;
        }

        .form-box{
            background:#fff;
            border:1px solid var(--line);
            border-radius:24px;
            box-shadow:var(--shadow);
            padding:24px;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
        }

        .field{display:grid;gap:8px}
        .field.full{grid-column:1 / -1}

        label{
            font-size:14px;
            font-weight:700;
        }

        input,select,textarea{
            width:100%;
            min-height:50px;
            border-radius:14px;
            border:1px solid var(--line);
            background:#fff;
            padding:14px 16px;
            font:inherit;
            color:var(--text);
            outline:none;
            transition:border-color .2s ease, box-shadow .2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus{
            border-color:#c4b5fd;
            box-shadow:0 0 0 4px rgba(139,92,246,.10);
        }

        textarea{
            min-height:120px;
            resize:vertical;
        }

        .alert{
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:14px;
            font-size:14px;
            font-weight:600;
        }

        .alert-success{
            background:#ecfdf3;
            color:#166534;
            border:1px solid #bbf7d0;
        }

        .alert-error{
            background:#fef2f2;
            color:#991b1b;
            border:1px solid #fecaca;
        }

        .stats{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
            margin-top:20px;
        }

        .stat{
            background:#fff;
            border:1px solid var(--line);
            border-radius:18px;
            padding:18px;
            box-shadow:var(--shadow);
        }

        .stat strong{
            display:block;
            font-size:28px;
            color:var(--dark);
            margin-bottom:6px;
        }

        .stat span{
            font-size:14px;
            color:var(--muted);
        }

        /* =========================
           Footer
        ========================= */
        .footer{
            margin-top:44px;
            padding:34px 0 50px;
            border-top:1px solid var(--line);
            color:var(--muted);
            background:linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,.55));
        }

        .footer-grid{
            display:grid;
            grid-template-columns:2fr 1fr 1fr;
            gap:24px;
            align-items:start;
        }

        .footer-title{
            margin:0 0 12px;
            color:var(--dark);
            font-size:18px;
            font-weight:800;
        }

        .footer-links{
            display:grid;
            gap:10px;
        }

        .footer-links a{
            color:var(--muted);
            font-weight:600;
        }

        .footer-links a:hover{
            color:var(--primary);
        }

        .small{
            font-size:14px;
            color:var(--muted);
        }

        .footer-bottom{
            margin-top:24px;
            padding-top:18px;
            border-top:1px solid var(--line);
        }

        @media (max-width: 1024px){
            .hero-grid,
            .subscribe-wrap{
                grid-template-columns:1fr;
            }

            .grid-4{
                grid-template-columns:repeat(2,1fr);
            }

            .stats{
                grid-template-columns:repeat(2,1fr);
            }

            .footer-grid{
                grid-template-columns:1fr 1fr;
            }
        }

        @media (max-width: 760px){
            .header-inner{
                min-height:auto;
                padding:14px 0;
                flex-wrap:wrap;
            }

            .nav-links{
                display:none;
            }

            .nav-toggle{
                display:inline-flex;
            }

            .grid-3,
            .grid-4,
            .form-grid,
            .stats,
            .footer-grid{
                grid-template-columns:1fr;
            }

            .hero,
            .hero-section{
                padding:56px 0 34px;
            }

            .hero h1,
            .hero-section h1{
                letter-spacing:-1px;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand">
            <?php if ($siteLogoUrl !== ''): ?>
                <img src="<?= h($siteLogoUrl) ?>" alt="<?= h($siteLogoAlt) ?>" class="brand-logo">
            <?php else: ?>
                <span class="brand-fallback">A</span>
            <?php endif; ?>

            <div class="brand-text">
                <span class="brand-title">AiServe.my</span>
                <span class="brand-subtitle">AI Business Operating System</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="/ai-bos.php">Ai-BOS</a>
            <a href="/solutions.php">Solutions</a>
            <a href="/sme-ai-transformation.php">SME Transformation</a>
            <a href="/industries.php">Industries</a>
            <a href="/projects.php">Projects</a>
            <a href="/about.php">About</a>
            <a href="/blog.php">Blog</a>
            <a href="/demos.php">Demos</a>
            <a href="/ai-customer-service-demo.php">AI CS Demo</a>
            <a href="/contact.php" class="btn-primary">Contact</a>
        </nav>

        <button class="nav-toggle" type="button" onclick="toggleMobileNav()">Menu</button>
    </div>

    <div class="container">
        <div id="mobileNav" class="mobile-nav">
            <a href="/ai-bos.php">Ai-BOS</a>
            <a href="/solutions.php">Solutions</a>
            <a href="/sme-ai-transformation.php">SME Transformation</a>
            <a href="/industries.php">Industries</a>
            <a href="/projects.php">Projects</a>
            <a href="/about.php">About</a>
            <a href="/blog.php">Blog</a>
            <a href="/demos.php">Demos</a>
            <a href="/contact.php">Contact</a>
        </div>
    </div>
</header>

<script>
function toggleMobileNav() {
    document.getElementById('mobileNav').classList.toggle('open');
}
</script>