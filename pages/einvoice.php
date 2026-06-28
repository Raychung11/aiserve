<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'einvoice';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

// Load SST / company settings for this tenant
$sst = Database::fetchOne("SELECT * FROM sst_settings WHERE tenant_id=?", [$_tenantId])
    ?: ['is_sst_registered'=>0,'sst_rate'=>8.00,'sst_number'=>'','invoice_prefix'=>'INV',
        'company_name'=>'','company_address'=>'','company_email'=>'',
        'company_phone'=>'','company_tin'=>'','bank_name'=>'','bank_account'=>'','bank_holder'=>'','invoice_notes'=>''];

// ── POST HANDLER ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Save SST / company settings
    if ($action === 'save_settings') {
        $data = [
            'tenant_id'         => $_tenantId,
            'is_sst_registered' => isset($_POST['is_sst_registered']) ? 1 : 0,
            'sst_number'        => trim($_POST['sst_number'] ?? ''),
            'sst_rate'          => (float)($_POST['sst_rate'] ?? 8),
            'company_name'      => trim($_POST['company_name'] ?? ''),
            'company_address'   => trim($_POST['company_address'] ?? ''),
            'company_email'     => trim($_POST['company_email'] ?? ''),
            'company_phone'     => trim($_POST['company_phone'] ?? ''),
            'company_tin'       => trim($_POST['company_tin'] ?? ''),
            'bank_name'         => trim($_POST['bank_name'] ?? ''),
            'bank_account'      => trim($_POST['bank_account'] ?? ''),
            'bank_holder'       => trim($_POST['bank_holder'] ?? ''),
            'invoice_prefix'    => strtoupper(trim($_POST['invoice_prefix'] ?? 'INV')),
            'invoice_notes'     => trim($_POST['invoice_notes'] ?? ''),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
        if (Database::count('sst_settings','tenant_id=?',[$_tenantId])) {
            Database::update('sst_settings', $data, 'tenant_id=?', [$_tenantId]);
        } else {
            Database::insert('sst_settings', $data);
        }
        ActivityLog::record('sst.settings_saved','SST/Invoice settings updated',$_tenantId,$_user['id']);
        $_SESSION['flash'] = ['success'=>'Settings saved.'];
        header('Location: '.APP_URL.'/einvoice?tab=settings'); exit;
    }

    // Create invoice
    if ($action === 'create') {
        $prefix    = strtoupper($sst['invoice_prefix'] ?: 'INV');
        $year      = date('Y');
        $count     = Database::count('einvoices','tenant_id=? AND YEAR(invoice_date)=?', [$_tenantId, $year]);
        $invoiceNo = $prefix.'-'.$year.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $descs  = $_POST['item_desc']  ?? [];
        $qtys   = $_POST['item_qty']   ?? [];
        $prices = $_POST['item_price'] ?? [];

        $subtotal = 0;
        $items = [];
        foreach ($descs as $i => $desc) {
            if (!trim($desc)) continue;
            $qty   = (float)($qtys[$i] ?? 1);
            $price = (float)($prices[$i] ?? 0);
            $amt   = round($qty * $price, 2);
            $subtotal += $amt;
            $items[] = ['description'=>trim($desc),'quantity'=>$qty,'unit_price'=>$price,'amount'=>$amt];
        }
        if (!$items) { $_SESSION['flash']=['error'=>'Add at least one line item.']; header('Location: '.APP_URL.'/einvoice?action=create'); exit; }

        $sstRate   = isset($_POST['apply_sst']) ? (float)$sst['sst_rate'] : 0;
        $sstAmount = round($subtotal * $sstRate / 100, 2);
        $total     = $subtotal + $sstAmount;

        $invoiceId = Database::insert('einvoices', [
            'tenant_id'       => $_tenantId,
            'invoice_no'      => $invoiceNo,
            'invoice_date'    => $_POST['invoice_date'] ?: date('Y-m-d'),
            'due_date'        => $_POST['due_date'] ?: null,
            'invoice_type'    => $_POST['invoice_type'] ?? 'management_fee',
            'billed_to_type'  => $_POST['billed_to_type'] ?? 'owner',
            'billed_to_id'    => $_POST['billed_to_id'] ?: null,
            'billed_to_name'  => trim($_POST['billed_to_name'] ?? ''),
            'billed_to_email' => trim($_POST['billed_to_email'] ?? '') ?: null,
            'billed_to_phone' => trim($_POST['billed_to_phone'] ?? '') ?: null,
            'billed_to_ic'    => trim($_POST['billed_to_ic'] ?? '') ?: null,
            'billed_to_tin'   => trim($_POST['billed_to_tin'] ?? '') ?: null,
            'billed_to_address'=> trim($_POST['billed_to_address'] ?? '') ?: null,
            'property_id'     => $_POST['property_id'] ?: null,
            'subtotal'        => $subtotal,
            'sst_rate'        => $sstRate,
            'sst_amount'      => $sstAmount,
            'total_amount'    => $total,
            'status'          => 'draft',
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'created_by'      => $_user['id'],
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        foreach ($items as $it) {
            Database::insert('einvoice_items', array_merge($it, ['invoice_id' => $invoiceId]));
        }
        ActivityLog::record('invoice.created',"Invoice $invoiceNo created",$_tenantId,$_user['id']);
        $_SESSION['flash'] = ['success'=>"Invoice $invoiceNo created."];
        header('Location: '.APP_URL.'/einvoice?view='.$invoiceId); exit;
    }

    // Update status
    if ($action === 'update_status') {
        $id     = (int)$_POST['invoice_id'];
        $status = $_POST['status'] ?? '';
        $inv    = Database::fetchOne("SELECT * FROM einvoices WHERE id=? AND tenant_id=?", [$id, $_tenantId]);
        if ($inv && in_array($status, ['issued','paid','cancelled','draft'])) {
            $upd = ['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')];
            if ($status === 'paid') {
                $upd['payment_date']   = $_POST['payment_date'] ?: date('Y-m-d');
                $upd['payment_method'] = $_POST['payment_method'] ?? '';
            }
            Database::update('einvoices', $upd, 'id=? AND tenant_id=?', [$id, $_tenantId]);
            ActivityLog::record('invoice.status',"Invoice #{$inv['invoice_no']} → $status",$_tenantId,$_user['id']);
            $_SESSION['flash'] = ['success'=>'Invoice updated.'];
        }
        header('Location: '.APP_URL.'/einvoice?view='.$id); exit;
    }

    // Delete draft
    if ($action === 'delete') {
        $id  = (int)$_POST['invoice_id'];
        $inv = Database::fetchOne("SELECT * FROM einvoices WHERE id=? AND tenant_id=? AND status='draft'", [$id, $_tenantId]);
        if ($inv) {
            Database::delete('einvoice_items','invoice_id=?',[$id]);
            Database::delete('einvoices','id=? AND tenant_id=?',[$id, $_tenantId]);
            ActivityLog::record('invoice.deleted',"Invoice #{$inv['invoice_no']} deleted",$_tenantId,$_user['id']);
            $_SESSION['flash'] = ['success'=>'Invoice deleted.'];
        }
        header('Location: '.APP_URL.'/einvoice'); exit;
    }
}

// ── PRINT / VIEW SINGLE INVOICE ───────────────────────────────────────────────
if (isset($_GET['view'])) {
    $inv   = Database::fetchOne("SELECT i.*, p.name AS property_name FROM einvoices i LEFT JOIN properties p ON p.id=i.property_id WHERE i.id=? AND i.tenant_id=?", [(int)$_GET['view'], $_tenantId]);
    if (!$inv) { header('Location: '.APP_URL.'/einvoice'); exit; }
    $items = Database::fetchAll("SELECT * FROM einvoice_items WHERE invoice_id=? ORDER BY id", [$inv['id']]);
    $isPrint = isset($_GET['print']);

    if ($isPrint) {
        // Clean print layout — no admin shell
        ?><!DOCTYPE html><html lang="en"><head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Invoice <?= htmlspecialchars($inv['invoice_no']) ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
        @media print{.no-print{display:none!important;}body{-webkit-print-color-adjust:exact;}}
        body{background:#fff;font-family:'Segoe UI',sans-serif;}
        .inv-header{background:#0f172a;color:#fff;padding:2rem;border-radius:0;}
        .inv-body{padding:2rem;}
        table th{background:#f8fafc;}
        .total-row td{font-weight:700;}
        </style></head><body>
        <div class="no-print p-3 d-flex gap-2" style="background:#f1f5f9;border-bottom:1px solid #e2e8f0;">
          <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
          <a href="<?= APP_URL ?>/einvoice?view=<?= $inv['id'] ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
        </div>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <?php include __DIR__.'/../includes/partials/invoice_print.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body></html>
        <?php exit;
    }

    $pageTitle = 'Invoice '.$inv['invoice_no'];
    $allProperties = Database::fetchAll("SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name",[$_tenantId]);
    include __DIR__.'/../includes/header.php';
    ?>

    <!-- Invoice Detail View -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-0"><?= htmlspecialchars($inv['invoice_no']) ?></h5>
        <small class="text-muted"><?= ucfirst(str_replace('_',' ',$inv['invoice_type'])) ?> · Created <?= date('d M Y', strtotime($inv['created_at'])) ?></small>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= APP_URL ?>/einvoice?view=<?= $inv['id'] ?>&print=1" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print / PDF</a>
        <?php if($inv['status']==='draft'): ?>
        <form method="POST" action="<?= APP_URL ?>/einvoice" class="d-inline">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
          <input type="hidden" name="status" value="issued">
          <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Issue Invoice</button>
        </form>
        <form method="POST" action="<?= APP_URL ?>/einvoice" onsubmit="return confirm('Delete this draft?')" class="d-inline">
          <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
        <?php elseif($inv['status']==='issued'): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal">
          <i class="bi bi-check2-circle me-1"></i>Mark as Paid
        </button>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/einvoice" class="btn btn-sm btn-outline-secondary">← All Invoices</a>
      </div>
    </div>

    <?php if($flash): ?>
    <div class="alert alert-<?= isset($flash['success'])?'success':'danger' ?> alert-dismissible fade show">
      <?= htmlspecialchars($flash['success'] ?? $flash['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <?php include __DIR__.'/../includes/partials/invoice_print.php'; ?>
      </div>
      <div class="col-lg-4">
        <!-- Status card -->
        <div class="card-box mb-3">
          <h6 class="fw-semibold mb-3 pb-2 border-bottom">Status</h6>
          <?php
          $statusColors = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger','overdue'=>'warning'];
          $sc = $statusColors[$inv['status']] ?? 'secondary'; ?>
          <span class="badge bg-<?= $sc ?> mb-3"><?= ucfirst($inv['status']) ?></span>
          <div class="row g-2 text-center" style="font-size:.8rem;">
            <div class="col-6"><div style="color:#94a3b8;">Subtotal</div><div class="fw-semibold">RM <?= number_format($inv['subtotal'],2) ?></div></div>
            <?php if($inv['sst_amount']>0): ?>
            <div class="col-6"><div style="color:#94a3b8;">SST (<?= $inv['sst_rate'] ?>%)</div><div class="fw-semibold text-warning">RM <?= number_format($inv['sst_amount'],2) ?></div></div>
            <?php endif; ?>
            <div class="col-12 mt-1 pt-2 border-top"><div style="color:#94a3b8;">Total</div><div class="fw-bold" style="font-size:1.2rem;color:#6366f1;">RM <?= number_format($inv['total_amount'],2) ?></div></div>
          </div>
          <?php if($inv['payment_date']): ?>
          <div class="mt-3 pt-2 border-top" style="font-size:.8rem;">
            <div class="text-muted">Paid on <?= date('d M Y',strtotime($inv['payment_date'])) ?></div>
            <?php if($inv['payment_method']): ?><div class="text-muted"><?= htmlspecialchars($inv['payment_method']) ?></div><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <!-- Billed to card -->
        <div class="card-box">
          <h6 class="fw-semibold mb-3 pb-2 border-bottom">Billed To</h6>
          <div style="font-size:.875rem;">
            <div class="fw-semibold"><?= htmlspecialchars($inv['billed_to_name']) ?></div>
            <?php foreach(['billed_to_email','billed_to_phone','billed_to_ic','billed_to_tin'] as $f): ?>
            <?php if($inv[$f]): ?><div class="text-muted"><?= htmlspecialchars($inv[$f]) ?></div><?php endif; ?>
            <?php endforeach; ?>
            <?php if($inv['billed_to_address']): ?><div class="text-muted mt-1" style="white-space:pre-line;"><?= htmlspecialchars($inv['billed_to_address']) ?></div><?php endif; ?>
            <?php if($inv['property_name']): ?><div class="mt-2 text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($inv['property_name']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Pay modal -->
    <div class="modal fade" id="payModal" tabindex="-1">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header"><h6 class="modal-title fw-bold">Record Payment</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <form method="POST" action="<?= APP_URL ?>/einvoice">
            <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
            <input type="hidden" name="status" value="paid">
            <div class="modal-body">
              <div class="mb-3"><label class="form-label fw-semibold" style="font-size:.875rem;">Payment Date</label>
                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
              <div class="mb-3"><label class="form-label fw-semibold" style="font-size:.875rem;">Method</label>
                <select name="payment_method" class="form-select">
                  <?php foreach(['Bank Transfer','DuitNow','Cash','Cheque','Online Banking','Other'] as $m): ?>
                  <option><?= $m ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success btn-sm w-100">Confirm Payment</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php include __DIR__.'/../includes/footer.php'; exit; ?>
<?php } // end view

// ── CREATE FORM ───────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'create') {
    $allProperties = Database::fetchAll("SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name", [$_tenantId]);
    $allOwners     = Database::fetchAll("SELECT id,name,email,phone,ic_number,bank_name FROM str_owners WHERE tenant_id=? AND is_active=1 ORDER BY name", [$_tenantId]);
    $allRenters    = Database::fetchAll("SELECT id,name,email,phone,ic_number FROM renter_profiles WHERE tenant_id=? ORDER BY name", [$_tenantId]);
    $pageTitle     = 'New Invoice';
    include __DIR__.'/../includes/header.php'; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div><h5 class="fw-bold mb-0">Create Invoice</h5><small class="text-muted">New e-invoice for management fee, rental or other charges</small></div>
      <a href="<?= APP_URL ?>/einvoice" class="btn btn-sm btn-outline-secondary">← Back</a>
    </div>

    <?php if($flash): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($flash['error']??'') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/einvoice">
      <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
      <input type="hidden" name="action" value="create">
      <div class="row g-4">

        <!-- Left: Billed to + Details -->
        <div class="col-lg-7">
          <div class="card-box mb-4">
            <h6 class="fw-semibold mb-3 pb-2 border-bottom">Bill To</h6>
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Type</label>
                <select name="billed_to_type" class="form-select form-select-sm" id="billedType" onchange="switchBilledTo(this.value)">
                  <option value="owner">Owner</option>
                  <option value="renter">Renter</option>
                  <option value="other">Other (manual)</option>
                </select>
              </div>
              <div class="col-md-8" id="selectWrap">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Select Owner</label>
                <select name="billed_to_id" class="form-select form-select-sm" id="billedSelect" onchange="fillBilledTo(this)">
                  <option value="">— Select —</option>
                  <?php foreach($allOwners as $o): ?>
                  <option value="<?= $o['id'] ?>" data-name="<?= htmlspecialchars($o['name']) ?>" data-email="<?= htmlspecialchars($o['email']??'') ?>" data-phone="<?= htmlspecialchars($o['phone']??'') ?>" data-ic="<?= htmlspecialchars($o['ic_number']??'') ?>"><?= htmlspecialchars($o['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="billed_to_renter_id" class="form-select form-select-sm d-none" id="renterSelect" onchange="fillBilledToRenter(this)">
                  <option value="">— Select —</option>
                  <?php foreach($allRenters as $r): ?>
                  <option value="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($r['name']) ?>" data-email="<?= htmlspecialchars($r['email']??'') ?>" data-phone="<?= htmlspecialchars($r['phone']??'') ?>" data-ic="<?= htmlspecialchars($r['ic_number']??'') ?>"><?= htmlspecialchars($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Name <span class="text-danger">*</span></label>
                <input type="text" name="billed_to_name" id="btName" class="form-control form-control-sm" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Email</label>
                <input type="email" name="billed_to_email" id="btEmail" class="form-control form-control-sm">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Phone</label>
                <input type="text" name="billed_to_phone" id="btPhone" class="form-control form-control-sm">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem;">IC / Passport</label>
                <input type="text" name="billed_to_ic" id="btIc" class="form-control form-control-sm">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.8rem;">TIN (Tax No.)</label>
                <input type="text" name="billed_to_tin" id="btTin" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Address</label>
                <textarea name="billed_to_address" id="btAddress" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="card-box">
            <h6 class="fw-semibold mb-3 pb-2 border-bottom">Line Items</h6>
            <div id="itemRows">
              <div class="row g-2 mb-2 align-items-end item-row">
                <div class="col-6"><label class="form-label fw-semibold" style="font-size:.75rem;">Description</label>
                  <input type="text" name="item_desc[]" class="form-control form-control-sm" placeholder="e.g. Management fee — Jan 2025" required></div>
                <div class="col-2"><label class="form-label fw-semibold" style="font-size:.75rem;">Qty</label>
                  <input type="number" name="item_qty[]" class="form-control form-control-sm" value="1" min="0.001" step="0.001" onchange="calcRow(this)" required></div>
                <div class="col-3"><label class="form-label fw-semibold" style="font-size:.75rem;">Unit Price (RM)</label>
                  <input type="number" name="item_price[]" class="form-control form-control-sm" value="0" min="0" step="0.01" onchange="calcRow(this)" required></div>
                <div class="col-1"><label class="form-label" style="font-size:.75rem;">&nbsp;</label>
                  <button type="button" class="btn btn-sm btn-outline-danger d-block w-100" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addItemRow()"><i class="bi bi-plus me-1"></i>Add Item</button>
            <!-- Totals -->
            <div class="mt-3 pt-3 border-top">
              <div class="d-flex justify-content-between mb-1" style="font-size:.875rem;">
                <span class="text-muted">Subtotal</span>
                <span id="subtotalDisplay" class="fw-semibold">RM 0.00</span>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.875rem;">
                <label class="d-flex align-items-center gap-2 text-muted">
                  <input type="checkbox" name="apply_sst" id="applySst" onchange="updateTotals()">
                  SST (<?= $sst['sst_rate'] ?>%)<?= $sst['is_sst_registered'] ? ' — '.$sst['sst_number'] : ' (not registered)' ?>
                </label>
                <span id="sstDisplay" class="fw-semibold text-warning">RM 0.00</span>
              </div>
              <div class="d-flex justify-content-between pt-2 border-top">
                <span class="fw-bold">Total</span>
                <span id="totalDisplay" class="fw-bold" style="color:#6366f1;font-size:1.1rem;">RM 0.00</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Meta -->
        <div class="col-lg-5">
          <div class="card-box mb-4">
            <h6 class="fw-semibold mb-3 pb-2 border-bottom">Invoice Details</h6>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.8rem;">Invoice Type</label>
              <select name="invoice_type" class="form-select form-select-sm">
                <?php foreach(['management_fee'=>'Management Fee','rental'=>'Rental','maintenance'=>'Maintenance','commission'=>'Commission','other'=>'Other'] as $v=>$l): ?>
                <option value="<?= $v ?>"><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Invoice Date</label>
                <input type="date" name="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:.8rem;">Due Date</label>
                <input type="date" name="due_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.8rem;">Property (optional)</label>
              <select name="property_id" class="form-select form-select-sm">
                <option value="">— None —</option>
                <?php foreach($allProperties as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.8rem;">Notes</label>
              <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Payment instructions, bank details, etc."><?= htmlspecialchars($sst['invoice_notes'] ?? '') ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-file-earmark-check me-1"></i>Create Invoice</button>
        </div>
      </div>
    </form>

    <script>
    const SST_RATE = <?= (float)$sst['sst_rate'] ?>;

    function addItemRow() {
      const tpl = document.querySelector('#itemRows .item-row').cloneNode(true);
      tpl.querySelectorAll('input').forEach(i => { if(i.type!=='number') i.value=''; else i.value=(i.name.includes('qty')?1:0); });
      document.getElementById('itemRows').appendChild(tpl);
    }
    function removeRow(btn) {
      const rows = document.querySelectorAll('.item-row');
      if(rows.length > 1) { btn.closest('.item-row').remove(); updateTotals(); }
    }
    function calcRow(el) { updateTotals(); }
    function updateTotals() {
      let sub = 0;
      document.querySelectorAll('.item-row').forEach(r => {
        const q = parseFloat(r.querySelector('[name="item_qty[]"]').value)||0;
        const p = parseFloat(r.querySelector('[name="item_price[]"]').value)||0;
        sub += q*p;
      });
      const applySst = document.getElementById('applySst').checked;
      const sst = applySst ? Math.round(sub * SST_RATE) / 100 : 0;
      document.getElementById('subtotalDisplay').textContent = 'RM ' + sub.toFixed(2);
      document.getElementById('sstDisplay').textContent = 'RM ' + sst.toFixed(2);
      document.getElementById('totalDisplay').textContent = 'RM ' + (sub+sst).toFixed(2);
    }

    // Owner/renter owners data
    const ownerData = <?= json_encode(array_column($allOwners, null, 'id')) ?>;
    const renterData = <?= json_encode(array_column($allRenters, null, 'id')) ?>;

    function switchBilledTo(type) {
      const sel = document.getElementById('billedSelect');
      const rsel = document.getElementById('renterSelect');
      if(type==='owner')  { sel.classList.remove('d-none'); rsel.classList.add('d-none'); }
      else if(type==='renter') { sel.classList.add('d-none'); rsel.classList.remove('d-none'); }
      else { sel.classList.add('d-none'); rsel.classList.add('d-none'); }
      clearBilledTo();
    }
    function fillBilledTo(sel) {
      const opt = sel.options[sel.selectedIndex];
      document.getElementById('btName').value  = opt.dataset.name||'';
      document.getElementById('btEmail').value = opt.dataset.email||'';
      document.getElementById('btPhone').value = opt.dataset.phone||'';
      document.getElementById('btIc').value    = opt.dataset.ic||'';
    }
    function fillBilledToRenter(sel) {
      const opt = sel.options[sel.selectedIndex];
      document.getElementById('btName').value  = opt.dataset.name||'';
      document.getElementById('btEmail').value = opt.dataset.email||'';
      document.getElementById('btPhone').value = opt.dataset.phone||'';
      document.getElementById('btIc').value    = opt.dataset.ic||'';
    }
    function clearBilledTo() {
      ['btName','btEmail','btPhone','btIc','btTin','btAddress'].forEach(id => document.getElementById(id).value='');
    }
    </script>

    <?php include __DIR__.'/../includes/footer.php'; exit;
} // end create form

// ── INVOICE LIST (default view) ───────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$params = [$_tenantId];
$where  = 'tenant_id=?';
if ($statusFilter) { $where .= ' AND status=?'; $params[] = $statusFilter; }

$invoices = Database::fetchAll(
    "SELECT * FROM einvoices WHERE $where ORDER BY created_at DESC",
    $params
);

// Counts per status
$counts = [];
foreach (['draft','issued','paid','cancelled'] as $s) {
    $counts[$s] = Database::count('einvoices','tenant_id=? AND status=?',[$_tenantId,$s]);
}

$tab = $_GET['tab'] ?? 'list';
$pageTitle = 'e-Invoice & SST';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0">e-Invoice &amp; SST</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Generate invoices for owners, renters, and management fees</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/einvoice?action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Invoice</a>
  </div>
</div>

<?php if($flash): ?>
<div class="alert alert-<?= isset($flash['success'])?'success':'danger' ?> alert-dismissible fade show">
  <?= htmlspecialchars($flash['success'] ?? $flash['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link <?= $tab==='list'?'active':'' ?>" href="<?= APP_URL ?>/einvoice?tab=list">Invoices</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='settings'?'active':'' ?>" href="<?= APP_URL ?>/einvoice?tab=settings">SST &amp; Company Settings</a></li>
</ul>

<?php if ($tab === 'settings'): ?>
<!-- ── SETTINGS TAB ── -->
<div class="card-box" style="max-width:680px;">
  <h6 class="fw-semibold mb-3 pb-2 border-bottom">SST Registration &amp; Company Details</h6>
  <form method="POST" action="<?= APP_URL ?>/einvoice">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="action" value="save_settings">
    <div class="row g-3">
      <div class="col-12">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_sst_registered" id="sstReg" value="1" <?= $sst['is_sst_registered']?'checked':'' ?>>
          <label class="form-check-label fw-semibold" for="sstReg">SST Registered</label>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.875rem;">SST Registration No.</label>
        <input type="text" name="sst_number" class="form-control" value="<?= htmlspecialchars($sst['sst_number']??'') ?>" placeholder="W10-1234-12345678">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">SST Rate (%)</label>
        <input type="number" name="sst_rate" class="form-control" value="<?= htmlspecialchars($sst['sst_rate']??'8') ?>" min="0" max="100" step="0.01">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Invoice Prefix</label>
        <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($sst['invoice_prefix']??'INV') ?>" maxlength="10">
      </div>
      <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Company / Issuer Details (printed on invoices)</small></div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Company Name</label>
        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($sst['company_name']??'') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Phone</label>
        <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars($sst['company_phone']??'') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.875rem;">TIN (Income Tax No.)</label>
        <input type="text" name="company_tin" class="form-control" value="<?= htmlspecialchars($sst['company_tin']??'') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Email</label>
        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($sst['company_email']??'') ?>">
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Address</label>
        <textarea name="company_address" class="form-control" rows="2"><?= htmlspecialchars($sst['company_address']??'') ?></textarea>
      </div>
      <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Bank Details (printed on invoices)</small></div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($sst['bank_name']??'') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Account No.</label>
        <input type="text" name="bank_account" class="form-control" value="<?= htmlspecialchars($sst['bank_account']??'') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Account Holder</label>
        <input type="text" name="bank_holder" class="form-control" value="<?= htmlspecialchars($sst['bank_holder']??'') ?>">
      </div>
      <div class="col-12">
        <label class="form-label fw-semibold" style="font-size:.875rem;">Default Invoice Notes</label>
        <textarea name="invoice_notes" class="form-control" rows="2" placeholder="e.g. Please transfer to the above account within 30 days."><?= htmlspecialchars($sst['invoice_notes']??'') ?></textarea>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Settings</button>
      </div>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ── INVOICE LIST TAB ── -->
<!-- Status filter pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="<?= APP_URL ?>/einvoice" class="btn btn-sm <?= !$statusFilter?'btn-dark':'btn-outline-secondary' ?>">All (<?= array_sum($counts) ?>)</a>
  <?php foreach(['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger'] as $s=>$c): ?>
  <a href="<?= APP_URL ?>/einvoice?status=<?= $s ?>" class="btn btn-sm <?= $statusFilter===$s?"btn-$c":"btn-outline-$c" ?>">
    <?= ucfirst($s) ?> (<?= $counts[$s] ?>)
  </a>
  <?php endforeach; ?>
</div>

<?php if($invoices): ?>
<div class="card-box">
  <table class="table table-hover mb-0">
    <thead>
      <tr>
        <th style="font-size:.75rem;">Invoice No.</th>
        <th style="font-size:.75rem;">Billed To</th>
        <th style="font-size:.75rem;">Type</th>
        <th style="font-size:.75rem;">Date</th>
        <th style="font-size:.75rem;">Amount</th>
        <th style="font-size:.75rem;">SST</th>
        <th style="font-size:.75rem;">Status</th>
        <th style="font-size:.75rem;"></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $statusBadge = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger','overdue'=>'warning'];
      foreach($invoices as $inv): ?>
      <tr>
        <td style="font-size:.85rem;" class="fw-semibold"><?= htmlspecialchars($inv['invoice_no']) ?></td>
        <td style="font-size:.8rem;"><?= htmlspecialchars($inv['billed_to_name']) ?><div class="text-muted" style="font-size:.7rem;"><?= ucfirst($inv['billed_to_type']) ?></div></td>
        <td style="font-size:.78rem;" class="text-muted"><?= ucfirst(str_replace('_',' ',$inv['invoice_type'])) ?></td>
        <td style="font-size:.78rem;" class="text-muted"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
        <td style="font-size:.85rem;" class="fw-semibold">RM <?= number_format($inv['total_amount'],2) ?></td>
        <td style="font-size:.78rem;" class="text-muted"><?= $inv['sst_amount']>0 ? 'RM '.number_format($inv['sst_amount'],2) : '—' ?></td>
        <td><span class="badge bg-<?= $statusBadge[$inv['status']]??'secondary' ?>"><?= ucfirst($inv['status']) ?></span></td>
        <td><a href="<?= APP_URL ?>/einvoice?view=<?= $inv['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px;">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card-box text-center py-5">
  <i class="bi bi-receipt" style="font-size:3rem;color:#cbd5e1;"></i>
  <h5 class="mt-3 mb-2">No invoices yet</h5>
  <a href="<?= APP_URL ?>/einvoice?action=create" class="btn btn-primary btn-sm">Create your first invoice</a>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__.'/../includes/footer.php'; ?>
