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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $result = Auth::login($email, $password);
        if ($result['success']) {
            header('Location: ' . Auth::postLoginUrl($result['user']));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-MY">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | AiServe ESG OS</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="canonical" href="<?= APP_URL ?>/login">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/landing.css" rel="stylesheet">
  <style>
    /* ── Login page overrides ────────────────────────── */
    .login-wrap {
      min-height: 100vh;
      display: flex;
    }

    /* Left panel */
    .login-brand {
      width: 420px;
      flex-shrink: 0;
      background: linear-gradient(160deg, #0d2a1e 0%, #0f172a 100%);
      border-right: 1px solid #1e293b;
      display: flex;
      flex-direction: column;
      padding: 40px 44px;
    }
    .login-brand-logo {
      width: 48px; height: 48px; border-radius: 14px;
      background: #16a34a;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; color: white; margin-bottom: 32px;
    }
    .login-brand h2 { font-size: 26px; font-weight: 800; color: #f1f5f9; margin-bottom: 8px; }
    .login-brand .brand-tagline { font-size: 14px; color: #64748b; margin-bottom: 40px; line-height: 1.6; }

    .role-chips { display: flex; flex-direction: column; gap: 10px; }
    .role-chip {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255,255,255,0.04);
      border: 1px solid #1e293b;
      border-radius: 12px;
      padding: 12px 16px;
    }
    .role-chip-icon {
      width: 36px; height: 36px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
    }
    .role-chip-body { flex: 1; }
    .role-chip-name  { font-size: 13px; font-weight: 700; color: #f1f5f9; }
    .role-chip-desc  { font-size: 11px; color: #64748b; }
    .role-chip-dest  { font-size: 10px; color: #475569; margin-top: 2px; font-family: monospace; }

    .login-brand-footer {
      margin-top: auto;
      padding-top: 32px;
      border-top: 1px solid #1e293b;
    }
    .fw-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .fw-chip  {
      font-size: 10px; font-weight: 600; padding: 3px 9px;
      border: 1px solid #334155; border-radius: 6px; color: #64748b;
    }

    /* Right panel — form */
    .login-form-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      padding: 40px 24px;
    }
    .login-form-box {
      width: 100%;
      max-width: 420px;
    }
    .login-form-box h1  { font-size: 28px; font-weight: 800; color: #f1f5f9; margin-bottom: 6px; }
    .login-form-box .sub { font-size: 14px; color: #64748b; margin-bottom: 32px; }

    .form-label        { font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
    .form-control {
      background: #1e293b; border: 1px solid #334155; color: #f1f5f9;
      border-radius: 10px; padding: 12px 14px; font-size: 14px;
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
    .input-group .form-control { border-radius: 0 10px 10px 0; }
    .input-group-text + .form-control { border-left: none; }
    .input-group .btn-toggle-pw {
      background: #1e293b; border: 1px solid #334155; border-left: none;
      color: #64748b; border-radius: 0 10px 10px 0; padding: 0 14px;
    }
    .input-group .btn-toggle-pw:hover { color: #94a3b8; }

    .btn-login {
      background: #16a34a; border: none; color: white;
      font-size: 15px; font-weight: 700; padding: 13px;
      border-radius: 10px; width: 100%;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-login:hover { background: #15803d; transform: translateY(-1px); }
    .btn-login:active { transform: translateY(0); }

    .divider-line {
      display: flex; align-items: center; gap: 12px;
      color: #334155; font-size: 12px; margin: 20px 0;
    }
    .divider-line::before, .divider-line::after {
      content: ''; flex: 1; height: 1px; background: #1e293b;
    }

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

    /* Responsive */
    @media (max-width: 768px) {
      .login-brand { display: none; }
      .login-form-panel { background: #0f172a; }
    }
  </style>
</head>
<body class="landing-body">

<div class="login-wrap">

  <!-- ═══════════════════ LEFT PANEL ═══════════════════ -->
  <div class="login-brand">

    <a href="<?= APP_URL ?>/" style="text-decoration:none">
      <div class="d-flex align-items-center gap-2 mb-4">
        <div class="login-brand-logo"><i class="bi bi-leaf-fill"></i></div>
        <span style="font-size:18px;font-weight:800;color:#f1f5f9">AiServe <span style="color:#16a34a">ESG OS</span></span>
      </div>
    </a>

    <h2>Welcome back</h2>
    <p class="brand-tagline">Sign in and pick up right where you left off. Every role gets their own tailored view.</p>

    <!-- Role guide -->
    <div class="role-chips">
      <?php
      $roles = [
        ['bi-building',        'rgba(22,163,74,0.2)',   '#22c55e', 'SME Owner',  'Your company ESG dashboard',   '→ Dashboard'],
        ['bi-diagram-3-fill',  'rgba(96,165,250,0.2)',  '#60a5fa', 'Principal',  'Firm-wide portfolio view',     '→ Dashboard (portfolio)'],
        ['bi-briefcase-fill',  'rgba(56,189,248,0.2)',  '#38bdf8', 'Associate',  'Client portfolio + team',      '→ Dashboard (clients)'],
        ['bi-person-workspace','rgba(148,163,184,0.2)', '#94a3b8', 'Manager',    'Assigned companies + tasks',   '→ Dashboard (tasks)'],
        ['bi-people-fill',     'rgba(251,191,36,0.2)',  '#fbbf24', 'Consultant', 'Multi-client management',      '→ Companies list'],
        ['bi-shield-lock',     'rgba(167,139,250,0.2)', '#a78bfa', 'Admin',      'System administration',        '→ Admin panel'],
      ];
      foreach ($roles as [$icon, $bg, $color, $name, $desc, $dest]):
      ?>
      <div class="role-chip">
        <div class="role-chip-icon" style="background:<?= $bg ?>; color:<?= $color ?>">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div class="role-chip-body">
          <div class="role-chip-name"><?= $name ?></div>
          <div class="role-chip-desc"><?= $desc ?></div>
          <div class="role-chip-dest"><?= $dest ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="login-brand-footer">
      <div style="font-size:11px;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Supported Frameworks</div>
      <div class="fw-chips">
        <?php foreach (['Bursa SEDG','GRI','TCFD','ISSB','CDP','ESRS','SASB'] as $fw): ?>
        <span class="fw-chip"><?= $fw ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ RIGHT PANEL — FORM ═══════════════════ -->
  <div class="login-form-panel">
    <div class="login-form-box">

      <h1>Sign in</h1>
      <p class="sub">Access your ESG dashboard</p>

      <?php if ($error): ?>
      <div class="error-box">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" id="loginForm">

        <div class="mb-3">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="you@company.com.my" required autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label mb-0" for="password">Password</label>
          </div>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="btn-toggle-pw" id="togglePw" title="Show / hide">
              <i class="bi bi-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>

      </form>

      <div class="divider-line">or</div>

      <div class="text-center" style="font-size:14px">
        <span style="color:#64748b">Don't have an account?</span>
        <a href="<?= APP_URL ?>/register" style="color:#22c55e;font-weight:700;text-decoration:none;margin-left:6px">
          Create free account <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="text-center mt-3">
        <a href="<?= APP_URL ?>/" style="color:#475569;font-size:12px;text-decoration:none">
          <i class="bi bi-arrow-left me-1"></i>Back to home
        </a>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password show/hide
document.getElementById('togglePw')?.addEventListener('click', function () {
  const pw   = document.getElementById('password');
  const icon = document.getElementById('pwIcon');
  const show = pw.type === 'password';
  pw.type    = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});

// Prevent double-submit
document.getElementById('loginForm')?.addEventListener('submit', function () {
  const btn = this.querySelector('button[type=submit]');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in…';
});
</script>
</body>
</html>
