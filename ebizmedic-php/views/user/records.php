<div class="pt-4">
    <?php if (empty($records)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16">
        <i class="fa-solid fa-notes-medical text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No medical records yet.</p>
        <p class="text-sm text-gray-400 mt-1">Records will appear here after your consultations are completed.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($records as $rec): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="font-semibold text-gray-900">Dr. <?= e($rec['doctor_name']) ?></p>
                    <p class="text-sm text-blue-600"><?= e($rec['speciality'] ?? 'General Practice') ?></p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <?= date('D, d M Y', strtotime($rec['appointment_date'])) ?>
                        &middot; <?= ucfirst($rec['type']) ?> consultation
                    </p>
                </div>
                <span class="text-xs text-gray-400"><?= date('d M Y', strtotime($rec['created_at'])) ?></span>
            </div>

            <div class="space-y-3 text-sm">
                <?php if ($rec['diagnosis']): ?>
                <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-red-600 mb-1 uppercase tracking-wide">
                        <i class="fa-solid fa-stethoscope mr-1"></i>Diagnosis
                    </p>
                    <p class="text-gray-800"><?= nl2br(e($rec['diagnosis'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($rec['treatment']): ?>
                <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-green-600 mb-1 uppercase tracking-wide">
                        <i class="fa-solid fa-pills mr-1"></i>Treatment
                    </p>
                    <p class="text-gray-800"><?= nl2br(e($rec['treatment'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($rec['prescription']): ?>
                <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-purple-600 mb-1 uppercase tracking-wide">
                        <i class="fa-solid fa-prescription mr-1"></i>Prescription
                    </p>
                    <p class="text-gray-800 font-mono text-xs whitespace-pre-line"><?= e($rec['prescription']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($rec['follow_up_date']): ?>
            <div class="mt-4 flex items-center gap-2 text-sm font-medium text-orange-600 bg-orange-50 border border-orange-100 rounded-lg px-4 py-2.5 w-fit">
                <i class="fa-solid fa-calendar-plus"></i>
                Follow-up appointment: <?= date('D, d M Y', strtotime($rec['follow_up_date'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
