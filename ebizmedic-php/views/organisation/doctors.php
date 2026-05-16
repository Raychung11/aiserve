<div class="pt-4">
    <div class="flex justify-end mb-6">
        <a href="<?= url('organisation/doctors/add') ?>"
           class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
            <i class="fa-solid fa-plus mr-1"></i> Add Doctor
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Doctor</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Speciality</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Availability</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Fee</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($doctors)): ?>
                <tr><td colspan="5" class="text-center py-10 text-gray-400">No doctors yet. Add your first doctor.</td></tr>
                <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                                <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= e($doc['name']) ?></p>
                                <p class="text-xs text-gray-400"><?= e($doc['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600"><?= e($doc['speciality'] ?? '—') ?></td>
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
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
