<!-- Hero -->
<section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-24">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">
            Healthcare at Your<br>
            <span class="text-blue-200">Fingertips</span>
        </h1>
        <p class="text-blue-100 text-lg mb-8 max-w-2xl mx-auto">
            Connect with trusted doctors and clinics near you. Book appointments online or onsite, anytime.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= url('doctors') ?>"
               class="px-8 py-3.5 bg-white text-blue-700 font-semibold rounded-xl hover:bg-blue-50 transition-colors text-sm">
                <i class="fa-solid fa-magnifying-glass mr-2"></i> Find a Doctor
            </a>
            <?php if (!Auth::check()): ?>
            <a href="<?= url('register') ?>"
               class="px-8 py-3.5 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-400 transition-colors text-sm border border-blue-400">
                Get Started Free
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="bg-white border-b border-gray-100 py-10">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-3 gap-8 text-center">
            <div>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($stats['doctors']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1">Doctors</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($stats['organisations']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1">Clinics</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($stats['appointments']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1">Appointments</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Doctors -->
<?php if (!empty($featuredDoctors)): ?>
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Featured Doctors</h2>
                <p class="text-sm text-gray-500 mt-1">Trusted healthcare professionals</p>
            </div>
            <a href="<?= url('doctors') ?>" class="text-sm text-blue-600 font-medium hover:underline">View all &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($featuredDoctors as $doc): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold flex-shrink-0">
                        <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 truncate"><?= e($doc['name']) ?></h3>
                        <p class="text-sm text-blue-600"><?= e($doc['speciality'] ?? 'General Practitioner') ?></p>
                        <div class="flex gap-2 mt-2">
                            <?php if ($doc['is_available_online']): ?>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Online</span>
                            <?php endif; ?>
                            <?php if ($doc['is_available_onsite']): ?>
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Onsite</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($doc['consultation_fee'] > 0): ?>
                <p class="text-xs text-gray-500 mt-4">Consultation fee: <span class="font-semibold text-gray-700">RM <?= number_format($doc['consultation_fee'], 2) ?></span></p>
                <?php endif; ?>
                <a href="<?= url('doctors/show?id=' . $doc['id']) ?>"
                   class="mt-4 block text-center py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    View Profile
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- How it works -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">How It Works</h2>
        <p class="text-gray-500 mb-12">Book a doctor appointment in 3 simple steps</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $steps = [
                ['icon' => 'fa-magnifying-glass', 'color' => 'blue', 'title' => 'Find a Doctor', 'desc' => 'Search by name, speciality, or location. Filter by online or onsite availability.'],
                ['icon' => 'fa-calendar-check',   'color' => 'green','title' => 'Book Appointment','desc' => 'Pick a date and time that works for you. Choose online or in-clinic visit.'],
                ['icon' => 'fa-stethoscope',      'color' => 'purple','title' => 'Get Consultation','desc' => 'See the doctor online or visit the clinic. Receive care from trusted professionals.'],
            ];
            foreach ($steps as $i => $step): ?>
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 rounded-2xl bg-<?= $step['color'] ?>-100 flex items-center justify-center mb-4">
                    <i class="fa-solid <?= $step['icon'] ?> text-<?= $step['color'] ?>-600 text-2xl"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-<?= $step['color'] ?>-600 text-white text-sm font-bold flex items-center justify-center -mt-4 mb-4"><?= $i + 1 ?></div>
                <h3 class="font-semibold text-gray-900 mb-2"><?= $step['title'] ?></h3>
                <p class="text-sm text-gray-500"><?= $step['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
