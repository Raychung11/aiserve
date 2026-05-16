<div class="pt-4">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $cards = [
            ['label'=>"Today's Appointments",'value'=>$stats['today'],    'icon'=>'fa-calendar-day',   'color'=>'blue'],
            ['label'=>'Pending',             'value'=>$stats['pending'],  'icon'=>'fa-clock',           'color'=>'yellow'],
            ['label'=>'Completed',           'value'=>$stats['completed'],'icon'=>'fa-circle-check',    'color'=>'green'],
            ['label'=>'Total',               'value'=>$stats['total'],    'icon'=>'fa-calendar-check',  'color'=>'purple'],
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

    <!-- Doctor availability status -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-900">Your Availability</p>
                <div class="flex gap-2 mt-1">
                    <?php if ($doctor['is_available_online']): ?>
                    <span class="text-xs bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full">Online consultations ON</span>
                    <?php else: ?>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-0.5 rounded-full">Online consultations OFF</span>
                    <?php endif; ?>
                    <?php if ($doctor['is_available_onsite']): ?>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full">Onsite available</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= url('medic/profile') ?>" class="text-sm text-blue-600 hover:underline">Edit profile</a>
        </div>
    </div>

    <!-- Upcoming appointments -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Upcoming Appointments</h2>
            <a href="<?= url('medic/appointments') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($upcoming)): ?>
            <div class="text-center py-10 text-gray-400">
                <i class="fa-solid fa-calendar-xmark text-3xl mb-2 block"></i>
                <p class="text-sm">No upcoming appointments</p>
            </div>
            <?php else: ?>
            <?php foreach ($upcoming as $appt): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">
                        <?= strtoupper(substr($appt['patient_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($appt['patient_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= date('D, d M Y', strtotime($appt['appointment_date'])) ?> at <?= date('H:i', strtotime($appt['appointment_time'])) ?></p>
                    </div>
                </div>
                <?php $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray'; ?>
                <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2.5 py-1 rounded-full capitalize"><?= $appt['status'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
