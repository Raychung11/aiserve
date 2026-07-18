<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user_id = auth_id();
$user    = auth_user();
$db      = getDB();

$balance    = get_wallet_balance($user_id);
$sell_price = get_active_sell_price();
$errors     = [];

// Pre-selected deposit from gold-deposit page
$preselect_dep_id = (int)($_GET['deposit_id'] ?? 0);
$preselect_dep    = null;
if ($preselect_dep_id) {
    $ps = $db->prepare("SELECT * FROM gold_deposits WHERE id=? AND user_id=? AND status='active'");
    $ps->execute([$preselect_dep_id, $user_id]);
    $preselect_dep = $ps->fetch() ?: null;
}

// User's active deposits (for form dropdown)
$adeps_stmt = $db->prepare("SELECT * FROM gold_deposits WHERE user_id=? AND status='active' ORDER BY created_at DESC");
$adeps_stmt->execute([$user_id]);
$active_deposits = $adeps_stmt->fetchAll();

// ── Handle submission ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $collateral_type = $_POST['collateral_type'] ?? 'digital';
    $bank_name       = sanitize_string($_POST['bank_name'] ?? '');
    $bank_account    = sanitize_string($_POST['bank_account'] ?? '');
    $bank_acct_name  = sanitize_string($_POST['bank_account_name'] ?? '');
    $user_notes      = sanitize_string($_POST['user_notes'] ?? '');
    $financing_req   = trim($_POST['financing_requested'] ?? '');

    if (!$bank_name)     $errors[] = 'Nama bank diperlukan.';
    if (!$bank_account)  $errors[] = 'Nombor akaun bank diperlukan.';
    if (!$bank_acct_name)$errors[] = 'Nama pemegang akaun diperlukan.';
    if (!is_numeric($financing_req) || (float)$financing_req <= 0) $errors[] = 'Masukkan jumlah pembiayaan yang dipohon.';

    if ($collateral_type === 'physical') {
        // ── Deposit-backed Ar-Rahnu ──────────────────────────────────────────
        $dep_id = (int)($_POST['deposit_id'] ?? 0);
        $dep_stmt = $db->prepare("SELECT * FROM gold_deposits WHERE id=? AND user_id=? AND status='active'");
        $dep_stmt->execute([$dep_id, $user_id]);
        $dep = $dep_stmt->fetch();

        if (!$dep) {
            $errors[] = 'Deposit tidak dijumpai atau tidak aktif.';
        } elseif (empty($errors)) {
            // Check no existing active ar_rahnu on this deposit
            $exist = $db->prepare("SELECT id FROM ar_rahnu_applications WHERE deposit_id=? AND status NOT IN ('rejected','cancelled','redeemed','defaulted')");
            $exist->execute([$dep_id]);
            if ($exist->fetch()) {
                $errors[] = 'Deposit ini sudah mempunyai permohonan Ar Rahnu yang aktif.';
            }
        }

        if (empty($errors)) {
            $price_snap = $sell_price ? (string)$sell_price['price_per_g'] : (string)(get_active_gold_price()['price_per_g'] ?? '390');
            $grams      = (string)$dep['gold_grams'];
            $mkt_value  = number_format((float)$grams * (float)$price_snap, 2, '.', '');
            $fin_req    = number_format((float)$financing_req, 2, '.', '');

            $db->beginTransaction();
            try {
                $db->prepare("INSERT INTO ar_rahnu_applications
                    (user_id, deposit_id, collateral_type, gold_grams, gold_points, gold_purity,
                     market_value_snapshot, financing_requested, bank_name, bank_account, bank_account_name,
                     user_notes, status, created_at, updated_at)
                    VALUES (?,?,?,?,0,?,?,?,?,?,?,'pending',NOW(),NOW())")
                  ->execute([$user_id, $dep_id, 'physical', $grams, $dep['gold_purity'],
                             $mkt_value, $fin_req, $bank_name, $bank_account, $bank_acct_name]);
                $db->prepare("UPDATE gold_deposits SET status='pawned', updated_at=NOW() WHERE id=?")
                   ->execute([$dep_id]);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                $errors[] = 'Ralat sistem. Sila cuba lagi.';
            }

            if (empty($errors)) {
                flash_set('arrahu', 'Permohonan Ar Rahnu (cagaran deposit '.$dep['deposit_ref'].') berjaya dihantar! Pasukan kami akan menghubungi anda dalam 2–3 hari bekerja.', 'success');
                redirect(APP_URL . '/ar-rahnu');
            }
        }

    } else {
        // ── Digital gold Ar-Rahnu (existing flow) ───────────────────────────
        $pts_input = trim($_POST['gold_points'] ?? '');
        $purity    = $_POST['gold_purity'] ?? '999';
        $valid_purities = ['916', '999', '9999'];
        if (!in_array($purity, $valid_purities)) $purity = '999';

        if (!is_numeric($pts_input) || (float)$pts_input <= 0) $errors[] = 'Masukkan jumlah mata emas yang sah.';
        $pts   = (float)$pts_input;
        $avail = (float)$balance['points'];
        if (empty($errors) && $pts > $avail)
            $errors[] = 'Mata tidak mencukupi. Baki anda: ' . gold_format_points((string)$avail) . ' pts.';

        if (empty($errors)) {
            $price_snap = $sell_price ? (string)$sell_price['price_per_g'] : (string)(get_active_gold_price()['price_per_g'] ?? '390');
            $grams      = gold_grams_from_points((string)$pts);
            $mkt_value  = number_format((float)$grams * (float)$price_snap, 2, '.', '');
            $fin_req    = number_format((float)$financing_req, 2, '.', '');

            $db->prepare("INSERT INTO ar_rahnu_applications
                (user_id, deposit_id, collateral_type, gold_grams, gold_points, gold_purity,
                 market_value_snapshot, financing_requested, bank_name, bank_account, bank_account_name,
                 user_notes, status, created_at, updated_at)
                VALUES (?,NULL,'digital',?,?,?,?,?,?,?,?,'pending',NOW(),NOW())")
              ->execute([$user_id, $grams, $pts, $purity, $mkt_value, $fin_req,
                         $bank_name, $bank_account, $bank_acct_name, $user_notes]);

            flash_set('arrahu', 'Permohonan Ar Rahnu berjaya dihantar! Pasukan kami akan menghubungi anda dalam 2–3 hari bekerja.', 'success');
            redirect(APP_URL . '/ar-rahnu');
        }
    }
}

// ── Load user applications ────────────────────────────────────────────────────
$apps_stmt = $db->prepare("SELECT ara.*, gd.deposit_ref FROM ar_rahnu_applications ara LEFT JOIN gold_deposits gd ON gd.id=ara.deposit_id WHERE ara.user_id=? ORDER BY ara.created_at DESC LIMIT 20");
$apps_stmt->execute([$user_id]);
$applications = $apps_stmt->fetchAll();

$hint_price = $sell_price ? (float)$sell_price['price_per_g'] : (float)(get_active_gold_price()['price_per_g'] ?? 390);
$default_collateral = $preselect_dep ? 'physical' : (empty($active_deposits) ? 'digital' : 'digital');

layout_begin_user('Ar Rahnu — Gadai Emas Islam');
?>
<style>
.collateral-card { border:2px solid #E5E7EB;border-radius:12px;padding:14px 16px;cursor:pointer;transition:.15s; }
.collateral-card:hover, .collateral-card.selected { border-color:var(--gold-dark);background:#FFFBEB; }
</style>

<div class="page-title">🕌 Ar Rahnu — Gadai Emas Islam</div>
<?= flash_html('arrahu') ?>

<!-- Info Banner -->
<div class="card-kasih card-gold" style="margin-bottom:20px;">
  <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
    <div style="font-size:2.5rem;">🕌</div>
    <div style="flex:1;">
      <div style="font-weight:800;font-size:1.05rem;margin-bottom:4px;">Kasih Ar Rahnu — Pembiayaan Patuh Syariah</div>
      <p style="font-size:0.83rem;color:#6B7280;margin-bottom:8px;">Gadaikan emas digital atau emas fizikal deposit anda untuk mendapatkan pembiayaan tunai segera. Patuh prinsip Syariah Islam.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <div style="background:#F0FDF4;border-radius:8px;padding:8px 12px;font-size:0.8rem;"><span style="font-weight:700;color:#065F46;">✅ Patuh Syariah</span></div>
        <div style="background:#EFF6FF;border-radius:8px;padding:8px 12px;font-size:0.8rem;"><span style="font-weight:700;color:#1E40AF;">💰 Sehingga 70% Nilai Emas</span></div>
        <div style="background:#FFF7ED;border-radius:8px;padding:8px 12px;font-size:0.8rem;"><span style="font-weight:700;color:#92400E;">📅 Tempoh 3–12 Bulan</span></div>
      </div>
    </div>
  </div>
</div>

<!-- Application Form -->
<div class="card-kasih" style="margin-bottom:20px;"
     x-data="{
       collateral: '<?= $default_collateral ?>',
       depId: <?= $preselect_dep ? $preselect_dep['id'] : 0 ?>,
       deposits: <?= json_encode(array_map(fn($d)=>['id'=>(int)$d['id'],'ref'=>$d['deposit_ref'],'grams'=>(float)$d['gold_grams'],'purity'=>$d['gold_purity']], $active_deposits)) ?>,
       pts: '',
       pricePerG: <?= $hint_price ?>,
       get selDep() { return this.deposits.find(d=>d.id==this.depId) || null; },
       get depGrams() { return this.selDep ? this.selDep.grams.toFixed(4) : '0.0000'; },
       get depMktVal() { return this.selDep ? (this.selDep.grams * this.pricePerG).toFixed(2) : '0.00'; },
       get depMaxFin() { return this.selDep ? (this.selDep.grams * this.pricePerG * 0.70).toFixed(2) : '0.00'; },
       get digGrams() { return this.pts > 0 ? (parseFloat(this.pts)/1000).toFixed(4) : '0.0000'; },
       get digMktVal() { return this.pts > 0 ? (parseFloat(this.digGrams)*this.pricePerG).toFixed(2) : '0.00'; },
       get digMaxFin() { return this.pts > 0 ? (parseFloat(this.digMktVal)*0.70).toFixed(2) : '0.00'; }
     }">
  <div class="section-title" style="margin-bottom:14px;">📋 Permohonan Ar Rahnu Baharu</div>

  <?php if (!empty($errors)): ?>
  <div style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px;margin-bottom:16px;">
    <?php foreach ($errors as $e): ?><div style="font-size:0.85rem;color:#991B1B;">• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Collateral type selector -->
  <div style="margin-bottom:16px;">
    <div class="form-label">Pilih Jenis Cagaran</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div class="collateral-card" :class="{selected: collateral==='digital'}" @click="collateral='digital'">
        <div style="display:flex;align-items:center;gap:10px;">
          <input type="radio" :checked="collateral==='digital'" @change="collateral='digital'" style="accent-color:var(--gold-dark);">
          <div>
            <div style="font-weight:700;font-size:0.9rem;">💰 Emas Digital</div>
            <div style="font-size:0.75rem;color:#6B7280;">Guna Gold Points dalam wallet anda</div>
            <div style="font-size:0.78rem;font-weight:600;color:var(--gold-dark);margin-top:2px;">
              Baki: <?= gold_format_points($balance['points']) ?> pts
            </div>
          </div>
        </div>
      </div>
      <div class="collateral-card" :class="{selected: collateral==='physical'}" @click="collateral='physical'"
           style="<?= empty($active_deposits) ? 'opacity:.5;cursor:not-allowed;' : '' ?>">
        <div style="display:flex;align-items:center;gap:10px;">
          <input type="radio" :checked="collateral==='physical'" @change="collateral='physical'"
                 <?= empty($active_deposits) ? 'disabled' : '' ?> style="accent-color:var(--gold-dark);">
          <div>
            <div style="font-weight:700;font-size:0.9rem;">🏦 Emas Deposit Fizikal</div>
            <div style="font-size:0.75rem;color:#6B7280;">Guna emas yang sudah didepositkan</div>
            <?php if (!empty($active_deposits)): ?>
            <div style="font-size:0.78rem;font-weight:600;color:#065F46;margin-top:2px;"><?= count($active_deposits) ?> deposit aktif</div>
            <?php else: ?>
            <div style="font-size:0.75rem;color:#9CA3AF;margin-top:2px;">Tiada deposit aktif</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="collateral_type" :value="collateral">

    <!-- Digital mode fields -->
    <div x-show="collateral==='digital'">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
          <label class="form-label">Gold Points untuk Digadai <span style="color:#EF4444;">*</span></label>
          <input type="number" name="gold_points" class="form-input" min="100" step="1"
                 max="<?= floor((float)$balance['points']) ?>"
                 x-model="pts" placeholder="Min: 100 pts">
          <div style="font-size:0.75rem;color:#9CA3AF;margin-top:3px;">Maks: <?= gold_format_points($balance['points']) ?> pts</div>
        </div>
        <div>
          <label class="form-label">Ketulenan</label>
          <select name="gold_purity" class="form-input">
            <option value="999">999 (Emas Tulen)</option>
            <option value="9999">9999 (Super Tulen)</option>
            <option value="916">916 (22 Karat)</option>
          </select>
        </div>
      </div>
      <div x-show="pts > 0" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px;margin-bottom:14px;">
        <div style="font-size:0.75rem;font-weight:600;color:#6B7280;margin-bottom:8px;text-transform:uppercase;">Kalkulator</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Gram</div><div style="font-weight:700;" x-text="digGrams+'g'"></div></div>
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Nilai Pasaran</div><div style="font-weight:700;" x-text="'RM '+digMktVal"></div></div>
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Maks 70%</div><div style="font-weight:800;color:#065F46;" x-text="'RM '+digMaxFin"></div></div>
        </div>
      </div>
    </div>

    <!-- Physical deposit mode fields -->
    <div x-show="collateral==='physical'">
      <div style="margin-bottom:14px;">
        <label class="form-label">Pilih Deposit <span style="color:#EF4444;">*</span></label>
        <select name="deposit_id" class="form-input" x-model="depId">
          <option value="0">— Pilih deposit aktif —</option>
          <?php foreach ($active_deposits as $ad): ?>
          <option value="<?= $ad['id'] ?>" <?= $preselect_dep && $preselect_dep['id']==$ad['id']?'selected':'' ?>>
            <?= h($ad['deposit_ref']) ?> — <?= gold_format_grams($ad['gold_grams']) ?> (<?= h($ad['gold_purity']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div x-show="selDep" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:14px;margin-bottom:14px;">
        <div style="font-size:0.75rem;font-weight:600;color:#6B7280;margin-bottom:8px;text-transform:uppercase;">Butiran Cagaran</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Gram Emas</div><div style="font-weight:700;" x-text="depGrams+'g'"></div></div>
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Nilai Pasaran</div><div style="font-weight:700;" x-text="'RM '+depMktVal"></div></div>
          <div><div style="font-size:0.7rem;color:#9CA3AF;">Maks Pembiayaan (70%)</div><div style="font-weight:800;color:#065F46;" x-text="'RM '+depMaxFin"></div></div>
        </div>
        <div style="font-size:0.75rem;color:#1E40AF;margin-top:8px;">
          ⚠️ Deposit anda akan dikunci (status "Digadai") semasa permohonan Ar Rahnu ini aktif. Faedah deposit tidak terganggu.
        </div>
      </div>
    </div>

    <!-- Financing amount (shared) -->
    <div style="margin-bottom:14px;">
      <label class="form-label">Jumlah Pembiayaan Dipohon (RM) <span style="color:#EF4444;">*</span></label>
      <input type="number" name="financing_requested" class="form-input" step="0.01" min="50"
             placeholder="Contoh: 1000.00"
             :max="collateral==='physical' ? depMaxFin : digMaxFin" value="<?= h($_POST['financing_requested'] ?? '') ?>">
    </div>

    <!-- Bank details -->
    <div class="section-title" style="margin-top:4px;margin-bottom:12px;">🏦 Akaun Bank untuk Disbursemen</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
      <div>
        <label class="form-label" style="font-size:0.78rem;">Nama Bank <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_name" class="form-input" placeholder="CIMB, Maybank..." value="<?= h($_POST['bank_name'] ?? '') ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size:0.78rem;">Nombor Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account" class="form-input" placeholder="1234567890" value="<?= h($_POST['bank_account'] ?? '') ?>" required>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label" style="font-size:0.78rem;">Nama Pemegang Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account_name" class="form-input" value="<?= h($_POST['bank_account_name'] ?? $user['full_name'] ?? '') ?>" required>
      </div>
    </div>

    <div style="margin-bottom:14px;">
      <label class="form-label" style="font-size:0.78rem;">Catatan / Tujuan Pembiayaan</label>
      <textarea name="user_notes" class="form-input" rows="2" placeholder="Nyatakan tujuan jika perlu..."><?= h($_POST['user_notes'] ?? '') ?></textarea>
    </div>

    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:0.8rem;color:#1E40AF;">
      ℹ️ Setelah diluluskan, pasukan Kasih Ar Rahnu akan menghubungi anda. Emas anda dikunci sebagai cagaran semasa tempoh gadaian aktif.
    </div>

    <button type="submit" class="btn-gold" style="width:100%;padding:14px;font-size:1rem;">
      🕌 Hantar Permohonan Ar Rahnu
    </button>
  </form>
</div>

<!-- History -->
<div class="card-kasih">
  <div class="section-title">📋 Sejarah Permohonan</div>
  <?php if (empty($applications)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Tiada permohonan lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#</th><th>Cagaran</th><th>Gram Emas</th><th>Nilai</th><th>Dipohon</th><th>Diluluskan</th><th>Tempoh</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $a['id'] ?></td>
          <td style="font-size:0.8rem;">
            <?php if ($a['collateral_type']==='physical' && $a['deposit_ref']): ?>
              <span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:600;">🏦 <?= h($a['deposit_ref']) ?></span>
            <?php else: ?>
              <span style="background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:600;">💰 Digital</span>
            <?php endif; ?>
          </td>
          <td><?= gold_format_grams($a['gold_grams']) ?></td>
          <td>RM <?= number_format((float)$a['market_value_snapshot'],2) ?></td>
          <td style="font-weight:600;">RM <?= number_format((float)$a['financing_requested'],2) ?></td>
          <td style="font-weight:700;color:#065F46;"><?= $a['financing_approved']?'RM '.number_format((float)$a['financing_approved'],2):'—' ?></td>
          <td style="font-size:0.8rem;">
            <?= $a['tenure_months'] ? $a['tenure_months'].' bln' : '—' ?>
            <?php if ($a['maturity_date']): ?><br><span style="font-size:0.7rem;color:#9CA3AF;"><?= $a['maturity_date'] ?></span><?php endif; ?>
          </td>
          <td><?= status_badge($a['status']) ?>
            <?php if ($a['status']==='rejected' && $a['rejection_reason']): ?>
              <div style="font-size:0.7rem;color:#991B1B;margin-top:2px;"><?= h($a['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($a['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php layout_end_user(); ?>
