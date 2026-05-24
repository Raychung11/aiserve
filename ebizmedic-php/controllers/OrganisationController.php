<?php

class OrganisationController
{
    private int $userId;
    private array $org;

    public function __construct()
    {
        Auth::requireRole('organisation');
        $this->userId = Auth::id();
        $org = Database::queryOne(
            'SELECT o.*, u.email FROM organisations o JOIN users u ON o.user_id = u.id WHERE o.user_id = ?',
            [$this->userId]
        );
        if (!$org) {
            Database::insert('INSERT INTO organisations (user_id, name, email) VALUES (?,?,?)', [
                $this->userId, Auth::user()['name'], ''
            ]);
            $org = Database::queryOne(
                'SELECT o.*, u.email FROM organisations o JOIN users u ON o.user_id = u.id WHERE o.user_id = ?',
                [$this->userId]
            );
        }
        $this->org = $org;
    }

    public function dashboard(): void
    {
        $orgId = $this->org['id'];

        $stats = [
            'doctors'      => Database::queryOne('SELECT COUNT(*) as c FROM doctors WHERE organisation_id = ?', [$orgId])['c'],
            'services'     => Database::queryOne('SELECT COUNT(*) as c FROM services WHERE organisation_id = ?', [$orgId])['c'],
            'appointments' => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE organisation_id = ?', [$orgId])['c'],
            'pending'      => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE organisation_id = ? AND status = "pending"', [$orgId])['c'],
            'completed'    => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE organisation_id = ? AND status = "completed"', [$orgId])['c'],
            'today'        => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE organisation_id = ? AND appointment_date = CURDATE()', [$orgId])['c'],
            'revenue'      => Database::queryOne(
                'SELECT COALESCE(SUM(d.consultation_fee),0) as r FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.organisation_id = ? AND a.status = "completed"',
                [$orgId]
            )['r'],
            'month_revenue'=> Database::queryOne(
                'SELECT COALESCE(SUM(d.consultation_fee),0) as r FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.organisation_id = ? AND a.status = "completed" AND MONTH(a.appointment_date) = MONTH(CURDATE()) AND YEAR(a.appointment_date) = YEAR(CURDATE())',
                [$orgId]
            )['r'],
        ];

        $recentAppointments = Database::query(
            'SELECT a.*, u.name AS patient_name, du.name AS doctor_name
         FROM appointments a
         JOIN users u ON a.patient_id = u.id
         JOIN doctors d ON a.doctor_id = d.id
         JOIN users du ON d.user_id = du.id
         WHERE a.organisation_id = ?
         ORDER BY a.created_at DESC LIMIT 8',
            [$orgId]
        );

        // Monthly chart data — last 6 months
        $monthlyRaw = Database::query(
            'SELECT DATE_FORMAT(a.appointment_date, "%Y-%m") as month, COUNT(*) as total
         FROM appointments a WHERE a.organisation_id = ? AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY month ORDER BY month ASC',
            [$orgId]
        );
        $monthMap = [];
        foreach ($monthlyRaw as $m) $monthMap[$m['month']] = (int)$m['total'];
        $chartLabels = [];
        $chartData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $chartLabels[] = date('M', strtotime("-$i months"));
            $chartData[]   = $monthMap[$key] ?? 0;
        }

        view('layouts/app', [
            'pageTitle'          => 'Organisation Dashboard',
            'content'            => 'organisation/dashboard',
            'org'                => $this->org,
            'stats'              => $stats,
            'recentAppointments' => $recentAppointments,
            'chartLabels'        => json_encode($chartLabels),
            'chartData'          => json_encode($chartData),
        ]);
    }

    public function doctors(): void
    {
        $orgId = $this->org['id'];
        $doctors = Database::query(
            'SELECT d.*, u.name, u.email, u.is_active FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE d.organisation_id = ? ORDER BY u.name',
            [$orgId]
        );

        view('layouts/app', [
            'pageTitle' => 'Our Doctors',
            'content'   => 'organisation/doctors',
            'org'       => $this->org,
            'doctors'   => $doctors,
        ]);
    }

    public function addDoctor(): void
    {
        view('layouts/app', [
            'pageTitle' => 'Add Doctor',
            'content'   => 'organisation/doctor_form',
            'org'       => $this->org,
        ]);
    }

    public function storeDoctor(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/doctors'); }

        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $speciality = trim($_POST['speciality'] ?? '');
        $fee       = (float) ($_POST['consultation_fee'] ?? 0);

        if (!$name || !$email) { flash('error', 'Name and email required.'); redirect('organisation/doctors/add'); }

        $exists = Database::queryOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) { flash('error', 'Email already registered.'); redirect('organisation/doctors/add'); }

        $userId = Database::insert(
            'INSERT INTO users (name, email, password, role) VALUES (?,?,?,"medic")',
            [$name, $email, password_hash('Doctor@123', PASSWORD_BCRYPT)]
        );

        Database::insert(
            'INSERT INTO doctors (user_id, organisation_id, speciality, consultation_fee) VALUES (?,?,?,?)',
            [$userId, $this->org['id'], $speciality, $fee]
        );

        flash('success', 'Doctor added. Default password: Doctor@123');
        redirect('organisation/doctors');
    }

    public function services(): void
    {
        $services = Database::query(
            'SELECT * FROM services WHERE organisation_id = ? ORDER BY name',
            [$this->org['id']]
        );

        view('layouts/app', [
            'pageTitle' => 'Our Services',
            'content'   => 'organisation/services',
            'org'       => $this->org,
            'services'  => $services,
        ]);
    }

    public function storeService(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/services'); }

        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = (float) ($_POST['price'] ?? 0);
        $duration = (int) ($_POST['duration_minutes'] ?? 30);

        if (!$name) { flash('error', 'Service name is required.'); redirect('organisation/services'); }

        Database::insert(
            'INSERT INTO services (organisation_id, name, description, price, duration_minutes) VALUES (?,?,?,?,?)',
            [$this->org['id'], $name, $desc, $price, $duration]
        );

        flash('success', 'Service added.');
        redirect('organisation/services');
    }

    public function deleteService(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/services'); }
        $id = (int) ($_POST['id'] ?? 0);
        Database::execute('DELETE FROM services WHERE id = ? AND organisation_id = ?', [$id, $this->org['id']]);
        flash('success', 'Service removed.');
        redirect('organisation/services');
    }

    public function appointments(): void
    {
        $orgId  = $this->org['id'];
        $status = $_GET['status'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        $where  = 'WHERE a.organisation_id = ?';
        $params = [$orgId];
        if ($status) { $where .= ' AND a.status = ?'; $params[] = $status; }

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM appointments a $where", $params)['c'],
            15, $page
        );

        $appointments = Database::query(
            "SELECT a.*, u.name AS patient_name, du.name AS doctor_name
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             $where ORDER BY a.appointment_date DESC LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        view('layouts/app', [
            'pageTitle'    => 'Appointments',
            'content'      => 'organisation/appointments',
            'appointments' => $appointments,
            'paging'       => $paging,
            'status'       => $status,
            'org'          => $this->org,
        ]);
    }

    public function profile(): void
    {
        view('layouts/app', [
            'pageTitle' => 'Organisation Profile',
            'content'   => 'organisation/profile',
            'org'       => $this->org,
        ]);
    }

    public function updateProfile(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/profile'); }

        $name    = trim($_POST['name'] ?? '');
        $type    = $_POST['type'] ?? 'clinic';
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        Database::execute(
            'UPDATE organisations SET name=?, type=?, phone=?, address=?, city=?, description=? WHERE id=?',
            [$name, $type, $phone, $address, $city, $desc, $this->org['id']]
        );

        flash('success', 'Profile updated.');
        redirect('organisation/profile');
    }

    public function updatePhoto(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/profile'); }
        $path = uploadPhoto('photo', 'orgs');
        if ($path) {
            Database::execute('UPDATE users SET avatar = ? WHERE id = ?', [$path, $this->userId]);
            Auth::refreshAvatar($path);
            flash('success', 'Logo updated.');
        }
        redirect('organisation/profile');
    }

    public function changePassword(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/profile'); }

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = Database::queryOne('SELECT password FROM users WHERE id = ?', [$this->userId]);
        if (!password_verify($current, $user['password'])) {
            flash('error', 'Current password is incorrect.'); redirect('organisation/profile');
        }
        if (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.'); redirect('organisation/profile');
        }
        if ($new !== $confirm) {
            flash('error', 'Passwords do not match.'); redirect('organisation/profile');
        }

        Database::execute('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_BCRYPT), $this->userId]);
        flash('success', 'Password changed successfully.');
        redirect('organisation/profile');
    }

    // ── Dispensary ────────────────────────────────────────────────────────────

    public function dispensary(): void
    {
        $orgId    = $this->org['id'];
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where  = 'WHERE organisation_id = ?';
        $params = [$orgId];
        if ($search)   { $where .= ' AND (name LIKE ? OR generic_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where .= ' AND category = ?'; $params[] = $category; }

        $medicines = Database::query("SELECT * FROM medicines $where ORDER BY name", $params);
        $categories = Database::query(
            'SELECT DISTINCT category FROM medicines WHERE organisation_id = ? AND category IS NOT NULL ORDER BY category', [$orgId]
        );
        $lowCount = Database::queryOne(
            'SELECT COUNT(*) as c FROM medicines WHERE organisation_id = ? AND stock_qty <= reorder_level AND is_active = 1', [$orgId]
        )['c'];

        // Pharmacist staff
        $staff = Database::query(
            'SELECT u.* FROM users u
             JOIN pharmacists p ON p.user_id = u.id
             WHERE p.organisation_id = ?',
            [$orgId]
        );

        view('layouts/app', [
            'pageTitle'  => 'Dispensary',
            'content'    => 'organisation/dispensary',
            'medicines'  => $medicines,
            'categories' => $categories,
            'lowCount'   => $lowCount,
            'search'     => $search,
            'category'   => $category,
            'org'        => $this->org,
            'staff'      => $staff,
        ]);
    }

    public function addMedicine(): void
    {
        view('layouts/app', [
            'pageTitle' => 'Add Medicine',
            'content'   => 'organisation/medicine_form',
            'medicine'  => null,
            'org'       => $this->org,
        ]);
    }

    public function storeMedicine(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/dispensary'); }

        $name        = trim($_POST['name'] ?? '');
        $generic     = trim($_POST['generic_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $unit        = trim($_POST['unit'] ?? 'tablet');
        $stock       = (int) ($_POST['stock_qty'] ?? 0);
        $reorder     = (int) ($_POST['reorder_level'] ?? 10);
        $price       = (float) ($_POST['unit_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$name) { flash('error', 'Medicine name is required.'); redirect('organisation/dispensary/add'); }

        $medId = Database::insert(
            'INSERT INTO medicines (organisation_id,name,generic_name,category,unit,stock_qty,reorder_level,unit_price,description)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$this->org['id'], $name, $generic, $category, $unit, $stock, $reorder, $price, $description]
        );

        if ($stock > 0) {
            Database::insert(
                'INSERT INTO stock_movements (medicine_id,type,quantity,reference,created_by) VALUES (?,?,?,?,?)',
                [$medId, 'in', $stock, 'Initial stock', $this->userId]
            );
        }

        flash('success', 'Medicine added successfully.');
        redirect('organisation/dispensary');
    }

    public function editMedicine(): void
    {
        $id  = (int) ($_GET['id'] ?? 0);
        $med = Database::queryOne('SELECT * FROM medicines WHERE id = ? AND organisation_id = ?', [$id, $this->org['id']]);
        if (!$med) { flash('error', 'Medicine not found.'); redirect('organisation/dispensary'); }

        view('layouts/app', [
            'pageTitle' => 'Edit Medicine',
            'content'   => 'organisation/medicine_form',
            'medicine'  => $med,
            'org'       => $this->org,
        ]);
    }

    public function updateMedicine(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/dispensary'); }

        $id          = (int) ($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $generic     = trim($_POST['generic_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $unit        = trim($_POST['unit'] ?? 'tablet');
        $reorder     = (int) ($_POST['reorder_level'] ?? 10);
        $price       = (float) ($_POST['unit_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        Database::execute(
            'UPDATE medicines SET name=?,generic_name=?,category=?,unit=?,reorder_level=?,unit_price=?,description=?,is_active=? WHERE id=? AND organisation_id=?',
            [$name, $generic, $category, $unit, $reorder, $price, $description, $isActive, $id, $this->org['id']]
        );

        flash('success', 'Medicine updated.');
        redirect('organisation/dispensary');
    }

    public function stockForm(): void
    {
        $id  = (int) ($_GET['id'] ?? 0);
        $med = Database::queryOne('SELECT * FROM medicines WHERE id = ? AND organisation_id = ?', [$id, $this->org['id']]);
        if (!$med) { flash('error', 'Medicine not found.'); redirect('organisation/dispensary'); }

        $movements = Database::query(
            'SELECT sm.*, u.name AS by_name FROM stock_movements sm
             JOIN users u ON sm.created_by = u.id
             WHERE sm.medicine_id = ? ORDER BY sm.created_at DESC LIMIT 20',
            [$id]
        );

        view('layouts/app', [
            'pageTitle' => 'Manage Stock — ' . $med['name'],
            'content'   => 'organisation/stock_form',
            'medicine'  => $med,
            'movements' => $movements,
            'org'       => $this->org,
        ]);
    }

    public function saveStock(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/dispensary'); }

        $medId    = (int) ($_POST['medicine_id'] ?? 0);
        $type     = in_array($_POST['type'] ?? '', ['in','adjustment']) ? $_POST['type'] : 'in';
        $qty      = (int) ($_POST['quantity'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        $ref      = trim($_POST['reference'] ?? '');

        $med = Database::queryOne('SELECT * FROM medicines WHERE id = ? AND organisation_id = ?', [$medId, $this->org['id']]);
        if (!$med || $qty <= 0) { flash('error', 'Invalid data.'); redirect('organisation/dispensary'); }

        if ($type === 'in') {
            Database::execute('UPDATE medicines SET stock_qty = stock_qty + ? WHERE id = ?', [$qty, $medId]);
        } else {
            Database::execute('UPDATE medicines SET stock_qty = ? WHERE id = ?', [$qty, $medId]);
        }

        Database::insert(
            'INSERT INTO stock_movements (medicine_id,type,quantity,reference,notes,created_by) VALUES (?,?,?,?,?,?)',
            [$medId, $type, $qty, $ref ?: 'Manual stock update', $notes, $this->userId]
        );

        flash('success', 'Stock updated successfully.');
        redirect('organisation/dispensary/stock?id=' . $medId);
    }

    public function dispensaryHistory(): void
    {
        $orgId  = $this->org['id'];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $paging = paginate(
            Database::queryOne('SELECT COUNT(*) as c FROM dispensings WHERE organisation_id = ?', [$orgId])['c'],
            20, $page
        );

        $dispensings = Database::query(
            'SELECT d.*, u.name AS patient_name, du.name AS dispensed_by_name
             FROM dispensings d
             JOIN users u  ON d.patient_id  = u.id
             JOIN users du ON d.dispensed_by = du.id
             WHERE d.organisation_id = ?
             ORDER BY d.created_at DESC LIMIT ? OFFSET ?',
            [$orgId, $paging['per_page'], $paging['offset']]
        );

        view('layouts/app', [
            'pageTitle'   => 'Dispensing History',
            'content'     => 'organisation/dispensary_history',
            'dispensings' => $dispensings,
            'paging'      => $paging,
            'org'         => $this->org,
        ]);
    }

    public function storePharmacist(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('organisation/dispensary'); }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = 'Pharma@123';

        if (!$name || !$email) { flash('error', 'Name and email required.'); redirect('organisation/dispensary'); }
        if (Database::queryOne('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', 'Email already registered.'); redirect('organisation/dispensary');
        }

        $userId = Database::insert(
            'INSERT INTO users (name,email,password,role,approved) VALUES (?,?,?,"pharmacist",1)',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
        );

        Database::insert(
            'INSERT INTO pharmacists (user_id,organisation_id) VALUES (?,?)',
            [$userId, $this->org['id']]
        );

        flash('success', "Pharmacist added. Default password: $password");
        redirect('organisation/dispensary');
    }
}
