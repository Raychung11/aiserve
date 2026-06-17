<div class="pt-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Add Service Form -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Add New Service</h3>
            <form method="POST" action="<?= url('organisation/services/store') ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service Name *</label>
                    <input type="text" name="name" required placeholder="e.g. General Consultation"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief description..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (RM)</label>
                        <input type="number" name="price" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (min)</label>
                        <input type="number" name="duration_minutes" min="5" value="30"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <button type="submit" class="w-full py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Add Service</button>
            </form>
        </div>

        <!-- Services List -->
        <div class="lg:col-span-2">
            <?php if (empty($services)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                <i class="fa-solid fa-stethoscope text-4xl mb-3 block"></i>
                <p>No services yet. Add your first service.</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($services as $svc): ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900"><?= e($svc['name']) ?></p>
                        <?php if ($svc['description']): ?>
                        <p class="text-xs text-gray-500 mt-0.5"><?= e($svc['description']) ?></p>
                        <?php endif; ?>
                        <div class="flex gap-3 mt-1">
                            <span class="text-xs text-gray-500">RM <?= number_format($svc['price'], 2) ?></span>
                            <span class="text-xs text-gray-400"><?= $svc['duration_minutes'] ?> min</span>
                        </div>
                    </div>
                    <form method="POST" action="<?= url('organisation/services/delete') ?>"
                          onsubmit="return confirm('Remove this service?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $svc['id'] ?>">
                        <button class="text-xs text-red-600 hover:underline px-3 py-1.5">Remove</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
