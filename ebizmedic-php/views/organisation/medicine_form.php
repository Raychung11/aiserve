<div class="pt-4 max-w-2xl">
    <div class="mb-5">
        <a href="<?= url('organisation/dispensary') ?>" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back to Dispensary
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900"><?= $medicine ? 'Edit Medicine' : 'Add New Medicine' ?></h2>
        </div>

        <form method="POST" action="<?= url($medicine ? 'organisation/dispensary/update' : 'organisation/dispensary/store') ?>" class="p-5 space-y-5">
            <?= csrf_field() ?>
            <?php if ($medicine): ?>
            <input type="hidden" name="id" value="<?= $medicine['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Medicine Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="<?= e($medicine['name'] ?? '') ?>"
                           placeholder="e.g. Paracetamol 500mg"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Generic Name</label>
                    <input type="text" name="generic_name" value="<?= e($medicine['generic_name'] ?? '') ?>"
                           placeholder="e.g. Acetaminophen"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                    <input type="text" name="category" value="<?= e($medicine['category'] ?? '') ?>"
                           placeholder="e.g. Analgesic, Antibiotic…"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit</label>
                    <select name="unit" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach (['tablet','capsule','ml','mg','sachet','patch','injection','bottle','tube','inhaler'] as $u): ?>
                        <option value="<?= $u ?>" <?= ($medicine['unit'] ?? 'tablet') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$medicine): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Initial Stock Qty</label>
                    <input type="number" name="stock_qty" value="0" min="0"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Use the Stock page to adjust stock later</p>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Reorder Level</label>
                    <input type="number" name="reorder_level" value="<?= e($medicine['reorder_level'] ?? 10) ?>" min="0"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Alert shown when stock drops below this</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit Price (RM) <span class="text-red-500">*</span></label>
                    <input type="number" name="unit_price" value="<?= e($medicine['unit_price'] ?? '0.00') ?>"
                           step="0.01" min="0" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Description / Usage Notes</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Storage requirements, usage notes…"><?= e($medicine['description'] ?? '') ?></textarea>
                </div>

                <?php if ($medicine): ?>
                <div class="col-span-2 flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="isActive" value="1" class="rounded" <?= ($medicine['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label for="isActive" class="text-sm text-gray-700">Active (available for dispensing)</label>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex gap-3 pt-2 border-t border-gray-100">
                <a href="<?= url('organisation/dispensary') ?>"
                   class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm text-center hover:bg-gray-50 font-medium">Cancel</a>
                <button type="submit"
                        class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 font-medium">
                    <?= $medicine ? 'Save Changes' : 'Add Medicine' ?>
                </button>
            </div>
        </form>
    </div>
</div>
