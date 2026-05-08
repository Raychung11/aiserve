<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::startSession();

if (Auth::check()) {
    $u = Auth::user();
    header('Location: ' . Auth::postLoginUrl(['id' => $u['id'], 'role' => $u['role']]));
    exit;
}

$error  = '';
$posted = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password = $_POST['password']              ?? '';
    $confirm  = $_POST['confirm_password']      ?? '';
    $role     = $_POST['role']                  ?? 'sme_owner';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $result = Auth::register($name, $email, $password, $role);
        if ($result['success']) {
            Auth::login($email, $password);
            header('Location: ' . Auth::postRegisterUrl($role));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/landing.css" rel="stylesheet">
  <style>
    /* ── Register page ────────────────────────── */
    .reg-wrap {
      min-height: 100vh;
      display: flex;
    }

    /* Left panel */
    .reg-brand {
      width: 380px;
      flex-shrink: 0;
      background: linear-gradient(160deg, #0d2a1e 0%, #0f172a 100%);
      border-right: 1px solid #1e293b;
      display: flex;
      flex-direction: column;
      padding: 40px 40px;
    }
    .reg-brand h2   { font-size: 24px; font-weight: 800; color: #f1f5f9; margin-bottom: 8px; }
    .reg-brand .sub { font-size: 13px; color: #64748b; margin-bottom: 32px; line-height: 1.6; }

    .plan-preview { display: flex; flex-direction: column; gap: 10px; margin-bottom: 32px; }
    .plan-chip {
      border: 1px solid #1e293b;
      border-radius: 12px;
      padding: 14px 16px;
      background: rgba(255,255,255,0.03);
    }
    .plan-chip-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
    .plan-chip-name  { font-size: 13px; font-weight: 700; color: #f1f5f9; }
    .plan-chip-price { font-size: 13px; font-weight: 700; }
    .plan-chip-desc  { font-size: 11px; color: #64748b; }
    .plan-chip-trial { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; }

    .reg-features { display: flex; flex-direction: column; gap: 8px; }
    .reg-feat {
      display: flex; align-items: center; gap: 10px;
      font-size: 13px; color: #94a3b8;
    }
    .reg-feat i { color: #22c55e; font-size: 14px; flex-shrink: 0; }

    /* Right panel */
    .reg-form-panel {
      flex: 1;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      background: #0f172a;
      padding: 40px 24px;
      overflow-y: auto;
    }
    .reg-form-box {
      width: 100%;
      max-width: 520px;
      padding: 20px 0;
    }
    .reg-form-box h1  { font-size: 26px; font-weight: 800; color: #f1f5f9; margin-bottom: 6px; }
    .reg-form-box .intro { font-size: 14px; color: #64748b; margin-bottom: 28px; }

    .form-label     { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }
    .form-control {
      background: #1e293b; border: 1px solid #334155; color: #f1f5f9;
      border-radius: 10px; padding: 11px 14px; font-size: 14px;
      transition: border-color 0.15s;
    }
    .form-control:focus {
      background: #1e293b; border-color: #16a34a;
      box-shadow: 0 0 0 3px rgba(22,163,74,0.15); color: #f1f5f9;
    }
    .form-control::placeholder { color: #475569; }
    .input-group-text {
      background: #1e293b; border: 1px solid #334155; color: #64748b;
      border-radius: 10px 0 0 10px;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0; border-left: none; }
    .pw-toggle {
      background: #1e293b; border: 1px solid #334155; border-left: none;
      color: #64748b; border-radius: 0 10px 10px 0; padding: 0 14px;
    }
    .pw-toggle:hover { color: #94a3b8; }
    .form-text { font-size: 11px; color: #475569; margin-top: 4px; }

    /* Role selector grid */
    .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .role-opt {
      cursor: pointer;
      border: 1.5px solid #334155;
      border-radius: 12px;
      padding: 14px 14px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      transition: border-color 0.15s, background 0.15s;
    }
    .role-opt:hover { border-color: #475569; }
    .role-opt.active {
      border-color: #16a34a;
      background: rgba(22,163,74,0.08);
    }
    .role-opt input[type=radio] { display: none; }
    .role-opt-icon {
      width: 36px; height: 36px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 17px; flex-shrink: 0;
    }
    .role-opt-body { flex: 1; }
    .role-opt-name { font-size: 13px; font-weight: 700; color: #f1f5f9; }
    .role-opt-desc { font-size: 11px; color: #64748b; margin-top: 1px; line-height: 1.4; }
    .role-opt-dest { font-size: 10px; color: #16a34a; margin-top: 3px; font-family: monospace; }

    .btn-register {
      background: #16a34a; border: none; color: white;
      font-size: 15px; font-weight: 700; padding: 13px;
      border-radius: 10px; width: 100%;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-register:hover  { background: #15803d; transform: translateY(-1px); }
    .btn-register:active { transform: translateY(0); }

    .error-box {
      background: rgba(220,38,38,0.12);
      border: 1px solid rgba(220,38,38,0.3);
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 13px;
      color: #fca5a5;
      margin-bottom: 20px;
      display: flex; align-items: center; gap: 8px;
    }

    .section-sep {
      font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.5px; color: #475569;
      border-bottom: 1px solid #1e293b;
      padding-bottom: 8px; margin-bottom: 14px; margin-top: 20px;
    }

    @media (max-width: 768px) {
      .reg-brand { display: none; }
      .role-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="landing-body">

<div class="reg-wrap">

  <!-- ═══════════════════ LEFT PANEL ═══════════════════ -->
  <div class="reg-brand">
    <a href="<?= APP_URL ?>/" style="text-decoration:none">
      <div class="d-flex align-items-center gap-2 mb-4">
        <div style="width:40px;height:40px;border-radius:11px;background:#16a34a;display:flex;align-items:center;justify-content:center;font-size:20px;color:white">
          <i class="bi bi-leaf-fill"></i>
        </div>
        <span style="font-size:17px;font-weight:800;color:#f1f5f9">AiServe <span style="color:#16a34a">ESG OS</span></span>
      </div>
    </a>

    <h2>Start for free</h2>
    <p class="sub">Every new account gets a 14-day Professional trial — all 200+ indicators, no credit card needed.</p>

    <!-- Plan overview -->
    <div class="plan-preview">
      <div class="plan-chip" style="border-color:#16a34a22">
        <div class="plan-chip-top">
          <span class="plan-chip-name">Starter</span>
          <span class="plan-chip-price" style="color:#64748b">Free</span>
        </div>
        <div class="plan-chip-desc">5 ESG indicators forever</div>
      </div>
      <div class="plan-chip" style="border-color:#0ea5e922">
        <div class="plan-chip-top">
          <span class="plan-chip-name">Standard</span>
          <span class="plan-chip-price" style="color:#38bdf8">RM 1,500/yr</span>
        </div>
        <div class="plan-chip-desc">15 mandatory Bursa SEDG indicators</div>
      </div>
      <div class="plan-chip" style="border-color:#7c3aed22;background:rgba(124,58,237,0.05)">
        <div class="plan-chip-top">
          <span class="plan-chip-name">Professional</span>
          <span class="plan-chip-price" style="color:#a78bfa">RM 3,500/yr</span>
        </div>
        <div class="plan-chip-desc">All 200+ indicators, all 6 frameworks</div>
        <div class="mt-1">
          <span class="plan-chip-trial" style="background:rgba(167,139,250,0.15);color:#a78bfa">
            14-day free trial on signup
          </span>
        </div>
      </div>
    </div>

    <div class="reg-features">
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Bursa SEDG mandatory disclosures</div>
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Automated gap analysis</div>
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Carbon calculator (Scope 1, 2 &amp; 3)</div>
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Green financing eligibility check</div>
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Audit-ready report generation</div>
      <div class="reg-feat"><i class="bi bi-check-circle-fill"></i> Multi-role team hierarchy</div>
    </div>
  </div>

  <!-- ═══════════════════ RIGHT PANEL ═══════════════════ -->
  <div class="reg-form-panel">
    <div class="reg-form-box">

      <h1>Create your account</h1>
      <p class="intro">Choose your role — each role gets a tailored dashboard after sign-in.</p>

      <?php if ($error): ?>
      <div class="error-box">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" id="regForm">

        <!-- Role -->
        <div class="section-sep">Select your role</div>
        <div class="role-grid mb-4">
          <?php
          $roleOpts = [
            ['sme_owner', 'bi-building',        'rgba(22,163,74,0.15)',   '#22c55e', 'SME Owner',  'Track your own company ESG',         '→ Your dashboard'],
            ['principal', 'bi-diagram-3-fill',  'rgba(96,165,250,0.15)',  '#60a5fa', 'Principal',  'Firm owner, manage associates',      '→ Portfolio view'],
            ['associate', 'bi-briefcase-fill',  'rgba(56,189,248,0.15)',  '#38bdf8', 'Associate',  'Accounting / COSEC firm',            '→ Client portfolio'],
            ['manager',   'bi-person-workspace','rgba(148,163,184,0.15)', '#94a3b8', 'Manager',    'Staff analyst, assigned clients',    '→ Task view'],
          ];
          $selectedRole = $posted['role'] ?? 'sme_owner';
          foreach ($roleOpts as [$val, $icon, $bg, $color, $name, $desc, $dest]):
          ?>
          <label class="role-opt <?= $selectedRole === $val ? 'active' : '' ?>" for="role_<?= $val ?>">
            <input type="radio" name="role" id="role_<?= $val ?>" value="<?= $val ?>"
                   <?= $selectedRole === $val ? 'checked' : '' ?>>
            <div class="role-opt-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
              <i class="bi <?= $icon ?>"></i>
            </div>
            <div class="role-opt-body">
              <div class="role-opt-name"><?= $name ?></div>
              <div class="role-opt-desc"><?= $desc ?></div>
              <div class="role-opt-dest"><?= $dest ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Account info -->
        <div class="section-sep">Account details</div>

        <div class="mb-3">
          <label class="form-label" for="name">Full Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="name" name="name"
                   placeholder="Ahmad bin Ali" required autocomplete="name"
                   value="<?= htmlspecialchars($posted['name'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Work Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="ahmad@company.com.my" required autocomplete="email"
                   value="<?= htmlspecialchars($posted['email'] ?? '') ?>">
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password"
                     placeholder="Min 8 chars" required minlength="8" autocomplete="new-password">
              <button type="button" class="pw-toggle" id="togglePw1"><i class="bi bi-eye" id="pwIcon1"></i></button>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label" for="confirm_password">Confirm</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                     placeholder="Repeat" required autocomplete="new-password">
              <button type="button" class="pw-toggle" id="togglePw2"><i class="bi bi-eye" id="pwIcon2"></i></button>
            </div>
          </div>
          <div class="col-12">
            <div id="pwStrength" class="form-text"></div>
          </div>
        </div>

        <!-- Role context hint -->
        <div id="roleHint" class="mb-4 p-3 rounded" style="background:rgba(22,163,74,0.08);border:1px solid rgba(22,163,74,0.2);font-size:13px;color:#86efac;display:none">
          <i class="bi bi-info-circle me-1"></i><span id="roleHintText"></span>
        </div>

        <button type="submit" class="btn-register" id="submitBtn">
          <i class="bi bi-rocket-takeoff me-2"></i>Create Account &amp; Get Started
        </button>

      </form>

      <div class="text-center mt-4" style="font-size:14px">
        <span style="color:#64748b">Already have an account?</span>
        <a href="<?= APP_URL ?>/login" style="color:#22c55e;font-weight:700;text-decoration:none;margin-left:6px">
          Sign in <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="text-center mt-2">
        <a href="<?= APP_URL ?>/" style="color:#475569;font-size:12px;text-decoration:none">
          <i class="bi bi-arrow-left me-1"></i>Back to home
        </a>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Role selector toggle
document.querySelectorAll('.role-opt').forEach(function(label) {
  label.addEventListener('click', function() {
    document.querySelectorAll('.role-opt').forEach(l => l.classList.remove('active'));
    this.classList.add('active');
    updateRoleHint(this.querySelector('input').value);
  });
});

const roleHints = {
  sme_owner:  'After registration you will set up your company profile — takes about 5 minutes.',
  principal:  'You will go straight to your portfolio dashboard. Add associates later via Team Management.',
  associate:  'You will go straight to your client dashboard. Set up client companies from there.',
  manager:    'You will go straight to your task dashboard. Your associate will assign clients to you.',
};

function updateRoleHint(role) {
  const hint = document.getElementById('roleHint');
  const text = document.getElementById('roleHintText');
  if (roleHints[role]) {
    text.textContent = roleHints[role];
    hint.style.display = 'block';
  } else {
    hint.style.display = 'none';
  }
}

// Show hint for pre-selected role
updateRoleHint('<?= htmlspecialchars($posted['role'] ?? 'sme_owner') ?>');

// Password show/hide toggles
function pwToggle(toggleId, inputId, iconId) {
  document.getElementById(toggleId)?.addEventListener('click', function() {
    const pw   = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
}
pwToggle('togglePw1','password','pwIcon1');
pwToggle('togglePw2','confirm_password','pwIcon2');

// Password strength indicator
document.getElementById('password')?.addEventListener('input', function() {
  const pw  = this.value;
  const el  = document.getElementById('pwStrength');
  if (!pw) { el.textContent = ''; return; }
  let strength = 0;
  if (pw.length >= 8)  strength++;
  if (/[A-Z]/.test(pw)) strength++;
  if (/[0-9]/.test(pw)) strength++;
  if (/[^A-Za-z0-9]/.test(pw)) strength++;
  const labels = ['','Weak','Fair','Good','Strong'];
  const colors = ['','#ef4444','#f97316','#eab308','#22c55e'];
  el.innerHTML = `Password strength: <span style="color:${colors[strength]};font-weight:700">${labels[strength]}</span>`;
});

// Prevent double-submit
document.getElementById('regForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account…';
});
</script>
</body>
</html>
