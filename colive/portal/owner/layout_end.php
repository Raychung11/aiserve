  </div><!-- /page-content -->
</div><!-- /main-wrap -->

<!-- ── Bottom tab nav (mobile only) ── -->
<?php
$btabs = [
    ['href' => 'dashboard.php', 'icon' => 'bi-house-fill',  'label' => 'Home',    'key' => 'dashboard'],
    ['href' => 'payouts.php',   'icon' => 'bi-wallet2',      'label' => 'Payouts', 'key' => 'payouts'],
    ['href' => 'units.php',     'icon' => 'bi-building',     'label' => 'Units',   'key' => 'units'],
];
?>
<nav class="bottom-tabs" aria-label="Bottom navigation">
  <?php foreach ($btabs as $bt):
    $isActive = ($activePage ?? '') === $bt['key'];
  ?>
  <a href="<?= e(APP_URL . '/portal/owner/' . $bt['href']) ?>"
     class="btab <?= $isActive ? 'active' : '' ?>">
    <i class="bi <?= e($bt['icon']) ?>"></i>
    <?= e($bt['label']) ?>
  </a>
  <?php endforeach; ?>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openSidebar() {
    document.getElementById('ownerSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('ownerSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
// Close sidebar on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
