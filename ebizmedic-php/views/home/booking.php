<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="mb-6">
        <a href="<?= url('doctors/show?id=' . $doctor['id']) ?>" class="text-sm text-blue-600 hover:underline">&larr; Back to Doctor Profile</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Doctor Header -->
        <div class="p-6 border-b border-gray-100 bg-blue-50">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 text-xl font-bold">
                    <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900 text-lg"><?= e($doctor['name']) ?></h2>
                    <p class="text-sm text-blue-600"><?= e($doctor['speciality'] ?? 'General Practitioner') ?></p>
                    <?php if ($doctor['consultation_fee'] > 0): ?>
                    <p class="text-xs text-gray-500 mt-0.5">Consultation fee: RM <?= number_format($doctor['consultation_fee'], 2) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <form method="POST" action="<?= url('booking/store') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">

            <!-- Service -->
            <?php if (!empty($services)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Service</label>
                <select name="service_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Select a service —</option>
                    <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>"><?= e($svc['name']) ?> — RM <?= number_format($svc['price'], 2) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Consultation Type</label>
                <div class="flex gap-3">
                    <?php if ($doctor['is_available_online']): ?>
                    <label class="flex items-center gap-2 cursor-pointer flex-1 border border-gray-300 rounded-lg px-4 py-2.5 hover:border-blue-500 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-colors">
                        <input type="radio" name="type" value="online" class="text-blue-600">
                        <span class="text-sm text-gray-700"><i class="fa-solid fa-video text-green-500 mr-1"></i> Online</span>
                    </label>
                    <?php endif; ?>
                    <?php if ($doctor['is_available_onsite']): ?>
                    <label class="flex items-center gap-2 cursor-pointer flex-1 border border-gray-300 rounded-lg px-4 py-2.5 hover:border-blue-500 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-colors">
                        <input type="radio" name="type" value="onsite" <?= !$doctor['is_available_online'] ? 'checked' : '' ?> class="text-blue-600">
                        <span class="text-sm text-gray-700"><i class="fa-solid fa-hospital text-blue-500 mr-1"></i> Onsite</span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Appointment Date *</label>
                    <input type="date" name="appointment_date" required
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Preferred Time *</label>
                    <input type="time" name="appointment_time" required value="09:00"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes (optional)</label>
                <textarea name="notes" rows="3" placeholder="Describe your symptoms or reason for visit..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <?php if (!empty($schedules)): ?>
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-700">
                <p class="font-medium mb-1"><i class="fa-solid fa-clock mr-1"></i>Doctor's available hours:</p>
                <?php foreach ($schedules as $s): ?>
                <p class="capitalize"><?= $s['day_of_week'] ?>: <?= date('H:i', strtotime($s['start_time'])) ?> – <?= date('H:i', strtotime($s['end_time'])) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button type="submit"
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                Confirm Booking
            </button>
        </form>
    </div>
</div>
