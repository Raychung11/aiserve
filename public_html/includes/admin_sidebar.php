<?php
// Admin sidebar — included by every admin page
$admin_menu = [
  ['Overview', [
    ['admin/dashboard',   'bi-speedometer2',  'Dashboard'],
  ]],
  ['Applications', [
    ['admin/leads',       'bi-funnel',        'Leads'],
    ['admin/cases',       'bi-folder2-open',  'MM2H Cases'],
    ['admin/users',       'bi-people',        'Users'],
  ]],
  ['Partners', [
    ['admin/partners',    'bi-diagram-3',     'Partners'],
    ['admin/commissions', 'bi-cash-stack',    'Commissions'],
  ]],
  ['Services', [
    ['admin/properties',  'bi-buildings',     'Properties'],
    ['admin/banks',       'bi-bank2',         'Banks'],
    ['admin/services',    'bi-grid',          'Services'],
  ]],
  ['System', [
    ['admin/settings',    'bi-gear',          'Settings'],
  ]],
];
$current_path = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$current_dir  = basename(dirname($_SERVER['SCRIPT_FILENAME']));
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="<?= APP_URL ?>/admin/dashboard">
      <span class="brand-icon brand-icon-sm">管</span>
      <span class="text-white fw-bold small">MM2H Admin</span>
    </a>
  </div>
  <?php foreach ($admin_menu as [$label, $items]): ?>
  <div class="sidebar-section-label"><?= $label ?></div>
  <?php foreach ($items as [$path, $icon, $title]):
    $active = str_contains($_SERVER['REQUEST_URI'] ?? '', $path) ? 'active' : '';
  ?>
  <a class="nav-link <?= $active ?>" href="<?= APP_URL ?>/<?= $path ?>">
    <i class="bi <?= $icon ?>"></i><?= $title ?>
  </a>
  <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="sidebar-section-label">Account</div>
  <a class="nav-link" href="<?= APP_URL ?>/logout"><i class="bi bi-box-arrow-right"></i><?= t('nav_logout') ?></a>
</div>
