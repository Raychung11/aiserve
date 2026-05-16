<?php

class MedicController
{
    private int $userId;
    private array $doctor;

    public function __construct()
    {
        Auth::requireRole('medic');
        $this->userId = Auth::id();
        $doctor = Database::queryOne(
            'SELECT d.*, u.name, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?',
            [$this->userId]
        );
        if (!$doctor) {
            // Create profile if missing
            Database::insert('INSERT INTO doctors (user_id) VALUES (?)', [$this->userId]);
            $doctor = Database::queryOne(
                'SELECT d.*, u.name, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?',
                [$this->userId]
            );
        }
        $this->doctor = $doctor;
    }

    public function dashboard(): void
    {
        $doctorId = $this->doctor['id'];

        $stats = [
            'today'     => Database::queryOne(
                'SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()', [$doctorId]
            )['c'],
            'pending'   => Database::queryOne(
                'SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ? AND status = "pending"', [$doctorId]
            )['c'],
            'completed' => Database::queryOne(
                'SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ? AND status = "completed"', [$doctorId]
            )['c'],
            'total'     => Database::queryOne(
                'SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ?', [$doctorId]
            )['c'],
        ];

        $upcoming = Database::query(
            'SELECT a.*, u.name AS patient_name, u.phone AS patient_phone
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE() AND a.status != "cancelled"
             ORDER BY a.appointment_date, a.appointment_time LIMIT 10',
            [$doctorId]
        );

        view('layouts/app', [
            'pageTitle' => 'My Dashboard',
            'content'   => 'medic/dashboard',
            'doctor'    => $this->doctor,
            'stats'     => $stats,
            'upcoming'  => $upcoming,
        ]);
    }

    public function appointments(): void
    {
        $doctorId = $this->doctor['id'];
        $status   = $_GET['status'] ?? '';
        $date     = $_GET['date'] ?? '';
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $conditions = ['a.doctor_id = ?'];
        $params     = [$doctorId];

        if ($status) { $conditions[] = 'a.status = ?'; $params[] = $status; }
        if ($date)   { $conditions[] = 'a.appointment_date = ?'; $params[] = $date; }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM appointments a $where", $params)['c'],
            15, $page
        );

        $appointments = Database::query(
            "SELECT a.*, u.name AS patient_name, u.phone AS patient_phone
             FROM appointments a JOIN users u ON a.patient_id = u.id
             $where ORDER BY a.appointment_date DESC, a.appointment_time DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        view('layouts/app', [
            'pageTitle'    => 'My Appointments',
            'content'      => 'medic/appointments',
            'appointments' => $appointments,
            'paging'       => $paging,
            'status'       => $status,
            'date'         => $date,
        ]);
    }

    public function updateAppointment(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('medic/appointments'); }
        $id     = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['confirmed','completed','cancelled'])) {
            Database::execute(
                'UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?',
                [$status, $id, $this->doctor['id']]
            );
            flash('success', 'Appointment updated.');
        }
        redirect('medic/appointments');
    }

    public function schedule(): void
    {
        $doctorId = $this->doctor['id'];
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        $schedules = Database::query(
            'SELECT * FROM schedules WHERE doctor_id = ?', [$doctorId]
        );
        // Key by day
        $byDay = [];
        foreach ($schedules as $s) {
            $byDay[$s['day_of_week']] = $s;
        }

        view('layouts/app', [
            'pageTitle' => 'My Schedule',
            'content'   => 'medic/schedule',
            'days'      => $days,
            'byDay'     => $byDay,
            'doctor'    => $this->doctor,
        ]);
    }

    public function updateSchedule(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('medic/schedule'); }
        $doctorId = $this->doctor['id'];
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        foreach ($days as $day) {
            $isAvailable = isset($_POST['available'][$day]) ? 1 : 0;
            $start = $_POST['start'][$day] ?? '08:00';
            $end   = $_POST['end'][$day]   ?? '17:00';

            $existing = Database::queryOne(
                'SELECT id FROM schedules WHERE doctor_id = ? AND day_of_week = ?', [$doctorId, $day]
            );

            if ($existing) {
                Database::execute(
                    'UPDATE schedules SET start_time=?, end_time=?, is_available=? WHERE doctor_id=? AND day_of_week=?',
                    [$start, $end, $isAvailable, $doctorId, $day]
                );
            } else {
                Database::insert(
                    'INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?,?,?,?,?)',
                    [$doctorId, $day, $start, $end, $isAvailable]
                );
            }
        }

        flash('success', 'Schedule updated successfully.');
        redirect('medic/schedule');
    }

    public function profile(): void
    {
        view('layouts/app', [
            'pageTitle' => 'My Profile',
            'content'   => 'medic/profile',
            'doctor'    => $this->doctor,
        ]);
    }

    public function updateProfile(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('medic/profile'); }

        $speciality  = trim($_POST['speciality'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $experience  = (int) ($_POST['experience_years'] ?? 0);
        $bio         = trim($_POST['bio'] ?? '');
        $fee         = (float) ($_POST['consultation_fee'] ?? 0);
        $online      = isset($_POST['is_available_online']) ? 1 : 0;
        $onsite      = isset($_POST['is_available_onsite']) ? 1 : 0;
        $name        = trim($_POST['name'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');

        Database::execute(
            'UPDATE users SET name=?, phone=? WHERE id=?',
            [$name, $phone, $this->userId]
        );

        Database::execute(
            'UPDATE doctors SET speciality=?, qualification=?, experience_years=?, bio=?, consultation_fee=?, is_available_online=?, is_available_onsite=? WHERE user_id=?',
            [$speciality, $qualification, $experience, $bio, $fee, $online, $onsite, $this->userId]
        );

        flash('success', 'Profile updated.');
        redirect('medic/profile');
    }
}
