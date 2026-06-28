<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
startSecureSession();

$db = getDB();

// ── Handle inquiry POST ───────────────────────────────────────────────────────
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $roomId   = (int)($_POST['room_id']        ?? 0);
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $phone    = trim($_POST['phone']            ?? '');
    $moveIn   = trim($_POST['move_in_date']     ?? '');
    $duration = max(1, (int)($_POST['duration_months'] ?? 1));
    $notes    = trim($_POST['notes']            ?? '');

    $err = '';
    if (!$roomId)  $err = 'Please select a room.';
    elseif (!$name)  $err = 'Name is required.';
    elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Valid email is required.';
    elseif (!$moveIn || strtotime($moveIn) === false) $err = 'Move-in date is required.';

    if (!$err) {
        // Verify room is still available
        $rStmt = $db->prepare(
            'SELECT r.*, c.id AS cid FROM rooms r
             JOIN companies c ON c.id=r.company_id
             LEFT JOIN tenancies t ON t.room_id=r.id AND t.status=\'active\'
             WHERE r.id=? AND r.is_active=1 AND t.id IS NULL'
        );
        $rStmt->execute([$roomId]);
        $room = $rStmt->fetch();

        if (!$room) {
            $err = 'Sorry, that room is no longer available. Please choose another.';
        } else {
            $db->prepare(
                'INSERT INTO bookings
                 (company_id,room_id,name,email,phone,move_in_date,duration_months,quoted_rent,notes,status)
                 VALUES (?,?,?,?,?,?,?,?,?,\'pending\')'
            )->execute([
                $room['cid'], $roomId, $name, $email, $phone,
                $moveIn, $duration, (float)$room['base_rent'], $notes
            ]);
            $successMsg = 'Your inquiry has been submitted! The property manager will contact you within 24 hours.';
        }
    }
    if ($err) $errorMsg = $err;
}

// ── Filters ───────────────────────────────────────────────────────────────────
$filterType  = $_GET['type']  ?? '';
$filterMax   = (int)($_GET['max'] ?? 0);
$filterMin   = (int)($_GET['min'] ?? 0);

$sql = '
    SELECT r.*, u.unit_no, b.name AS building_name, b.address AS building_address,
           c.name AS company_name, c.brand_color,
           (SELECT COUNT(*) FROM beds bd WHERE bd.room_id=r.id AND bd.is_active=1) AS bed_count
    FROM rooms r
    JOIN units u ON u.id=r.unit_id
    JOIN buildings b ON b.id=u.building_id
    JOIN companies c ON c.id=r.company_id AND c.status IN (\'trial\',\'active\')
    LEFT JOIN tenancies t ON t.room_id=r.id AND t.status=\'active\'
    WHERE r.is_active=1 AND t.id IS NULL
';
$params = [];
if ($filterType) { $sql .= ' AND r.room_type=?'; $params[] = $filterType; }
if ($filterMin)  { $sql .= ' AND r.base_rent >= ?'; $params[] = $filterMin; }
if ($filterMax)  { $sql .= ' AND r.base_rent <= ?'; $params[] = $filterMax; }
$sql .= ' ORDER BY c.name, b.name, u.unit_no, r.room_no';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Group by building for display
$byBuilding = [];
foreach ($rooms as $rm) {
    $key = $rm['company_name'] . '||' . $rm['building_name'];
    $byBuilding[$key][] = $rm;
}

// ── Type options for filter ───────────────────────────────────────────────────
$typeLabels = [
    'single' => 'Single', 'twin' => 'Twin', 'master' => 'Master',
    'studio' => 'Studio', 'common' => 'Common Room',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Available Rooms &mdash; CoLive OS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --brand:#7c3aed; }
body { font-family:'Segoe UI',system-ui,sans-serif; background:#f8fafc; color:#0f172a; }

/* NAV */
.top-nav { background:#fff;border-bottom:1px solid #f1f5f9;padding:.85rem 0;position:sticky;top:0;z-index:100; }
.nav-inner { max-width:1100px;margin:auto;padding:0 1.25rem;display:flex;align-items:center;justify-content:space-between; }
.nav-logo { font-weight:800;font-size:1.05rem;color:#0f172a;text-decoration:none;display:flex;align-items:center;gap:.4rem; }
.nav-logo i { color:var(--brand); }

/* HERO */
.listings-hero { background:linear-gradient(135deg,#7c3aed 0%,#5b21b6 100%);padding:3.5rem 1.25rem 2.5rem;text-align:center;color:#fff; }
.listings-hero h1 { font-size:clamp(1.6rem,4vw,2.5rem);font-weight:800;margin-bottom:.5rem; }
.listings-hero p { color:#e9d5ff;font-size:.95rem;margin-bottom:0; }

/* FILTER BAR */
.filter-bar { background:#fff;border-bottom:1px solid #f1f5f9;padding:1rem 1.25rem;position:sticky;top:65px;z-index:90; }
.filter-inner { max-width:1100px;margin:auto; }

/* ROOM CARD */
.room-card { background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;
             transition:box-shadow .2s,transform .2s;height:100%; }
.room-card:hover { box-shadow:0 8px 32px rgba(124,58,237,.13);transform:translateY(-3px); }
.room-card-img { height:160px;background:linear-gradient(135deg,#ede9fe 0%,#ddd6fe 100%);
                 display:flex;align-items:center;justify-content:center;position:relative; }
.room-type-badge { position:absolute;top:.75rem;left:.75rem;background:var(--brand);color:#fff;
                   font-size:.7rem;font-weight:700;padding:.25rem .6rem;border-radius:20px;text-transform:capitalize; }
.room-card-body { padding:1.1rem; }
.room-rent { font-size:1.35rem;font-weight:800;color:var(--brand); }
.room-rent span { font-size:.75rem;font-weight:400;color:#94a3b8; }
.room-loc { font-size:.78rem;color:#64748b;margin-bottom:.6rem; }
.room-chips { display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1rem; }
.chip { background:#f1f5f9;color:#475569;font-size:.7rem;padding:.2rem .55rem;border-radius:20px; }
.chip.green { background:#f0fdf4;color:#166534; }
.chip.purple { background:#faf5ff;color:#6b21a8; }
.btn-inquire { background:var(--brand);color:#fff;border:none;border-radius:8px;width:100%;
               padding:.55rem;font-weight:700;font-size:.875rem;cursor:pointer;transition:background .15s; }
.btn-inquire:hover { background:#5b21b6; }

/* BUILDING SECTION */
.building-label { font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
                  color:#94a3b8;margin:2rem 0 1rem; }

/* EMPTY */
.empty-state { text-align:center;padding:5rem 1rem;color:#94a3b8; }
.empty-state i { font-size:3rem;margin-bottom:1rem;display:block; }

/* SUCCESS banner */
.success-banner { background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:12px;
                  padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem; }

@media(max-width:576px){
  .filter-bar { position:static; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="top-nav">
  <div class="nav-inner">
    <a href="/colive/" class="nav-logo"><i class="bi bi-buildings-fill"></i> CoLive OS</a>
    <div class="d-flex gap-2 align-items-center">
      <a href="/colive/portal/resident/login.php" style="font-size:.83rem;color:#64748b;text-decoration:none;">Resident Login</a>
      <a href="/colive/app/login.php" class="btn btn-sm" style="background:var(--brand);color:#fff;border-radius:8px;font-size:.83rem;font-weight:600;">Operator Login</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="listings-hero">
  <h1>Find Your Next Room</h1>
  <p><?= count($rooms) ?> room<?= count($rooms) !== 1 ? 's' : '' ?> available now &mdash; browse and inquire instantly</p>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
  <div class="filter-inner">
    <form method="GET" action="listings.php" class="d-flex flex-wrap align-items-center gap-2">
      <select name="type" class="form-select form-select-sm" style="width:auto;min-width:130px;" onchange="this.form.submit()">
        <option value="">All types</option>
        <?php foreach ($typeLabels as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <select name="max" class="form-select form-select-sm" style="width:auto;min-width:150px;" onchange="this.form.submit()">
        <option value="">Any price</option>
        <?php foreach ([500,700,900,1200,1500,2000] as $p): ?>
        <option value="<?= $p ?>" <?= $filterMax === $p ? 'selected' : '' ?>>Up to RM <?= number_format($p) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filterType || $filterMax): ?>
      <a href="listings.php" class="btn btn-sm btn-outline-secondary">Clear</a>
      <?php endif; ?>
      <span class="ms-auto" style="font-size:.8rem;color:#94a3b8;"><?= count($rooms) ?> result<?= count($rooms) !== 1 ? 's' : '' ?></span>
    </form>
  </div>
</div>

<!-- MAIN -->
<div style="max-width:1100px;margin:auto;padding:1.5rem 1.25rem 4rem;">

  <?php if ($successMsg): ?>
  <div class="success-banner">
    <i class="bi bi-check-circle-fill" style="font-size:1.3rem;color:#22c55e;flex-shrink:0;"></i>
    <div>
      <strong>Inquiry sent!</strong> <?= htmlspecialchars($successMsg) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
  <div class="alert alert-danger border-0 rounded-3 mb-3"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <?php if (!$rooms): ?>
  <div class="empty-state">
    <i class="bi bi-house-slash"></i>
    <p style="font-size:1rem;font-weight:600;">No rooms match your filters right now.</p>
    <a href="listings.php" class="btn btn-sm" style="background:var(--brand);color:#fff;border-radius:8px;">Clear Filters</a>
  </div>
  <?php else: ?>

    <?php foreach ($byBuilding as $groupKey => $groupRooms):
      [$companyName, $buildingName] = explode('||', $groupKey, 2);
      $address = $groupRooms[0]['building_address'] ?? '';
    ?>
    <div class="building-label">
      <i class="bi bi-building me-1"></i>
      <?= htmlspecialchars($buildingName) ?>
      <?php if ($address): ?>&nbsp;&mdash; <?= htmlspecialchars($address) ?><?php endif; ?>
      <span style="color:#cbd5e1;">&nbsp;/&nbsp;<?= htmlspecialchars($companyName) ?></span>
    </div>
    <div class="row g-3 mb-2">
      <?php foreach ($groupRooms as $rm):
        $brand = !empty($rm['brand_color']) ? $rm['brand_color'] : '#7c3aed';
        $beds  = (int)$rm['bed_count'];
      ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="room-card">
          <div class="room-card-img" style="background:linear-gradient(135deg,<?= htmlspecialchars($brand) ?>22 0%,<?= htmlspecialchars($brand) ?>44 100%);">
            <i class="bi bi-door-open-fill" style="font-size:3rem;color:<?= htmlspecialchars($brand) ?>;opacity:.6;"></i>
            <span class="room-type-badge" style="background:<?= htmlspecialchars($brand) ?>;">
              <?= htmlspecialchars($typeLabels[$rm['room_type']] ?? ucfirst($rm['room_type'])) ?>
            </span>
          </div>
          <div class="room-card-body">
            <div class="room-loc">
              <i class="bi bi-geo-alt me-1"></i>
              <?= htmlspecialchars('Unit ' . $rm['unit_no'] . ' &mdash; Room ' . $rm['room_no']) ?>
            </div>
            <div class="room-rent">
              RM <?= number_format((float)$rm['base_rent'], 0) ?>
              <span>/ month</span>
            </div>
            <div class="room-chips mt-2">
              <?php if ($rm['capacity'] > 0): ?>
              <span class="chip"><i class="bi bi-person me-1"></i><?= $rm['capacity'] ?> pax</span>
              <?php endif; ?>
              <?php if ($beds > 0): ?>
              <span class="chip purple"><i class="bi bi-vinyl me-1"></i><?= $beds ?> bed<?= $beds > 1 ? 's' : '' ?></span>
              <?php endif; ?>
              <?php if ($rm['has_attached_bath']): ?>
              <span class="chip green"><i class="bi bi-droplet me-1"></i>En-suite</span>
              <?php endif; ?>
              <?php if ((int)$rm['deposit_months'] === 0): ?>
              <span class="chip green"><i class="bi bi-star me-1"></i>Zero deposit</span>
              <?php endif; ?>
            </div>
            <button type="button" class="btn-inquire"
                    onclick="openInquiry(<?= $rm['id'] ?>, '<?= htmlspecialchars(addslashes($buildingName . ' &mdash; Unit ' . $rm['unit_no'] . ' / Rm ' . $rm['room_no'])) ?>', <?= (float)$rm['base_rent'] ?>)"
                    >
              <i class="bi bi-send me-1"></i>Inquire Now
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

<!-- FOOTER -->
<div style="background:#0f172a;color:#64748b;text-align:center;padding:1.5rem;font-size:.78rem;">
  Powered by <strong style="color:#a78bfa;">CoLive OS</strong> &mdash; Smart Co-Living Management &nbsp;|&nbsp;
  <a href="/colive/" style="color:#a78bfa;">Platform Info</a>
</div>

<!-- INQUIRY MODAL -->
<div class="modal fade" id="inquiryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold" style="font-size:1rem;">Inquire About This Room</h5>
          <p id="modalRoomName" class="text-muted mb-0" style="font-size:.82rem;"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <form method="POST" action="listings.php" id="inquiryForm">
          <?= csrfField() ?>
          <input type="hidden" name="room_id" id="modalRoomId">

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.83rem;">Full Name *</label>
            <input type="text" name="name" class="form-control form-control-sm" required placeholder="Your full name">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label fw-semibold" style="font-size:.83rem;">Email *</label>
              <input type="email" name="email" class="form-control form-control-sm" required placeholder="you@example.com">
            </div>
            <div class="col-5">
              <label class="form-label fw-semibold" style="font-size:.83rem;">Phone</label>
              <input type="tel" name="phone" class="form-control form-control-sm" placeholder="01X-XXXXXXX">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label fw-semibold" style="font-size:.83rem;">Preferred Move-in *</label>
              <input type="date" name="move_in_date" class="form-control form-control-sm" required
                     min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-5">
              <label class="form-label fw-semibold" style="font-size:.83rem;">Duration</label>
              <select name="duration_months" class="form-select form-select-sm">
                <?php foreach ([1,3,6,12,24] as $mo): ?>
                <option value="<?= $mo ?>"><?= $mo ?> month<?= $mo > 1 ? 's' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.83rem;">Message (optional)</label>
            <textarea name="notes" class="form-control form-control-sm" rows="3"
                      placeholder="Any questions or special requirements..."></textarea>
          </div>

          <div id="modalRentInfo" style="background:#faf5ff;border-radius:8px;padding:.6rem .9rem;font-size:.8rem;color:#6b21a8;margin-bottom:1rem;">
            <i class="bi bi-info-circle me-1"></i> Quoted rent: <strong id="modalRentDisplay"></strong>/month
          </div>

          <button type="submit" class="btn w-100 fw-bold" style="background:var(--brand);color:#fff;border-radius:8px;">
            <i class="bi bi-send me-2"></i>Submit Inquiry
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const inquiryModal = new bootstrap.Modal(document.getElementById('inquiryModal'));

function openInquiry(roomId, roomLabel, rent) {
  document.getElementById('modalRoomId').value = roomId;
  document.getElementById('modalRoomName').textContent = roomLabel;
  document.getElementById('modalRentDisplay').textContent = 'RM ' + rent.toLocaleString('en-MY', {minimumFractionDigits:0});
  inquiryModal.show();
}

<?php if ($successMsg): ?>
document.addEventListener('DOMContentLoaded', () => {
  window.scrollTo({top:0, behavior:'smooth'});
});
<?php endif; ?>
</script>
</body>
</html>
