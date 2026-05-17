<?php

class PharmacistController
{
    private int $userId;
    private ?array $org;

    public function __construct()
    {
        Auth::requireRole('pharmacist');
        $this->userId = Auth::id();

        // Pharmacist is linked to an organisation via the users table (org_id stored in doctors-like way)
        // We find their org via the pharmacists table join or org assignment
        $this->org = Database::queryOne(
            'SELECT o.* FROM organisations o
             JOIN users u ON u.id = ?
             WHERE o.id = (SELECT organisation_id FROM pharmacists WHERE user_id = ? LIMIT 1)',
            [$this->userId, $this->userId]
        );
    }

    public function dashboard(): void
    {
        $orgId = $this->org['id'] ?? null;

        $stats = [
            'medicines'   => $orgId ? Database::queryOne('SELECT COUNT(*) as c FROM medicines WHERE organisation_id = ? AND is_active = 1', [$orgId])['c'] : 0,
            'dispensed'   => $orgId ? Database::queryOne('SELECT COUNT(*) as c FROM dispensings WHERE organisation_id = ?', [$orgId])['c'] : 0,
            'low_stock'   => $orgId ? Database::queryOne('SELECT COUNT(*) as c FROM medicines WHERE organisation_id = ? AND stock_qty <= reorder_level AND is_active = 1', [$orgId])['c'] : 0,
            'today'       => $orgId ? Database::queryOne('SELECT COUNT(*) as c FROM dispensings WHERE organisation_id = ? AND DATE(created_at) = CURDATE()', [$orgId])['c'] : 0,
        ];

        $lowStock = $orgId ? Database::query(
            'SELECT * FROM medicines WHERE organisation_id = ? AND stock_qty <= reorder_level AND is_active = 1 ORDER BY stock_qty ASC LIMIT 8',
            [$orgId]
        ) : [];

        $recentDispensings = $orgId ? Database::query(
            'SELECT d.*, u.name AS patient_name, du.name AS dispensed_by_name
             FROM dispensings d
             JOIN users u  ON d.patient_id   = u.id
             JOIN users du ON d.dispensed_by  = du.id
             WHERE d.organisation_id = ?
             ORDER BY d.created_at DESC LIMIT 8',
            [$orgId]
        ) : [];

        view('layouts/app', [
            'pageTitle'         => 'Dispensary Dashboard',
            'content'           => 'pharmacist/dashboard',
            'org'               => $this->org,
            'stats'             => $stats,
            'lowStock'          => $lowStock,
            'recentDispensings' => $recentDispensings,
        ]);
    }

    public function medicines(): void
    {
        $orgId    = $this->org['id'] ?? null;
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where  = 'WHERE m.organisation_id = ? AND m.is_active = 1';
        $params = [$orgId];
        if ($search)   { $where .= ' AND (m.name LIKE ? OR m.generic_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where .= ' AND m.category = ?'; $params[] = $category; }

        $medicines = $orgId ? Database::query(
            "SELECT * FROM medicines m $where ORDER BY m.name", $params
        ) : [];

        $categories = $orgId ? Database::query(
            'SELECT DISTINCT category FROM medicines WHERE organisation_id = ? AND category IS NOT NULL ORDER BY category', [$orgId]
        ) : [];

        view('layouts/app', [
            'pageTitle'  => 'Medicine Inventory',
            'content'    => 'pharmacist/medicines',
            'medicines'  => $medicines,
            'categories' => $categories,
            'search'     => $search,
            'category'   => $category,
            'org'        => $this->org,
        ]);
    }

    public function dispense(): void
    {
        $orgId = $this->org['id'] ?? null;
        if (!$orgId) { flash('error', 'No organisation assigned. Contact admin.'); redirect('pharmacist/dashboard'); }

        $recordId  = (int) ($_GET['record_id'] ?? 0);
        $record    = null;
        $patient   = null;

        if ($recordId) {
            $record = Database::queryOne(
                'SELECT mr.*, u.name AS patient_name, u.id AS patient_user_id,
                        du.name AS doctor_name, a.appointment_date
                 FROM medical_records mr
                 JOIN users u  ON mr.patient_id = u.id
                 JOIN doctors d ON mr.doctor_id = d.id
                 JOIN users du ON d.user_id = du.id
                 JOIN appointments a ON mr.appointment_id = a.id
                 WHERE mr.id = ? AND mr.doctor_id IN (SELECT id FROM doctors WHERE organisation_id = ?)',
                [$recordId, $orgId]
            );
        }

        $medicines = Database::query(
            'SELECT * FROM medicines WHERE organisation_id = ? AND is_active = 1 AND stock_qty > 0 ORDER BY name',
            [$orgId]
        );

        // Pending medical records not yet dispensed for this org
        $pendingRecords = Database::query(
            'SELECT mr.id, u.name AS patient_name, du.name AS doctor_name,
                    a.appointment_date, mr.diagnosis, mr.prescription
             FROM medical_records mr
             JOIN users u  ON mr.patient_id = u.id
             JOIN doctors d ON mr.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             JOIN appointments a ON mr.appointment_id = a.id
             WHERE d.organisation_id = ?
               AND mr.id NOT IN (SELECT COALESCE(medical_record_id, 0) FROM dispensings WHERE medical_record_id IS NOT NULL)
             ORDER BY a.appointment_date DESC LIMIT 30',
            [$orgId]
        );

        view('layouts/app', [
            'pageTitle'      => 'Dispense Medicines',
            'content'        => 'pharmacist/dispense',
            'medicines'      => $medicines,
            'record'         => $record,
            'pendingRecords' => $pendingRecords,
            'org'            => $this->org,
        ]);
    }

    public function storeDispensing(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('pharmacist/dispense'); }

        $orgId    = $this->org['id'] ?? null;
        if (!$orgId) { flash('error', 'No organisation assigned.'); redirect('pharmacist/dashboard'); }

        $recordId  = $_POST['medical_record_id'] ? (int) $_POST['medical_record_id'] : null;
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $notes     = trim($_POST['notes'] ?? '');

        $medIds   = $_POST['medicine_id']  ?? [];
        $qtys     = $_POST['quantity']     ?? [];
        $dosages  = $_POST['dosage']       ?? [];

        if (!$patientId || empty($medIds)) {
            flash('error', 'Patient and at least one medicine are required.');
            redirect('pharmacist/dispense');
        }

        // Get doctor_id from medical record if linked
        $doctorId = null;
        if ($recordId) {
            $rec = Database::queryOne('SELECT doctor_id FROM medical_records WHERE id = ?', [$recordId]);
            $doctorId = $rec['doctor_id'] ?? null;
        }

        // Calculate total
        $total = 0;
        $lines = [];
        foreach ($medIds as $i => $medId) {
            $qty = (int) ($qtys[$i] ?? 0);
            if ($qty <= 0 || !$medId) continue;
            $med = Database::queryOne('SELECT * FROM medicines WHERE id = ? AND organisation_id = ?', [(int)$medId, $orgId]);
            if (!$med || $med['stock_qty'] < $qty) {
                flash('error', "Insufficient stock for {$med['name']}.");
                redirect('pharmacist/dispense');
            }
            $total += $med['unit_price'] * $qty;
            $lines[] = ['med' => $med, 'qty' => $qty, 'dosage' => $dosages[$i] ?? ''];
        }

        $dispensingId = Database::insert(
            'INSERT INTO dispensings (patient_id,doctor_id,organisation_id,medical_record_id,dispensed_by,notes,total_amount)
             VALUES (?,?,?,?,?,?,?)',
            [$patientId, $doctorId, $orgId, $recordId, $this->userId, $notes, $total]
        );

        foreach ($lines as $line) {
            Database::insert(
                'INSERT INTO dispensing_items (dispensing_id,medicine_id,quantity,unit_price,dosage_instructions) VALUES (?,?,?,?,?)',
                [$dispensingId, $line['med']['id'], $line['qty'], $line['med']['unit_price'], $line['dosage']]
            );
            // Deduct stock
            Database::execute('UPDATE medicines SET stock_qty = stock_qty - ? WHERE id = ?', [$line['qty'], $line['med']['id']]);
            // Log movement
            Database::insert(
                'INSERT INTO stock_movements (medicine_id,type,quantity,reference,created_by) VALUES (?,?,?,?,?)',
                [$line['med']['id'], 'out', $line['qty'], 'Dispensing #' . $dispensingId, $this->userId]
            );
        }

        // Notify patient
        notify($patientId, 'dispensing', 'Medicines Dispensed',
            count($lines) . ' medicine(s) dispensed. Total: RM ' . number_format($total, 2) . '. Check your dispensing history.',
            'user/dispensary');

        flash('success', 'Medicines dispensed successfully. Total: RM ' . number_format($total, 2));
        redirect('pharmacist/history');
    }

    public function history(): void
    {
        $orgId = $this->org['id'] ?? null;
        $page  = max(1, (int) ($_GET['page'] ?? 1));

        $paging = paginate(
            Database::queryOne('SELECT COUNT(*) as c FROM dispensings WHERE organisation_id = ?', [$orgId])['c'],
            20, $page
        );

        $dispensings = $orgId ? Database::query(
            'SELECT d.*, u.name AS patient_name, du.name AS dispensed_by_name
             FROM dispensings d
             JOIN users u  ON d.patient_id  = u.id
             JOIN users du ON d.dispensed_by = du.id
             WHERE d.organisation_id = ?
             ORDER BY d.created_at DESC LIMIT ? OFFSET ?',
            [$orgId, $paging['per_page'], $paging['offset']]
        ) : [];

        view('layouts/app', [
            'pageTitle'   => 'Dispensing History',
            'content'     => 'pharmacist/history',
            'dispensings' => $dispensings,
            'paging'      => $paging,
            'org'         => $this->org,
        ]);
    }
}
