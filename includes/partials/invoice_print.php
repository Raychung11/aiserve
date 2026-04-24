<?php
// Expects: $inv (invoice row), $items (line items), $sst (sst_settings row)
// Used by einvoice.php both in admin view and standalone print mode
$co = $sst ?? [];
?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;font-family:'Segoe UI',sans-serif;">
  <!-- Header -->
  <div style="background:#0f172a;color:#fff;padding:1.75rem 2rem;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div style="font-size:1.2rem;font-weight:800;margin-bottom:.25rem;">
          <i class="bi bi-house-heart-fill" style="color:#6366f1;margin-right:.4rem;"></i>
          <?= htmlspecialchars($co['company_name'] ?: ($_user['tenant_name'] ?? 'STRHub AI')) ?>
        </div>
        <?php if(!empty($co['company_address'])): ?><div style="font-size:.78rem;color:#94a3b8;white-space:pre-line;"><?= htmlspecialchars($co['company_address']) ?></div><?php endif; ?>
        <?php if(!empty($co['company_email'])): ?><div style="font-size:.75rem;color:#94a3b8;"><?= htmlspecialchars($co['company_email']) ?></div><?php endif; ?>
        <?php if(!empty($co['company_phone'])): ?><div style="font-size:.75rem;color:#94a3b8;"><?= htmlspecialchars($co['company_phone']) ?></div><?php endif; ?>
        <?php if(!empty($co['company_tin'])): ?><div style="font-size:.72rem;color:#64748b;margin-top:.25rem;">TIN: <?= htmlspecialchars($co['company_tin']) ?></div><?php endif; ?>
        <?php if(!empty($co['sst_number']) && !empty($co['is_sst_registered'])): ?><div style="font-size:.72rem;color:#64748b;">SST Reg: <?= htmlspecialchars($co['sst_number']) ?></div><?php endif; ?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.2rem;">Invoice</div>
        <div style="font-size:1.4rem;font-weight:900;color:#a5b4fc;"><?= htmlspecialchars($inv['invoice_no']) ?></div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.35rem;">
          Date: <?= date('d M Y', strtotime($inv['invoice_date'])) ?></div>
        <?php if($inv['due_date']): ?>
        <div style="font-size:.75rem;color:<?= strtotime($inv['due_date']) < time() && $inv['status']!=='paid' ? '#f87171' : '#94a3b8' ?>;">
          Due: <?= date('d M Y', strtotime($inv['due_date'])) ?></div>
        <?php endif; ?>
        <div style="margin-top:.5rem;">
          <?php
          $statusColors = ['draft'=>'#94a3b8','issued'=>'#6366f1','paid'=>'#10b981','cancelled'=>'#ef4444','overdue'=>'#f59e0b'];
          $sc = $statusColors[$inv['status']] ?? '#94a3b8'; ?>
          <span style="background:<?= $sc ?>;color:#fff;font-size:.65rem;font-weight:700;padding:.2rem .7rem;border-radius:20px;text-transform:uppercase;letter-spacing:.05em;">
            <?= ucfirst($inv['status']) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Bill to / Property -->
  <div style="padding:1.5rem 2rem;border-bottom:1px solid #f1f5f9;background:#fafafa;">
    <div class="row g-3">
      <div class="col-md-6">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.4rem;">Bill To</div>
        <div style="font-weight:700;font-size:.95rem;color:#0f172a;"><?= htmlspecialchars($inv['billed_to_name']) ?></div>
        <?php if($inv['billed_to_email']): ?><div style="font-size:.8rem;color:#64748b;"><?= htmlspecialchars($inv['billed_to_email']) ?></div><?php endif; ?>
        <?php if($inv['billed_to_phone']): ?><div style="font-size:.8rem;color:#64748b;"><?= htmlspecialchars($inv['billed_to_phone']) ?></div><?php endif; ?>
        <?php if($inv['billed_to_ic']): ?><div style="font-size:.78rem;color:#94a3b8;">IC/Passport: <?= htmlspecialchars($inv['billed_to_ic']) ?></div><?php endif; ?>
        <?php if($inv['billed_to_tin']): ?><div style="font-size:.78rem;color:#94a3b8;">TIN: <?= htmlspecialchars($inv['billed_to_tin']) ?></div><?php endif; ?>
        <?php if($inv['billed_to_address']): ?><div style="font-size:.78rem;color:#64748b;white-space:pre-line;margin-top:.25rem;"><?= htmlspecialchars($inv['billed_to_address']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.4rem;">Details</div>
        <div style="font-size:.8rem;color:#475569;"><span style="color:#94a3b8;">Type:</span> <?= ucfirst(str_replace('_',' ',$inv['invoice_type'])) ?></div>
        <?php if(!empty($inv['property_name'])): ?><div style="font-size:.8rem;color:#475569;"><span style="color:#94a3b8;">Property:</span> <?= htmlspecialchars($inv['property_name']) ?></div><?php endif; ?>
        <?php if($inv['payment_date']): ?><div style="font-size:.8rem;color:#10b981;"><span style="color:#94a3b8;">Paid:</span> <?= date('d M Y',strtotime($inv['payment_date'])) ?> — <?= htmlspecialchars($inv['payment_method']??'') ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Line items -->
  <div style="padding:1.5rem 2rem;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
      <thead>
        <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
          <th style="text-align:left;padding:.6rem .75rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;">Description</th>
          <th style="text-align:center;padding:.6rem .75rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;width:70px;">Qty</th>
          <th style="text-align:right;padding:.6rem .75rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;width:120px;">Unit Price</th>
          <th style="text-align:right;padding:.6rem .75rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;width:120px;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:.65rem .75rem;color:#0f172a;"><?= htmlspecialchars($it['description']) ?></td>
          <td style="padding:.65rem .75rem;text-align:center;color:#64748b;"><?= rtrim(rtrim(number_format($it['quantity'],3),'0'),'.') ?></td>
          <td style="padding:.65rem .75rem;text-align:right;color:#64748b;">RM <?= number_format($it['unit_price'],2) ?></td>
          <td style="padding:.65rem .75rem;text-align:right;font-weight:600;color:#0f172a;">RM <?= number_format($it['amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <div style="margin-top:1rem;padding-top:.75rem;border-top:2px solid #e2e8f0;">
      <table style="width:100%;font-size:.875rem;">
        <tr>
          <td style="width:60%;"></td>
          <td style="text-align:right;padding:.25rem .75rem;color:#64748b;">Subtotal</td>
          <td style="text-align:right;padding:.25rem .75rem;width:130px;font-weight:600;">RM <?= number_format($inv['subtotal'],2) ?></td>
        </tr>
        <?php if($inv['sst_amount'] > 0): ?>
        <tr>
          <td></td>
          <td style="text-align:right;padding:.25rem .75rem;color:#64748b;">SST (<?= rtrim(rtrim(number_format($inv['sst_rate'],2),'0'),'.') ?>%)</td>
          <td style="text-align:right;padding:.25rem .75rem;font-weight:600;color:#d97706;">RM <?= number_format($inv['sst_amount'],2) ?></td>
        </tr>
        <?php endif; ?>
        <tr style="background:#f5f3ff;border-radius:6px;">
          <td></td>
          <td style="text-align:right;padding:.6rem .75rem;font-weight:800;color:#4f46e5;font-size:.95rem;">Total Due</td>
          <td style="text-align:right;padding:.6rem .75rem;font-weight:900;color:#4f46e5;font-size:1.05rem;">RM <?= number_format($inv['total_amount'],2) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Bank / Notes footer -->
  <?php if(!empty($co['bank_name']) || !empty($inv['notes'])): ?>
  <div style="padding:1.25rem 2rem 1.5rem;border-top:1px solid #f1f5f9;background:#fafafa;">
    <div class="row g-3">
      <?php if(!empty($co['bank_name'])): ?>
      <div class="col-md-6">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.35rem;">Payment Details</div>
        <div style="font-size:.8rem;color:#475569;"><span style="color:#94a3b8;">Bank:</span> <?= htmlspecialchars($co['bank_name']) ?></div>
        <?php if(!empty($co['bank_account'])): ?><div style="font-size:.8rem;color:#475569;"><span style="color:#94a3b8;">Account No.:</span> <?= htmlspecialchars($co['bank_account']) ?></div><?php endif; ?>
        <?php if(!empty($co['bank_holder'])): ?><div style="font-size:.8rem;color:#475569;"><span style="color:#94a3b8;">Account Name:</span> <?= htmlspecialchars($co['bank_holder']) ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if(!empty($inv['notes'])): ?>
      <div class="col-md-6">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.35rem;">Notes</div>
        <div style="font-size:.8rem;color:#64748b;line-height:1.6;white-space:pre-line;"><?= htmlspecialchars($inv['notes']) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Footer strip -->
  <div style="background:#0f172a;color:#475569;font-size:.7rem;padding:.75rem 2rem;text-align:center;">
    Generated by STRHub AI · <?= date('d M Y H:i') ?> · This is a computer-generated invoice
    <?php if(!empty($co['is_sst_registered']) && !empty($co['sst_number'])): ?> · SST Reg: <?= htmlspecialchars($co['sst_number']) ?><?php endif; ?>
  </div>
</div>
