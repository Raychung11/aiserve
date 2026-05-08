<?php
/**
 * Pricing page — public, no auth required
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Subscription.php';

Auth::startSession();

$pageTitle  = 'Pricing';
$plans      = Subscription::getAllPlans();
$collections= Subscription::getAllCollections();
$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pricing — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body style="background:var(--color-bg)">

<!-- Top nav -->
<nav class="navbar bg-white border-bottom px-4 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>">
    <span style="background:#16a34a;color:white;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px"><i class="bi bi-leaf-fill"></i></span>
    <strong><?= APP_NAME ?></strong>
  </a>
  <div class="d-flex gap-2">
    <?php if ($isLoggedIn): ?>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline-primary btn-sm">Dashboard</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/login"    class="btn btn-outline-secondary btn-sm">Login</a>
    <a href="<?= APP_URL ?>/register" class="btn btn-primary btn-sm">Start Free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<div class="text-center py-5 px-3">
  <div class="d-inline-block bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill small fw-semibold mb-3">
    <i class="bi bi-shield-check me-1"></i>Malaysia ESG Compliance Platform
  </div>
  <h1 class="fw-bold" style="font-size:2.6rem">Simple, transparent pricing</h1>
  <p class="text-muted mt-2 mb-0" style="font-size:1.1rem">
    Start free. Upgrade when you need more indicators or frameworks.<br>
    All plans include a <strong>14-day free trial</strong> of Professional features.
  </p>
</div>

<!-- Plan cards -->
<div class="container" style="max-width:1000px">
  <div class="row g-4 justify-content-center mb-5">
    <?php foreach ($plans as $plan): ?>
    <?php $pop = $plan['popular']; ?>
    <div class="col-md-4">
      <div class="pricing-card <?= $pop ? 'pricing-card-popular' : '' ?>">
        <?php if ($pop): ?>
        <div class="pricing-popular-badge">Most Popular</div>
        <?php endif; ?>

        <div class="pricing-header" style="--plan-color: <?= $plan['color'] ?>">
          <div class="pricing-plan-name"><?= $plan['name'] ?></div>
          <div class="pricing-tagline"><?= $plan['tagline'] ?></div>
          <div class="pricing-price">
            <?php if ($plan['price_myr'] === 0): ?>
            <span class="pricing-amount">Free</span>
            <?php else: ?>
            <span class="pricing-currency">RM</span>
            <span class="pricing-amount"><?= number_format($plan['price_myr']) ?></span>
            <span class="pricing-period"> / year</span>
            <?php endif; ?>
          </div>
          <?php if ($plan['price_myr'] > 0): ?>
          <div class="pricing-monthly-equiv">
            ≈ RM <?= number_format($plan['price_myr'] / 12, 0) ?> / month
          </div>
          <?php endif; ?>
        </div>

        <div class="pricing-body">
          <!-- Indicator count highlight -->
          <div class="pricing-indicator-count">
            <i class="bi bi-list-check" style="color:<?= $plan['color'] ?>"></i>
            <?php if ($plan['indicator_ids'] === 'all'): ?>
            <strong>200+ indicators</strong> across 10 frameworks
            <?php else: ?>
            <strong><?= count($plan['indicator_ids']) ?> indicators</strong>
            (Bursa SEDG core)
            <?php endif; ?>
          </div>

          <ul class="pricing-features">
            <?php foreach ($plan['features'] as $feat): ?>
            <li><i class="bi bi-check-circle-fill" style="color:<?= $plan['color'] ?>"></i><?= $feat ?></li>
            <?php endforeach; ?>
            <?php foreach ($plan['locked_features'] ?? [] as $feat): ?>
            <li class="locked"><i class="bi bi-x-circle-fill text-muted"></i><span class="text-muted"><?= $feat ?></span></li>
            <?php endforeach; ?>
          </ul>

          <a href="<?= $isLoggedIn ? APP_URL.'/billing' : APP_URL.'/register' ?>"
             class="btn w-100 <?= $pop ? 'btn-primary' : 'btn-outline-secondary' ?> mt-2">
            <?= $plan['cta'] ?>
          </a>
          <?php if (!empty($plan['trial_days'])): ?>
          <div class="text-center text-muted small mt-2">
            <i class="bi bi-gift me-1"></i><?= $plan['trial_days'] ?>-day free trial included
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Indicator Collections -->
  <div class="mb-5">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Indicator Collections</h2>
      <p class="text-muted">Add-on bundles for Standard plan users. Unlock specific framework indicators without upgrading to Professional.</p>
    </div>

    <div class="row g-3">
      <?php foreach ($collections as $col): ?>
      <div class="col-md-6">
        <div class="collection-card">
          <div class="collection-card-header">
            <div class="collection-icon" style="background:<?= $col['color'] ?>20;color:<?= $col['color'] ?>">
              <i class="bi <?= $col['icon'] ?>"></i>
            </div>
            <div class="flex-grow-1">
              <div class="collection-name"><?= $col['name'] ?></div>
              <div class="collection-tagline"><?= $col['tagline'] ?></div>
            </div>
            <div class="collection-price-tag" style="background:<?= $col['color'] ?>15;color:<?= $col['color'] ?>">
              <?= $col['badge'] ?>
            </div>
          </div>
          <p class="collection-desc"><?= $col['description'] ?></p>
          <div class="collection-footer">
            <span class="collection-ind-count">
              <i class="bi bi-list-check me-1"></i><?= $col['indicator_count'] ?> indicators
            </span>
            <?php if ($col['urgent_note']): ?>
            <span class="collection-urgent"><i class="bi bi-clock me-1"></i><?= $col['urgent_note'] ?></span>
            <?php endif; ?>
            <span class="collection-bestfor text-muted small ms-auto"><?= $col['best_for'] ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="text-center text-muted small mt-3">
      Collections are available to Standard plan subscribers. Professional plan includes all collections.
      <a href="<?= $isLoggedIn ? APP_URL.'/billing' : APP_URL.'/register' ?>" class="text-primary">Get started →</a>
    </p>
  </div>

  <!-- FAQ row -->
  <div class="row g-4 mb-5">
    <div class="col-12"><h2 class="fw-bold text-center mb-4">Frequently Asked Questions</h2></div>
    <?php
    $faqs = [
        ['q' => 'What are the 15 Bursa SEDG mandatory indicators?',
         'a' => 'The Standard plan covers exactly the 15 primary mandatory disclosures required under Bursa Malaysia\'s Sustainability and ESG Disclosure Guide (SEDG 2022): 5 Environment (energy, water, Scope 1, Scope 2, waste), 6 Social (headcount, turnover, training, LTIFR, fatalities, parental leave), and 4 Governance (board gender, board age, anti-corruption, community investment).'],
        ['q' => 'Can I upgrade or downgrade at any time?',
         'a' => 'Yes. Contact us to change your plan. When upgrading mid-year, we pro-rate the difference. Collections can be added at any time and are valid for 12 months from purchase date.'],
        ['q' => 'What happens after the 14-day trial?',
         'a' => 'After the trial, you stay on the Starter (free) plan with 5 preview indicators until you activate a paid plan. Your data is never deleted — it\'s waiting for you when you upgrade.'],
        ['q' => 'Do I need a payment gateway to start?',
         'a' => 'Contact us at hello@aiserve.my to activate your plan. We issue an invoice and activate your account manually within 1 business day. Online self-serve payment is coming soon.'],
    ];
    foreach ($faqs as $faq): ?>
    <div class="col-md-6">
      <div class="faq-card">
        <div class="faq-q"><?= $faq['q'] ?></div>
        <div class="faq-a"><?= $faq['a'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Footer -->
<div class="text-center py-4 text-muted small border-top">
  © <?= date('Y') ?> AiServe Sdn Bhd &bull; hello@aiserve.my &bull; All prices in Malaysian Ringgit (MYR) excl. SST
</div>

</body>
</html>
