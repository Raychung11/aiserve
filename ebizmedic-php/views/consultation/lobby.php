<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 flex items-center justify-center px-4 py-12">
<div class="w-full max-w-lg">

    <!-- Card -->
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        <!-- Top banner -->
        <div class="bg-gradient-to-r from-blue-600 to-cyan-500 px-8 py-5 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-video text-white text-sm"></i>
            </div>
            <div>
                <p class="text-white font-bold">eBizMedic Online Consultation</p>
                <p class="text-blue-100 text-xs">Secure, private, encrypted</p>
            </div>
            <div class="ml-auto flex items-center gap-1.5 bg-white/15 px-3 py-1 rounded-full">
                <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                <span class="text-white text-xs font-medium">Secure</span>
            </div>
        </div>

        <div class="p-8">

            <!-- Participants -->
            <div class="flex items-center gap-5 mb-8">
                <div class="flex -space-x-3">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xl font-bold ring-3 ring-white z-10">
                        <?= strtoupper(substr($appt['doctor_name'], 0, 1)) ?>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center text-white text-xl font-bold ring-3 ring-white">
                        <?= strtoupper(substr($appt['patient_name'], 0, 1)) ?>
                    </div>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Dr. <?= e($appt['doctor_name']) ?></p>
                    <p class="text-sm text-blue-600"><?= e($appt['speciality'] ?? 'General Practitioner') ?></p>
                    <p class="text-xs text-gray-400 mt-0.5">with <?= e($appt['patient_name']) ?></p>
                </div>
            </div>

            <!-- Appointment info -->
            <div class="bg-gray-50 rounded-2xl p-5 mb-6 space-y-2">
                <div class="flex items-center gap-3 text-sm">
                    <i class="fa-regular fa-calendar text-blue-400 w-4 text-center"></i>
                    <span class="text-gray-700 font-medium"><?= date('l, d F Y', strtotime($appt['appointment_date'])) ?></span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <i class="fa-regular fa-clock text-blue-400 w-4 text-center"></i>
                    <span class="text-gray-700 font-medium"><?= date('H:i', strtotime($appt['appointment_time'])) ?></span>
                    <?php
                    $apptDt  = new DateTime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
                    $now     = new DateTime();
                    $diffMin = ($apptDt->getTimestamp() - $now->getTimestamp()) / 60;
                    ?>
                    <?php if ($diffMin > 0 && $diffMin <= 30): ?>
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Starting soon</span>
                    <?php elseif ($diffMin <= 0): ?>
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium animate-pulse">Live now</span>
                    <?php endif; ?>
                </div>
                <?php if ($appt['org_name']): ?>
                <div class="flex items-center gap-3 text-sm">
                    <i class="fa-solid fa-hospital text-blue-400 w-4 text-center"></i>
                    <span class="text-gray-500"><?= e($appt['org_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Countdown -->
            <div class="text-center mb-6" id="countdownBlock">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Consultation <?= $diffMin > 0 ? 'starts in' : 'started' ?></p>
                <div id="countdown" class="text-4xl font-bold tabular-nums <?= $diffMin <= 0 ? 'text-green-600' : 'text-blue-600' ?>">--:--</div>
            </div>

            <!-- System checklist -->
            <div class="space-y-3 mb-8">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Before You Join</p>
                <?php
                $checks = [
                    ['fa-video',     'Camera',      'Ensure your camera is plugged in and accessible'],
                    ['fa-microphone','Microphone',  'Check your microphone is working'],
                    ['fa-wifi',      'Connection',  'Stable internet connection recommended (4G+)'],
                    ['fa-headphones','Audio Output','Use headphones for best audio quality'],
                ];
                foreach ($checks as $check): ?>
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid <?= $check[0] ?> text-blue-500 text-xs"></i>
                    </div>
                    <div>
                        <span class="font-medium text-gray-800"><?= $check[1] ?></span>
                        <span class="text-gray-400 text-xs ml-2"><?= $check[2] ?></span>
                    </div>
                    <i class="fa-solid fa-check-circle text-green-400 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Role badge -->
            <div class="mb-5 flex items-center gap-2">
                <div class="<?= $isDoctor ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?> text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5">
                    <i class="fa-solid <?= $isDoctor ? 'fa-user-doctor' : 'fa-user' ?> text-xs"></i>
                    Joining as: <?= $isDoctor ? 'Doctor' : 'Patient' ?>
                </div>
            </div>

            <!-- Join button -->
            <a href="<?= url('consultation/room?appointment_id=' . $appt['id']) ?>"
               class="block w-full py-4 bg-green-500 hover:bg-green-600 text-white font-bold text-center rounded-2xl transition-all hover:shadow-lg hover:-translate-y-0.5 transform duration-200 text-sm">
                <i class="fa-solid fa-video mr-2"></i> Join Video Consultation
            </a>

            <a href="javascript:history.back()" class="block text-center text-xs text-gray-400 hover:text-gray-600 mt-4">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Appointments
            </a>
        </div>
    </div>

    <!-- Tips -->
    <p class="text-center text-xs text-gray-400 mt-5">
        <i class="fa-solid fa-lock mr-1"></i>
        Your consultation is private and end-to-end encrypted via Jitsi Meet.
    </p>
</div>
</div>

<script>
(function() {
    var apptDate = '<?= $appt['appointment_date'] ?>';
    var apptTime = '<?= $appt['appointment_time'] ?>';
    var apptTs   = new Date(apptDate + 'T' + apptTime).getTime();
    var el       = document.getElementById('countdown');

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var diff = apptTs - Date.now();
        if (diff <= 0) {
            el.textContent   = 'Now';
            el.className     = 'text-4xl font-bold text-green-600 animate-pulse';
            document.getElementById('countdownBlock').querySelector('p').textContent = 'Consultation is live';
            return;
        }
        var h = Math.floor(diff / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000)   / 1000);
        el.textContent = (h ? h + 'h ' : '') + pad(m) + ':' + pad(s);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
