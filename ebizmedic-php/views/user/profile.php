<div class="pt-4 max-w-lg">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div>
                <p class="font-semibold text-gray-900 text-lg"><?= e($user['name']) ?></p>
                <p class="text-sm text-gray-500"><?= e($user['email']) ?></p>
            </div>
        </div>

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
                <p class="text-xs text-gray-400 mt-1">Contact support to change your email.</p>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                Save Changes
            </button>
        </form>
    </div>
</div>
