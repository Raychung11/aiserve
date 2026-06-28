<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

registerDebugShutdown();
requireOwnerLogin();

$db        = getDB();
$ownerId   = (int)$_SESSION['owner_id'];
$companyId = (int)$_SESSION['owner_company_id'];

// Fetch owner bank info
$stmtOwner = $db->prepare('SELECT * FROM owners WHERE id = ? AND company_id = ? LIMIT 1');
$stmtOwner->execute([$ownerId, $companyId]);
$ownerRow = $stmtOwner->fetch() ?: [];

// Build last 6 month period tabs
$periodTabs = [];
for ($i = 0; $i < 6; $i++) {
    $ts = strtotime('-' . $i . ' months');
    $periodTabs[] = date('Y-m', $ts);
}

// Selected period filter - default to latest
$selectedPeriod = $_GET['period'] ?? $periodTabs[0];
// Validate it is one of the tabs or a valid YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $selectedPeriod)) {
    $selectedPeriod = $periodTabs[0];
}

// Fetch all payouts (up to 60, ordered newest first)
$stmtAll = $db->prepare(
    'SELECT p.*, u.unit_no, b.name AS building_name
     FROM owner_payouts p
     JOIN units u ON u.id = p.unit_id
     JOIN buildings b ON b.id = u.building_id
     WHERE p.company_id = ? AND p.owner_id = ?
     ORDER BY p.period DESC, u.unit_no ASC
     LIMIT 60'
);
$stmtAll->execute([$companyId, $ownerId]);
$allPayouts = $stmtAll->fetchAll();

// Filter for selected period
$filtered = array_values(array_filter($allPayouts, static function (array $p) use ($selectedPeriod): bool {
    return $p['period'] === $selectedPeriod;
}));

// Summary for selected period
$summaryGross      = 0.0;
$summaryDeductions = 0.0;
$summaryNet        = 0.0;
foreach ($filtered as $row) {
    $summaryGross      += (float)$row['gross_rent'];
    $summaryDeductions += (float)$row['deductions'];
    $summaryNet        += (float)$row['net_payout'];
}

function payoutStatusBadge(string $status): string
{
    return match ($status) {
        'paid'     => '<span class="s-badge badge-paid">Paid</span>',
        'approved' => '<span class="s-badge badge-approved">Approved</span>',
        default    => '<span class="s-badge badge-draft">Draft</span>',
    };
}

$pageTitle  = 'Payouts';
$activePage = 'payouts';
include __DIR__ . '/layout.php';
?>

<style>
.badge-draft    { background: #f1f5f9; color: #64748b; }
.badge-approved { background: #dbeafe; color: #1d4ed8; }

.period-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: 1.25rem;
}
.period-btn {
    padding: .35rem .85rem;
    border-radius: 20px;
    border: 1.5px solid #e2d9fb;
    background: #fff;
    font-size: .78rem;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    transition: background .12s, color .12s, border-color .12s;
    cursor: pointer;
    white-space: nowrap;
}
.period-btn:hover {
    background: var(--brand-dim);
    border-color: var(--brand);
    color: var(--brand);
}
.period-btn.active {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
}

.summary-strip {
    background: var(--brand-soft);
    border: 1px solid var(--brand-dim);
    border-radius: 12px;
    padding: .85rem 1.25rem;
    margin-bottom: 1.25rem;
}
.summary-item-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94a3b8;
    font-weight: 600;
}
.summary-item-val {
    font-size: 1rem;
    font-weight: 800;
    color: #1a1035;
}

.bank-info-box {
    background: #f8f7ff;
    border: 1px solid #ede9fb;
    border-radius: 12px;
    padding: .9rem 1.2rem;
    margin-bottom: 1.5rem;
    font-size: .83rem;
    color: #64748b;
}
.bank-info-box strong { color: #1a1035; }

.payout-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #ede9fb;
    padding: 1rem 1.2rem;
    margin-bottom: .75rem;
}
.payout-period-badge {
    font-size: .7rem;
    font-weight: 700;
    padding: .2rem .6rem;
    border-radius: 8px;
    background: var(--brand-soft);
    color: var(--brand);
    letter-spacing: .04em;
}
.payout-meta {
    font-size: .78rem;
    color: #94a3b8;
}
.payout-amount {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1a1035;
}
.payout-deduction {
    font-size: .78rem;
    color: #ef4444;
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}
.empty-state i {
    font-size: 2.5rem;
    color: #cbd5e1;
    display: block;
    margin-bottom: .75rem;
}
.empty-state p {
    color: #94a3b8;
    font-size: .88rem;
    margin: 0;
}

/* Desktop table view */
@media (min-width: 768px) {
    .payout-card-list { display: none; }
    .payout-table-wrap { display: block; }
}
@media (max-width: 767.98px) {
    .payout-card-list { display: block; }
    .payout-table-wrap { display: none; }
}
</style>

<!-- Page Heading -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color:#1a1035;font-size:1.05rem;">Payouts</h5>
</div>

<!-- Bank Info Box -->
<div class="bank-info-box">
    <i class="bi bi-bank me-2 text-brand"></i>
    <?php if (!empty($ownerRow['bank_account'])): ?>
    Your registered bank account:
    <strong><?= e((string)($ownerRow['bank_name'] ?? '')) ?></strong>
    &mdash;
    Account No. <strong><?= e((string)$ownerRow['bank_account']) ?></strong>
    <?php if (!empty($ownerRow['bank_holder'])): ?>
    (<?= e((string)$ownerRow['bank_holder']) ?>)
    <?php endif; ?>
    <?php else: ?>
    <span class="text-danger fw-semibold">No bank account on file.</span>
    Please contact your property manager to register your bank details.
    <?php endif; ?>
</div>

<!-- Period Filter Tabs -->
<div class="period-tabs">
    <?php foreach ($periodTabs as $tab):
        $label    = date('M Y', strtotime($tab . '-01'));
        $isActive = $tab === $selectedPeriod;
        $url      = e(APP_URL . '/portal/owner/payouts.php?period=' . urlencode($tab));
    ?>
    <a href="<?= $url ?>" class="period-btn <?= $isActive ? 'active' : '' ?>">
        <?= e($label) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Summary strip (only when records exist) -->
<?php if (!empty($filtered)): ?>
<div class="summary-strip">
    <div class="row g-2 text-center">
        <div class="col-4">
            <div class="summary-item-label">Gross Rent</div>
            <div class="summary-item-val"><?= e(money($summaryGross)) ?></div>
        </div>
        <div class="col-4">
            <div class="summary-item-label">Deductions</div>
            <div class="summary-item-val text-danger">&minus;<?= e(money($summaryDeductions)) ?></div>
        </div>
        <div class="col-4">
            <div class="summary-item-label">Net Payout</div>
            <div class="summary-item-val text-brand"><?= e(money($summaryNet)) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($filtered)): ?>
<!-- Empty state -->
<div class="card-box empty-state">
    <i class="bi bi-wallet2"></i>
    <p>No payout records for <?= e(date('F Y', strtotime($selectedPeriod . '-01'))) ?>.</p>
</div>

<?php else: ?>

<!-- Mobile: card list -->
<div class="payout-card-list">
    <?php foreach ($filtered as $row): ?>
    <div class="payout-card">
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div>
                <span class="payout-period-badge"><?= e($row['period']) ?></span>
                <div class="mt-1 fw-semibold" style="font-size:.88rem;color:#1a1035;">
                    Unit <?= e($row['unit_no']) ?>
                </div>
                <div class="payout-meta"><?= e($row['building_name']) ?></div>
            </div>
            <div class="text-end">
                <?= payoutStatusBadge((string)$row['status']) ?>
                <div class="payout-amount mt-1"><?= e(money((float)$row['net_payout'])) ?></div>
                <?php if ((float)$row['deductions'] > 0): ?>
                <div class="payout-deduction">&minus;<?= e(money((float)$row['deductions'])) ?> deducted</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between" style="font-size:.75rem;color:#94a3b8;">
            <span><i class="bi bi-cash me-1"></i>Gross: <?= e(money((float)$row['gross_rent'])) ?></span>
            <?php if (!empty($row['paid_date'])): ?>
            <span><i class="bi bi-calendar-check me-1"></i><?= dateDisplay((string)$row['paid_date']) ?></span>
            <?php else: ?>
            <span>&mdash;</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($row['bank_ref'])): ?>
        <div style="font-size:.73rem;color:#94a3b8;margin-top:.3rem;">
            <i class="bi bi-hash me-1"></i>Ref: <?= e($row['bank_ref']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Desktop: table -->
<div class="payout-table-wrap card-box p-0" style="overflow:hidden;">
    <table class="table tbl mb-0">
        <thead>
            <tr>
                <th>Period</th>
                <th>Unit</th>
                <th>Building</th>
                <th class="text-end">Gross</th>
                <th class="text-end">Deductions</th>
                <th class="text-end">Net</th>
                <th>Status</th>
                <th>Paid Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filtered as $row): ?>
            <tr>
                <td><span class="payout-period-badge"><?= e($row['period']) ?></span></td>
                <td class="fw-semibold">Unit <?= e($row['unit_no']) ?></td>
                <td><?= e($row['building_name']) ?></td>
                <td class="text-end"><?= e(money((float)$row['gross_rent'])) ?></td>
                <td class="text-end text-danger"><?= (float)$row['deductions'] > 0 ? '&minus;' . e(money((float)$row['deductions'])) : '&mdash;' ?></td>
                <td class="text-end fw-bold"><?= e(money((float)$row['net_payout'])) ?></td>
                <td><?= payoutStatusBadge((string)$row['status']) ?></td>
                <td><?= !empty($row['paid_date']) ? dateDisplay((string)$row['paid_date']) : '&mdash;' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
