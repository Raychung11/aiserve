<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle  = 'Company Settings';
$success    = $error = '';
$companyId  = $activeCompanyId;
$role       = $currentUser['role'];

// Determine edit permission:
// admin always; owner/editor via user_companies; hierarchy roles with active company
$canEdit = false;
if ($role === 'admin') {
    $canEdit = true;
} elseif (in_array($role, ['principal', 'associate', 'manager', 'consultant', 'sme_owner'])) {
    $ucRow = Database::fetchOne(
        'SELECT role FROM user_companies WHERE user_id = ? AND company_id = ?',
        [$currentUser['id'], $companyId]
    );
    $canEdit = $ucRow && in_array($ucRow['role'], ['owner', 'editor']);
}

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please refresh and try again.';
    } elseif (!$canEdit) {
        $error = 'You do not have permission to edit this company.';
    } else {
        $section = $_POST['section'] ?? '';

        if ($section === 'basic') {
            $name = trim($_POST['name'] ?? '');
            if (strlen($name) < 2) {
                $error = 'Company name must be at least 2 characters.';
            } else {
                Company::update($companyId, [
                    'name'            => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    'registration_no' => trim($_POST['registration_no'] ?? '') ?: null,
                    'industry'        => $_POST['industry'] ?? $activeCompany['industry'],
                    'employee_count'  => max(1, (int)($_POST['employee_count'] ?? 1)),
                    'revenue_tier'    => $_POST['revenue_tier'] ?? $activeCompany['revenue_tier'],
                    'is_pre_ipo'      => (int)(!empty($_POST['is_pre_ipo'])),
                ]);
                $success = 'Company information updated.';
            }
        } elseif ($section === 'bursa') {
            $validSectors = require __DIR__ . '/../config/bursa_sectors.php';
            Company::update($companyId, [
                'bursa_sector'    => in_array($_POST['bursa_sector'] ?? '', $validSectors) ? $_POST['bursa_sector'] : null,
                'reporting_scope' => in_array($_POST['reporting_scope'] ?? '', ['hq','factory','group']) ? $_POST['reporting_scope'] : 'hq',
                'report_level'    => trim($_POST['report_level'] ?? '') ?: null,
            ]);
            $success = 'Bursa & reporting settings updated.';
        } elseif ($section === 'framework') {
            $allFw = array_keys(Company::getAllFrameworks());
            $newFw = $_POST['framework'] ?? '';
            if (!in_array($newFw, $allFw)) {
                $error = 'Invalid framework selected.';
            } else {
                $newYear = (int)($_POST['reporting_year'] ?? date('Y'));
                if ($newYear < 2018 || $newYear > (int)date('Y') + 1) {
                    $error = 'Invalid reporting year.';
                } else {
                    $oldFw = $activeCompany['framework'];
                    Company::update($companyId, [
                        'framework'      => $newFw,
                        'reporting_year' => $newYear,
                    ]);
                    Database::insert('activity_log', [
                        'user_id'     => $currentUser['id'],
                        'company_id'  => $companyId,
                        'action'      => 'FRAMEWORK_CHANGED',
                        'description' => "Framework changed from {$oldFw} to {$newFw} (year: {$newYear})",
                        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ]);
                    $success = 'ESG framework updated. Gap analysis cache will refresh on next visit.';
                    // Bust gap analysis cache so scores recalculate for new framework
                    Database::query('DELETE FROM gap_analyses WHERE company_id = ?', [$companyId]);
                }
            }
        }

        // Reload company data after update
        if (!$error) {
            $activeCompany = Company::getById($companyId, $currentUser['id'], $role);
        }
    }
}

$bursaSectors    = require __DIR__ . '/../config/bursa_sectors.php';
$allFrameworks   = Company::getAllFrameworks();
$groupedFw       = Company::getFrameworksByCategory();

include __DIR__ . '/../includes/header.php';
?>
<style>
.settings-wrap { max-width: 820px; }
.settings-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 24px 28px;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.settings-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16px;
  margin-bottom: 20px;
  border-bottom: 1px solid #f0f2f5;
}
.settings-card-title {
  font-size: 15px;
  font-weight: 700;
  color: #1a202c;
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
}
.settings-card-title i { font-size: 18px; }
.fw-option-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 10px;
  margin-bottom: 4px;
}
.fw-opt {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  background: #fafafa;
  transition: border-color .15s, background .15s;
}
.fw-opt input[type=radio] { margin-top: 3px; flex-shrink: 0; }
.fw-opt:has(input:checked) { border-color: var(--fw-clr,#0d6efd); background: #f0f6ff; }
.fw-opt-name { font-size: 13px; font-weight: 600; color: #1a202c; }
.fw-opt-short { font-size: 11px; color: #6c757d; margin-top: 1px; }
.scope-options { display: flex; gap: 16px; flex-wrap: wrap; }
.scope-opt {
  flex: 1; min-width: 140px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 12px 14px;
  cursor: pointer;
  background: #fafafa;
  transition: border-color .15s, background .15s;
}
.scope-opt:has(input:checked) { border-color: #0d6efd; background: #eff6ff; }
.scope-opt input { display: none; }
.scope-opt-label { font-size: 13px; font-weight: 600; color: #1a202c; }
.scope-opt-desc  { font-size: 11px; color: #6c757d; margin-top: 2px; }
.danger-zone {
  border: 1px solid #fee2e2;
  border-radius: 12px;
  padding: 20px 24px;
  background: #fff5f5;
}
.readonly-badge {
  font-size: 11px;
  background: #f3f4f6;
  color: #6b7280;
  padding: 2px 8px;
  border-radius: 20px;
  font-weight: 600;
}
</style>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-gear me-2 text-secondary"></i>Company Settings</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars($activeCompany['name']) ?></span>
      </div>
      <div class="topbar-actions">
        <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>

    <div class="content-body">
      <div class="settings-wrap">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <?php if (!$canEdit): ?>
      <div class="alert alert-warning">
        <i class="bi bi-eye me-2"></i>You have <strong>view-only</strong> access to this company. Contact the owner to request edit permissions.
      </div>
      <?php endif; ?>

      <!-- ── Section 1: Basic Information ───────────────────────────── -->
      <div class="settings-card">
        <div class="settings-card-header">
          <h5 class="settings-card-title">
            <i class="bi bi-building text-primary"></i>Company Information
          </h5>
          <?php if (!$canEdit): ?><span class="readonly-badge"><i class="bi bi-eye me-1"></i>Read only</span><?php endif; ?>
        </div>
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="section" value="basic">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" required <?= !$canEdit ? 'disabled' : '' ?>
                     value="<?= htmlspecialchars($activeCompany['name']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">SSM Registration No.</label>
              <input type="text" class="form-control" name="registration_no" <?= !$canEdit ? 'disabled' : '' ?>
                     placeholder="e.g. 1234567-X"
                     value="<?= htmlspecialchars($activeCompany['registration_no'] ?? '') ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Industry <span class="text-danger">*</span></label>
              <select class="form-select" name="industry" <?= !$canEdit ? 'disabled' : '' ?>>
                <?php foreach (['Manufacturing','Services','Trading','Construction','Other'] as $ind): ?>
                <option value="<?= $ind ?>" <?= $activeCompany['industry'] === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Number of Employees</label>
              <input type="number" class="form-control" name="employee_count" min="1" <?= !$canEdit ? 'disabled' : '' ?>
                     value="<?= (int)$activeCompany['employee_count'] ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Annual Revenue Tier</label>
              <select class="form-select" name="revenue_tier" <?= !$canEdit ? 'disabled' : '' ?>>
                <option value="below_10M"  <?= $activeCompany['revenue_tier'] === 'below_10M'  ? 'selected' : '' ?>>Below RM10 Million</option>
                <option value="10M_to_50M" <?= $activeCompany['revenue_tier'] === '10M_to_50M' ? 'selected' : '' ?>>RM10M – RM50M</option>
                <option value="above_50M"  <?= $activeCompany['revenue_tier'] === 'above_50M'  ? 'selected' : '' ?>>Above RM50 Million</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_pre_ipo" value="1" id="preIpo"
                       <?= !empty($activeCompany['is_pre_ipo']) ? 'checked' : '' ?>
                       <?= !$canEdit ? 'disabled' : '' ?>>
                <label class="form-check-label" for="preIpo">
                  <strong>Pre-IPO / Bursa Listing Target</strong>
                  <div class="text-muted small">Activates enhanced SEDG disclosure requirements</div>
                </label>
              </div>
            </div>
          </div>
          <?php if ($canEdit): ?>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-floppy me-1"></i>Save Changes
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ── Section 2: Bursa & Reporting ───────────────────────────── -->
      <div class="settings-card">
        <div class="settings-card-header">
          <h5 class="settings-card-title">
            <i class="bi bi-buildings text-success"></i>Bursa Malaysia & Reporting Scope
          </h5>
          <?php if (!$canEdit): ?><span class="readonly-badge"><i class="bi bi-eye me-1"></i>Read only</span><?php endif; ?>
        </div>
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="section" value="bursa">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Bursa Malaysia Sector</label>
              <select class="form-select" name="bursa_sector" <?= !$canEdit ? 'disabled' : '' ?>>
                <option value="">Not listed / N/A</option>
                <?php foreach ($bursaSectors as $sector): ?>
                <option value="<?= htmlspecialchars($sector) ?>"
                  <?= ($activeCompany['bursa_sector'] ?? '') === $sector ? 'selected' : '' ?>>
                  <?= htmlspecialchars($sector) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Used for peer benchmarking against Bursa-listed companies in the same sector.</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Report Level</label>
              <input type="text" class="form-control" name="report_level"
                     placeholder="e.g. Standalone, Consolidated, Entity-level"
                     <?= !$canEdit ? 'disabled' : '' ?>
                     value="<?= htmlspecialchars($activeCompany['report_level'] ?? '') ?>">
              <div class="form-text">Optional free-text description for report headers.</div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Reporting Scope</label>
            <div class="scope-options">
              <?php foreach ([
                  'hq'      => ['HQ Only',      'Headquarters operations only'],
                  'factory' => ['Factory / Site','Single facility reporting'],
                  'group'   => ['Group-wide',    'Consolidated group reporting'],
              ] as $val => [$lbl, $desc]): ?>
              <label class="scope-opt <?= !$canEdit ? 'opacity-75' : '' ?>">
                <input type="radio" name="reporting_scope" value="<?= $val ?>"
                       <?= (($activeCompany['reporting_scope'] ?? 'hq') === $val) ? 'checked' : '' ?>
                       <?= !$canEdit ? 'disabled' : '' ?>>
                <div class="scope-opt-label"><?= $lbl ?></div>
                <div class="scope-opt-desc"><?= $desc ?></div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if ($canEdit): ?>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-floppy me-1"></i>Save Changes
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ── Section 3: ESG Framework ───────────────────────────────── -->
      <div class="settings-card">
        <div class="settings-card-header">
          <h5 class="settings-card-title">
            <i class="bi bi-diagram-3 text-warning"></i>ESG Reporting Framework
          </h5>
          <?php if (!$canEdit): ?><span class="readonly-badge"><i class="bi bi-eye me-1"></i>Read only</span><?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
        <div class="alert alert-warning alert-sm py-2 mb-3" role="alert">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong>Changing the framework</strong> will clear the gap analysis cache and re-score all indicators.
          Existing ESG data is preserved but some indicators may no longer apply.
        </div>
        <?php endif; ?>
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="section" value="framework">
          <div class="mb-4">
            <?php foreach ($groupedFw as $groupLabel => $fwList): ?>
            <div class="small text-muted fw-semibold text-uppercase mb-2 mt-3" style="letter-spacing:.07em"><?= htmlspecialchars($groupLabel) ?></div>
            <div class="fw-option-row">
              <?php foreach ($fwList as $fw): ?>
              <label class="fw-opt" style="--fw-clr:<?= $fw['color'] ?>">
                <input type="radio" name="framework" value="<?= $fw['id'] ?>"
                       <?= $activeCompany['framework'] === $fw['id'] ? 'checked' : '' ?>
                       <?= !$canEdit ? 'disabled' : '' ?>>
                <div>
                  <div class="fw-opt-name" style="color:<?= $fw['color'] ?>"><?= htmlspecialchars($fw['badge']) ?></div>
                  <div class="fw-opt-short"><?= htmlspecialchars($fw['short'] ?? $fw['name']) ?></div>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label fw-semibold">Reporting Year</label>
              <select class="form-select" name="reporting_year" <?= !$canEdit ? 'disabled' : '' ?>>
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= (int)$activeCompany['reporting_year'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <?php if ($canEdit): ?>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-warning text-dark"
                    onclick="return confirm('Change the ESG framework? Gap analysis cache will be cleared.')">
              <i class="bi bi-arrow-repeat me-1"></i>Update Framework
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ── Section 4: Read-only audit info ────────────────────────── -->
      <div class="settings-card">
        <div class="settings-card-header">
          <h5 class="settings-card-title">
            <i class="bi bi-info-circle text-secondary"></i>Audit Information
          </h5>
          <span class="readonly-badge"><i class="bi bi-lock me-1"></i>Read only</span>
        </div>
        <div class="row text-sm">
          <div class="col-md-4 mb-3">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Company ID</div>
            <div class="fw-semibold">#<?= $companyId ?></div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Created</div>
            <div class="fw-semibold"><?= date('d M Y', strtotime($activeCompany['created_at'] ?? 'now')) ?></div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Active Plan</div>
            <div class="fw-semibold"><?= ucfirst($activePlan['code'] ?? 'starter') ?></div>
          </div>
        </div>
      </div>

      <?php if ($canEdit && $role !== 'admin'): ?>
      <!-- ── Danger zone ─────────────────────────────────────────────── -->
      <div class="danger-zone">
        <h6 class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-octagon me-2"></i>Danger Zone</h6>
        <p class="text-muted small mb-3">These actions are permanent and cannot be undone.</p>
        <a href="<?= url('companies') ?>?delete=<?= $companyId ?>&token=<?= Auth::csrfToken() ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Permanently delete this company and all its ESG data? This cannot be undone.')">
          <i class="bi bi-trash me-1"></i>Delete This Company
        </a>
      </div>
      <?php endif; ?>

      </div><!-- /.settings-wrap -->
    </div><!-- /.content-body -->
  </div><!-- /.main-content -->
</div><!-- /.app-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
