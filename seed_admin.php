<?php
/**
 * STRHub — First Admin Seeder
 * Run ONCE via browser: https://roomee.my/seed_admin.php
 * DELETE this file from the server after use.
 */

// ── Change these before running ──────────────────────────────────────────────
$COMPANY   = 'SLV Group Sdn Bhd';
$ADMIN_NAME  = 'Admin';
$ADMIN_EMAIL = 'admin@roomee.my';
$ADMIN_PASS  = 'Admin@1234';   // change this — min 8 chars
$PLAN        = 'growth';       // starter | growth | enterprise
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/config/database.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $db  = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Check if tenant already exists
    $exists = $db->prepare('SELECT id FROM tenants WHERE email=? LIMIT 1');
    $exists->execute([$ADMIN_EMAIL]);
    if ($exists->fetch()) {
        die('<p style="font-family:sans-serif;color:#b45309;padding:2rem;">
            ⚠️ Admin with that email already exists. Seeder skipped.<br>
            <strong>Delete this file from the server.</strong></p>');
    }

    $planLimits = [
        'starter'    => ['max_properties' => 5],
        'growth'     => ['max_properties' => 20],
        'enterprise' => ['max_properties' => 999],
    ];
    $maxProps  = $planLimits[$PLAN]['max_properties'] ?? 5;
    $trialEnd  = date('Y-m-d H:i:s', strtotime('+14 days'));
    $now       = date('Y-m-d H:i:s');

    $db->beginTransaction();

    // Insert tenant
    $t = $db->prepare(
        'INSERT INTO tenants (name, email, plan, status, max_properties, trial_ends_at, created_at, updated_at)
         VALUES (?,?,?,\'trial\',?,?,?,?)'
    );
    $t->execute([$COMPANY, $ADMIN_EMAIL, $PLAN, $maxProps, $trialEnd, $now, $now]);
    $tenantId = (int)$db->lastInsertId();

    // Insert admin user
    $u = $db->prepare(
        'INSERT INTO str_users (tenant_id, name, email, password_hash, role, is_active, created_at, updated_at)
         VALUES (?,?,?,?,\'admin\',1,?,?)'
    );
    $u->execute([
        $tenantId,
        $ADMIN_NAME,
        $ADMIN_EMAIL,
        password_hash($ADMIN_PASS, PASSWORD_BCRYPT),
        $now,
        $now,
    ]);

    $db->commit();

} catch (Throwable $e) {
    echo '<p style="font-family:sans-serif;color:#dc2626;padding:2rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
  body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .card { background:#fff; border-radius:16px; padding:2.5rem; max-width:420px; width:100%; box-shadow:0 8px 32px rgba(0,0,0,.1); }
  .badge { background:#dcfce7; color:#15803d; font-size:.75rem; font-weight:700; padding:.25rem .75rem; border-radius:20px; }
  .row { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #f1f5f9; font-size:.875rem; }
  .label { color:#64748b; }
  .val { font-weight:600; color:#0f172a; }
  .btn { display:block; text-align:center; background:#6366f1; color:#fff; text-decoration:none; padding:.65rem 1.5rem; border-radius:10px; font-weight:700; margin-top:1.5rem; }
  .warn { background:#fef3c7; color:#92400e; border-radius:10px; padding:.75rem 1rem; font-size:.8rem; margin-top:1rem; }
</style>
</head>
<body>
<div class="card">
  <div style="text-align:center;margin-bottom:1.5rem;">
    <span class="badge">✓ Admin created</span>
    <h4 style="margin:.75rem 0 .25rem;font-weight:800;">Roomee is ready</h4>
    <p style="color:#64748b;font-size:.85rem;margin:0;">Your first admin account has been set up.</p>
  </div>
  <div class="row"><span class="label">Company</span><span class="val"><?= htmlspecialchars($COMPANY) ?></span></div>
  <div class="row"><span class="label">Email</span><span class="val"><?= htmlspecialchars($ADMIN_EMAIL) ?></span></div>
  <div class="row"><span class="label">Password</span><span class="val"><?= htmlspecialchars($ADMIN_PASS) ?></span></div>
  <div class="row"><span class="label">Plan</span><span class="val"><?= ucfirst($PLAN) ?> (14-day trial)</span></div>
  <a href="/login" class="btn">Go to Login &rarr;</a>
  <div class="warn">⚠️ <strong>Delete this file</strong> from the server immediately after logging in:<br>
    <code style="font-size:.78rem;">public_html/seed_admin.php</code>
  </div>
</div>
</body></html>
