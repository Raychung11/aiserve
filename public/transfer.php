<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user_id = auth_id();
$user    = auth_user();
$balance = get_wallet_balance($user_id);
$price   = get_active_gold_price();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $identifier = sanitize_string($_POST['recipient_identifier'] ?? '');
    $points_str = sanitize_string($_POST['transfer_points'] ?? '');
    $note       = sanitize_string($_POST['note'] ?? '', 200);

    $req = validate_required($_POST, ['recipient_identifier', 'transfer_points']);
    $errors = array_merge($errors, $req);

    $points = (float)$points_str;
    if ($points <= 0) $errors['transfer_points'] = 'Jumlah mata mesti lebih dari 0.';

    // Check balance
    if ($points > (float)$balance['points']) {
        $errors['transfer_points'] = 'Baki mata tidak mencukupi. Baki semasa: ' . gold_format_points($balance['points']) . ' pts.';
    }

    if (empty($errors)) {
        $db = getDB();
        // Find recipient by email or referral_code
        $stmt = $db->prepare("SELECT id, full_name, email, status FROM users WHERE (email=? OR referral_code=?) AND id != ? LIMIT 1");
        $stmt->execute([$identifier, strtoupper($identifier), $user_id]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            $errors['recipient_identifier'] = 'Penerima tidak dijumpai. Semak e-mel atau kod rujukan.';
        } elseif ($recipient['status'] !== 'active') {
            $errors['recipient_identifier'] = 'Akaun penerima tidak aktif.';
        }
    }

    if (empty($errors) && isset($recipient)) {
        $db = getDB();
        $price_snap = $price ? (string)$price['price_per_g'] : '390.0000';
        $pts_str    = number_format($points, GOLD_POINT_DECIMALS, '.', '');
        $grams      = gold_grams_from_points($pts_str);
        $rm_val     = gold_rm_from_points($pts_str, $price_snap);

        // Atomic transfer
        $db->beginTransaction();
        try {
            $sender_wallet   = get_wallet($user_id);
            $receiver_wallet_id = ensure_wallet_exists((int)$recipient['id']);
            $receiver_wallet = get_wallet((int)$recipient['id']);

            // Create transfer record
            $db->prepare("INSERT INTO wallet_transfers (sender_user_id,receiver_user_id,points,grams,price_per_g_snapshot,rm_reference_value,transfer_status,note,created_at) VALUES (?,?,?,?,?,?,'pending',?,NOW())")
               ->execute([$user_id, $recipient['id'], $pts_str, $grams, $price_snap, $rm_val, $note]);
            $transfer_id = (int)$db->lastInsertId();

            // Debit sender
            $debit_id = ledger_debit((int)$sender_wallet['id'], $user_id, $pts_str, $grams, $rm_val, $price_snap, 'transfer_out', $transfer_id, 'Pindahan kepada ' . $recipient['full_name'] . ($note ? " — {$note}" : ''));

            // Credit receiver
            $credit_id = ledger_credit($receiver_wallet_id, (int)$recipient['id'], $pts_str, $grams, $rm_val, $price_snap, 'transfer_in', $transfer_id, 'Pindahan dari ' . $user['full_name'] . ($note ? " — {$note}" : ''));

            // Update transfer record
            $db->prepare("UPDATE wallet_transfers SET transfer_status='completed',sender_ledger_id=?,receiver_ledger_id=?,completed_at=NOW() WHERE id=?")
               ->execute([$debit_id, $credit_id, $transfer_id]);

            $db->commit();

            audit_log($user_id, 'user', 'points_transfer', 'wallet_transfers', $transfer_id, null, ['to' => $recipient['email'], 'points' => $pts_str]);
            flash_set('main', gold_format_points($pts_str) . ' Gold Points berjaya dipindahkan kepada ' . $recipient['full_name'] . '.', 'success');
            redirect(APP_URL . '/wallet');
        } catch (\Throwable $e) {
            $db->rollBack();
            $errors['general'] = 'Pindahan gagal. Sila cuba lagi. (' . $e->getMessage() . ')';
        }
    }
}

layout_begin_user('Pindah Gold Points');
?>
<div style="max-width:520px;margin:0 auto;">
  <div class="page-title">↔️ Pindah Gold Points</div>

  <!-- Current Balance -->
  <div class="card-wallet" style="margin-bottom:20px;">
    <div class="wallet-balance-label">Baki Semasa</div>
    <div class="wallet-balance-points"><?= gold_format_points($balance['points']) ?> <span style="font-size:1rem;font-weight:400;">pts</span></div>
    <div class="wallet-balance-rm"><?= gold_format_rm($balance['rm_value']) ?></div>
  </div>

  <div class="card-kasih card-gold">
    <?= flash_html('main') ?>
    <?= validation_errors($errors) ?>

    <?php if (isset($errors['general'])): ?>
      <div class="alert alert-error"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" id="transfer_form">
      <?= csrf_field() ?>
      <?php if ($price): ?>
        <input type="hidden" id="current_gold_price" value="<?= h((string)$price['price_per_g']) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="label-kasih" for="recipient_identifier">Penerima (E-mel atau Kod Rujukan) <span class="required">*</span></label>
        <input type="text" id="recipient_identifier" name="recipient_identifier" class="input-kasih"
               value="<?= h($_POST['recipient_identifier'] ?? '') ?>"
               placeholder="E-mel atau Kod Rujukan penerima" required autocomplete="off">
        <?php if (isset($errors['recipient_identifier'])): ?><span class="error-text"><?= h($errors['recipient_identifier']) ?></span><?php endif; ?>
        <span class="help-text">Contoh: rakan@email.com atau USR00001</span>
      </div>

      <div class="form-group">
        <label class="label-kasih" for="transfer_points">Jumlah Mata <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">pts</span>
          <input type="number" id="transfer_points" name="transfer_points" class="input-kasih"
                 min="1" max="<?= number_format((float)$balance['points'], 4, '.', '') ?>"
                 step="0.0001" placeholder="0.0000" required
                 value="<?= h($_POST['transfer_points'] ?? '') ?>">
        </div>
        <?php if (isset($errors['transfer_points'])): ?><span class="error-text"><?= h($errors['transfer_points']) ?></span><?php endif; ?>
      </div>

      <?php if ($price): ?>
      <div class="calc-panel" style="margin-bottom:16px;">
        <div class="calc-result-row"><span class="calc-result-label">Nilai Anggaran</span><span class="calc-result-value" id="estimated_rm_value">—</span></div>
        <div class="calc-result-row"><span class="calc-result-label">Emas Setara</span><span class="calc-result-value" id="estimated_grams">—</span></div>
      </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="label-kasih" for="note">Nota (Pilihan)</label>
        <input type="text" id="note" name="note" class="input-kasih"
               value="<?= h($_POST['note'] ?? '') ?>" placeholder="Sebab pindahan (pilihan)" maxlength="200">
      </div>

      <div class="alert alert-warning" style="font-size:0.8rem;">
        ⚠️ Pindahan <strong>tidak boleh dibatalkan</strong> setelah disahkan. Pastikan penerima adalah betul.
      </div>

      <button type="submit" class="btn-gold btn-block btn-lg">Teruskan Pindahan →</button>
    </form>
  </div>
</div>
<?php layout_end_user(); ?>
