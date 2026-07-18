<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (
        full_name LIKE ?
        OR company_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        OR interest LIKE ?
    )";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($status !== '' && in_array($status, contact_status_options(), true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_header('Contact List');
?>

<div class="card">
    <h3 style="margin-top:0;">Contact Submissions</h3>
    <div class="muted" style="margin-bottom:14px;">All website subscription inquiries for AiServe.my</div>

    <form method="get" style="margin-bottom:16px;">
        <div class="form-grid">
            <div class="field">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Name, company, email, phone, interest">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="">All status</option>
                    <?php foreach (contact_status_options() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn">Filter</button>
            <a href="/admin/contacts.php" class="btn-secondary">Reset</a>
            <a href="/admin/contacts_export.php?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>" class="btn-secondary">Export CSV</a>
        </div>
    </form>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Interest</th>
                    <th>Status</th>
                    <th>Subscribed</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th>View</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="11">No contacts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['full_name']) ?></td>
                        <td><?= h($r['company_name']) ?></td>
                        <td><?= h($r['email']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h($r['interest']) ?></td>
                        <td><span class="pill"><?= h($r['status']) ?></span></td>
                        <td><?= (int)$r['is_subscribed'] === 1 ? 'Yes' : 'No' ?></td>
                        <td><?= h($r['source_page']) ?></td>
                        <td><?= h($r['created_at']) ?></td>
                        <td><a href="/admin/contact_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>