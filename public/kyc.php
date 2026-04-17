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

// Load existing submission
$kyc_stmt = $db->prepare("SELECT * FROM kyc_submissions WHERE user_id = ?");
$kyc_stmt->execute([$user_id]);
$kyc = $kyc_stmt->fetch();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Block re-submission if approved
    if ($kyc && $kyc['status'] === 'approved') {
        flash_set('kyc', 'eKYC anda telah disahkan. Tidak perlu hantar semula.', 'info');
        redirect(APP_URL . '/kyc');
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $ic_number = trim($_POST['ic_number'] ?? '');
    $dob       = trim($_POST['date_of_birth'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $state     = trim($_POST['state'] ?? '');
    $postcode  = trim($_POST['postcode'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if (!$full_name)  $errors[] = 'Nama penuh diperlukan.';
    if (!$ic_number)  $errors[] = 'Nombor IC diperlukan.';
    if (!$dob)        $errors[] = 'Tarikh lahir diperlukan.';
    if (!$address)    $errors[] = 'Alamat diperlukan.';
    if (!$city)       $errors[] = 'Bandar diperlukan.';
    if (!$state)      $errors[] = 'Negeri diperlukan.';
    if (!$postcode)   $errors[] = 'Poskod diperlukan.';

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size      = 5 * 1024 * 1024; // 5MB

    $ic_front_file = $_FILES['ic_front'] ?? null;
    $ic_back_file  = $_FILES['ic_back']  ?? null;

    // If editing existing, keep old images if no new upload
    $ic_front_saved = $kyc['ic_front'] ?? '';
    $ic_back_saved  = $kyc['ic_back']  ?? '';

    $kyc_dir = UPLOAD_PATH . '/kyc';
    if (!is_dir($kyc_dir)) mkdir($kyc_dir, 0755, true);

    // IC Front
    if (!empty($ic_front_file['tmp_name'])) {
        if ($ic_front_file['error'] !== UPLOAD_ERR_OK)    $errors[] = 'Ralat memuat naik IC Hadapan.';
        elseif ($ic_front_file['size'] > $max_size)        $errors[] = 'IC Hadapan melebihi 5MB.';
        elseif (!in_array($ic_front_file['type'], $allowed_types)) $errors[] = 'IC Hadapan mestilah JPG/PNG/WebP.';
        else {
            $ext             = pathinfo($ic_front_file['name'], PATHINFO_EXTENSION);
            $ic_front_saved  = 'kyc_front_' . $user_id . '_' . time() . '.' . strtolower($ext);
            if (!move_uploaded_file($ic_front_file['tmp_name'], $kyc_dir . '/' . $ic_front_saved)) {
                $errors[] = 'Gagal menyimpan IC Hadapan.';
                $ic_front_saved = $kyc['ic_front'] ?? '';
            }
        }
    } elseif (!$ic_front_saved) {
        $errors[] = 'Sila muat naik gambar IC Hadapan.';
    }

    // IC Back
    if (!empty($ic_back_file['tmp_name'])) {
        if ($ic_back_file['error'] !== UPLOAD_ERR_OK)    $errors[] = 'Ralat memuat naik IC Belakang.';
        elseif ($ic_back_file['size'] > $max_size)        $errors[] = 'IC Belakang melebihi 5MB.';
        elseif (!in_array($ic_back_file['type'], $allowed_types)) $errors[] = 'IC Belakang mestilah JPG/PNG/WebP.';
        else {
            $ext            = pathinfo($ic_back_file['name'], PATHINFO_EXTENSION);
            $ic_back_saved  = 'kyc_back_' . $user_id . '_' . time() . '.' . strtolower($ext);
            if (!move_uploaded_file($ic_back_file['tmp_name'], $kyc_dir . '/' . $ic_back_saved)) {
                $errors[] = 'Gagal menyimpan IC Belakang.';
                $ic_back_saved = $kyc['ic_back'] ?? '';
            }
        }
    } elseif (!$ic_back_saved) {
        $errors[] = 'Sila muat naik gambar IC Belakang.';
    }

    if (empty($errors)) {
        if ($kyc) {
            $db->prepare("UPDATE kyc_submissions SET
                full_name=?, ic_number=?, date_of_birth=?, address=?, city=?, state=?, postcode=?, phone=?,
                ic_front=?, ic_back=?, status='pending', rejection_reason=NULL, reviewed_by=NULL, reviewed_at=NULL,
                submitted_at=NOW(), updated_at=NOW()
                WHERE user_id=?")
              ->execute([$full_name, $ic_number, $dob, $address, $city, $state, $postcode, $phone,
                         $ic_front_saved, $ic_back_saved, $user_id]);
        } else {
            $db->prepare("INSERT INTO kyc_submissions
                (user_id, full_name, ic_number, date_of_birth, address, city, state, postcode, phone,
                 ic_front, ic_back, status, submitted_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending',NOW(),NOW())")
              ->execute([$user_id, $full_name, $ic_number, $dob, $address, $city, $state, $postcode, $phone,
                         $ic_front_saved, $ic_back_saved]);
        }
        flash_set('kyc', 'Permohonan eKYC berjaya dihantar. Sila tunggu kelulusan admin.', 'success');
        redirect(APP_URL . '/kyc');
    }

    // Repopulate on error
    $kyc = array_merge($kyc ?: [], compact('full_name','ic_number','dob','address','city','state','postcode','phone'));
}

$flash = flash_html('kyc');

$malaysia_states = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Perak','Perlis',
    'Pulau Pinang','Sabah','Sarawak','Selangor','Terengganu','Kuala Lumpur','Labuan','Putrajaya'];

layout_begin_user('eKYC — Pengesahan Identiti');
?>

<div class="page-title">🪪 Pengesahan Identiti (eKYC)</div>
<?= $flash ?>

<?php
$status = $kyc['status'] ?? null;

if ($status === 'approved'): ?>
<div class="card-kasih" style="border-left:4px solid #10B981;margin-bottom:20px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="font-size:2.5rem;">✅</div>
    <div>
      <div style="font-weight:700;font-size:1.05rem;color:#065F46;">eKYC Disahkan</div>
      <div style="font-size:0.85rem;color:#6B7280;margin-top:2px;">
        Identiti anda telah disahkan pada <?= format_date($kyc['reviewed_at']) ?>.
        Anda kini boleh menggunakan semua ciri platform sepenuhnya.
      </div>
    </div>
  </div>
</div>

<!-- Show submitted details (read-only) -->
<div class="card-kasih">
  <div class="section-title">📋 Maklumat Dihantar</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div><div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:2px;">Nama Penuh</div><div style="font-weight:600;"><?= h($kyc['full_name']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:2px;">No. IC</div><div style="font-weight:600;"><?= h($kyc['ic_number']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:2px;">Tarikh Lahir</div><div style="font-weight:600;"><?= h($kyc['date_of_birth']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:2px;">No. Telefon</div><div style="font-weight:600;"><?= h($kyc['phone'] ?: '—') ?></div></div>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:2px;">Alamat</div><div style="font-weight:600;"><?= h($kyc['address']) ?>, <?= h($kyc['postcode']) ?> <?= h($kyc['city']) ?>, <?= h($kyc['state']) ?></div></div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
    <div>
      <div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:6px;">IC Hadapan</div>
      <img src="<?= APP_URL ?>/kyc-image.php?f=<?= h($kyc['ic_front']) ?>" alt="IC Front"
           style="width:100%;max-width:280px;border-radius:8px;border:1px solid #E5E7EB;">
    </div>
    <div>
      <div style="font-size:0.75rem;color:#9CA3AF;margin-bottom:6px;">IC Belakang</div>
      <img src="<?= APP_URL ?>/kyc-image.php?f=<?= h($kyc['ic_back']) ?>" alt="IC Back"
           style="width:100%;max-width:280px;border-radius:8px;border:1px solid #E5E7EB;">
    </div>
  </div>
</div>

<?php elseif ($status === 'pending'): ?>
<div class="card-kasih" style="border-left:4px solid #F59E0B;margin-bottom:20px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="font-size:2.5rem;">⏳</div>
    <div>
      <div style="font-weight:700;font-size:1.05rem;color:#92400E;">Dalam Semakan</div>
      <div style="font-size:0.85rem;color:#6B7280;margin-top:2px;">
        Permohonan eKYC anda sedang disemak oleh admin. Proses ini mengambil masa 1–3 hari bekerja.
      </div>
      <div style="font-size:0.78rem;color:#9CA3AF;margin-top:4px;">Dihantar: <?= format_date($kyc['submitted_at']) ?></div>
    </div>
  </div>
</div>

<?php else: ?>

<?php if ($status === 'rejected'): ?>
<div class="alert alert-error" style="margin-bottom:20px;">
  <strong>❌ Permohonan Ditolak</strong><br>
  Sebab: <?= h($kyc['rejection_reason'] ?: 'Sila hubungi admin untuk maklumat lanjut.') ?><br>
  <small style="color:#9CA3AF;">Ditolak pada: <?= format_date($kyc['reviewed_at']) ?></small><br>
  <small style="margin-top:6px;display:block;">Anda boleh hantar semula permohonan di bawah.</small>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:16px;">
  <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Submission form -->
<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card-kasih" style="margin-bottom:16px;">
    <div class="section-title">👤 Maklumat Peribadi</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

      <div style="grid-column:1/-1;">
        <label class="form-label">Nama Penuh (seperti dalam IC) <span style="color:#EF4444;">*</span></label>
        <input type="text" name="full_name" class="form-input"
               value="<?= h($kyc['full_name'] ?? $user['full_name'] ?? '') ?>"
               placeholder="Contoh: Ahmad bin Kassim" required>
      </div>

      <div>
        <label class="form-label">Nombor IC (tanpa sempang) <span style="color:#EF4444;">*</span></label>
        <input type="text" name="ic_number" class="form-input"
               value="<?= h($kyc['ic_number'] ?? '') ?>"
               placeholder="Contoh: 900101012345" maxlength="20" required>
      </div>

      <div>
        <label class="form-label">Tarikh Lahir <span style="color:#EF4444;">*</span></label>
        <input type="date" name="date_of_birth" class="form-input"
               value="<?= h($kyc['date_of_birth'] ?? $kyc['dob'] ?? '') ?>" required>
      </div>

      <div>
        <label class="form-label">No. Telefon</label>
        <input type="tel" name="phone" class="form-input"
               value="<?= h($kyc['phone'] ?? $user['phone'] ?? '') ?>"
               placeholder="Contoh: 0123456789">
      </div>

    </div>
  </div>

  <div class="card-kasih" style="margin-bottom:16px;">
    <div class="section-title">🏠 Alamat Kediaman</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

      <div style="grid-column:1/-1;">
        <label class="form-label">Alamat Penuh <span style="color:#EF4444;">*</span></label>
        <textarea name="address" class="form-input" rows="2"
                  placeholder="No. rumah, jalan, taman..." required><?= h($kyc['address'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="form-label">Poskod <span style="color:#EF4444;">*</span></label>
        <input type="text" name="postcode" class="form-input"
               value="<?= h($kyc['postcode'] ?? '') ?>"
               placeholder="Contoh: 47500" maxlength="10" required>
      </div>

      <div>
        <label class="form-label">Bandar <span style="color:#EF4444;">*</span></label>
        <input type="text" name="city" class="form-input"
               value="<?= h($kyc['city'] ?? '') ?>"
               placeholder="Contoh: Subang Jaya" required>
      </div>

      <div>
        <label class="form-label">Negeri <span style="color:#EF4444;">*</span></label>
        <select name="state" class="form-input" required>
          <option value="">-- Pilih Negeri --</option>
          <?php foreach ($malaysia_states as $s): ?>
          <option value="<?= h($s) ?>" <?= ($kyc['state'] ?? '') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>
  </div>

  <div class="card-kasih" style="margin-bottom:20px;">
    <div class="section-title">📷 Gambar Kad Pengenalan (IC)</div>
    <p style="font-size:0.82rem;color:#6B7280;margin-bottom:16px;">
      Muat naik gambar jelas IC hadapan dan belakang. Format: JPG, PNG atau WebP. Saiz maksimum: 5MB setiap satu.
    </p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

      <div>
        <label class="form-label">IC Hadapan <span style="color:#EF4444;">*</span></label>
        <?php if (!empty($kyc['ic_front']) && $status === 'rejected'): ?>
        <div style="margin-bottom:8px;">
          <img src="<?= APP_URL ?>/kyc-image.php?f=<?= h($kyc['ic_front']) ?>" alt="IC Front"
               style="width:100%;max-width:200px;border-radius:6px;border:1px solid #E5E7EB;">
          <div style="font-size:0.72rem;color:#9CA3AF;margin-top:2px;">Gambar semasa (ganti jika perlu)</div>
        </div>
        <?php endif; ?>
        <div class="upload-zone" style="border:2px dashed #D1D5DB;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
             onclick="document.getElementById('ic_front').click()">
          <div id="ic_front_preview" style="display:none;margin-bottom:8px;">
            <img id="ic_front_img" src="" alt="Preview" style="max-width:100%;max-height:120px;border-radius:6px;">
          </div>
          <div id="ic_front_placeholder">
            <div style="font-size:2rem;margin-bottom:4px;">🪪</div>
            <div style="font-size:0.82rem;color:#6B7280;">Klik untuk muat naik IC Hadapan</div>
          </div>
        </div>
        <input type="file" id="ic_front" name="ic_front" accept="image/jpeg,image/png,image/webp"
               style="display:none;" onchange="previewImage(this,'ic_front_img','ic_front_preview','ic_front_placeholder')">
      </div>

      <div>
        <label class="form-label">IC Belakang <span style="color:#EF4444;">*</span></label>
        <?php if (!empty($kyc['ic_back']) && $status === 'rejected'): ?>
        <div style="margin-bottom:8px;">
          <img src="<?= APP_URL ?>/kyc-image.php?f=<?= h($kyc['ic_back']) ?>" alt="IC Back"
               style="width:100%;max-width:200px;border-radius:6px;border:1px solid #E5E7EB;">
          <div style="font-size:0.72rem;color:#9CA3AF;margin-top:2px;">Gambar semasa (ganti jika perlu)</div>
        </div>
        <?php endif; ?>
        <div class="upload-zone" style="border:2px dashed #D1D5DB;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border-color 0.2s;"
             onclick="document.getElementById('ic_back').click()">
          <div id="ic_back_preview" style="display:none;margin-bottom:8px;">
            <img id="ic_back_img" src="" alt="Preview" style="max-width:100%;max-height:120px;border-radius:6px;">
          </div>
          <div id="ic_back_placeholder">
            <div style="font-size:2rem;margin-bottom:4px;">🪪</div>
            <div style="font-size:0.82rem;color:#6B7280;">Klik untuk muat naik IC Belakang</div>
          </div>
        </div>
        <input type="file" id="ic_back" name="ic_back" accept="image/jpeg,image/png,image/webp"
               style="display:none;" onchange="previewImage(this,'ic_back_img','ic_back_preview','ic_back_placeholder')">
      </div>

    </div>
  </div>

  <div style="text-align:center;">
    <button type="submit" class="btn-gold" style="padding:12px 40px;font-size:1rem;">
      🪪 Hantar Permohonan eKYC
    </button>
    <p style="font-size:0.75rem;color:#9CA3AF;margin-top:10px;">
      Maklumat anda dilindungi dan hanya digunakan untuk pengesahan identiti.
    </p>
  </div>

</form>

<script>
function previewImage(input, imgId, previewId, placeholderId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(previewId).style.display = 'block';
            document.getElementById(placeholderId).style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php endif; ?>

<?php layout_end_user(); ?>
