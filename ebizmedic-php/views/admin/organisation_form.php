<div class="pt-4 max-w-xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="<?= url('admin/organisations/store') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Organisation Name *</label>
                <input type="text" name="name" required placeholder="e.g. Klinik Sehat"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Admin Email *</label>
                <input type="email" name="email" required placeholder="admin@clinic.com"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                    <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="clinic">Clinic</option>
                        <option value="hospital">Hospital</option>
                        <option value="specialist_center">Specialist Center</option>
                        <option value="wellness_center">Wellness Center</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                    <input type="text" name="city" placeholder="Kuala Lumpur"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                <input type="text" name="phone" placeholder="+60 3-1234 5678"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                <i class="fa-solid fa-circle-info text-yellow-500 mr-1"></i>
                Default password will be <strong>Org@123456</strong> — remind the organisation to change it.
            </p>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">Add Organisation</button>
                <a href="<?= url('admin/organisations') ?>" class="px-6 py-2.5 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
