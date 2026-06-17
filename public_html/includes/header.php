<?php
/**
 * MM2H 管家 Platform — Shared Header
 * Variables expected: $page_title (string), $body_class (optional string)
 */

// Ensure foundation loaded
defined('APP_NAME') || require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
start_secure_session();

$page_title  = isset($page_title)  ? h($page_title) . ' — ' . APP_NAME : APP_NAME;
$body_class  = $body_class ?? '';
$is_auth     = auth_check();
$user_role   = $_SESSION['user_role'] ?? '';
$user_name   = $_SESSION['user_name'] ?? '';
$current_lang = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= html_lang() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="MM2H 管家 — Malaysia's AI-powered MM2H concierge platform for investors, families, and entrepreneurs.">
  <title><?= $page_title ?></title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Platform CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/platform.css">
</head>
<body class="<?= h($body_class) ?>">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark mm2h-navbar sticky-top">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>">
      <span class="brand-icon">管</span>
      <div>
        <span class="brand-name">MM2H 管家</span>
        <small class="brand-tagline d-none d-lg-block"><?= t('footer_tagline') ?></small>
      </div>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>"><?= t('nav_home') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/about"><?= t('nav_about') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/services"><?= t('nav_services') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/pricing"><?= t('nav_pricing') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/mm2h-guide">MM2H Guide</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/contact"><?= t('nav_contact') ?></a></li>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <!-- Language switcher -->
        <div class="lang-switcher">
          <a href="<?= APP_URL ?>/lang?set=en&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>"
             class="<?= $current_lang === 'en' ? 'active' : '' ?>">EN</a>
          <span>|</span>
          <a href="<?= APP_URL ?>/lang?set=zh_hant&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>"
             class="<?= $current_lang === 'zh_hant' ? 'active' : '' ?>">繁</a>
          <span>|</span>
          <a href="<?= APP_URL ?>/lang?set=zh_hans&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>"
             class="<?= $current_lang === 'zh_hans' ? 'active' : '' ?>">简</a>
        </div>

        <?php if ($is_auth): ?>
          <!-- Logged-in user menu -->
          <div class="dropdown">
            <button class="btn btn-outline-gold btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= h($user_name) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (is_admin()): ?>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/dashboard"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
              <?php elseif (is_partner()): ?>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/partner/dashboard"><i class="bi bi-briefcase me-2"></i>Partner Panel</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/member/dashboard"><i class="bi bi-house me-2"></i><?= t('nav_dashboard') ?></a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i><?= t('nav_logout') ?></a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= APP_URL ?>/login" class="btn btn-outline-gold btn-sm"><?= t('nav_login') ?></a>
          <a href="<?= APP_URL ?>/register" class="btn btn-gold btn-sm"><?= t('nav_register') ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<!-- /Navigation -->
