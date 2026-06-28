<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db     = getDB();
$cid    = companyId();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$ITEM_TYPES = ['rent','utility','deposit','carpark','late_fee','adjustment','other'];

// ── Generate invoice number ───────────────────────────────────────────────────
function nextInvoiceNo(PDO $db, int $cid): string {
    $db->prepare('UPDATE companies SET invoice_seq=invoice_seq+1 WHERE id=?')->execute([$cid]);
    $row = $db->prepare('SELECT invoice_prefix, invoice_seq FROM companies WHERE id=?');
    $row->execute([$cid]);
    $co = $row->fetch();
    return ($co['invoice_prefix'] ?? 'INV') . '-' . str_pad((string)(int)$co['invoice_seq'], 5, '0', STR_PAD_LEFT);
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'create') {
        $tId    = (int)($_POST['tenancy_id'] ?? 0);
        $period = trim($_POST['period']      ?? '');
        $due    = $_POST['due_date']         ?? '';
        $notes  = trim($_POST['notes']       ?? '');
        $issue  = isset($_POST['issue_now']);

        // Verify tenancy
        $ts = $db->prepare('SELECT t.*, r.resident_id FROM tenancies t
                            WHERE t.id=? AND t.company_id=?');
        $ts->execute([$tId, $cid]);
        $tenancy = $ts->fetch();
        if (!$tenancy) { flashSet('danger','Invalid tenancy.'); header('Location: invoices.php'); exit; }

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            flashSet('danger','Period must be YYYY-MM.'); header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        // Collect line items
        $descs   = $_POST['desc']   ?? [];
        $types   = $_POST['itype']  ?? [];
        $qtys    = $_POST['qty']    ?? [];
        $prices  = $_POST['price']  ?? [];
        $items   = [];
        $subtotal = 0.0;
        foreach ($descs as $i => $desc) {
            if (trim($desc) === '') continue;
            $qty    = max(0, (float)($qtys[$i]   ?? 0));
            $price  = max(0, (float)($prices[$i] ?? 0));
            $amount = round($qty * $price, 2);
            $type   = in_array($types[$i] ?? '', $ITEM_TYPES, true) ? $types[$i] : 'other';
            $items[] = compact('desc','type','qty','price','amount');
            $subtotal += $amount;
        }
        if (!$items) { flashSet('danger','Add at least one line item.'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }

        $invNo  = nextInvoiceNo($db, $cid);
        $status = $issue ? 'issued' : 'draft';
        $db->prepare(
            'INSERT INTO invoices (company_id,invoice_no,tenancy_id,resident_id,period,due_date,
             subtotal,tax_amount,total_amount,balance,status,issued_at,notes)
             VALUES (?,?,?,?,?,?,?,0,?,?,?,?,?)'
        )->execute([
            $cid, $invNo, $tId, $tenancy['resident_id'], $period,
            $due ?: null, $subtotal, $subtotal, $subtotal,
            $status, $issue ? date('Y-m-d H:i:s') : null, $notes
        ]);
        $invId = (int)$db->lastInsertId();
        foreach ($items as $it) {
            $db->prepare(
                'INSERT INTO invoice_items (company_id,invoice_id,description,quantity,unit_price,amount,item_type)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([$cid, $invId, $it['desc'], $it['qty'], $it['price'], $it['amount'], $it['type']]);
        }
        auditLog($db,'create','invoices',$invId,[],['invoice_no'=>$invNo,'total'=>$subtotal]);
        flashSet('success', 'Invoice ' . $invNo . ' created' . ($issue ? ' and issued.' : ' as draft.'));
        header('Location: invoices.php?action=view&id=' . $invId); exit;
    }

    if ($act === 'issue') {
        $iid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare("SELECT id,status FROM invoices WHERE id=? AND company_id=? AND status='draft'");
        $chk->execute([$iid, $cid]);
        if ($chk->fetch()) {
            $db->prepare("UPDATE invoices SET status='issued',issued_at=NOW() WHERE id=? AND company_id=?")
               ->execute([$iid, $cid]);
            flashSet('success','Invoice issued.');
        }
        header('Location: invoices.php?action=view&id=' . $iid); exit;
    }

    if ($act === 'void') {
        $iid = (int)($_POST['id'] ?? 0);
        $chk = $db->prepare("SELECT id FROM invoices WHERE id=? AND company_id=? AND status IN ('draft','issued')");
        $chk->execute([$iid, $cid]);
        if ($chk->fetch()) {
            $db->prepare("UPDATE invoices SET status='void' WHERE id=? AND company_id=?")->execute([$iid, $cid]);
            auditLog($db,'void','invoices',$iid,[],[]);
            flashSet('success','Invoice voided.');
        }
        header('Location: invoices.php'); exit;
    }
}

// ── View invoice ──────────────────────────────────────────────────────────────
$viewing = null;
$lineItems = [];
$payments = [];
if ($action === 'view' && $id) {
    $stmt = $db->prepare(
        'SELECT i.*, t.monthly_rent, res.name AS resident_name, res.phone AS resident_phone, res.email AS resident_email,
                r.room_no, u.unit_no, b.name AS building_name
         FROM invoices i
         JOIN tenancies t ON t.id = i.tenancy_id
         JOIN residents res ON res.id = i.resident_id
         JOIN rooms r ON r.id = t.room_id
         JOIN units u ON u.id = r.unit_id
         JOIN buildings b ON b.id = u.building_id
         WHERE i.id=? AND i.company_id=?'
    );
    $stmt->execute([$id, $cid]);
    $viewing = $stmt->fetch() ?: null;
    if (!$viewing) { flashSet('danger','Invoice not found.'); header('Location: invoices.php'); exit; }
    $li = $db->prepare('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id');
    $li->execute([$id]);
    $lineItems = $li->fetchAll();
    $pm = $db->prepare('SELECT * FROM payments WHERE invoice_id=? AND company_id=? ORDER BY payment_date DESC');
    $pm->execute([$id, $cid]);
    $payments = $pm->fetchAll();
}

// ── Create form pre-fill from tenancy ────────────────────────────────────────
$preloadTenancy = null;
$preloadItems   = [];
$preTenancyId   = (int)($_GET['tenancy_id'] ?? 0);
if ($action === 'create' && $preTenancyId) {
    $ts = $db->prepare('SELECT t.*, r.room_no, u.unit_no, b.name AS bname, res.name AS rname
                        FROM tenancies t
                        JOIN rooms r ON r.id=t.room_id JOIN units u ON u.id=r.unit_id
                        JOIN buildings b ON b.id=u.building_id JOIN residents res ON res.id=t.resident_id
                        WHERE t.id=? AND t.company_id=?');
    $ts->execute([$preTenancyId, $cid]);
    $preloadTenancy = $ts->fetch() ?: null;
    if ($preloadTenancy) {
        $preloadItems[] = ['desc' => 'Monthly Rent — ' . $preloadTenancy['bname'] . ' Unit ' . $preloadTenancy['unit_no'] . ' / ' . $preloadTenancy['room_no'],
                           'type' => 'rent', 'qty' => 1, 'price' => $preloadTenancy['monthly_rent']];
    }
}

// ── Tenancy options for dropdown ──────────────────────────────────────────────
$tenOpts = $db->prepare(
    "SELECT t.id, res.name AS rname, r.room_no, u.unit_no, b.name AS bname
     FROM tenancies t JOIN residents res ON res.id=t.resident_id
     JOIN rooms r ON r.id=t.room_id JOIN units u ON u.id=r.unit_id JOIN buildings b ON b.id=u.building_id
     WHERE t.company_id=? AND t.status IN ('active','pending') ORDER BY res.name"
);
$tenOpts->execute([$cid]);
$tenOpts = $tenOpts->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$sql    = 'SELECT i.*, res.name AS resident_name FROM invoices i
           JOIN residents res ON res.id = i.resident_id
           WHERE i.company_id=?';
$params = [$cid];
if ($filterStatus && in_array($filterStatus, ['draft','issued','paid','partial','overdue','void'], true)) {
    $sql .= ' AND i.status=?'; $params[] = $filterStatus;
}
$sql .= ' ORDER BY i.created_at DESC LIMIT 100';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$cnts = $db->prepare('SELECT status, COUNT(*) AS n FROM invoices WHERE company_id=? GROUP BY status');
$cnts->execute([$cid]);
$tabCounts = [];
foreach ($cnts->fetchAll() as $row) $tabCounts[$row['status']] = (int)$row['n'];

$pageTitle  = 'Invoices';
$activePage = 'invoices';
include __DIR__ . '/layout.php';

$stBadge = ['draft'=>'badge-pending','issued'=>'badge-open','paid'=>'badge-paid','partial'=>'badge-pending','overdue'=>'badge-overdue','void'=>''];
?>

<?php if ($action === 'view' && $viewing): ?>
<!-- ── Invoice view ─────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3">
  <a href="invoices.php" style="font-size:.85rem;color:#64748b;">&larr; Back</a>
  <h5 class="fw-bold mb-0"><?= e($viewing['invoice_no']) ?></h5>
  <span class="s-badge <?= $stBadge[$viewing['status']] ?? '' ?>"><?= ucfirst($viewing['status']) ?></span>
  <?php if ($viewing['status'] === 'draft'): ?>
  <form method="POST" class="d-inline ms-auto">
    <?= csrfField() ?><input type="hidden" name="_action" value="issue"><input type="hidden" name="id" value="<?= $viewing['id'] ?>">
    <button class="btn btn-sm btn-brand">Issue Invoice</button>
  </form>
  <?php endif; ?>
  <?php if (in_array($viewing['status'], ['draft','issued'], true)): ?>
  <form method="POST" class="d-inline <?= $viewing['status']==='draft'?'':' ms-auto' ?>"
        onsubmit="return confirm('Void this invoice?')">
    <?= csrfField() ?><input type="hidden" name="_action" value="void"><input type="hidden" name="id" value="<?= $viewing['id'] ?>">
    <button class="btn btn-sm btn-outline-danger">Void</button>
  </form>
  <?php endif; ?>
  <?php if (in_array($viewing['status'], ['issued','partial','overdue'], true)): ?>
  <a href="payments.php?action=create&invoice_id=<?= $viewing['id'] ?>" class="btn btn-sm btn-outline-success ms-auto">
    Record Payment
  </a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card-box">
      <div class="fw-bold mb-2"><?= e($viewing['resident_name']) ?></div>
      <div style="font-size:.83rem;color:#64748b;"><?= e($viewing['resident_email'] ?? '') ?></div>
      <div style="font-size:.83rem;color:#64748b;"><?= e($viewing['resident_phone'] ?? '') ?></div>
      <hr style="border-color:#f1f5f9;">
      <div style="font-size:.83rem;"><?= e($viewing['building_name'] . ' &mdash; Unit ' . $viewing['unit_no'] . ' / ' . $viewing['room_no']) ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card-box">
      <div class="row g-2" style="font-size:.83rem;">
        <div class="col-5 text-muted">Invoice No</div>   <div class="col-7 fw-semibold"><?= e($viewing['invoice_no']) ?></div>
        <div class="col-5 text-muted">Period</div>        <div class="col-7"><?= e($viewing['period']) ?></div>
        <div class="col-5 text-muted">Due Date</div>      <div class="col-7"><?= dateDisplay($viewing['due_date'] ?? '') ?></div>
        <div class="col-5 text-muted">Issued</div>        <div class="col-7"><?= $viewing['issued_at'] ? dateDisplay($viewing['issued_at']) : '&mdash;' ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card-box mb-4">
  <table class="table tbl mb-0">
    <thead>
      <tr><th>Description</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr>
    </thead>
    <tbody>
      <?php foreach ($lineItems as $item): ?>
      <tr>
        <td><?= e($item['description']) ?></td>
        <td style="font-size:.78rem;color:#94a3b8;"><?= ucfirst(str_replace('_',' ',$item['item_type'])) ?></td>
        <td class="text-end"><?= number_format((float)$item['quantity'], 3) ?></td>
        <td class="text-end"><?= money((float)$item['unit_price']) ?></td>
        <td class="text-end fw-semibold"><?= money((float)$item['amount']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="border-top:2px solid #f1f5f9;">
        <td colspan="4" class="text-end fw-bold">Total</td>
        <td class="text-end fw-bold" style="font-size:1rem;"><?= money((float)$viewing['total_amount']) ?></td>
      </tr>
      <?php if ($viewing['amount_paid'] > 0): ?>
      <tr>
        <td colspan="4" class="text-end text-muted">Paid</td>
        <td class="text-end" style="color:#15803d;">&minus; <?= money((float)$viewing['amount_paid']) ?></td>
      </tr>
      <tr>
        <td colspan="4" class="text-end fw-bold">Balance</td>
        <td class="text-end fw-bold" style="color:<?= $viewing['balance'] > 0 ? '#b91c1c' : '#15803d' ?>;"><?= money((float)$viewing['balance']) ?></td>
      </tr>
      <?php endif; ?>
    </tfoot>
  </table>
</div>

<?php if ($payments): ?>
<div class="card-box">
  <h6 class="fw-bold mb-3">Payments Received</h6>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead><tr><th>Date</th><th>Method</th><th>Ref</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $pm): ?>
        <tr>
          <td style="font-size:.82rem;"><?= dateDisplay($pm['payment_date']) ?></td>
          <td style="font-size:.82rem;"><?= ucfirst(str_replace('_',' ',$pm['payment_method'])) ?></td>
          <td style="font-size:.8rem;color:#64748b;"><?= $pm['gateway_ref'] ? e($pm['gateway_ref']) : '&mdash;' ?></td>
          <td class="text-end fw-semibold"><?= money((float)$pm['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php elseif ($action === 'create'): ?>
<!-- ── Create invoice ───────────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3">
  <a href="invoices.php" style="font-size:.85rem;color:#64748b;">&larr; Back</a>
  <h5 class="fw-bold mb-0">New Invoice</h5>
</div>

<?php if (!$tenOpts): ?>
<div class="alert alert-warning" style="font-size:.87rem;">
  No active tenancies found. <a href="tenancies.php?action=create">Create a tenancy</a> first.
</div>
<?php else: ?>
<div class="card-box" style="max-width:800px;">
  <form method="POST" action="invoices.php" id="invForm">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="create">
    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Tenancy *</label>
        <select name="tenancy_id" class="form-select form-select-sm" required>
          <option value="">Select tenancy&hellip;</option>
          <?php foreach ($tenOpts as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $preTenancyId == $t['id'] ? 'selected' : '' ?>>
            <?= e($t['rname'] . ' &mdash; ' . $t['bname'] . ' / ' . $t['room_no']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Period (YYYY-MM) *</label>
        <input type="text" name="period" class="form-control form-control-sm" required
               pattern="\d{4}-\d{2}" placeholder="<?= date('Y-m') ?>"
               value="<?= date('Y-m') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Due Date</label>
        <input type="date" name="due_date" class="form-control form-control-sm"
               value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
      </div>
    </div>

    <h6 class="fw-semibold mb-2" style="font-size:.85rem;">Line Items</h6>
    <div class="table-responsive mb-2">
      <table class="table tbl mb-0" id="itemTable">
        <thead>
          <tr>
            <th style="width:40%;">Description</th>
            <th style="width:15%;">Type</th>
            <th style="width:10%;">Qty</th>
            <th style="width:15%;">Unit Price</th>
            <th style="width:12%;">Amount</th>
            <th style="width:8%;"></th>
          </tr>
        </thead>
        <tbody id="lineItems">
          <?php
          $rows = $preloadItems ?: [['desc'=>'','type'=>'rent','qty'=>1,'price'=>0]];
          foreach ($rows as $row):
          ?>
          <tr>
            <td><input type="text" name="desc[]" class="form-control form-control-sm" value="<?= e($row['desc']) ?>" required></td>
            <td>
              <select name="itype[]" class="form-select form-select-sm">
                <?php foreach ($ITEM_TYPES as $t): ?>
                <option value="<?= $t ?>" <?= ($row['type'] ?? 'rent') === $t ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm item-qty" value="<?= $row['qty'] ?>" min="0" step="0.001"></td>
            <td><input type="number" name="price[]" class="form-control form-control-sm item-price" value="<?= $row['price'] ?>" min="0" step="0.01"></td>
            <td><input type="number" name="amount[]" class="form-control form-control-sm item-amount" readonly step="0.01" value="<?= round($row['qty'] * $row['price'], 2) ?>"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" style="font-size:.7rem;">&times;</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addRow()" style="font-size:.78rem;">
      <i class="bi bi-plus me-1"></i>Add Line
    </button>

    <div class="d-flex justify-content-end mb-4">
      <div style="min-width:220px;">
        <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
          <span class="text-muted">Subtotal</span>
          <span id="spSubtotal" class="fw-semibold">RM 0.00</span>
        </div>
        <div class="d-flex justify-content-between" style="font-size:1rem;font-weight:800;">
          <span>Total</span>
          <span id="spTotal">RM 0.00</span>
        </div>
        <input type="hidden" name="inv_total" id="hdnTotal" value="0">
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" name="issue_now" class="btn btn-brand btn-sm">Create &amp; Issue</button>
      <button type="submit" class="btn btn-sm btn-outline-secondary">Save as Draft</button>
      <a href="invoices.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ── List ────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2">
    <?php
    $tabs = ['' => 'All', 'draft' => 'Draft', 'issued' => 'Issued', 'paid' => 'Paid', 'overdue' => 'Overdue', 'void' => 'Void'];
    foreach ($tabs as $val => $label):
      $n = $val ? ($tabCounts[$val] ?? 0) : array_sum($tabCounts);
      $isAct = $filterStatus === $val;
    ?>
    <a href="invoices.php<?= $val ? '?status='.$val : '' ?>"
       class="btn btn-sm <?= $isAct ? 'btn-brand' : 'btn-outline-secondary' ?>" style="font-size:.78rem;">
      <?= $label ?> (<?= $n ?>)
    </a>
    <?php endforeach; ?>
  </div>
  <a href="invoices.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>New Invoice
  </a>
</div>

<div class="card-box">
  <?php if ($invoices): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Invoice</th><th>Resident</th><th>Period</th><th>Due</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td class="fw-semibold" style="font-size:.83rem;">
            <a href="invoices.php?action=view&id=<?= $inv['id'] ?>" class="text-brand"><?= e($inv['invoice_no']) ?></a>
          </td>
          <td style="font-size:.82rem;"><?= e($inv['resident_name']) ?></td>
          <td style="font-size:.82rem;color:#64748b;"><?= e($inv['period']) ?></td>
          <td style="font-size:.78rem;color:#94a3b8;"><?= dateDisplay($inv['due_date'] ?? '') ?></td>
          <td style="font-size:.83rem;"><?= money((float)$inv['total_amount']) ?></td>
          <td style="font-size:.83rem;color:#15803d;"><?= $inv['amount_paid'] > 0 ? money((float)$inv['amount_paid']) : '&mdash;' ?></td>
          <td><span class="s-badge <?= $stBadge[$inv['status']] ?? '' ?>"><?= ucfirst($inv['status']) ?></span></td>
          <td class="text-end">
            <a href="invoices.php?action=view&id=<?= $inv['id'] ?>"
               class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-receipt-cutoff" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No invoices yet.</p>
    <a href="invoices.php?action=create" class="btn btn-brand btn-sm">Create Invoice</a>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
$extraJs = <<<'JS'
<script>
function calcRow(row) {
  const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
  const price = parseFloat(row.querySelector('.item-price').value) || 0;
  const amt   = (qty * price).toFixed(2);
  row.querySelector('.item-amount').value = amt;
  return parseFloat(amt);
}
function updateTotals() {
  let sub = 0;
  document.querySelectorAll('#lineItems tr').forEach(r => { sub += calcRow(r); });
  document.getElementById('spSubtotal').textContent = 'RM ' + sub.toFixed(2);
  document.getElementById('spTotal').textContent    = 'RM ' + sub.toFixed(2);
  document.getElementById('hdnTotal').value = sub.toFixed(2);
}
function addRow() {
  const tmpl = document.querySelector('#lineItems tr').cloneNode(true);
  tmpl.querySelectorAll('input[type=text],input[type=number]').forEach(i => i.value = i.type === 'number' ? '0' : '');
  tmpl.querySelector('.item-qty').value   = '1';
  tmpl.querySelector('.item-price').value = '0';
  tmpl.querySelector('.item-amount').value= '0';
  document.getElementById('lineItems').appendChild(tmpl);
  attachEvents(tmpl);
}
function removeRow(btn) {
  if (document.querySelectorAll('#lineItems tr').length <= 1) return;
  btn.closest('tr').remove();
  updateTotals();
}
function attachEvents(row) {
  row.querySelectorAll('.item-qty,.item-price').forEach(el => el.addEventListener('input', updateTotals));
}
document.querySelectorAll('#lineItems tr').forEach(attachEvents);
updateTotals();
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
