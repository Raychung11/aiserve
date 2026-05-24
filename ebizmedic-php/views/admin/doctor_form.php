<div class="pt-4 max-w-2xl">

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">

        <form method="POST" action="<?= url($doctor ? 'admin/doctors/update' : 'admin/doctors/store') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($doctor): ?>
            <input type="hidden" name="id" value="<?= $doctor['id'] ?>">
            <?php endif; ?>

            <?php if (!$doctor): ?>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" required placeholder="Dr. Ahmad"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                    <input type="email" name="email" required placeholder="doctor@email.com"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
                Editing: <strong><?= e($doctor['name']) ?></strong> (<?= e($doctor['email']) ?>)
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Speciality</label>
                    <input type="text" name="speciality" value="<?= e($doctor['speciality'] ?? '') ?>" placeholder="e.g. Cardiology"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Organisation</label>
                    <select name="organisation_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— None —</option>
                        <?php foreach ($organisations as $org): ?>
                        <option value="<?= $org['id'] ?>" <?= ($doctor['organisation_id'] ?? '') == $org['id'] ? 'selected' : '' ?>>
                            <?= e($org['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Consultation Fee (RM)</label>
                <input type="number" name="consultation_fee" step="0.01" min="0"
                       value="<?= $doctor['consultation_fee'] ?? '0' ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bio</label>
                <textarea name="bio" rows="3" placeholder="Doctor's background and expertise..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($doctor['bio'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_online" value="1"
                           <?= ($doctor['is_available_online'] ?? 0) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Available Online</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_onsite" value="1"
                           <?= ($doctor['is_available_onsite'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Available Onsite</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                    <?= $doctor ? 'Save Changes' : 'Add Doctor' ?>
                </button>
                <a href="<?= url('admin/doctors') ?>" class="px-6 py-2.5 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
