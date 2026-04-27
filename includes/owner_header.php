<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Owner Portal') ?> — Roomee</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --accent: #6366f1; }
body  { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
.owner-navbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: .75rem 1.5rem; }
.brand-logo   { font-weight: 700; color: var(--accent); font-size: 1.1rem; letter-spacing: -.02em; }
.owner-tag    { font-size: .7rem; background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
.main-content { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }
.card-box     { background: #fff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid #e2e8f0; }
.stat-value   { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
.stat-label   { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-top: .2rem; }
.badge-green  { background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 99px; font-size: .72rem; font-weight: 600; display: inline-block; }
.badge-amber  { background: #fef9c3; color: #a16207; padding: 2px 8px; border-radius: 99px; font-size: .72rem; font-weight: 600; display: inline-block; }
.badge-red    { background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 99px; font-size: .72rem; font-weight: 600; display: inline-block; }
.nav-link-owner { color: #475569; font-weight: 500; font-size: .875rem; padding: .4rem .75rem; border-radius: 6px; text-decoration: none; }
.nav-link-owner:hover, .nav-link-owner.active { background: #ede9fe; color: var(--accent); }
</style>
</head>
<body>

<nav class="owner-navbar d-flex align-items-center justify-content-between">
  <div class="d-flex align-items-center gap-3">
    <span class="brand-logo"><i class="bi bi-house-heart-fill me-1"></i>Roomee</span>
    <span class="owner-tag">Owner Portal</span>
  </div>
  <div class="d-flex align-items-center gap-1">
    <a href="<?= APP_URL ?>/owner-portal" class="nav-link-owner <?= str_contains($pageTitle??'','Dashboard')||($pageTitle??'')==='Owner Portal'?'active':'' ?>">
      <i class="bi bi-speedometer2 me-1"></i>Dashboard
    </a>
    <a href="<?= APP_URL ?>/owner-portal?tab=documents" class="nav-link-owner <?= str_contains($pageTitle??'','Documents')?'active':'' ?>">
      <i class="bi bi-folder2-open me-1"></i>Documents
    </a>
  </div>
  <div class="d-flex align-items-center gap-2">
    <span class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($_user['name'] ?? '') ?></span>
    <form action="<?= APP_URL ?>/logout" method="POST" class="m-0">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">Sign Out</button>
    </form>
  </div>
</nav>

<div class="main-content">

<?php
$flash = $_SESSION['flash'] ?? null;
if ($flash) { unset($_SESSION['flash']); ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>
