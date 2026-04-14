<?php
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Company Setup';
$error = $success = '';
$step  = (int)($_GET['step'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session. Please refresh and try again.';
    } else {
        $result = Company::create($_POST, $currentUser['id']);
        if ($result['success']) {
            Auth::setActiveCompany($result['company_id']);
            header('Location: ' . APP_URL . '/dashboard?welcome=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="onboarding-container">
  <div class="onboarding-box">

    <div class="onboarding-header">
      <div class="onboarding-logo"><i class="bi bi-leaf-fill"></i></div>
      <h2>Set Up Your Company</h2>
      <p class="text-muted">Takes about 3 minutes. You can edit everything later.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="onboardingForm">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

      <!-- Step 1: Company basics -->
      <div class="onboarding-section">
        <div class="section-label"><span class="step-badge">1</span> Company Information</div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Company Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="e.g. Maju Jaya Sdn Bhd"
                   required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">SSM Registration No.</label>
            <input type="text" class="form-control" name="registration_no" placeholder="e.g. 1234567-X"
                   value="<?= htmlspecialchars($_POST['registration_no'] ?? '') ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Industry <span class="text-danger">*</span></label>
            <select class="form-select" name="industry" required>
              <option value="">Select industry...</option>
              <?php foreach (['Manufacturing','Services','Trading','Construction','Other'] as $ind): ?>
              <option value="<?= $ind ?>" <?= ($_POST['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Number of Employees <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="employee_count" min="1"
                   placeholder="e.g. 85" required value="<?= htmlspecialchars($_POST['employee_count'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Step 2: Business size -->
      <div class="onboarding-section">
        <div class="section-label"><span class="step-badge">2</span> Business Size</div>
        <label class="form-label">Annual Revenue <span class="text-danger">*</span></label>
        <div class="revenue-selector">
          <?php
          $revenueOptions = [
              'below_10M'   => ['label' => '< RM10 Million', 'desc' => 'Micro / Small Enterprise', 'icon' => 'bi-shop'],
              '10M_to_50M'  => ['label' => 'RM10M – RM50M',  'desc' => 'Small / Medium Enterprise', 'icon' => 'bi-building'],
              'above_50M'   => ['label' => '> RM50 Million',  'desc' => 'Large Enterprise / Pre-IPO', 'icon' => 'bi-buildings'],
          ];
          foreach ($revenueOptions as $val => $opt):
          ?>
          <label class="revenue-option <?= ($_POST['revenue_tier'] ?? '') === $val ? 'selected' : '' ?>">
            <input type="radio" name="revenue_tier" value="<?= $val ?>"
                   <?= ($_POST['revenue_tier'] ?? '') === $val ? 'checked' : '' ?> required>
            <i class="bi <?= $opt['icon'] ?>"></i>
            <div>
              <strong><?= $opt['label'] ?></strong>
              <small><?= $opt['desc'] ?></small>
            </div>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="row mt-3">
          <div class="col-md-6 mb-3">
            <label class="form-label">Reporting Year <span class="text-danger">*</span></label>
            <select class="form-select" name="reporting_year">
              <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
              <option value="<?= $y ?>" <?= ($_POST['reporting_year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_pre_ipo" value="1" id="preIpo"
                     <?= !empty($_POST['is_pre_ipo']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="preIpo">
                <strong>Pre-IPO / Bursa Listing Target</strong>
                <div class="text-muted small">Activates enhanced SEDG requirements</div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Framework selection -->
      <div class="onboarding-section">
        <div class="section-label"><span class="step-badge">3</span> ESG Reporting Framework</div>
        <p class="text-muted small mb-3">Your consultant can change this anytime. We recommend starting with Bursa SEDG for Malaysian companies.</p>
        <div class="framework-selector">
          <?php
          $frameworks = [
              'BURSA_SEDG' => [
                  'label' => 'Bursa Malaysia SEDG',
                  'desc'  => 'Mandatory for Bursa-listed companies. Best for Malaysian market.',
                  'icon'  => 'bi-graph-up',
                  'badge' => 'Recommended for Malaysia',
                  'indicators' => '41 indicators',
              ],
              'GRI' => [
                  'label' => 'GRI Standards',
                  'desc'  => 'Global standard. Best for international supply chains and investors.',
                  'icon'  => 'bi-globe',
                  'badge' => 'International Standard',
                  'indicators' => '33 indicators',
              ],
              'BOTH' => [
                  'label' => 'Bursa SEDG + GRI',
                  'desc'  => 'Comprehensive coverage. Best for pre-IPO and export-oriented companies.',
                  'icon'  => 'bi-layers',
                  'badge' => 'Most Comprehensive',
                  'indicators' => '60+ indicators',
              ],
          ];
          foreach ($frameworks as $val => $fw):
          ?>
          <label class="framework-option <?= ($_POST['framework'] ?? 'BURSA_SEDG') === $val ? 'selected' : '' ?>">
            <input type="radio" name="framework" value="<?= $val ?>"
                   <?= ($_POST['framework'] ?? 'BURSA_SEDG') === $val ? 'checked' : '' ?> required>
            <div class="fw-header">
              <i class="bi <?= $fw['icon'] ?>"></i>
              <div>
                <strong><?= $fw['label'] ?></strong>
                <span class="fw-badge"><?= $fw['badge'] ?></span>
              </div>
              <span class="fw-count"><?= $fw['indicators'] ?></span>
            </div>
            <p class="fw-desc"><?= $fw['desc'] ?></p>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="bi bi-rocket-takeoff me-2"></i>Launch My ESG Dashboard
        </button>
      </div>
      <p class="text-center text-muted small mt-2">You can add more companies or change the framework later.</p>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Selector highlights
['revenue_tier', 'framework', 'role'].forEach(function(name) {
  document.querySelectorAll('[name="' + name + '"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      const group = document.querySelectorAll('[name="' + name + '"]');
      group.forEach(r => r.closest('label')?.classList.remove('selected'));
      this.closest('label')?.classList.add('selected');
    });
  });
});
</script>
