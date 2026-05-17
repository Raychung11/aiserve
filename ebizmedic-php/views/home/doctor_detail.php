<div class="max-w-4xl mx-auto px-4 py-10">
    <a href="<?= url('doctors') ?>" class="text-sm text-blue-600 hover:underline mb-6 inline-block">&larr; Back to Doctors</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Profile -->
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-start gap-5">
                    <?php if (!empty($doctor['avatar'])): ?>
                    <img src="<?= asset($doctor['avatar']) ?>" class="w-20 h-20 rounded-2xl object-cover flex-shrink-0" alt="">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-3xl font-bold flex-shrink-0">
                        <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900"><?= e($doctor['name']) ?></h1>
                        <p class="text-blue-600 font-medium"><?= e($doctor['speciality'] ?? 'General Practitioner') ?></p>
                        <?php if ($doctor['qualification']): ?>
                        <p class="text-sm text-gray-500 mt-0.5"><?= e($doctor['qualification']) ?></p>
                        <?php endif; ?>
                        <?php if ($doctor['org_name']): ?>
                        <p class="text-sm text-gray-500 mt-0.5">
                            <i class="fa-solid fa-hospital text-gray-400 mr-1"></i><?= e($doctor['org_name']) ?><?= $doctor['city'] ? ', ' . e($doctor['city']) : '' ?>
                        </p>
                        <?php endif; ?>

                        <!-- Rating summary -->
                        <?php if ($rating['total'] > 0): ?>
                        <div class="flex items-center gap-2 mt-2">
                            <?= stars((float)($rating['avg'] ?? 0), (int)($rating['total'] ?? 0)) ?>
                            <span class="text-sm font-semibold text-gray-700"><?= number_format($rating['avg'], 1) ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <?php if ($doctor['is_available_online']): ?>
                            <span class="text-xs bg-green-100 text-green-700 border border-green-200 px-3 py-1 rounded-full">
                                <i class="fa-solid fa-video mr-1"></i>Online
                            </span>
                            <?php endif; ?>
                            <?php if ($doctor['is_available_onsite']): ?>
                            <span class="text-xs bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded-full">
                                <i class="fa-solid fa-hospital mr-1"></i>Onsite
                            </span>
                            <?php endif; ?>
                            <?php if ($doctor['experience_years']): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                                <?= $doctor['experience_years'] ?> years exp.
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($doctor['bio']): ?>
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-2">About</h3>
                    <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(e($doctor['bio'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Available Hours -->
            <?php if (!empty($schedules)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Available Hours</h3>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($schedules as $s): ?>
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                        <span class="text-sm text-gray-700 capitalize font-medium"><?= $s['day_of_week'] ?></span>
                        <span class="text-sm text-gray-500"><?= date('H:i', strtotime($s['start_time'])) ?> – <?= date('H:i', strtotime($s['end_time'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rate this Doctor -->
            <?php if (!empty($ratableAppointments)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6">
                <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-star text-yellow-500"></i> Rate Your Experience
                </h3>
                <p class="text-sm text-gray-500 mb-4">You have <?= count($ratableAppointments) ?> completed appointment<?= count($ratableAppointments) !== 1 ? 's' : '' ?> with this doctor. Share your feedback!</p>

                <form method="POST" action="<?= url('user/appointments/rate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="appointment_id" value="<?= $ratableAppointments[0]['id'] ?>">

                    <!-- Star selector -->
                    <div class="flex gap-2 mb-4" id="starPicker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="<?= $i ?>" class="sr-only" required>
                            <i class="fa-regular fa-star text-gray-300 text-3xl hover:text-yellow-400 transition-colors" data-star="<?= $i ?>"></i>
                        </label>
                        <?php endfor; ?>
                    </div>

                    <textarea name="comment" rows="3" placeholder="Share your experience with this doctor (optional)…"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 mb-3"></textarea>

                    <button type="submit"
                            class="px-6 py-2.5 bg-yellow-500 text-white font-semibold rounded-xl hover:bg-yellow-600 transition-colors text-sm">
                        Submit Review
                    </button>
                </form>
            </div>
            <script>
            document.querySelectorAll('#starPicker label').forEach((label, idx) => {
                label.querySelector('i').addEventListener('click', function() {
                    label.querySelector('input').checked = true;
                    document.querySelectorAll('#starPicker i').forEach((star, si) => {
                        star.className = si <= idx
                            ? 'fa-solid fa-star text-yellow-400 text-3xl transition-colors'
                            : 'fa-regular fa-star text-gray-300 text-3xl transition-colors';
                    });
                });
            });
            </script>
            <?php endif; ?>

            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-blue-400"></i> Patient Reviews
                    <span class="text-sm font-normal text-gray-400">(<?= $rating['total'] ?>)</span>
                </h3>
                <div class="space-y-4">
                    <?php foreach ($reviews as $rev): ?>
                    <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700 flex-shrink-0">
                                    <?= strtoupper(substr($rev['patient_name'], 0, 1)) ?>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?= e($rev['patient_name']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <?= stars((float)$rev['rating']) ?>
                                <span class="text-xs text-gray-400 ml-1"><?= ago($rev['created_at']) ?></span>
                            </div>
                        </div>
                        <?php if ($rev['comment']): ?>
                        <p class="text-sm text-gray-600 pl-9 leading-relaxed"><?= e($rev['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: Book + Services -->
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 text-center">
                <?php if ($doctor['consultation_fee'] > 0): ?>
                <p class="text-3xl font-bold text-blue-600">RM <?= number_format($doctor['consultation_fee'], 2) ?></p>
                <p class="text-xs text-gray-500 mb-4">Consultation fee</p>
                <?php endif; ?>
                <a href="<?= url('booking?doctor_id=' . $doctor['id']) ?>"
                   class="block w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors text-sm">
                    <i class="fa-solid fa-calendar-plus mr-2"></i>Book Appointment
                </a>
                <?php if (!Auth::check()): ?>
                <p class="text-xs text-gray-400 mt-2">You'll need to <a href="<?= url('login') ?>" class="text-blue-600 hover:underline">log in</a> to book</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($services)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Services</h3>
                <div class="space-y-2">
                    <?php foreach ($services as $svc): ?>
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm text-gray-800"><?= e($svc['name']) ?></p>
                            <p class="text-xs text-gray-400"><?= $svc['duration_minutes'] ?> min</p>
                        </div>
                        <span class="text-sm font-medium text-gray-700">RM <?= number_format($svc['price'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
