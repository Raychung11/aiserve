<div class="pt-4">
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="<?= url('admin/organisations') ?>" class="flex gap-2">
            <input type="hidden" name="url" value="admin/organisations">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search organisations..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Search</button>
        </form>
        <a href="<?= url('admin/organisations/create') ?>"
           class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
            <i class="fa-solid fa-plus mr-1"></i> Add Organisation
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Organisation</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Type</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">City</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Contact</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($organisations)): ?>
                <tr><td colspan="5" class="text-center py-10 text-gray-400">No organisations found</td></tr>
                <?php else: ?>
                <?php foreach ($organisations as $org): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600 text-xs font-bold flex-shrink-0">
                                <?= strtoupper(substr($org['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= e($org['name']) ?></p>
                                <p class="text-xs text-gray-400"><?= e($org['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 capitalize"><?= e(str_replace('_', ' ', $org['type'] ?? '')) ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= e($org['city'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= e($org['phone'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $org['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                            <?= $org['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
