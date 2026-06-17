<div class="pt-4 space-y-6">

    <!-- Stats + Actions bar -->
    <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-4">
            <div class="bg-white rounded-xl border border-gray-100 px-5 py-3 flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-capsules text-blue-500"></i>
                <div>
                    <p class="text-xs text-gray-500">Total Medicines</p>
                    <p class="text-lg font-bold text-gray-900"><?= count($medicines) ?></p>
                </div>
            </div>
            <?php if ($lowCount > 0): ?>
            <div class="bg-white rounded-xl border border-red-100 px-5 py-3 flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                <div>
                    <p class="text-xs text-gray-500">Low / Out of Stock</p>
                    <p class="text-lg font-bold text-red-600"><?= $lowCount ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('organisation/dispensary/history') ?>"
               class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 font-medium">
                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> History
            </a>
            <a href="<?= url('organisation/dispensary/add') ?>"
               class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 font-medium">
                <i class="fa-solid fa-plus mr-1.5"></i> Add Medicine
            </a>
        </div>
    </div>

    <!-- Search/Filter -->
    <form method="GET" action="<?= url('organisation/dispensary') ?>" class="flex gap-2 flex-wrap">
        <input type="hidden" name="url" value="organisation/dispensary">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search medicines…"
               class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>><?= e($cat['category']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Search</button>
    </form>

    <!-- Medicine Table -->
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
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($medicines)): ?>
                <tr><td colspan="7" class="text-center py-10 text-gray-400">No medicines found. <a href="<?= url('organisation/dispensary/add') ?>" class="text-blue-600 hover:underline">Add the first one.</a></td></tr>
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
                        <?php if ($m['stock_qty'] == 0): ?>
                        <span class="text-xs text-red-500 ml-1"><i class="fa-solid fa-circle-xmark"></i> Out</span>
                        <?php elseif ($isLow): ?>
                        <span class="text-xs text-yellow-500 ml-1"><i class="fa-solid fa-triangle-exclamation"></i> Low</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $m['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex gap-2">
                            <a href="<?= url('organisation/dispensary/stock') ?>?id=<?= $m['id'] ?>"
                               class="text-xs px-2.5 py-1 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 font-medium">
                                <i class="fa-solid fa-boxes-stacked mr-1"></i> Stock
                            </a>
                            <a href="<?= url('organisation/dispensary/edit') ?>?id=<?= $m['id'] ?>"
                               class="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 font-medium">
                                <i class="fa-solid fa-pen mr-1"></i> Edit
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pharmacist Staff Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-nurse text-purple-500"></i> Pharmacist Staff
                </h2>
                <button onclick="document.getElementById('addStaffModal').classList.remove('hidden')"
                        class="text-xs px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 font-medium">
                    <i class="fa-solid fa-plus mr-1"></i> Add Pharmacist
                </button>
            </div>
            <?php if (empty($staff)): ?>
            <p class="text-center text-sm text-gray-400 py-8">No pharmacists assigned yet</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($staff as $s): ?>
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-sm font-bold text-purple-700 flex-shrink-0">
                        <?= strtoupper(substr($s['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($s['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($s['email']) ?></p>
                    </div>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full <?= $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                        <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- Add Pharmacist Modal -->
<div id="addStaffModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Add Pharmacist</h3>
            <button onclick="document.getElementById('addStaffModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= url('organisation/dispensary/staff/store') ?>" class="p-5 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-400">Default password: <strong>Pharma@123</strong></p>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addStaffModal').classList.add('hidden')"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-purple-600 text-white rounded-xl text-sm hover:bg-purple-700 font-medium">Add Pharmacist</button>
            </div>
        </form>
    </div>
</div>
