<div class="pt-4">
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-2 flex-wrap">
            <?php foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
            <a href="?url=user/appointments<?= $val ? '&status=' . $val : '' ?>"
               class="px-4 py-2 text-sm rounded-lg font-medium transition-colors <?= $status === $val ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= url('doctors') ?>" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
            <i class="fa-solid fa-plus mr-1"></i> New Booking
        </a>
    </div>

    <?php if (empty($appointments)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16">
        <i class="fa-solid fa-calendar-xmark text-4xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No appointments found</p>
        <a href="<?= url('doctors') ?>" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Book with a doctor</a>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($appointments as $appt): ?>
        <?php $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray'; ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-gray-900">Dr. <?= e($appt['doctor_name']) ?></p>
                    <p class="text-sm text-blue-600"><?= e($appt['speciality'] ?? 'General Practice') ?></p>
                    <?php if ($appt['org_name']): ?>
                    <p class="text-xs text-gray-500 mt-0.5"><?= e($appt['org_name']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-3 py-1 rounded-full font-medium capitalize"><?= $appt['status'] ?></span>
            </div>
            <div class="flex gap-4 mt-3 text-sm text-gray-600">
                <span><i class="fa-regular fa-calendar text-gray-400 mr-1"></i><?= date('D, d M Y', strtotime($appt['appointment_date'])) ?></span>
                <span><i class="fa-regular fa-clock text-gray-400 mr-1"></i><?= date('H:i', strtotime($appt['appointment_time'])) ?></span>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $appt['type'] === 'online' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                    <?= ucfirst($appt['type']) ?>
                </span>
            </div>
            <?php if ($appt['notes']): ?>
            <p class="text-xs text-gray-500 mt-2 border-t border-gray-100 pt-2"><?= e($appt['notes']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
