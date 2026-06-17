<div class="pt-4">

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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

    <!-- Revenue + Today Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center mb-3">
                <i class="fa-solid fa-money-bill-wave text-white"></i>
            </div>
            <p class="text-2xl font-bold">RM <?= number_format($stats['revenue'], 0) ?></p>
            <p class="text-sm text-emerald-100 mt-0.5">Total Revenue</p>
        </div>
        <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl p-5 text-white shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center mb-3">
                <i class="fa-solid fa-chart-line text-white"></i>
            </div>
            <p class="text-2xl font-bold">RM <?= number_format($stats['month_revenue'], 0) ?></p>
            <p class="text-sm text-blue-100 mt-0.5">This Month</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-circle-check text-green-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= $stats['completed'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Completed</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-calendar-day text-sky-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= $stats['today'] ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Today</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Monthly Chart -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Appointments (Last 6 Months)</h3>
            <canvas id="orgChart" height="200"></canvas>
        </div>

        <!-- Recent Appointments -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('orgChart'), {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= $chartData ?>,
            backgroundColor: 'rgba(59,130,246,0.75)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
