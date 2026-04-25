<?php
$page_title = t('about_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section section-dark" style="padding:4rem 0;">
  <div class="container">
    <div class="section-label"><?= t('nav_about') ?></div>
    <h1 class="hero-title" style="font-size:clamp(1.8rem,4vw,3rem);"><?= t('about_title') ?></h1>
    <p class="hero-subtitle on-dark"><?= t('about_desc') ?></p>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div class="section-label">Our Mission</div>
        <h2 class="section-title">A Foreign Capital Operating System for Malaysia</h2>
        <p class="text-muted">MM2H 管家 was built to solve the fragmented, confusing, and time-consuming process that foreign investors, entrepreneurs, and families face when trying to establish roots in Malaysia.</p>
        <p class="text-muted">We are not just a visa platform. We are a complete operating system — connecting applicants with licensed agents, property, banking, legal, and business infrastructure.</p>
        <p class="text-muted">Operated by SLV Group, Malaysia's trusted cross-border investment facilitation company, MM2H 管家 brings together the best partners to deliver a seamless, premium experience.</p>
      </div>
      <div class="col-lg-6">
        <div class="row g-4">
          <?php
          $pillars = [
            ['bi-shield-check', 'Licensed & Compliant', 'All visa work handled exclusively by licensed MM2H agents under Malaysian immigration law.'],
            ['bi-robot', 'AI-Powered', 'Smart onboarding, eligibility assessment, and document guidance powered by AI.'],
            ['bi-diagram-3-fill', 'Partner Network', 'Curated network of banks, developers, agents, and business connectors.'],
            ['bi-translate', 'Multilingual', 'Full support in English, Traditional Chinese, and Simplified Chinese.'],
          ];
          foreach ($pillars as [$icon, $title, $desc]):
          ?>
          <div class="col-6">
            <div class="mm2h-card">
              <i class="bi <?= $icon ?> fs-2 text-gold mb-2 d-block"></i>
              <h6 class="fw-bold"><?= $title ?></h6>
              <p class="card-text small"><?= $desc ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Disclaimer</div>
      <h2 class="section-title">Important Notice</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="mm2h-form-card" style="border-left:4px solid var(--secondary);">
          <p class="mb-0"><?= t('disclaimer') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
