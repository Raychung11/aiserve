<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $routeKey = trim($_POST['route_key'] ?? '');
    $routeName = trim($_POST['route_name'] ?? '');
    $keywordMatch = trim($_POST['keyword_match'] ?? '');
    $assignedAdminId = (int)($_POST['assigned_admin_id'] ?? 0);
    $assignedDepartment = trim($_POST['assigned_department'] ?? '');

    if ($routeKey !== '' && $routeName !== '') {
        $stmt = db()->prepare("
            INSERT INTO wa_department_routes
            (route_key, route_name, keyword_match, assigned_admin_id, assigned_department, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                route_name = VALUES(route_name),
                keyword_match = VALUES(keyword_match),
                assigned_admin_id = VALUES(assigned_admin_id),
                assigned_department = VALUES(assigned_department)
        ");
        $stmt->execute([
            $routeKey,
            $routeName,
            $keywordMatch,
            $assignedAdminId > 0 ? $assignedAdminId : null,
            $assignedDepartment
        ]);
    }

    redirect('/admin/whatsapp_routing.php');
}

$admins = db()->query("SELECT id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();
$routes = db()->query("SELECT * FROM wa_department_routes ORDER BY id DESC")->fetchAll();

admin_header('WhatsApp Routing');
?>

<div class="card">
    <h3 style="margin-top:0;">Department Routing Rules</h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Route Key</label>
                <input type="text" name="route_key" placeholder="sales_route" required>
            </div>
            <div class="field">
                <label>Route Name</label>
                <input type="text" name="route_name" placeholder="Sales Route" required>
            </div>
            <div class="field full">
                <label>Keyword Match</label>
                <input type="text" name="keyword_match" placeholder="quotation,quote,pricing,sales">
            </div>
            <div class="field">
                <label>Assigned Admin</label>
                <select name="assigned_admin_id">
                    <option value="0">No direct admin</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= (int)$admin['id'] ?>"><?= h($admin['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Department</label>
                <input type="text" name="assigned_department" placeholder="sales">
            </div>
            <div class="field full">
                <button type="submit" class="btn">Save Route</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Existing Routes</h3>
    <?php foreach ($routes as $route): ?>
        <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
            <strong><?= h($route['route_name']) ?></strong>
            <div class="muted">Key: <?= h($route['route_key']) ?></div>
            <div class="muted">Keywords: <?= h($route['keyword_match']) ?></div>
            <div class="muted">Department: <?= h($route['assigned_department']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>