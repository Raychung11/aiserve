<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'STRHub AI — Malaysia\'s Smart Property Management Platform') ?></title>
<meta name="description" content="STRHub AI helps property managers track STR, mid-term, sublet and corporate leases — with built-in compliance, ROI engine, owner portal and rent payment tracking.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --accent:#6366f1; --accent-dark:#4f46e5; --dark:#0f172a; --muted:#64748b; }
* { box-sizing:border-box; }
body { font-family:'Segoe UI',system-ui,sans-serif; color:var(--dark); background:#fff; }

/* ── Navbar ── */
.lp-nav {
  position:sticky; top:0; z-index:200;
  background:rgba(255,255,255,.92);
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  border-bottom:1px solid #e2e8f0;
  padding:.75rem 0;
  transition:box-shadow .2s;
}
.lp-nav.scrolled { box-shadow:0 2px 20px rgba(0,0,0,.08); }
.nav-brand { font-weight:800; font-size:1.15rem; color:var(--dark); text-decoration:none; display:flex; align-items:center; gap:.5rem; }
.nav-brand i { color:var(--accent); font-size:1.3rem; }
.nav-brand:hover { color:var(--accent); }
.lp-nav .nav-link { color:#475569; font-size:.875rem; font-weight:500; padding:.35rem .75rem; border-radius:6px; transition:color .15s,background .15s; }
.lp-nav .nav-link:hover { color:var(--accent); background:#f5f3ff; }
.btn-nav-login  { color:var(--accent)!important; font-weight:600!important; }
.btn-nav-signup { background:var(--accent); color:#fff!important; padding:.4rem 1.1rem!important; border-radius:8px!important; font-weight:600!important; transition:background .15s!important; }
.btn-nav-signup:hover { background:var(--accent-dark)!important; }

/* Mobile toggler */
.navbar-toggler { border:none; padding:.25rem .5rem; }
.navbar-toggler:focus { box-shadow:none; }
</style>
</head>
<body>

<nav class="lp-nav" id="lpNav">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <!-- Brand -->
      <a href="<?= APP_URL ?>/" class="nav-brand">
        <i class="bi bi-house-heart-fill"></i> STRHub AI
      </a>

      <!-- Desktop nav -->
      <div class="d-none d-md-flex align-items-center gap-1">
        <a href="#features"  class="nav-link">Features</a>
        <a href="#how"       class="nav-link">How It Works</a>
        <a href="#pricing"   class="nav-link">Pricing</a>
        <div class="ms-3 d-flex align-items-center gap-2">
          <a href="<?= APP_URL ?>/login"    class="nav-link btn-nav-login">Sign In</a>
          <a href="<?= APP_URL ?>/register" class="nav-link btn-nav-signup">Start Free Trial</a>
        </div>
      </div>

      <!-- Mobile toggle -->
      <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
        <i class="bi bi-list" style="font-size:1.4rem;color:#475569;"></i>
      </button>
    </div>

    <!-- Mobile menu -->
    <div class="collapse" id="mobileMenu">
      <div class="pt-3 pb-2 d-flex flex-column gap-1 border-top mt-2">
        <a href="#features"  class="nav-link">Features</a>
        <a href="#how"       class="nav-link">How It Works</a>
        <a href="#pricing"   class="nav-link">Pricing</a>
        <hr class="my-1">
        <a href="<?= APP_URL ?>/login"    class="nav-link btn-nav-login">Sign In</a>
        <a href="<?= APP_URL ?>/register" class="btn btn-primary btn-sm mt-1 w-100 py-2" style="border-radius:8px;">Start Free Trial</a>
      </div>
    </div>
  </div>
</nav>

<script>
// Add scrolled class for shadow
window.addEventListener('scroll', function(){
  document.getElementById('lpNav').classList.toggle('scrolled', window.scrollY > 10);
});
// Smooth-scroll anchors
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({behavior:'smooth', block:'start'}); }
  });
});
</script>
