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
<style>
/* ── Onboarding layout ── */
.onboarding-wrap {
  max-width: 860px;
  margin: 0 auto;
}

/* ── Section cards ── */
.onboarding-section {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 24px 28px;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
}

.section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f0f2f5;
}

.step-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #0d6efd;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

/* ── Revenue selector ── */
.revenue-selector {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.revenue-option {
  flex: 1;
  min-width: 180px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  cursor: pointer;
  background: #fafafa;
  transition: border-color .15s, background .15s, box-shadow .15s;
}

.revenue-option input[type="radio"] { display: none; }

.revenue-option i {
  font-size: 28px;
  color: #6c757d;
  flex-shrink: 0;
  transition: color .15s;
}

.revenue-option div strong {
  display: block;
  font-size: 14px;
  color: #212529;
  line-height: 1.3;
}

.revenue-option div small {
  display: block;
  font-size: 12px;
  color: #6c757d;
  margin-top: 2px;
}

.revenue-option:hover {
  border-color: #0d6efd;
  background: #f0f6ff;
}

.revenue-option.selected {
  border-color: #0d6efd;
  background: #e8f0fe;
  box-shadow: 0 0 0 3px rgba(13,110,253,.12);
}

.revenue-option.selected i { color: #0d6efd; }

/* ── Framework selector ── */
.fw-group-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #6c757d;
  margin: 16px 0 10px;
}

.framework-selector-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.framework-option-card {
  display: flex;
  flex-direction: column;
  padding: 16px;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  cursor: pointer;
  background: #fafafa;
  transition: border-color .15s, background .15s, box-shadow .15s;
  position: relative;
}

.framework-option-card input[type="radio"] { display: none; }

.framework-option-card:hover {
  border-color: var(--fw-color, #0d6efd);
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.framework-option-card.selected {
  border-color: var(--fw-color, #0d6efd);
  background: #fff;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--fw-color, #0d6efd) 15%, transparent);
}

.fwc-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.fwc-top i { font-size: 22px; }

.fwc-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  letter-spacing: .04em;
}

.fwc-name {
  font-size: 13px;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 4px;
}

.fwc-desc {
  font-size: 11px;
  color: #6c757d;
  line-height: 1.5;
  flex: 1;
  margin-bottom: 12px;
}

.fwc-footer {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #6c757d;
  border-top: 1px solid #f0f2f5;
  padding-top: 8px;
  margin-top: auto;
}

.fwc-indicators, .fwc-region {
  display: flex;
  align-items: center;
}

.fwc-mandatory {
  font-size: 10px;
  color: #dc3545;
  background: #fff3f3;
  border-radius: 4px;
  padding: 4px 8px;
  margin-top: 8px;
}

/* ── Submit button area ── */
.ob-submit {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 24px 28px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
</style>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-building-add me-2 text-primary"></i>Add New Company</h1>
        <span class="topbar-subtitle">Complete the form to create a new ESG profile</span>
      </div>
      <div class="topbar-actions">
        <a href="<?= url('companies') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Companies
        </a>
      </div>
    </div>

    <div class="content-body">
      <div class="onboarding-wrap">

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
        <p class="text-muted small mb-3">
          Select the standard your company needs to report against. Consultants can assign a different framework per client.
          <strong>Not sure?</strong> Start with Bursa SEDG — you can add more later.
        </p>

        <?php
        $allFrameworks    = Company::getAllFrameworks();
        $groupedFrameworks= Company::getFrameworksByCategory();
        $selectedFw       = $_POST['framework'] ?? 'BURSA_SEDG';
        ?>

        <?php foreach ($groupedFrameworks as $groupLabel => $fwList): ?>
        <div class="fw-group-label"><?= htmlspecialchars($groupLabel) ?></div>
        <div class="framework-selector-grid">
          <?php foreach ($fwList as $fw): ?>
          <label class="framework-option-card <?= $selectedFw === $fw['id'] ? 'selected' : '' ?>"
                 style="--fw-color: <?= $fw['color'] ?>">
            <input type="radio" name="framework" value="<?= $fw['id'] ?>"
                   <?= $selectedFw === $fw['id'] ? 'checked' : '' ?> required>
            <div class="fwc-top">
              <i class="bi <?= $fw['icon'] ?>" style="color:<?= $fw['color'] ?>"></i>
              <span class="fwc-badge" style="background:<?= $fw['color'] ?>20;color:<?= $fw['color'] ?>"><?= htmlspecialchars($fw['badge']) ?></span>
            </div>
            <div class="fwc-name"><?= htmlspecialchars($fw['name']) ?></div>
            <div class="fwc-desc"><?= htmlspecialchars($fw['description']) ?></div>
            <div class="fwc-footer">
              <span class="fwc-indicators"><i class="bi bi-list-check me-1"></i><?= $fw['indicators'] ?> indicators</span>
              <span class="fwc-region"><i class="bi bi-geo-alt me-1"></i><?= $fw['region'] ?></span>
            </div>
            <?php if (!empty($fw['mandatory_for'])): ?>
            <div class="fwc-mandatory"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($fw['mandatory_for']) ?></div>
            <?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="ob-submit">
        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-rocket-takeoff me-2"></i>Launch My ESG Dashboard
          </button>
        </div>
        <p class="text-center text-muted small mt-3 mb-0">You can add more companies or change the framework later.</p>
      </div>
    </form>

      </div><!-- /.onboarding-wrap -->
    </div><!-- /.content-body -->
  </div><!-- /.main-content -->
</div><!-- /.app-layout -->

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
