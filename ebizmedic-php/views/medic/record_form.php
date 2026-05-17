<div class="pt-4 max-w-2xl">

    <!-- Appointment info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <p class="text-sm font-semibold text-blue-800 mb-0.5">
            <i class="fa-solid fa-user mr-1"></i> Patient: <?= e($appointment['patient_name']) ?>
        </p>
        <p class="text-xs text-blue-600">
            <?= date('D, d M Y', strtotime($appointment['appointment_date'])) ?>
            at <?= date('H:i', strtotime($appointment['appointment_time'])) ?>
            &middot; <?= ucfirst($appointment['type']) ?>
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="<?= url('medic/records/store') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Chief Complaint</label>
                <textarea name="chief_complaint" rows="2" required
                          placeholder="What brings the patient in today?"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Diagnosis</label>
                <textarea name="diagnosis" rows="3" required
                          placeholder="Clinical diagnosis..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Treatment Plan</label>
                <textarea name="treatment" rows="3"
                          placeholder="Recommended treatment, procedures, lifestyle changes..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Prescription</label>
                <textarea name="prescription" rows="4"
                          placeholder="Medication name — dosage — frequency&#10;e.g. Paracetamol 500mg — 1 tablet — 3x daily for 5 days"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Follow-up Date (optional)</label>
                <input type="date" name="follow_up_date"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Additional Notes</label>
                <textarea name="notes" rows="2"
                          placeholder="Any other remarks..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Record
                </button>
                <a href="<?= url('medic/appointments') ?>" class="px-6 py-2.5 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
