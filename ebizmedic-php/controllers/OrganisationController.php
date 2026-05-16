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
            'pending'      => Database::queryOne(
                'SELECT COUNT(*) as c FROM appointments WHERE organisation_id = ? AND status = "pending"', [$orgId]
            )['c'],
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

        view('layouts/app', [
            'pageTitle'          => 'Organisation Dashboard',
            'content'            => 'organisation/dashboard',
            'org'                => $this->org,
            'stats'              => $stats,
            'recentAppointments' => $recentAppointments,
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
}
