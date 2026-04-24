<?php
require_once __DIR__ . '/../includes/auth_check.php';
$activePage = 'revenue';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

// ── Annual report mode ─────────────────────────────────────────────────
if (!empty($_GET['report'])) {
    $pageTitle    = 'Annual Revenue Report';
    $pageSubtitle = 'Full year P&L summary';
    $year = (int)($_GET['year'] ?? date('Y'));
    $report = [];
    $annualIncome = $annualExpense = 0;
    for ($m = 1; $m <= 12; $m++) {
        $p   = sprintf('%d-%02d', $year, $m);
        $inc = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="income"',  [$_tenantId,$p])['s']??0);
        $exp = (float)(Database::fetchOne('SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type="expense"', [$_tenantId,$p])['s']??0);
        $annualIncome  += $inc; $annualExpense += $exp;
        $report[$p] = ['month'=>date('M',mktime(0,0,0,$m,1)),'income'=>$inc,'expense'=>$exp,'profit'=>$inc-$exp];
    }
    $annualProfit = $annualIncome - $annualExpense;
    $properties = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL', [$_tenantId]);
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="d-flex gap-2 mb-4">
      <form method="GET" action="<?= APP_URL ?>/revenue" class="d-flex gap-2">
        <input type="hidden" name="report" value="1">
        <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
          <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?=$y?>" <?=$year==$y?'selected':''?>><?=$y?></option><?php endfor; ?>
        </select>
      </form>
    </div>
    <div class="row g-3 mb-4">
      <?php foreach([['Annual Income','text-success','RM '.number_format($annualIncome,0)],['Annual Expenses','text-danger','RM '.number_format($annualExpense,0)],['Annual Net Profit',$annualProfit>=0?'text-success':'text-danger','RM '.number_format(abs($annualProfit),0)]] as [$l,$c,$v]): ?>
      <div class="col-md-4"><div class="card-box"><div class="stat-label"><?=$l?></div><div class="stat-value <?=$c?>"><?=$v?></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="card-box mb-4"><h6 class="fw-semibold mb-3">Monthly Chart</h6><canvas id="annualChart" height="70"></canvas></div>
    <div class="card-box">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Month</th><th class="text-success">Income</th><th class="text-danger">Expenses</th><th>Net Profit</th><th>Margin</th></tr></thead>
        <tbody>
          <?php foreach($report as $p=>$d): ?>
          <tr>
            <td class="fw-semibold" style="font-size:.875rem;"><?=$d['month']?> <?=$year?></td>
            <td class="text-success">RM <?=number_format($d['income'],0)?></td>
            <td class="text-danger">RM <?=number_format($d['expense'],0)?></td>
            <td class="<?=$d['profit']>=0?'text-success':'text-danger'?> fw-semibold">RM <?=number_format(abs($d['profit']),0)?></td>
            <td class="text-muted" style="font-size:.8rem;"><?=$d['income']>0?number_format(($d['profit']/$d['income'])*100,1).'%':'—'?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
          <tr><td>Total</td><td class="text-success">RM <?=number_format($annualIncome,0)?></td><td class="text-danger">RM <?=number_format($annualExpense,0)?></td>
          <td class="<?=$annualProfit>=0?'text-success':'text-danger'?>">RM <?=number_format(abs($annualProfit),0)?></td>
          <td class="text-muted"><?=$annualIncome>0?number_format(($annualProfit/$annualIncome)*100,1).'%':'—'?></td></tr>
        </tfoot>
      </table>
    </div>
    <?php $extraJs = '<script>
    new Chart(document.getElementById("annualChart"),{type:"bar",
      data:{labels:'.json_encode(array_column($report,"month")).',datasets:[
        {label:"Income",data:'.json_encode(array_column($report,"income")).',backgroundColor:"#6366f1",borderRadius:4},
        {label:"Expenses",data:'.json_encode(array_column($report,"expense")).',backgroundColor:"#fca5a5",borderRadius:4}]},
      options:{responsive:true,plugins:{legend:{position:"top"}},scales:{y:{beginAtZero:true,ticks:{callback:v=>"RM "+v.toLocaleString()}}}}});
    </script>';
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Create entry ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (!empty($_GET['action']) && $_GET['action'] === 'create')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::verifyCsrf();
        $data = [
            'tenant_id'    => $_tenantId,
            'property_id'  => (int)$_POST['property_id'],
            'period'       => $_POST['period'],
            'type'         => $_POST['type'],
            'category'     => $_POST['category'],
            'amount'       => (float)$_POST['amount'],
            'description'  => trim($_POST['description']??''),
            'payment_date' => $_POST['payment_date'] ?: null,
            'payment_ref'  => trim($_POST['payment_ref']??''),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        $entryId = Database::insert('revenue_entries', $data);

        // Auto-generate commission if rental income + property has agent
        if ($data['type'] === 'income' && $data['category'] === 'rental') {
            $prop = Database::fetchOne('SELECT p.*,u.commission_tier FROM properties p LEFT JOIN users u ON u.id=p.agent_id WHERE p.id=? AND p.tenant_id=?', [$data['property_id'], $_tenantId]);
            if ($prop && $prop['agent_id']) {
                $rate        = (float)$prop['commission_tier'] / 100;
                $platformCut = $data['amount'] * PLATFORM_REVENUE_SHARE;
                $commission  = $platformCut * $rate;
                Database::insert('commission_logs', [
                    'tenant_id'=>$_tenantId,'agent_id'=>$prop['agent_id'],'property_id'=>$data['property_id'],
                    'revenue_entry_id'=>$entryId,'revenue_amount'=>$data['amount'],
                    'commission_rate'=>$rate*100,'commission_amount'=>$commission,
                    'platform_net'=>$platformCut-$commission,'status'=>'pending',
                    'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
                ]);
            }
        }
        ActivityLog::record('revenue.created', 'Entry: RM '.$data['amount'].' ('.$data['category'].')');
        $_SESSION['flash'] = ['success' => 'Entry recorded.'];
        header('Location: ' . APP_URL . '/revenue?period='.$data['period']); exit;
    }
    // Show form
    $pageTitle    = 'Add Revenue / Expense';
    $pageSubtitle = 'Record income or expense';
    $properties   = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL', [$_tenantId]);
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row justify-content-center"><div class="col-lg-7"><div class="card-box">
    <form action="<?= APP_URL ?>/revenue" method="POST">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Property</label>
        <select name="property_id" class="form-select" required>
          <option value="">Select property</option>
          <?php foreach($properties as $p): ?><option value="<?=$p['id']?>" <?= ($_GET['property_id']??'')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Period</label>
        <input type="month" name="period" class="form-control" value="<?= date('Y-m') ?>" required></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Type</label>
        <div class="d-flex gap-2">
          <?php foreach(['income'=>'Income','expense'=>'Expense'] as $v=>$l): ?>
          <label class="border rounded-3 px-3 py-2 flex-grow-1 d-flex align-items-center gap-2" style="cursor:pointer;">
            <input type="radio" name="type" value="<?=$v?>" <?=$v==='income'?'checked':''?> required> <?=$l?>
          </label>
          <?php endforeach; ?>
        </div></div>
      <div class="col-md-8"><label class="form-label fw-semibold">Category</label>
        <select name="category" class="form-select" required>
          <optgroup label="Income"><option value="rental">Rental Income</option></optgroup>
          <optgroup label="Expenses">
            <?php foreach(['cleaning','utilities','maintenance','platform_fee','commission','insurance','assessment','management_fee','other'] as $c): ?>
            <option value="<?=$c?>"><?= ucfirst(str_replace('_',' ',$c)) ?></option>
            <?php endforeach; ?>
          </optgroup>
        </select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Amount (RM)</label>
        <div class="input-group"><span class="input-group-text">RM</span>
        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required></div></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Payment Date</label>
        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Description</label>
        <input type="text" name="description" class="form-control" placeholder="Optional"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Reference No.</label>
        <input type="text" name="payment_ref" class="form-control"></div>
      <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="<?= APP_URL ?>/revenue" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Save Entry</button>
      </div>
    </div>
    </form>
    </div></div></div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Delete entry ───────────────────────────────────────────────────────
if (!empty($_GET['delete'])) {
    Auth::verifyCsrf();
    Database::delete('revenue_entries','id=? AND tenant_id=?',[(int)$_GET['delete'],$_tenantId]);
    $_SESSION['flash'] = ['success' => 'Entry removed.'];
    header('Location: ' . APP_URL . '/revenue'); exit;
}

// ── Revenue list ───────────────────────────────────────────────────────
$pageTitle    = 'Revenue & Expenses';
$period       = $_GET['period'] ?? date('Y-m');
$pageSubtitle = 'Period: ' . $period;

$where  = 'r.tenant_id=? AND r.period=?';
$params = [$_tenantId, $period];
if (!empty($_GET['property_id'])) { $where .= ' AND r.property_id=?'; $params[] = (int)$_GET['property_id']; }
if (!empty($_GET['type']))        { $where .= ' AND r.type=?'; $params[] = $_GET['type']; }

$entries = Database::fetchAll("SELECT r.*,p.name AS property_name FROM revenue_entries r LEFT JOIN properties p ON p.id=r.property_id WHERE $where ORDER BY r.created_at DESC", $params);
$totalIncome  = (float)(Database::fetchOne("SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type='income'",  [$_tenantId,$period])['s']??0);
$totalExpense = (float)(Database::fetchOne("SELECT SUM(amount) s FROM revenue_entries WHERE tenant_id=? AND period=? AND type='expense'", [$_tenantId,$period])['s']??0);
$properties   = Database::fetchAll('SELECT id,name FROM properties WHERE tenant_id=? AND deleted_at IS NULL', [$_tenantId]);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
  <?php foreach([['Total Income','text-success','RM '.number_format($totalIncome,0)],['Total Expenses','text-danger','RM '.number_format($totalExpense,0)],['Net Profit',($totalIncome-$totalExpense)>=0?'text-success':'text-danger','RM '.number_format(abs($totalIncome-$totalExpense),0)]] as [$l,$c,$v]): ?>
  <div class="col-md-4"><div class="card-box"><div class="stat-label"><?=$l?></div><div class="stat-value <?=$c?>"><?=$v?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="d-flex gap-2 align-items-center justify-content-between mb-3 flex-wrap">
  <form class="d-flex gap-2 flex-wrap" method="GET" action="<?= APP_URL ?>/revenue">
    <input type="month" name="period" class="form-control form-control-sm" value="<?= $period ?>" style="width:auto;" onchange="this.form.submit()">
    <select name="property_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All Properties</option>
      <?php foreach($properties as $p): ?><option value="<?=$p['id']?>" <?= ($_GET['property_id']??'')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="type" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
      <option value="">All</option><option value="income" <?= ($_GET['type']??'')==='income'?'selected':'' ?>>Income</option><option value="expense" <?= ($_GET['type']??'')==='expense'?'selected':'' ?>>Expenses</option>
    </select>
  </form>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/revenue?report=1" class="btn btn-sm btn-outline-secondary">Annual Report</a>
    <a href="<?= APP_URL ?>/revenue?action=create" class="btn btn-sm btn-primary">+ Add Entry</a>
  </div>
</div>
<div class="card-box">
  <table class="table table-hover mb-0">
    <thead class="table-light"><tr><th style="font-size:.8rem;">Property</th><th style="font-size:.8rem;">Category</th><th style="font-size:.8rem;">Type</th><th style="font-size:.8rem;">Amount</th><th style="font-size:.8rem;">Date</th><th></th></tr></thead>
    <tbody>
      <?php if(!$entries): ?><tr><td colspan="6" class="text-center text-muted py-4">No entries for this period.</td></tr>
      <?php else: foreach($entries as $e): ?>
      <tr>
        <td style="font-size:.85rem;"><?= htmlspecialchars($e['property_name']??'—') ?></td>
        <td style="font-size:.85rem;"><?= ucfirst(str_replace('_',' ',$e['category'])) ?></td>
        <td><span style="font-size:.75rem;padding:.2rem .5rem;border-radius:6px;background:<?=$e['type']==='income'?'#dcfce7':'#fee2e2'?>;color:<?=$e['type']==='income'?'#15803d':'#b91c1c'?>"><?=ucfirst($e['type'])?></span></td>
        <td class="<?=$e['type']==='income'?'text-success':'text-danger'?> fw-semibold" style="font-size:.875rem;"><?=$e['type']==='income'?'+':'-'?>RM <?=number_format($e['amount'],2)?></td>
        <td class="text-muted" style="font-size:.78rem;"><?=$e['payment_date']?date('d M Y',strtotime($e['payment_date'])):'—'?></td>
        <td><a href="<?= APP_URL ?>/revenue?delete=<?=$e['id']?>&_token=<?= Auth::csrfToken() ?>" onclick="return confirm('Remove?')" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></a></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php';
