<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db     = getDB();
$cid    = companyId();
$action = $_GET['action'] ?? 'list';

$METHODS = ['fpx','duitnow','cash','bank_transfer','cheque','other'];

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'record') {
        $invId  = (int)($_POST['invoice_id']    ?? 0);
        $amount = (float)($_POST['amount']       ?? 0);
        $method = $_POST['payment_method']       ?? 'cash';
        $ref    = trim($_POST['gateway_ref']     ?? '');
        $date   = $_POST['payment_date']         ?? date('Y-m-d');
        $notes  = trim($_POST['notes']           ?? '');

        if ($invId <= 0 || $amount <= 0) {
            flashSet('danger', 'Invoice and amount are required.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        if (!in_array($method, $METHODS, true)) $method = 'cash';

        // Verify invoice
        $chk = $db->prepare("SELECT * FROM invoices WHERE id=? AND company_id=? AND status NOT IN ('void','paid')");
        $chk->execute([$invId, $cid]);
        $invoice = $chk->fetch();
        if (!$invoice) {
            flashSet('danger', 'Invoice not found or already fully paid/voided.');
            header('Location: payments.php'); exit;
        }

        $resId = $invoice['resident_id'];
        $uid   = $_SESSION['user_id'] ?? null;

        $db->prepare(
            'INSERT INTO payments (company_id,invoice_id,resident_id,amount,payment_method,gateway_ref,payment_date,notes,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([$cid, $invId, $resId, $amount, $method, $ref ?: null, $date, $notes, $uid]);
        $pmId = (int)$db->lastInsertId();

        // Update invoice balance and status
        $newPaid    = (float)$invoice['amount_paid'] + $amount;
        $newBalance = max(0.0, (float)$invoice['total_amount'] - $newPaid);
        $newStatus  = $newBalance <= 0.001 ? 'paid' : 'partial';
        $paidAt     = $newBalance <= 0.001 ? 'NOW()' : 'NULL';
        $db->prepare("UPDATE invoices SET amount_paid=?,balance=?,status=?,paid_at=IF(?='paid',NOW(),paid_at) WHERE id=? AND company_id=?")
           ->execute([$newPaid, $newBalance, $newStatus, $newStatus, $invId, $cid]);

        auditLog($db,'create','payments',$pmId,[],['invoice_id'=>$invId,'amount'=>$amount,'method'=>$method]);
        flashSet('success', 'Payment of ' . money($amount) . ' recorded.');
        header('Location: invoices.php?action=view&id=' . $invId); exit;
    }
}

// ── Pre-fill from invoice ─────────────────────────────────────────────────────
$preInvoice = null;
$preInvId   = (int)($_GET['invoice_id'] ?? 0);
if ($action === 'create' && $preInvId) {
    $stmt = $db->prepare(
        'SELECT i.*, res.name AS resident_name FROM invoices i
         JOIN residents res ON res.id = i.resident_id
         WHERE i.id=? AND i.company_id=?'
    );
    $stmt->execute([$preInvId, $cid]);
    $preInvoice = $stmt->fetch() ?: null;
}

// ── Open invoices for dropdown ────────────────────────────────────────────────
$openInvoices = $db->prepare(
    "SELECT i.*, res.name AS rname FROM invoices i
     JOIN residents res ON res.id = i.resident_id
     WHERE i.company_id=? AND i.status IN ('issued','partial','overdue')
     ORDER BY i.due_date ASC LIMIT 100"
);
$openInvoices->execute([$cid]);
$openInvoices = $openInvoices->fetchAll();

// ── List ──────────────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    'SELECT p.*, inv.invoice_no, res.name AS resident_name
     FROM payments p
     JOIN invoices inv ON inv.id = p.invoice_id
     JOIN residents res ON res.id = p.resident_id
     WHERE p.company_id=?
     ORDER BY p.payment_date DESC, p.created_at DESC LIMIT 100'
);
$stmt->execute([$cid]);
$payments = $stmt->fetchAll();

$pageTitle  = 'Payments';
$activePage = 'payments';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="page-sub mb-0"><?= count($payments) ?> payment<?= count($payments) !== 1 ? 's' : '' ?> recorded</p>
  <a href="payments.php?action=create" class="btn btn-brand btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Record Payment
  </a>
</div>

<?php if ($action === 'create'): ?>
<div class="card-box mb-4" style="max-width:560px;">
  <h6 class="fw-bold mb-3">Record Payment</h6>

  <?php if ($preInvoice): ?>
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.85rem;">
    <div class="fw-semibold"><?= e($preInvoice['invoice_no']) ?> &mdash; <?= e($preInvoice['resident_name']) ?></div>
    <div style="color:#64748b;">
      Total: <?= money((float)$preInvoice['total_amount']) ?> &mdash;
      Balance: <strong style="color:#b91c1c;"><?= money((float)$preInvoice['balance']) ?></strong>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!$openInvoices && !$preInvoice): ?>
  <p style="color:#64748b;font-size:.87rem;">No open invoices to record payment against. <a href="invoices.php">View invoices</a>.</p>
  <?php else: ?>
  <form method="POST" action="payments.php">
    <?= csrfField() ?>
    <input type="hidden" name="_action" value="record">
    <div class="mb-3">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Invoice *</label>
      <?php if ($preInvoice): ?>
      <input type="hidden" name="invoice_id" value="<?= $preInvId ?>">
      <input type="text" class="form-control form-control-sm" readonly
             value="<?= e($preInvoice['invoice_no'] . ' &mdash; ' . $preInvoice['resident_name']) ?>">
      <?php else: ?>
      <select name="invoice_id" class="form-select form-select-sm" required>
        <option value="">Select invoice&hellip;</option>
        <?php foreach ($openInvoices as $inv): ?>
        <option value="<?= $inv['id'] ?>">
          <?= e($inv['invoice_no'] . ' &mdash; ' . $inv['rname'] . ' (' . $inv['period'] . ') Balance: RM ' . number_format((float)$inv['balance'],2)) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Amount (RM) *</label>
        <input type="number" name="amount" class="form-control form-control-sm" min="0.01" step="0.01"
               value="<?= $preInvoice ? number_format((float)$preInvoice['balance'],2) : '' ?>"
               required autofocus>
      </div>
      <div class="col-md-7">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Method</label>
        <select name="payment_method" class="form-select form-select-sm">
          <?php foreach ($METHODS as $m): ?>
          <option value="<?= $m ?>"><?= ucwords(str_replace('_',' ',$m)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Date</label>
        <input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Reference / Ref No.</label>
        <input type="text" name="gateway_ref" class="form-control form-control-sm" placeholder="FPX ref, receipt no...">
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-brand btn-sm">Record Payment</button>
      <a href="payments.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card-box">
  <?php if ($payments): ?>
  <div class="table-responsive">
    <table class="table tbl mb-0">
      <thead>
        <tr><th>Date</th><th>Resident</th><th>Invoice</th><th>Method</th><th>Ref</th><th class="text-end">Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $pm): ?>
        <tr>
          <td style="font-size:.82rem;"><?= dateDisplay($pm['payment_date']) ?></td>
          <td style="font-size:.82rem;"><?= e($pm['resident_name']) ?></td>
          <td>
            <a href="invoices.php?action=view&id=<?= $pm['invoice_id'] ?>" class="text-brand" style="font-size:.82rem;">
              <?= e($pm['invoice_no']) ?>
            </a>
          </td>
          <td style="font-size:.78rem;color:#64748b;"><?= ucwords(str_replace('_',' ',$pm['payment_method'])) ?></td>
          <td style="font-size:.78rem;color:#94a3b8;"><?= $pm['gateway_ref'] ? e($pm['gateway_ref']) : '&mdash;' ?></td>
          <td class="text-end fw-semibold"><?= money((float)$pm['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-cash-coin" style="font-size:2.5rem;color:#cbd5e1;"></i>
    <p class="text-muted mt-2 mb-3" style="font-size:.9rem;">No payments recorded yet.</p>
    <a href="payments.php?action=create" class="btn btn-brand btn-sm">Record First Payment</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
