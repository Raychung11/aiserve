<div class="pt-4">
    <div class="mb-5 flex items-center justify-between">
        <a href="<?= url('organisation/dispensary') ?>" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back to Dispensary
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Stock Update Form -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Medicine Info -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs text-gray-500 mb-1">Medicine</p>
                <p class="text-lg font-bold text-gray-900"><?= e($medicine['name']) ?></p>
                <?php if ($medicine['generic_name']): ?>
                <p class="text-sm text-gray-400"><?= e($medicine['generic_name']) ?></p>
                <?php endif; ?>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold <?= $medicine['stock_qty'] == 0 ? 'text-red-600' : ($medicine['stock_qty'] <= $medicine['reorder_level'] ? 'text-yellow-600' : 'text-green-600') ?>">
                            <?= $medicine['stock_qty'] ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">Current Stock</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-gray-600"><?= $medicine['reorder_level'] ?></p>
                        <p class="text-xs text-gray-500 mt-0.5">Reorder Level</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Price: <strong>RM <?= number_format($medicine['unit_price'], 2) ?></strong> / <?= e($medicine['unit']) ?></p>
            </div>

            <!-- Add Stock Form -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Update Stock</h3>
                <form method="POST" action="<?= url('organisation/dispensary/stock/save') ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="medicine_id" value="<?= $medicine['id'] ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" id="stockType" onchange="updateTypeLabel()"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="in">Add Stock (Received)</option>
                            <option value="adjustment">Set Stock Level (Adjust)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5" id="qtyLabel">Quantity to Add</label>
                        <input type="number" name="quantity" min="1" required
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Reference</label>
                        <input type="text" name="reference" placeholder="e.g. Invoice #1234, Delivery order"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Optional notes…"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-green-600 text-white rounded-xl text-sm hover:bg-green-700 font-medium">
                        <i class="fa-solid fa-check mr-1.5"></i> Update Stock
                    </button>
                </form>
            </div>
        </div>

        <!-- Movement History -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Stock Movement History</h3>
                </div>
                <?php if (empty($movements)): ?>
                <p class="text-center text-sm text-gray-400 py-10">No movements recorded yet</p>
                <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium text-gray-600">Date</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-600">Type</th>
                            <th class="text-right px-5 py-3 font-medium text-gray-600">Qty</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-600">Reference</th>
                            <th class="text-left px-5 py-3 font-medium text-gray-600">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($movements as $mv): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500 text-xs"><?= date('d M Y H:i', strtotime($mv['created_at'])) ?></td>
                            <td class="px-5 py-3">
                                <?php if ($mv['type'] === 'in'): ?>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                    <i class="fa-solid fa-arrow-up mr-0.5"></i> In
                                </span>
                                <?php elseif ($mv['type'] === 'out'): ?>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">
                                    <i class="fa-solid fa-arrow-down mr-0.5"></i> Out
                                </span>
                                <?php else: ?>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium">Adjust</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold <?= $mv['type'] === 'in' ? 'text-green-600' : ($mv['type'] === 'out' ? 'text-red-600' : 'text-gray-600') ?>">
                                <?= $mv['type'] === 'in' ? '+' : ($mv['type'] === 'out' ? '-' : '') ?><?= $mv['quantity'] ?>
                            </td>
                            <td class="px-5 py-3 text-gray-600 text-xs"><?= e($mv['reference'] ?? '—') ?></td>
                            <td class="px-5 py-3 text-gray-500 text-xs"><?= e($mv['by_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function updateTypeLabel() {
    const type = document.getElementById('stockType').value;
    document.getElementById('qtyLabel').textContent = type === 'adjustment' ? 'New Stock Level' : 'Quantity to Add';
}
</script>
