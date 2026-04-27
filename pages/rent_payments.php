<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'renters';
$flash = [];

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── Record / update a single payment ─────────────────────────────────────
    if ($action === 'record') {
        $tenancyId  = (int)$_POST['tenancy_id'];
        $period     = $_POST['period'] ?? '';
        $amountPaid = (float)$_POST['amount_paid'];
        $amountDue  = (float)$_POST['amount_due'];
        $paidDate   = $_POST['payment_date'] ?: date('Y-m-d');
        $method     = $_POST['payment_method'] ?? 'bank_transfer';
        $receiptNo  = trim($_POST['receipt_no'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');

        // Verify tenancy belongs to this tenant
        $tenancy = Database::fetchOne("SELECT * FROM tenancies WHERE id=? AND tenant_id=?", [$tenancyId, $_tenantId]);
        if (!$tenancy) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Tenancy not found.']; header('Location: '.APP_URL.'/rent-payments'); exit; }

        $status = 'pending';
        if ($amountPaid <= 0)                              $status = 'pending';
        elseif ($amountPaid >= $amountDue)                 $status = 'paid';
        elseif ($amountPaid > 0 && $amountPaid < $amountDue) $status = 'partial';

        // Mark overdue if period is past and not fully paid
        $periodDate = strtotime($period.'-01');
        $endOfPeriod = strtotime(date('Y-m-t', $periodDate));
        if ($status !== 'paid' && $endOfPeriod < time()) $status = 'overdue';

        $existing = Database::fetchOne("SELECT id FROM rent_payments WHERE tenancy_id=? AND period=?", [$tenancyId, $period]);
        $data = [
            'tenant_id'      => $_tenantId,
            'tenancy_id'     => $tenancyId,
            'period'         => $period,
            'amount_due'     => $amountDue,
            'amount_paid'    => $amountPaid,
            'payment_date'   => $amountPaid > 0 ? $paidDate : null,
            'payment_method' => $method,
            'receipt_no'     => $receiptNo ?: null,
            'status'         => $status,
            'notes'          => $notes ?: null,
            'recorded_by'    => $_user['id'],
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            Database::update('rent_payments', $data, 'id=?', [$existing['id']]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            Database::insert('rent_payments', $data);
        }
        ActivityLog::record('payment.record', "Recorded RM$amountPaid for tenancy #$tenancyId period $period", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Payment recorded for $period."];
        header('Location: '.APP_URL.'/rent-payments?tenancy_id='.$tenancyId); exit;
    }

    // ── Generate schedule for a tenancy ──────────────────────────────────────
    if ($action === 'generate_schedule') {
        $tenancyId = (int)$_POST['tenancy_id'];
        $tenancy   = Database::fetchOne("SELECT * FROM tenancies WHERE id=? AND tenant_id=?", [$tenancyId, $_tenantId]);
        if (!$tenancy) { $_SESSION['flash'] = ['type'=>'danger','msg'=>'Tenancy not found.']; header('Location: '.APP_URL.'/rent-payments'); exit; }

        $start   = new DateTime($tenancy['start_date']);
        $end     = new DateTime($tenancy['end_date']);
        $current = clone $start;
        $current->modify('first day of this month');
        $count = 0;

        while ($current <= $end) {
            $period = $current->format('Y-m');
            $periodEnd = strtotime($current->format('Y-m-t'));
            $status = ($periodEnd < time()) ? 'overdue' : 'pending';

            $exists = Database::count('rent_payments', 'tenancy_id=? AND period=?', [$tenancyId, $period]);
            if (!$exists) {
                Database::insert('rent_payments', [
                    'tenant_id'  => $_tenantId,
                    'tenancy_id' => $tenancyId,
                    'period'     => $period,
                    'amount_due' => $tenancy['monthly_rent'],
                    'amount_paid' => 0,
                    'status'     => $status,
                    'recorded_by' => $_user['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $count++;
            }
            $current->modify('+1 month');
        }
        ActivityLog::record('payment.schedule', "Generated $count payment records for tenancy #$tenancyId", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"$count payment periods created."];
        header('Location: '.APP_URL.'/rent-payments?tenancy_id='.$tenancyId); exit;
    }

    // ── Delete single record ──────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)$_POST['payment_id'];
        $p  = Database::fetchOne("SELECT tenancy_id FROM rent_payments WHERE id=? AND tenant_id=?", [$id, $_tenantId]);
        Database::delete('rent_payments', 'id=? AND tenant_id=?', [$id, $_tenantId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Payment record deleted.'];
        header('Location: '.APP_URL.'/rent-payments?tenancy_id='.($p['tenancy_id']??'')); exit;
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

// ── LOAD ──────────────────────────────────────────────────────────────────────
$tenancyId  = (int)($_GET['tenancy_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$properties = Database::fetchAll("SELECT id, name FROM properties WHERE tenant_id=? AND deleted_at IS NULL ORDER BY name", [$_tenantId]);

$tenancy     = null;
$renter      = null;
$payments    = [];
$summary     = [];

if ($tenancyId) {
    $tenancy = Database::fetchOne(
        "SELECT t.*, p.name AS property_name, r.name AS renter_name, r.id AS renter_id_val
         FROM tenancies t
         LEFT JOIN properties p ON p.id = t.property_id
         LEFT JOIN renter_profiles r ON r.id = t.renter_id
         WHERE t.id=? AND t.tenant_id=? AND t.deleted_at IS NULL",
        [$tenancyId, $_tenantId]
    );
    if ($tenancy) {
        $where  = 'tenant_id=? AND tenancy_id=?';
        $params = [$_tenantId, $tenancyId];
        if ($filterStatus) { $where .= ' AND status=?'; $params[] = $filterStatus; }
        $payments = Database::fetchAll("SELECT * FROM rent_payments WHERE $where ORDER BY period DESC", $params);
        $summary  = Database::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) AS overdue_count,
                    SUM(CASE WHEN status='partial' THEN 1 ELSE 0 END) AS partial_count,
                    SUM(amount_due) AS total_due,
                    SUM(amount_paid) AS total_paid
             FROM rent_payments WHERE tenant_id=? AND tenancy_id=?",
            [$_tenantId, $tenancyId]
        );
    }
}

// Overdue across all tenancies (for dashboard panel when no tenancy selected)
$overdueAll = Database::fetchAll(
    "SELECT rp.*, t.tenant_name, t.monthly_rent, p.name AS property_name
     FROM rent_payments rp
     JOIN tenancies t ON t.id = rp.tenancy_id
     JOIN properties p ON p.id = t.property_id
     WHERE rp.tenant_id=? AND rp.status='overdue'
     ORDER BY rp.period ASC LIMIT 50",
    [$_tenantId]
);

$pageTitle = $tenancy ? 'Payments — '.$tenancy['tenant_name'] : 'Rent Payments';
include __DIR__.'/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0">Rent Payments</h4>
    <p class="text-muted mb-0" style="font-size:.875rem;">Track monthly rent collection per tenancy</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Tenancy Selector -->
<div class="card-box mb-4">
  <form method="GET" action="<?= APP_URL ?>/rent-payments" class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label fw-semibold" style="font-size:.8rem;">Select Tenancy</label>
      <select name="tenancy_id" class="form-select" onchange="this.form.submit()">
        <option value="">— Choose a tenancy —</option>
        <?php
        $allTenancies = Database::fetchAll(
            "SELECT t.id, t.tenant_name, t.start_date, t.end_date, t.status, p.name AS property_name
             FROM tenancies t LEFT JOIN properties p ON p.id=t.property_id
             WHERE t.tenant_id=? AND t.deleted_at IS NULL ORDER BY t.start_date DESC",
            [$_tenantId]
        );
        foreach ($allTenancies as $at): ?>
        <option value="<?= $at['id'] ?>" <?= $at['id']==$tenancyId?'selected':'' ?>>
          <?= htmlspecialchars($at['tenant_name']) ?> — <?= htmlspecialchars($at['property_name']??'') ?>
          (<?= date('M Y',strtotime($at['start_date'])) ?> – <?= date('M Y',strtotime($at['end_date'])) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($tenancyId): ?>
    <div class="col-md-3">
      <label class="form-label fw-semibold" style="font-size:.8rem;">Filter Status</label>
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach(['pending','paid','partial','overdue'] as $s): ?>
        <option value="<?=$s?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </form>
</div>

<?php if ($tenancy): ?>

<!-- Tenancy Header -->
<div class="card-box mb-3">
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
      <div class="fw-bold" style="font-size:1.05rem;"><?= htmlspecialchars($tenancy['tenant_name']) ?></div>
      <div class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($tenancy['property_name']??'') ?> · <?= date('d M Y',strtotime($tenancy['start_date'])) ?> – <?= date('d M Y',strtotime($tenancy['end_date'])) ?></div>
      <?php if ($tenancy['renter_name']): ?>
      <a href="<?= APP_URL ?>/renters?view=<?= $tenancy['renter_id_val'] ?>" class="text-muted" style="font-size:.75rem;"><i class="bi bi-person me-1"></i>View Profile</a>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <!-- Generate schedule -->
      <?php if (!$payments): ?>
      <form method="POST" action="<?= APP_URL ?>/rent-payments">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="generate_schedule">
        <input type="hidden" name="tenancy_id" value="<?= $tenancy['id'] ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-calendar-plus me-1"></i>Generate Schedule</button>
      </form>
      <?php endif; ?>
      <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#recordModal">
        <i class="bi bi-plus-circle me-1"></i>Record Payment
      </button>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<?php if ($summary['total']): ?>
<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Total Due</div>
      <div class="stat-value">RM <?= number_format($summary['total_due'],0) ?></div>
      <div class="stat-label"><?= $summary['total'] ?> periods</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Total Collected</div>
      <div class="stat-value text-success">RM <?= number_format($summary['total_paid'],0) ?></div>
      <div class="stat-label"><?= $summary['paid_count'] ?> paid in full</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Outstanding</div>
      <div class="stat-value <?= ($summary['total_due']-$summary['total_paid'])>0?'text-danger':'' ?>">
        RM <?= number_format(max(0,$summary['total_due']-$summary['total_paid']),0) ?>
      </div>
      <div class="stat-label"><?= $summary['overdue_count'] ?> overdue · <?= $summary['partial_count'] ?> partial</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card-box text-center">
      <div class="text-muted mb-1" style="font-size:.75rem;">Collection Rate</div>
      <?php $rate = $summary['total_due'] > 0 ? round($summary['total_paid'] / $summary['total_due'] * 100) : 0; ?>
      <div class="stat-value <?= $rate>=90?'text-success':($rate>=70?'text-warning':'text-danger') ?>"><?= $rate ?>%</div>
      <div class="stat-label">overall</div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Payment Table -->
<?php if ($payments): ?>
<div class="card-box">
  <table class="table table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th style="font-size:.75rem;">Period</th>
        <th style="font-size:.75rem;" class="text-end">Due</th>
        <th style="font-size:.75rem;" class="text-end">Paid</th>
        <th style="font-size:.75rem;" class="text-end">Balance</th>
        <th style="font-size:.75rem;">Method</th>
        <th style="font-size:.75rem;">Receipt No.</th>
        <th style="font-size:.75rem;">Date Paid</th>
        <th style="font-size:.75rem;">Status</th>
        <th style="font-size:.75rem;"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $p):
        $balance = $p['amount_due'] - $p['amount_paid'];
        $sc = match($p['status']) { 'paid'=>'badge-green', 'partial'=>'badge-amber', 'overdue'=>'badge-red', default=>'badge bg-light text-secondary border' };
        $methodLabels = ['bank_transfer'=>'Bank Transfer','cash'=>'Cash','cheque'=>'Cheque','duitnow'=>'DuitNow','online'=>'Online','other'=>'Other'];
      ?>
      <tr>
        <td class="fw-semibold" style="font-size:.85rem;"><?= date('M Y', strtotime($p['period'].'-01')) ?></td>
        <td class="text-end" style="font-size:.85rem;">RM <?= number_format($p['amount_due'],0) ?></td>
        <td class="text-end fw-semibold text-success" style="font-size:.85rem;">RM <?= number_format($p['amount_paid'],0) ?></td>
        <td class="text-end <?= $balance>0?'text-danger':'' ?>" style="font-size:.85rem;">
          <?= $balance > 0 ? 'RM '.number_format($balance,0) : '—' ?>
        </td>
        <td style="font-size:.78rem;"><?= $methodLabels[$p['payment_method']??''] ?? '—' ?></td>
        <td style="font-size:.78rem;" class="text-muted"><?= htmlspecialchars($p['receipt_no'] ?: '—') ?></td>
        <td style="font-size:.78rem;"><?= $p['payment_date'] ? date('d M Y',strtotime($p['payment_date'])) : '—' ?></td>
        <td><span class="<?= $sc ?>" style="font-size:.72rem;"><?= ucfirst($p['status']) ?></span></td>
        <td>
          <button class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 6px;"
            onclick="fillEdit('<?= $p['period'] ?>','<?= $p['amount_due'] ?>','<?= $p['amount_paid'] ?>','<?= $p['payment_method']??'bank_transfer' ?>','<?= htmlspecialchars($p['receipt_no']??'',ENT_QUOTES) ?>','<?= $p['payment_date']??'' ?>','<?= htmlspecialchars($p['notes']??'',ENT_QUOTES) ?>')"
            data-bs-toggle="modal" data-bs-target="#recordModal">
            Edit
          </button>
          <form method="POST" action="<?= APP_URL ?>/rent-payments" class="d-inline" onsubmit="return confirm('Delete this payment record?')">
            <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="tenancy_id" value="<?= $tenancyId ?>">
            <button class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:2px 6px;"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php elseif (!$filterStatus): ?>
<div class="card-box text-center py-4">
  <div style="font-size:2.5rem;">📋</div>
  <h5 class="mt-3 mb-2">No Payment Records</h5>
  <p class="text-muted">Click <strong>Generate Schedule</strong> to auto-create monthly payment records, or <strong>Record Payment</strong> to log individually.</p>
</div>
<?php endif; ?>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">Record Payment</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= APP_URL ?>/rent-payments">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="record">
        <input type="hidden" name="tenancy_id" value="<?= $tenancy['id'] ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Period</label>
              <input type="month" name="period" id="modal_period" class="form-control" value="<?= date('Y-m') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount Due (RM)</label>
              <input type="number" name="amount_due" id="modal_due" class="form-control" value="<?= $tenancy['monthly_rent'] ?>" step="0.01" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount Paid (RM)</label>
              <input type="number" name="amount_paid" id="modal_paid" class="form-control" value="<?= $tenancy['monthly_rent'] ?>" step="0.01" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Date</label>
              <input type="date" name="payment_date" id="modal_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Method</label>
              <select name="payment_method" id="modal_method" class="form-select">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="duitnow">DuitNow</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
                <option value="online">Online</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Receipt / Ref No.</label>
              <input type="text" name="receipt_no" id="modal_receipt" class="form-control" placeholder="Optional">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Notes</label>
              <textarea name="notes" id="modal_notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- No tenancy selected — show overdue summary -->
<div class="row g-3">
  <div class="col-lg-8">
    <?php if ($overdueAll): ?>
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Overdue Payments (<?= count($overdueAll) ?>)</h6>
      </div>
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
          <th style="font-size:.75rem;">Tenant</th>
          <th style="font-size:.75rem;">Property</th>
          <th style="font-size:.75rem;">Period</th>
          <th style="font-size:.75rem;" class="text-end">Due</th>
          <th style="font-size:.75rem;" class="text-end">Paid</th>
          <th style="font-size:.75rem;"></th>
        </tr></thead>
        <tbody>
          <?php foreach ($overdueAll as $p): ?>
          <tr>
            <td class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($p['tenant_name']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($p['property_name']) ?></td>
            <td style="font-size:.82rem;"><?= date('M Y',strtotime($p['period'].'-01')) ?></td>
            <td class="text-end" style="font-size:.82rem;">RM <?= number_format($p['amount_due'],0) ?></td>
            <td class="text-end text-success" style="font-size:.82rem;">RM <?= number_format($p['amount_paid'],0) ?></td>
            <td><a href="<?= APP_URL ?>/rent-payments?tenancy_id=<?= $p['tenancy_id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px;">Manage</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card-box text-center py-5">
      <div style="font-size:3rem;">✅</div>
      <h5 class="mt-3 mb-2">No Overdue Payments</h5>
      <p class="text-muted">All payments are up to date. Select a tenancy above to view or record payments.</p>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card-box">
      <h6 class="fw-semibold mb-3">Quick Actions</h6>
      <div class="d-grid gap-2">
        <a href="<?= APP_URL ?>/tenancies" class="btn btn-outline-primary btn-sm">View All Tenancies</a>
        <a href="<?= APP_URL ?>/renters" class="btn btn-outline-secondary btn-sm">Renter Profiles</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = <<<'JS'
function fillEdit(period, due, paid, method, receipt, date, notes) {
  document.getElementById('modal_period').value  = period;
  document.getElementById('modal_due').value     = due;
  document.getElementById('modal_paid').value    = paid;
  document.getElementById('modal_method').value  = method;
  document.getElementById('modal_receipt').value = receipt;
  document.getElementById('modal_date').value    = date;
  document.getElementById('modal_notes').value   = notes;
}
JS;

include __DIR__.'/../includes/footer.php';
