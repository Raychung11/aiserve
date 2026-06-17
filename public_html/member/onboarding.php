<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

$profile_stmt = $pdo->prepare('SELECT * FROM member_profiles WHERE user_id = ?');
$profile_stmt->execute([$uid]);
$p = $profile_stmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $step = (int)($_POST['step'] ?? 1);

    $fields = [
        'nationality', 'date_of_birth', 'gender', 'passport_number',
        'passport_expiry', 'country_of_origin', 'current_residence',
        'purpose', 'estimated_investment', 'preferred_location',
        'monthly_income', 'liquid_assets', 'family_members', 'notes',
    ];
    $checkboxes = ['property_interest', 'banking_support', 'business_networking', 'fd_readiness'];

    $sets = [];
    $vals = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $sets[] = "$f = ?";
            $vals[] = trim($_POST[$f]);
        }
    }
    foreach ($checkboxes as $f) {
        $sets[] = "$f = ?";
        $vals[] = isset($_POST[$f]) ? 1 : 0;
    }

    if ($step === 5) {
        $sets[] = 'onboarding_completed = 1';
    }

    if ($sets) {
        $vals[] = $uid;
        $pdo->prepare('UPDATE member_profiles SET ' . implode(', ', $sets) . ' WHERE user_id = ?')->execute($vals);
    }

    if ($step === 5) {
        // Create case if none exists
        $exists = $pdo->prepare('SELECT id FROM mm2h_cases WHERE applicant_id = ?');
        $exists->execute([$uid]);
        if (!$exists->fetch()) {
            $cn = generate_case_number();
            $pdo->prepare('INSERT INTO mm2h_cases (case_number, applicant_id, current_status) VALUES (?,?,?)')
                ->execute([$cn, $uid, 'new_lead']);
        }
        log_activity('onboarding_complete');
        flash('success', 'Profile complete! Your case has been created. Our team will be in touch shortly.');
        redirect('member/dashboard');
    }

    flash('success', 'Step ' . $step . ' saved.');
    redirect('member/onboarding?step=' . ($step + 1));
}

$current_step = max(1, min(5, (int)($_GET['step'] ?? 1)));
$step_labels  = [
    1 => t('onboarding_step1'),
    2 => t('onboarding_step2'),
    3 => t('onboarding_step3'),
    4 => t('onboarding_step4'),
    5 => t('onboarding_step5'),
];

$page_title = t('onboarding_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-person-lines-fill me-2 text-gold"></i><?= t('onboarding_title') ?></h1>
    </div>

    <!-- Step indicator -->
    <div class="onboarding-steps mb-4">
      <?php foreach ($step_labels as $n => $label): ?>
      <div class="onboarding-step <?= $n < $current_step ? 'done' : ($n === $current_step ? 'active' : '') ?>">
        <div class="step-circle"><?= $n < $current_step ? '<i class="bi bi-check-lg"></i>' : $n ?></div>
        <span class="step-label d-none d-md-inline"><?= h($label) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php render_flash(); ?>

    <div class="mm2h-form-card">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="<?= $current_step ?>">

        <?php if ($current_step === 1): ?>
        <h5 class="mb-4"><?= t('onboarding_step1') ?></h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" value="<?= h($_SESSION['user_name'] ?? '') ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="<?= h($p['date_of_birth'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">Select…</option>
              <?php foreach (['male' => 'Male', 'female' => 'Female', 'prefer_not' => 'Prefer not to say'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= ($p['gender'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" class="form-control" value="<?= h($p['nationality'] ?? '') ?>" placeholder="e.g. Chinese, Taiwanese…">
          </div>
          <div class="col-md-6">
            <label class="form-label">Passport Number</label>
            <input type="text" name="passport_number" class="form-control" value="<?= h($p['passport_number'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Passport Expiry</label>
            <input type="date" name="passport_expiry" class="form-control" value="<?= h($p['passport_expiry'] ?? '') ?>">
          </div>
        </div>

        <?php elseif ($current_step === 2): ?>
        <h5 class="mb-4"><?= t('onboarding_step2') ?></h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Country of Origin</label>
            <input type="text" name="country_of_origin" class="form-control" value="<?= h($p['country_of_origin'] ?? '') ?>" placeholder="e.g. Taiwan, China, Hong Kong">
          </div>
          <div class="col-md-6">
            <label class="form-label">Current Country of Residence</label>
            <input type="text" name="current_residence" class="form-control" value="<?= h($p['current_residence'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Primary Purpose of MM2H</label>
            <select name="purpose" class="form-select">
              <option value="">Select…</option>
              <?php foreach (['retirement' => 'Retirement', 'business' => 'Business Relocation', 'family' => 'Family Relocation', 'investment' => 'Investment', 'property' => 'Property Purchase', 'education' => 'Education Planning'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= ($p['purpose'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Number of Family Members Applying</label>
            <input type="number" name="family_members" class="form-control" min="0" max="20" value="<?= h($p['family_members'] ?? '0') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Additional Notes / Special Requirements</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any specific needs or context..."><?= h($p['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <?php elseif ($current_step === 3): ?>
        <h5 class="mb-4"><?= t('onboarding_step3') ?></h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Monthly Income (USD equivalent)</label>
            <select name="monthly_income" class="form-select">
              <option value="">Select range…</option>
              <?php foreach (['under_2000' => 'Under USD 2,000', '2000_5000' => 'USD 2,000 – 5,000', '5000_10000' => 'USD 5,000 – 10,000', 'over_10000' => 'Over USD 10,000'] as $v => $l): ?>
              <option value="<?=$v?>" <?=($p['monthly_income']??'')===$v?'selected':''?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Liquid Assets (USD equivalent)</label>
            <select name="liquid_assets" class="form-select">
              <option value="">Select range…</option>
              <?php foreach (['under_100k' => 'Under USD 100,000', '100k_300k' => 'USD 100,000 – 300,000', '300k_500k' => 'USD 300,000 – 500,000', 'over_500k' => 'Over USD 500,000'] as $v => $l): ?>
              <option value="<?=$v?>" <?=($p['liquid_assets']??'')===$v?'selected':''?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Estimated Investment Amount (MYR)</label>
            <select name="estimated_investment" class="form-select">
              <option value="">Select range…</option>
              <?php foreach (['500k_1m' => 'MYR 500k – 1M', '1m_2m' => 'MYR 1M – 2M', '2m_5m' => 'MYR 2M – 5M', 'over_5m' => 'Over MYR 5M'] as $v => $l): ?>
              <option value="<?=$v?>" <?=($p['estimated_investment']??'')===$v?'selected':''?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="fd_readiness" id="fd_ready"
                     <?= !empty($p['fd_readiness']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="fd_ready">
                I am ready to place the MYR 500,000 Fixed Deposit as required by MM2H
              </label>
            </div>
          </div>
        </div>

        <?php elseif ($current_step === 4): ?>
        <h5 class="mb-4"><?= t('onboarding_step4') ?></h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Preferred Location in Malaysia</label>
            <select name="preferred_location" class="form-select">
              <option value="">Select…</option>
              <?php foreach (['Kuala Lumpur', 'Penang', 'Johor Bahru', 'Kota Kinabalu (Sabah)', 'Kuching (Sarawak)', 'Pahang (Highlands)', 'Other'] as $loc): ?>
              <option value="<?=$loc?>" <?=($p['preferred_location']??'')===$loc?'selected':''?>><?=$loc?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 d-flex flex-column justify-content-center">
            <label class="form-label">Services Required</label>
            <div class="d-flex flex-column gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="property_interest" id="prop_int"
                       <?= !empty($p['property_interest']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="prop_int"><i class="bi bi-buildings me-1 text-gold"></i>Property Matching</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="banking_support" id="bank_sup"
                       <?= !empty($p['banking_support']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="bank_sup"><i class="bi bi-bank2 me-1 text-gold"></i>Banking Support</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="business_networking" id="biz_net"
                       <?= !empty($p['business_networking']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="biz_net"><i class="bi bi-people me-1 text-gold"></i>Business Networking</label>
              </div>
            </div>
          </div>
        </div>

        <?php elseif ($current_step === 5): ?>
        <h5 class="mb-4"><?= t('onboarding_step5') ?></h5>
        <div class="alert alert-info mb-4">
          <i class="bi bi-info-circle me-2"></i>
          Document uploads are managed in the <a href="<?= APP_URL ?>/member/documents" class="alert-link">Documents section</a>.
          Click <strong>Submit Profile</strong> below to finalise your onboarding — you can upload documents anytime.
        </div>
        <div class="mm2h-form-card" style="background:var(--light);">
          <h6 class="mb-3">Profile Summary</h6>
          <div class="row g-2">
            <?php
            $summary_fields = [
              'Nationality'   => $p['nationality'] ?? '—',
              'Purpose'       => ucfirst($p['purpose'] ?? '—'),
              'Country'       => $p['country_of_origin'] ?? '—',
              'Investment'    => $p['estimated_investment'] ?? '—',
              'FD Ready'      => !empty($p['fd_readiness']) ? 'Yes' : 'No',
              'Location'      => $p['preferred_location'] ?? '—',
            ];
            foreach ($summary_fields as $label => $val):
            ?>
            <div class="col-6 col-md-4">
              <div class="small text-muted"><?= $label ?></div>
              <div class="fw-semibold small"><?= h($val) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mt-4">
          <?php if ($current_step > 1): ?>
          <a href="?step=<?= $current_step - 1 ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?= t('back') ?>
          </a>
          <?php else: ?>
          <div></div>
          <?php endif; ?>
          <button type="submit" class="btn btn-gold px-4">
            <?= $current_step === 5 ? '<i class="bi bi-check-lg me-1"></i>Submit Profile' : t('next') . ' <i class="bi bi-arrow-right ms-1"></i>' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
