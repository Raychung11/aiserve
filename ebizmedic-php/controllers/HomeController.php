<?php

class HomeController
{
    public function index(): void
    {
        $featuredDoctors = Database::query(
            'SELECT d.*, u.name, u.avatar FROM doctors d
             JOIN users u ON d.user_id = u.id
             WHERE d.is_active = 1 ORDER BY d.created_at DESC LIMIT 6'
        );

        $stats = [
            'doctors'       => Database::queryOne('SELECT COUNT(*) as c FROM doctors WHERE is_active = 1')['c'],
            'organisations' => Database::queryOne('SELECT COUNT(*) as c FROM organisations WHERE is_active = 1')['c'],
            'appointments'  => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE status = "completed"')['c'],
        ];

        view('layouts/public', [
            'pageTitle'       => 'eBizMedic — Healthcare at Your Fingertips',
            'content'         => 'home/index',
            'featuredDoctors' => $featuredDoctors,
            'stats'           => $stats,
        ]);
    }

    public function doctors(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $speciality = trim($_GET['speciality'] ?? '');
        $type       = $_GET['type'] ?? '';
        $page       = max(1, (int) ($_GET['page'] ?? 1));

        $conditions = ['d.is_active = 1'];
        $params     = [];

        if ($search) {
            $conditions[] = '(u.name LIKE ? OR d.speciality LIKE ? OR d.bio LIKE ?)';
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($speciality) {
            $conditions[] = 'd.speciality = ?';
            $params[] = $speciality;
        }
        if ($type === 'online')  { $conditions[] = 'd.is_available_online = 1'; }
        if ($type === 'onsite')  { $conditions[] = 'd.is_available_onsite = 1'; }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM doctors d JOIN users u ON d.user_id = u.id $where", $params)['c'],
            12, $page
        );

        $doctors = Database::query(
            "SELECT d.*, u.name, u.avatar, o.name AS org_name, o.city
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN organisations o ON d.organisation_id = o.id
             $where ORDER BY d.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        $specialities = Database::query(
            'SELECT DISTINCT speciality FROM doctors WHERE speciality IS NOT NULL AND speciality != "" ORDER BY speciality'
        );

        view('layouts/public', [
            'pageTitle'   => 'Find a Doctor — eBizMedic',
            'content'     => 'home/doctors',
            'doctors'     => $doctors,
            'paging'      => $paging,
            'specialities'=> $specialities,
            'search'      => $search,
            'speciality'  => $speciality,
            'type'        => $type,
        ]);
    }

    public function showDoctor(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $doctor = Database::queryOne(
            'SELECT d.*, u.name, u.avatar, o.name AS org_name, o.city, o.address, o.phone AS org_phone
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN organisations o ON d.organisation_id = o.id
             WHERE d.id = ? AND d.is_active = 1',
            [$id]
        );

        if (!$doctor) { flash('error', 'Doctor not found.'); redirect('doctors'); }

        $schedules = Database::query(
            'SELECT * FROM schedules WHERE doctor_id = ? AND is_available = 1 ORDER BY FIELD(day_of_week,"monday","tuesday","wednesday","thursday","friday","saturday","sunday")',
            [$id]
        );

        $services = $doctor['organisation_id']
            ? Database::query('SELECT * FROM services WHERE organisation_id = ? AND is_active = 1', [$doctor['organisation_id']])
            : [];

        view('layouts/public', [
            'pageTitle' => $doctor['name'] . ' — eBizMedic',
            'content'   => 'home/doctor_detail',
            'doctor'    => $doctor,
            'schedules' => $schedules,
            'services'  => $services,
        ]);
    }

    public function booking(): void
    {
        Auth::require();

        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        $doctor = Database::queryOne(
            'SELECT d.*, u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.is_active = 1',
            [$doctorId]
        );
        if (!$doctor) { flash('error', 'Doctor not found.'); redirect('doctors'); }

        $services = $doctor['organisation_id']
            ? Database::query('SELECT * FROM services WHERE organisation_id = ? AND is_active = 1', [$doctor['organisation_id']])
            : [];

        $schedules = Database::query(
            'SELECT * FROM schedules WHERE doctor_id = ? AND is_available = 1',
            [$doctorId]
        );

        view('layouts/public', [
            'pageTitle' => 'Book Appointment — eBizMedic',
            'content'   => 'home/booking',
            'doctor'    => $doctor,
            'services'  => $services,
            'schedules' => $schedules,
        ]);
    }

    public function storeBooking(): void
    {
        Auth::require();
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('doctors'); }

        $doctorId  = (int) ($_POST['doctor_id'] ?? 0);
        $serviceId = $_POST['service_id'] ? (int) $_POST['service_id'] : null;
        $date      = $_POST['appointment_date'] ?? '';
        $time      = $_POST['appointment_time'] ?? '';
        $type      = in_array($_POST['type'] ?? '', ['online','onsite']) ? $_POST['type'] : 'onsite';
        $notes     = trim($_POST['notes'] ?? '');
        $patientId = Auth::id();

        if (!$doctorId || !$date || !$time) {
            flash('error', 'Please fill in all required fields.');
            redirect("booking?doctor_id=$doctorId");
        }

        $doctor = Database::queryOne('SELECT organisation_id FROM doctors WHERE id = ?', [$doctorId]);

        Database::insert(
            'INSERT INTO appointments (patient_id, doctor_id, service_id, organisation_id, appointment_date, appointment_time, type, notes)
             VALUES (?,?,?,?,?,?,?,?)',
            [$patientId, $doctorId, $serviceId, $doctor['organisation_id'], $date, $time, $type, $notes]
        );

        flash('success', 'Appointment booked successfully! The doctor will confirm shortly.');
        redirect('user/appointments');
    }
}
