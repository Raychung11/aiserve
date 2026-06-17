<div class="pt-4 max-w-2xl">

    <?php if (empty($notifications)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-bell-slash text-gray-400 text-xl"></i>
        </div>
        <p class="text-gray-500 font-medium">No notifications yet</p>
        <p class="text-sm text-gray-400 mt-1">You'll be notified about appointments, records, and dispensings here</p>
    </div>
    <?php else: ?>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">All Notifications</h2>
            <span class="text-xs text-gray-400"><?= count($notifications) ?> total</span>
        </div>

        <div class="divide-y divide-gray-50">
            <?php foreach ($notifications as $n): ?>
            <?php
                $icons = [
                    'appointment' => ['fa-calendar-check', 'blue'],
                    'record'      => ['fa-notes-medical',  'green'],
                    'dispensing'  => ['fa-capsules',        'purple'],
                    'approval'    => ['fa-user-check',      'teal'],
                ];
                [$icon, $color] = $icons[$n['type']] ?? ['fa-bell', 'gray'];
            ?>
            <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50">
                <div class="w-9 h-9 rounded-full bg-<?= $color ?>-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fa-solid <?= $icon ?> text-<?= $color ?>-600 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <?php if ($n['link']): ?>
                    <a href="<?= url($n['link']) ?>" class="block hover:text-blue-600">
                    <?php endif; ?>
                        <p class="text-sm font-semibold text-gray-900"><?= e($n['title']) ?></p>
                        <?php if ($n['message']): ?>
                        <p class="text-sm text-gray-500 mt-0.5 leading-relaxed"><?= e($n['message']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1"><?= ago($n['created_at']) ?></p>
                    <?php if ($n['link']): ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
