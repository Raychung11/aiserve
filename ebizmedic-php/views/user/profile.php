<div class="pt-4 max-w-lg space-y-5">

    <!-- Photo Upload -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Profile Photo</h3>
        <div class="flex items-center gap-5">
            <?php if ($user['avatar']): ?>
            <img src="<?= asset($user['avatar']) ?>" class="w-20 h-20 rounded-full object-cover border-2 border-blue-100">
            <?php else: ?>
            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="<?= url('user/profile/photo') ?>" enctype="multipart/form-data" class="flex-1">
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
        <h3 class="font-semibold text-gray-900 mb-4">Personal Information</h3>
        <form method="POST" action="<?= url('user/profile/update') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                <input type="text" name="name" value="<?= e($user['name']) ?>" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+60 12-345 6789"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
        <form method="POST" action="<?= url('user/profile/password') ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
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
