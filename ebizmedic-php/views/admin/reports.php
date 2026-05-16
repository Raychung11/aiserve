<div class="pt-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Summary -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Appointment Summary</h3>
            <p class="text-3xl font-bold text-blue-600 mb-1"><?= number_format($totalAppointments) ?></p>
            <p class="text-sm text-gray-500 mb-4">Total appointments</p>
            <div class="space-y-2">
                <?php
                $colors = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'];
                foreach ($byStatus as $row):
                $c = $colors[$row['status']] ?? 'gray';
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-<?= $c ?>-400"></div>
                        <span class="text-sm text-gray-600 capitalize"><?= $row['status'] ?></span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900"><?= number_format($row['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Doctors -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Top Doctors</h3>
            <?php if (empty($topDoctors)): ?>
            <p class="text-sm text-gray-400">No data yet</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($topDoctors as $i => $doc): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center"><?= $i + 1 ?></span>
                        <span class="text-sm text-gray-700"><?= e($doc['name']) ?></span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900"><?= $doc['total'] ?> appts</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Monthly -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Monthly Trend</h3>
            <?php if (empty($monthly)): ?>
            <p class="text-sm text-gray-400">No data yet</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php
                $max = max(array_column($monthly, 'total'));
                foreach (array_slice($monthly, 0, 6) as $row):
                $width = $max > 0 ? round(($row['total'] / $max) * 100) : 0;
                ?>
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span><?= $row['month'] ?></span>
                        <span><?= $row['total'] ?></span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full" style="width: <?= $width ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
