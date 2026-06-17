<div class="pt-4 max-w-2xl space-y-6">

    <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 flex items-start gap-3 text-sm text-blue-800">
        <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 flex-shrink-0"></i>
        <p>Your health profile helps doctors provide better care. This information is only visible to healthcare professionals treating you.</p>
    </div>

    <form method="POST" action="<?= url('user/health-profile/update') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Medical Info -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-heart-pulse text-red-500"></i> Medical Information
            </h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Blood Type</label>
                <select name="blood_type"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach (['Unknown','A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                    <option value="<?= $bt ?>" <?= ($profile['blood_type'] ?? 'Unknown') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Known Allergies</label>
                <textarea name="allergies" rows="3"
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g. Penicillin, shellfish, latex, dust mites…"><?= e($profile['allergies'] ?? '') ?></textarea>
                <p class="text-xs text-gray-400 mt-1">List any medication, food, or environmental allergies</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Chronic Conditions</label>
                <textarea name="chronic_conditions" rows="3"
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g. Type 2 Diabetes, Hypertension, Asthma…"><?= e($profile['chronic_conditions'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Medications</label>
                <textarea name="current_medications" rows="3"
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g. Metformin 500mg twice daily, Amlodipine 5mg once daily…"><?= e($profile['current_medications'] ?? '') ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Include dosage and frequency if known</p>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-phone text-green-500"></i> Emergency Contact
            </h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Name</label>
                    <input type="text" name="emergency_contact_name"
                           value="<?= e($profile['emergency_contact_name'] ?? '') ?>"
                           placeholder="e.g. Ahmad Razali"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Phone</label>
                    <input type="tel" name="emergency_contact_phone"
                           value="<?= e($profile['emergency_contact_phone'] ?? '') ?>"
                           placeholder="+60 12-345 6789"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <button type="submit"
                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors text-sm">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Save Health Profile
        </button>
    </form>
</div>
