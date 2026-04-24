<?php
$page_title = '403 — Access Denied';
require __DIR__ . '/header.php';
?>
<div class="container min-vh-75 d-flex align-items-center justify-content-center py-5">
  <div class="text-center">
    <div class="display-1 fw-bold text-gold">403</div>
    <h2 class="mt-3">Access Denied</h2>
    <p class="text-muted">You don't have permission to access this page.</p>
    <a href="<?= APP_URL ?>" class="btn btn-gold mt-3">Return Home</a>
  </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
