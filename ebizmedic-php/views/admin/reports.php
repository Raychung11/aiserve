<div class="pt-4">

    <!-- Range Tabs -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <?php foreach ([1=>'Last Month',3=>'3 Months',6=>'6 Months',12=>'12 Months',24=>'2 Years'] as $val=>$label): ?>
        <a href="?url=admin/reports&range=<?= $val ?>"
           class="px-4 py-2 text-sm rounded-lg font-medium transition-colors <?= $range == $val ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-calendar-check text-blue-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($totalAppointments) ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Total Appointments</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-circle-check text-green-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($completedCount) ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Completed</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900">RM <?= number_format($totalRevenue, 0) ?></p>
            <p class="text-sm text-gray-500 mt-0.5">Total Revenue</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-chart-line text-purple-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900">RM <?= number_format($monthRevenue, 0) ?></p>
            <p class="text-sm text-gray-500 mt-0.5">This Month</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Monthly Trend Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Appointment Trend</h3>
            <canvas id="trendChart" height="100"></canvas>
        </div>

        <!-- Status Donut -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Status Breakdown</h3>
            <?php if (!empty($byStatus)): ?>
            <canvas id="statusChart" height="160"></canvas>
            <div class="mt-4 space-y-2">
                <?php
                $colors = ['pending'=>['#f59e0b','yellow'],'confirmed'=>['#3b82f6','blue'],'completed'=>['#22c55e','green'],'cancelled'=>['#ef4444','red']];
                foreach ($byStatus as $row):
                    [$hex] = $colors[$row['status']] ?? ['#9ca3af','gray'];
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background:<?= $hex ?>"></div>
                        <span class="text-sm text-gray-600 capitalize"><?= $row['status'] ?></span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900"><?= number_format($row['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400 text-center py-8">No data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Doctors -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-5">Top Doctors by Completed Appointments</h3>
        <?php if (empty($topDoctors)): ?>
        <p class="text-sm text-gray-400">No data yet</p>
        <?php else: ?>
        <canvas id="doctorsChart" height="60"></canvas>
        <div class="mt-5 divide-y divide-gray-50">
            <?php foreach ($topDoctors as $i => $doc): ?>
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0"><?= $i + 1 ?></span>
                    <span class="text-sm font-medium text-gray-800"><?= e($doc['name']) ?></span>
                </div>
                <div class="flex items-center gap-6 text-sm">
                    <span class="text-gray-500"><?= $doc['total'] ?> appointments</span>
                    <span class="font-semibold text-emerald-600">RM <?= number_format($doc['revenue'], 0) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const monthLabels   = <?= $monthLabels ?>;
const monthData     = <?= $monthData ?>;
const monthCompleted = <?= $monthCompleted ?>;

// Trend chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'Total',
                data: monthData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6',
            },
            {
                label: 'Completed',
                data: monthCompleted,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.06)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#22c55e',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

<?php if (!empty($byStatus)): ?>
// Status donut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($byStatus, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($byStatus, 'total')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($r) => ($colors[$r['status']] ?? ['#9ca3af'])[0], $byStatus)) ?>,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

<?php if (!empty($topDoctors)): ?>
// Top doctors horizontal bar
new Chart(document.getElementById('doctorsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topDoctors, 'name')) ?>,
        datasets: [{
            label: 'Completed Appointments',
            data: <?= json_encode(array_column($topDoctors, 'total')) ?>,
            backgroundColor: 'rgba(59,130,246,0.8)',
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>
</script>
