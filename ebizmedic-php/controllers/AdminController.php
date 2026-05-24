<?php

class AdminController
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function dashboard(): void
    {
        $stats = [
            'doctors'          => Database::queryOne('SELECT COUNT(*) as c FROM doctors')['c'],
            'organisations'    => Database::queryOne('SELECT COUNT(*) as c FROM organisations')['c'],
            'appointments'     => Database::queryOne('SELECT COUNT(*) as c FROM appointments')['c'],
            'users'            => Database::queryOne('SELECT COUNT(*) as c FROM users WHERE role = "user"')['c'],
            'today'            => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()')['c'],
            'pending_approvals'=> Database::queryOne('SELECT COUNT(*) as c FROM users WHERE approved = 0 AND role IN ("medic","organisation")')['c'],
            'total_revenue'    => Database::queryOne(
                'SELECT COALESCE(SUM(d.consultation_fee),0) as r FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.status = "completed"'
            )['r'],
            'today_revenue'    => Database::queryOne(
                'SELECT COALESCE(SUM(d.consultation_fee),0) as r FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.status = "completed" AND a.appointment_date = CURDATE()'
            )['r'],
        ];

        $recentAppointments = Database::query(
            'SELECT a.*, u.name AS patient_name, du.name AS doctor_name
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             ORDER BY a.created_at DESC LIMIT 10'
        );

        $recentDoctors = Database::query(
            'SELECT d.*, u.name, u.email, u.avatar FROM doctors d
             JOIN users u ON d.user_id = u.id
             ORDER BY d.created_at DESC LIMIT 5'
        );

        view('layouts/app', [
            'pageTitle'          => 'Admin Dashboard',
            'content'            => 'admin/dashboard',
            'stats'              => $stats,
            'recentAppointments' => $recentAppointments,
            'recentDoctors'      => $recentDoctors,
        ]);
    }

    public function doctors(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $search  = trim($_GET['search'] ?? '');
        $paging  = paginate(
            Database::queryOne(
                'SELECT COUNT(*) as c FROM doctors d JOIN users u ON d.user_id = u.id
                 WHERE u.name LIKE ? OR d.speciality LIKE ?',
                ["%$search%", "%$search%"]
            )['c'],
            15, $page
        );

        $doctors = Database::query(
            'SELECT d.*, u.name, u.email, u.is_active, o.name AS org_name
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN organisations o ON d.organisation_id = o.id
             WHERE u.name LIKE ? OR d.speciality LIKE ?
             ORDER BY d.created_at DESC
             LIMIT ? OFFSET ?',
            ["%$search%", "%$search%", $paging['per_page'], $paging['offset']]
        );

        $organisations = Database::query('SELECT id, name FROM organisations WHERE is_active = 1 ORDER BY name');

        view('layouts/app', [
            'pageTitle'     => 'Manage Doctors',
            'content'       => 'admin/doctors',
            'doctors'       => $doctors,
            'organisations' => $organisations,
            'paging'        => $paging,
            'search'        => $search,
        ]);
    }

    public function createDoctor(): void
    {
        $organisations = Database::query('SELECT id, name FROM organisations WHERE is_active = 1 ORDER BY name');
        view('layouts/app', [
            'pageTitle'     => 'Add Doctor',
            'content'       => 'admin/doctor_form',
            'doctor'        => null,
            'organisations' => $organisations,
        ]);
    }

    public function storeDoctor(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/doctors'); }

        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? 'Doctor@123';
        $speciality = trim($_POST['speciality'] ?? '');
        $orgId     = $_POST['organisation_id'] ? (int) $_POST['organisation_id'] : null;
        $fee       = (float) ($_POST['consultation_fee'] ?? 0);
        $online    = isset($_POST['is_available_online']) ? 1 : 0;
        $onsite    = isset($_POST['is_available_onsite']) ? 1 : 0;

        if (!$name || !$email) { flash('error', 'Name and email are required.'); redirect('admin/doctors/create'); }

        $exists = Database::queryOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) { flash('error', 'Email already registered.'); redirect('admin/doctors/create'); }

        $userId = Database::insert(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "medic")',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
        );

        Database::insert(
            'INSERT INTO doctors (user_id, organisation_id, speciality, consultation_fee, is_available_online, is_available_onsite)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $orgId, $speciality, $fee, $online, $onsite]
        );

        flash('success', 'Doctor added successfully.');
        redirect('admin/doctors');
    }

    public function editDoctor(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $doctor = Database::queryOne(
            'SELECT d.*, u.name, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?', [$id]
        );
        if (!$doctor) { flash('error', 'Doctor not found.'); redirect('admin/doctors'); }

        $organisations = Database::query('SELECT id, name FROM organisations WHERE is_active = 1 ORDER BY name');
        view('layouts/app', [
            'pageTitle'     => 'Edit Doctor',
            'content'       => 'admin/doctor_form',
            'doctor'        => $doctor,
            'organisations' => $organisations,
        ]);
    }

    public function updateDoctor(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/doctors'); }

        $id         = (int) ($_POST['id'] ?? 0);
        $speciality = trim($_POST['speciality'] ?? '');
        $orgId      = $_POST['organisation_id'] ? (int) $_POST['organisation_id'] : null;
        $fee        = (float) ($_POST['consultation_fee'] ?? 0);
        $online     = isset($_POST['is_available_online']) ? 1 : 0;
        $onsite     = isset($_POST['is_available_onsite']) ? 1 : 0;
        $bio        = trim($_POST['bio'] ?? '');

        Database::execute(
            'UPDATE doctors SET organisation_id=?, speciality=?, consultation_fee=?, is_available_online=?, is_available_onsite=?, bio=? WHERE id=?',
            [$orgId, $speciality, $fee, $online, $onsite, $bio, $id]
        );

        flash('success', 'Doctor updated.');
        redirect('admin/doctors');
    }

    public function deleteDoctor(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/doctors'); }
        $id = (int) ($_POST['id'] ?? 0);
        $doctor = Database::queryOne('SELECT user_id FROM doctors WHERE id = ?', [$id]);
        if ($doctor) {
            Database::execute('DELETE FROM users WHERE id = ?', [$doctor['user_id']]);
        }
        flash('success', 'Doctor removed.');
        redirect('admin/doctors');
    }

    public function organisations(): void
    {
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $paging = paginate(
            Database::queryOne('SELECT COUNT(*) as c FROM organisations WHERE name LIKE ?', ["%$search%"])['c'],
            15, $page
        );

        $organisations = Database::query(
            'SELECT o.*, u.email FROM organisations o JOIN users u ON o.user_id = u.id
             WHERE o.name LIKE ? ORDER BY o.created_at DESC LIMIT ? OFFSET ?',
            ["%$search%", $paging['per_page'], $paging['offset']]
        );

        view('layouts/app', [
            'pageTitle'     => 'Manage Organisations',
            'content'       => 'admin/organisations',
            'organisations' => $organisations,
            'paging'        => $paging,
            'search'        => $search,
        ]);
    }

    public function createOrganisation(): void
    {
        view('layouts/app', ['pageTitle' => 'Add Organisation', 'content' => 'admin/organisation_form', 'org' => null]);
    }

    public function storeOrganisation(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/organisations'); }

        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $type    = $_POST['type'] ?? 'clinic';
        $city    = trim($_POST['city'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $password = 'Org@123456';

        if (!$name || !$email) { flash('error', 'Name and email are required.'); redirect('admin/organisations/create'); }

        $userId = Database::insert(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "organisation")',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
        );

        Database::insert(
            'INSERT INTO organisations (user_id, name, type, city, phone, email) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $name, $type, $city, $phone, $email]
        );

        flash('success', 'Organisation added. Default password: Org@123456');
        redirect('admin/organisations');
    }

    public function appointments(): void
    {
        $status = $_GET['status'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        $where  = $status ? 'WHERE a.status = ?' : 'WHERE 1';
        $params = $status ? [$status] : [];

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM appointments a $where", $params)['c'],
            20, $page
        );

        $appointments = Database::query(
            "SELECT a.*, u.name AS patient_name, du.name AS doctor_name, o.name AS org_name
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             LEFT JOIN organisations o ON a.organisation_id = o.id
             $where ORDER BY a.appointment_date DESC, a.appointment_time DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        view('layouts/app', [
            'pageTitle'    => 'All Appointments',
            'content'      => 'admin/appointments',
            'appointments' => $appointments,
            'paging'       => $paging,
            'status'       => $status,
        ]);
    }

    public function updateAppointment(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/appointments'); }
        $id     = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['pending','confirmed','completed','cancelled'])) {
            Database::execute('UPDATE appointments SET status = ? WHERE id = ?', [$status, $id]);
            $appt = Database::queryOne('SELECT patient_id FROM appointments WHERE id = ?', [$id]);
            if ($appt && in_array($status, ['confirmed','completed','cancelled'])) {
                $msgs = [
                    'confirmed'  => 'Your appointment has been confirmed.',
                    'completed'  => 'Your appointment is marked as completed.',
                    'cancelled'  => 'Your appointment has been cancelled.',
                ];
                notify($appt['patient_id'], 'appointment', 'Appointment ' . ucfirst($status),
                    $msgs[$status], 'user/appointments');
            }
            flash('success', 'Appointment status updated.');
        }
        redirect('admin/appointments');
    }

    public function users(): void
    {
        $search = trim($_GET['search'] ?? '');
        $role   = $_GET['role'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        $where  = 'WHERE (u.name LIKE ? OR u.email LIKE ?)';
        $params = ["%$search%", "%$search%"];
        if ($role) { $where .= ' AND u.role = ?'; $params[] = $role; }

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM users u $where", $params)['c'],
            20, $page
        );

        $users = Database::query(
            "SELECT u.* FROM users u $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        view('layouts/app', [
            'pageTitle' => 'All Users',
            'content'   => 'admin/users',
            'users'     => $users,
            'paging'    => $paging,
            'search'    => $search,
            'roleFilter'=> $role,
        ]);
    }

    public function toggleUser(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/users'); }
        $id = (int) ($_POST['id'] ?? 0);
        Database::execute('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != "admin"', [$id]);
        flash('success', 'User status updated.');
        redirect('admin/users');
    }

    public function reports(): void
    {
        $range = (int) ($_GET['range'] ?? 12); // months back
        if (!in_array($range, [1, 3, 6, 12, 24])) $range = 12;

        $totalAppointments = Database::queryOne('SELECT COUNT(*) as c FROM appointments')['c'];
        $totalRevenue = Database::queryOne(
            'SELECT COALESCE(SUM(d.consultation_fee),0) as r
             FROM appointments a JOIN doctors d ON a.doctor_id = d.id
             WHERE a.status = "completed"'
        )['r'];
        $monthRevenue = Database::queryOne(
            'SELECT COALESCE(SUM(d.consultation_fee),0) as r
             FROM appointments a JOIN doctors d ON a.doctor_id = d.id
             WHERE a.status = "completed" AND MONTH(a.appointment_date) = MONTH(CURDATE()) AND YEAR(a.appointment_date) = YEAR(CURDATE())'
        )['r'];
        $completedCount = Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE status = "completed"')['c'];

        $byStatus = Database::query(
            'SELECT status, COUNT(*) as total FROM appointments GROUP BY status'
        );
        $topDoctors = Database::query(
            'SELECT du.name, COUNT(a.id) as total, COALESCE(SUM(d.consultation_fee),0) as revenue
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             WHERE a.status = "completed"
             GROUP BY a.doctor_id ORDER BY total DESC LIMIT 5'
        );
        $monthly = Database::query(
            'SELECT DATE_FORMAT(appointment_date, "%Y-%m") as month, COUNT(*) as total,
                    SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed
             FROM appointments
             WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC',
            [$range]
        );

        // Build zero-filled month array for chart
        $monthLabels = [];
        $monthData   = [];
        $monthCompleted = [];
        $monthMap    = [];
        foreach ($monthly as $m) {
            $monthMap[$m['month']] = $m;
        }
        for ($i = $range - 1; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $monthLabels[]    = date('M Y', strtotime("-$i months"));
            $monthData[]      = (int) ($monthMap[$key]['total'] ?? 0);
            $monthCompleted[] = (int) ($monthMap[$key]['completed'] ?? 0);
        }

        view('layouts/app', [
            'pageTitle'         => 'Reports & Analytics',
            'content'           => 'admin/reports',
            'totalAppointments' => $totalAppointments,
            'totalRevenue'      => $totalRevenue,
            'monthRevenue'      => $monthRevenue,
            'completedCount'    => $completedCount,
            'byStatus'          => $byStatus,
            'topDoctors'        => $topDoctors,
            'monthly'           => $monthly,
            'monthLabels'       => json_encode($monthLabels),
            'monthData'         => json_encode($monthData),
            'monthCompleted'    => json_encode($monthCompleted),
            'range'             => $range,
        ]);
    }

    public function dispensary(): void
    {
        $stats = [
            'medicines'  => Database::queryOne('SELECT COUNT(*) as c FROM medicines WHERE is_active = 1')['c'],
            'dispensed'  => Database::queryOne('SELECT COUNT(*) as c FROM dispensings')['c'],
            'low_stock'  => Database::queryOne('SELECT COUNT(*) as c FROM medicines WHERE stock_qty <= reorder_level AND is_active = 1')['c'],
        ];

        $lowStock = Database::query(
            'SELECT m.*, o.name AS org_name FROM medicines m
             JOIN organisations o ON m.organisation_id = o.id
             WHERE m.stock_qty <= m.reorder_level AND m.is_active = 1
             ORDER BY m.stock_qty ASC LIMIT 15'
        );

        $recentDispensings = Database::query(
            'SELECT d.*, u.name AS patient_name, o.name AS org_name, du.name AS dispensed_by_name
             FROM dispensings d
             JOIN users u  ON d.patient_id   = u.id
             JOIN users du ON d.dispensed_by  = du.id
             JOIN organisations o ON d.organisation_id = o.id
             ORDER BY d.created_at DESC LIMIT 10'
        );

        view('layouts/app', [
            'pageTitle'         => 'Dispensary Overview',
            'content'           => 'admin/dispensary',
            'stats'             => $stats,
            'lowStock'          => $lowStock,
            'recentDispensings' => $recentDispensings,
        ]);
    }

    public function approvals(): void
    {
        $pending = Database::query(
            'SELECT u.*, d.speciality, o.name AS org_name
             FROM users u
             LEFT JOIN doctors d ON d.user_id = u.id
             LEFT JOIN organisations o ON o.user_id = u.id
             WHERE u.approved = 0 AND u.role IN ("medic","organisation")
             ORDER BY u.created_at DESC'
        );

        view('layouts/app', [
            'pageTitle' => 'Pending Approvals',
            'content'   => 'admin/approvals',
            'pending'   => $pending,
        ]);
    }

    public function approvalAction(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/approvals'); }
        $id     = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($action === 'approve') {
            Database::execute('UPDATE users SET approved = 1, is_active = 1 WHERE id = ?', [$id]);
            notify($id, 'approval', 'Account Approved',
                'Your account has been approved. You can now log in to EbizMedic.', 'login');
            flash('success', 'Account approved. The user can now log in.');
        } elseif ($action === 'reject') {
            Database::execute('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
            flash('success', 'Account rejected.');
        }
        redirect('admin/approvals');
    }

    public function settings(): void
    {
        view('layouts/app', ['pageTitle' => 'Settings', 'content' => 'admin/settings']);
    }

    public function updateSettings(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('admin/settings'); }
        flash('success', 'Settings saved.');
        redirect('admin/settings');
    }
}
