<div class="pt-4">
    <div class="mb-5">
        <a href="<?= url('organisation/dispensary') ?>" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back to Dispensary
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Dispensing History</h2>
            <span class="text-xs text-gray-400"><?= $paging['total'] ?> total</span>
        </div>

        <?php if (empty($dispensings)): ?>
        <p class="text-center text-sm text-gray-400 py-12">No dispensings recorded yet</p>
        <?php else: ?>
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
                            <i class="fa-solid fa-prescription-bottle text-blue-600 text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900"><?= e($d['patient_name']) ?></p>
                            <p class="text-xs text-gray-400">Dispensed by <?= e($d['dispensed_by_name']) ?> · <?= ago($d['created_at']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0 ml-4">
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
                                <th class="text-right pb-2 font-medium">Unit Price</th>
                                <th class="text-right pb-2 font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="py-1.5 font-medium text-gray-800"><?= e($item['medicine_name']) ?></td>
                                <td class="py-1.5 text-center text-gray-600"><?= $item['quantity'] ?> <?= e($item['unit']) ?></td>
                                <td class="py-1.5 text-gray-500"><?= e($item['dosage_instructions'] ?: '—') ?></td>
                                <td class="py-1.5 text-right text-gray-500">RM <?= number_format($item['unit_price'], 2) ?></td>
                                <td class="py-1.5 text-right font-medium text-gray-700">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($d['notes']): ?>
                    <p class="mt-2 text-xs text-gray-500 italic"><?= e($d['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($paging['pages'] > 1): ?>
        <div class="flex justify-center gap-1 px-5 py-4 border-t border-gray-100">
            <?php for ($p = 1; $p <= $paging['pages']; $p++): ?>
            <a href="<?= url('organisation/dispensary/history') ?>?page=<?= $p ?>"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium <?= $p == $paging['page'] ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
