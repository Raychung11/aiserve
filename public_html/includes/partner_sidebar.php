<?php
$partner_menu = [
  ['Overview', [
    ['partner/dashboard',   'bi-speedometer2',  'Dashboard'],
  ]],
  ['Business', [
    ['partner/leads',       'bi-funnel',        'My Leads'],
    ['partner/referrals',   'bi-diagram-3',     'Referrals'],
    ['partner/commissions', 'bi-cash-stack',    'Commissions'],
  ]],
  ['Account', [
    ['partner/profile',     'bi-person-circle', 'My Profile'],
  ]],
];
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="<?= APP_URL ?>/partner/dashboard">
      <span class="brand-icon brand-icon-sm">管</span>
      <span class="text-white fw-bold small">Partner Portal</span>
    </a>
  </div>
  <?php foreach ($partner_menu as [$label, $items]): ?>
  <div class="sidebar-section-label"><?= $label ?></div>
  <?php foreach ($items as [$path, $icon, $title]):
    $active = str_contains($_SERVER['REQUEST_URI'] ?? '', basename($path)) ? 'active' : '';
  ?>
  <a class="nav-link <?= $active ?>" href="<?= APP_URL ?>/<?= $path ?>">
    <i class="bi <?= $icon ?>"></i><?= $title ?>
  </a>
  <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="sidebar-section-label">Account</div>
  <a class="nav-link" href="<?= APP_URL ?>/logout"><i class="bi bi-box-arrow-right"></i><?= t('nav_logout') ?></a>
</div>
