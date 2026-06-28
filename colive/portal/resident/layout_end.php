  </div><!-- /res-content -->

  <!-- Bottom tab bar (mobile only) -->
  <nav class="res-bottom-tabs d-md-none" aria-label="Mobile navigation">
    <?php
    $tabNav = [
        ['href' => 'dashboard.php',   'icon' => 'bi-house-fill',     'label' => 'Home',        'key' => 'dashboard'],
        ['href' => 'invoices.php',    'icon' => 'bi-receipt-cutoff', 'label' => 'Invoices',    'key' => 'invoices'],
        ['href' => 'maintenance.php', 'icon' => 'bi-tools',          'label' => 'Maintenance', 'key' => 'maintenance'],
        ['href' => 'support.php',     'icon' => 'bi-chat-dots-fill', 'label' => 'Support',     'key' => 'support'],
    ];
    foreach ($tabNav as $tab):
        $isActive = ($activePage ?? '') === $tab['key'];
    ?>
    <a href="<?= e(APP_URL . '/portal/resident/' . $tab['href']) ?>"
       class="res-tab-item <?= $isActive ? 'active' : '' ?>">
      <i class="bi <?= e($tab['icon']) ?>"></i>
      <?= e($tab['label']) ?>
    </a>
    <?php endforeach; ?>
  </nav>

</div><!-- /res-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var sidebar  = document.getElementById('resSidebar');
  var overlay  = document.getElementById('sidebarOverlay');
  var hamburger = document.getElementById('hamburgerBtn');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (hamburger) {
    hamburger.addEventListener('click', function () {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }
})();
</script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
