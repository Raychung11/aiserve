<div class="pt-4">
    <!-- Search/filter -->
    <form method="GET" action="<?= url('pharmacist/medicines') ?>" class="flex gap-2 mb-6 flex-wrap">
        <input type="hidden" name="url" value="pharmacist/medicines">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search medicines..."
               class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>><?= e($cat['category']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Search</button>
    </form>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Medicine</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Category</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Unit</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Price</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Stock</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($medicines)): ?>
                <tr><td colspan="6" class="text-center py-10 text-gray-400">No medicines found</td></tr>
                <?php else: ?>
                <?php foreach ($medicines as $m): ?>
                <?php $isLow = $m['stock_qty'] <= $m['reorder_level']; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900"><?= e($m['name']) ?></p>
                        <?php if ($m['generic_name']): ?>
                        <p class="text-xs text-gray-400"><?= e($m['generic_name']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600"><?= e($m['category'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-600 capitalize"><?= e($m['unit']) ?></td>
                    <td class="px-5 py-3 text-gray-600">RM <?= number_format($m['unit_price'], 2) ?></td>
                    <td class="px-5 py-3">
                        <span class="font-semibold <?= $m['stock_qty'] == 0 ? 'text-red-600' : ($isLow ? 'text-yellow-600' : 'text-green-600') ?>">
                            <?= $m['stock_qty'] ?>
                        </span>
                        <?php if ($isLow && $m['stock_qty'] > 0): ?>
                        <span class="text-xs text-yellow-600 ml-1"><i class="fa-solid fa-triangle-exclamation"></i> Low</span>
                        <?php elseif ($m['stock_qty'] == 0): ?>
                        <span class="text-xs text-red-600 ml-1"><i class="fa-solid fa-circle-xmark"></i> Out</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $m['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
