<?php
$page_title = 'MM2H 管家 — Malaysia\'s Foreign Capital Operating System';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 hero-content">
        <div class="hero-badge">
          <i class="bi bi-star-fill"></i>
          MM2H Concierge Platform
        </div>
        <h1 class="hero-title">
          <?= t('hero_headline') ?><br>
          <span>Malaysia</span>
        </h1>
        <p class="hero-subtitle"><?= t('hero_sub') ?></p>
        <div class="hero-actions">
          <a href="<?= APP_URL ?>/register" class="btn btn-gold btn-lg">
            <i class="bi bi-rocket-takeoff me-2"></i><?= t('hero_cta_primary') ?>
          </a>
          <a href="#eligibility" class="btn btn-outline-gold btn-lg">
            <i class="bi bi-shield-check me-2"></i><?= t('hero_cta_secondary') ?>
          </a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat-item">
            <div class="hero-stat-number">500+</div>
            <div class="hero-stat-label">Applications</div>
          </div>
          <div class="hero-stat-item">
            <div class="hero-stat-number">50+</div>
            <div class="hero-stat-label">Partner Banks & Agents</div>
          </div>
          <div class="hero-stat-item">
            <div class="hero-stat-number">15+</div>
            <div class="hero-stat-label">Countries Served</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="hero-visual text-center">
          <div class="p-4" style="background:rgba(255,255,255,.06);border-radius:20px;border:1px solid rgba(200,160,60,.2);">
            <div class="row g-3">
              <?php
              $features = [
                ['bi-passport', 'MM2H Visa'],
                ['bi-building', 'Property'],
                ['bi-bank', 'Banking'],
                ['bi-briefcase', 'Business'],
                ['bi-graph-up-arrow', 'Investment'],
                ['bi-people', 'Network'],
              ];
              foreach ($features as [$icon, $label]):
              ?>
              <div class="col-4">
                <div class="p-3 text-center" style="background:rgba(255,255,255,.06);border-radius:12px;border:1px solid rgba(200,160,60,.15);">
                  <i class="bi <?= $icon ?> text-gold fs-3"></i>
                  <div class="text-white small mt-1 fw-500"><?= $label ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Why Malaysia -->
<section class="section section-alt">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Why Malaysia</div>
      <h2 class="section-title">Malaysia — Asia's Premier Destination for Global Families</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="row g-4">
      <?php
      $reasons = [
        ['bi-sun', '#f59e0b', 'Tropical Climate', 'Year-round sunshine, world-class nature, and vibrant multicultural city life.'],
        ['bi-currency-dollar', '#10b981', 'Cost of Living', 'Premium lifestyle at 40–60% lower cost than Singapore, Hong Kong, or Taiwan.'],
        ['bi-hospital', '#3b82f6', 'Healthcare', 'ISO-accredited hospitals with English-speaking specialists at accessible prices.'],
        ['bi-mortarboard', '#8b5cf6', 'International Schools', 'Top international and Chinese-medium schools for all education levels.'],
        ['bi-shield-check', '#ef4444', 'Political Stability', 'Stable government, rule of law, and pro-investment policy framework.'],
        ['bi-globe2', '#06b6d4', 'Strategic Location', 'Gateway to ASEAN, within 3–6 hours of all major Asian business hubs.'],
      ];
      foreach ($reasons as [$icon, $color, $title, $desc]):
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card">
          <div class="card-icon" style="background:<?= $color ?>20;color:<?= $color ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 class="card-title"><?= $title ?></h5>
          <p class="card-text"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Services -->
<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label"><?= t('services_title') ?></div>
      <h2 class="section-title"><?= t('services_title') ?></h2>
      <div class="divider-gold"></div>
      <p class="text-muted mt-3 col-lg-6 mx-auto">From first inquiry to settled life in Malaysia — we guide you at every step.</p>
    </div>
    <div class="row g-4">
      <?php
      $services = [
        ['bi-passport-fill', 'svc_visa',     'End-to-end MM2H application support, agent matching, and document guidance.'],
        ['bi-buildings',     'svc_property', 'Curated Malaysian properties matched to your budget, location, and lifestyle.'],
        ['bi-bank2',         'svc_banking',  'Fixed deposit setup, account opening, and banking liaison at top Malaysian banks.'],
        ['bi-people-fill',   'svc_business', 'Connect with Malaysian entrepreneurs, investors, and business partners.'],
        ['bi-graph-up',      'svc_investment','Investment opportunities in property, fintech, FMCG, healthcare, and more.'],
        ['bi-diagram-3',     'svc_partner',  'Join our partner program and earn commissions for successful referrals.'],
      ];
      foreach ($services as [$icon, $key, $desc]):
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card">
          <div class="card-icon"><i class="bi <?= $icon ?>"></i></div>
          <h5 class="card-title"><?= t($key) ?></h5>
          <p class="card-text"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- AI Eligibility Check -->
<section id="eligibility" class="section section-dark">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-5">
        <div class="section-label"><?= t('ai_title') ?></div>
        <h2 class="section-title text-white"><?= t('ai_title') ?></h2>
        <div class="divider-gold" style="margin:1rem 0 1.5rem;"></div>
        <p class="text-muted mb-3">Answer a few quick questions and our AI concierge will assess your MM2H eligibility instantly.</p>
        <ul class="list-unstyled">
          <?php foreach (['Instant result', 'No registration required', 'Personalised document list', 'Next-step guidance'] as $pt): ?>
          <li class="mb-2 text-muted"><i class="bi bi-check-circle-fill text-gold me-2"></i><?= $pt ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-7">
        <div class="mm2h-form-card" style="border-color:rgba(200,160,60,.3);">
          <form id="eligibilityForm">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Age</label>
                <input type="number" name="age" class="form-control" min="18" max="99" placeholder="e.g. 45" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Nationality</label>
                <select name="nationality" class="form-select" required>
                  <option value="">Select country…</option>
                  <?php foreach (['Chinese', 'Taiwanese', 'Hongkongese', 'Singaporean', 'Japanese', 'Korean', 'British', 'Australian', 'American', 'Other'] as $nat): ?>
                  <option><?= $nat ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Monthly Income (USD)</label>
                <select name="monthly_income" class="form-select" required>
                  <option value="">Select range…</option>
                  <option value="under_2000">Under USD 2,000</option>
                  <option value="2000_5000">USD 2,000 – 5,000</option>
                  <option value="5000_10000">USD 5,000 – 10,000</option>
                  <option value="over_10000">Over USD 10,000</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Liquid Assets (USD)</label>
                <select name="liquid_assets" class="form-select" required>
                  <option value="">Select range…</option>
                  <option value="under_100k">Under USD 100,000</option>
                  <option value="100k_300k">USD 100,000 – 300,000</option>
                  <option value="300k_500k">USD 300,000 – 500,000</option>
                  <option value="over_500k">Over USD 500,000</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Ready for Fixed Deposit (MYR 500k)?</label>
                <select name="fd_readiness" class="form-select" required>
                  <option value="">Select…</option>
                  <option value="yes">Yes</option>
                  <option value="maybe">Possibly</option>
                  <option value="no">Not Yet</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <select name="purpose" class="form-select" required>
                  <option value="">Select…</option>
                  <option value="retirement">Retirement</option>
                  <option value="business">Business Relocation</option>
                  <option value="family">Family Relocation</option>
                  <option value="investment">Investment</option>
                  <option value="property">Property Purchase</option>
                  <option value="education">Education Planning</option>
                </select>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold w-100 py-2">
                  <i class="bi bi-shield-check me-2"></i>Check My Eligibility
                </button>
              </div>
            </div>
          </form>
          <div id="eligibilityResult" class="mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- How It Works -->
<section class="section section-alt">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Process</div>
      <h2 class="section-title">How MM2H 管家 Works</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="row g-4">
      <?php
      $steps = [
        ['1', 'bi-person-plus', 'Register & Check Eligibility', 'Create your account and complete our AI-powered eligibility assessment.'],
        ['2', 'bi-file-earmark-text', 'Complete Onboarding', 'Fill in your profile, upload documents, and get matched with a licensed MM2H agent.'],
        ['3', 'bi-arrow-repeat', 'Track Your Application', 'Monitor every stage of your MM2H application in real-time on your dashboard.'],
        ['4', 'bi-check-circle', 'Settle & Thrive', 'Receive final approval, set up banking, find property, and build your Malaysian life.'],
      ];
      foreach ($steps as [$num, $icon, $title, $desc]):
      ?>
      <div class="col-sm-6 col-lg-3">
        <div class="step-card">
          <div class="step-number"><?= $num ?></div>
          <i class="bi <?= $icon ?> fs-2 text-gold mb-2 d-block"></i>
          <h5><?= $title ?></h5>
          <p class="text-muted small"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= APP_URL ?>/register" class="btn btn-gold btn-lg">
        <i class="bi bi-rocket-takeoff me-2"></i><?= t('hero_cta_primary') ?>
      </a>
    </div>
  </div>
</section>

<!-- Pricing Preview -->
<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label"><?= t('pricing_title') ?></div>
      <h2 class="section-title"><?= t('pricing_title') ?></h2>
      <div class="divider-gold"></div>
    </div>
    <div class="row g-4 justify-content-center">
      <!-- Free -->
      <div class="col-md-4">
        <div class="pricing-card">
          <h4><?= t('plan_free') ?></h4>
          <div class="pricing-price"><?= t('plan_free_price') ?></div>
          <ul class="pricing-features">
            <li>Basic MM2H information</li>
            <li>Eligibility self-check</li>
            <li>Basic document checklist</li>
          </ul>
          <a href="<?= APP_URL ?>/register" class="btn btn-outline-gold w-100 mt-4"><?= t('btn_get_started') ?></a>
        </div>
      </div>
      <!-- Premium -->
      <div class="col-md-4">
        <div class="pricing-card featured">
          <div class="pricing-featured-badge">Most Popular</div>
          <h4><?= t('plan_premium') ?></h4>
          <div class="pricing-price"><?= t('plan_premium_price') ?></div>
          <ul class="pricing-features">
            <li>AI concierge dashboard</li>
            <li>Application progress tracker</li>
            <li>Property matching</li>
            <li>Banking support tracker</li>
            <li>Business networking access</li>
          </ul>
          <a href="<?= APP_URL ?>/register" class="btn btn-gold w-100 mt-4"><?= t('btn_subscribe') ?></a>
        </div>
      </div>
      <!-- Concierge -->
      <div class="col-md-4">
        <div class="pricing-card">
          <h4><?= t('plan_concierge') ?></h4>
          <div class="pricing-price"><?= t('plan_concierge_price') ?></div>
          <ul class="pricing-features">
            <li>Licensed agent support</li>
            <li>Property tour coordination</li>
            <li>Bank appointment setup</li>
            <li>Business partner matching</li>
            <li>Dedicated case manager</li>
          </ul>
          <a href="<?= APP_URL ?>/contact" class="btn btn-navy w-100 mt-4"><?= t('btn_contact_us') ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
