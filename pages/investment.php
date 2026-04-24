<?php
require_once __DIR__.'/../includes/auth_check.php';

$propertyId = (int)($_GET['property_id'] ?? 0);
$flash = [];

$properties = Database::fetchAll(
    "SELECT id, name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name",
    [$_tenantId]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $pid = (int)$_POST['property_id'];
    $prop = Database::fetchOne("SELECT id FROM properties WHERE id=? AND tenant_id=?", [$pid, $_tenantId]);
    if (!$prop) { header('Location: '.APP_URL.'/investment'); exit; }

    $fields = [
        'tenant_id'          => $_tenantId,
        'property_id'        => $pid,
        'purchase_price'     => (float)($_POST['purchase_price'] ?? 0),
        'stamp_duty'         => (float)($_POST['stamp_duty'] ?? 0),
        'legal_fees'         => (float)($_POST['legal_fees'] ?? 0),
        'renovation_cost'    => (float)($_POST['renovation_cost'] ?? 0),
        'furniture_cost'     => (float)($_POST['furniture_cost'] ?? 0),
        'appliances_cost'    => (float)($_POST['appliances_cost'] ?? 0),
        'other_costs'        => (float)($_POST['other_costs'] ?? 0),
        'monthly_loan'       => (float)($_POST['monthly_loan'] ?? 0),
        'loan_tenure_months' => (int)($_POST['loan_tenure_months'] ?? 0),
        'interest_rate'      => (float)($_POST['interest_rate'] ?? 0),
        'notes'              => trim($_POST['notes'] ?? ''),
        'updated_at'         => date('Y-m-d H:i:s'),
    ];

    $existing = Database::fetchOne("SELECT id FROM investments WHERE property_id=? AND tenant_id=?", [$pid, $_tenantId]);
    if ($existing) {
        Database::update('investments', $fields, 'id=?', [$existing['id']]);
        ActivityLog::record($_tenantId, $_user['id'], 'investment_update', 'investments', $existing['id'], "Updated investment for property #$pid");
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Investment data updated.'];
    } else {
        $fields['created_at'] = date('Y-m-d H:i:s');
        $newId = Database::insert('investments', $fields);
        ActivityLog::record($_tenantId, $_user['id'], 'investment_create', 'investments', $newId, "Created investment for property #$pid");
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Investment data saved.'];
    }
    header('Location: '.APP_URL.'/investment?property_id='.$pid); exit;
}

$investment = null;
$property   = null;
$roi        = null;

if ($propertyId) {
    $property   = Database::fetchOne("SELECT * FROM properties WHERE id=? AND tenant_id=? AND deleted_at IS NULL", [$propertyId, $_tenantId]);
    if ($property) {
        $investment = Database::fetchOne("SELECT * FROM investments WHERE property_id=? AND tenant_id=?", [$propertyId, $_tenantId]);
        $roi        = ROIEngine::calculate($propertyId, $_tenantId, 12);
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// Build JS only when property is loaded
$extraJs = '';
if ($property) {
    $extraJs = <<<JS
const invFields = ['purchase_price','stamp_duty','legal_fees','renovation_cost','furniture_cost','appliances_cost','other_costs'];
function recalcTotal() {
  let total = 0;
  invFields.forEach(f => {
    const el = document.querySelector('[name="'+f+'"]');
    if (el) total += parseFloat(el.value)||0;
  });
  const el = document.getElementById('totalAmt');
  if (el) el.textContent = 'RM ' + total.toLocaleString('en-MY',{minimumFractionDigits:0,maximumFractionDigits:0});
}
invFields.forEach(f => {
  const el = document.querySelector('[name="'+f+'"]');
  if (el) el.addEventListener('input', recalcTotal);
});
recalcTotal();
JS;
}

$pageTitle = 'Investment & ROI';
include __DIR__.'/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Investment & ROI</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Track capital invested and calculate return on investment</p>
  </div>
  <a href="<?= APP_URL ?>/roi-calculator" class="btn btn-outline-primary btn-sm"><i class="bi bi-calculator me-1"></i>ROI Calculator</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Property Selector -->
<div class="card-box mb-4">
  <h6 class="fw-semibold mb-3">Select Property</h6>
  <form method="GET" action="<?= APP_URL ?>/investment" class="row g-2">
    <div class="col-md-6">
      <select name="property_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Choose a property --</option>
        <?php foreach ($properties as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $p['id']==$propertyId?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($property): ?>

<?php if ($roi && ($roi['revenue'] > 0 || $investment)): ?>
<?php
$total      = $investment ? ROIEngine::totalInvestment($investment) : 0;
$annualRev  = $roi['revenue']  * (12 / max($roi['months'],1));
$annualExp  = $roi['expenses'] * (12 / max($roi['months'],1));
$annualNet  = $annualRev - $annualExp - ($investment ? $investment['monthly_loan']*12 : 0);
$roiPct     = $total > 0 ? ($annualNet / $total * 100) : 0;
$rateLabel  = ROIEngine::rate($roiPct);
$payback    = ($total > 0 && $annualNet > 0) ? ($total / $annualNet) : null;
?>
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Total Invested</div>
      <div class="stat-value">RM <?= number_format($total,0) ?></div>
      <div class="stat-label">capital deployed</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Annual Net Profit</div>
      <div class="stat-value <?= $annualNet>=0?'text-success':'text-danger' ?>">RM <?= number_format(abs($annualNet),0) ?></div>
      <div class="stat-label"><?= $annualNet>=0?'profit':'loss' ?> / year</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Annual ROI</div>
      <div class="stat-value <?= $roiPct>=10?'text-success':($roiPct>=6?'text-warning':'text-danger') ?>"><?= number_format($roiPct,1) ?>%</div>
      <div class="stat-label"><?= $rateLabel ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Payback Period</div>
      <?php if ($payback): ?>
      <div class="stat-value"><?= number_format($payback,1) ?> yrs</div>
      <div class="stat-label">to recoup investment</div>
      <?php else: ?>
      <div class="stat-value text-muted">—</div>
      <div class="stat-label">insufficient data</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card-box mb-4">
  <h6 class="fw-semibold mb-3">12-Month Performance (<?= $roi['months'] ?> months data)</h6>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Gross Revenue</span>
        <span class="fw-semibold text-success">RM <?= number_format($roi['revenue'],0) ?></span>
      </div>
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Total Expenses</span>
        <span class="fw-semibold text-danger">RM <?= number_format($roi['expenses'],0) ?></span>
      </div>
      <?php if ($investment && $investment['monthly_loan']): ?>
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Loan Payments</span>
        <span class="fw-semibold text-danger">RM <?= number_format($investment['monthly_loan'] * $roi['months'],0) ?></span>
      </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between py-2">
        <span class="fw-semibold" style="font-size:.85rem;">Net Profit</span>
        <span class="fw-bold <?= $roi['net']>=0?'text-success':'text-danger' ?>">RM <?= number_format(abs($roi['net']),0) ?></span>
      </div>
    </div>
    <div class="col-md-4">
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Avg Monthly Revenue</span>
        <span class="fw-semibold">RM <?= number_format($roi['avg_monthly_revenue'],0) ?></span>
      </div>
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Avg Monthly Expense</span>
        <span class="fw-semibold">RM <?= number_format($roi['avg_monthly_expense'],0) ?></span>
      </div>
      <div class="d-flex justify-content-between py-2">
        <span class="text-muted" style="font-size:.85rem;">Avg Monthly Net</span>
        <span class="fw-semibold <?= $roi['avg_monthly_net']>=0?'text-success':'text-danger' ?>">RM <?= number_format(abs($roi['avg_monthly_net']),0) ?></span>
      </div>
    </div>
    <div class="col-md-4">
      <?php if ($investment && $investment['interest_rate']): ?>
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Loan Interest Rate</span>
        <span class="fw-semibold"><?= number_format($investment['interest_rate'],2) ?>%</span>
      </div>
      <?php endif; ?>
      <?php if ($investment && $investment['loan_tenure_months']): ?>
      <div class="d-flex justify-content-between py-2 border-bottom">
        <span class="text-muted" style="font-size:.85rem;">Loan Tenure</span>
        <span class="fw-semibold"><?= number_format($investment['loan_tenure_months']/12,0) ?> years</span>
      </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between py-2">
        <span class="text-muted" style="font-size:.85rem;">ROI Rating</span>
        <span class="fw-bold <?= $roiPct>=10?'text-success':($roiPct>=6?'text-warning':'text-danger') ?>"><?= $rateLabel ?></span>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Investment Form -->
<div class="card-box">
  <h6 class="fw-semibold mb-3 pb-2 border-bottom">
    Investment Data — <?= htmlspecialchars($property['name']) ?>
    <?php if ($investment): ?><span class="badge bg-success ms-2" style="font-size:.7rem;">Saved</span><?php endif; ?>
  </h6>
  <form method="POST" action="<?= APP_URL ?>/investment">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

    <p class="text-muted fw-semibold mb-2" style="font-size:.8rem;letter-spacing:.05em;">PURCHASE COSTS</p>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Purchase Price (RM)</label>
        <input type="number" name="purchase_price" class="form-control" value="<?= $investment['purchase_price']??'' ?>" min="0" step="1000" placeholder="e.g. 450000" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Stamp Duty (RM)</label>
        <input type="number" name="stamp_duty" class="form-control" value="<?= $investment['stamp_duty']??'' ?>" min="0" step="100" placeholder="e.g. 8500">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Legal Fees (RM)</label>
        <input type="number" name="legal_fees" class="form-control" value="<?= $investment['legal_fees']??'' ?>" min="0" step="100" placeholder="e.g. 3500">
      </div>
    </div>

    <p class="text-muted fw-semibold mb-2" style="font-size:.8rem;letter-spacing:.05em;">SETUP COSTS</p>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Renovation Cost (RM)</label>
        <input type="number" name="renovation_cost" class="form-control" value="<?= $investment['renovation_cost']??'' ?>" min="0" step="500" placeholder="e.g. 25000">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Furniture Cost (RM)</label>
        <input type="number" name="furniture_cost" class="form-control" value="<?= $investment['furniture_cost']??'' ?>" min="0" step="100" placeholder="e.g. 12000">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Appliances Cost (RM)</label>
        <input type="number" name="appliances_cost" class="form-control" value="<?= $investment['appliances_cost']??'' ?>" min="0" step="100" placeholder="e.g. 5000">
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold">Other Costs (RM)</label>
        <input type="number" name="other_costs" class="form-control" value="<?= $investment['other_costs']??'' ?>" min="0" step="100" placeholder="e.g. agency fees, misc" style="max-width:320px;">
      </div>
    </div>

    <p class="text-muted fw-semibold mb-2" style="font-size:.8rem;letter-spacing:.05em;">FINANCING</p>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Monthly Loan Payment (RM)</label>
        <input type="number" name="monthly_loan" class="form-control" value="<?= $investment['monthly_loan']??'' ?>" min="0" step="50" placeholder="e.g. 1800">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Loan Tenure (months)</label>
        <input type="number" name="loan_tenure_months" class="form-control" value="<?= $investment['loan_tenure_months']??'' ?>" min="0" step="12" placeholder="e.g. 360 = 30 yrs">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Interest Rate (% p.a.)</label>
        <input type="number" name="interest_rate" class="form-control" value="<?= $investment['interest_rate']??'' ?>" min="0" step="0.05" placeholder="e.g. 3.75">
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."><?= htmlspecialchars($investment['notes']??'') ?></textarea>
      </div>
    </div>

    <div class="alert alert-light border mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Total Capital Required</span>
        <span class="fw-bold fs-5" id="totalAmt">RM 0</span>
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
      <a href="<?= APP_URL ?>/properties?view=<?= $property['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary px-4"><?= $investment ? 'Update Investment' : 'Save Investment' ?></button>
    </div>
  </form>
</div>

<?php elseif (!$propertyId): ?>
<div class="card-box text-center py-5">
  <div style="font-size:3rem;">📊</div>
  <h5 class="mt-3 mb-2">Select a Property</h5>
  <p class="text-muted">Choose a property above to view or enter investment data and see ROI calculations.</p>
  <?php if (empty($properties)): ?>
  <a href="<?= APP_URL ?>/properties?action=create" class="btn btn-primary">Add Your First Property</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-warning">Property not found or access denied.</div>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
