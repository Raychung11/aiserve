<div class="pt-4">

    <?php if (empty($dispensings)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-prescription-bottle-medical text-gray-400 text-xl"></i>
        </div>
        <p class="text-gray-500 font-medium">No dispensing records yet</p>
        <p class="text-sm text-gray-400 mt-1">Medicines prescribed by your doctor will appear here after dispensing</p>
    </div>
    <?php else: ?>

    <div class="space-y-4">
        <?php foreach ($dispensings as $d): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Dispensing header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-prescription-bottle-medical text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900"><?= e($d['org_name']) ?></p>
                        <p class="text-xs text-gray-400">Dispensed by <?= e($d['dispensed_by_name']) ?> · <?= ago($d['created_at']) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-base font-bold text-green-600">RM <?= number_format($d['total_amount'], 2) ?></p>
                    <p class="text-xs text-gray-400"><?= count($d['items']) ?> medicine<?= count($d['items']) !== 1 ? 's' : '' ?></p>
                </div>
            </div>

            <!-- Dispensed items -->
            <div class="px-5 py-3">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 border-b border-gray-100">
                            <th class="text-left pb-2 font-medium">Medicine</th>
                            <th class="text-center pb-2 font-medium">Qty</th>
                            <th class="text-left pb-2 font-medium">Instructions</th>
                            <th class="text-right pb-2 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($d['items'] as $item): ?>
                        <tr>
                            <td class="py-2 font-medium text-gray-900">
                                <?= e($item['medicine_name']) ?>
                                <span class="text-xs text-gray-400 font-normal ml-1">(<?= e($item['unit']) ?>)</span>
                            </td>
                            <td class="py-2 text-center text-gray-600"><?= $item['quantity'] ?></td>
                            <td class="py-2 text-gray-500 text-xs"><?= e($item['dosage_instructions'] ?: '—') ?></td>
                            <td class="py-2 text-right text-gray-700 font-medium">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($d['notes']): ?>
            <div class="px-5 pb-4">
                <p class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-circle-info mr-1 text-blue-400"></i><?= e($d['notes']) ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
