<div class="max-w-4xl mx-auto px-4 py-10">
    <a href="<?= url('doctors') ?>" class="text-sm text-blue-600 hover:underline mb-6 inline-block">&larr; Back to Doctors</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Profile -->
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-start gap-5">
                    <div class="w-20 h-20 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold flex-shrink-0">
                        <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?= e($doctor['name']) ?></h1>
                        <p class="text-blue-600 font-medium"><?= e($doctor['speciality'] ?? 'General Practitioner') ?></p>
                        <?php if ($doctor['qualification']): ?>
                        <p class="text-sm text-gray-500 mt-0.5"><?= e($doctor['qualification']) ?></p>
                        <?php endif; ?>
                        <?php if ($doctor['org_name']): ?>
                        <p class="text-sm text-gray-500 mt-0.5"><i class="fa-solid fa-hospital text-gray-400 mr-1"></i><?= e($doctor['org_name']) ?><?= $doctor['city'] ? ', ' . e($doctor['city']) : '' ?></p>
                        <?php endif; ?>

                        <div class="flex gap-2 mt-3">
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

            <!-- Schedule -->
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
        </div>

        <!-- Sidebar: Booking + Services -->
        <div class="space-y-5">
            <!-- Book Now Card -->
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

            <!-- Services -->
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
