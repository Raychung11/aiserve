<div class="pt-4 space-y-6">

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <?php $cards = [
            ['label'=>'Active Medicines', 'value'=>$stats['medicines'], 'icon'=>'fa-capsules',              'color'=>'blue'],
            ['label'=>'Total Dispensed',  'value'=>$stats['dispensed'], 'icon'=>'fa-receipt',               'color'=>'green'],
            ['label'=>'Low / Out Stock',  'value'=>$stats['low_stock'], 'icon'=>'fa-triangle-exclamation',  'color'=>'red'],
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
            <div class="flex items-center gap-2 p-5 border-b border-gray-100">
                <i class="fa-solid fa-triangle-exclamation text-yellow-500"></i>
                <h2 class="font-semibold text-gray-900">Low Stock Across All Organisations</h2>
            </div>
            <?php if (empty($lowStock)): ?>
            <p class="text-center text-sm text-gray-400 py-10">All medicines are well stocked</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($lowStock as $m): ?>
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($m['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($m['org_name']) ?></p>
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
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recent Dispensings</h2>
            </div>
            <?php if (empty($recentDispensings)): ?>
            <p class="text-center text-sm text-gray-400 py-10">No dispensings recorded yet</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($recentDispensings as $d): ?>
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= e($d['patient_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($d['org_name']) ?> · by <?= e($d['dispensed_by_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= ago($d['created_at']) ?></p>
                    </div>
                    <p class="text-sm font-semibold text-green-600">RM <?= number_format($d['total_amount'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
