<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/Subscription.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle   = 'Plan & Billing';
$success     = $error = '';
$plans       = Subscription::getAllPlans();
$collections = Subscription::getAllCollections();
$activePlan  = Subscription::getActivePlan($activeCompanyId);
$activeCols  = Subscription::getActiveCollections($activeCompanyId);

// Admin can activate plans / collections from here
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired.';
    } elseif ($currentUser['role'] === 'admin') {
        $action = $_POST['action'] ?? '';

        if ($action === 'activate_plan') {
            $planCode = $_POST['plan_code'] ?? '';
            $months   = (int)($_POST['months'] ?? 12);
            if (Subscription::activatePlan($activeCompanyId, $planCode, $months, $currentUser['id'])) {
                $success = 'Plan "' . ($plans[$planCode]['name'] ?? $planCode) . '" activated for ' . $months . ' months.';
                $activePlan = Subscription::getActivePlan($activeCompanyId); // refresh
            } else {
                $error = 'Invalid plan code.';
            }
        } elseif ($action === 'activate_collection') {
            $colCode = $_POST['collection_code'] ?? '';
            $months  = (int)($_POST['months'] ?? 12);
            if (Subscription::activateCollection($activeCompanyId, $colCode, $months, $currentUser['id'])) {
                $success = 'Collection "' . ($collections[$colCode]['name'] ?? $colCode) . '" activated.';
                $activeCols = Subscription::getActiveCollections($activeCompanyId); // refresh
            } else {
                $error = 'Invalid collection code.';
            }
        }
    } else {
        // Non-admin "request upgrade" — log the intent
        $requestedPlan = htmlspecialchars($_POST['plan_code'] ?? '');
        Database::insert('activity_log', [
            'user_id'     => $currentUser['id'],
            'company_id'  => $activeCompanyId,
            'action'      => 'UPGRADE_REQUESTED',
            'description' => 'User requested upgrade to: ' . $requestedPlan,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        $success = 'Upgrade request received! We\'ll contact you at ' . htmlspecialchars($currentUser['email']) . ' within 1 business day.';
    }
}

$sourceLabel = [
    'subscription' => 'Active subscription',
    'trial'        => 'Free trial',
    'free'         => 'Free plan',
][$activePlan['_source']] ?? '';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-credit-card me-2 text-primary"></i>Plan &amp; Billing</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars($activeCompany['name']) ?></span>
      </div>
      <div class="topbar-actions">
        <a href="<?= url('pricing') ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
          <i class="bi bi-box-arrow-up-right me-1"></i>View Pricing
        </a>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Current plan banner -->
      <div class="billing-current-plan mb-4" style="--plan-color:<?= $activePlan['color'] ?>">
        <div class="billing-plan-left">
          <div class="billing-plan-source"><?= $sourceLabel ?></div>
          <div class="billing-plan-name"><?= $activePlan['name'] ?></div>
          <div class="billing-plan-tagline"><?= $activePlan['tagline'] ?></div>
          <?php if ($activePlan['_source'] === 'trial'): ?>
          <div class="billing-trial-notice">
            <i class="bi bi-hourglass-split me-1"></i>
            Trial ends <?= $activePlan['_trial_ends'] ?> — <?= $activePlan['_days_left'] ?> days remaining
          </div>
          <?php elseif (!empty($activePlan['_subscription']['expires_at'])): ?>
          <div class="billing-trial-notice">
            <i class="bi bi-calendar-check me-1"></i>
            Renews <?= date('d M Y', strtotime($activePlan['_subscription']['expires_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="billing-plan-right">
          <div class="billing-plan-price">
            <?php if ($activePlan['price_myr'] > 0): ?>
            <span class="billing-price-currency">RM</span>
            <span class="billing-price-amount"><?= number_format($activePlan['price_myr']) ?></span>
            <span class="billing-price-period">/ year</span>
            <?php else: ?>
            <span class="billing-price-amount">Free</span>
            <?php endif; ?>
          </div>
          <div class="billing-indicator-stat">
            <?php if ($activePlan['indicator_ids'] === 'all'): ?>
            <strong>200+</strong> indicators unlocked
            <?php else: ?>
            <strong><?= count($activePlan['indicator_ids']) ?></strong> indicators unlocked
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row g-4">

        <!-- Available plans -->
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header">
              <?= $currentUser['role'] === 'admin' ? 'Activate Plan (Admin)' : 'Upgrade Your Plan' ?>
            </div>
            <div class="p-3">
              <?php foreach ($plans as $plan):
                $isCurrent = $activePlan['code'] === $plan['code'];
              ?>
              <div class="billing-plan-option <?= $isCurrent ? 'billing-plan-current' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                  <div class="billing-plan-dot" style="background:<?= $plan['color'] ?>"></div>
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2">
                      <strong><?= $plan['name'] ?></strong>
                      <?php if ($plan['popular']): ?>
                      <span class="badge bg-success">Popular</span>
                      <?php endif; ?>
                      <?php if ($isCurrent): ?>
                      <span class="badge bg-secondary">Current</span>
                      <?php endif; ?>
                    </div>
                    <div class="text-muted small"><?= $plan['tagline'] ?></div>
                    <div class="small mt-1">
                      <?php if ($plan['indicator_ids'] === 'all'): ?>
                      <span class="text-purple fw-semibold">All 200+ indicators</span>
                      <?php else: ?>
                      <span class="text-success fw-semibold"><?= count($plan['indicator_ids']) ?> indicators</span>
                      <?php endif; ?>
                      &bull; <?= $plan['company_limit'] === 5 ? 'up to 5 companies' : '1 company' ?>
                    </div>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <div class="fw-bold" style="color:<?= $plan['color'] ?>">
                      <?= $plan['price_myr'] === 0 ? 'Free' : 'RM ' . number_format($plan['price_myr']) . '/yr' ?>
                    </div>
                    <?php if (!$isCurrent): ?>
                    <form method="POST" class="mt-1">
                      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                      <input type="hidden" name="plan_code" value="<?= $plan['code'] ?>">
                      <?php if ($currentUser['role'] === 'admin'): ?>
                      <input type="hidden" name="action" value="activate_plan">
                      <div class="d-flex gap-1 align-items-center">
                        <select name="months" class="form-select form-select-sm" style="width:70px">
                          <option value="12">12m</option>
                          <option value="6">6m</option>
                          <option value="1">1m</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Activate</button>
                      </div>
                      <?php else: ?>
                      <input type="hidden" name="action" value="request_upgrade">
                      <button type="submit" class="btn btn-sm btn-outline-primary"><?= $plan['cta'] ?></button>
                      <?php endif; ?>
                    </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>

              <?php if ($currentUser['role'] !== 'admin'): ?>
              <p class="text-muted small mt-3 mb-0">
                <i class="bi bi-envelope me-1"></i>
                We'll contact you at <strong><?= htmlspecialchars($currentUser['email']) ?></strong> to arrange payment and activation.
                Questions? Email <a href="mailto:hello@aiserve.my">hello@aiserve.my</a>
              </p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Indicator Collections -->
        <div class="col-lg-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Indicator Collections</span>
              <span class="text-muted small">Add-ons for Standard plan</span>
            </div>
            <div class="p-3">
              <?php foreach ($collections as $col):
                $isActive = in_array($col['code'], $activeCols) || $activePlan['indicator_ids'] === 'all';
              ?>
              <div class="collection-billing-row <?= $isActive ? 'collection-active' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                  <div class="collection-icon-sm" style="background:<?= $col['color'] ?>20;color:<?= $col['color'] ?>">
                    <i class="bi <?= $col['icon'] ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:13px"><?= $col['name'] ?></div>
                    <div class="text-muted" style="font-size:11px"><?= $col['indicator_count'] ?> indicators &bull; <?= $col['framework'] ?></div>
                    <div class="text-muted small"><?= $col['best_for'] ?></div>
                    <?php if (!empty($col['urgent_note'])): ?>
                    <div class="text-danger small mt-1"><i class="bi bi-clock me-1"></i><?= $col['urgent_note'] ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <?php if ($isActive): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <div class="fw-bold small" style="color:<?= $col['color'] ?>">RM <?= number_format($col['price_myr']) ?>/yr</div>
                    <form method="POST" class="mt-1">
                      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                      <input type="hidden" name="collection_code" value="<?= $col['code'] ?>">
                      <?php if ($currentUser['role'] === 'admin'): ?>
                      <input type="hidden" name="action" value="activate_collection">
                      <input type="hidden" name="months" value="12">
                      <button type="submit" class="btn btn-xs btn-outline-success">Activate</button>
                      <?php else: ?>
                      <input type="hidden" name="action" value="request_upgrade">
                      <input type="hidden" name="plan_code" value="collection_<?= $col['code'] ?>">
                      <button type="submit" class="btn btn-xs btn-outline-secondary">Request</button>
                      <?php endif; ?>
                    </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
