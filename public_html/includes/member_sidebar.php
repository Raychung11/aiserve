<?php
// Member sidebar — included by every member page
$member_menu = [
  ['My Application', [
    ['member/dashboard',       'bi-house-fill',       'Dashboard'],
    ['member/onboarding',      'bi-person-lines-fill','Complete Profile'],
    ['member/case-progress',   'bi-folder2-open',     'Case Progress'],
    ['member/checklist',       'bi-list-check',       'Document Checklist'],
    ['member/documents',       'bi-folder-fill',      'My Documents'],
  ]],
  ['Concierge Services', [
    ['member/property-match',  'bi-buildings',        'Property Match'],
    ['member/bank-support',    'bi-bank2',            'Banking Support'],
    ['member/business-network','bi-people-fill',      'Business Network'],
  ]],
  ['Account', [
    ['member/profile',         'bi-person-circle',    'My Profile'],
  ]],
];
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="<?= APP_URL ?>/member/dashboard">
      <span class="brand-icon brand-icon-sm">管</span>
      <span class="text-white fw-bold small">MM2H 管家</span>
    </a>
  </div>
  <?php foreach ($member_menu as [$label, $items]): ?>
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
