<div class="pt-4 max-w-2xl space-y-5">

    <!-- Photo Upload -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Profile Photo</h3>
        <div class="flex items-center gap-5">
            <?php if ($doctor['avatar']): ?>
            <img src="<?= asset($doctor['avatar']) ?>" class="w-20 h-20 rounded-full object-cover border-2 border-blue-100">
            <?php else: ?>
            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold">
                <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('medic/profile/photo') ?>" enctype="multipart/form-data" class="flex-1">
                <?= csrf_field() ?>
                <input type="file" name="photo" accept="image/*" required
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                    Upload Photo
                </button>
                <p class="text-xs text-gray-400 mt-2">JPG, PNG or WEBP — max 2 MB</p>
            </form>
        </div>
    </div>

    <!-- Profile Info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Profile Information</h3>
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
                    <input type="text" name="speciality" value="<?= e($doctor['speciality'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Qualification</label>
                    <input type="text" name="qualification" value="<?= e($doctor['qualification'] ?? '') ?>"
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
                <textarea name="bio" rows="4"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($doctor['bio'] ?? '') ?></textarea>
            </div>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_online" value="1" <?= $doctor['is_available_online'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-sm text-gray-700">Available Online</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_available_onsite" value="1" <?= $doctor['is_available_onsite'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-sm text-gray-700">Available Onsite</span>
                </label>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Save Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
        <form method="POST" action="<?= url('medic/profile/password') ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required autocomplete="new-password" minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="confirm_password" required autocomplete="new-password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 font-medium">
                Change Password
            </button>
        </form>
    </div>
</div>
