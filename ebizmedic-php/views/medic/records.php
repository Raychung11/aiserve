<div class="pt-4">
    <div class="flex justify-end mb-6">
        <a href="<?= url('medic/appointments') ?>" class="text-sm text-blue-600 hover:underline">
            <i class="fa-solid fa-arrow-left mr-1"></i> Go to Appointments to create a record
        </a>
    </div>

    <?php if (empty($records)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16">
        <i class="fa-solid fa-notes-medical text-5xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No medical records yet.</p>
        <p class="text-sm text-gray-400 mt-1">Create a record from an appointment page after consultation.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($records as $rec): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="font-semibold text-gray-900 text-lg"><?= e($rec['patient_name']) ?></p>
                    <p class="text-sm text-gray-500">
                        <?= date('D, d M Y', strtotime($rec['appointment_date'])) ?>
                        at <?= date('H:i', strtotime($rec['appointment_time'])) ?>
                        &middot;
                        <span class="capitalize"><?= $rec['type'] ?></span>
                    </p>
                </div>
                <span class="text-xs text-gray-400"><?= ago($rec['created_at']) ?></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <?php $fields = [
                    'chief_complaint' => ['Chief Complaint', 'fa-comment-medical', 'blue'],
                    'diagnosis'       => ['Diagnosis',       'fa-stethoscope',     'red'],
                    'treatment'       => ['Treatment',       'fa-pills',           'green'],
                    'prescription'    => ['Prescription',    'fa-prescription',    'purple'],
                ]; ?>
                <?php foreach ($fields as $key => [$label, $icon, $color]): ?>
                <?php if ($rec[$key]): ?>
                <div class="bg-<?= $color ?>-50 border border-<?= $color ?>-100 rounded-xl p-4">
                    <p class="text-xs font-semibold text-<?= $color ?>-600 mb-1 uppercase tracking-wide">
                        <i class="fa-solid <?= $icon ?> mr-1"></i><?= $label ?>
                    </p>
                    <p class="text-gray-700 whitespace-pre-line"><?= e($rec[$key]) ?></p>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($rec['follow_up_date']): ?>
            <div class="mt-4 flex items-center gap-2 text-sm text-orange-600 bg-orange-50 border border-orange-100 rounded-lg px-4 py-2 w-fit">
                <i class="fa-solid fa-calendar-plus"></i>
                Follow-up: <strong><?= date('d M Y', strtotime($rec['follow_up_date'])) ?></strong>
            </div>
            <?php endif; ?>

            <?php if ($rec['notes']): ?>
            <div class="mt-4 text-sm text-gray-500 border-t border-gray-100 pt-3">
                <span class="font-medium text-gray-600">Notes:</span> <?= e($rec['notes']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
