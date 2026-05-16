<div class="pt-4">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $cards = [
            ['label'=>'Doctors',      'value'=>$stats['doctors'],      'icon'=>'fa-user-doctor',    'color'=>'blue'],
            ['label'=>'Services',     'value'=>$stats['services'],     'icon'=>'fa-stethoscope',    'color'=>'purple'],
            ['label'=>'Appointments', 'value'=>$stats['appointments'], 'icon'=>'fa-calendar-check', 'color'=>'green'],
            ['label'=>'Pending',      'value'=>$stats['pending'],      'icon'=>'fa-clock',          'color'=>'yellow'],
        ];
        foreach ($cards as $card): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-<?= $card['color'] ?>-100 flex items-center justify-center mb-3">
                <i class="fa-solid <?= $card['icon'] ?> text-<?= $card['color'] ?>-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= $card['value'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5"><?= $card['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Recent Appointments</h2>
            <a href="<?= url('organisation/appointments') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recentAppointments)): ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fa-solid fa-calendar-xmark text-3xl mb-2 block"></i>
                <p class="text-sm">No appointments yet</p>
            </div>
            <?php else: ?>
            <?php foreach ($recentAppointments as $appt): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= e($appt['patient_name']) ?></p>
                    <p class="text-xs text-gray-500">Dr. <?= e($appt['doctor_name']) ?> &middot; <?= date('d M Y', strtotime($appt['appointment_date'])) ?></p>
                </div>
                <?php $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray'; ?>
                <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2.5 py-1 rounded-full capitalize"><?= $appt['status'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
