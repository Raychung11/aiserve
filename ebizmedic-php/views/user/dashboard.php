<div class="pt-4">

    <?php if (!empty($todayOnline)): ?>
    <?php foreach ($todayOnline as $toa): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="relative flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-lg">
                    <?= strtoupper(substr($toa['doctor_name'], 0, 1)) ?>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-green-400 rounded-full border-2 border-white animate-pulse"></span>
            </div>
            <div>
                <p class="font-bold text-gray-900">Online Consultation Ready</p>
                <p class="text-sm text-gray-600">Dr. <?= e($toa['doctor_name']) ?> &middot; <?= e($toa['speciality'] ?? 'General Practice') ?></p>
                <p class="text-xs text-green-700 font-medium mt-0.5"><i class="fa-regular fa-clock mr-1"></i>Today at <?= date('H:i', strtotime($toa['appointment_time'])) ?></p>
            </div>
        </div>
        <a href="<?= url('consultation/lobby?appointment_id=' . $toa['id']) ?>"
           class="flex-shrink-0 px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl text-sm transition-colors shadow-md hover:shadow-lg whitespace-nowrap">
            <i class="fa-solid fa-video mr-1.5"></i> Join Now
        </a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <?php
        $cards = [
            ['label'=>'Total Appointments','value'=>$stats['total'],    'icon'=>'fa-calendar-check','color'=>'blue'],
            ['label'=>'Pending',           'value'=>$stats['pending'],  'icon'=>'fa-clock',         'color'=>'yellow'],
            ['label'=>'Completed',         'value'=>$stats['completed'],'icon'=>'fa-circle-check',  'color'=>'green'],
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

    <div class="flex gap-4 mb-8">
        <a href="<?= url('doctors') ?>"
           class="flex-1 py-4 bg-blue-600 text-white text-center rounded-2xl font-medium hover:bg-blue-700 transition-colors">
            <i class="fa-solid fa-magnifying-glass block text-2xl mb-1"></i>
            <span class="text-sm">Find a Doctor</span>
        </a>
        <a href="<?= url('user/appointments') ?>"
           class="flex-1 py-4 bg-white border border-gray-200 text-gray-700 text-center rounded-2xl font-medium hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-calendar-check block text-2xl mb-1 text-green-600"></i>
            <span class="text-sm">My Appointments</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Recent Appointments</h2>
            <a href="<?= url('user/appointments') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recent)): ?>
            <div class="text-center py-10">
                <i class="fa-solid fa-calendar-xmark text-3xl text-gray-300 mb-3 block"></i>
                <p class="text-sm text-gray-400">No appointments yet</p>
                <a href="<?= url('doctors') ?>" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Book your first appointment</a>
            </div>
            <?php else: ?>
            <?php foreach ($recent as $appt): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">Dr. <?= e($appt['doctor_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= e($appt['speciality'] ?? 'General Practice') ?> &middot; <?= date('d M Y', strtotime($appt['appointment_date'])) ?></p>
                </div>
                <?php $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray'; ?>
                <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2.5 py-1 rounded-full capitalize"><?= $appt['status'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
