<!-- Footer -->
<footer class="mm2h-footer mt-auto">
  <div class="container">
    <div class="row g-4">
      <!-- Brand column -->
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="brand-icon brand-icon-sm">管</span>
          <span class="text-gold fw-bold fs-5">MM2H 管家</span>
        </div>
        <p class="footer-text small"><?= t('footer_tagline') ?></p>
        <p class="footer-text small"><?= t('footer_operated') ?></p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="footer-social"><i class="bi bi-facebook fs-5"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-linkedin fs-5"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-whatsapp fs-5"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-wechat fs-5"></i></a>
        </div>
      </div>

      <!-- Services -->
      <div class="col-6 col-lg-2">
        <h6 class="text-gold text-uppercase fw-bold small mb-3"><?= t('nav_services') ?></h6>
        <ul class="list-unstyled small">
          <li class="mb-1"><a href="<?= APP_URL ?>/services" class="footer-link"><?= t('svc_visa') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/services" class="footer-link"><?= t('svc_property') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/services" class="footer-link"><?= t('svc_banking') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/services" class="footer-link"><?= t('svc_business') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/services" class="footer-link"><?= t('svc_investment') ?></a></li>
        </ul>
      </div>

      <!-- Platform -->
      <div class="col-6 col-lg-2">
        <h6 class="text-gold text-uppercase fw-bold small mb-3">Platform</h6>
        <ul class="list-unstyled small">
          <li class="mb-1"><a href="<?= APP_URL ?>/about"    class="footer-link"><?= t('nav_about') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/pricing"  class="footer-link"><?= t('nav_pricing') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/register" class="footer-link"><?= t('nav_register') ?></a></li>
          <li class="mb-1"><a href="<?= APP_URL ?>/contact"  class="footer-link"><?= t('nav_contact') ?></a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-lg-4">
        <h6 class="text-gold text-uppercase fw-bold small mb-3"><?= t('nav_contact') ?></h6>
        <p class="footer-text small mb-1"><i class="bi bi-envelope me-2"></i><?= h(get_setting('site_email', 'info@mm2h.com')) ?></p>
        <p class="footer-text small mb-1"><i class="bi bi-telephone me-2"></i><?= h(get_setting('site_phone', '+60 3-XXXX XXXX')) ?></p>
        <p class="footer-text small"><i class="bi bi-geo-alt me-2"></i>Kuala Lumpur, Malaysia</p>
      </div>
    </div>

    <hr class="footer-divider">

    <!-- Disclaimer -->
    <div class="footer-disclaimer">
      <p class="footer-text small mb-2">
        <strong class="footer-text-bright"><?= t('warning') ?>:</strong>
        <?= t('disclaimer') ?>
      </p>
    </div>

    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="footer-text small mb-0">&copy; <?= date('Y') ?> MM2H 管家. <?= t('footer_rights') ?></p>
      </div>
      <div class="col-md-6 text-md-end">
        <div class="lang-switcher-footer">
          <a href="<?= APP_URL ?>/lang?set=en&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>">English</a>
          <span>|</span>
          <a href="<?= APP_URL ?>/lang?set=zh_hant&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>">繁體中文</a>
          <span>|</span>
          <a href="<?= APP_URL ?>/lang?set=zh_hans&amp;return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>">简体中文</a>
        </div>
      </div>
    </div>
  </div>
</footer>
<!-- /Footer -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Platform JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
