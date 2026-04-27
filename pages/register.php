<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ActivityLog.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::start();
if (Auth::check()) { header('Location: ' . APP_URL . '/dashboard'); exit; }

$errors = [];
$old    = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $old = $_POST;
    if (empty($_POST['company_name'])) $errors[] = 'Company name is required.';
    if (empty($_POST['name']))         $errors[] = 'Your name is required.';
    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($_POST['password'] ?? '') < 8) $errors[] = 'Password must be at least 8 characters.';
    if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if (empty($_POST['phone'])) $errors[] = 'Phone number is required.';

    if (!$errors) {
        $result = Auth::register($_POST);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        $errors[] = $result['error'];
    }
}

$planFeatures = [
    'starter'    => ['Up to 5 properties', '2 agents', 'Rent payment tracking', 'Owner portal', 'Document uploads'],
    'growth'     => ['Up to 20 properties', '10 agents', 'Everything in Starter', 'ROI calculator', 'Annual reports'],
    'enterprise' => ['Unlimited properties', 'Unlimited agents', 'Everything in Growth', 'Priority support', 'Custom branding'],
];
$selectedPlan = $old['plan'] ?? 'growth';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Roomee</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --accent:#6366f1; --dark:#0f172a; }
body { background:#f1f5f9; font-family:'Segoe UI',sans-serif; min-height:100vh; }
.panel-left {
  background:var(--dark);
  min-height:100vh;
  padding:3rem 2.5rem;
  display:flex; flex-direction:column; justify-content:center;
}
.panel-right { padding:2.5rem; overflow-y:auto; }
.brand-icon { width:48px;height:48px;background:rgba(99,102,241,.2);border-radius:14px;
              display:flex;align-items:center;justify-content:center;font-size:1.4rem; }
.feature-item { display:flex;align-items:flex-start;gap:.65rem;margin-bottom:.75rem; }
.feature-dot  { width:20px;height:20px;background:rgba(99,102,241,.25);border-radius:50%;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px; }
.form-card { background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:2rem; }
.plan-card { border:2px solid #e2e8f0;border-radius:12px;padding:1rem 1.1rem;
             cursor:pointer;transition:border-color .15s,background .15s; }
.plan-card:has(input:checked),
.plan-card.selected { border-color:var(--accent);background:#f5f3ff; }
.plan-card input[type=radio] { accent-color:var(--accent); }
.plan-badge { font-size:.65rem;font-weight:700;padding:.15rem .5rem;border-radius:20px;
              text-transform:uppercase;letter-spacing:.05em; }
.badge-popular { background:#dbeafe;color:#1e40af; }
.badge-best    { background:#dcfce7;color:#15803d; }
.strength-bar { height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden;transition:all .2s; }
.strength-fill { height:100%;border-radius:2px;width:0;transition:width .3s,background .3s; }
.btn-primary { background:var(--accent);border-color:var(--accent);font-weight:600; }
.btn-primary:hover { background:#4f46e5;border-color:#4f46e5; }
.pass-toggle { cursor:pointer;border-left:none; }
@media(max-width:991px){
  .panel-left { min-height:auto;padding:2rem 1.5rem; }
  .panel-right { padding:1.5rem; }
}
</style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="row g-0 min-vh-100">

    <!-- Left panel -->
    <div class="col-lg-5 panel-left">
      <div>
        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="brand-icon"><i class="bi bi-house-heart-fill" style="color:#a5b4fc;"></i></div>
          <div>
            <div class="text-white fw-bold" style="font-size:1.1rem;">Roomee</div>
            <div style="color:#64748b;font-size:.75rem;">Property Management Platform</div>
          </div>
        </div>
        <h2 class="text-white fw-bold mb-2" style="line-height:1.3;">
          Manage your properties<br>smarter &amp; faster
        </h2>
        <p style="color:#94a3b8;font-size:.9rem;margin-bottom:2rem;">
          Start your free 14-day trial. No credit card required.
        </p>
        <div>
          <?php
          $features = [
            ['icon'=>'buildings','text'=>'Track properties, owners &amp; tenancies in one place'],
            ['icon'=>'cash-coin','text'=>'Automated rent payment ledger with overdue alerts'],
            ['icon'=>'folder-fill','text'=>'Owner portal with document sharing'],
            ['icon'=>'bar-chart-fill','text'=>'Revenue reports &amp; ROI calculator'],
            ['icon'=>'people-fill','text'=>'Agent network with leaderboard'],
          ];
          foreach($features as $f): ?>
          <div class="feature-item">
            <div class="feature-dot"><i class="bi bi-<?= $f['icon'] ?>" style="color:#a5b4fc;font-size:.6rem;"></i></div>
            <div style="color:#cbd5e1;font-size:.875rem;"><?= $f['text'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.08);">
          <p style="color:#64748b;font-size:.78rem;">Already have an account?
            <a href="<?= APP_URL ?>/login" style="color:#a5b4fc;text-decoration:none;">Sign in &rarr;</a>
          </p>
        </div>
      </div>
    </div>

    <!-- Right panel -->
    <div class="col-lg-7 panel-right d-flex align-items-center justify-content-center">
      <div style="max-width:560px;width:100%;">
        <div class="mb-4">
          <h4 class="fw-bold mb-1">Create your account</h4>
          <p class="text-muted mb-0" style="font-size:.875rem;">Fill in the details below to get started</p>
        </div>

        <?php if($errors): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.875rem;">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?php if(count($errors)===1): ?>
            <?= htmlspecialchars($errors[0]) ?>
          <?php else: ?>
            <ul class="mb-0 mt-1 ps-3"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <form action="<?= APP_URL ?>/register" method="POST" class="form-card" id="regForm">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">

          <!-- Company -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.875rem;">Company / Agency Name</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-building text-muted"></i></span>
              <input type="text" name="company_name" class="form-control"
                     value="<?= htmlspecialchars($old['company_name'] ?? '') ?>"
                     placeholder="e.g. SLV Group" required>
            </div>
          </div>

          <!-- Name + Phone -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.875rem;">Your Full Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.875rem;">Phone Number</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                <input type="text" name="phone" class="form-control"
                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                       placeholder="+60 12-345 6789" required>
              </div>
            </div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.875rem;">Email Address</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </div>
          </div>

          <!-- Password row -->
          <div class="row g-3 mb-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.875rem;">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" name="password" id="pwField" class="form-control"
                       required minlength="8" oninput="updateStrength(this.value)">
                <span class="input-group-text pass-toggle bg-light" onclick="togglePass('pwField','eyeIcon1')">
                  <i class="bi bi-eye id="eyeIcon1" text-muted" style="font-size:.85rem;"></i>
                </span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size:.875rem;">Confirm Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                <input type="password" name="password_confirm" id="pwConfirm" class="form-control" required>
                <span class="input-group-text pass-toggle bg-light" onclick="togglePass('pwConfirm','eyeIcon2')">
                  <i class="bi bi-eye" id="eyeIcon2" style="font-size:.85rem;" class="text-muted"></i>
                </span>
              </div>
            </div>
          </div>
          <!-- Strength meter -->
          <div class="mb-3">
            <div class="strength-bar mt-2"><div class="strength-fill" id="strengthFill"></div></div>
            <div id="strengthLabel" class="mt-1" style="font-size:.72rem;color:#94a3b8;"></div>
          </div>

          <!-- Plan selection -->
          <div class="mb-4">
            <label class="form-label fw-semibold" style="font-size:.875rem;">Choose a Plan</label>
            <div class="row g-2">
              <?php foreach(PLAN_LIMITS as $key => $p): ?>
              <?php $isSel = $selectedPlan === $key; ?>
              <div class="col-md-4">
                <label class="plan-card d-block <?= $isSel ? 'selected' : '' ?>" id="planCard_<?= $key ?>">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <input type="radio" name="plan" value="<?= $key ?>" <?= $isSel?'checked':'' ?>
                           onchange="selectPlan('<?= $key ?>')">
                    <?php if($key==='growth'): ?>
                    <span class="plan-badge badge-popular">Popular</span>
                    <?php elseif($key==='enterprise'): ?>
                    <span class="plan-badge badge-best">Best Value</span>
                    <?php endif; ?>
                  </div>
                  <div class="fw-bold" style="font-size:.9rem;"><?= ucfirst($key) ?></div>
                  <div class="text-muted" style="font-size:.72rem;">RM <?= number_format($p['price_monthly']) ?>/mo</div>
                  <hr class="my-2" style="border-color:#f1f5f9;">
                  <?php foreach($planFeatures[$key] as $feat): ?>
                  <div style="font-size:.72rem;color:#475569;margin-bottom:.2rem;">
                    <i class="bi bi-check2 me-1" style="color:#6366f1;"></i><?= $feat ?>
                  </div>
                  <?php endforeach; ?>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">
              <i class="bi bi-info-circle me-1"></i>All plans start with a 14-day free trial. Upgrade or cancel anytime.
            </p>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.9rem;">
            <i class="bi bi-rocket-takeoff me-2"></i>Create Account &amp; Start Trial
          </button>
          <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
            By registering you agree to our Terms of Service and Privacy Policy.
          </p>
        </form>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(fieldId, iconId) {
  const f = document.getElementById(fieldId);
  const i = document.getElementById(iconId);
  if (!f || !i) return;
  if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
  else { f.type = 'password'; i.className = 'bi bi-eye'; }
}

function updateStrength(val) {
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!fill || !label) return;
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { pct: '0%',   color: '#e2e8f0', text: '' },
    { pct: '25%',  color: '#ef4444', text: 'Weak' },
    { pct: '50%',  color: '#f59e0b', text: 'Fair' },
    { pct: '75%',  color: '#3b82f6', text: 'Good' },
    { pct: '90%',  color: '#10b981', text: 'Strong' },
    { pct: '100%', color: '#059669', text: 'Very strong' },
  ];
  const lvl = levels[Math.min(score, 5)];
  fill.style.width = lvl.pct;
  fill.style.background = lvl.color;
  label.textContent = lvl.text;
  label.style.color = lvl.color;
}

function selectPlan(key) {
  document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
  const card = document.getElementById('planCard_' + key);
  if (card) card.classList.add('selected');
}
</script>
</body>
</html>
