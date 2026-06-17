<div class="pt-4 max-w-2xl space-y-5">

    <!-- Logo Upload -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Organisation Logo</h3>
        <div class="flex items-center gap-5">
            <?php $orgUser = \Database::queryOne('SELECT avatar FROM users WHERE id = ?', [$org['user_id']]); ?>
            <?php if (!empty($orgUser['avatar'])): ?>
            <img src="<?= asset($orgUser['avatar']) ?>" class="w-20 h-20 rounded-xl object-cover border border-gray-200">
            <?php else: ?>
            <div class="w-20 h-20 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 text-3xl font-bold">
                <?= strtoupper(substr($org['name'], 0, 1)) ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('organisation/profile/photo') ?>" enctype="multipart/form-data" class="flex-1">
                <?= csrf_field() ?>
                <input type="file" name="photo" accept="image/*" required
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 mb-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Upload Logo</button>
                <p class="text-xs text-gray-400 mt-2">JPG, PNG or WEBP — max 2 MB</p>
            </form>
        </div>
    </div>

    <!-- Profile Info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Organisation Details</h3>
        <form method="POST" action="<?= url('organisation/profile/update') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Organisation Name *</label>
                    <input type="text" name="name" value="<?= e($org['name']) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                    <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach (['clinic'=>'Clinic','hospital'=>'Hospital','specialist_center'=>'Specialist Center','wellness_center'=>'Wellness Center'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($org['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= e($org['phone'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                    <input type="text" name="city" value="<?= e($org['city'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                <input type="text" name="address" value="<?= e($org['address'] ?? '') ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($org['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Save Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
        <form method="POST" action="<?= url('organisation/profile/password') ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 font-medium">Change Password</button>
        </form>
    </div>
</div>
