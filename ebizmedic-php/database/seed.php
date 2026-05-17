<?php
/**
 * eBizMedic Demo Seeder
 * Run once: visit yourdomain.com/database/seed.php
 * DELETE this file after seeding for security.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$done   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    try {
        $pdo = Database::connect();

        // ── Wipe existing data (FK-safe order) ───────────────────────────
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['appointments','schedules','services','doctors','organisations','users'] as $t) {
            $pdo->exec("TRUNCATE TABLE $t");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // ── Passwords ─────────────────────────────────────────────────────
        $adminPass = password_hash('Admin@123',   PASSWORD_BCRYPT);
        $orgPass   = password_hash('Org@123456',  PASSWORD_BCRYPT);
        $docPass   = password_hash('Doctor@123',  PASSWORD_BCRYPT);
        $userPass  = password_hash('User@123',    PASSWORD_BCRYPT);

        // ══════════════════════════════════════════════════════════════════
        // USERS
        // ══════════════════════════════════════════════════════════════════
        $users = [
            // Admin
            ['Admin eBizMedic',          'admin@ebizmedic.com',        $adminPass, 'admin',        '+60 3-1234 5678'],

            // Organisations
            ['Klinik Sehat KL',          'org@kliniksehat.com',        $orgPass,   'organisation', '+60 3-2222 3333'],
            ['Hospital Prima Penang',     'org@hospitalprima.com',      $orgPass,   'organisation', '+60 4-5555 6666'],

            // Doctors
            ['Dr. Ahmad Razif',          'ahmad.razif@doctor.com',     $docPass,   'medic',        '+60 12-111 2222'],
            ['Dr. Sarah Lee Mei Ling',   'sarah.lee@doctor.com',       $docPass,   'medic',        '+60 12-333 4444'],
            ['Dr. Mohd Hafiz Yusof',     'hafiz.yusof@doctor.com',     $docPass,   'medic',        '+60 11-555 6666'],
            ['Dr. Priya Nair',           'priya.nair@doctor.com',      $docPass,   'medic',        '+60 16-777 8888'],
            ['Dr. James Wong',           'james.wong@doctor.com',      $docPass,   'medic',        '+60 17-999 0000'],

            // Patients
            ['Ali Hassan',               'ali@patient.com',            $userPass,  'user',         '+60 12-123 4567'],
            ['Siti Aminah Binti Razak',  'siti@patient.com',           $userPass,  'user',         '+60 13-234 5678'],
            ['Raj Kumar',                'raj@patient.com',            $userPass,  'user',         '+60 14-345 6789'],
            ['Lim Wei Ting',             'lim@patient.com',            $userPass,  'user',         '+60 15-456 7890'],
        ];

        $userIds = [];
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role,phone,approved) VALUES (?,?,?,?,?,1)');
        foreach ($users as $u) {
            $stmt->execute($u);
            $userIds[$u[1]] = (int) $pdo->lastInsertId();
        }

        // ══════════════════════════════════════════════════════════════════
        // ORGANISATIONS
        // ══════════════════════════════════════════════════════════════════
        $orgs = [
            [
                'user_id'     => $userIds['org@kliniksehat.com'],
                'name'        => 'Klinik Sehat KL',
                'type'        => 'clinic',
                'description' => 'A trusted family clinic in the heart of Kuala Lumpur, serving patients since 2010 with compassionate and affordable care.',
                'address'     => 'No. 12, Jalan Bukit Bintang, 55100',
                'city'        => 'Kuala Lumpur',
                'state'       => 'Kuala Lumpur',
                'phone'       => '+60 3-2222 3333',
                'email'       => 'org@kliniksehat.com',
            ],
            [
                'user_id'     => $userIds['org@hospitalprima.com'],
                'name'        => 'Hospital Prima Penang',
                'type'        => 'hospital',
                'description' => 'A leading private hospital in Penang offering comprehensive medical services with modern facilities and experienced specialists.',
                'address'     => 'No. 88, Jalan Sultan Ahmad Shah, 10050',
                'city'        => 'Penang',
                'state'       => 'Pulau Pinang',
                'phone'       => '+60 4-5555 6666',
                'email'       => 'org@hospitalprima.com',
            ],
        ];

        $orgIds = [];
        $stmt = $pdo->prepare(
            'INSERT INTO organisations (user_id,name,type,description,address,city,state,phone,email)
             VALUES (:user_id,:name,:type,:description,:address,:city,:state,:phone,:email)'
        );
        foreach ($orgs as $o) {
            $stmt->execute($o);
            $orgIds[$o['email']] = (int) $pdo->lastInsertId();
        }

        $ksId = $orgIds['org@kliniksehat.com'];
        $hpId = $orgIds['org@hospitalprima.com'];

        // ══════════════════════════════════════════════════════════════════
        // DOCTORS
        // ══════════════════════════════════════════════════════════════════
        $doctors = [
            [
                'user_id'              => $userIds['ahmad.razif@doctor.com'],
                'organisation_id'      => $ksId,
                'speciality'           => 'Cardiology',
                'qualification'        => 'MBBS (UM), MRCP (UK), Fellowship in Cardiology',
                'experience_years'     => 12,
                'bio'                  => 'Dr. Ahmad Razif is a consultant cardiologist with over 12 years of experience managing heart diseases. He specialises in echocardiography, coronary artery disease, and heart failure management.',
                'consultation_fee'     => 150.00,
                'is_available_online'  => 1,
                'is_available_onsite'  => 1,
            ],
            [
                'user_id'              => $userIds['sarah.lee@doctor.com'],
                'organisation_id'      => $ksId,
                'speciality'           => 'Dermatology',
                'qualification'        => 'MBBS (USM), MMed Dermatology',
                'experience_years'     => 8,
                'bio'                  => 'Dr. Sarah Lee is a certified dermatologist specialising in acne, eczema, psoriasis, and skin cancer screening. She is known for her patient-centred approach and evidence-based treatments.',
                'consultation_fee'     => 120.00,
                'is_available_online'  => 1,
                'is_available_onsite'  => 1,
            ],
            [
                'user_id'              => $userIds['hafiz.yusof@doctor.com'],
                'organisation_id'      => $hpId,
                'speciality'           => 'General Practice',
                'qualification'        => 'MBBS (UKM), Dip. Family Medicine',
                'experience_years'     => 10,
                'bio'                  => 'Dr. Mohd Hafiz is a dedicated general practitioner with a decade of experience in primary healthcare. He provides comprehensive care for adults and children including chronic disease management.',
                'consultation_fee'     => 60.00,
                'is_available_online'  => 0,
                'is_available_onsite'  => 1,
            ],
            [
                'user_id'              => $userIds['priya.nair@doctor.com'],
                'organisation_id'      => $hpId,
                'speciality'           => 'Paediatrics',
                'qualification'        => 'MBBS (IMU), MMed Paediatrics, MRCPCH (UK)',
                'experience_years'     => 9,
                'bio'                  => 'Dr. Priya Nair is a paediatrician passionate about child health and development. She manages common childhood illnesses, growth concerns, vaccinations, and neonatal care.',
                'consultation_fee'     => 90.00,
                'is_available_online'  => 1,
                'is_available_onsite'  => 1,
            ],
            [
                'user_id'              => $userIds['james.wong@doctor.com'],
                'organisation_id'      => null,
                'speciality'           => 'Orthopaedics',
                'qualification'        => 'MBBS (UPM), MS Orthopaedics',
                'experience_years'     => 15,
                'bio'                  => 'Dr. James Wong is an orthopaedic surgeon specialising in joint replacement, sports injuries, and spine disorders. He has performed over 2,000 successful surgeries throughout his career.',
                'consultation_fee'     => 200.00,
                'is_available_online'  => 0,
                'is_available_onsite'  => 1,
            ],
        ];

        $doctorIds = [];
        $stmt = $pdo->prepare(
            'INSERT INTO doctors (user_id,organisation_id,speciality,qualification,experience_years,bio,consultation_fee,is_available_online,is_available_onsite)
             VALUES (:user_id,:organisation_id,:speciality,:qualification,:experience_years,:bio,:consultation_fee,:is_available_online,:is_available_onsite)'
        );
        foreach ($doctors as $d) {
            $stmt->execute($d);
            $doctorIds[$d['user_id']] = (int) $pdo->lastInsertId();
        }

        $drAhmad = $doctorIds[$userIds['ahmad.razif@doctor.com']];
        $drSarah = $doctorIds[$userIds['sarah.lee@doctor.com']];
        $drHafiz = $doctorIds[$userIds['hafiz.yusof@doctor.com']];
        $drPriya = $doctorIds[$userIds['priya.nair@doctor.com']];
        $drJames = $doctorIds[$userIds['james.wong@doctor.com']];

        // ══════════════════════════════════════════════════════════════════
        // SCHEDULES
        // ══════════════════════════════════════════════════════════════════
        $weekdays    = ['monday','tuesday','wednesday','thursday','friday'];
        $weekdaysSat = ['monday','tuesday','wednesday','thursday','friday','saturday'];

        $scheduleGroups = [
            // Dr Ahmad: Mon-Fri 9am-5pm + Sat morning
            $drAhmad => [
                'days'  => $weekdaysSat,
                'start' => ['monday'=>'09:00','tuesday'=>'09:00','wednesday'=>'09:00','thursday'=>'09:00','friday'=>'09:00','saturday'=>'09:00'],
                'end'   => ['monday'=>'17:00','tuesday'=>'17:00','wednesday'=>'17:00','thursday'=>'17:00','friday'=>'17:00','saturday'=>'13:00'],
            ],
            // Dr Sarah: Mon-Thu 10am-6pm, Fri 10am-4pm
            $drSarah => [
                'days'  => ['monday','tuesday','wednesday','thursday','friday'],
                'start' => ['monday'=>'10:00','tuesday'=>'10:00','wednesday'=>'10:00','thursday'=>'10:00','friday'=>'10:00'],
                'end'   => ['monday'=>'18:00','tuesday'=>'18:00','wednesday'=>'18:00','thursday'=>'18:00','friday'=>'16:00'],
            ],
            // Dr Hafiz: Mon-Fri 8am-5pm
            $drHafiz => [
                'days'  => $weekdays,
                'start' => array_fill_keys($weekdays, '08:00'),
                'end'   => array_fill_keys($weekdays, '17:00'),
            ],
            // Dr Priya: Mon-Fri 9am-5pm, Sat 9am-1pm
            $drPriya => [
                'days'  => $weekdaysSat,
                'start' => array_merge(array_fill_keys($weekdays, '09:00'), ['saturday'=>'09:00']),
                'end'   => array_merge(array_fill_keys($weekdays, '17:00'), ['saturday'=>'13:00']),
            ],
            // Dr James: Tue/Thu/Sat only
            $drJames => [
                'days'  => ['tuesday','thursday','saturday'],
                'start' => ['tuesday'=>'08:00','thursday'=>'08:00','saturday'=>'08:00'],
                'end'   => ['tuesday'=>'16:00','thursday'=>'16:00','saturday'=>'12:00'],
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO schedules (doctor_id,day_of_week,start_time,end_time,is_available) VALUES (?,?,?,?,1)'
        );
        foreach ($scheduleGroups as $docId => $sg) {
            foreach ($sg['days'] as $day) {
                $stmt->execute([$docId, $day, $sg['start'][$day], $sg['end'][$day]]);
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SERVICES
        // ══════════════════════════════════════════════════════════════════
        $services = [
            // Klinik Sehat KL
            [$ksId, 'General Consultation',       'Basic health assessment and treatment for common illnesses.',    50.00,  30],
            [$ksId, 'Heart Screening (ECG)',       'Electrocardiogram to detect heart irregularities.',           150.00,  45],
            [$ksId, 'Skin Consultation',           'Assessment and treatment for skin conditions.',               120.00,  30],
            [$ksId, 'Blood Test Panel',            'Comprehensive blood workup including FBC, lipid profile.',     80.00,  30],
            [$ksId, 'Online Consultation',         'Video call consultation with our doctors from anywhere.',      60.00,  20],

            // Hospital Prima Penang
            [$hpId, 'General Consultation',       'General check-up and treatment by our experienced doctors.',   60.00,  30],
            [$hpId, 'Paediatric Check-Up',        'Full health assessment for children from birth to 18 years.',  90.00,  45],
            [$hpId, 'X-Ray Imaging',              'Digital X-ray for chest, limbs, and spinal assessment.',      120.00,  30],
            [$hpId, 'Physiotherapy Session',      'Guided physiotherapy for musculoskeletal rehabilitation.',    100.00,  60],
            [$hpId, 'Orthopaedic Consultation',   'Assessment and management of bone, joint and muscle issues.', 200.00,  45],
        ];

        $serviceIds = [];
        $stmt = $pdo->prepare(
            'INSERT INTO services (organisation_id,name,description,price,duration_minutes) VALUES (?,?,?,?,?)'
        );
        foreach ($services as $svc) {
            $stmt->execute($svc);
            $serviceIds[] = (int) $pdo->lastInsertId();
        }

        // ══════════════════════════════════════════════════════════════════
        // APPOINTMENTS
        // ══════════════════════════════════════════════════════════════════
        $aliId  = $userIds['ali@patient.com'];
        $sitiId = $userIds['siti@patient.com'];
        $rajId  = $userIds['raj@patient.com'];
        $limId  = $userIds['lim@patient.com'];

        $appointments = [
            // Completed appointments (past)
            [$aliId,  $drAhmad, $serviceIds[1], $ksId, date('Y-m-d', strtotime('-20 days')), '09:30', 'onsite',  'completed', 'Chest pain and shortness of breath.'],
            [$sitiId, $drSarah, $serviceIds[2], $ksId, date('Y-m-d', strtotime('-15 days')), '10:00', 'onsite',  'completed', 'Persistent acne breakout on face and back.'],
            [$rajId,  $drHafiz, $serviceIds[5], $hpId, date('Y-m-d', strtotime('-10 days')), '08:30', 'onsite',  'completed', 'Follow-up for high blood pressure.'],
            [$limId,  $drPriya, $serviceIds[6], $hpId, date('Y-m-d', strtotime('-8 days')),  '09:00', 'onsite',  'completed', 'Child annual health check-up, 5 years old.'],
            [$aliId,  $drSarah, $serviceIds[4], $ksId, date('Y-m-d', strtotime('-5 days')),  '11:00', 'online',  'completed', 'Eczema flare-up on arms and legs.'],

            // Confirmed appointments (upcoming)
            [$sitiId, $drAhmad, $serviceIds[1], $ksId, date('Y-m-d', strtotime('+2 days')),  '10:00', 'onsite',  'confirmed', 'Routine heart check-up.'],
            [$rajId,  $drPriya, $serviceIds[6], $hpId, date('Y-m-d', strtotime('+3 days')),  '09:30', 'onsite',  'confirmed', 'Child vaccination and growth assessment.'],
            [$limId,  $drSarah, $serviceIds[4], $ksId, date('Y-m-d', strtotime('+5 days')),  '10:30', 'online',  'confirmed', 'Skin rash on neck and chest.'],

            // Pending appointments
            [$aliId,  $drHafiz, $serviceIds[5], $hpId, date('Y-m-d', strtotime('+7 days')),  '08:00', 'onsite',  'pending',   'General health screening.'],
            [$sitiId, $drPriya, $serviceIds[6], $hpId, date('Y-m-d', strtotime('+8 days')),  '11:00', 'onsite',  'pending',   'My daughter has a fever and cough for 3 days.'],
            [$rajId,  $drJames, null,            null,  date('Y-m-d', strtotime('+10 days')), '08:00', 'onsite',  'pending',   'Knee pain after sports injury last month.'],
            [$limId,  $drAhmad, $serviceIds[4], $ksId, date('Y-m-d', strtotime('+12 days')), '09:00', 'online',  'pending',   'Heart palpitations, feeling anxious.'],

            // Cancelled appointment
            [$aliId,  $drPriya, $serviceIds[6], $hpId, date('Y-m-d', strtotime('-3 days')),  '14:00', 'onsite',  'cancelled', 'Could not attend due to work emergency.'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO appointments (patient_id,doctor_id,service_id,organisation_id,appointment_date,appointment_time,type,status,notes)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        foreach ($appointments as $appt) {
            $stmt->execute($appt);
        }

        $done = true;

    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>eBizMedic — Demo Seeder</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
<div class="w-full max-w-2xl">

    <div class="text-center mb-8">
        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">eBizMedic Demo Seeder</h1>
        <p class="text-gray-500 text-sm mt-1">This will wipe all existing data and load demo records</p>
    </div>

    <?php if ($done): ?>
    <!-- SUCCESS -->
    <div class="bg-white rounded-2xl border border-green-200 shadow-sm p-8">
        <div class="flex items-center gap-3 text-green-600 mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-xl font-bold">Database seeded successfully!</h2>
        </div>

        <div class="space-y-6">
            <!-- Admin -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Admin</h3>
                <div class="bg-red-50 border border-red-100 rounded-xl p-4 font-mono text-sm">
                    <p>Email: <strong>admin@ebizmedic.com</strong></p>
                    <p>Password: <strong>Admin@123</strong></p>
                </div>
            </div>

            <!-- Organisations -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Organisations</h3>
                <div class="space-y-2 font-mono text-sm">
                    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                        <p class="font-bold text-purple-700">Klinik Sehat KL</p>
                        <p>Email: org@kliniksehat.com &nbsp;|&nbsp; Password: <strong>Org@123456</strong></p>
                    </div>
                    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
                        <p class="font-bold text-purple-700">Hospital Prima Penang</p>
                        <p>Email: org@hospitalprima.com &nbsp;|&nbsp; Password: <strong>Org@123456</strong></p>
                    </div>
                </div>
            </div>

            <!-- Doctors -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Doctors</h3>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 font-mono text-sm space-y-1.5">
                    <p><strong>ahmad.razif@doctor.com</strong> — Cardiology (Klinik Sehat)</p>
                    <p><strong>sarah.lee@doctor.com</strong> — Dermatology (Klinik Sehat)</p>
                    <p><strong>hafiz.yusof@doctor.com</strong> — General Practice (Hospital Prima)</p>
                    <p><strong>priya.nair@doctor.com</strong> — Paediatrics (Hospital Prima)</p>
                    <p><strong>james.wong@doctor.com</strong> — Orthopaedics (Independent)</p>
                    <p class="text-gray-500 mt-2">All doctors password: <strong>Doctor@123</strong></p>
                </div>
            </div>

            <!-- Patients -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Patients</h3>
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 font-mono text-sm space-y-1.5">
                    <p><strong>ali@patient.com</strong> — Ali Hassan</p>
                    <p><strong>siti@patient.com</strong> — Siti Aminah</p>
                    <p><strong>raj@patient.com</strong> — Raj Kumar</p>
                    <p><strong>lim@patient.com</strong> — Lim Wei Ting</p>
                    <p class="text-gray-500 mt-2">All patients password: <strong>User@123</strong></p>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-5 border-t border-gray-100 flex gap-3">
            <a href="../index.php" class="px-5 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 font-medium">
                Go to Site
            </a>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-2.5 text-sm text-yellow-700 flex-1">
                ⚠️ <strong>Delete this file</strong> from your server — it clears all data on every run.
            </div>
        </div>
    </div>

    <?php elseif (!empty($errors)): ?>
    <!-- ERRORS -->
    <div class="bg-white rounded-2xl border border-red-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-red-600 mb-3">Error</h2>
        <?php foreach ($errors as $err): ?>
        <p class="text-sm text-red-700 bg-red-50 rounded-lg px-4 py-3 font-mono"><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
        <a href="" class="mt-4 inline-block text-sm text-blue-600 hover:underline">Try again</a>
    </div>

    <?php else: ?>
    <!-- CONFIRM FORM -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-sm text-yellow-800">
            ⚠️ <strong>Warning:</strong> Running this will <strong>delete all existing data</strong> and replace it with demo records. Only run this on a fresh installation.
        </div>

        <h2 class="font-semibold text-gray-900 mb-4">This will create:</h2>
        <ul class="text-sm text-gray-600 space-y-1.5 mb-8">
            <li>✅ 1 Admin account</li>
            <li>✅ 2 Organisations (Klinik Sehat KL, Hospital Prima Penang)</li>
            <li>✅ 5 Doctors (Cardiology, Dermatology, GP, Paediatrics, Orthopaedics)</li>
            <li>✅ 4 Patient accounts</li>
            <li>✅ 10 Services across both organisations</li>
            <li>✅ Weekly schedules for all 5 doctors</li>
            <li>✅ 13 Appointments (completed, confirmed, pending, cancelled)</li>
        </ul>

        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors text-sm">
                Seed Demo Data Now
            </button>
        </form>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
