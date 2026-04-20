<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = sanitize_string($_POST['action'] ?? '');
    if ($action === 'toggle') {
        $cid = (int)$_POST['campaign_id'];
        $active_new = (int)$_POST['is_active_new'];
        $db->prepare("UPDATE campaigns SET is_active=? WHERE id=?")->execute([$active_new,$cid]);
        flash_set('main','Status kempen dikemaskini.','success');
        redirect(APP_URL . '/admin/campaigns');
    } elseif ($action === 'create') {
        $title  = sanitize_string($_POST['title']??'');
        $type   = sanitize_string($_POST['type']??'general');
        $desc   = sanitize_string($_POST['description']??'',2000);
        $target_pts = (float)$_POST['target_points']??0;
        $monthly    = (float)$_POST['monthly_suggested']??0;
        $target_date = sanitize_string($_POST['target_date']??'');
        $cta    = sanitize_string($_POST['cta_text']??'');
        if (!$title) { $errors['title']='Tajuk kempen diperlukan.'; }
        if (empty($errors)) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/','- ',iconv('UTF-8','ASCII//TRANSLIT',$title)),'-'));
            $slug .= '-' . time();
            $db->prepare("INSERT INTO campaigns (title,slug,description,type,target_points,monthly_suggested_contribution,target_date,cta_text,is_active,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,?,NOW(),NOW())")
               ->execute([$title,$slug,$desc,$type,$target_pts ?: null,$monthly ?: null,$target_date ?: null,$cta,$admin['id']]);
            flash_set('main','Kempen baharu berjaya dicipta.','success');
            redirect(APP_URL . '/admin/campaigns');
        }
    }
}

$campaigns = $db->query("SELECT c.*, (SELECT COUNT(*) FROM user_campaigns uc WHERE uc.campaign_id=c.id) AS participant_count FROM campaigns c ORDER BY c.created_at DESC")->fetchAll();

layout_begin_admin('Pengurusan Kempen');
?>
<div class="page-title">🎯 Pengurusan Kempen</div>
<?= flash_html('main') ?>
<?= validation_errors($errors) ?>

<!-- Create form -->
<div class="card-kasih card-gold" style="margin-bottom:20px;">
  <div class="section-title">+ Cipta Kempen Baharu</div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="form-group"><label class="label-kasih">Tajuk <span class="required">*</span></label><input type="text" name="title" class="input-kasih" value="<?= h($_POST['title']??'') ?>" required></div>
      <div class="form-group"><label class="label-kasih">Jenis</label>
        <select name="type" class="select-kasih">
          <?php foreach (['funeral_savings'=>'Tabung Pengebumian','family_savings'=>'Simpanan Keluarga','child_savings'=>'Simpanan Anak','senior_care'=>'Warga Emas','koperasi'=>'Koperasi','general'=>'Umum'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($_POST['type']??'')===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="label-kasih">Sasaran Mata</label><input type="number" name="target_points" class="input-kasih" step="0.0001" value="<?= h($_POST['target_points']??'') ?>" placeholder="100000"></div>
      <div class="form-group"><label class="label-kasih">Cadangan Bulanan (pts)</label><input type="number" name="monthly_suggested" class="input-kasih" step="0.0001" value="<?= h($_POST['monthly_suggested']??'') ?>"></div>
      <div class="form-group"><label class="label-kasih">Tarikh Sasaran</label><input type="date" name="target_date" class="input-kasih" value="<?= h($_POST['target_date']??'') ?>"></div>
      <div class="form-group"><label class="label-kasih">Teks CTA</label><input type="text" name="cta_text" class="input-kasih" value="<?= h($_POST['cta_text']??'') ?>" placeholder="Sertai Kempen"></div>
      <div class="form-group" style="grid-column:1/-1;"><label class="label-kasih">Keterangan</label><textarea name="description" class="textarea-kasih" rows="3"><?= h($_POST['description']??'') ?></textarea></div>
    </div>
    <button type="submit" class="btn-gold">+ Cipta Kempen</button>
  </form>
</div>

<!-- List -->
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Tajuk</th><th>Jenis</th><th>Sasaran (pts)</th><th>Peserta</th><th>Status</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($campaigns as $c): ?>
        <tr>
          <td style="font-weight:600;"><?= h($c['title']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= h($c['type']) ?></td>
          <td><?= $c['target_points'] ? gold_format_points($c['target_points']) : '—' ?></td>
          <td style="font-weight:600;"><?= $c['participant_count'] ?></td>
          <td><?= $c['is_active'] ? '<span class="badge badge-active">Aktif</span>' : '<span class="badge badge-cancelled">Tidak Aktif</span>' ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="is_active_new" value="<?= $c['is_active'] ? 0 : 1 ?>">
              <button type="submit" class="<?= $c['is_active']?'btn-danger':'btn-gold' ?> btn-sm" style="padding:3px 8px;">
                <?= $c['is_active'] ? 'Nyahaktif' : 'Aktifkan' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end_admin(); ?>
