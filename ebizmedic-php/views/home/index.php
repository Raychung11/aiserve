<style>
@keyframes float      { 0%,100%{transform:translateY(0)}  50%{transform:translateY(-10px)} }
@keyframes floatAlt   { 0%,100%{transform:translateY(0)}  50%{transform:translateY(-14px)} }
@keyframes blob       { 0%,100%{border-radius:60% 40% 30% 70%/60% 30% 70% 40%} 50%{border-radius:30% 60% 70% 40%/50% 60% 30% 60%} }
@keyframes heroFadeUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
@keyframes spinSlow   { to{transform:rotate(360deg)} }
@keyframes ping2      { 0%{transform:scale(1);opacity:.8} 100%{transform:scale(2.2);opacity:0} }
.pill-1{animation:float    3.2s ease-in-out infinite}
.pill-2{animation:floatAlt 4.1s ease-in-out infinite; animation-delay:.8s}
.pill-3{animation:float    3.8s ease-in-out infinite; animation-delay:1.5s}
.pill-4{animation:floatAlt 4.5s ease-in-out infinite; animation-delay:.3s}
.hero-up{opacity:0;animation:heroFadeUp .7s ease forwards}
.reveal{opacity:0;transform:translateY(32px);transition:opacity .65s ease,transform .65s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.stagger-1{transition-delay:.1s}.stagger-2{transition-delay:.2s}.stagger-3{transition-delay:.3s}.stagger-4{transition-delay:.4s}
</style>

<!-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ -->
<section class="relative bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 text-white overflow-hidden" style="min-height:92vh;display:flex;align-items:center">

    <!-- Animated background blobs -->
    <div class="absolute -top-32 -right-32 w-[500px] h-[500px] opacity-10 pointer-events-none" style="background:radial-gradient(circle,white,transparent);animation:blob 8s ease-in-out infinite"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-blue-900/20 rounded-full blur-3xl pointer-events-none" style="animation:blob 10s ease-in-out infinite reverse"></div>
    <div class="absolute top-1/3 right-1/3 w-64 h-64 bg-cyan-300/10 rounded-full blur-2xl pointer-events-none" style="animation:blob 12s ease-in-out infinite 2s"></div>

    <!-- Floating pills (desktop only) -->
    <div class="absolute top-20 right-20 hidden xl:flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/20 px-4 py-2.5 rounded-2xl text-sm font-semibold shadow-xl pill-1 cursor-default">
        <div class="w-7 h-7 bg-blue-400/30 rounded-lg flex items-center justify-center"><i class="fa-solid fa-video text-cyan-200 text-xs"></i></div>
        Online Consultation
    </div>
    <div class="absolute top-44 right-10 hidden xl:flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 px-4 py-2.5 rounded-2xl text-sm font-semibold shadow-xl pill-2">
        <div class="w-7 h-7 bg-green-400/20 rounded-lg flex items-center justify-center"><i class="fa-solid fa-file-prescription text-green-300 text-xs"></i></div>
        E-Prescription
    </div>
    <div class="absolute bottom-36 right-24 hidden xl:flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/20 px-4 py-2.5 rounded-2xl text-sm font-semibold shadow-xl pill-3">
        <div class="w-7 h-7 bg-purple-400/20 rounded-lg flex items-center justify-center"><i class="fa-solid fa-truck-medical text-purple-200 text-xs"></i></div>
        Doorstep Delivery
    </div>
    <div class="absolute bottom-24 left-20 hidden xl:flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 px-4 py-2.5 rounded-2xl text-sm font-semibold shadow-xl pill-4">
        <div class="w-7 h-7 bg-yellow-400/20 rounded-lg flex items-center justify-center"><i class="fa-solid fa-star text-yellow-300 text-xs"></i></div>
        4.9 / 5 Rating
    </div>

    <div class="relative max-w-7xl mx-auto px-4 py-28 text-center w-full">

        <!-- Live badge -->
        <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur text-blue-100 text-xs font-semibold px-4 py-1.5 rounded-full mb-6 tracking-wide uppercase hero-up" style="animation-delay:.05s">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
            </span>
            Malaysia's Telehealth Platform — Now Live
        </div>

        <h1 class="text-4xl md:text-6xl font-extrabold mb-6 leading-tight tracking-tight hero-up" style="animation-delay:.15s">
            Comprehensive Telehealth<br class="hidden md:block">
            <span class="text-cyan-200" id="heroTyped">&nbsp;</span>
        </h1>

        <p class="text-blue-100 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed hero-up" style="animation-delay:.3s">
            At EbizMedic, we bring healthcare to your fingertips. Our platform simplifies the medical journey — connecting you with licensed doctors, digital prescriptions, and doorstep medicine delivery — anytime, anywhere.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center hero-up" style="animation-delay:.45s">
            <a href="<?= url('doctors') ?>"
               class="group px-8 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-all text-sm shadow-lg hover:shadow-xl hover:-translate-y-1 transform duration-200">
                <i class="fa-solid fa-video mr-2 group-hover:text-blue-500 transition-colors"></i> Consult a Doctor
            </a>
            <?php if (!Auth::check()): ?>
            <a href="<?= url('register') ?>"
               class="group px-8 py-4 bg-white/20 backdrop-blur text-white font-bold rounded-2xl hover:bg-white/30 transition-all text-sm border border-white/30 hover:-translate-y-1 transform duration-200">
                <i class="fa-solid fa-user-plus mr-2"></i> Get Started Free
            </a>
            <?php else: ?>
            <a href="<?= url(Auth::dashboardPath()) ?>"
               class="group px-8 py-4 bg-white/20 backdrop-blur text-white font-bold rounded-2xl hover:bg-white/30 transition-all text-sm border border-white/30 hover:-translate-y-1 transform duration-200">
                <i class="fa-solid fa-gauge mr-2"></i> My Dashboard
            </a>
            <?php endif; ?>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 opacity-50 animate-bounce">
            <span class="text-[10px] text-blue-200 uppercase tracking-widest">Scroll</span>
            <i class="fa-solid fa-chevron-down text-blue-200 text-xs"></i>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     LIVE STATS  (count-up on scroll)
══════════════════════════════════════════════════════════ -->
<section class="bg-white border-b border-gray-100 py-14" id="statsSection">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-3 gap-8 text-center">
            <div class="reveal stagger-1">
                <div class="text-4xl md:text-5xl font-extrabold text-blue-600 counter" data-target="<?= (int)$stats['doctors'] ?>" data-suffix="+">0+</div>
                <p class="text-sm text-gray-500 mt-2 font-medium"><i class="fa-solid fa-user-doctor text-blue-300 mr-1.5"></i>Licensed Doctors</p>
            </div>
            <div class="reveal stagger-2">
                <div class="text-4xl md:text-5xl font-extrabold text-blue-600 counter" data-target="<?= (int)$stats['organisations'] ?>" data-suffix="+">0+</div>
                <p class="text-sm text-gray-500 mt-2 font-medium"><i class="fa-solid fa-hospital text-blue-300 mr-1.5"></i>Partner Clinics</p>
            </div>
            <div class="reveal stagger-3">
                <div class="text-4xl md:text-5xl font-extrabold text-blue-600 counter" data-target="<?= (int)$stats['appointments'] ?>" data-suffix="+">0+</div>
                <p class="text-sm text-gray-500 mt-2 font-medium"><i class="fa-solid fa-calendar-check text-blue-300 mr-1.5"></i>Consultations</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     SERVICES  (tab switcher)
══════════════════════════════════════════════════════════ -->
<section class="py-24 bg-gray-50" id="servicesSection">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Everything You Need, In One Place</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">From consultation to doorstep delivery — your complete healthcare journey, simplified.</p>
        </div>

        <!-- Tab buttons -->
        <div class="flex flex-wrap justify-center gap-2 mb-10 reveal stagger-2">
            <?php
            $services = [
                ['id'=>'online',       'icon'=>'fa-video',             'color'=>'blue',   'label'=>'Online Consult'],
                ['id'=>'prescription', 'icon'=>'fa-file-prescription', 'color'=>'green',  'label'=>'E-Prescription'],
                ['id'=>'delivery',     'icon'=>'fa-truck-medical',     'color'=>'purple', 'label'=>'Med Delivery'],
                ['id'=>'followup',     'icon'=>'fa-heart-pulse',       'color'=>'orange', 'label'=>'Follow-Up Care'],
            ];
            foreach ($services as $i => $svc): ?>
            <button data-tab="<?= $svc['id'] ?>"
                    class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 border <?= $i === 0 ? 'bg-blue-600 text-white border-blue-600 shadow-lg' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' ?>">
                <i class="fa-solid <?= $svc['icon'] ?>"></i> <?= $svc['label'] ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab panels -->
        <div class="relative reveal stagger-3">

            <!-- Online Consultation -->
            <div id="tab-online" class="tab-panel grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-video text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Online Doctor Consultation</h3>
                    <p class="text-gray-500 leading-relaxed mb-6">Skip the clinic queues and consult a professional from the comfort of home. Our secure platform connects you with licensed Malaysian doctors for private video consultations — whether it's a minor ailment, a follow-up, or expert advice.</p>
                    <ul class="space-y-3">
                        <?php foreach(['<strong>Available 7 days</strong> a week, mornings to evenings','<strong>Privacy First:</strong> Encrypted, secure, professional video calls','<strong>Digital Records:</strong> Receive medical reports and certificates instantly'] as $pt): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fa-solid fa-check text-blue-600 text-xs"></i></span>
                            <span><?= $pt ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('doctors') ?>" class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors">Book a Consultation <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="relative hidden lg:block">
                    <div class="bg-gradient-to-br from-blue-100 to-blue-50 rounded-3xl p-8 flex items-center justify-center h-72">
                        <i class="fa-solid fa-video text-blue-400" style="font-size:7rem;opacity:.3"></i>
                        <div class="absolute top-6 left-6 bg-white rounded-xl shadow px-4 py-3 text-xs font-semibold text-gray-700 flex items-center gap-2"><span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>Doctor Online</div>
                        <div class="absolute bottom-6 right-6 bg-blue-600 text-white rounded-xl px-4 py-3 text-xs font-semibold">HD Video Call</div>
                    </div>
                </div>
            </div>

            <!-- E-Prescription -->
            <div id="tab-prescription" class="tab-panel hidden grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-file-prescription text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">E-Prescription Services</h3>
                    <p class="text-gray-500 leading-relaxed mb-6">No more misplaced paperwork. Following your consultation, our doctors issue a verified electronic prescription directly through the platform — ready the moment your call ends.</p>
                    <ul class="space-y-3">
                        <?php foreach(['<strong>Instant Access:</strong> Prescription ready the moment your call ends','<strong>Legally Recognised:</strong> Secure digital signatures accepted at partner clinics','<strong>Accuracy:</strong> Eliminates errors from handwritten notes'] as $pt): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fa-solid fa-check text-green-600 text-xs"></i></span>
                            <span><?= $pt ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('register') ?>" class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-green-600 text-white rounded-xl font-semibold text-sm hover:bg-green-700 transition-colors">Get Started Free <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="relative hidden lg:block">
                    <div class="bg-gradient-to-br from-green-100 to-green-50 rounded-3xl p-8 flex items-center justify-center h-72">
                        <i class="fa-solid fa-file-prescription text-green-400" style="font-size:7rem;opacity:.3"></i>
                        <div class="absolute top-6 right-6 bg-white rounded-xl shadow px-4 py-3 text-xs font-semibold text-gray-700"><i class="fa-solid fa-shield-halved text-green-500 mr-1"></i>Verified & Secure</div>
                        <div class="absolute bottom-6 left-6 bg-green-600 text-white rounded-xl px-4 py-3 text-xs font-semibold">Digital Signature ✓</div>
                    </div>
                </div>
            </div>

            <!-- Doorstep Delivery -->
            <div id="tab-delivery" class="tab-panel hidden grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-purple-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-truck-medical text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Doorstep Medicine Delivery</h3>
                    <p class="text-gray-500 leading-relaxed mb-6">Get your medication without leaving the house. We partner with licensed pharmacies to ensure your treatment is genuine, safe, and delivered promptly to your door.</p>
                    <ul class="space-y-3">
                        <?php foreach(['<strong>Nationwide Reach:</strong> Fast delivery to homes and offices across Malaysia','<strong>Quality Assured:</strong> Temperature-controlled packaging for sensitive medications','<strong>Live Tracking:</strong> Monitor your delivery status in real-time'] as $pt): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fa-solid fa-check text-purple-600 text-xs"></i></span>
                            <span><?= $pt ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('register') ?>" class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-colors">Sign Up Today <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="relative hidden lg:block">
                    <div class="bg-gradient-to-br from-purple-100 to-purple-50 rounded-3xl p-8 flex items-center justify-center h-72">
                        <i class="fa-solid fa-truck-medical text-purple-400" style="font-size:7rem;opacity:.3"></i>
                        <div class="absolute top-6 left-6 bg-white rounded-xl shadow px-4 py-3 text-xs font-semibold text-gray-700"><i class="fa-solid fa-location-dot text-purple-500 mr-1"></i>Live Tracking</div>
                        <div class="absolute bottom-6 right-6 bg-purple-600 text-white rounded-xl px-4 py-3 text-xs font-semibold">Same-Day Delivery</div>
                    </div>
                </div>
            </div>

            <!-- Follow-Up Care -->
            <div id="tab-followup" class="tab-panel hidden grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-orange-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-heart-pulse text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Continuous Follow-Up Care</h3>
                    <p class="text-gray-500 leading-relaxed mb-6">Your health journey doesn't end after one call. EbizMedic prioritises long-term wellness through integrated follow-up appointments, health profiles, and automated reminders.</p>
                    <ul class="space-y-3">
                        <?php foreach(['<strong>Seamless Monitoring:</strong> Stay on track with personalised treatment plans','<strong>Direct Access:</strong> Message your care team for post-consultation clarity','<strong>Proactive Health:</strong> Automated reminders for follow-ups and refills'] as $pt): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fa-solid fa-check text-orange-600 text-xs"></i></span>
                            <span><?= $pt ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= url('register') ?>" class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-orange-500 text-white rounded-xl font-semibold text-sm hover:bg-orange-600 transition-colors">Start Your Journey <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="relative hidden lg:block">
                    <div class="bg-gradient-to-br from-orange-100 to-orange-50 rounded-3xl p-8 flex items-center justify-center h-72">
                        <i class="fa-solid fa-heart-pulse text-orange-400" style="font-size:7rem;opacity:.3"></i>
                        <div class="absolute top-6 right-6 bg-white rounded-xl shadow px-4 py-3 text-xs font-semibold text-gray-700"><i class="fa-solid fa-bell text-orange-500 mr-1"></i>Smart Reminders</div>
                        <div class="absolute bottom-6 left-6 bg-orange-500 text-white rounded-xl px-4 py-3 text-xs font-semibold">Personalised Plans</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     HOW IT WORKS  (animated steps)
══════════════════════════════════════════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="reveal mb-14">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">How It Works</h2>
            <p class="text-gray-500 text-lg">Get care in 4 simple steps</p>
        </div>

        <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Connector line (desktop) -->
            <div class="hidden lg:block absolute top-8 left-[12.5%] right-[12.5%] h-0.5 bg-gray-200" style="z-index:0">
                <div id="stepLine" class="h-full bg-blue-400 transition-all duration-1000 ease-in-out" style="width:0%"></div>
            </div>

            <?php
            $steps = [
                ['icon'=>'fa-user-plus',        'color'=>'blue',   'n'=>'1','title'=>'Create Account',    'desc'=>'Sign up for free and complete your health profile in under a minute.'],
                ['icon'=>'fa-magnifying-glass',  'color'=>'cyan',   'n'=>'2','title'=>'Find a Doctor',     'desc'=>'Browse specialists, filter by availability, location, or consultation type.'],
                ['icon'=>'fa-calendar-check',    'color'=>'green',  'n'=>'3','title'=>'Book & Consult',    'desc'=>'Pick a slot, consult via video or in-clinic, get your e-prescription.'],
                ['icon'=>'fa-truck-medical',     'color'=>'purple', 'n'=>'4','title'=>'Receive Medication','desc'=>'Your medicines are dispensed and delivered right to your doorstep.'],
            ];
            foreach ($steps as $i => $step): ?>
            <div class="relative flex flex-col items-center px-4 step-card reveal stagger-<?= $i+1 ?>" style="z-index:1">
                <div class="w-16 h-16 rounded-2xl bg-<?= $step['color'] ?>-100 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fa-solid <?= $step['icon'] ?> text-<?= $step['color'] ?>-600 text-xl"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-<?= $step['color'] ?>-600 text-white text-sm font-bold flex items-center justify-center -mt-5 mb-4 relative z-20 border-2 border-white shadow">
                    <?= $step['n'] ?>
                </div>
                <h3 class="font-bold text-gray-900 mb-2"><?= $step['title'] ?></h3>
                <p class="text-sm text-gray-500 leading-relaxed"><?= $step['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     FEATURED DOCTORS  (carousel)
══════════════════════════════════════════════════════════ -->
<?php if (!empty($featuredDoctors)): ?>
<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-10 reveal">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">Meet Our Doctors</h2>
                <p class="text-gray-500 mt-1">Verified, licensed Malaysian healthcare professionals</p>
            </div>
            <div class="flex items-center gap-3">
                <button id="docPrev" class="w-10 h-10 rounded-full border border-gray-300 bg-white flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left text-gray-600 text-sm"></i>
                </button>
                <button id="docNext" class="w-10 h-10 rounded-full border border-gray-300 bg-white flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-colors">
                    <i class="fa-solid fa-chevron-right text-gray-600 text-sm"></i>
                </button>
                <a href="<?= url('doctors') ?>" class="text-sm text-blue-600 font-semibold hover:underline ml-2">View all <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
        </div>

        <div class="overflow-hidden reveal stagger-2">
            <div id="docTrack" class="flex gap-6 transition-transform duration-500 ease-in-out">
                <?php foreach ($featuredDoctors as $doc): ?>
                <div class="flex-none w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1 p-6">
                    <div class="flex items-start gap-4">
                        <?php if (!empty($doc['avatar'])): ?>
                        <img src="<?= asset($doc['avatar']) ?>" class="w-14 h-14 rounded-full object-cover flex-shrink-0" alt="">
                        <?php else: ?>
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                            <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 truncate"><?= e($doc['name']) ?></h3>
                            <p class="text-sm text-blue-600 font-medium"><?= e($doc['speciality'] ?? 'General Practitioner') ?></p>
                            <?php if ($doc['org_name'] ?? null): ?>
                            <p class="text-xs text-gray-400 mt-0.5 truncate"><?= e($doc['org_name']) ?></p>
                            <?php endif; ?>
                            <div class="flex gap-2 mt-2">
                                <?php if ($doc['is_available_online']): ?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium"><i class="fa-solid fa-video mr-0.5"></i> Online</span>
                                <?php endif; ?>
                                <?php if ($doc['is_available_onsite']): ?>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium"><i class="fa-solid fa-hospital mr-0.5"></i> Onsite</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($doc['consultation_fee'] > 0): ?>
                    <p class="text-xs text-gray-400 mt-4 flex items-center gap-1">
                        <i class="fa-solid fa-tag text-gray-300"></i>
                        From <span class="font-semibold text-gray-700 ml-1">RM <?= number_format($doc['consultation_fee'], 2) ?></span>
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

        <!-- Dots -->
        <div id="docDots" class="flex justify-center gap-2 mt-6"></div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     WHY CHOOSE  (hover + scroll-reveal)
══════════════════════════════════════════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14 reveal">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Why Choose EbizMedic?</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">We built EbizMedic on four principles that put your health and trust first.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $reasons = [
                ['icon'=>'fa-shield-halved','color'=>'blue',  'title'=>'Trust',    'desc'=>'Verified, licensed Malaysian medical professionals you can rely on for every consultation.'],
                ['icon'=>'fa-lock',         'color'=>'green', 'title'=>'Security', 'desc'=>'High-level data encryption keeps your medical history private and safe at all times.'],
                ['icon'=>'fa-hand-pointer', 'color'=>'purple','title'=>'Simplicity','desc'=>'A user-friendly interface thoughtfully designed for all age groups and technical abilities.'],
                ['icon'=>'fa-tags',         'color'=>'orange','title'=>'Value',    'desc'=>'Transparent, affordable pricing with no hidden fees — quality care that fits your budget.'],
            ];
            foreach ($reasons as $i => $r): ?>
            <div class="group bg-gray-50 hover:bg-<?= $r['color'] ?>-600 rounded-3xl p-7 transition-all duration-300 cursor-default reveal stagger-<?= $i+1 ?> hover:-translate-y-1 hover:shadow-xl">
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
     FAQ  (accordion)
══════════════════════════════════════════════════════════ -->
<section class="py-24 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Frequently Asked Questions</h2>
            <p class="text-gray-500 text-lg">Everything you need to know about EbizMedic.</p>
        </div>

        <?php
        $faqs = [
            ['q'=>'Is EbizMedic available to all Malaysians?',
             'a'=>'Yes. EbizMedic is available to all Malaysians nationwide. As long as you have an internet connection, you can consult a licensed doctor, receive an e-prescription, and arrange medicine delivery wherever you are in Malaysia.'],
            ['q'=>'How do I book an online consultation?',
             'a'=>'Simply create a free account, browse our directory of doctors, filter by speciality or availability, and pick a time slot that suits you. Payments and confirmations are handled entirely through the platform.'],
            ['q'=>'Are the doctors on EbizMedic licensed and verified?',
             'a'=>'Absolutely. Every doctor on our platform is verified by our admin team before going live. We check their MMC (Malaysian Medical Council) registration and professional qualifications to ensure you receive care from fully qualified practitioners.'],
            ['q'=>'How does medicine delivery work?',
             'a'=>'After your consultation, the doctor issues an e-prescription directly on the platform. Our partner pharmacies receive it, prepare your medication, and dispatch it to your registered address. You can track the delivery in real-time from your patient dashboard.'],
            ['q'=>'Is my medical information kept private?',
             'a'=>'Yes. All data transmitted on EbizMedic is encrypted. Your medical records, prescriptions, and personal information are only accessible to you and the licensed healthcare professional treating you. We comply fully with Malaysian data protection standards.'],
            ['q'=>'Can I use EbizMedic for my children or elderly parents?',
             'a'=>'Yes. You can book appointments on behalf of family members. Simply note the patient\'s details during the booking process. Our paediatric and geriatric specialists are well-experienced in treating patients of all ages.'],
        ];
        foreach ($faqs as $i => $faq): ?>
        <div class="faq-item bg-white rounded-2xl border border-gray-100 shadow-sm mb-3 overflow-hidden reveal stagger-<?= min($i+1,4) ?>">
            <button class="faq-btn w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-gray-900 hover:text-blue-600 transition-colors group">
                <span><?= $faq['q'] ?></span>
                <span class="faq-icon w-7 h-7 rounded-full bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center flex-shrink-0 ml-4 transition-all">
                    <i class="fa-solid fa-plus text-gray-500 group-hover:text-blue-600 text-xs transition-all"></i>
                </span>
            </button>
            <div class="faq-body hidden px-6 pb-5 text-sm text-gray-500 leading-relaxed border-t border-gray-50">
                <p class="pt-4"><?= $faq['a'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     FINAL CTA
══════════════════════════════════════════════════════════ -->
<section class="relative bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 py-24 text-white text-center overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 60% 0%,rgba(255,255,255,.07),transparent 70%)"></div>

    <div class="relative max-w-3xl mx-auto px-4">
        <!-- Pulsing icon -->
        <div class="flex items-center justify-center mb-6">
            <div class="relative">
                <div class="absolute inset-0 rounded-2xl bg-white/20" style="animation:ping2 2s cubic-bezier(0,0,.2,1) infinite"></div>
                <div class="relative w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center">
                    <i class="fa-solid fa-heart-pulse text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <h2 class="text-3xl md:text-4xl font-extrabold mb-4 leading-tight reveal">Your Health, Simplified</h2>
        <p class="text-blue-100 text-lg mb-10 leading-relaxed reveal stagger-2">
            Experience a modern, reliable way to manage your wellness. At EbizMedic, we are redefining healthcare to be <strong class="text-white">safe</strong>, <strong class="text-white">simple</strong>, and <strong class="text-white">smart</strong>.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center reveal stagger-3">
            <?php if (!Auth::check()): ?>
            <a href="<?= url('register') ?>"
               class="px-10 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-all shadow-lg hover:-translate-y-1 transform duration-200">
                Start Your Journey Today
            </a>
            <a href="<?= url('doctors') ?>"
               class="px-10 py-4 bg-white/20 text-white font-bold rounded-2xl hover:bg-white/30 transition-all border border-white/30 hover:-translate-y-1 transform duration-200">
                Browse Doctors
            </a>
            <?php else: ?>
            <a href="<?= url('doctors') ?>"
               class="px-10 py-4 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-all shadow-lg hover:-translate-y-1 transform duration-200">
                <i class="fa-solid fa-magnifying-glass mr-2"></i> Find a Doctor
            </a>
            <a href="<?= url(Auth::dashboardPath()) ?>"
               class="px-10 py-4 bg-white/20 text-white font-bold rounded-2xl hover:bg-white/30 transition-all border border-white/30 hover:-translate-y-1 transform duration-200">
                <i class="fa-solid fa-gauge mr-2"></i> Go to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
// ── Typewriter ────────────────────────────────────────────
(function() {
    var el   = document.getElementById('heroTyped');
    var text = 'Solutions for Every Malaysian';
    var i    = 0;
    function type() {
        if (i <= text.length) {
            el.textContent = text.slice(0, i);
            i++;
            setTimeout(type, i === 1 ? 600 : 55);
        }
    }
    setTimeout(type, 700);
})();

// ── Scroll reveal ─────────────────────────────────────────
var revealObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
        if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); }
    });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(function(el) { revealObs.observe(el); });

// ── Count-up ──────────────────────────────────────────────
function animateCount(el) {
    var target = parseInt(el.dataset.target, 10);
    var suffix = el.dataset.suffix || '';
    var start  = 0;
    var dur    = 1800;
    var step   = dur / 60;
    var inc    = target / (dur / step);
    var timer  = setInterval(function() {
        start += inc;
        if (start >= target) { start = target; clearInterval(timer); }
        el.textContent = Math.floor(start).toLocaleString() + suffix;
    }, step);
}
var countObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
        if (e.isIntersecting) { animateCount(e.target); countObs.unobserve(e.target); }
    });
}, { threshold: 0.5 });
document.querySelectorAll('.counter').forEach(function(el) { countObs.observe(el); });

// ── Step line ─────────────────────────────────────────────
var lineObs = new IntersectionObserver(function(entries) {
    if (entries[0].isIntersecting) {
        document.getElementById('stepLine').style.width = '100%';
        lineObs.disconnect();
    }
}, { threshold: 0.4 });
var stepLine = document.getElementById('stepLine');
if (stepLine) lineObs.observe(stepLine.parentElement);

// ── Service tabs ──────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.tab;
        // Panels
        document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.add('hidden'); });
        var panel = document.getElementById('tab-' + id);
        if (panel) { panel.classList.remove('hidden'); panel.style.opacity=0; setTimeout(function(){panel.style.transition='opacity .3s';panel.style.opacity=1;},10); }
        // Buttons
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.className = b.className.replace(/bg-\w+-600 text-white border-\w+-600 shadow-lg/, '');
            b.classList.remove('bg-blue-600','text-white','border-blue-600','shadow-lg');
            b.classList.add('bg-white','text-gray-600','border-gray-200');
        });
        btn.classList.remove('bg-white','text-gray-600','border-gray-200');
        btn.classList.add('bg-blue-600','text-white','border-blue-600','shadow-lg');
    });
});

// ── Doctor carousel ───────────────────────────────────────
(function() {
    var track   = document.getElementById('docTrack');
    var prevBtn = document.getElementById('docPrev');
    var nextBtn = document.getElementById('docNext');
    var dotsEl  = document.getElementById('docDots');
    if (!track) return;

    var cards   = track.children;
    var total   = cards.length;
    var perView = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 640 ? 2 : 1;
    var maxIdx  = Math.max(0, total - perView);
    var current = 0;
    var autoTimer;

    // Build dots
    var dots = [];
    for (var d = 0; d <= maxIdx; d++) {
        var dot = document.createElement('button');
        dot.className = 'w-2 h-2 rounded-full transition-all ' + (d === 0 ? 'bg-blue-600 w-5' : 'bg-gray-300');
        dot.dataset.idx = d;
        dot.addEventListener('click', function() { goTo(parseInt(this.dataset.idx)); });
        dotsEl.appendChild(dot);
        dots.push(dot);
    }

    function updateDots() {
        dots.forEach(function(d, i) {
            d.className = 'w-2 h-2 rounded-full transition-all ' + (i === current ? 'bg-blue-600 w-5' : 'bg-gray-300');
        });
    }

    function getCardWidth() {
        var gap = 24;
        return (track.parentElement.offsetWidth - gap * (perView - 1)) / perView;
    }

    function goTo(idx) {
        current = Math.max(0, Math.min(idx, maxIdx));
        var w   = getCardWidth() + 24;
        track.style.transform = 'translateX(-' + (current * w) + 'px)';
        prevBtn.disabled = current === 0;
        nextBtn.disabled = current === maxIdx;
        updateDots();
        resetAuto();
    }

    function resetAuto() {
        clearInterval(autoTimer);
        autoTimer = setInterval(function() { goTo(current < maxIdx ? current + 1 : 0); }, 4500);
    }

    prevBtn.addEventListener('click', function() { goTo(current - 1); });
    nextBtn.addEventListener('click', function() { goTo(current + 1); });
    goTo(0);
})();

// ── FAQ accordion ─────────────────────────────────────────
document.querySelectorAll('.faq-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var body = btn.nextElementSibling;
        var icon = btn.querySelector('.faq-icon i');
        var isOpen = !body.classList.contains('hidden');

        // Close all
        document.querySelectorAll('.faq-body').forEach(function(b) { b.classList.add('hidden'); });
        document.querySelectorAll('.faq-icon i').forEach(function(ic) {
            ic.classList.remove('fa-minus','rotate-45');
            ic.classList.add('fa-plus');
        });
        document.querySelectorAll('.faq-btn').forEach(function(b) { b.classList.remove('text-blue-600'); });

        // Open clicked (toggle)
        if (!isOpen) {
            body.classList.remove('hidden');
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
            btn.classList.add('text-blue-600');
        }
    });
});
</script>
