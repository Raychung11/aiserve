<?php

class UserController
{
    public function __construct()
    {
        Auth::requireRole('user');
    }

    public function dashboard(): void
    {
        $userId = Auth::id();
        $stats = [
            'total'     => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE patient_id = ?', [$userId])['c'],
            'pending'   => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status = "pending"', [$userId])['c'],
            'completed' => Database::queryOne('SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status = "completed"', [$userId])['c'],
        ];

        $recent = Database::query(
            'SELECT a.*, du.name AS doctor_name, d.speciality
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             WHERE a.patient_id = ?
             ORDER BY a.created_at DESC LIMIT 5',
            [$userId]
        );

        view('layouts/app', [
            'pageTitle' => 'My Dashboard',
            'content'   => 'user/dashboard',
            'stats'     => $stats,
            'recent'    => $recent,
        ]);
    }

    public function appointments(): void
    {
        $userId = Auth::id();
        $status = $_GET['status'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        $where  = 'WHERE a.patient_id = ?';
        $params = [$userId];
        if ($status) { $where .= ' AND a.status = ?'; $params[] = $status; }

        $paging = paginate(
            Database::queryOne("SELECT COUNT(*) as c FROM appointments a $where", $params)['c'],
            10, $page
        );

        $appointments = Database::query(
            "SELECT a.*, du.name AS doctor_name, d.speciality, o.name AS org_name
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             LEFT JOIN organisations o ON a.organisation_id = o.id
             $where ORDER BY a.appointment_date DESC, a.appointment_time DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['per_page'], $paging['offset']])
        );

        view('layouts/app', [
            'pageTitle'    => 'My Appointments',
            'content'      => 'user/appointments',
            'appointments' => $appointments,
            'paging'       => $paging,
            'status'       => $status,
        ]);
    }

    public function profile(): void
    {
        $user = Database::queryOne('SELECT * FROM users WHERE id = ?', [Auth::id()]);
        view('layouts/app', [
            'pageTitle' => 'My Profile',
            'content'   => 'user/profile',
            'user'      => $user,
        ]);
    }

    public function updateProfile(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('user/profile'); }

        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (strlen($name) < 2) { flash('error', 'Name is too short.'); redirect('user/profile'); }

        Database::execute('UPDATE users SET name=?, phone=? WHERE id=?', [$name, $phone, Auth::id()]);

        // Update session name
        $_SESSION['user_name'] = $name;

        flash('success', 'Profile updated.');
        redirect('user/profile');
    }
}
