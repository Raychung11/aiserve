<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';
registerDebugShutdown();
requireResidentLogin();

$db          = getDB();
$residentId  = (int)$_SESSION['resident_id'];
$companyId   = (int)$_SESSION['resident_company_id'];

// ── 1. Active tenancy ─────────────────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT t.*, r.room_no, r.room_type, u.unit_no, b.name AS building_name
     FROM tenancies t
     JOIN rooms    r ON r.id = t.room_id
     JOIN units    u ON u.id = r.unit_id
     JOIN buildings b ON b.id = u.building_id
     WHERE t.company_id = ? AND t.resident_id = ? AND t.status = ?
     LIMIT 1'
);
$stmt->execute([$companyId, $residentId, 'active']);
$tenancy = $stmt->fetch() ?: null;

// ── 2. Outstanding balance ────────────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT COALESCE(SUM(balance), 0)
     FROM invoices
     WHERE company_id = ? AND resident_id = ? AND status IN (\'issued\',\'partial\',\'overdue\')'
);
$stmt->execute([$companyId, $residentId]);
$outstandingBalance = (float)$stmt->fetchColumn();

// ── 3. Next due invoice ───────────────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT * FROM invoices
     WHERE company_id = ? AND resident_id = ? AND status IN (\'issued\',\'partial\',\'overdue\')
     ORDER BY due_date ASC
     LIMIT 1'
);
$stmt->execute([$companyId, $residentId]);
$nextInvoice = $stmt->fetch() ?: null;

// ── 4. Recent payments (last 3) ───────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT * FROM payments
     WHERE company_id = ? AND resident_id = ?
     ORDER BY payment_date DESC
     LIMIT 3'
);
$stmt->execute([$companyId, $residentId]);
$recentPayments = $stmt->fetchAll();

// ── 5. Open maintenance tickets ───────────────────────────────────────────────
$openTicketCount = 0;
if ($tenancy) {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM maintenance_tickets
         WHERE company_id = ? AND room_id = ? AND status IN (\'open\',\'in_progress\')'
    );
    $stmt->execute([$companyId, (int)$tenancy['room_id']]);
    $openTicketCount = (int)$stmt->fetchColumn();
}

// ── Days until tenancy expiry ─────────────────────────────────────────────────
$daysUntilExpiry  = null;
$expiryColorClass = 'text-success';
if ($tenancy && !empty($tenancy['end_date'])) {
    $today      = new DateTimeImmutable('today');
    $endDate    = new DateTimeImmutable($tenancy['end_date']);
    $diff       = (int)$today->diff($endDate)->days;
    $isPast     = $endDate < $today;
    $daysUntilExpiry = $isPast ? -$diff : $diff;
    if ($isPast || $daysUntilExpiry <= 30) {
        $expiryColorClass = 'text-danger';
    } elseif ($daysUntilExpiry <= 90) {
        $expiryColorClass = 'text-warning';
    }
}

// ── Next due days ─────────────────────────────────────────────────────────────
$dueDaysLabel = '';
if ($nextInvoice && !empty($nextInvoice['due_date'])) {
    $today   = new DateTimeImmutable('today');
    $dueDate = new DateTimeImmutable($nextInvoice['due_date']);
    $diff    = (int)$today->diff($dueDate)->days;
    $isPast  = $dueDate < $today;
    if ($isPast) {
        $dueDaysLabel = $diff . ' day' . ($diff === 1 ? '' : 's') . ' overdue';
    } elseif ($diff === 0) {
        $dueDaysLabel = 'Due today';
    } else {
        $dueDaysLabel = 'Due in ' . $diff . ' day' . ($diff === 1 ? '' : 's');
    }
}

// ── Payment method label ──────────────────────────────────────────────────────
function pmtMethodLabel(string $method): string {
    $map = [
        'bank_transfer' => 'Bank Transfer',
        'cash'          => 'Cash',
        'card'          => 'Card',
        'fpx'           => 'FPX',
        'ewallet'       => 'e-Wallet',
        'cheque'        => 'Cheque',
        'online'        => 'Online',
    ];
    return $map[$method] ?? ucfirst(str_replace('_', ' ', $method));
}

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/layout.php';
?>

<style>
.welcome-banner {
    background: linear-gradient(135deg, var(--brand) 0%, color-mix(in srgb, var(--brand) 70%, #1e40af) 100%);
    border-radius: 16px;
    padding: 1.4rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.welcome-banner::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.welcome-banner::before {
    content: '';
    position: absolute;
    right: 40px; bottom: -40px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.welcome-name  { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
.welcome-date  { font-size: .78rem; opacity: .8; }

.kpi-card { box-shadow: 0 1px 6px rgba(0,0,0,.06); }
.kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
    margin-bottom: .75rem;
}

.tenancy-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    overflow: hidden;
}
.tenancy-card-header {
    background: var(--brand-dim, #f5f3ff);
    border-bottom: 1px solid #e2e8f0;
    padding: .75rem 1.25rem;
    display: flex; align-items: center; gap: .5rem;
}
.tenancy-card-header .tc-title {
    font-size: .78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--brand);
}
.tenancy-body { padding: 1.1rem 1.25rem; }
.tenancy-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
}
@media (min-width: 576px) {
    .tenancy-meta-grid { grid-template-columns: repeat(3, 1fr); }
}
.t-meta-lbl { font-size: .68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .1rem; }
.t-meta-val { font-size: .88rem; font-weight: 700; color: #0f172a; }

.section-heading {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8; margin-bottom: .65rem;
}

.pmt-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .65rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.pmt-item:last-child { border-bottom: none; }
.pmt-icon-wrap {
    width: 36px; height: 36px; border-radius: 10px;
    background: #f0fdf4; color: #16a34a;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.pmt-desc  { font-size: .84rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
.pmt-sub   { font-size: .72rem; color: #94a3b8; }
.pmt-amt   { font-size: .9rem; font-weight: 700; color: #16a34a; margin-left: auto; white-space: nowrap; }

.maint-cta {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: .85rem;
    text-decoration: none; color: inherit;
    transition: box-shadow .15s, border-color .15s;
}
.maint-cta:hover { box-shadow: 0 4px 14px rgba(0,0,0,.1); border-color: var(--brand); color: inherit; }
.maint-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: #fef9c3; color: #a16207;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.maint-icon.alert-red { background: #fee2e2; color: #b91c1c; }
.maint-label { font-size: .88rem; font-weight: 700; color: #1e293b; }
.maint-sub   { font-size: .74rem; color: #64748b; }
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-name">
        Welcome back, <?= e($_SESSION['resident_name'] ?? 'Resident') ?>!
    </div>
    <div class="welcome-date">
        <?= e(date('l, j F Y')) ?>
    </div>
</div>

<!-- KPI Row: Outstanding Balance | Next Due -->
<div class="row g-3 mb-3">

    <!-- Outstanding Balance -->
    <div class="col-6">
        <div class="kpi-card card-box h-100">
            <div class="kpi-icon" style="background:#fef2f2; color:#b91c1c;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="kpi-lbl">Outstanding</div>
            <div class="kpi-val" style="font-size:1.35rem;">
                <?= $outstandingBalance > 0 ? money($outstandingBalance) : '<span style="color:#16a34a;">All clear</span>' ?>
            </div>
            <?php if ($outstandingBalance > 0): ?>
            <div class="kpi-sub" style="color:#b91c1c;">
                Unpaid balance
            </div>
            <?php else: ?>
            <div class="kpi-sub">No outstanding invoices</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Next Due -->
    <div class="col-6">
        <div class="kpi-card card-box h-100">
            <div class="kpi-icon" style="background:#eff6ff; color:#1d4ed8;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="kpi-lbl">Next Due</div>
            <?php if ($nextInvoice): ?>
                <div class="kpi-val" style="font-size:1.35rem;">
                    <?= money((float)$nextInvoice['balance']) ?>
                </div>
                <div class="kpi-sub" style="color:<?= (str_contains($dueDaysLabel, 'overdue') || $dueDaysLabel === 'Due today') ? '#b91c1c' : '#64748b' ?>;">
                    <?= e($dueDaysLabel) ?>
                </div>
            <?php else: ?>
                <div class="kpi-val" style="font-size:1.1rem; color:#16a34a;">None</div>
                <div class="kpi-sub">No pending invoices</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Active Tenancy Card -->
<?php if ($tenancy): ?>
<div class="tenancy-card mb-3">
    <div class="tenancy-card-header">
        <i class="bi bi-door-open-fill" style="color:var(--brand); font-size:.95rem;"></i>
        <span class="tc-title">Your Room</span>
        <span class="ms-auto">
            <span class="s-badge badge-open" style="font-size:.65rem;">Active</span>
        </span>
    </div>
    <div class="tenancy-body">
        <div class="tenancy-meta-grid">
            <div>
                <div class="t-meta-lbl">Building</div>
                <div class="t-meta-val"><?= e($tenancy['building_name']) ?></div>
            </div>
            <div>
                <div class="t-meta-lbl">Unit / Room</div>
                <div class="t-meta-val"><?= e($tenancy['unit_no']) ?> &mdash; <?= e($tenancy['room_no']) ?></div>
            </div>
            <div>
                <div class="t-meta-lbl">Room Type</div>
                <div class="t-meta-val"><?= e(ucfirst((string)$tenancy['room_type'])) ?></div>
            </div>
            <div>
                <div class="t-meta-lbl">Monthly Rent</div>
                <div class="t-meta-val"><?= money((float)$tenancy['monthly_rent']) ?></div>
            </div>
            <div>
                <div class="t-meta-lbl">Move-in</div>
                <div class="t-meta-val"><?= dateDisplay($tenancy['start_date']) ?></div>
            </div>
            <div>
                <div class="t-meta-lbl">Lease End</div>
                <div class="t-meta-val <?= e($expiryColorClass) ?>">
                    <?php if (!empty($tenancy['end_date'])): ?>
                        <?= dateDisplay($tenancy['end_date']) ?>
                        <?php if ($daysUntilExpiry !== null): ?>
                            <div class="<?= e($expiryColorClass) ?>" style="font-size:.68rem; font-weight:600; margin-top:.1rem;">
                                <?php if ($daysUntilExpiry < 0): ?>
                                    Expired <?= abs((int)$daysUntilExpiry) ?> day<?= abs((int)$daysUntilExpiry) === 1 ? '' : 's' ?> ago
                                <?php elseif ($daysUntilExpiry === 0): ?>
                                    Expires today
                                <?php else: ?>
                                    <?= (int)$daysUntilExpiry ?> day<?= (int)$daysUntilExpiry === 1 ? '' : 's' ?> remaining
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Open-ended</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- No active tenancy notice -->
<div class="card-box mb-3 text-center py-4" style="border-style:dashed; background:#fafafa;">
    <i class="bi bi-house-slash" style="font-size:2rem; color:#cbd5e1;"></i>
    <div class="mt-2" style="font-size:.88rem; color:#64748b; font-weight:600;">No active tenancy found</div>
    <div style="font-size:.78rem; color:#94a3b8; margin-top:.25rem;">Please contact your property manager if this is unexpected.</div>
</div>
<?php endif; ?>

<!-- Recent Payments -->
<div class="card-box mb-3" style="box-shadow:0 1px 6px rgba(0,0,0,.06);">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="section-heading mb-0">Recent Payments</div>
        <a href="<?= e(APP_URL) ?>/portal/resident/invoices.php"
           class="text-decoration-none" style="font-size:.75rem; color:var(--brand); font-weight:700;">
            View all &rsaquo;
        </a>
    </div>

    <?php if (empty($recentPayments)): ?>
        <div class="text-center py-3">
            <i class="bi bi-receipt" style="font-size:1.8rem; color:#cbd5e1;"></i>
            <div style="font-size:.82rem; color:#94a3b8; margin-top:.4rem;">No payments on record yet.</div>
        </div>
    <?php else: ?>
        <?php foreach ($recentPayments as $pmt): ?>
        <div class="pmt-item">
            <div class="pmt-icon-wrap">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="pmt-desc"><?= e(pmtMethodLabel((string)($pmt['payment_method'] ?? ''))) ?></div>
                <div class="pmt-sub"><?= dateDisplay($pmt['payment_date']) ?><?= !empty($pmt['gateway_ref']) ? ' &middot; Ref: ' . e($pmt['gateway_ref']) : '' ?></div>
            </div>
            <div class="pmt-amt">+<?= money((float)$pmt['amount']) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Maintenance CTA -->
<?php if ($tenancy): ?>
<a href="<?= e(APP_URL) ?>/portal/resident/maintenance.php" class="maint-cta mb-1">
    <div class="maint-icon<?= $openTicketCount > 0 ? ' alert-red' : '' ?>">
        <i class="bi bi-tools"></i>
    </div>
    <div>
        <div class="maint-label">Maintenance Requests</div>
        <div class="maint-sub">
            <?php if ($openTicketCount > 0): ?>
                <span class="fw-bold" style="color:#b91c1c;">
                    <?= $openTicketCount ?> open ticket<?= $openTicketCount === 1 ? '' : 's' ?> for your room
                </span>
            <?php else: ?>
                No open tickets &mdash; raise a new request
            <?php endif; ?>
        </div>
    </div>
    <i class="bi bi-chevron-right ms-auto" style="color:#cbd5e1; font-size:.85rem;"></i>
</a>
<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
