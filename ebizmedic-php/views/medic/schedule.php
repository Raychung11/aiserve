<div class="pt-4 max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-1">Weekly Schedule</h2>
        <p class="text-sm text-gray-500 mb-6">Set your available days and hours. Patients can only book within these windows.</p>

        <form method="POST" action="<?= url('medic/schedule/update') ?>" class="space-y-3">
            <?= csrf_field() ?>

            <div class="grid grid-cols-[1.5rem_1fr_auto_auto_auto] gap-x-4 gap-y-3 items-center">
                <div></div>
                <div class="text-xs font-medium text-gray-500 uppercase">Day</div>
                <div class="text-xs font-medium text-gray-500 uppercase">Start</div>
                <div class="text-xs font-medium text-gray-500 uppercase">End</div>
                <div class="text-xs font-medium text-gray-500 uppercase">On</div>

                <?php foreach ($days as $day): ?>
                <?php $s = $byDay[$day] ?? null; ?>
                <div class="text-xs text-gray-400 font-medium"><?= strtoupper(substr($day, 0, 2)) ?></div>
                <div class="text-sm text-gray-700 capitalize font-medium"><?= $day ?></div>

                <input type="time" name="start[<?= $day ?>]" value="<?= e($s['start_time'] ?? '08:00') ?>"
                       class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

                <input type="time" name="end[<?= $day ?>]" value="<?= e($s['end_time'] ?? '17:00') ?>"
                       class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

                <label class="flex items-center justify-center cursor-pointer">
                    <input type="checkbox" name="available[<?= $day ?>]" value="1"
                           <?= ($s['is_available'] ?? 0) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                </label>
                <?php endforeach; ?>
            </div>

            <div class="pt-4">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                    Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>
