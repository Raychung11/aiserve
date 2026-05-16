<div class="pt-4">

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $statCards = [
            ['label' => 'Doctors',       'value' => $stats['doctors'],       'icon' => 'fa-user-doctor',    'color' => 'blue'],
            ['label' => 'Organisations', 'value' => $stats['organisations'],  'icon' => 'fa-hospital',       'color' => 'purple'],
            ['label' => 'Appointments',  'value' => $stats['appointments'],   'icon' => 'fa-calendar-check', 'color' => 'green'],
            ['label' => 'Patients',      'value' => $stats['users'],          'icon' => 'fa-users',          'color' => 'orange'],
        ];
        foreach ($statCards as $card): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-<?= $card['color'] ?>-100 flex items-center justify-center">
                    <i class="fa-solid <?= $card['icon'] ?> text-<?= $card['color'] ?>-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($card['value']) ?></p>
            <p class="text-sm text-gray-500 mt-0.5"><?= $card['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Appointments -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recent Appointments</h2>
                <a href="<?= url('admin/appointments') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php if (empty($recentAppointments)): ?>
                <p class="text-sm text-gray-400 text-center py-8">No appointments yet</p>
                <?php else: ?>
                <?php foreach ($recentAppointments as $appt): ?>
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?= e($appt['patient_name']) ?></p>
                        <p class="text-xs text-gray-500">Dr. <?= e($appt['doctor_name']) ?> &middot; <?= date('d M Y', strtotime($appt['appointment_date'])) ?></p>
                    </div>
                    <?php
                    $colors = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'];
                    $c = $colors[$appt['status']] ?? 'gray';
                    ?>
                    <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2.5 py-1 rounded-full font-medium capitalize">
                        <?= $appt['status'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Doctors -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recent Doctors</h2>
                <a href="<?= url('admin/doctors') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php if (empty($recentDoctors)): ?>
                <p class="text-sm text-gray-400 text-center py-8">No doctors yet</p>
                <?php else: ?>
                <?php foreach ($recentDoctors as $doc): ?>
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold flex-shrink-0">
                        <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= e($doc['name']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e($doc['speciality'] ?? 'General Practitioner') ?></p>
                    </div>
                    <span class="text-xs text-gray-400"><?= ago($doc['created_at']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
