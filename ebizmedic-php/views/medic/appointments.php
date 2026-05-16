<div class="pt-4">
    <!-- Filters -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <?php foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
        <a href="?url=medic/appointments<?= $val ? '&status=' . $val : '' ?>"
           class="px-4 py-2 text-sm rounded-lg font-medium transition-colors <?= $status === $val ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Patient</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Date & Time</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Type</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Notes</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($appointments)): ?>
                <tr><td colspan="6" class="text-center py-10 text-gray-400">No appointments found</td></tr>
                <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900"><?= e($appt['patient_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($appt['patient_phone'] ?? '') ?></p>
                    </td>
                    <td class="px-5 py-3 text-gray-600">
                        <?= date('D, d M Y', strtotime($appt['appointment_date'])) ?><br>
                        <span class="text-xs"><?= date('H:i', strtotime($appt['appointment_time'])) ?></span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $appt['type'] === 'online' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                            <?= ucfirst($appt['type']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs max-w-xs truncate"><?= e($appt['notes'] ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <?php $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray'; ?>
                        <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2.5 py-1 rounded-full capitalize"><?= $appt['status'] ?></span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <?php if (!in_array($appt['status'], ['completed','cancelled'])): ?>
                        <form method="POST" action="<?= url('medic/appointments/update') ?>" class="inline-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $appt['id'] ?>">
                            <select name="status" class="text-xs border border-gray-300 rounded px-2 py-1 bg-white">
                                <?php foreach (['confirmed','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Update</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
