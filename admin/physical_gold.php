<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();
$errors = [];

// ── Handle actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $req_id = (int)($_POST['req_id'] ?? 0);

    $req = $db->prepare("SELECT * FROM gold_physical_redemptions WHERE id=?");
    $req->execute([$req_id]);
    $req = $req->fetch();

    if (!$req) {
        flash_set('main', 'Rekod tidak dijumpai.', 'error');
        redirect(APP_URL . '/admin/physical-gold');
    }

    // Approve — mark as ready, generate pickup reference
    if ($action === 'approve' && $req['status'] === 'pending') {
        $admin_notes = sanitize_string($_POST['admin_notes'] ?? '', 300);
        $ref = 'PG-' . strtoupper(substr(md5($req_id . time()), 0, 8));

        $db->prepare("UPDATE gold_physical_redemptions SET status='ready', pickup_reference=?, admin_notes=?,
            approved_by=?, approved_at=NOW(), ready_at=NOW(), updated_at=NOW() WHERE id=?")
          ->execute([$ref, $admin_notes, $admin['id'], $req_id]);

        audit_log((int)$admin['id'], 'super_admin', 'physical_gold_approved', 'gold_physical_redemptions', $req_id, null, ['ref' => $ref]);
        flash_set('main', 'Permohonan #' . $req_id . ' diluluskan. Ref: ' . $ref, 'success');

    // Mark collected — deduct from physical vault stock
    } elseif ($action === 'collected' && $req['status'] === 'ready') {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE gold_physical_redemptions SET status='collected', collected_at=NOW(), updated_at=NOW() WHERE id=?")
              ->execute([$req_id]);
            adjust_gold_stock(
                -(float)$req['total_grams'], 'redemption_out',
                'Plat emas dikutip — permohonan #' . $req_id . ' (' . $req['plates_count'] . ' plat, ' . $req['total_grams'] . 'g)',
                'physical_redemption', $req_id, (int)$admin['id']
            );
            $db->commit();
            audit_log((int)$admin['id'], 'super_admin', 'physical_gold_collected', 'gold_physical_redemptions', $req_id, null, null);
            flash_set('main', 'Permohonan #' . $req_id . ' ditanda sebagai dikutip. Stok dikurangkan ' . $req['total_grams'] . 'g.', 'success');
        } catch (Throwable $e) {
            $db->rollBack();
            flash_set('main', 'Ralat sistem semasa menanda kutipan.', 'error');
        }

    // Reject — refund points
    } elseif ($action === 'reject' && in_array($req['status'], ['pending', 'approved', 'ready'])) {
        $reason = sanitize_string($_POST['reject_reason'] ?? '', 300);
        if (!$reason) {
            flash_set('main', 'Sila berikan sebab penolakan.', 'error');
            redirect(APP_URL . '/admin/physical-gold');
        }

        $db->beginTransaction();
        try {
            $wid = ensure_wallet_exists((int)$req['user_id']);
            ledger_credit($wid, (int)$req['user_id'],
                (string)$req['points_redeemed'], (string)$req['total_grams'],
                '0', (string)$req['price_per_g_snapshot'],
                'physical_gold_refund', $req_id,
                'Bayaran balik — permohonan emas fizikal #' . $req_id . ' ditolak');

            $db->prepare("UPDATE gold_physical_redemptions SET status='rejected', admin_notes=?, rejected_at=NOW(), updated_at=NOW() WHERE id=?")
              ->execute([$reason, $req_id]);

            $db->commit();
            audit_log((int)$admin['id'], 'super_admin', 'physical_gold_rejected', 'gold_physical_redemptions', $req_id, null, ['reason' => $reason]);
            flash_set('main', 'Permohonan #' . $req_id . ' ditolak dan mata dikembalikan kepada pengguna.', 'success');
        } catch (Throwable $e) {
            $db->rollBack();
            flash_set('main', 'Ralat sistem semasa menolak permohonan.', 'error');
        }
    }

    redirect(APP_URL . '/admin/physical-gold');
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$stats = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='pending')   AS pending,
    SUM(status='ready')     AS ready,
    SUM(status='collected') AS collected,
    SUM(status='rejected')  AS rejected,
    SUM(CASE WHEN status IN ('ready','collected') THEN plates_count ELSE 0 END) AS plates_approved,
    SUM(CASE WHEN status='collected' THEN total_grams ELSE 0 END) AS grams_dispensed
    FROM gold_physical_redemptions")->fetch();

// ── Requests list ─────────────────────────────────────────────────────────────
$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE gpr.status = " . $db->quote($filter) : '';

$requests = $db->query("
    SELECT gpr.*, u.full_name, u.email, u.phone,
           a.full_name AS approved_by_name
    FROM gold_physical_redemptions gpr
    JOIN users u ON u.id = gpr.user_id
    LEFT JOIN users a ON a.id = gpr.approved_by
    $where
    ORDER BY FIELD(gpr.status,'pending','ready','approved','collected','rejected','cancelled'), gpr.created_at DESC
    LIMIT 100")->fetchAll();

layout_begin_admin('Pengurusan Emas Fizikal');
?>
<div class="page-title">🥇 Pengurusan Pengeluaran Emas Fizikal</div>
<?= flash_html('main') ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:24px;">
  <?php
  $stat_cards = [
    ['Jumlah',         $stats['total'],           '#1F2937', '#F9FAFB', '#E5E7EB'],
    ['Menunggu',       $stats['pending'],          '#92400E', '#FFFBEB', '#FDE68A'],
    ['Siap Ambil',     $stats['ready'],            '#065F46', '#D1FAE5', '#6EE7B7'],
    ['Dikutip',        $stats['collected'],        '#1D4ED8', '#EFF6FF', '#BFDBFE'],
    ['Ditolak',        $stats['rejected'],         '#991B1B', '#FEF2F2', '#FECACA'],
    ['Plat Diluluskan',$stats['plates_approved'],  '#92400E', '#FFFBEB', '#FDE68A'],
  ];
  foreach ($stat_cards as [$label, $val, $color, $bg, $border]):
  ?>
  <div style="background:<?= $bg ?>;border:1px solid <?= $border ?>;border-radius:10px;padding:14px 16px;">
    <div style="font-size:0.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;"><?= $label ?></div>
    <div style="font-size:1.8rem;font-weight:800;color:<?= $color ?>;line-height:1.1;margin-top:4px;"><?= number_format((float)$val) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <?php foreach (['all'=>'Semua','pending'=>'Menunggu','ready'=>'Siap Ambil','collected'=>'Dikutip','rejected'=>'Ditolak'] as $val=>$label): ?>
  <a href="?status=<?= $val ?>" class="btn-sm <?= $filter===$val ? 'btn-gold' : 'btn-gold-outline' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr>
          <th>#</th>
          <th>Pengguna</th>
          <th>Plat</th>
          <th>Berat</th>
          <th>Mata Ditolak</th>
          <th>Status</th>
          <th>Ref Pengambilan</th>
          <th>Tarikh Mohon</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
        <tr><td colspan="9" style="text-align:center;color:#9CA3AF;padding:24px;">Tiada rekod.</td></tr>
        <?php endif; ?>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td style="font-size:0.8rem;color:#9CA3AF;"><?= $r['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($r['full_name']) ?></div>
            <div style="font-size:0.72rem;color:#9CA3AF;"><?= h($r['email']) ?></div>
            <?php if ($r['phone']): ?><div style="font-size:0.72rem;color:#9CA3AF;"><?= h($r['phone']) ?></div><?php endif; ?>
          </td>
          <td style="font-weight:700;font-size:1rem;"><?= $r['plates_count'] ?></td>
          <td><?= number_format((float)$r['total_grams'], 4) ?>g</td>
          <td style="color:#DC2626;font-weight:600;"><?= gold_format_points($r['points_redeemed']) ?> pts</td>
          <td><?= status_badge($r['status']) ?></td>
          <td style="font-weight:700;color:var(--gold-dark);font-size:0.85rem;">
            <?= $r['pickup_reference'] ? h($r['pickup_reference']) : '—' ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['created_at']) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
            <!-- Approve form -->
            <div x-data="{open:false}" style="display:inline;">
              <button @click="open=!open" class="btn-sm" style="background:#065F46;color:#fff;border:none;cursor:pointer;border-radius:6px;padding:5px 10px;font-size:0.78rem;">✅ Lulus</button>
              <div x-show="open" style="position:absolute;z-index:50;background:#fff;border:1px solid #D1D5DB;border-radius:8px;padding:14px;width:280px;box-shadow:0 4px 20px rgba(0,0,0,0.12);margin-top:4px;">
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action"  value="approve">
                  <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
                  <div style="font-size:0.78rem;font-weight:600;margin-bottom:8px;">Lulus & Jana Ref Pengambilan</div>
                  <textarea name="admin_notes" class="input-kasih" rows="2" placeholder="Nota untuk pengguna (pilihan)" style="font-size:0.8rem;margin-bottom:8px;"></textarea>
                  <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn-sm" style="background:#065F46;color:#fff;border:none;cursor:pointer;border-radius:6px;flex:1;">Jana & Lulus</button>
                    <button type="button" @click="open=false" class="btn-sm btn-gold-outline">Batal</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Reject form -->
            <div x-data="{open:false}" style="display:inline;margin-left:4px;">
              <button @click="open=!open" class="btn-sm" style="background:#DC2626;color:#fff;border:none;cursor:pointer;border-radius:6px;padding:5px 10px;font-size:0.78rem;">✗ Tolak</button>
              <div x-show="open" style="position:absolute;z-index:50;background:#fff;border:1px solid #FECACA;border-radius:8px;padding:14px;width:280px;box-shadow:0 4px 20px rgba(0,0,0,0.12);margin-top:4px;">
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action"  value="reject">
                  <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
                  <div style="font-size:0.78rem;font-weight:600;color:#991B1B;margin-bottom:8px;">Tolak & Pulangkan Mata</div>
                  <textarea name="reject_reason" class="input-kasih" rows="2" placeholder="Sebab penolakan (wajib)" required style="font-size:0.8rem;margin-bottom:8px;"></textarea>
                  <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn-sm" style="background:#DC2626;color:#fff;border:none;cursor:pointer;border-radius:6px;flex:1;">Tolak</button>
                    <button type="button" @click="open=false" class="btn-sm btn-gold-outline">Batal</button>
                  </div>
                </form>
              </div>
            </div>

            <?php elseif ($r['status'] === 'ready'): ?>
            <form method="POST" onsubmit="return confirm('Sahkan pengguna telah mengutip plat emas?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action"  value="collected">
              <input type="hidden" name="req_id"  value="<?= $r['id'] ?>">
              <button type="submit" class="btn-sm" style="background:#1D4ED8;color:#fff;border:none;cursor:pointer;border-radius:6px;padding:5px 10px;font-size:0.78rem;">🤝 Dikutip</button>
            </form>

            <?php else: ?>
            <span style="font-size:0.75rem;color:#9CA3AF;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php if ($r['user_notes'] || $r['admin_notes']): ?>
        <tr style="background:#FAFAFA;">
          <td colspan="9" style="font-size:0.78rem;color:#6B7280;padding:5px 12px;">
            <?php if ($r['user_notes']): ?><span style="margin-right:16px;">💬 Pengguna: <?= h($r['user_notes']) ?></span><?php endif; ?>
            <?php if ($r['admin_notes']): ?><span style="color:#92400E;">📌 Admin: <?= h($r['admin_notes']) ?></span><?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_end_admin(); ?>
