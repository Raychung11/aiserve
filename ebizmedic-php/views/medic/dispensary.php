<div class="pt-4">

    <?php if (empty($dispensings)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-prescription-bottle text-gray-400 text-xl"></i>
        </div>
        <p class="text-gray-500 font-medium">No dispensing records</p>
        <p class="text-sm text-gray-400 mt-1">Medicines dispensed for your patients will appear here</p>
    </div>
    <?php else: ?>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Patient Dispensing Records</h2>
            <span class="text-xs text-gray-400"><?= count($dispensings) ?> record<?= count($dispensings) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="divide-y divide-gray-50">
            <?php foreach ($dispensings as $d): ?>
            <?php
                $items = Database::query(
                    'SELECT di.*, m.name AS medicine_name, m.unit
                     FROM dispensing_items di JOIN medicines m ON di.medicine_id = m.id
                     WHERE di.dispensing_id = ?', [$d['id']]
                );
            ?>
            <details class="group">
                <summary class="flex items-center justify-between px-5 py-3 cursor-pointer hover:bg-gray-50 list-none">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-user text-blue-600 text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900"><?= e($d['patient_name']) ?></p>
                            <p class="text-xs text-gray-400">by <?= e($d['dispensed_by_name']) ?> · <?= ago($d['created_at']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-sm font-bold text-green-600">RM <?= number_format($d['total_amount'], 2) ?></span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 group-open:rotate-180 transition-transform"></i>
                    </div>
                </summary>

                <div class="px-5 pb-4 bg-gray-50">
                    <table class="w-full text-xs mt-2">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="text-left pb-2 font-medium">Medicine</th>
                                <th class="text-center pb-2 font-medium">Qty</th>
                                <th class="text-left pb-2 font-medium">Dosage</th>
                                <th class="text-right pb-2 font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="py-1.5 font-medium text-gray-800"><?= e($item['medicine_name']) ?> <span class="text-gray-400 font-normal">(<?= e($item['unit']) ?>)</span></td>
                                <td class="py-1.5 text-center text-gray-600"><?= $item['quantity'] ?></td>
                                <td class="py-1.5 text-gray-500"><?= e($item['dosage_instructions'] ?: '—') ?></td>
                                <td class="py-1.5 text-right text-gray-700">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
