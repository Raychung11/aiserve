<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';
registerDebugShutdown();
requireResidentLogin();

$db  = getDB();
$rid = (int)$_SESSION['resident_id'];
$cid = (int)$_SESSION['resident_company_id'];
$residentName = $_SESSION['resident_name'] ?? 'Resident';

$CATEGORIES = ['plumbing','electrical','aircon','furniture','cleaning','internet','security','other'];
$PRIORITIES = ['low','medium','high','urgent'];

// ── Resolve active tenancy / room ─────────────────────────────────────────────
$tenStmt = $db->prepare(
    'SELECT t.room_id, r.room_no FROM tenancies t
     LEFT JOIN rooms r ON r.id = t.room_id
     WHERE t.company_id=? AND t.resident_id=? AND t.status=\'active\'
     LIMIT 1'
);
$tenStmt->execute([$cid, $rid]);
$tenancy = $tenStmt->fetch() ?: null;
$roomId  = $tenancy ? ((int)$tenancy['room_id'] ?: null) : null;
$roomNo  = $tenancy['room_no'] ?? null;

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'submit') {
        $title    = trim($_POST['title']       ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $cat      = $_POST['category']         ?? 'other';
        $pri      = $_POST['priority']         ?? 'medium';

        if ($title === '') {
            flashSet('danger', 'Please enter a title for your request.');
            header('Location: maintenance.php'); exit;
        }
        if ($desc === '') {
            flashSet('danger', 'Please describe the issue.');
            header('Location: maintenance.php'); exit;
        }
        if (!in_array($cat, $CATEGORIES, true)) $cat = 'other';
        if (!in_array($pri, $PRIORITIES,  true)) $pri = 'medium';

        $notes = 'Submitted via resident portal by ' . $residentName;

        $db->prepare(
            'INSERT INTO maintenance_tickets
             (company_id, room_id, title, description, category, priority, status, notes, created_at, updated_at)
             VALUES (?,?,?,?,?,?,\'open\',?,NOW(),NOW())'
        )->execute([$cid, $roomId, $title, $desc, $cat, $pri, $notes]);

        flashSet('success', 'Your maintenance request has been submitted. We will be in touch soon.');
        header('Location: maintenance.php'); exit;
    }
}

// ── Filter ────────────────────────────────────────────────────────────────────
$filterTab = $_GET['tab'] ?? 'active';
if (!in_array($filterTab, ['active','resolved','all'], true)) $filterTab = 'active';

// ── Fetch tickets ─────────────────────────────────────────────────────────────
// Show all tickets for this resident's room (plus any with their name in notes as fallback)
$tickets = [];
if ($roomId) {
    $sql    = 'SELECT * FROM maintenance_tickets WHERE company_id=? AND room_id=?';
    $params = [$cid, $roomId];

    if ($filterTab === 'active') {
        $sql .= " AND status IN ('open','in_progress')";
    } elseif ($filterTab === 'resolved') {
        $sql .= " AND status IN ('resolved','closed')";
    }
    $sql .= ' ORDER BY FIELD(priority,\'urgent\',\'high\',\'medium\',\'low\'), created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} elseif ($tenancy === null) {
    // No tenancy at all — skip query, show message below
} else {
    // Tenancy exists but no room_id — fall back to notes lookup
    $sql    = "SELECT * FROM maintenance_tickets WHERE company_id=? AND notes LIKE ?";
    $params = [$cid, '%portal by ' . $residentName . '%'];

    if ($filterTab === 'active') {
        $sql .= " AND status IN ('open','in_progress')";
    } elseif ($filterTab === 'resolved') {
        $sql .= " AND status IN ('resolved','closed')";
    }
    $sql .= ' ORDER BY FIELD(priority,\'urgent\',\'high\',\'medium\',\'low\'), created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
}

// ── Tab counts ────────────────────────────────────────────────────────────────
$countActive   = 0;
$countResolved = 0;
if ($roomId) {
    $cntStmt = $db->prepare(
        "SELECT
           SUM(status IN ('open','in_progress')) AS active_n,
           SUM(status IN ('resolved','closed'))  AS resolved_n
         FROM maintenance_tickets
         WHERE company_id=? AND room_id=?"
    );
    $cntStmt->execute([$cid, $roomId]);
    $cntRow        = $cntStmt->fetch();
    $countActive   = (int)($cntRow['active_n']   ?? 0);
    $countResolved = (int)($cntRow['resolved_n'] ?? 0);
}

// ── Priority / status display maps ───────────────────────────────────────────
$priStyle = [
    'urgent' => 'background:#fee2e2;color:#b91c1c;',
    'high'   => 'background:#fef3c7;color:#92400e;',
    'medium' => 'background:#fef9c3;color:#a16207;',
    'low'    => 'background:#f1f5f9;color:#64748b;',
];
$priLabel = [
    'urgent' => 'Urgent',
    'high'   => 'High',
    'medium' => 'Medium',
    'low'    => 'Low',
];
$statStyle = [
    'open'        => 'background:#dbeafe;color:#1d4ed8;',
    'in_progress' => 'background:#f3e8ff;color:#7e22ce;',
    'resolved'    => 'background:#dcfce7;color:#15803d;',
    'closed'      => 'background:#f1f5f9;color:#64748b;',
];
$statLabel = [
    'open'        => 'Open',
    'in_progress' => 'In Progress',
    'resolved'    => 'Resolved',
    'closed'      => 'Closed',
];
$catIcon = [
    'plumbing'   => 'bi-droplet-fill',
    'electrical' => 'bi-lightning-charge-fill',
    'aircon'     => 'bi-thermometer-snow',
    'furniture'  => 'bi-archive-fill',
    'cleaning'   => 'bi-stars',
    'internet'   => 'bi-wifi',
    'security'   => 'bi-shield-lock-fill',
    'other'      => 'bi-tools',
];

$pageTitle  = 'Maintenance';
$activePage = 'maintenance';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title">Maintenance Requests</div>
    <p class="page-sub mb-0">
      <?php if ($roomNo): ?>
        Room <?= e($roomNo) ?> &mdash; report issues and track repair progress
      <?php else: ?>
        Report issues and track repair progress
      <?php endif; ?>
    </p>
  </div>
  <button class="btn btn-brand btn-sm" type="button"
          data-bs-toggle="collapse" data-bs-target="#submitForm"
          aria-expanded="false" aria-controls="submitForm">
    <i class="bi bi-plus-lg me-1"></i>New Request
  </button>
</div>

<!-- ── Submit form (collapsible) ──────────────────────────────────────────── -->
<div class="collapse mb-4" id="submitForm">
  <div class="card-box">
    <h6 class="fw-bold mb-1" style="font-size:.95rem;">
      <i class="bi bi-wrench-adjustable-circle-fill me-2 text-brand"></i>Submit a Maintenance Request
    </h6>
    <p class="text-muted mb-3" style="font-size:.8rem;">
      Describe the issue and our team will respond as soon as possible.
    </p>

    <form method="POST" action="maintenance.php" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="_action" value="submit">

      <div class="mb-3">
        <label class="form-label fw-semibold" style="font-size:.84rem;" for="ticketTitle">
          Title <span class="text-danger">*</span>
        </label>
        <input type="text" id="ticketTitle" name="title"
               class="form-control form-control-sm"
               placeholder="e.g. Air conditioner not cooling"
               maxlength="200" required autofocus>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.84rem;" for="ticketCategory">Category</label>
          <select id="ticketCategory" name="category" class="form-select form-select-sm">
            <?php foreach ($CATEGORIES as $c): ?>
            <option value="<?= e($c) ?>"><?= e(ucfirst($c)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold" style="font-size:.84rem;">Priority</label>
          <div class="d-flex gap-2 flex-wrap mt-1">
            <?php
            $priOpts = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
            foreach ($priOpts as $pVal => $pLbl):
            ?>
            <div class="form-check form-check-inline m-0">
              <input class="form-check-input" type="radio" name="priority"
                     id="pri_<?= $pVal ?>" value="<?= $pVal ?>"
                     <?= $pVal === 'medium' ? 'checked' : '' ?>>
              <label class="form-check-label" for="pri_<?= $pVal ?>"
                     style="font-size:.83rem;cursor:pointer;"><?= $pLbl ?></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold" style="font-size:.84rem;" for="ticketDesc">
          Description <span class="text-danger">*</span>
        </label>
        <textarea id="ticketDesc" name="description" class="form-control form-control-sm"
                  rows="4" required
                  placeholder="Please describe the issue in detail &mdash; what happened, when it started, and how severe it is."></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-brand btn-sm">
          <i class="bi bi-send-fill me-1"></i>Submit Request
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="collapse" data-bs-target="#submitForm">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Tickets list ───────────────────────────────────────────────────────── -->
<?php if ($tenancy === null): ?>
<div class="card-box text-center py-5">
  <i class="bi bi-house-x" style="font-size:2.5rem;color:#cbd5e1;"></i>
  <p class="text-muted mt-3 mb-1" style="font-size:.9rem;font-weight:600;">No Active Tenancy Found</p>
  <p class="text-muted mb-0" style="font-size:.82rem;">
    You do not have an active tenancy linked to your account.<br>
    Please contact the property manager if you believe this is an error.
  </p>
</div>

<?php else: ?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-3" style="flex-wrap:wrap;">
  <?php
  $tabs = [
      'active'   => 'Active (' . $countActive . ')',
      'resolved' => 'Resolved (' . $countResolved . ')',
      'all'      => 'All',
  ];
  foreach ($tabs as $tKey => $tLabel):
    $isActive = $filterTab === $tKey;
  ?>
  <a href="maintenance.php?tab=<?= $tKey ?>"
     class="btn btn-sm <?= $isActive ? 'btn-brand' : 'btn-outline-secondary' ?>"
     style="font-size:.78rem;"><?= e($tLabel) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tickets): ?>
<div class="row g-3">
  <?php foreach ($tickets as $t): ?>
  <div class="col-md-6">
    <div class="card-box h-100" style="position:relative;">
      <!-- Priority stripe -->
      <div style="position:absolute;top:0;left:0;bottom:0;width:4px;border-radius:14px 0 0 14px;
                  background:<?= $t['priority'] === 'urgent' ? '#ef4444' : ($t['priority'] === 'high' ? '#f59e0b' : ($t['priority'] === 'medium' ? '#eab308' : '#94a3b8')) ?>;"></div>

      <div class="ps-2">
        <!-- Header row -->
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center gap-2">
            <span style="font-size:1.1rem;color:var(--brand);">
              <i class="bi <?= e($catIcon[$t['category']] ?? 'bi-tools') ?>"></i>
            </span>
            <div>
              <div class="fw-semibold" style="font-size:.88rem;color:#0f172a;line-height:1.3;">
                <?= e($t['title']) ?>
              </div>
              <div style="font-size:.73rem;color:#94a3b8;">
                #<?= (int)$t['id'] ?> &middot; <?= e(ucfirst($t['category'])) ?>
              </div>
            </div>
          </div>
          <!-- Status badge -->
          <span class="s-badge" style="<?= e($statStyle[$t['status']] ?? '') ?>;white-space:nowrap;flex-shrink:0;">
            <?= e($statLabel[$t['status']] ?? ucfirst($t['status'])) ?>
          </span>
        </div>

        <!-- Description excerpt -->
        <?php if (!empty($t['description'])): ?>
        <p style="font-size:.8rem;color:#475569;margin-bottom:.75rem;line-height:1.5;">
          <?= e(mb_strimwidth($t['description'], 0, 140, '...')) ?>
        </p>
        <?php endif; ?>

        <!-- Meta row -->
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <span class="s-badge" style="<?= e($priStyle[$t['priority']] ?? '') ?>">
            <?= e($priLabel[$t['priority']] ?? ucfirst($t['priority'])) ?>
          </span>
          <span style="font-size:.73rem;color:#94a3b8;">
            <i class="bi bi-calendar3 me-1"></i><?= dateDisplay($t['created_at']) ?>
          </span>
          <?php if (!empty($t['resolved_at']) && in_array($t['status'], ['resolved','closed'], true)): ?>
          <span style="font-size:.73rem;color:#15803d;">
            <i class="bi bi-check-circle me-1"></i>Resolved <?= dateDisplay($t['resolved_at']) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="card-box text-center py-5">
  <i class="bi bi-tools" style="font-size:2.5rem;color:#cbd5e1;"></i>
  <p class="text-muted mt-3 mb-3" style="font-size:.9rem;">
    <?php if ($filterTab === 'active'): ?>
      No active maintenance requests for your room.
    <?php elseif ($filterTab === 'resolved'): ?>
      No resolved requests found.
    <?php else: ?>
      No maintenance requests found.
    <?php endif; ?>
  </p>
  <button class="btn btn-brand btn-sm" type="button"
          data-bs-toggle="collapse" data-bs-target="#submitForm">
    <i class="bi bi-plus-lg me-1"></i>Submit a Request
  </button>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/layout_end.php'; ?>
