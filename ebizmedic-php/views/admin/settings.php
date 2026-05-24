<div class="pt-4 max-w-xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-6">Platform Settings</h2>
        <form method="POST" action="<?= url('admin/settings/update') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Platform Name</label>
                <input type="text" name="app_name" value="<?= e(APP_NAME) ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Support Email</label>
                <input type="email" name="support_email" placeholder="support@ebizmedic.com"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-500 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i>
                More settings can be added here as your platform grows — e.g. payment gateway keys, email SMTP config, etc.
            </p>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                Save Settings
            </button>
        </form>
    </div>
</div>
