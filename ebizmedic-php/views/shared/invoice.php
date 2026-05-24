<div class="pt-4 max-w-2xl">

    <!-- Print button — hidden on print -->
    <div class="flex items-center justify-between mb-5 print:hidden">
        <a href="javascript:history.back()" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back
        </a>
        <button onclick="window.print()"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
            <i class="fa-solid fa-print mr-1.5"></i> Print Invoice
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 print:shadow-none print:rounded-none print:border-0">

        <!-- Header -->
        <div class="flex items-start justify-between mb-8 pb-6 border-b border-gray-100">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-heart-pulse text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-lg text-gray-900">EbizMedic</span>
                </div>
                <p class="text-sm font-semibold text-gray-700"><?= e($dispensing['org_name']) ?></p>
                <?php if ($dispensing['org_address']): ?>
                <p class="text-xs text-gray-500"><?= e($dispensing['org_address']) ?><?= $dispensing['org_city'] ? ', ' . e($dispensing['org_city']) : '' ?></p>
                <?php endif; ?>
                <?php if ($dispensing['org_phone']): ?>
                <p class="text-xs text-gray-500"><?= e($dispensing['org_phone']) ?></p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900">INVOICE</p>
                <p class="text-sm text-gray-500 mt-1">#<?= str_pad($dispensing['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?= date('d M Y, H:i', strtotime($dispensing['created_at'])) ?></p>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Billed To</p>
                <p class="text-sm font-semibold text-gray-900"><?= e($dispensing['patient_name']) ?></p>
                <?php if ($dispensing['patient_phone']): ?>
                <p class="text-xs text-gray-500"><?= e($dispensing['patient_phone']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Dispensed By</p>
                <p class="text-sm font-semibold text-gray-900"><?= e($dispensing['dispensed_by_name']) ?></p>
                <p class="text-xs text-gray-500"><?= e($dispensing['org_name']) ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left pb-3 font-semibold text-gray-700">Medicine</th>
                    <th class="text-center pb-3 font-semibold text-gray-700">Unit</th>
                    <th class="text-center pb-3 font-semibold text-gray-700">Qty</th>
                    <th class="text-right pb-3 font-semibold text-gray-700">Unit Price</th>
                    <th class="text-right pb-3 font-semibold text-gray-700">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr class="border-b border-gray-50">
                    <td class="py-3">
                        <p class="font-medium text-gray-900"><?= e($item['medicine_name']) ?></p>
                        <?php if ($item['generic_name']): ?>
                        <p class="text-xs text-gray-400"><?= e($item['generic_name']) ?></p>
                        <?php endif; ?>
                        <?php if ($item['dosage_instructions']): ?>
                        <p class="text-xs text-blue-600 mt-0.5"><?= e($item['dosage_instructions']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 text-center text-gray-600 capitalize"><?= e($item['unit']) ?></td>
                    <td class="py-3 text-center text-gray-700"><?= $item['quantity'] ?></td>
                    <td class="py-3 text-right text-gray-600">RM <?= number_format($item['unit_price'], 2) ?></td>
                    <td class="py-3 text-right font-semibold text-gray-800">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total -->
        <div class="flex justify-end mb-6">
            <div class="w-64">
                <div class="flex justify-between text-sm text-gray-600 py-1">
                    <span>Subtotal</span>
                    <span>RM <?= number_format($dispensing['total_amount'], 2) ?></span>
                </div>
                <div class="flex justify-between text-sm text-gray-600 py-1 border-b border-gray-200">
                    <span>Tax (0%)</span>
                    <span>RM 0.00</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 text-lg pt-2">
                    <span>Total</span>
                    <span>RM <?= number_format($dispensing['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <?php if ($dispensing['notes']): ?>
        <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-600 mb-6">
            <span class="font-medium">Notes:</span> <?= e($dispensing['notes']) ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 pt-6 border-t border-gray-100">
            <p>Thank you for choosing EbizMedic.</p>
            <p class="mt-0.5">This is a computer-generated invoice and does not require a signature.</p>
        </div>
    </div>
</div>

<style>
@media print {
    body { background: white; }
    aside, header { display: none !important; }
    .flex-1 { width: 100% !important; }
    main { padding: 0 !important; }
}
</style>
