<!-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ -->
<section class="relative bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 text-white overflow-hidden">
    <!-- Decorative blobs -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-16 -left-16 w-80 h-80 bg-blue-900/30 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 py-28 text-center">
        <span class="inline-block bg-white/15 backdrop-blur text-blue-100 text-xs font-semibold px-4 py-1.5 rounded-full mb-6 tracking-wide uppercase">
            Malaysia's Telehealth Platform
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6 leading-tight tracking-tight">
            Comprehensive Telehealth<br class="hidden md:block">
            <span class="text-cyan-200">Solutions for Every Malaysian</span>
        </h1>
        <p class="text-blue-100 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            At EbizMedic, we bring healthcare to your fingertips. Our platform simplifies the medical journey—connecting you with licensed doctors, digital prescriptions, and doorstep medicine delivery—anytime, anywhere.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= url('doctors') ?>"
               class="px-8 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors text-sm shadow-lg">
                <i class="fa-solid fa-video mr-2"></i> Consult a Doctor
            </a>
            <?php if (!Auth::check()): ?>
            <a href="<?= url('register') ?>"
               class="px-8 py-4 bg-blue-500/60 backdrop-blur text-white font-bold rounded-2xl hover:bg-blue-500/80 transition-colors text-sm border border-white/30">
                <i class="fa-solid fa-user-plus mr-2"></i> Get Started Free
            </a>
            <?php else: ?>
            <a href="<?= url(Auth::dashboardPath()) ?>"
               class="px-8 py-4 bg-blue-500/60 backdrop-blur text-white font-bold rounded-2xl hover:bg-blue-500/80 transition-colors text-sm border border-white/30">
                <i class="fa-solid fa-gauge mr-2"></i> My Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     LIVE STATS
══════════════════════════════════════════════════════════ -->
<section class="bg-white border-b border-gray-100 py-10">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-3 gap-8 text-center">
            <div>
                <p class="text-4xl font-extrabold text-blue-600"><?= number_format($stats['doctors']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1.5">Licensed Doctors</p>
            </div>
            <div>
                <p class="text-4xl font-extrabold text-blue-600"><?= number_format($stats['organisations']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1.5">Partner Clinics</p>
            </div>
            <div>
                <p class="text-4xl font-extrabold text-blue-600"><?= number_format($stats['appointments']) ?>+</p>
                <p class="text-sm text-gray-500 mt-1.5">Consultations</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     SERVICES
══════════════════════════════════════════════════════════ -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Everything You Need, In One Place</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">From consultation to doorstep delivery — your complete healthcare journey, simplified.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- Online Consultation -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-8">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-video text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Online Doctor Consultation</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Skip the clinic queues and consult a professional from the comfort of home. Our secure platform connects you with licensed Malaysian doctors for private video consultations. Whether it's a minor ailment, a follow-up, or expert medical advice, we're here for you.
                </p>
                <ul class="space-y-2.5">
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-blue-600 text-xs"></i>
                        </div>
                        <span><strong>Availability:</strong> Accessible 7 days a week</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-blue-600 text-xs"></i>
                        </div>
                        <span><strong>Privacy First:</strong> Encrypted, secure, and professional video calls</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-blue-600 text-xs"></i>
                        </div>
                        <span><strong>Digital Records:</strong> Receive medical reports and certificates instantly</span>
                    </li>
                </ul>
            </div>

            <!-- E-Prescription -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-8">
                <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-file-prescription text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">E-Prescription Services</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    No more misplaced paperwork. Following your consultation, our doctors issue a verified electronic prescription directly through the platform — ready the moment your call ends.
                </p>
                <ul class="space-y-2.5">
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-green-600 text-xs"></i>
                        </div>
                        <span><strong>Instant Access:</strong> Your prescription is ready the moment your call ends</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-green-600 text-xs"></i>
                        </div>
                        <span><strong>Legally Recognised:</strong> Secure digital signatures accepted by partner clinics</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-green-600 text-xs"></i>
                        </div>
                        <span><strong>Accuracy:</strong> Eliminates the risk of errors from handwritten notes</span>
                    </li>
                </ul>
            </div>

            <!-- Doorstep Delivery -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-8">
                <div class="w-14 h-14 rounded-2xl bg-purple-100 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-truck-medical text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Doorstep Medicine Delivery</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Get your medication without leaving the house. We partner with licensed pharmacies to ensure your treatment is genuine, safe, and delivered promptly to your door.
                </p>
                <ul class="space-y-2.5">
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-purple-600 text-xs"></i>
                        </div>
                        <span><strong>Nationwide Reach:</strong> Fast delivery to homes and offices across Malaysia</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-purple-600 text-xs"></i>
                        </div>
                        <span><strong>Quality Assured:</strong> Safe, temperature-controlled packaging for sensitive meds</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-purple-600 text-xs"></i>
                        </div>
                        <span><strong>Live Tracking:</strong> Monitor your delivery status in real-time</span>
                    </li>
                </ul>
            </div>

            <!-- Follow-Up Care -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-8">
                <div class="w-14 h-14 rounded-2xl bg-orange-100 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-heart-pulse text-orange-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Continuous Follow-Up Care</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">
                    Your health journey doesn't end after one call. EbizMedic prioritises long-term wellness through integrated follow-up appointments and chat support.
                </p>
                <ul class="space-y-2.5">
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-orange-600 text-xs"></i>
                        </div>
                        <span><strong>Seamless Monitoring:</strong> Stay on track with personalised treatment plans</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-orange-600 text-xs"></i>
                        </div>
                        <span><strong>Direct Access:</strong> Message your care team for post-consultation clarity</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-orange-600 text-xs"></i>
                        </div>
                        <span><strong>Proactive Health:</strong> Automated reminders for follow-ups and refills</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════════════════════════ -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">How It Works</h2>
        <p class="text-gray-500 text-lg mb-14">Get care in 4 simple steps</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $steps = [
                ['icon' => 'fa-user-plus',           'color' => 'blue',   'step' => '1', 'title' => 'Create Account',       'desc' => 'Sign up for free and complete your health profile in under a minute.'],
                ['icon' => 'fa-magnifying-glass',     'color' => 'cyan',   'step' => '2', 'title' => 'Find a Doctor',         'desc' => 'Browse specialists, filter by availability, location, or consultation type.'],
                ['icon' => 'fa-calendar-check',       'color' => 'green',  'step' => '3', 'title' => 'Book & Consult',        'desc' => 'Pick a slot, consult via video or visit in-clinic, get your e-prescription.'],
                ['icon' => 'fa-truck-medical',        'color' => 'purple', 'step' => '4', 'title' => 'Receive Medication',    'desc' => 'Your medicines are dispensed and delivered right to your doorstep.'],
            ];
            foreach ($steps as $step): ?>
            <div class="relative flex flex-col items-center px-4">
                <div class="w-16 h-16 rounded-2xl bg-<?= $step['color'] ?>-100 flex items-center justify-center mb-3 relative z-10">
                    <i class="fa-solid <?= $step['icon'] ?> text-<?= $step['color'] ?>-600 text-xl"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-<?= $step['color'] ?>-600 text-white text-sm font-bold flex items-center justify-center -mt-5 mb-4 relative z-20 border-2 border-white">
                    <?= $step['step'] ?>
                </div>
                <h3 class="font-bold text-gray-900 mb-2"><?= $step['title'] ?></h3>
                <p class="text-sm text-gray-500 leading-relaxed"><?= $step['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     FEATURED DOCTORS
══════════════════════════════════════════════════════════ -->
<?php if (!empty($featuredDoctors)): ?>
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">Meet Our Doctors</h2>
                <p class="text-gray-500 mt-1">Verified, licensed Malaysian healthcare professionals</p>
            </div>
            <a href="<?= url('doctors') ?>" class="text-sm text-blue-600 font-semibold hover:underline flex items-center gap-1">
                View all <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($featuredDoctors as $doc): ?>
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                        <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 truncate"><?= e($doc['name']) ?></h3>
                        <p class="text-sm text-blue-600 font-medium"><?= e($doc['speciality'] ?? 'General Practitioner') ?></p>
                        <?php if ($doc['org_name'] ?? null): ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?= e($doc['org_name']) ?></p>
                        <?php endif; ?>
                        <div class="flex gap-2 mt-2">
                            <?php if ($doc['is_available_online']): ?>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
                                <i class="fa-solid fa-video mr-0.5"></i> Online
                            </span>
                            <?php endif; ?>
                            <?php if ($doc['is_available_onsite']): ?>
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                                <i class="fa-solid fa-hospital mr-0.5"></i> Onsite
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($doc['consultation_fee'] > 0): ?>
                <p class="text-xs text-gray-400 mt-4 flex items-center gap-1">
                    <i class="fa-solid fa-tag text-gray-300"></i>
                    Consultation fee: <span class="font-semibold text-gray-700 ml-1">RM <?= number_format($doc['consultation_fee'], 2) ?></span>
                </p>
                <?php endif; ?>
                <a href="<?= url('doctors/show?id=' . $doc['id']) ?>"
                   class="mt-4 block text-center py-2.5 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700 transition-colors font-semibold">
                    Book Consultation
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     WHY CHOOSE EBIZMEDIC
══════════════════════════════════════════════════════════ -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Why Choose EbizMedic?</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">We built EbizMedic on four principles that put your health and trust first.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $reasons = [
                ['icon' => 'fa-shield-halved', 'color' => 'blue',   'title' => 'Trust',    'desc' => 'Verified, licensed Malaysian medical professionals you can rely on for every consultation.'],
                ['icon' => 'fa-lock',          'color' => 'green',  'title' => 'Security', 'desc' => 'High-level data encryption keeps your medical history private and safe at all times.'],
                ['icon' => 'fa-hand-pointer',  'color' => 'purple', 'title' => 'Simplicity','desc' => 'A user-friendly interface thoughtfully designed for all age groups and technical abilities.'],
                ['icon' => 'fa-tags',          'color' => 'orange', 'title' => 'Value',    'desc' => 'Transparent, affordable pricing with no hidden fees — quality care that fits your budget.'],
            ];
            foreach ($reasons as $r): ?>
            <div class="group bg-gray-50 hover:bg-<?= $r['color'] ?>-600 rounded-3xl p-7 transition-colors duration-300">
                <div class="w-12 h-12 rounded-2xl bg-<?= $r['color'] ?>-100 group-hover:bg-white/20 flex items-center justify-center mb-5 transition-colors">
                    <i class="fa-solid <?= $r['icon'] ?> text-<?= $r['color'] ?>-600 group-hover:text-white text-xl transition-colors"></i>
                </div>
                <h3 class="font-bold text-gray-900 group-hover:text-white text-lg mb-2 transition-colors"><?= $r['title'] ?></h3>
                <p class="text-sm text-gray-500 group-hover:text-<?= $r['color'] ?>-100 leading-relaxed transition-colors"><?= $r['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     FINAL CTA
══════════════════════════════════════════════════════════ -->
<section class="bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 py-20 text-white text-center">
    <div class="max-w-3xl mx-auto px-4">
        <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-heart-pulse text-white text-3xl"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-extrabold mb-4 leading-tight">Your Health, Simplified</h2>
        <p class="text-blue-100 text-lg mb-10 leading-relaxed">
            Experience a modern, reliable way to manage your wellness. At EbizMedic, we are redefining healthcare to be <strong class="text-white">safe</strong>, <strong class="text-white">simple</strong>, and <strong class="text-white">smart</strong>.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if (!Auth::check()): ?>
            <a href="<?= url('register') ?>"
               class="px-10 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow-lg">
                Start Your Journey Today
            </a>
            <a href="<?= url('doctors') ?>"
               class="px-10 py-4 bg-blue-500/50 text-white font-bold rounded-2xl hover:bg-blue-500/70 transition-colors border border-white/30">
                Browse Doctors
            </a>
            <?php else: ?>
            <a href="<?= url('doctors') ?>"
               class="px-10 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow-lg">
                <i class="fa-solid fa-magnifying-glass mr-2"></i> Find a Doctor
            </a>
            <a href="<?= url(Auth::dashboardPath()) ?>"
               class="px-10 py-4 bg-blue-500/50 text-white font-bold rounded-2xl hover:bg-blue-500/70 transition-colors border border-white/30">
                <i class="fa-solid fa-gauge mr-2"></i> Go to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>
