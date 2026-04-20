<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_role(['super_admin'], '/login');

$db       = getDB();
$admin_id = auth_id();
$errors   = [];

// ── Handle actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // ── Set buy price ──
    if ($action === 'set_price') {
        $price_val = trim($_POST['price_per_g'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');
        if (!is_numeric($price_val) || (float)$price_val <= 0)
            $errors[] = 'Sila masukkan harga yang sah.';
        elseif ((float)$price_val < 50 || (float)$price_val > 9999)
            $errors[] = 'Harga mesti antara RM50 dan RM9999 per gram.';

        if (empty($errors)) {
            $db->query("UPDATE gold_sell_prices SET status='superseded' WHERE status='active'");
            $db->prepare("INSERT INTO gold_sell_prices (price_per_g, status, notes, created_by, effective_at, created_at) VALUES (?,'active',?,?,NOW(),NOW())")
               ->execute([$price_val, $notes, $admin_id]);
            flash_set('main', 'Harga beli emas dikemaskini: RM ' . number_format((float)$price_val,2) . '/g', 'success');
            redirect(APP_URL . '/admin/sell-gold');
        }
    }

    // ── Approve request — gold returns to vault stock ──
    elseif ($action === 'approve') {
        $req_id = (int)($_POST['req_id'] ?? 0);
        $rs = $db->prepare("SELECT * FROM gold_sell_requests WHERE id=?"); $rs->execute([$req_id]); $req = $rs->fetch();
        if ($req && $req['status'] === 'pending') {
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE gold_sell_requests SET status='approved', reviewed_by=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?")
                   ->execute([$admin_id, $req_id]);
                adjust_gold_stock(
                    (float)$req['grams_amount'], 'buyback_in',
                    'Beli balik emas — permintaan jual #' . $req_id . ' (' . $req['grams_amount'] . 'g @ RM' . $req['price_per_g_snapshot'] . '/g)',
                    'sell_request', $req_id, (int)$admin_id
                );
                $db->commit();
                flash_set('main', 'Permintaan #'.$req_id.' diluluskan. Stok ditambah ' . $req['grams_amount'] . 'g.', 'success');
            } catch (Throwable $e) {
                $db->rollBack();
                flash_set('main', 'Ralat sistem semasa meluluskan.', 'error');
            }
        }
        redirect(APP_URL . '/admin/sell-gold');
    }

    // ── Mark as paid ──
    elseif ($action === 'mark_paid') {
        $req_id   = (int)($_POST['req_id'] ?? 0);
        $pay_ref  = trim($_POST['payment_reference'] ?? '');
        $rs = $db->prepare("SELECT * FROM gold_sell_requests WHERE id=?"); $rs->execute([$req_id]); $req = $rs->fetch();
        if ($req && $req['status'] === 'approved') {
            $db->prepare("UPDATE gold_sell_requests SET status='paid', payment_reference=?, paid_at=NOW(), updated_at=NOW() WHERE id=?")
               ->execute([$pay_ref, $req_id]);
            flash_set('main', 'Permintaan #'.$req_id.' ditandakan sebagai dibayar.', 'success');
        }
        redirect(APP_URL . '/admin/sell-gold');
    }

    // ── Reject request ──
    elseif ($action === 'reject') {
        $req_id = (int)($_POST['req_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (!$reason) { flash_set('main','Sila masukkan sebab penolakan.','error'); redirect(APP_URL.'/admin/sell-gold'); }
        $rs = $db->prepare("SELECT * FROM gold_sell_requests WHERE id=?"); $rs->execute([$req_id]); $req = $rs->fetch();
        if ($req && in_array($req['status'], ['pending','approved'])) {
            $db->beginTransaction();
            try {
                // Refund gold points to user
                $price      = get_active_gold_price();
                $price_snap = $price ? (string)$price['price_per_g'] : $req['price_per_g_snapshot'];
                $pts        = (string)$req['points_amount'];
                $grams      = (string)$req['grams_amount'];
                $rm_val     = gold_rm_from_points($pts, $price_snap);
                $wid        = ensure_wallet_exists((int)$req['user_id']);
                ledger_credit($wid, (int)$req['user_id'], $pts, $grams, $rm_val, $price_snap,
                    'gold_sell_refund', $req_id, 'Bayaran balik: permohonan jual #'.$req_id.' ditolak');

                $db->prepare("UPDATE gold_sell_requests SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?")
                   ->execute([$reason, $admin_id, $req_id]);
                $db->commit();
                flash_set('main', 'Permintaan #'.$req_id.' ditolak dan mata dikembalikan.', 'success');
            } catch (\Throwable $e) {
                $db->rollBack();
                flash_set('main', 'Ralat: '.$e->getMessage(), 'error');
            }
        }
        redirect(APP_URL . '/admin/sell-gold');
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$current_sell_price = get_active_sell_price();
$current_buy_price  = get_active_gold_price();

$price_history = $db->query("SELECT gsp.*, u.full_name FROM gold_sell_prices gsp LEFT JOIN users u ON u.id=gsp.created_by ORDER BY gsp.effective_at DESC LIMIT 10")->fetchAll();

$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per           = 20;

$where  = '1=1';
$params = [];
if ($status_filter) { $where .= ' AND gsr.status=?'; $params[] = $status_filter; }
if ($q)             { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR gsr.id LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM gold_sell_requests gsr JOIN users u ON u.id=gsr.user_id WHERE $where");
$ct->execute($params);
$total = (int)$ct->fetchColumn();
$pag   = paginate($total, $per, $page, APP_URL.'/admin/sell-gold?status='.urlencode($status_filter).'&q='.urlencode($q).'&page={page}');

$reqs = $db->prepare("SELECT gsr.*, u.full_name AS user_name, u.email AS user_email FROM gold_sell_requests gsr JOIN users u ON u.id=gsr.user_id WHERE $where ORDER BY gsr.created_at DESC LIMIT ?,?");
$reqs->execute(array_merge($params, [$pag['offset'], $per]));
$requests = $reqs->fetchAll();

// Stats
$st = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='pending')  AS pending,
    SUM(status='approved') AS approved,
    SUM(status='paid')     AS paid,
    SUM(status='rejected') AS rejected,
    COALESCE(SUM(CASE WHEN status='paid' THEN rm_payout ELSE 0 END),0) AS total_rm_paid
FROM gold_sell_requests")->fetch();

layout_begin_admin('Jual Emas — Admin');
?>

<div class="page-title">💰 Pengurusan Jual Emas</div>
<?= flash_html('main') ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

  <!-- Current Price -->
  <div class="price-card">
    <div class="price-label">Harga Beli Emas Semasa (Kami Bayar)</div>
    <?php if ($current_sell_price): ?>
      <div class="price-big">RM <?= number_format((float)$current_sell_price['price_per_g'],2) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;">/g</span></div>
      <div class="price-date">Dikemaskini: <?= format_date($current_sell_price['effective_at']) ?></div>
    <?php else: ?>
      <div style="color:#DC2626;font-weight:700;">⚠️ Belum ditetapkan</div>
    <?php endif; ?>
    <?php if ($current_buy_price): ?>
      <div style="font-size:0.78rem;color:#9CA3AF;margin-top:8px;">
        Harga jual semasa (pengguna beli): RM <?= number_format((float)$current_buy_price['price_per_g'],2) ?>/g
        <?php $spread = $current_sell_price ? number_format((float)$current_buy_price['price_per_g'] - (float)$current_sell_price['price_per_g'],2) : '—'; ?>
        &nbsp;|&nbsp; Spread: RM <?= $spread ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Set Price Form -->
  <div class="card-kasih">
    <div class="section-title" style="margin-bottom:12px;">✏️ Tetapkan Harga Beli Baharu</div>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:10px;"><?= implode('<br>', array_map('h', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_price">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label class="form-label" style="font-size:0.78rem;">Harga (RM/gram)</label>
          <input type="number" name="price_per_g" class="form-input" step="0.01" min="50" max="9999"
                 style="width:140px;" placeholder="Contoh: 370.00" required>
        </div>
        <div style="flex:1;">
          <label class="form-label" style="font-size:0.78rem;">Nota (pilihan)</label>
          <input type="text" name="notes" class="form-input" placeholder="Nota pentadbir..." maxlength="300">
        </div>
        <button type="submit" class="btn-gold btn-sm">Tetapkan</button>
      </div>
    </form>
  </div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= (int)$st['total'] ?></div><div class="stat-label">Jumlah Permintaan</div></div>
  <div class="stat-card stat-card-blue" style="<?= (int)$st['pending']>0?'border-left:3px solid #F59E0B;':'' ?>">
    <div class="stat-value" style="color:<?= (int)$st['pending']>0?'#D97706':'inherit'?>;"><?= (int)$st['pending'] ?></div>
    <div class="stat-label">Menunggu</div>
  </div>
  <div class="stat-card stat-card-purple"><div class="stat-value"><?= (int)$st['approved'] ?></div><div class="stat-label">Diluluskan</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$st['paid'] ?></div><div class="stat-label">Dibayar</div></div>
  <div class="stat-card" style="background:#FEF2F2;"><div class="stat-value" style="color:#991B1B;"><?= (int)$st['rejected'] ?></div><div class="stat-label">Ditolak</div></div>
  <div class="stat-card" style="background:#F0FDF4;">
    <div class="stat-value" style="color:#065F46;font-size:1rem;">RM <?= number_format((float)$st['total_rm_paid'],2) ?></div>
    <div class="stat-label">Jumlah RM Dibayar</div>
  </div>
</div>

<!-- Filter -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:150px;">
        <option value="">Semua</option>
        <option value="pending"  <?= $status_filter==='pending'  ?'selected':''?>>⏳ Menunggu</option>
        <option value="approved" <?= $status_filter==='approved' ?'selected':''?>>✅ Diluluskan</option>
        <option value="paid"     <?= $status_filter==='paid'     ?'selected':''?>>💰 Dibayar</option>
        <option value="rejected" <?= $status_filter==='rejected' ?'selected':''?>>❌ Ditolak</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari</label>
      <input type="text" name="q" class="form-input" value="<?= h($q) ?>" placeholder="Nama / e-mel / ID..." style="min-width:180px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/admin/sell-gold" class="btn-gold-outline btn-sm">Reset</a>
  </form>
</div>

<!-- Requests Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> permintaan</div>
  </div>
  <?php if (empty($requests)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;">Tiada permintaan dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Pengguna</th><th>Mata</th><th>Gram</th><th>RM Bayar</th><th>Bank</th><th>Status</th><th>Tarikh</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr style="<?= $r['status']==='pending'?'background:#FFFBEB;':'' ?>">
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $r['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($r['user_name']) ?></div>
            <div style="font-size:0.72rem;color:#9CA3AF;"><?= h($r['user_email']) ?></div>
          </td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($r['points_amount']) ?></td>
          <td style="font-size:0.82rem;"><?= gold_format_grams($r['grams_amount']) ?></td>
          <td style="font-weight:700;color:#065F46;">RM <?= number_format((float)$r['rm_payout'],2) ?></td>
          <td style="font-size:0.78rem;">
            <div><?= h($r['bank_name']) ?></div>
            <div style="color:#9CA3AF;"><?= h($r['bank_account']) ?></div>
            <div style="color:#6B7280;"><?= h($r['bank_account_name']) ?></div>
          </td>
          <td>
            <?= status_badge($r['status']) ?>
            <?php if ($r['payment_reference']): ?>
              <div style="font-size:0.7rem;color:#6B7280;margin-top:2px;">Ref: <?= h($r['payment_reference']) ?></div>
            <?php endif; ?>
            <?php if ($r['status']==='rejected' && $r['rejection_reason']): ?>
              <div style="font-size:0.7rem;color:#991B1B;margin-top:2px;"><?= h($r['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
              <?php if ($r['status'] === 'pending'): ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Luluskan permintaan ini?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action"  value="approve">
                  <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
                  <button class="btn-gold btn-sm">✅ Lulus</button>
                </form>
              <?php endif; ?>

              <?php if ($r['status'] === 'approved'): ?>
                <div x-data="{open:false}" style="position:relative;">
                  <button @click="open=!open" class="btn-gold btn-sm">💳 Bayar</button>
                  <div x-show="open" @click.outside="open=false" style="position:absolute;right:0;top:100%;background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:12px;width:240px;z-index:50;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action"  value="mark_paid">
                      <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
                      <label class="form-label" style="font-size:0.75rem;">Rujukan Pembayaran</label>
                      <input type="text" name="payment_reference" class="form-input" placeholder="No. transaksi bank..." style="margin-bottom:8px;">
                      <button type="submit" class="btn-gold btn-sm" style="width:100%;">Tandakan Dibayar</button>
                    </form>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (in_array($r['status'], ['pending','approved'])): ?>
                <div x-data="{open:false}" style="position:relative;">
                  <button @click="open=!open" class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;">Tolak</button>
                  <div x-show="open" @click.outside="open=false" style="position:absolute;right:0;top:100%;background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:12px;width:240px;z-index:50;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action"  value="reject">
                      <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
                      <label class="form-label" style="font-size:0.75rem;">Sebab Penolakan</label>
                      <input type="text" name="rejection_reason" class="form-input" placeholder="Sebab..." required style="margin-bottom:8px;">
                      <button type="submit" class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;width:100%;">Tolak &amp; Kembalikan Mata</button>
                    </form>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (in_array($r['status'], ['paid','rejected','cancelled'])): ?>
                <span style="color:#D1D5DB;font-size:0.78rem;">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>

<!-- Price History -->
<?php if (!empty($price_history)): ?>
<div class="card-kasih" style="margin-top:20px;">
  <div class="section-title">📈 Sejarah Harga Beli</div>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Harga RM/g</th><th>Status</th><th>Ditetapkan Oleh</th><th>Tarikh</th><th>Nota</th></tr></thead>
      <tbody>
        <?php foreach ($price_history as $ph): ?>
        <tr>
          <td style="font-weight:700;">RM <?= number_format((float)$ph['price_per_g'],2) ?></td>
          <td><?= status_badge($ph['status']) ?></td>
          <td style="font-size:0.82rem;"><?= h($ph['full_name'] ?? '—') ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= format_date($ph['effective_at']) ?></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($ph['notes'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php layout_end_admin(); ?>
