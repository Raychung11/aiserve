<?php
require_once __DIR__.'/../includes/auth_check.php';

$flash = [];
$tenant = Database::fetchOne("SELECT * FROM tenants WHERE id=?", [$_tenantId]);

// Handle webhook callback from Billplz
if (isset($_GET['billplz_callback'])) {
    // Billplz redirects here after payment with GET params
    $billplzId  = $_GET['billplz']['id'] ?? '';
    $paid       = ($_GET['billplz']['paid'] ?? 'false') === 'true';
    $paidAt     = $_GET['billplz']['paid_at'] ?? '';
    $xSignature = $_GET['billplz']['x_signature'] ?? '';

    if ($billplzId && $paid) {
        $sub = Database::fetchOne("SELECT * FROM subscriptions WHERE bill_id=? AND tenant_id=?", [$billplzId, $_tenantId]);
        if ($sub && $sub['status'] === 'pending') {
            // Verify signature
            $data = $_GET['billplz'];
            if (BillplzService::verifyWebhook($data)) {
                Database::update('subscriptions', [
                    'status'   => 'active',
                    'paid_at'  => date('Y-m-d H:i:s', strtotime($paidAt ?: 'now')),
                ], 'id=?', [$sub['id']]);
                // Update tenant plan and status
                Database::update('tenants', [
                    'plan'       => $sub['plan'],
                    'status'     => 'active',
                    'trial_ends_at' => null,
                ], 'id=?', [$_tenantId]);
                ActivityLog::record($_tenantId, $_user['id'], 'subscription_paid', 'subscriptions', $sub['id'], "Plan {$sub['plan']} activated");
                $_SESSION['flash'] = ['type'=>'success','msg'=>'Payment confirmed! Your plan has been activated.'];
            }
        }
    }
    header('Location: '.APP_URL.'/subscription'); exit;
}

// Handle POST: initiate payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $plan     = $_POST['plan'] ?? '';
    $billing  = $_POST['billing'] ?? 'monthly'; // monthly or annual
    $validPlans = ['starter','growth','enterprise'];

    if (!in_array($plan, $validPlans)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid plan selected.'];
        header('Location: '.APP_URL.'/subscription'); exit;
    }

    $amount = $billing === 'annual'
        ? PLAN_LIMITS[$plan]['price_annual']
        : PLAN_LIMITS[$plan]['price_monthly'];

    $result = BillplzService::createSubscriptionBill($tenant, $_user, $plan, (float)$amount);

    if ($result && !empty($result['url'])) {
        // Save pending subscription record
        Database::insert('subscriptions', [
            'tenant_id'  => $_tenantId,
            'plan'       => $plan,
            'amount'     => $amount,
            'billing'    => $billing,
            'status'     => 'pending',
            'bill_id'    => $result['id'] ?? '',
            'bill_url'   => $result['url'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::record($_tenantId, $_user['id'], 'subscription_initiated', 'subscriptions', 0, "Initiated $plan plan payment RM$amount");
        header('Location: '.$result['url']); exit;
    } else {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Payment gateway error. Please try again.'];
        header('Location: '.APP_URL.'/subscription'); exit;
    }
}

// Load subscription history
$subscriptions = Database::fetchAll(
    "SELECT * FROM subscriptions WHERE tenant_id=? ORDER BY created_at DESC LIMIT 10",
    [$_tenantId]
);

// Trial info
$trialEnds  = $tenant['trial_ends_at'] ?? null;
$trialDays  = $trialEnds ? max(0, (int)((strtotime($trialEnds) - time()) / 86400)) : 0;
$isOnTrial  = $trialEnds && strtotime($trialEnds) > time();
$currentPlan = strtolower($tenant['plan'] ?? 'starter');

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

$pageTitle = 'Subscription & Billing';
include __DIR__.'/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Subscription & Billing</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Manage your STRHub AI plan</p>
  </div>
  <?php if ($isOnTrial): ?>
  <span class="badge bg-warning text-dark px-3 py-2" style="font-size:.85rem;">Trial — <?= $trialDays ?> days left</span>
  <?php endif; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Current Plan -->
<div class="card-box mb-4">
  <div class="d-flex align-items-center justify-content-between">
    <div>
      <h6 class="fw-semibold mb-1">Current Plan</h6>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary px-3 py-2" style="font-size:.9rem;"><?= ucfirst($currentPlan) ?></span>
        <span class="text-muted" style="font-size:.875rem;"><?= $tenant['status']==='trial'?'(Free Trial)':ucfirst($tenant['status']??'active') ?></span>
      </div>
    </div>
    <div class="text-end">
      <div class="text-muted" style="font-size:.75rem;">Properties</div>
      <div class="fw-semibold"><?= PLAN_LIMITS[$currentPlan]['properties'] === 9999 ? 'Unlimited' : PLAN_LIMITS[$currentPlan]['properties'] ?></div>
    </div>
    <div class="text-end">
      <div class="text-muted" style="font-size:.75rem;">Agents</div>
      <div class="fw-semibold"><?= PLAN_LIMITS[$currentPlan]['agents'] === 9999 ? 'Unlimited' : PLAN_LIMITS[$currentPlan]['agents'] ?></div>
    </div>
  </div>
</div>

<!-- Billing Toggle -->
<div class="text-center mb-4">
  <div class="btn-group" role="group" id="billingToggle">
    <button type="button" class="btn btn-outline-primary active" data-billing="monthly">Monthly</button>
    <button type="button" class="btn btn-outline-primary" data-billing="annual">Annual <span class="badge bg-success ms-1">Save ~16%</span></button>
  </div>
</div>

<!-- Plan Cards -->
<div class="row g-3 mb-4">
  <?php
  $plans = [
    'starter'    => ['name'=>'Starter',    'icon'=>'bi-house',      'color'=>'#3b82f6', 'desc'=>'Perfect for individual property managers starting out'],
    'growth'     => ['name'=>'Growth',     'icon'=>'bi-graph-up',   'color'=>'#8b5cf6', 'desc'=>'For growing operators managing multiple properties'],
    'enterprise' => ['name'=>'Enterprise', 'icon'=>'bi-building',   'color'=>'#059669', 'desc'=>'For agencies and large-scale operations'],
  ];
  foreach ($plans as $key => $plan):
    $isCurrentPlan = $currentPlan === $key;
    $monthlyPrice  = PLAN_LIMITS[$key]['price_monthly'];
    $annualPrice   = PLAN_LIMITS[$key]['price_annual'];
    $maxProps      = PLAN_LIMITS[$key]['properties'];
    $maxAgents     = PLAN_LIMITS[$key]['agents'];
  ?>
  <div class="col-md-4">
    <div class="card-box h-100 <?= $isCurrentPlan ? 'border border-primary' : '' ?>" style="position:relative;">
      <?php if ($isCurrentPlan): ?>
      <span class="badge bg-primary position-absolute top-0 end-0 m-2">Current Plan</span>
      <?php endif; ?>
      <div class="d-flex align-items-center gap-2 mb-3">
        <div style="width:36px;height:36px;background:<?= $plan['color'] ?>20;border-radius:8px;display:flex;align-items:center;justify-content:center;">
          <i class="<?= $plan['icon'] ?>" style="color:<?= $plan['color'] ?>;font-size:1.1rem;"></i>
        </div>
        <h6 class="fw-bold mb-0"><?= $plan['name'] ?></h6>
      </div>
      <p class="text-muted mb-3" style="font-size:.8rem;"><?= $plan['desc'] ?></p>

      <div class="mb-3">
        <div class="price-monthly">
          <span class="fw-bold" style="font-size:1.6rem;">RM <?= number_format($monthlyPrice,0) ?></span>
          <span class="text-muted" style="font-size:.8rem;">/month</span>
        </div>
        <div class="price-annual d-none">
          <span class="fw-bold" style="font-size:1.6rem;">RM <?= number_format($annualPrice,0) ?></span>
          <span class="text-muted" style="font-size:.8rem;">/year</span>
          <div class="text-success" style="font-size:.75rem;">RM <?= number_format($annualPrice/12,0) ?>/mo billed annually</div>
        </div>
      </div>

      <ul class="list-unstyled mb-4" style="font-size:.82rem;">
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><?= $maxProps===9999?'Unlimited':$maxProps ?> Properties</li>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><?= $maxAgents===9999?'Unlimited':$maxAgents ?> Agents</li>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Compliance Engine</li>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>ROI Calculator</li>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Strategy Recommendations</li>
        <?php if ($key !== 'starter'): ?>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Agent Commission Tracking</li>
        <?php endif; ?>
        <?php if ($key === 'enterprise'): ?>
        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Priority Support</li>
        <?php endif; ?>
      </ul>

      <?php if (!$isCurrentPlan): ?>
      <form method="POST" action="<?= APP_URL ?>/subscription" class="plan-form" data-plan="<?= $key ?>">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="plan" value="<?= $key ?>">
        <input type="hidden" name="billing" value="monthly" class="billing-input">
        <button type="submit" class="btn btn-primary w-100" style="background:<?= $plan['color'] ?>;border-color:<?= $plan['color'] ?>;">
          <?= $currentPlan === 'trial' ? 'Subscribe' : 'Switch to '.$plan['name'] ?>
        </button>
      </form>
      <?php else: ?>
      <button class="btn btn-outline-secondary w-100" disabled>Current Plan</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Payment History -->
<?php if ($subscriptions): ?>
<div class="card-box">
  <h6 class="fw-semibold mb-3">Payment History</h6>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead>
        <tr>
          <th style="font-size:.75rem;">Date</th>
          <th style="font-size:.75rem;">Plan</th>
          <th style="font-size:.75rem;">Billing</th>
          <th style="font-size:.75rem;">Amount</th>
          <th style="font-size:.75rem;">Status</th>
          <th style="font-size:.75rem;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subscriptions as $sub): ?>
        <tr>
          <td style="font-size:.8rem;"><?= date('d M Y', strtotime($sub['created_at'])) ?></td>
          <td style="font-size:.8rem;"><?= ucfirst($sub['plan']) ?></td>
          <td style="font-size:.8rem;"><?= ucfirst($sub['billing'] ?? 'monthly') ?></td>
          <td style="font-size:.8rem;" class="fw-semibold">RM <?= number_format($sub['amount'],0) ?></td>
          <td>
            <?php if ($sub['status'] === 'active'): ?>
              <span class="badge-green">Paid</span>
            <?php elseif ($sub['status'] === 'pending'): ?>
              <span class="badge-amber">Pending</span>
            <?php else: ?>
              <span class="badge bg-secondary" style="font-size:.72rem;"><?= ucfirst($sub['status']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($sub['status'] === 'pending' && !empty($sub['bill_url'])): ?>
            <a href="<?= htmlspecialchars($sub['bill_url']) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px;" target="_blank">Pay Now</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- FPX Note -->
<div class="alert alert-light border mt-3" style="font-size:.82rem;">
  <i class="bi bi-shield-check me-2 text-primary"></i>
  Payments are processed securely via <strong>Billplz FPX</strong>. All Malaysian banks are supported.
  For billing queries, contact <a href="mailto:billing@slvgroup.my">billing@slvgroup.my</a>.
</div>

<?php
$extraJs = <<<'JS'
// Billing toggle
const btns = document.querySelectorAll('#billingToggle button');
btns.forEach(btn => {
  btn.addEventListener('click', function() {
    btns.forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const billing = this.dataset.billing;
    document.querySelectorAll('.price-monthly').forEach(el => el.classList.toggle('d-none', billing === 'annual'));
    document.querySelectorAll('.price-annual').forEach(el => el.classList.toggle('d-none', billing === 'monthly'));
    document.querySelectorAll('.billing-input').forEach(el => el.value = billing);
  });
});
JS;

include __DIR__.'/../includes/footer.php';
