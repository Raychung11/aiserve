<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

registerDebugShutdown();
requireOwnerLogin();

$db        = getDB();
$ownerId   = (int)$_SESSION['owner_id'];
$companyId = (int)$_SESSION['owner_company_id'];

// 1. Units owned with building name and room count
$stmtUnits = $db->prepare(
    'SELECT u.*, b.name AS building_name,
            COUNT(r.id) AS room_count
     FROM units u
     JOIN buildings b ON b.id = u.building_id
     LEFT JOIN rooms r ON r.unit_id = u.id AND r.is_active = 1
     WHERE u.company_id = ? AND u.owner_id = ? AND u.is_active = 1
     GROUP BY u.id
     ORDER BY b.name, u.unit_no'
);
$stmtUnits->execute([$companyId, $ownerId]);
$units = $stmtUnits->fetchAll();

// 2. Occupied rooms count
$stmtOcc = $db->prepare(
    'SELECT COUNT(DISTINCT t.room_id)
     FROM tenancies t
     JOIN rooms r ON r.id = t.room_id
     JOIN units u ON u.id = r.unit_id
     WHERE u.company_id = ? AND u.owner_id = ? AND t.status = \'active\''
);
$stmtOcc->execute([$companyId, $ownerId]);
$occupiedCount = (int)$stmtOcc->fetchColumn();

// 3. Last payout (paid)
$stmtLastPayout = $db->prepare(
    'SELECT * FROM owner_payouts
     WHERE company_id = ? AND owner_id = ? AND status = \'paid\'
     ORDER BY paid_date DESC LIMIT 1'
);
$stmtLastPayout->execute([$companyId, $ownerId]);
$lastPayout = $stmtLastPayout->fetch() ?: [];

// 4. Pending payouts total (draft + approved)
$stmtPending = $db->prepare(
    'SELECT COALESCE(SUM(net_payout), 0)
     FROM owner_payouts
     WHERE company_id = ? AND owner_id = ? AND status IN (\'draft\', \'approved\')'
);
$stmtPending->execute([$companyId, $ownerId]);
$pendingTotal = (float)$stmtPending->fetchColumn();

// 5. YTD net income (current year)
$currentYear = date('Y');
$stmtYtd = $db->prepare(
    'SELECT COALESCE(SUM(net_payout), 0)
     FROM owner_payouts
     WHERE company_id = ? AND owner_id = ? AND status = \'paid\' AND period LIKE ?'
);
$stmtYtd->execute([$companyId, $ownerId, $currentYear . '-%']);
$ytdIncome = (float)$stmtYtd->fetchColumn();

// Total rooms across all units (for occupancy denominator)
$totalRooms = 0;
foreach ($units as $u) {
    $totalRooms += (int)$u['room_count'];
}

$unitCount   = count($units);
$todayLabel  = date('l, d F Y');

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/layout.php';
?>

<style>
.welcome-banner {
    background: linear-gradient(135deg, var(--brand) 0%, var(--brand) 70%, rgba(255,255,255,.08) 100%);
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.welcome-banner::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.welcome-name {
    font-size: 1.3rem;
    font-weight: 800;
    margin-bottom: .2rem;
}
.welcome-date {
    font-size: .8rem;
    color: rgba(255,255,255,.72);
}

.unit-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #ede9fb;
    overflow: hidden;
    transition: box-shadow .15s;
}
.unit-card:hover {
    box-shadow: 0 4px 20px rgba(147,51,234,.1);
}
.unit-card-header {
    padding: 1rem 1.25rem .75rem;
    border-bottom: 1px solid #f5f3ff;
}
.unit-bldg {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94a3b8;
    font-weight: 600;
    margin-bottom: .2rem;
}
.unit-no {
    font-size: 1rem;
    font-weight: 800;
    color: #1a1035;
}
.unit-card-body {
    padding: .75rem 1.25rem 1rem;
}
.unit-stat-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .4rem;
    font-size: .82rem;
    color: #64748b;
}
.unit-stat-row:last-child { margin-bottom: 0; }
.unit-stat-val {
    font-weight: 700;
    color: #1a1035;
}
.occ-bar-wrap {
    height: 5px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
    margin-top: .6rem;
}
.occ-bar-fill {
    height: 100%;
    border-radius: 3px;
    background: var(--brand);
    transition: width .4s;
}

.payout-last-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #ede9fb;
    padding: 1.25rem 1.4rem;
}
.section-heading {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    margin-bottom: .85rem;
}
.cta-link {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--brand);
    text-decoration: none;
    padding: .45rem .85rem;
    border-radius: 8px;
    border: 1.5px solid var(--brand);
    transition: background .13s, color .13s;
}
.cta-link:hover {
    background: var(--brand);
    color: #fff;
}
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-name">Welcome, <?= e($_SESSION['owner_name'] ?? 'Owner') ?></div>
    <div class="welcome-date"><i class="bi bi-calendar3 me-1"></i><?= e($todayLabel) ?></div>
</div>

<!-- KPI Row 1: Units + Occupied Rooms -->
<div class="row g-3 mb-3">
    <div class="col-6">
        <div class="kpi-card h-100">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="kpi-lbl">My Units</div>
                    <div class="kpi-val"><?= $unitCount ?></div>
                    <div class="kpi-sub">Active unit<?= $unitCount !== 1 ? 's' : '' ?></div>
                </div>
                <div class="kpi-ico">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="kpi-card h-100">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="kpi-lbl">Occupied</div>
                    <div class="kpi-val"><?= $occupiedCount ?></div>
                    <div class="kpi-sub">of <?= $totalRooms ?> room<?= $totalRooms !== 1 ? 's' : '' ?></div>
                </div>
                <div class="kpi-ico">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPI Row 2: YTD Income + Pending Payout -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="kpi-card h-100">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="kpi-lbl">YTD Income</div>
                    <div class="kpi-val" style="font-size:1.2rem;"><?= e(money($ytdIncome)) ?></div>
                    <div class="kpi-sub"><?= e($currentYear) ?> paid</div>
                </div>
                <div class="kpi-ico">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="kpi-card h-100">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="kpi-lbl">Pending</div>
                    <div class="kpi-val" style="font-size:1.2rem;"><?= e(money($pendingTotal)) ?></div>
                    <div class="kpi-sub">Awaiting payment</div>
                </div>
                <div class="kpi-ico">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Units Overview -->
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="section-heading mb-0">Units Overview</div>
        <a href="<?= e(APP_URL) ?>/portal/owner/units.php" class="cta-link" style="font-size:.75rem;padding:.3rem .65rem;">
            <i class="bi bi-list-ul"></i>View All
        </a>
    </div>

    <?php if (empty($units)): ?>
    <div class="card-box text-center py-4">
        <i class="bi bi-building text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-2 mb-0" style="font-size:.88rem;">No units assigned to your account yet.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($units as $unit):
            $roomCount  = (int)$unit['room_count'];
            // Per-unit occupied rooms sub-query
            $stmtUnitOcc = $db->prepare(
                'SELECT COUNT(DISTINCT t.room_id)
                 FROM tenancies t
                 JOIN rooms r ON r.id = t.room_id
                 WHERE r.unit_id = ? AND r.company_id = ? AND t.status = \'active\''
            );
            $stmtUnitOcc->execute([(int)$unit['id'], $companyId]);
            $unitOccupied = (int)$stmtUnitOcc->fetchColumn();
            $occPct = $roomCount > 0 ? round($unitOccupied / $roomCount * 100) : 0;
        ?>
        <div class="col-12 col-md-6">
            <div class="unit-card">
                <div class="unit-card-header">
                    <div class="unit-bldg"><i class="bi bi-geo-alt me-1"></i><?= e($unit['building_name']) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="unit-no">Unit <?= e($unit['unit_no']) ?></div>
                        <span class="s-badge <?= $unitOccupied >= $roomCount && $roomCount > 0 ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $unitOccupied ?>/<?= $roomCount ?> Occupied
                        </span>
                    </div>
                    <?php if ($roomCount > 0): ?>
                    <div class="occ-bar-wrap mt-2">
                        <div class="occ-bar-fill" style="width:<?= $occPct ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="unit-card-body">
                    <div class="unit-stat-row">
                        <span><i class="bi bi-layers me-1"></i>Floor</span>
                        <span class="unit-stat-val"><?= $unit['floor'] !== null && $unit['floor'] !== '' ? e((string)$unit['floor']) : '&mdash;' ?></span>
                    </div>
                    <div class="unit-stat-row">
                        <span><i class="bi bi-door-closed me-1"></i>Total Rooms</span>
                        <span class="unit-stat-val"><?= (int)$unit['total_rooms'] ?></span>
                    </div>
                    <div class="unit-stat-row">
                        <span><i class="bi bi-cash me-1"></i>Master Rent</span>
                        <span class="unit-stat-val"><?= e(money((float)$unit['master_rent'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Last Payout Card + CTA -->
<div class="row g-3 mb-2">
    <div class="col-12 col-md-6">
        <div class="payout-last-card h-100">
            <div class="section-heading">Last Payout Received</div>
            <?php if (empty($lastPayout)): ?>
            <p class="text-muted mb-0" style="font-size:.85rem;">No paid payouts on record yet.</p>
            <?php else: ?>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-ico flex-shrink-0">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div style="font-size:1.35rem;font-weight:800;color:#1a1035;"><?= e(money((float)$lastPayout['net_payout'])) ?></div>
                    <div style="font-size:.78rem;color:#64748b;">Period: <strong><?= e($lastPayout['period']) ?></strong></div>
                </div>
            </div>
            <div style="font-size:.8rem;color:#64748b;">
                <i class="bi bi-calendar-check me-1"></i>Paid on <?= dateDisplay((string)$lastPayout['paid_date']) ?>
                <?php if (!empty($lastPayout['bank_ref'])): ?>
                &nbsp;&middot;&nbsp;<i class="bi bi-hash me-1"></i><?= e($lastPayout['bank_ref']) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card-box h-100 d-flex flex-column justify-content-center gap-3">
            <div class="section-heading mb-1">Quick Links</div>
            <a href="<?= e(APP_URL) ?>/portal/owner/payouts.php" class="cta-link">
                <i class="bi bi-wallet2"></i>View All Payouts
            </a>
            <a href="<?= e(APP_URL) ?>/portal/owner/units.php" class="cta-link">
                <i class="bi bi-building"></i>View My Units
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
