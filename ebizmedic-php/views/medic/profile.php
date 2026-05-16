<div class="pt-4 max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="<?= url('medic/profile/update') ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="<?= e($doctor['name']) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= e($doctor['phone'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Speciality</label>
                    <input type="text" name="speciality" value="<?= e($doctor['speciality'] ?? '') ?>" placeholder="e.g. General Practice"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Qualification</label>
                    <input type="text" name="qualification" value="<?= e($doctor['qualification'] ?? '') ?>" placeholder="MBBS, MD, etc."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Years of Experience</label>
                    <input type="number" name="experience_years" min="0" value="<?= e($doctor['experience_years'] ?? 0) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Consultation Fee (RM)</label>
                    <input type="number" name="consultation_fee" step="0.01" min="0" value="<?= e($doctor['consultation_fee'] ?? 0) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bio</label>
                <textarea name="bio" rows="4" placeholder="Tell patients about your background and expertise..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($doctor['bio'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_online" value="1"
                           <?= $doctor['is_available_online'] ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Available for Online Consultations</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_onsite" value="1"
                           <?= $doctor['is_available_onsite'] ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Available Onsite</span>
                </label>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                Save Profile
            </button>
        </form>
    </div>
</div>
