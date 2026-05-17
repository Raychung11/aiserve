<div class="pt-4">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left: Pending Records -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="p-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-notes-medical text-blue-500"></i> Pending Prescriptions
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Click a record to auto-fill patient</p>
            </div>
            <?php if (empty($pendingRecords)): ?>
            <p class="text-center text-sm text-gray-400 py-8">No pending prescriptions</p>
            <?php else: ?>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                <?php foreach ($pendingRecords as $pr): ?>
                <a href="<?= url('pharmacist/dispense') ?>?record_id=<?= $pr['id'] ?>"
                   class="block px-4 py-3 hover:bg-blue-50 transition-colors <?= (($record['id'] ?? 0) == $pr['id']) ? 'bg-blue-50 border-l-2 border-blue-500' : '' ?>">
                    <p class="text-sm font-medium text-gray-900"><?= e($pr['patient_name']) ?></p>
                    <p class="text-xs text-gray-500">Dr. <?= e($pr['doctor_name']) ?> · <?= date('d M Y', strtotime($pr['appointment_date'])) ?></p>
                    <?php if ($pr['diagnosis']): ?>
                    <p class="text-xs text-blue-600 mt-0.5 truncate"><?= e($pr['diagnosis']) ?></p>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Dispense Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-medical text-green-500"></i> Dispense Medicines
                </h2>
            </div>

            <form method="POST" action="<?= url('pharmacist/dispense/store') ?>" id="dispenseForm" class="p-5 space-y-5">
                <?= csrf_field() ?>

                <!-- Patient & Record Info -->
                <input type="hidden" name="medical_record_id" id="recordId" value="<?= e($record['id'] ?? '') ?>">
                <input type="hidden" name="patient_id" id="patientId" value="<?= e($record['patient_user_id'] ?? '') ?>">

                <div class="bg-gray-50 rounded-xl p-4">
                    <?php if ($record): ?>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900"><?= e($record['patient_name']) ?></p>
                            <p class="text-xs text-gray-500">Dr. <?= e($record['doctor_name']) ?> · <?= date('d M Y', strtotime($record['appointment_date'])) ?></p>
                            <?php if ($record['diagnosis']): ?>
                            <p class="text-xs text-gray-700 mt-1"><span class="font-medium">Diagnosis:</span> <?= e($record['diagnosis']) ?></p>
                            <?php endif; ?>
                            <?php if ($record['prescription']): ?>
                            <p class="text-xs text-gray-700 mt-0.5 font-mono bg-white border border-gray-200 rounded px-2 py-1 mt-2"><?= nl2br(e($record['prescription'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="<?= url('pharmacist/dispense') ?>" class="text-xs text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <p class="text-xs text-gray-500 text-center">No record selected — select from the list or dispense manually below</p>
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Patient Name / Search</label>
                        <select name="patient_id" id="patientId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select patient…</option>
                            <?php
                            $patients = Database::query('SELECT id, name FROM users WHERE role = "user" ORDER BY name');
                            foreach ($patients as $p):
                            ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Medicine Lines -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Medicines to Dispense</h3>
                        <button type="button" id="addLine"
                                class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 font-medium">
                            <i class="fa-solid fa-plus mr-1"></i> Add Medicine
                        </button>
                    </div>

                    <div id="medicineLines" class="space-y-3">
                        <!-- Template line (rendered by JS) -->
                    </div>

                    <p class="text-xs text-gray-400 mt-2" id="noMedMsg">Add at least one medicine to dispense.</p>
                </div>

                <!-- Total & Notes -->
                <div class="border-t border-gray-100 pt-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Total Amount</span>
                        <span class="text-xl font-bold text-green-600" id="totalDisplay">RM 0.00</span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Additional instructions or pharmacist notes…"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors text-sm">
                        <i class="fa-solid fa-check mr-2"></i> Confirm Dispensing
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
</div>

<script>
const medicines = <?= json_encode(array_map(fn($m) => [
    'id'         => $m['id'],
    'name'       => $m['name'],
    'unit'       => $m['unit'],
    'price'      => (float)$m['unit_price'],
    'stock'      => (int)$m['stock_qty'],
], $medicines)) ?>;

let lineCount = 0;

function medOptions(selectedId = '') {
    let opts = '<option value="">Select medicine…</option>';
    medicines.forEach(m => {
        const sel = (m.id == selectedId) ? 'selected' : '';
        opts += `<option value="${m.id}" data-price="${m.price}" data-unit="${m.unit}" data-stock="${m.stock}" ${sel}>${m.name} (${m.stock} ${m.unit} left)</option>`;
    });
    return opts;
}

function addLine(medId = '', qty = 1, dosage = '') {
    const idx = lineCount++;
    const div = document.createElement('div');
    div.className = 'grid grid-cols-12 gap-2 items-end bg-gray-50 rounded-xl p-3';
    div.id = `line_${idx}`;
    div.innerHTML = `
        <div class="col-span-5">
            <label class="block text-xs text-gray-500 mb-1">Medicine</label>
            <select name="medicine_id[]" onchange="updateTotal()" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                ${medOptions(medId)}
            </select>
        </div>
        <div class="col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Qty</label>
            <input type="number" name="quantity[]" value="${qty}" min="1" onchange="updateTotal()"
                   class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="col-span-4">
            <label class="block text-xs text-gray-500 mb-1">Dosage Instructions</label>
            <input type="text" name="dosage[]" value="${dosage}" placeholder="e.g. 1 tablet 3x daily"
                   class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="col-span-1 flex justify-end">
            <button type="button" onclick="removeLine('line_${idx}')" class="text-red-400 hover:text-red-600 text-xs p-2">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`;
    document.getElementById('medicineLines').appendChild(div);
    updateTotal();
    updateNoMedMsg();
}

function removeLine(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
    updateTotal();
    updateNoMedMsg();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('#medicineLines > div').forEach(line => {
        const sel = line.querySelector('select[name="medicine_id[]"]');
        const qty = line.querySelector('input[name="quantity[]"]');
        if (!sel || !qty) return;
        const opt = sel.selectedOptions[0];
        if (opt && opt.dataset.price) {
            total += parseFloat(opt.dataset.price) * parseInt(qty.value || 0);
        }
    });
    document.getElementById('totalDisplay').textContent = 'RM ' + total.toFixed(2);
}

function updateNoMedMsg() {
    const lines = document.querySelectorAll('#medicineLines > div').length;
    document.getElementById('noMedMsg').style.display = lines > 0 ? 'none' : 'block';
}

document.getElementById('addLine').addEventListener('click', () => addLine());

// Validation
document.getElementById('dispenseForm').addEventListener('submit', function(e) {
    const lines = document.querySelectorAll('#medicineLines > div').length;
    if (lines === 0) { e.preventDefault(); alert('Please add at least one medicine.'); }

    const patEl = document.getElementById('patientId');
    if (!patEl.value) { e.preventDefault(); alert('Please select a patient.'); }
});

// Add one line by default
addLine();
</script>
