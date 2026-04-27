<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'cp58';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

$year = (int)($_GET['year'] ?? date('Y') - 1);   // default: prior year (tax season)
$sst  = Database::fetchOne("SELECT * FROM sst_settings WHERE tenant_id=?", [$_tenantId])
      ?: ['company_name'=>'','company_address'=>'','company_tin'=>''];

// ── POST HANDLER ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Save / update a CP58 record
    if ($action === 'save') {
        $agentId = (int)$_POST['agent_id'];
        $agent   = Database::fetchOne("SELECT * FROM users WHERE id=? AND tenant_id=? AND role='agent'", [$agentId, $_tenantId]);
        if (!$agent) { $_SESSION['flash']=['error'=>'Agent not found.']; header('Location: '.APP_URL.'/cp58?year='.$year); exit; }

        $months = ['jan','feb','mar','apr','may_amt','jun','jul','aug','sep','oct','nov','dec_amt'];
        $data   = ['tenant_id'=>$_tenantId,'year'=>$year,'agent_id'=>$agentId,
                   'agent_name'=>$agent['name'],
                   'agent_ic'  => trim($_POST['agent_ic']  ?? $agent['ic_number'] ?? ''),
                   'agent_tin' => trim($_POST['agent_tin'] ?? ''),
                   'agent_address' => trim($_POST['agent_address'] ?? ''),
                   'notes'     => trim($_POST['notes'] ?? ''),
                   'created_by'=> $_user['id'],
                   'updated_at'=> date('Y-m-d H:i:s'),];
        $total = 0;
        foreach ($months as $m) {
            $val = (float)($_POST[$m] ?? 0);
            $data[$m] = $val;
            $total += $val;
        }
        $data['total_commission'] = $total;

        if (Database::count('cp58_records','tenant_id=? AND agent_id=? AND year=?',[$_tenantId,$agentId,$year])) {
            Database::update('cp58_records', $data, 'tenant_id=? AND agent_id=? AND year=?', [$_tenantId,$agentId,$year]);
        } else {
            $data['status']     = 'draft';
            $data['created_at'] = date('Y-m-d H:i:s');
            Database::insert('cp58_records', $data);
        }
        ActivityLog::record('cp58.saved',"CP58 {$year} saved for agent #{$agentId}",$_tenantId,$_user['id']);
        $_SESSION['flash'] = ['success'=>'CP58 record saved.'];
        header('Location: '.APP_URL.'/cp58?year='.$year); exit;
    }

    // Issue CP58
    if ($action === 'issue') {
        $id = (int)$_POST['record_id'];
        Database::update('cp58_records',['status'=>'issued','issued_date'=>date('Y-m-d'),'updated_at'=>date('Y-m-d H:i:s')],'id=? AND tenant_id=?',[$id,$_tenantId]);
        ActivityLog::record('cp58.issued',"CP58 record #$id issued",$_tenantId,$_user['id']);
        $_SESSION['flash'] = ['success'=>'CP58 issued.'];
        header('Location: '.APP_URL.'/cp58?year='.$year); exit;
    }

    // Delete draft
    if ($action === 'delete') {
        $id = (int)$_POST['record_id'];
        Database::delete('cp58_records','id=? AND tenant_id=? AND status="draft"',[$id,$_tenantId]);
        ActivityLog::record('cp58.deleted',"CP58 record #$id deleted",$_tenantId,$_user['id']);
        $_SESSION['flash'] = ['success'=>'Record deleted.'];
        header('Location: '.APP_URL.'/cp58?year='.$year); exit;
    }
}

// ── PRINT SINGLE CP58 ─────────────────────────────────────────────────────────
if (isset($_GET['print'])) {
    $rec = Database::fetchOne("SELECT * FROM cp58_records WHERE id=? AND tenant_id=?", [(int)$_GET['print'], $_tenantId]);
    if (!$rec) { header('Location: '.APP_URL.'/cp58'); exit; }
    $months = ['jan'=>'Jan','feb'=>'Feb','mar'=>'Mar','apr'=>'Apr','may_amt'=>'May','jun'=>'Jun',
               'jul'=>'Jul','aug'=>'Aug','sep'=>'Sep','oct'=>'Oct','nov'=>'Nov','dec_amt'=>'Dec'];
    ?>
    <!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>CP58 — <?= htmlspecialchars($rec['agent_name']) ?> — <?= $rec['year'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    @media print{.no-print{display:none!important;}body{-webkit-print-color-adjust:exact;}}
    body{font-family:'Segoe UI',sans-serif;background:#fff;}
    .cp58-border{border:2px solid #0f172a;}
    .field-line{border-bottom:1px solid #333;min-height:22px;padding:2px 4px;}
    </style></head><body>
    <div class="no-print p-3 d-flex gap-2" style="background:#f1f5f9;border-bottom:1px solid #e2e8f0;">
      <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
      <a href="<?= APP_URL ?>/cp58?year=<?= $rec['year'] ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
    </div>

    <div style="max-width:720px;margin:2rem auto;padding:1rem;">
      <div class="cp58-border p-4">
        <!-- Header -->
        <div class="text-center mb-3 pb-2" style="border-bottom:2px solid #0f172a;">
          <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">LEMBAGA HASIL DALAM NEGERI MALAYSIA</div>
          <h4 class="fw-bold my-1" style="font-size:1.1rem;">BORANG CP58</h4>
          <div style="font-size:.8rem;">Penyata Komisyen / Yuran kepada Ejen / Perunding / Pengedar</div>
          <div style="font-size:.75rem;color:#64748b;">Statement of Commission / Fees to Agent / Dealer / Distributor</div>
          <div class="fw-bold mt-1" style="font-size:.9rem;">TAHUN / YEAR: <?= $rec['year'] ?></div>
        </div>

        <!-- Payer section -->
        <div class="mb-3">
          <div class="fw-bold mb-2" style="font-size:.8rem;text-transform:uppercase;background:#f8fafc;padding:.25rem .5rem;">Bahagian A: Maklumat Pembayar / Section A: Payer Details</div>
          <div class="row g-2" style="font-size:.8rem;">
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">Nama Syarikat / Company Name</div>
              <div class="field-line fw-semibold"><?= htmlspecialchars($sst['company_name'] ?: ($_user['tenant_name'] ?? '')) ?></div>
            </div>
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">No. Pendaftaran / Registration No.</div>
              <div class="field-line"><?= htmlspecialchars($sst['company_tin'] ?? '') ?></div>
            </div>
            <div class="col-12">
              <div style="font-size:.7rem;color:#64748b;">Alamat / Address</div>
              <div class="field-line" style="min-height:36px;white-space:pre-line;"><?= htmlspecialchars($sst['company_address'] ?? '') ?></div>
            </div>
          </div>
        </div>

        <!-- Recipient section -->
        <div class="mb-3">
          <div class="fw-bold mb-2" style="font-size:.8rem;text-transform:uppercase;background:#f8fafc;padding:.25rem .5rem;">Bahagian B: Maklumat Penerima / Section B: Recipient Details</div>
          <div class="row g-2" style="font-size:.8rem;">
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">Nama / Name</div>
              <div class="field-line fw-semibold"><?= htmlspecialchars($rec['agent_name']) ?></div>
            </div>
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">No. K/P atau No. Pasport / IC or Passport No.</div>
              <div class="field-line"><?= htmlspecialchars($rec['agent_ic'] ?? '') ?></div>
            </div>
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">No. Cukai Pendapatan / Income Tax No. (TIN)</div>
              <div class="field-line"><?= htmlspecialchars($rec['agent_tin'] ?? '') ?></div>
            </div>
            <div class="col-6">
              <div style="font-size:.7rem;color:#64748b;">Jenis Pembayaran / Type of Payment</div>
              <div class="field-line">Komisyen / Commission</div>
            </div>
            <div class="col-12">
              <div style="font-size:.7rem;color:#64748b;">Alamat / Address</div>
              <div class="field-line" style="min-height:36px;white-space:pre-line;"><?= htmlspecialchars($rec['agent_address'] ?? '') ?></div>
            </div>
          </div>
        </div>

        <!-- Monthly payments -->
        <div class="mb-3">
          <div class="fw-bold mb-2" style="font-size:.8rem;text-transform:uppercase;background:#f8fafc;padding:.25rem .5rem;">Bahagian C: Butiran Pembayaran / Section C: Payment Details</div>
          <table style="width:100%;font-size:.78rem;border-collapse:collapse;">
            <thead>
              <tr>
                <?php foreach($months as $mk => $ml): ?>
                <th style="border:1px solid #cbd5e1;padding:.35rem;text-align:center;background:#f8fafc;"><?= $ml ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <tr>
                <?php foreach($months as $mk => $ml): ?>
                <td style="border:1px solid #cbd5e1;padding:.4rem;text-align:center;">
                  <?= $rec[$mk] > 0 ? number_format($rec[$mk],2) : '—' ?>
                </td>
                <?php endforeach; ?>
              </tr>
            </tbody>
          </table>
          <div class="mt-2 d-flex justify-content-end">
            <div style="font-size:.85rem;border:2px solid #0f172a;padding:.5rem 1.5rem;">
              <span style="font-weight:700;">JUMLAH / TOTAL: RM <?= number_format($rec['total_commission'],2) ?></span>
            </div>
          </div>
        </div>

        <!-- Signature -->
        <div class="mt-4 pt-3" style="border-top:1px solid #0f172a;">
          <div class="row g-3">
            <div class="col-6">
              <div style="font-size:.75rem;color:#64748b;margin-bottom:2.5rem;">Tandatangan Pemberi / Payer's Signature</div>
              <div style="border-top:1px solid #333;padding-top:.25rem;font-size:.75rem;">Tarikh / Date: ___________________</div>
            </div>
            <div class="col-6">
              <div style="font-size:.75rem;color:#64748b;margin-bottom:2.5rem;">Cop Syarikat / Company Stamp</div>
              <div style="border:1px dashed #94a3b8;height:60px;border-radius:4px;"></div>
            </div>
          </div>
          <?php if($rec['status']==='issued' && $rec['issued_date']): ?>
          <div class="mt-2" style="font-size:.7rem;color:#10b981;"><i class="bi bi-check-circle-fill me-1"></i>Issued on <?= date('d M Y',strtotime($rec['issued_date'])) ?></div>
          <?php endif; ?>
        </div>

        <div class="mt-3 text-center" style="font-size:.65rem;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:.5rem;">
          Generated by Roomee · <?= date('d M Y H:i') ?>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body></html>
    <?php exit;
}

// ── LOAD DATA ─────────────────────────────────────────────────────────────────
$agents  = Database::fetchAll("SELECT id,name,phone,agent_code FROM users WHERE tenant_id=? AND role='agent' AND is_active=1 ORDER BY name", [$_tenantId]);
$records = Database::fetchAll("SELECT * FROM cp58_records WHERE tenant_id=? AND year=? ORDER BY agent_name", [$_tenantId, $year]);
$recMap  = array_column($records, null, 'agent_id');

$pageTitle = 'CP58 Commission Statements';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0">CP58 Commission Statements</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Annual commission statements for agents — submit to LHDN by 31 March</p>
  </div>
  <form method="GET" action="<?= APP_URL ?>/cp58" class="d-flex gap-2 align-items-center">
    <label class="text-muted" style="font-size:.875rem;white-space:nowrap;">Tax Year:</label>
    <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <?php for($y=date('Y');$y>=date('Y')-5;$y--): ?>
      <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </form>
</div>

<?php if($flash): ?>
<div class="alert alert-<?= isset($flash['success'])?'success':'danger' ?> alert-dismissible fade show">
  <?= htmlspecialchars($flash['success'] ?? $flash['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if(!$agents): ?>
<div class="card-box text-center py-5">
  <i class="bi bi-person-badge" style="font-size:3rem;color:#cbd5e1;"></i>
  <h5 class="mt-3 mb-2">No agents found</h5>
  <p class="text-muted">Add agents first via the Agents page.</p>
  <a href="<?= APP_URL ?>/agents?action=create" class="btn btn-primary btn-sm">Add Agent</a>
</div>
<?php else: ?>
<div class="row g-4">
  <?php foreach($agents as $agent):
    $rec = $recMap[$agent['id']] ?? null;
    $months = ['jan','feb','mar','apr','may_amt','jun','jul','aug','sep','oct','nov','dec_amt'];
    $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  ?>
  <div class="col-12">
    <div class="card-box">
      <!-- Agent header -->
      <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
            <?= strtoupper(substr($agent['name'],0,1)) ?>
          </div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($agent['name']) ?></div>
            <div style="font-size:.75rem;color:#94a3b8;"><?= htmlspecialchars($agent['phone']??'') ?> · Code: <?= $agent['agent_code'] ?></div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if($rec): ?>
          <span class="badge bg-<?= $rec['status']==='issued'?'success':'warning text-dark' ?>"><?= ucfirst($rec['status']) ?></span>
          <span class="fw-bold text-primary">RM <?= number_format($rec['total_commission'],2) ?></span>
          <a href="<?= APP_URL ?>/cp58?print=<?= $rec['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print CP58
          </a>
          <?php if($rec['status']==='draft'): ?>
          <form method="POST" action="<?= APP_URL ?>/cp58" class="d-inline">
            <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="issue">
            <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <button class="btn btn-sm btn-success">Issue</button>
          </form>
          <form method="POST" action="<?= APP_URL ?>/cp58" onsubmit="return confirm('Delete this CP58 record?')" class="d-inline">
            <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
          <?php endif; ?>
          <?php else: ?>
          <span class="text-muted" style="font-size:.8rem;">No CP58 for <?= $year ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Collapsible entry form -->
      <div id="cp58Form_<?= $agent['id'] ?>" class="<?= !$rec ? '' : 'collapse' ?>">
        <form method="POST" action="<?= APP_URL ?>/cp58">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
          <input type="hidden" name="year" value="<?= $year ?>">

          <!-- Agent details for print -->
          <div class="row g-3 mb-3 pb-3 border-bottom">
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:.8rem;">IC / Passport No.</label>
              <input type="text" name="agent_ic" class="form-control form-control-sm" value="<?= htmlspecialchars($rec['agent_ic']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:.8rem;">TIN (Income Tax No.)</label>
              <input type="text" name="agent_tin" class="form-control form-control-sm" value="<?= htmlspecialchars($rec['agent_tin']??'') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:.8rem;">Notes</label>
              <input type="text" name="notes" class="form-control form-control-sm" value="<?= htmlspecialchars($rec['notes']??'') ?>" placeholder="Optional">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" style="font-size:.8rem;">Address</label>
              <textarea name="agent_address" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($rec['agent_address']??'') ?></textarea>
            </div>
          </div>

          <!-- Monthly commission inputs -->
          <div class="row g-2 mb-3" id="monthRow_<?= $agent['id'] ?>">
            <?php foreach($months as $i => $mk): ?>
            <div class="col-6 col-md-2 col-lg-1" style="min-width:80px;">
              <label class="form-label" style="font-size:.7rem;color:#64748b;"><?= $monthLabels[$i] ?></label>
              <input type="number" name="<?= $mk ?>" class="form-control form-control-sm commission-input"
                     data-agent="<?= $agent['id'] ?>"
                     value="<?= number_format((float)($rec[$mk]??0),2,'.','') ?>"
                     min="0" step="0.01" onchange="updateTotal(<?= $agent['id'] ?>)">
            </div>
            <?php endforeach; ?>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-bold" style="font-size:.9rem;">
              Total: <span id="total_<?= $agent['id'] ?>" style="color:#6366f1;">RM <?= number_format((float)($rec['total_commission']??0),2) ?></span>
            </div>
            <div class="d-flex gap-2">
              <?php if($rec): ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleForm(<?= $agent['id'] ?>)">Cancel</button>
              <?php endif; ?>
              <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Save CP58</button>
            </div>
          </div>
        </form>
      </div>

      <?php if($rec): ?>
      <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="toggleForm(<?= $agent['id'] ?>)" id="editBtn_<?= $agent['id'] ?>">
        <i class="bi bi-pencil me-1"></i>Edit Amounts
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if(!empty($records)): ?>
<div class="card-box mt-4">
  <h6 class="fw-semibold mb-3">Summary — Year <?= $year ?></h6>
  <table class="table table-hover mb-0">
    <thead>
      <tr>
        <th style="font-size:.75rem;">Agent</th>
        <th style="font-size:.75rem;">Total Commission</th>
        <th style="font-size:.75rem;">Status</th>
        <th style="font-size:.75rem;">Issued Date</th>
        <th style="font-size:.75rem;">Print</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($records as $r): ?>
      <tr>
        <td style="font-size:.875rem;" class="fw-semibold"><?= htmlspecialchars($r['agent_name']) ?></td>
        <td style="font-size:.875rem;" class="fw-semibold text-primary">RM <?= number_format($r['total_commission'],2) ?></td>
        <td><span class="badge bg-<?= $r['status']==='issued'?'success':'warning text-dark' ?>"><?= ucfirst($r['status']) ?></span></td>
        <td style="font-size:.8rem;" class="text-muted"><?= $r['issued_date'] ? date('d M Y',strtotime($r['issued_date'])) : '—' ?></td>
        <td><a href="<?= APP_URL ?>/cp58?print=<?= $r['id'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px;"><i class="bi bi-printer"></i></a></td>
      </tr>
      <?php endforeach; ?>
      <tr class="table-light">
        <td class="fw-bold" style="font-size:.875rem;">Total</td>
        <td class="fw-bold text-primary" style="font-size:.95rem;">RM <?= number_format(array_sum(array_column($records,'total_commission')),2) ?></td>
        <td colspan="3"></td>
      </tr>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
function updateTotal(agentId) {
  let total = 0;
  document.querySelectorAll('#monthRow_' + agentId + ' .commission-input').forEach(inp => {
    total += parseFloat(inp.value) || 0;
  });
  document.getElementById('total_' + agentId).textContent = 'RM ' + total.toFixed(2);
}
function toggleForm(agentId) {
  const form = document.getElementById('cp58Form_' + agentId);
  form.classList.toggle('collapse');
  const btn = document.getElementById('editBtn_' + agentId);
  if (btn) btn.style.display = form.classList.contains('collapse') ? '' : 'none';
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
