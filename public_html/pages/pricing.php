<?php
$page_title = t('pricing_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section section-dark" style="padding:4rem 0;">
  <div class="container text-center">
    <div class="section-label">Plans</div>
    <h1 class="hero-title" style="font-size:clamp(1.8rem,4vw,3rem);"><?= t('pricing_title') ?></h1>
    <p class="hero-subtitle on-dark mx-auto" style="max-width:520px;">Choose the plan that fits your journey. Upgrade anytime.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Free -->
      <div class="col-md-6 col-lg-4">
        <div class="pricing-card">
          <div class="text-gold fw-bold small text-uppercase mb-2 letter-spacing-1">Starter</div>
          <h3><?= t('plan_free') ?></h3>
          <div class="pricing-price"><?= t('plan_free_price') ?><small>/forever</small></div>
          <p class="text-muted small mb-4">Get started with essential MM2H information and tools.</p>
          <ul class="pricing-features mb-4">
            <li>MM2H eligibility self-check</li>
            <li>Basic document checklist</li>
            <li>General MM2H information</li>
            <li>Language support (EN / 繁 / 简)</li>
          </ul>
          <a href="<?= APP_URL ?>/register" class="btn btn-outline-gold w-100"><?= t('btn_get_started') ?></a>
        </div>
      </div>

      <!-- Premium -->
      <div class="col-md-6 col-lg-4">
        <div class="pricing-card featured">
          <div class="pricing-featured-badge">⭐ Most Popular</div>
          <div class="text-gold fw-bold small text-uppercase mb-2">Premium</div>
          <h3><?= t('plan_premium') ?></h3>
          <div class="pricing-price"><?= t('plan_premium_price') ?></div>
          <p class="text-muted small mb-4">Everything you need to manage your MM2H application.</p>
          <ul class="pricing-features mb-4">
            <li>AI concierge dashboard</li>
            <li>Application progress tracker</li>
            <li>Full document management</li>
            <li>Property matching</li>
            <li>Banking support tracker</li>
            <li>Business networking access</li>
            <li>Partner introductions</li>
            <li>Priority support</li>
          </ul>
          <a href="<?= APP_URL ?>/register" class="btn btn-gold w-100"><?= t('btn_subscribe') ?></a>
        </div>
      </div>

      <!-- Concierge -->
      <div class="col-md-6 col-lg-4">
        <div class="pricing-card">
          <div class="text-gold fw-bold small text-uppercase mb-2">Enterprise</div>
          <h3><?= t('plan_concierge') ?></h3>
          <div class="pricing-price"><?= t('plan_concierge_price') ?></div>
          <p class="text-muted small mb-4">Full white-glove service for complex cases and high-net-worth clients.</p>
          <ul class="pricing-features mb-4">
            <li>Dedicated case manager</li>
            <li>Licensed MM2H agent support</li>
            <li>Physical property tours</li>
            <li>Bank relationship manager</li>
            <li>Business partner matching</li>
            <li>Legal & accounting referrals</li>
            <li>Investment deal access</li>
            <li>VIP network introductions</li>
          </ul>
          <a href="<?= APP_URL ?>/contact" class="btn btn-navy w-100"><?= t('btn_contact_us') ?></a>
        </div>
      </div>
    </div>

    <!-- Partner / Affiliate note -->
    <div class="row justify-content-center mt-5">
      <div class="col-lg-8">
        <div class="mm2h-card text-center" style="border-color:var(--secondary);">
          <i class="bi bi-diagram-3-fill fs-2 text-gold mb-3 d-block"></i>
          <h4>Are you an Agent or Partner?</h4>
          <p class="text-muted">MM2H agents, property partners, and business connectors can join our partner network and earn 15–25% commission on successful deals.</p>
          <a href="<?= APP_URL ?>/contact" class="btn btn-gold"><?= t('btn_contact_us') ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Frequently Asked Questions</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">
          <?php
          $faqs = [
            ['Is MM2H 管家 a licensed visa agent?', 'No. MM2H 管家 is a digital concierge and support platform. All visa applications are handled by licensed MM2H agents who are accredited by the Malaysian government.'],
            ['How long does MM2H approval take?', 'Processing times vary. Typically 3–6 months for government processing after submission. The preparation stage (documents, eligibility review) is managed on our platform.'],
            ['What is the Fixed Deposit requirement?', 'Under the 2024 MM2H conditions, applicants are required to place a Fixed Deposit of MYR 500,000 (reduced to MYR 300,000 after property purchase). Our banking partners facilitate this.'],
            ['Can I cancel my Premium subscription?', 'Yes, you may cancel at any time. Your access continues until the end of the billing period.'],
            ['What languages are supported?', 'The platform fully supports English, Traditional Chinese (繁體中文), and Simplified Chinese (简体中文). Our support team is also multilingual.'],
          ];
          foreach ($faqs as $i => [$q, $a]):
          ?>
          <div class="accordion-item border mb-2 rounded-mm2h overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold"
                      type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                <?= h($q) ?>
              </button>
            </h2>
            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted"><?= h($a) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
