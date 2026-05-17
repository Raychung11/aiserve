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

    public function updatePhoto(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('user/profile'); }
        $path = uploadPhoto('photo', 'users');
        if ($path) {
            Database::execute('UPDATE users SET avatar = ? WHERE id = ?', [$path, Auth::id()]);
            flash('success', 'Photo updated.');
        }
        redirect('user/profile');
    }

    public function changePassword(): void
    {
        if (!csrf_verify()) { flash('error', 'Invalid request.'); redirect('user/profile'); }

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = Database::queryOne('SELECT password FROM users WHERE id = ?', [Auth::id()]);
        if (!password_verify($current, $user['password'])) {
            flash('error', 'Current password is incorrect.'); redirect('user/profile');
        }
        if (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.'); redirect('user/profile');
        }
        if ($new !== $confirm) {
            flash('error', 'Passwords do not match.'); redirect('user/profile');
        }

        Database::execute('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_BCRYPT), Auth::id()]);
        flash('success', 'Password changed successfully.');
        redirect('user/profile');
    }

    public function dispensary(): void
    {
        $userId    = Auth::id();
        $dispensings = Database::query(
            'SELECT d.*, o.name AS org_name, du.name AS dispensed_by_name
             FROM dispensings d
             JOIN organisations o ON d.organisation_id = o.id
             JOIN users du        ON d.dispensed_by     = du.id
             WHERE d.patient_id = ?
             ORDER BY d.created_at DESC',
            [$userId]
        );

        // Fetch items for each dispensing
        foreach ($dispensings as &$disp) {
            $disp['items'] = Database::query(
                'SELECT di.*, m.name AS medicine_name, m.unit FROM dispensing_items di
                 JOIN medicines m ON di.medicine_id = m.id
                 WHERE di.dispensing_id = ?',
                [$disp['id']]
            );
        }

        view('layouts/app', [
            'pageTitle'   => 'My Dispensing History',
            'content'     => 'user/dispensary',
            'dispensings' => $dispensings,
        ]);
    }

    public function records(): void
    {
        $userId  = Auth::id();
        $records = Database::query(
            'SELECT mr.*, du.name AS doctor_name, d.speciality,
                    a.appointment_date, a.appointment_time, a.type
             FROM medical_records mr
             JOIN doctors d ON mr.doctor_id = d.id
             JOIN users du ON d.user_id = du.id
             JOIN appointments a ON mr.appointment_id = a.id
             WHERE mr.patient_id = ?
             ORDER BY mr.created_at DESC',
            [$userId]
        );

        view('layouts/app', [
            'pageTitle' => 'My Medical Records',
            'content'   => 'user/records',
            'records'   => $records,
        ]);
    }
}
