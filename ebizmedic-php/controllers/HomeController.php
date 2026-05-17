<?php

class HomeController
{
    public function index(): void
    {
        $featuredDoctors = Database::query(
            'SELECT d.*, u.name, u.avatar, o.name AS org_name FROM doctors d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN organisations o ON d.organisation_id = o.id
             WHERE d.is_active = 1 ORDER BY d.created_at DESC LIMIT 6'
        );

        $stats = [
            'doctors'       => Database::queryOne('SELECT COUNT(*) as c FROM doctors WHERE is_active = 1')['c'],
            'organisations' => Database::queryOne('SELECT COUNT(*) as c FROM organisations WHERE is_active = 1')['c'],
            'appointments'  => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE status = "completed"')['c'],
        ];

        view('layouts/public', [
            'pageTitle'       => 'EbizMedic — Comprehensive Telehealth Solutions for Every Malaysian',
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
            'SELECT d.*, u.name, u.avatar FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.is_active = 1',
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

        // Build schedule map keyed by day for JS slot picker
        $scheduleMap = [];
        foreach ($schedules as $s) {
            $scheduleMap[$s['day_of_week']] = ['start' => $s['start_time'], 'end' => $s['end_time']];
        }

        // Pre-load booked slots for next 90 days so JS can disable them
        $bookedSlots = Database::query(
            "SELECT appointment_date, appointment_time FROM appointments
             WHERE doctor_id = ? AND status NOT IN ('cancelled')
             AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)",
            [$doctorId]
        );
        $bookedMap = [];
        foreach ($bookedSlots as $b) {
            $bookedMap[$b['appointment_date']][] = substr($b['appointment_time'], 0, 5);
        }

        view('layouts/public', [
            'pageTitle'   => 'Book Appointment — eBizMedic',
            'content'     => 'home/booking',
            'doctor'      => $doctor,
            'services'    => $services,
            'schedules'   => $schedules,
            'scheduleMap' => $scheduleMap,
            'bookedMap'   => $bookedMap,
        ]);
    }

    // AJAX endpoint: return available slots for a doctor+date as JSON
    public function slots(): void
    {
        header('Content-Type: application/json');
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        $date     = $_GET['date'] ?? '';

        if (!$doctorId || !$date) { echo json_encode([]); exit; }

        $dayName  = strtolower(date('l', strtotime($date)));
        $schedule = Database::queryOne(
            'SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1',
            [$doctorId, $dayName]
        );

        if (!$schedule) { echo json_encode([]); exit; }

        $booked = array_column(Database::query(
            "SELECT appointment_time FROM appointments
             WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('cancelled')",
            [$doctorId, $date]
        ), 'appointment_time');

        $booked  = array_map(fn($t) => substr($t, 0, 5), $booked);
        $slots   = [];
        $current = strtotime($date . ' ' . $schedule['start_time']);
        $end     = strtotime($date . ' ' . $schedule['end_time']);

        while ($current < $end) {
            $time   = date('H:i', $current);
            $slots[] = ['time' => $time, 'booked' => in_array($time, $booked)];
            $current += 30 * 60; // 30-min intervals
        }

        echo json_encode($slots);
        exit;
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
