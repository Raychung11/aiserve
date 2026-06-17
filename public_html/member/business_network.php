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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $sector      = trim($_POST['sector'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $investment  = $_POST['investment_range'] ?? '';
    if ($sector && $description) {
        $pdo->prepare(
            'INSERT INTO business_interests (member_id, sector, description, investment_range, status) VALUES (?,?,?,?,?)'
        )->execute([$uid, $sector, $description, $investment, 'pending']);
        flash('success', 'Business interest submitted. Our team will match you with suitable partners within 5 business days.');
        log_activity('submit_business_interest');
    }
    redirect('member/business-network');
}

$interests_stmt = $pdo->prepare(
    'SELECT bi.*, bm.id AS match_id, bm.status AS match_status, u.full_name AS partner_name
     FROM business_interests bi
     LEFT JOIN business_matches bm ON bm.interest_id=bi.id
     LEFT JOIN users u ON u.id=bm.partner_id
     WHERE bi.member_id=? ORDER BY bi.created_at DESC'
);
$interests_stmt->execute([$uid]);
$my_interests = $interests_stmt->fetchAll();

$sectors = ['Property','Fintech','FMCG','Takaful','Healthcare','Education','Tourism','Franchise','F&B','Manufacturing','E-Commerce','Logistics','Agriculture','Technology','Renewable Energy'];

$page_title = 'Business Networking — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-people-fill me-2 text-gold"></i>Business Networking</h1>
      <p class="text-muted">Connect with Malaysian entrepreneurs, investors, and industry leaders.</p>
    </div>
    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Submit interest -->
      <div class="col-lg-5">
        <div class="mm2h-form-card">
          <h5 class="mb-4">Submit Business Interest</h5>
          <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Sector of Interest <span class="text-danger">*</span></label>
              <select name="sector" class="form-select" required>
                <option value="">Select sector…</option>
                <?php foreach ($sectors as $s): ?>
                <option value="<?= h($s) ?>"><?= h($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Investment Range</label>
              <select name="investment_range" class="form-select">
                <option value="">Select…</option>
                <option>Under MYR 500,000</option>
                <option>MYR 500k – 1M</option>
                <option>MYR 1M – 5M</option>
                <option>MYR 5M – 20M</option>
                <option>Above MYR 20M</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Describe Your Interest <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="4" required
                        placeholder="What kind of business opportunities or partnerships are you looking for? Include any specific requirements or background."></textarea>
            </div>
            <button type="submit" class="btn btn-gold w-100">
              <i class="bi bi-send me-2"></i>Submit Interest
            </button>
          </form>
        </div>

        <!-- Sectors grid -->
        <div class="mm2h-form-card mt-4">
          <h6 class="mb-3">Active Sectors in Our Network</h6>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($sectors as $s): ?>
            <span class="badge" style="background:rgba(200,160,60,.1);color:var(--secondary);border:1px solid rgba(200,160,60,.3);font-weight:500;padding:.35rem .7rem;"><?= h($s) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- My interests -->
      <div class="col-lg-7">
        <h5 class="mb-3">My Submitted Interests</h5>
        <?php if ($my_interests): ?>
        <?php foreach ($my_interests as $interest): ?>
        <div class="mm2h-form-card mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div class="fw-bold"><?= h($interest['sector']) ?></div>
              <?php if ($interest['investment_range']): ?>
              <div class="small text-muted"><?= h($interest['investment_range']) ?></div>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <?= status_badge($interest['status']) ?>
              <span class="text-muted small"><?= time_ago($interest['created_at']) ?></span>
            </div>
          </div>
          <p class="small text-muted mb-3"><?= h($interest['description']) ?></p>
          <?php if ($interest['match_id']): ?>
          <div class="alert alert-success mb-0 py-2 small">
            <i class="bi bi-people-fill me-2"></i>
            Matched with partner: <strong><?= h($interest['partner_name'] ?? 'TBC') ?></strong>
            — Status: <?= status_badge($interest['match_status'] ?? 'proposed') ?>
          </div>
          <?php else: ?>
          <div class="alert alert-info mb-0 py-2 small">
            <i class="bi bi-clock me-2"></i>Awaiting partner match. Our team will connect you within 5 business days.
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="mm2h-form-card text-center py-5 text-muted">
          <i class="bi bi-people fs-1 d-block mb-3" style="opacity:.3"></i>
          <p>No business interests submitted yet. Use the form to tell us what opportunities you're seeking.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
