<?php
/**
 * Adcellent ESG OS — Demo Account Seeder
 * ------------------------------------
 * Run ONCE to create demo accounts for all 6 roles.
 * DELETE this file after use.
 *
 * Access: https://yourdomain.com/setup_demo?token=esg-demo-2024
 */

// ── Security token ──────────────────────────────────────────
define('SEED_TOKEN', 'esg-demo-2024');

if (($_GET['token'] ?? '') !== SEED_TOKEN) {
    http_response_code(403);
    die('<h2 style="font-family:monospace;color:red">403 — Missing or wrong ?token=</h2>');
}

// ── Bootstrap ────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Database.php';

// ── Demo password (same for all accounts) ───────────────────
$demoPassword = 'Demo@1234';
$hash         = password_hash($demoPassword, PASSWORD_BCRYPT);

// ── Demo accounts ───────────────────────────────────────────
$users = [
    ['Admin',         'admin@demo.com',       'admin'],
    ['Principal Lee', 'principal@demo.com',   'principal'],
    ['Associate Tan', 'associate@demo.com',   'associate'],
    ['Manager Wong',  'manager@demo.com',     'manager'],
    ['Consultant Ng', 'consultant@demo.com',  'consultant'],
    ['SME Owner Lim', 'owner@demo.com',       'sme_owner'],
];

$results = [];
$userIds = [];

foreach ($users as [$name, $email, $role]) {
    $existing = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) {
        $userIds[$role] = $existing['id'];
        $results[]      = ['status' => 'skip', 'email' => $email, 'role' => $role, 'msg' => 'already exists'];
        continue;
    }

    $id = Database::insert('users', [
        'name'      => $name,
        'email'     => $email,
        'password'  => $hash,
        'role'      => $role,
        'is_active' => 1,
    ]);

    // Set principal as parent of associate
    if ($role === 'associate' && isset($userIds['principal'])) {
        Database::query('UPDATE users SET parent_id = ? WHERE id = ?', [$userIds['principal'], $id]);
    }
    // Set associate as parent of manager
    if ($role === 'manager' && isset($userIds['associate'])) {
        Database::query('UPDATE users SET parent_id = ? WHERE id = ?', [$userIds['associate'], $id]);
    }

    $userIds[$role] = $id;
    $results[]      = ['status' => 'ok', 'email' => $email, 'role' => $role, 'msg' => "created (id=$id)"];
}

// ── Demo company linked to sme_owner and consultant ──────────
$demoCompanyId = null;
$existingCo    = Database::fetchOne("SELECT id FROM companies WHERE name = 'Demo Sdn Bhd'");

if ($existingCo) {
    $demoCompanyId = $existingCo['id'];
    $results[]     = ['status' => 'skip', 'email' => '—', 'role' => 'company', 'msg' => 'Demo Sdn Bhd already exists'];
} elseif (isset($userIds['sme_owner'])) {
    $demoCompanyId = Database::insert('companies', [
        'name'            => 'Demo Sdn Bhd',
        'registration_no' => '202401012345',
        'industry'        => 'Manufacturing',
        'revenue_tier'    => '10M_to_50M',
        'employee_count'  => 120,
        'framework'       => 'BURSA_SEDG',
        'reporting_year'  => date('Y'),
        'created_by'      => $userIds['sme_owner'],
    ]);
    $results[] = ['status' => 'ok', 'email' => '—', 'role' => 'company', 'msg' => "Demo Sdn Bhd created (id=$demoCompanyId)"];
}

// Link sme_owner and consultant to the demo company
if ($demoCompanyId) {
    foreach (['sme_owner' => 'owner', 'consultant' => 'editor'] as $role => $ucRole) {
        if (!isset($userIds[$role])) continue;
        $linked = Database::fetchOne(
            'SELECT id FROM user_companies WHERE user_id = ? AND company_id = ?',
            [$userIds[$role], $demoCompanyId]
        );
        if (!$linked) {
            Database::insert('user_companies', [
                'user_id'    => $userIds[$role],
                'company_id' => $demoCompanyId,
                'role'       => $ucRole,
            ]);
            $results[] = ['status' => 'ok', 'email' => '—', 'role' => $role, 'msg' => "linked to Demo Sdn Bhd as $ucRole"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demo Seed — Adcellent ESG OS</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #0f172a; color: #f1f5f9; padding: 40px 24px; }
    .wrap { max-width: 780px; margin: 0 auto; }
    h1 { font-size: 22px; font-weight: 800; color: #22c55e; margin-bottom: 4px; }
    .sub { font-size: 13px; color: #64748b; margin-bottom: 32px; }

    .warn { background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.4);
            border-radius: 10px; padding: 14px 18px; font-size: 13px; color: #fbbf24;
            margin-bottom: 28px; }
    .warn strong { display: block; margin-bottom: 4px; }

    .accounts { background: #1e293b; border: 1px solid #334155; border-radius: 12px;
                overflow: hidden; margin-bottom: 28px; }
    .accounts-head { background: #16a34a; padding: 12px 20px; font-size: 13px;
                     font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 10px 20px; font-size: 11px; font-weight: 700;
         text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;
         border-bottom: 1px solid #334155; }
    td { padding: 12px 20px; font-size: 13px; border-bottom: 1px solid #1e293b; }
    tr:last-child td { border-bottom: none; }
    .role-badge { display: inline-block; padding: 2px 10px; border-radius: 6px;
                  font-size: 11px; font-weight: 700; }
    .role-admin       { background: rgba(167,139,250,0.2); color: #a78bfa; }
    .role-principal   { background: rgba(96,165,250,0.2);  color: #60a5fa; }
    .role-associate   { background: rgba(56,189,248,0.2);  color: #38bdf8; }
    .role-manager     { background: rgba(148,163,184,0.2); color: #94a3b8; }
    .role-consultant  { background: rgba(251,191,36,0.2);  color: #fbbf24; }
    .role-sme_owner   { background: rgba(34,197,94,0.2);   color: #22c55e; }
    .role-company     { background: rgba(251,146,60,0.2);  color: #fb923c; }

    .pw-box { background: #0f172a; border: 1px solid #334155; border-radius: 8px;
              padding: 14px 20px; font-size: 14px; display: flex; align-items: center; gap: 16px; }
    .pw-box .label { color: #64748b; font-size: 12px; }
    .pw-box .pw { font-family: monospace; font-size: 18px; font-weight: 800; color: #22c55e; }

    .log { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-top: 28px; }
    .log-head { background: #334155; padding: 10px 20px; font-size: 12px; font-weight: 700;
                text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
    .log-item { padding: 8px 20px; font-size: 12px; font-family: monospace;
                border-bottom: 1px solid #0f172a; display: flex; gap: 10px; }
    .log-item:last-child { border-bottom: none; }
    .status-ok   { color: #22c55e; }
    .status-skip { color: #64748b; }

    .login-btn { display: inline-block; margin-top: 28px; background: #16a34a;
                 color: white; text-decoration: none; padding: 12px 28px; border-radius: 10px;
                 font-weight: 700; font-size: 14px; }
  </style>
</head>
<body>
<div class="wrap">
  <h1>Demo Accounts Created</h1>
  <p class="sub">Adcellent ESG OS — one-time seed script</p>

  <div class="warn">
    <strong>⚠ Delete this file after use</strong>
    Remove <code>setup_demo.php</code> from your <code>public_html/</code> — it grants account creation without authentication.
  </div>

  <!-- Password -->
  <div class="pw-box" style="margin-bottom:28px">
    <div>
      <div class="label">Password (all accounts)</div>
      <div class="pw"><?= htmlspecialchars($demoPassword) ?></div>
    </div>
    <div style="color:#475569;font-size:12px">Use this password to log in to every demo account below.</div>
  </div>

  <!-- Accounts table -->
  <div class="accounts">
    <div class="accounts-head">Demo Login Credentials</div>
    <table>
      <thead>
        <tr>
          <th>Role</th>
          <th>Email</th>
          <th>Password</th>
          <th>Redirects To</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="role-badge role-admin">Admin</span></td>
          <td>admin@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/admin</td>
        </tr>
        <tr>
          <td><span class="role-badge role-principal">Principal</span></td>
          <td>principal@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/dashboard (portfolio)</td>
        </tr>
        <tr>
          <td><span class="role-badge role-associate">Associate</span></td>
          <td>associate@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/dashboard (clients)</td>
        </tr>
        <tr>
          <td><span class="role-badge role-manager">Manager</span></td>
          <td>manager@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/dashboard (tasks)</td>
        </tr>
        <tr>
          <td><span class="role-badge role-consultant">Consultant</span></td>
          <td>consultant@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/companies (has demo company)</td>
        </tr>
        <tr>
          <td><span class="role-badge role-sme_owner">SME Owner</span></td>
          <td>owner@demo.com</td>
          <td><?= htmlspecialchars($demoPassword) ?></td>
          <td>/dashboard (has demo company)</td>
        </tr>
      </tbody>
    </table>
  </div>

  <a href="<?= APP_URL ?>/login" class="login-btn">Go to Login →</a>

  <!-- Seed log -->
  <div class="log" style="margin-top:28px">
    <div class="log-head">Seed Log</div>
    <?php foreach ($results as $r): ?>
    <div class="log-item">
      <span class="status-<?= $r['status'] ?>">[<?= strtoupper($r['status']) ?>]</span>
      <span class="role-badge role-<?= $r['role'] ?>"><?= $r['role'] ?></span>
      <span style="color:#94a3b8"><?= htmlspecialchars($r['email']) ?></span>
      <span style="color:#475569">— <?= htmlspecialchars($r['msg']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

</div>
</body>
</html>
