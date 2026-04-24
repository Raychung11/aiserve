<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Handle interest response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rec_id'])) {
    verify_csrf();
    $rec_id    = (int)$_POST['rec_id'];
    $interested = (int)(isset($_POST['interested']) && $_POST['interested'] === '1');
    $pdo->prepare('UPDATE property_recommendations SET interested=?, viewed=1 WHERE id=? AND member_id=?')
        ->execute([$interested, $rec_id, $uid]);
    flash('success', 'Your interest has been recorded. Our team will follow up.');
    redirect('member/property-match');
}

// Mark as viewed
$pdo->prepare('UPDATE property_recommendations SET viewed=1 WHERE member_id=? AND viewed=0')->execute([$uid]);

// Recommended properties
$recs_stmt = $pdo->prepare(
    'SELECT pr.*, p.property_name, p.location, p.state, p.type, p.price, p.price_currency,
            p.developer, p.agent_name, p.agent_contact, p.mm2h_suitability, p.rental_roi_estimate,
            p.investment_notes, p.image, p.status AS prop_status
     FROM property_recommendations pr
     JOIN properties p ON p.id = pr.property_id
     WHERE pr.member_id = ? ORDER BY pr.created_at DESC'
);
$recs_stmt->execute([$uid]);
$recommendations = $recs_stmt->fetchAll();

// All featured properties (browse)
$featured = $pdo->query(
    'SELECT * FROM properties WHERE featured=1 AND status="available" ORDER BY created_at DESC LIMIT 9'
)->fetchAll();

$page_title = 'Property Matching — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-buildings me-2 text-gold"></i>Property Matching</h1>
    </div>
    <?php render_flash(); ?>

    <!-- Recommended -->
    <?php if ($recommendations): ?>
    <h5 class="mb-3">Recommended for You</h5>
    <div class="row g-4 mb-5">
      <?php foreach ($recommendations as $rec): ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card position-relative">
          <?php if ($rec['mm2h_suitability']): ?>
          <span class="badge bg-warning text-dark position-absolute" style="top:1rem;right:1rem;">MM2H Suitable</span>
          <?php endif; ?>
          <div class="mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-building fs-2 text-gold"></i>
            <div>
              <div class="fw-bold"><?= h($rec['property_name']) ?></div>
              <div class="text-muted small"><?= h($rec['location'] ?? '') ?>, <?= h($rec['state'] ?? '') ?></div>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Type</div>
              <div class="small fw-semibold"><?= h(ucwords(str_replace('_',' ',$rec['type'] ?? ''))) ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Price</div>
              <div class="small fw-semibold text-gold"><?= format_money((float)$rec['price']) ?></div>
            </div>
            <?php if ($rec['rental_roi_estimate']): ?>
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Est. ROI</div>
              <div class="small fw-semibold"><?= h($rec['rental_roi_estimate']) ?>%/yr</div>
            </div>
            <?php endif; ?>
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Developer</div>
              <div class="small fw-semibold"><?= h($rec['developer'] ?? '—') ?></div>
            </div>
          </div>
          <?php if ($rec['investment_notes']): ?>
          <p class="small text-muted mb-3"><?= h($rec['investment_notes']) ?></p>
          <?php endif; ?>
          <?php if ($rec['interested'] === null): ?>
          <form method="POST" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="rec_id" value="<?= $rec['id'] ?>">
            <button name="interested" value="1" class="btn btn-gold btn-sm flex-fill">
              <i class="bi bi-heart me-1"></i>Interested
            </button>
            <button name="interested" value="0" class="btn btn-outline-secondary btn-sm flex-fill">
              Not Now
            </button>
          </form>
          <?php elseif ($rec['interested']): ?>
          <div class="alert alert-success mb-0 py-2 small"><i class="bi bi-check-circle me-1"></i>You expressed interest. Agent will contact you.</div>
          <?php else: ?>
          <div class="text-muted small">Declined.</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Browse all featured -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Featured Properties</h5>
      <span class="text-muted small"><?= count($featured) ?> listings</span>
    </div>
    <?php if ($featured): ?>
    <div class="row g-4">
      <?php foreach ($featured as $prop): ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card">
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-building fs-2 text-gold"></i>
            <div>
              <div class="fw-bold small"><?= h($prop['property_name']) ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= h($prop['location'] ?? '') ?></div>
            </div>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="small text-muted"><?= h(ucwords(str_replace('_',' ',$prop['type'] ?? ''))) ?></span>
            <span class="fw-bold text-gold small"><?= format_money((float)$prop['price']) ?></span>
          </div>
          <?php if ($prop['rental_roi_estimate']): ?>
          <div class="small text-muted">Est. ROI: <?= h($prop['rental_roi_estimate']) ?>%/yr</div>
          <?php endif; ?>
          <div class="mt-3 small text-muted">
            Agent: <?= h($prop['agent_name'] ?? '—') ?>
            <?php if ($prop['agent_contact']): ?> · <?= h($prop['agent_contact']) ?><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="mm2h-form-card text-center py-5 text-muted">
      <i class="bi bi-buildings fs-1 d-block mb-3" style="opacity:.3"></i>
      <p>No featured properties at this time. Our team will assign recommendations based on your profile.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
