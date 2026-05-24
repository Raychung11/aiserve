<div class="pt-4">

    <?php if (!empty($todayOnline)): ?>
    <?php foreach ($todayOnline as $toa): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="relative flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center text-white font-bold text-lg">
                    <?= strtoupper(substr($toa['patient_name'], 0, 1)) ?>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-green-400 rounded-full border-2 border-white animate-pulse"></span>
            </div>
            <div>
                <p class="font-bold text-gray-900">Online Patient Ready</p>
                <p class="text-sm text-gray-600"><?= e($toa['patient_name']) ?></p>
                <p class="text-xs text-green-700 font-medium mt-0.5"><i class="fa-regular fa-clock mr-1"></i>Today at <?= date('H:i', strtotime($toa['appointment_time'])) ?></p>
            </div>
        </div>
        <a href="<?= url('consultation/lobby?appointment_id=' . $toa['id']) ?>"
           class="flex-shrink-0 px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl text-sm transition-colors shadow-md hover:shadow-lg whitespace-nowrap">
            <i class="fa-solid fa-video mr-1.5"></i> Start Consultation
        </a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

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

    <!-- Doctor availability status + quick toggle -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="font-medium text-gray-900">Your Availability</p>
                <p class="text-xs text-gray-400 mt-0.5">Toggle to update patients seeing you online or onsite</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <form method="POST" action="<?= url('medic/toggle-availability') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field" value="is_available_online">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border transition-colors <?= $doctor['is_available_online'] ? 'bg-green-500 text-white border-green-500 hover:bg-green-600' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50' ?>">
                        <i class="fa-solid fa-video"></i>
                        Online: <?= $doctor['is_available_online'] ? 'ON' : 'OFF' ?>
                    </button>
                </form>
                <form method="POST" action="<?= url('medic/toggle-availability') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field" value="is_available_onsite">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border transition-colors <?= $doctor['is_available_onsite'] ? 'bg-blue-500 text-white border-blue-500 hover:bg-blue-600' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50' ?>">
                        <i class="fa-solid fa-hospital"></i>
                        Onsite: <?= $doctor['is_available_onsite'] ? 'ON' : 'OFF' ?>
                    </button>
                </form>
                <a href="<?= url('medic/profile') ?>"
                   class="flex items-center gap-2 px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                </a>
            </div>
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
