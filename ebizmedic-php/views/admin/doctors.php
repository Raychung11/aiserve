<div class="pt-4">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="<?= url('admin/doctors') ?>" class="flex gap-2">
            <input type="hidden" name="url" value="admin/doctors">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search doctors..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Search</button>
        </form>
        <a href="<?= url('admin/doctors/create') ?>"
           class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <i class="fa-solid fa-plus mr-1"></i> Add Doctor
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Doctor</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Speciality</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Organisation</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Availability</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Fee</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($doctors)): ?>
                <tr><td colspan="7" class="text-center py-10 text-gray-400">No doctors found</td></tr>
                <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">
                                <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= e($doc['name']) ?></p>
                                <p class="text-xs text-gray-400"><?= e($doc['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600"><?= e($doc['speciality'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-600"><?= e($doc['org_name'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <?php if ($doc['is_available_online']): ?>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full mr-1">Online</span>
                        <?php endif; ?>
                        <?php if ($doc['is_available_onsite']): ?>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Onsite</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-gray-600">RM <?= number_format($doc['consultation_fee'], 2) ?></td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $doc['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                            <?= $doc['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="<?= url('admin/doctors/edit?id=' . $doc['id']) ?>"
                           class="text-xs text-blue-600 hover:underline mr-3">Edit</a>
                        <form method="POST" action="<?= url('admin/doctors/delete') ?>" class="inline"
                              onsubmit="return confirm('Remove this doctor?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                            <button class="text-xs text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($paging['pages'] > 1): ?>
    <div class="flex justify-between items-center mt-4 text-sm text-gray-500">
        <p><?= $paging['total'] ?> total doctors</p>
        <div class="flex gap-2">
            <?php if ($paging['has_prev']): ?>
            <a href="?url=admin/doctors&page=<?= $paging['current'] - 1 ?>&search=<?= urlencode($search) ?>"
               class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">&larr;</a>
            <?php endif; ?>
            <span class="px-3 py-1.5"><?= $paging['current'] ?> / <?= $paging['pages'] ?></span>
            <?php if ($paging['has_next']): ?>
            <a href="?url=admin/doctors&page=<?= $paging['current'] + 1 ?>&search=<?= urlencode($search) ?>"
               class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">&rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
