<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = sanitize_string($_POST['action']??'');
    if ($action === 'save_settings') {
        set_setting('ai_model',         sanitize_string($_POST['ai_model']??'claude-sonnet-4-6'),   (int)$admin['id']);
        set_setting('ai_assistant_enabled', sanitize_string($_POST['ai_enabled']??'1'),             (int)$admin['id']);
        set_setting('ai_system_prompt', sanitize_string($_POST['ai_system_prompt']??'',5000),       (int)$admin['id']);
        audit_log((int)$admin['id'],'super_admin','ai_settings_updated','settings',0,null,['model'=>$_POST['ai_model']??'']);
        flash_set('main','Tetapan AI berjaya disimpan.','success');
        redirect(APP_URL . '/admin/ai-settings');
    }
}

$ai_model   = get_setting('ai_model','claude-sonnet-4-6');
$ai_enabled = get_setting('ai_assistant_enabled','1');
$ai_prompt  = get_setting('ai_system_prompt','');

// Recent AI conversations
$convs = $db->query("SELECT ac.*, u.full_name FROM ai_conversations ac LEFT JOIN users u ON u.id=ac.user_id ORDER BY ac.created_at DESC LIMIT 20")->fetchAll();

layout_begin_admin('Tetapan AI');
?>
<div class="page-title">🤖 Tetapan Pembantu AI</div>
<?= flash_html('main') ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
  <div class="card-kasih card-gold">
    <div class="section-title">⚙️ Konfigurasi AI</div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_settings">
      <div class="form-group">
        <label class="label-kasih">Status AI</label>
        <select name="ai_enabled" class="select-kasih">
          <option value="1" <?= $ai_enabled==='1'?'selected':'' ?>>Aktif</option>
          <option value="0" <?= $ai_enabled==='0'?'selected':'' ?>>Tidak Aktif</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label-kasih">Model AI</label>
        <select name="ai_model" class="select-kasih">
          <option value="claude-sonnet-4-6" <?= $ai_model==='claude-sonnet-4-6'?'selected':'' ?>>Claude Sonnet 4.6 (Disyorkan)</option>
          <option value="claude-opus-4-6"   <?= $ai_model==='claude-opus-4-6'?'selected':'' ?>>Claude Opus 4.6</option>
          <option value="claude-haiku-4-5-20251001" <?= $ai_model==='claude-haiku-4-5-20251001'?'selected':'' ?>>Claude Haiku 4.5 (Pantas)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="label-kasih">System Prompt</label>
        <textarea name="ai_system_prompt" class="textarea-kasih" rows="8"><?= h($ai_prompt) ?></textarea>
        <span class="help-text">Prompt ini mengawal tingkah laku AI. Pastikan ia sentiasa merujuk harga emas terkini dan tidak memalsukan transaksi.</span>
      </div>
      <button type="submit" class="btn-gold btn-block">Simpan Tetapan AI</button>
    </form>
  </div>

  <div class="card-kasih">
    <div class="section-title">ℹ️ Panduan Integrasi AI</div>
    <div style="font-size:0.85rem;line-height:1.8;color:#374151;">
      <p><strong>Endpoint API:</strong></p>
      <code style="background:#F3F4F6;display:block;padding:8px 12px;border-radius:6px;font-size:0.78rem;margin-bottom:12px;">POST /api/ai-chat</code>
      <p><strong>Parameter:</strong></p>
      <ul style="padding-left:16px;color:#6B7280;font-size:0.82rem;">
        <li><code>message</code> — Soalan pengguna</li>
        <li><code>session_token</code> — Token sesi (pilihan)</li>
      </ul>
      <p style="margin-top:12px;"><strong>Tindakan yang disokong:</strong></p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;">
        <?php foreach (['buy','transfer','check_wallet','list_marketplace','referral_summary','zakat_estimate','help','simulate_only'] as $action): ?>
        <span class="badge badge-processing"><?= $action ?></span>
        <?php endforeach; ?>
      </div>
      <p style="margin-top:12px;color:#DC2626;font-size:0.8rem;">⚠️ AI tidak boleh sahkan transaksi sebenar. Ia adalah panduan dan anggaran sahaja.</p>
    </div>
  </div>
</div>

<!-- Recent conversations -->
<div class="card-kasih">
  <div class="section-title">💬 Perbualan AI Terkini</div>
  <?php if (empty($convs)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Tiada perbualan AI lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Pengguna</th><th>Peranan</th><th>Mesej</th><th>Tindakan</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($convs as $c): ?>
        <tr>
          <td style="font-size:0.82rem;"><?= h($c['full_name'] ?? 'Tetamu') ?></td>
          <td><?= status_badge($c['actor_role']) ?></td>
          <td style="font-size:0.8rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($c['message']) ?>"><?= h(substr($c['message'],0,80)) ?></td>
          <td style="font-size:0.78rem;"><?= $c['action_detected'] ? h($c['action_detected']) : '—' ?></td>
          <td style="font-size:0.72rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php layout_end_admin(); ?>
