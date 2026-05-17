<div class="pt-4">
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-2 flex-wrap">
            <?php foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label): ?>
            <a href="?url=user/appointments<?= $val ? '&status=' . $val : '' ?>"
               class="px-4 py-2 text-sm rounded-lg font-medium transition-colors <?= $status === $val ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= url('doctors') ?>" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
            <i class="fa-solid fa-plus mr-1"></i> New Booking
        </a>
    </div>

    <?php if (empty($appointments)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16">
        <i class="fa-solid fa-calendar-xmark text-4xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500">No appointments found</p>
        <a href="<?= url('doctors') ?>" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Book with a doctor</a>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($appointments as $appt): ?>
        <?php
            $c = ['pending'=>'yellow','confirmed'=>'blue','completed'=>'green','cancelled'=>'red'][$appt['status']] ?? 'gray';
            $canRate = $appt['status'] === 'completed' && !in_array($appt['id'], $ratedIds ?? []);
        ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-gray-900">Dr. <?= e($appt['doctor_name']) ?></p>
                    <p class="text-sm text-blue-600"><?= e($appt['speciality'] ?? 'General Practice') ?></p>
                    <?php if ($appt['org_name']): ?>
                    <p class="text-xs text-gray-500 mt-0.5"><?= e($appt['org_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($canRate): ?>
                    <button onclick="document.getElementById('rate-<?= $appt['id'] ?>').classList.toggle('hidden')"
                            class="text-xs px-3 py-1 border border-yellow-400 text-yellow-600 rounded-full hover:bg-yellow-50 transition-colors font-medium">
                        <i class="fa-regular fa-star mr-1"></i>Rate
                    </button>
                    <?php endif; ?>
                    <span class="text-xs bg-<?= $c ?>-100 text-<?= $c ?>-700 px-3 py-1 rounded-full font-medium capitalize"><?= $appt['status'] ?></span>
                </div>
            </div>
            <div class="flex gap-4 mt-3 text-sm text-gray-600">
                <span><i class="fa-regular fa-calendar text-gray-400 mr-1"></i><?= date('D, d M Y', strtotime($appt['appointment_date'])) ?></span>
                <span><i class="fa-regular fa-clock text-gray-400 mr-1"></i><?= date('H:i', strtotime($appt['appointment_time'])) ?></span>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $appt['type'] === 'online' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                    <?= ucfirst($appt['type']) ?>
                </span>
            </div>
            <?php if ($appt['notes']): ?>
            <p class="text-xs text-gray-500 mt-2 border-t border-gray-100 pt-2"><?= e($appt['notes']) ?></p>
            <?php endif; ?>

            <?php if ($canRate): ?>
            <div id="rate-<?= $appt['id'] ?>" class="hidden mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm font-medium text-gray-700 mb-3">Rate your experience with Dr. <?= e($appt['doctor_name']) ?></p>
                <form method="POST" action="<?= url('user/appointments/rate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                    <div class="flex gap-2 mb-3" id="stars-<?= $appt['id'] ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="<?= $i ?>" class="sr-only" required>
                            <i class="fa-regular fa-star text-gray-300 text-2xl hover:text-yellow-400 transition-colors" data-star="<?= $i ?>"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" rows="2" placeholder="Share your experience (optional)…"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 mb-3"></textarea>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="px-5 py-2 bg-yellow-500 text-white font-semibold rounded-lg hover:bg-yellow-600 transition-colors text-sm">
                            Submit Review
                        </button>
                        <button type="button"
                                onclick="document.getElementById('rate-<?= $appt['id'] ?>').classList.add('hidden')"
                                class="px-5 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
                <script>
                (function() {
                    var picker = document.getElementById('stars-<?= $appt['id'] ?>');
                    picker.querySelectorAll('label').forEach(function(label, idx) {
                        label.querySelector('i').addEventListener('click', function() {
                            label.querySelector('input').checked = true;
                            picker.querySelectorAll('i').forEach(function(star, si) {
                                star.className = si <= idx
                                    ? 'fa-solid fa-star text-yellow-400 text-2xl transition-colors'
                                    : 'fa-regular fa-star text-gray-300 text-2xl transition-colors';
                            });
                        });
                    });
                })();
                </script>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
