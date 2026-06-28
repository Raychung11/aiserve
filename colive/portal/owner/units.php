<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

registerDebugShutdown();
requireOwnerLogin();

$db        = getDB();
$ownerId   = (int)$_SESSION['owner_id'];
$companyId = (int)$_SESSION['owner_company_id'];

// Fetch all units for this owner
$stmtUnits = $db->prepare(
    'SELECT u.*, b.name AS building_name, b.address AS building_address,
            b.city, b.state
     FROM units u
     JOIN buildings b ON b.id = u.building_id
     WHERE u.company_id = ? AND u.owner_id = ?
     ORDER BY b.name, u.unit_no'
);
$stmtUnits->execute([$companyId, $ownerId]);
$units = $stmtUnits->fetchAll();

// For each unit, fetch its rooms + active tenancy info
$unitRooms = [];
foreach ($units as $unit) {
    $unitId = (int)$unit['id'];
    $stmtRooms = $db->prepare(
        'SELECT r.*,
                t.status AS tenancy_status,
                t.end_date,
                t.monthly_rent,
                res.name AS resident_name
         FROM rooms r
         LEFT JOIN tenancies t
               ON t.room_id = r.id
              AND t.status = \'active\'
              AND t.company_id = ?
         LEFT JOIN residents res ON res.id = t.resident_id
         WHERE r.unit_id = ? AND r.company_id = ? AND r.is_active = 1
         ORDER BY r.room_no'
    );
    $stmtRooms->execute([$companyId, $unitId, $companyId]);
    $unitRooms[$unitId] = $stmtRooms->fetchAll();
}

$unitCount = count($units);

$pageTitle  = 'My Units';
$activePage = 'units';
include __DIR__ . '/layout.php';
?>

<style>
.unit-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #ede9fb;
    overflow: hidden;
    transition: box-shadow .15s;
}
.unit-card:hover {
    box-shadow: 0 4px 24px rgba(147,51,234,.1);
}
.unit-card-header {
    background: var(--brand);
    padding: 1rem 1.25rem;
    color: #fff;
    position: relative;
}
.unit-card-header::after {
    content: '';
    position: absolute;
    right: -20px; top: -20px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    pointer-events: none;
}
.unit-bldg-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.7);
    font-weight: 600;
    margin-bottom: .2rem;
}
.unit-no-label {
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
}
.unit-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: .6rem;
    font-size: .73rem;
    color: rgba(255,255,255,.8);
}
.unit-meta-row span {
    display: flex;
    align-items: center;
    gap: .25rem;
}
.occ-fraction {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .65rem;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    background: rgba(255,255,255,.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}

.room-list {
    padding: .75rem 0 .25rem;
}
.room-row {
    display: flex;
    align-items: center;
    padding: .6rem 1.25rem;
    border-bottom: 1px solid #faf8ff;
    gap: .75rem;
    font-size: .83rem;
}
.room-row:last-child {
    border-bottom: none;
}
.room-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.room-dot.occupied { background: var(--brand); }
.room-dot.vacant   { background: #cbd5e1; }
.room-no {
    font-weight: 700;
    color: #1a1035;
    min-width: 3.5rem;
    flex-shrink: 0;
}
.room-type {
    font-size: .72rem;
    color: #94a3b8;
    min-width: 4rem;
}
.room-resident {
    flex: 1;
    color: #334155;
    font-size: .82rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.room-status-badge {
    flex-shrink: 0;
    font-size: .68rem;
    font-weight: 700;
    padding: .18rem .5rem;
    border-radius: 10px;
}
.room-status-occupied {
    background: #ede9fb;
    color: var(--brand);
}
.room-status-vacant {
    background: #f1f5f9;
    color: #64748b;
}
.room-rent {
    font-size: .78rem;
    font-weight: 700;
    color: #1a1035;
    flex-shrink: 0;
    min-width: 5rem;
    text-align: right;
}
.room-end-date {
    font-size: .7rem;
    color: #94a3b8;
    flex-shrink: 0;
}

.no-rooms-row {
    padding: 1rem 1.25rem;
    font-size: .83rem;
    color: #94a3b8;
    text-align: center;
}

.section-heading {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    padding: .6rem 1.25rem .2rem;
    border-top: 1px solid #f5f3ff;
}

.unit-addr {
    font-size: .75rem;
    color: #94a3b8;
    padding: .45rem 1.25rem .5rem;
    border-top: 1px solid #f5f3ff;
}

.occ-bar-wrap {
    height: 4px;
    background: rgba(255,255,255,.2);
    border-radius: 3px;
    overflow: hidden;
    margin-top: .5rem;
}
.occ-bar-fill {
    height: 100%;
    border-radius: 3px;
    background: rgba(255,255,255,.85);
    transition: width .4s;
}

.empty-state {
    text-align: center;
    padding: 3.5rem 1rem;
}
.empty-state i {
    font-size: 2.8rem;
    color: #cbd5e1;
    display: block;
    margin-bottom: .75rem;
}
.empty-state p {
    color: #94a3b8;
    font-size: .9rem;
    margin: 0;
}
</style>

<!-- Page Heading -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0" style="color:#1a1035;font-size:1.05rem;">
        My Units
        <?php if ($unitCount > 0): ?>
        <span class="badge rounded-pill ms-1" style="background:var(--brand);font-size:.7rem;"><?= $unitCount ?></span>
        <?php endif; ?>
    </h5>
</div>

<?php if (empty($units)): ?>
<!-- Empty state -->
<div class="card-box empty-state">
    <i class="bi bi-building"></i>
    <p>No units are assigned to your account yet.</p>
    <p style="font-size:.8rem;margin-top:.4rem;color:#b0bec5;">Contact your property manager if you believe this is an error.</p>
</div>

<?php else: ?>

<div class="row g-3">
    <?php foreach ($units as $unit):
        $unitId    = (int)$unit['id'];
        $rooms     = $unitRooms[$unitId] ?? [];
        $roomCount = count($rooms);

        $occupiedCount = 0;
        foreach ($rooms as $r) {
            if ($r['tenancy_status'] === 'active') {
                $occupiedCount++;
            }
        }

        $occPct = $roomCount > 0 ? round($occupiedCount / $roomCount * 100) : 0;

        // Column width: 2-up on md+, unless only 1 unit (full width)
        $colClass = $unitCount === 1 ? 'col-12' : 'col-12 col-md-6';
    ?>
    <div class="<?= $colClass ?>">
        <div class="unit-card h-100">

            <!-- Unit header (brand color bg) -->
            <div class="unit-card-header">
                <div class="unit-bldg-label">
                    <i class="bi bi-geo-alt me-1"></i><?= e($unit['building_name']) ?>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="unit-no-label">Unit <?= e($unit['unit_no']) ?></div>
                    <div class="occ-fraction">
                        <i class="bi bi-person-fill" style="font-size:.75rem;"></i>
                        <?= $occupiedCount ?>/<?= $roomCount ?>
                    </div>
                </div>
                <div class="unit-meta-row">
                    <?php if ($unit['floor'] !== null && $unit['floor'] !== ''): ?>
                    <span><i class="bi bi-layers"></i>Floor <?= e((string)$unit['floor']) ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-door-closed"></i><?= (int)$unit['total_rooms'] ?> room<?= (int)$unit['total_rooms'] !== 1 ? 's' : '' ?></span>
                    <span><i class="bi bi-cash"></i><?= e(money((float)$unit['master_rent'])) ?>/mo</span>
                </div>
                <?php if ($roomCount > 0): ?>
                <div class="occ-bar-wrap">
                    <div class="occ-bar-fill" style="width:<?= $occPct ?>%;"></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Address line -->
            <?php if (!empty($unit['building_address']) || !empty($unit['city'])): ?>
            <div class="unit-addr">
                <i class="bi bi-pin-map me-1"></i>
                <?php
                $addrParts = array_filter([
                    $unit['building_address'] ?? '',
                    $unit['city'] ?? '',
                    $unit['state'] ?? '',
                ]);
                echo e(implode(', ', $addrParts));
                ?>
            </div>
            <?php endif; ?>

            <!-- Rooms section heading -->
            <div class="section-heading">
                Rooms
                <span style="font-weight:400;color:#b0bec5;text-transform:none;letter-spacing:0;">
                    &mdash; <?= $occupiedCount ?> occupied, <?= $roomCount - $occupiedCount ?> vacant
                </span>
            </div>

            <!-- Room list -->
            <div class="room-list">
                <?php if (empty($rooms)): ?>
                <div class="no-rooms-row">No rooms on record for this unit.</div>
                <?php else: ?>
                <?php foreach ($rooms as $room):
                    $isOccupied  = $room['tenancy_status'] === 'active';
                    $dotClass    = $isOccupied ? 'occupied' : 'vacant';
                    $statusClass = $isOccupied ? 'room-status-occupied' : 'room-status-vacant';
                    $statusLabel = $isOccupied ? 'Occupied' : 'Vacant';
                ?>
                <div class="room-row">
                    <div class="room-dot <?= $dotClass ?>"></div>
                    <div class="room-no">Rm <?= e($room['room_no']) ?></div>
                    <div class="room-type"><?= e(ucfirst((string)($room['room_type'] ?? ''))) ?></div>

                    <?php if ($isOccupied && !empty($room['resident_name'])): ?>
                    <div class="room-resident" title="<?= e($room['resident_name']) ?>">
                        <i class="bi bi-person me-1" style="font-size:.8rem;color:#94a3b8;"></i><?= e($room['resident_name']) ?>
                    </div>
                    <?php else: ?>
                    <div class="room-resident" style="color:#cbd5e1;">&mdash;</div>
                    <?php endif; ?>

                    <?php if ($isOccupied && !empty($room['end_date'])): ?>
                    <div class="room-end-date" title="Tenancy ends">
                        <i class="bi bi-calendar-event me-1"></i><?= dateDisplay((string)$room['end_date']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($isOccupied && !empty($room['monthly_rent'])): ?>
                    <div class="room-rent"><?= e(money((float)$room['monthly_rent'])) ?></div>
                    <?php elseif (!empty($room['base_rent'])): ?>
                    <div class="room-rent" style="color:#94a3b8;"><?= e(money((float)$room['base_rent'])) ?></div>
                    <?php else: ?>
                    <div class="room-rent">&mdash;</div>
                    <?php endif; ?>

                    <span class="room-status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /unit-card -->
    </div><!-- /col -->
    <?php endforeach; ?>
</div><!-- /row -->

<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
