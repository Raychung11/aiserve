<div class="pt-4">

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php $cards = [
            ['label'=>'Medicines',       'value'=>$stats['medicines'], 'icon'=>'fa-capsules',      'color'=>'blue'],
            ['label'=>'Dispensed Today', 'value'=>$stats['today'],    'icon'=>'fa-hand-holding-medical','color'=>'green'],
            ['label'=>'Total Dispensed', 'value'=>$stats['dispensed'],'icon'=>'fa-receipt',        'color'=>'purple'],
            ['label'=>'Low Stock',       'value'=>$stats['low_stock'],'icon'=>'fa-triangle-exclamation','color'=>'red'],
        ]; foreach ($cards as $card): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-<?= $card['color'] ?>-100 flex items-center justify-center mb-3">
                <i class="fa-solid <?= $card['icon'] ?> text-<?= $card['color'] ?>-600"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($card['value']) ?></p>
            <p class="text-sm text-gray-500 mt-0.5"><?= $card['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500"></i> Low Stock Alerts
                </h2>
                <a href="<?= url('pharmacist/medicines') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <?php if (empty($lowStock)): ?>
            <p class="text-center text-sm text-gray-400 py-10">All medicines are well stocked</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($lowStock as $m): ?>
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($m['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($m['generic_name'] ?? '') ?> · <?= e($m['unit']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold <?= $m['stock_qty'] == 0 ? 'text-red-600' : 'text-yellow-600' ?>">
                            <?= $m['stock_qty'] ?> left
                        </p>
                        <p class="text-xs text-gray-400">reorder at <?= $m['reorder_level'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Dispensings -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recent Dispensings</h2>
                <a href="<?= url('pharmacist/history') ?>" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <?php if (empty($recentDispensings)): ?>
            <p class="text-center text-sm text-gray-400 py-10">No dispensings yet</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($recentDispensings as $d): ?>
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($d['patient_name']) ?></p>
                        <p class="text-xs text-gray-400">by <?= e($d['dispensed_by_name']) ?> · <?= ago($d['created_at']) ?></p>
                    </div>
                    <p class="text-sm font-semibold text-green-600">RM <?= number_format($d['total_amount'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="mt-6 flex gap-4">
        <a href="<?= url('pharmacist/dispense') ?>"
           class="flex-1 py-4 bg-blue-600 text-white text-center rounded-2xl font-semibold hover:bg-blue-700 transition-colors">
            <i class="fa-solid fa-hand-holding-medical block text-2xl mb-1"></i>
            <span class="text-sm">Dispense Medicines</span>
        </a>
        <a href="<?= url('pharmacist/medicines') ?>"
           class="flex-1 py-4 bg-white border border-gray-200 text-gray-700 text-center rounded-2xl font-semibold hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-capsules block text-2xl mb-1 text-blue-500"></i>
            <span class="text-sm">View Inventory</span>
        </a>
        <a href="<?= url('pharmacist/history') ?>"
           class="flex-1 py-4 bg-white border border-gray-200 text-gray-700 text-center rounded-2xl font-semibold hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-clock-rotate-left block text-2xl mb-1 text-purple-500"></i>
            <span class="text-sm">History</span>
        </a>
    </div>
</div>
