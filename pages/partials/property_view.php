<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card-box h-100">
      <h6 class="fw-semibold mb-3">Compliance</h6>
      <div class="text-center py-2">
        <div style="font-size:2.5rem;"><?= $compliance['status']==='green'?'🟢':($compliance['status']==='amber'?'🟡':'🔴') ?></div>
        <div class="fw-bold mt-1" style="color:<?= $compliance['status']==='green'?'#16a34a':($compliance['status']==='amber'?'#d97706':'#dc2626') ?>">
          <?= strtoupper($compliance['status']) ?>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size:.8rem;"><?= htmlspecialchars($compliance['recommendation']) ?></p>
      </div>
      <?php if($compliance['flags']): ?><ul class="mt-3 ps-3 mb-0" style="font-size:.78rem;color:#64748b;"><?php foreach($compliance['flags'] as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card-box h-100">
      <h6 class="fw-semibold mb-3">Strategy</h6>
      <div class="text-center py-2">
        <?= StrategyEngine::badge($strategy['recommended']) ?>
        <p class="text-muted mt-2 mb-3" style="font-size:.8rem;"><?= htmlspecialchars($strategy['description']) ?></p>
      </div>
      <?php if($strategy['reasons']): ?><ul class="ps-3 mb-3" style="font-size:.78rem;color:#64748b;"><?php foreach($strategy['reasons'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <form action="<?= APP_URL ?>/properties" method="POST">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="apply_strategy">
        <input type="hidden" name="id" value="<?= $property['id'] ?>">
        <button class="btn btn-sm btn-outline-primary w-100">Apply Recommendation</button>
      </form>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card-box h-100">
      <h6 class="fw-semibold mb-3">This Month (<?= date('Y-m') ?>)</h6>
      <div class="mb-3"><div class="text-muted" style="font-size:.75rem;">Revenue</div><div class="fw-bold text-success" style="font-size:1.4rem;">RM <?= number_format($monthRevenue,0) ?></div></div>
      <div class="mb-3"><div class="text-muted" style="font-size:.75rem;">Expenses</div><div class="fw-bold text-danger" style="font-size:1.4rem;">RM <?= number_format($monthExpense,0) ?></div></div>
      <div class="pt-2 border-top"><div class="text-muted" style="font-size:.75rem;">Net Profit</div>
        <div class="fw-bold <?= ($monthRevenue-$monthExpense)>=0?'text-success':'text-danger' ?>" style="font-size:1.4rem;">RM <?= number_format(abs($monthRevenue-$monthExpense),0) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Property Details</h6>
        <a href="<?= APP_URL ?>/properties?action=edit&id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
      </div>
      <table class="table table-sm table-borderless mb-0">
        <?php foreach([
          ['Type', ucfirst(str_replace('_',' ',$property['property_type']))],
          ['Bedrooms / Bathrooms', $property['bedrooms'].' BR / '.$property['bathrooms'].' BA'],
          ['Area', $property['area_sqft'] ? number_format($property['area_sqft'],0).' sqft' : '—'],
          ['Building', $property['strata_building'] ?: '—'],
          ['Owner', $property['owner_name'] ?: '—'],
          ['Owner Phone', $property['owner_phone'] ?: '—'],
          ['Monthly Target', 'RM '.number_format($property['monthly_target'],0)],
          ['Strategy', $property['strategy_mode']],
          ['Status', ucfirst($property['listing_status'])],
        ] as [$k,$v]): ?>
        <tr><td class="text-muted" style="font-size:.8rem;width:42%"><?= $k ?></td><td style="font-size:.875rem;"><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-box mb-3">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Investment</h6>
        <a href="<?= APP_URL ?>/investment?property_id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
      </div>
      <?php if($investment): ?>
        <?php $total = ROIEngine::totalInvestment($investment); ?>
        <div class="d-flex justify-content-between">
          <div><div class="text-muted" style="font-size:.75rem;">Total Invested</div><div class="fw-bold" style="font-size:1.2rem;">RM <?= number_format($total,0) ?></div></div>
          <div class="text-end"><div class="text-muted" style="font-size:.75rem;">Purchase</div><div class="fw-semibold">RM <?= number_format($investment['purchase_price'],0) ?></div></div>
        </div>
      <?php else: ?>
        <p class="text-muted mb-2" style="font-size:.875rem;">No investment data yet.</p>
        <a href="<?= APP_URL ?>/investment?property_id=<?= $property['id'] ?>" class="btn btn-sm btn-primary">Add Investment Data</a>
      <?php endif; ?>
    </div>
    <div class="card-box">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-semibold mb-0">Active Tenancy</h6>
        <a href="<?= APP_URL ?>/tenancies?action=create&property_id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary">+ Add</a>
      </div>
      <?php if($activeTenancy): ?>
        <?php $daysLeft = (int)((strtotime($activeTenancy['end_date'])-time())/86400); ?>
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($activeTenancy['tenant_name']) ?></div>
            <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($activeTenancy['tenant_phone']??'') ?></div>
            <div class="text-muted" style="font-size:.78rem;"><?= date('d M Y',strtotime($activeTenancy['start_date'])) ?> → <?= date('d M Y',strtotime($activeTenancy['end_date'])) ?></div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-success">RM <?= number_format($activeTenancy['monthly_rent'],0) ?>/mo</div>
            <span class="<?= $daysLeft<=30?'badge-amber':'badge-green' ?>"><?= $daysLeft ?>d left</span>
          </div>
        </div>
      <?php else: ?><p class="text-muted mb-0" style="font-size:.875rem;">No active tenancy.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="mt-3 d-flex gap-2">
  <a href="<?= APP_URL ?>/revenue?action=create&property_id=<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Revenue Entry</a>
  <form action="<?= APP_URL ?>/properties" method="POST" onsubmit="return confirm('Remove this property?')">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?= $property['id'] ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Remove</button>
  </form>
</div>
