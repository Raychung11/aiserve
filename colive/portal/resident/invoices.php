<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';
registerDebugShutdown();
requireResidentLogin();

$db         = getDB();
$residentId = (int)$_SESSION['resident_id'];
$companyId  = (int)$_SESSION['resident_company_id'];

// ── Status badge helper ───────────────────────────────────────────────────────
function invoiceStatusBadge(string $status): string {
    $map = [
        'paid'    => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Paid'],
        'overdue' => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'label' => 'Overdue'],
        'partial' => ['bg' => '#fef9c3', 'color' => '#a16207', 'label' => 'Partial'],
        'issued'  => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'label' => 'Issued'],
        'draft'   => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Draft'],
        'void'    => ['bg' => '#f1f5f9', 'color' => '#94a3b8', 'label' => 'Void'],
    ];
    $s     = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => ucfirst($status)];
    $lbl   = htmlspecialchars($s['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $bg    = htmlspecialchars($s['bg'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $clr   = htmlspecialchars($s['color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<span class="s-badge" style="background:' . $bg . ';color:' . $clr . ';">' . $lbl . '</span>';
}

// ── Detail view ───────────────────────────────────────────────────────────────
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
if ($viewId > 0) {

    // Fetch invoice — scoped to resident + company
    $stmt = $db->prepare(
        'SELECT * FROM invoices
         WHERE id = ? AND company_id = ? AND resident_id = ?
         LIMIT 1'
    );
    $stmt->execute([$viewId, $companyId, $residentId]);
    $invoice = $stmt->fetch() ?: null;

    if (!$invoice) {
        flashSet('error', 'Invoice not found.');
        header('Location: ' . APP_URL . '/portal/resident/invoices.php');
        exit;
    }

    // Line items
    $stmt = $db->prepare(
        'SELECT * FROM invoice_items
         WHERE invoice_id = ? AND company_id = ?
         ORDER BY id ASC'
    );
    $stmt->execute([$viewId, $companyId]);
    $lineItems = $stmt->fetchAll();

    // Payment history for this invoice
    $stmt = $db->prepare(
        'SELECT * FROM payments
         WHERE invoice_id = ? AND company_id = ? AND resident_id = ?
         ORDER BY payment_date ASC'
    );
    $stmt->execute([$viewId, $companyId, $residentId]);
    $paymentHistory = $stmt->fetchAll();

    $pageTitle  = 'Invoice ' . ($invoice['invoice_no'] ?? '#' . $viewId);
    $activePage = 'invoices';
    include __DIR__ . '/layout.php';
?>

<style>
.inv-detail-header {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    padding: 1.25rem 1.35rem;
    margin-bottom: 1rem;
}
.inv-no    { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
.inv-meta  { font-size: .78rem; color: #64748b; margin-top: .15rem; }

.inv-amounts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}
.inv-amt-block { text-align: center; }
.inv-amt-lbl { font-size: .67rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; margin-bottom: .15rem; }
.inv-amt-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
.inv-amt-val.balance-val { color: #b91c1c; }
.inv-amt-val.paid-val    { color: #15803d; }

.items-table-wrap {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 1rem;
}
.items-table-head {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: .6rem 1.1rem;
    display: flex; align-items: center; gap: .4rem;
}
.items-table-head span {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em; color: #64748b;
}
.items-tbl { width: 100%; border-collapse: collapse; font-size: .82rem; }
.items-tbl th {
    font-size: .67rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: #94a3b8; padding: .5rem 1.1rem;
    border-bottom: 1px solid #f1f5f9; text-align: left;
}
.items-tbl th.num { text-align: right; }
.items-tbl td {
    padding: .65rem 1.1rem; color: #334155;
    border-bottom: 1px solid #f8fafc; vertical-align: top;
}
.items-tbl tr:last-child td { border-bottom: none; }
.items-tbl td.num { text-align: right; white-space: nowrap; }
.items-tbl .item-type-badge {
    display: inline-block; padding: .1rem .4rem;
    border-radius: 4px; font-size: .65rem; font-weight: 600;
    background: #f1f5f9; color: #64748b;
    margin-top: .1rem;
}
.items-tbl .total-row td {
    border-top: 2px solid #e2e8f0;
    font-weight: 800; background: #f8fafc;
    color: #0f172a; border-bottom: none;
}

.pmt-history-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    padding: 1.1rem 1.25rem;
    margin-bottom: 1rem;
}
.pmt-history-title {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
    color: #94a3b8; margin-bottom: .85rem;
}
.pmt-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: .83rem;
}
.pmt-row:last-child { border-bottom: none; }
.pmt-row-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: #f0fdf4; color: #16a34a;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0;
}
.pmt-row-desc   { font-weight: 600; color: #1e293b; }
.pmt-row-sub    { font-size: .7rem; color: #94a3b8; }
.pmt-row-amount { margin-left: auto; font-weight: 700; color: #16a34a; white-space: nowrap; }

.pay-now-card {
    background: linear-gradient(135deg, var(--brand) 0%, color-mix(in srgb, var(--brand) 65%, #1e40af) 100%);
    border-radius: 14px;
    padding: 1.15rem 1.35rem;
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    margin-bottom: 1rem;
    color: #fff;
}
.pay-now-label  { font-size: .85rem; font-weight: 800; }
.pay-now-sub    { font-size: .72rem; opacity: .8; margin-top: .1rem; }
.btn-pay-now {
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.4);
    color: #fff; font-weight: 700; font-size: .82rem;
    border-radius: 8px; padding: .45rem .9rem;
    text-decoration: none; white-space: nowrap;
    transition: background .15s;
}
.btn-pay-now:hover { background: rgba(255,255,255,.3); color: #fff; }
</style>

<!-- Back link -->
<a href="<?= e(APP_URL) ?>/portal/resident/invoices.php"
   class="d-inline-flex align-items-center gap-1 mb-3 text-decoration-none"
   style="font-size:.82rem; color:var(--brand); font-weight:700;">
    <i class="bi bi-arrow-left"></i> All Invoices
</a>

<!-- Invoice header -->
<div class="inv-detail-header">
    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
        <div>
            <div class="inv-no">Invoice <?= e($invoice['invoice_no'] ?? '#' . $invoice['id']) ?></div>
            <div class="inv-meta">
                Period: <?= e($invoice['period'] ?? '&mdash;') ?>
                &nbsp;&middot;&nbsp;
                Due: <?= dateDisplay($invoice['due_date']) ?>
                &nbsp;&middot;&nbsp;
                Issued: <?= dateDisplay($invoice['created_at']) ?>
            </div>
        </div>
        <div><?= invoiceStatusBadge((string)$invoice['status']) ?></div>
    </div>
    <div class="inv-amounts-grid">
        <div class="inv-amt-block">
            <div class="inv-amt-lbl">Total</div>
            <div class="inv-amt-val"><?= money((float)$invoice['total_amount']) ?></div>
        </div>
        <div class="inv-amt-block">
            <div class="inv-amt-lbl">Paid</div>
            <div class="inv-amt-val paid-val"><?= money((float)$invoice['amount_paid']) ?></div>
        </div>
        <div class="inv-amt-block">
            <div class="inv-amt-lbl">Balance</div>
            <div class="inv-amt-val <?= ((float)$invoice['balance'] > 0) ? 'balance-val' : '' ?>">
                <?= money((float)$invoice['balance']) ?>
            </div>
        </div>
    </div>
</div>

<!-- Pay Now CTA — only if there's a balance -->
<?php if (in_array($invoice['status'], ['issued', 'partial', 'overdue'], true) && (float)$invoice['balance'] > 0): ?>
<div class="pay-now-card">
    <div>
        <div class="pay-now-label">Pay <?= money((float)$invoice['balance']) ?> Now</div>
        <div class="pay-now-sub">Online payment &mdash; coming soon</div>
    </div>
    <a href="#" class="btn-pay-now" title="Online payment coming soon">
        <i class="bi bi-credit-card me-1"></i>Pay Now
    </a>
</div>
<?php endif; ?>

<!-- Line items -->
<div class="items-table-wrap">
    <div class="items-table-head">
        <i class="bi bi-list-ul" style="color:#64748b; font-size:.9rem;"></i>
        <span>Line Items</span>
    </div>
    <?php if (empty($lineItems)): ?>
        <div class="text-center py-4" style="color:#94a3b8; font-size:.83rem;">
            <i class="bi bi-inbox" style="font-size:1.5rem; display:block; margin-bottom:.4rem;"></i>
            No line items found for this invoice.
        </div>
    <?php else: ?>
    <table class="items-tbl">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $item): ?>
            <tr>
                <td>
                    <div style="font-weight:600; color:#1e293b;"><?= e($item['description']) ?></div>
                    <?php if (!empty($item['item_type'])): ?>
                    <div class="item-type-badge"><?= e(ucfirst(str_replace('_', ' ', (string)$item['item_type']))) ?></div>
                    <?php endif; ?>
                </td>
                <td class="num"><?= e((string)$item['qty']) ?></td>
                <td class="num"><?= money((float)$item['unit_price']) ?></td>
                <td class="num" style="font-weight:700;"><?= money((float)$item['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td class="num"><?= money((float)$invoice['total_amount']) ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<!-- Payment history -->
<div class="pmt-history-card">
    <div class="pmt-history-title">Payment History</div>
    <?php if (empty($paymentHistory)): ?>
        <div class="text-center py-3" style="color:#94a3b8; font-size:.82rem;">
            <i class="bi bi-clock-history" style="font-size:1.4rem; display:block; margin-bottom:.35rem;"></i>
            No payments recorded for this invoice yet.
        </div>
    <?php else: ?>
        <?php foreach ($paymentHistory as $p): ?>
        <div class="pmt-row">
            <div class="pmt-row-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <div class="pmt-row-desc"><?= e(ucfirst(str_replace('_', ' ', (string)($p['payment_method'] ?? '')))) ?></div>
                <div class="pmt-row-sub">
                    <?= dateDisplay($p['payment_date']) ?>
                    <?php if (!empty($p['gateway_ref'])): ?>
                        &middot; Ref: <?= e($p['gateway_ref']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pmt-row-amount">+<?= money((float)$p['amount']) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
    include __DIR__ . '/layout_end.php';
    exit;
}

// ── LIST VIEW ─────────────────────────────────────────────────────────────────

$statusFilter = $_GET['status'] ?? 'all';
$allowedFilters = ['all' => 'All', 'outstanding' => 'Outstanding', 'paid' => 'Paid'];
if (!array_key_exists($statusFilter, $allowedFilters)) {
    $statusFilter = 'all';
}

// Build WHERE clause based on filter
if ($statusFilter === 'outstanding') {
    $statusWhere = "AND status IN ('issued','partial','overdue')";
    $bindParams  = [$companyId, $residentId];
} elseif ($statusFilter === 'paid') {
    $statusWhere = "AND status = 'paid'";
    $bindParams  = [$companyId, $residentId];
} else {
    $statusWhere = '';
    $bindParams  = [$companyId, $residentId];
}

$stmt = $db->prepare(
    'SELECT * FROM invoices
     WHERE company_id = ? AND resident_id = ?
     ' . $statusWhere . '
     ORDER BY created_at DESC
     LIMIT 50'
);
$stmt->execute($bindParams);
$invoices = $stmt->fetchAll();

// Summary counts for filter tabs
$stmt = $db->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status IN ('issued','partial','overdue') THEN 1 ELSE 0 END) AS outstanding,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid
     FROM invoices
     WHERE company_id = ? AND resident_id = ?"
);
$stmt->execute([$companyId, $residentId]);
$counts = $stmt->fetch();

$pageTitle  = 'My Invoices';
$activePage = 'invoices';
include __DIR__ . '/layout.php';
?>

<style>
.inv-filter-tabs {
    display: flex; gap: .35rem;
    margin-bottom: 1rem;
    overflow-x: auto;
    padding-bottom: .1rem;
    scrollbar-width: none;
}
.inv-filter-tabs::-webkit-scrollbar { display: none; }
.inv-tab {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .35rem .8rem;
    border-radius: 20px; font-size: .79rem; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    border: 1.5px solid #e2e8f0;
    background: #fff; color: #64748b;
    transition: border-color .13s, color .13s, background .13s;
}
.inv-tab:hover  { border-color: var(--brand); color: var(--brand); }
.inv-tab.active { border-color: var(--brand); background: var(--brand); color: #fff; }
.inv-tab-count {
    background: rgba(255,255,255,.25);
    border-radius: 20px;
    padding: .05rem .35rem;
    font-size: .65rem;
    min-width: 18px; text-align: center;
}
.inv-tab:not(.active) .inv-tab-count {
    background: #f1f5f9; color: #94a3b8;
}

/* Invoice card list item */
.inv-item {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: .9rem 1.1rem;
    margin-bottom: .6rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: .85rem;
    transition: box-shadow .15s, border-color .15s;
}
.inv-item:hover { box-shadow: 0 4px 14px rgba(0,0,0,.09); border-color: #c7d2fe; }

.inv-item-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: #eff6ff; color: #1d4ed8;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.inv-item-icon.paid    { background: #f0fdf4; color: #16a34a; }
.inv-item-icon.overdue { background: #fef2f2; color: #b91c1c; }
.inv-item-icon.partial { background: #fefce8; color: #a16207; }
.inv-item-icon.void    { background: #f8fafc; color: #94a3b8; }
.inv-item-icon.draft   { background: #f8fafc; color: #94a3b8; }

.inv-item-main  { flex: 1; min-width: 0; }
.inv-item-no    { font-size: .88rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.inv-item-meta  { font-size: .72rem; color: #94a3b8; margin-top: .1rem; }
.inv-item-right { flex-shrink: 0; text-align: right; }
.inv-item-amt   { font-size: .92rem; font-weight: 800; color: #0f172a; }
.inv-item-bal   { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
.inv-item-bal.has-balance { color: #b91c1c; font-weight: 600; }

/* Empty state */
.inv-empty {
    text-align: center; padding: 3rem 1rem;
    background: #fff; border-radius: 14px;
    border: 1px dashed #e2e8f0;
}
.inv-empty i   { font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: .65rem; }
.inv-empty h6  { font-size: .92rem; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
.inv-empty p   { font-size: .78rem; color: #94a3b8; margin: 0; }

.page-header-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .75rem;
}
</style>

<div class="page-header-row">
    <div>
        <div class="page-title">My Invoices</div>
        <p class="page-sub">Your billing history and payment records</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="inv-filter-tabs">
    <?php
    $tabDef = [
        'all'         => ['label' => 'All',         'count' => (int)($counts['total'] ?? 0)],
        'outstanding' => ['label' => 'Outstanding',  'count' => (int)($counts['outstanding'] ?? 0)],
        'paid'        => ['label' => 'Paid',         'count' => (int)($counts['paid'] ?? 0)],
    ];
    foreach ($tabDef as $key => $tab):
        $isActive = $statusFilter === $key;
        $url = e(APP_URL . '/portal/resident/invoices.php' . ($key !== 'all' ? '?status=' . $key : ''));
    ?>
    <a href="<?= $url ?>" class="inv-tab <?= $isActive ? 'active' : '' ?>">
        <?= e($tab['label']) ?>
        <span class="inv-tab-count"><?= $tab['count'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Invoice list -->
<?php if (empty($invoices)): ?>
    <div class="inv-empty">
        <i class="bi bi-receipt"></i>
        <h6>
            <?php if ($statusFilter === 'outstanding'): ?>
                No outstanding invoices
            <?php elseif ($statusFilter === 'paid'): ?>
                No paid invoices yet
            <?php else: ?>
                No invoices found
            <?php endif; ?>
        </h6>
        <p>
            <?php if ($statusFilter === 'outstanding'): ?>
                You have no unpaid invoices &mdash; great work!
            <?php else: ?>
                Invoices issued to you will appear here.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($invoices as $inv):
        $status    = (string)$inv['status'];
        $hasBalance = ((float)$inv['balance'] > 0.005);
        $iconClass  = match($status) {
            'paid'    => 'paid',
            'overdue' => 'overdue',
            'partial' => 'partial',
            'void',
            'draft'   => 'void',
            default   => '',
        };
        $icon = match($status) {
            'paid'    => 'bi-check-circle-fill',
            'overdue' => 'bi-exclamation-circle-fill',
            'partial' => 'bi-clock-fill',
            'void'    => 'bi-slash-circle',
            'draft'   => 'bi-file-earmark',
            default   => 'bi-receipt-cutoff',
        };
    ?>
    <a href="<?= e(APP_URL . '/portal/resident/invoices.php?view=' . (int)$inv['id']) ?>"
       class="inv-item text-decoration-none">

        <div class="inv-item-icon <?= e($iconClass) ?>">
            <i class="bi <?= e($icon) ?>"></i>
        </div>

        <div class="inv-item-main">
            <div class="inv-item-no"><?= e($inv['invoice_no'] ?? 'INV-' . $inv['id']) ?></div>
            <div class="inv-item-meta">
                <?php if (!empty($inv['period'])): ?>
                    <?= e($inv['period']) ?> &middot;
                <?php endif; ?>
                Due <?= dateDisplay($inv['due_date']) ?>
            </div>
            <div class="mt-1"><?= invoiceStatusBadge($status) ?></div>
        </div>

        <div class="inv-item-right">
            <div class="inv-item-amt"><?= money((float)$inv['total_amount']) ?></div>
            <?php if ($hasBalance && $status !== 'paid' && $status !== 'void'): ?>
                <div class="inv-item-bal has-balance">Bal: <?= money((float)$inv['balance']) ?></div>
            <?php elseif ($status === 'paid'): ?>
                <div class="inv-item-bal" style="color:#16a34a;">Paid in full</div>
            <?php else: ?>
                <div class="inv-item-bal">&nbsp;</div>
            <?php endif; ?>
        </div>

        <i class="bi bi-chevron-right" style="color:#cbd5e1; font-size:.8rem; flex-shrink:0;"></i>
    </a>
    <?php endforeach; ?>

    <?php if (count($invoices) >= 50): ?>
    <div class="text-center mt-2" style="font-size:.75rem; color:#94a3b8;">
        Showing the most recent 50 invoices.
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
