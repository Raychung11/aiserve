<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="mb-6">
        <a href="<?= url('doctors/show?id=' . $doctor['id']) ?>" class="text-sm text-blue-600 hover:underline">&larr; Back to Doctor Profile</a>
    </div>

    <!-- Doctor Header -->
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mb-6 flex items-center gap-4">
        <?php if ($doctor['avatar']): ?>
        <img src="<?= asset($doctor['avatar']) ?>" class="w-14 h-14 rounded-full object-cover flex-shrink-0">
        <?php else: ?>
        <div class="w-14 h-14 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 text-xl font-bold flex-shrink-0">
            <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div>
            <h2 class="font-bold text-gray-900 text-lg"><?= e($doctor['name']) ?></h2>
            <p class="text-sm text-blue-600"><?= e($doctor['speciality'] ?? 'General Practitioner') ?></p>
            <?php if ($doctor['consultation_fee'] > 0): ?>
            <p class="text-xs text-gray-500 mt-0.5">Consultation fee: <strong>RM <?= number_format($doctor['consultation_fee'], 2) ?></strong></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <form method="POST" action="<?= url('booking/store') ?>" id="bookingForm" class="p-6 space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">

            <!-- Service -->
            <?php if (!empty($services)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Service</label>
                <select name="service_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Select a service (optional) —</option>
                    <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>"><?= e($svc['name']) ?> — RM <?= number_format($svc['price'], 2) ?> (<?= $svc['duration_minutes'] ?>min)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Consultation Type</label>
                <div class="flex gap-3">
                    <?php if ($doctor['is_available_online']): ?>
                    <label class="flex-1 flex items-center gap-3 border-2 border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-400 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="type" value="online" class="text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-800"><i class="fa-solid fa-video text-green-500 mr-1"></i> Online</p>
                            <p class="text-xs text-gray-400">Video consultation</p>
                        </div>
                    </label>
                    <?php endif; ?>
                    <?php if ($doctor['is_available_onsite']): ?>
                    <label class="flex-1 flex items-center gap-3 border-2 border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-400 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="type" value="onsite" <?= !$doctor['is_available_online'] ? 'checked' : '' ?> class="text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-800"><i class="fa-solid fa-hospital text-blue-500 mr-1"></i> Onsite</p>
                            <p class="text-xs text-gray-400">Visit the clinic</p>
                        </div>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Appointment Date</label>
                <input type="date" id="appointmentDate" name="appointment_date" required
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p id="noSchedule" class="hidden text-xs text-red-500 mt-1">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i>Doctor is not available on this day. Please choose another date.
                </p>
            </div>

            <!-- Time Slots -->
            <div id="slotsSection" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Time Slots</label>
                <div id="slotsGrid" class="grid grid-cols-4 sm:grid-cols-6 gap-2"></div>
                <input type="hidden" name="appointment_time" id="selectedTime">
                <p id="noSlots" class="hidden text-xs text-gray-500 mt-2">No available slots for this date.</p>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes (optional)</label>
                <textarea name="notes" rows="3" placeholder="Describe your symptoms or reason for visit..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <button type="submit" id="submitBtn" disabled
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl transition-colors text-sm disabled:opacity-40 disabled:cursor-not-allowed hover:bg-blue-700">
                Confirm Booking
            </button>
        </form>
    </div>
</div>

<script>
const scheduleMap = <?= json_encode($scheduleMap) ?>;
const bookedMap   = <?= json_encode($bookedMap) ?>;
const days        = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];

const dateInput     = document.getElementById('appointmentDate');
const slotsSection  = document.getElementById('slotsSection');
const slotsGrid     = document.getElementById('slotsGrid');
const selectedTime  = document.getElementById('selectedTime');
const noSchedule    = document.getElementById('noSchedule');
const noSlots       = document.getElementById('noSlots');
const submitBtn     = document.getElementById('submitBtn');

dateInput.addEventListener('change', function () {
    const date    = this.value;
    const dayName = days[new Date(date + 'T00:00:00').getDay()];
    const sched   = scheduleMap[dayName];

    slotsGrid.innerHTML = '';
    selectedTime.value  = '';
    submitBtn.disabled  = true;

    if (!sched) {
        noSchedule.classList.remove('hidden');
        slotsSection.classList.add('hidden');
        return;
    }
    noSchedule.classList.add('hidden');
    slotsSection.classList.remove('hidden');

    // Generate 30-min slots
    const booked = bookedMap[date] || [];
    const slots  = [];
    let cur = toMinutes(sched.start);
    const end = toMinutes(sched.end);
    while (cur < end) {
        slots.push(toTime(cur));
        cur += 30;
    }

    if (slots.length === 0) {
        noSlots.classList.remove('hidden');
        return;
    }
    noSlots.classList.add('hidden');

    slots.forEach(time => {
        const taken = booked.includes(time);
        const btn   = document.createElement('button');
        btn.type    = 'button';
        btn.textContent = time;
        btn.disabled    = taken;
        btn.className   = taken
            ? 'py-2 text-xs rounded-lg bg-gray-100 text-gray-400 line-through cursor-not-allowed border border-gray-200'
            : 'py-2 text-xs rounded-lg border border-gray-300 text-gray-700 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 transition-colors font-medium';

        if (!taken) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#slotsGrid button').forEach(b => {
                    b.classList.remove('bg-blue-600','text-white','border-blue-600');
                    b.classList.add('border-gray-300','text-gray-700');
                });
                this.classList.add('bg-blue-600','text-white','border-blue-600');
                this.classList.remove('border-gray-300','text-gray-700');
                selectedTime.value = time;
                submitBtn.disabled = false;
            });
        }
        slotsGrid.appendChild(btn);
    });
});

function toMinutes(t) {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}
function toTime(m) {
    return String(Math.floor(m/60)).padStart(2,'0') + ':' + String(m%60).padStart(2,'0');
}
</script>
