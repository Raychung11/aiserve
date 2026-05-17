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
        foreach ([
            'notifications','ratings','health_profiles',
            'dispensing_items','dispensings','stock_movements','medicines',
            'pharmacists','appointments','medical_records','schedules',
            'services','doctors','organisations','users'
        ] as $t) {
            $pdo->exec("TRUNCATE TABLE $t");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // ── Passwords ─────────────────────────────────────────────────────
        $adminPass   = password_hash('Admin@123',   PASSWORD_BCRYPT);
        $orgPass     = password_hash('Org@123456',  PASSWORD_BCRYPT);
        $docPass     = password_hash('Doctor@123',  PASSWORD_BCRYPT);
        $userPass    = password_hash('User@123',    PASSWORD_BCRYPT);
        $pharmaPass  = password_hash('Pharma@123',  PASSWORD_BCRYPT);

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

            // Pharmacists
            ['Nurul Aina',               'pharma@kliniksehat.com',     $pharmaPass,'pharmacist',   '+60 11-222 3333'],
            ['Rajan Pillai',             'pharma@hospitalprima.com',   $pharmaPass,'pharmacist',   '+60 11-444 5555'],

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
        $apptIds = [];
        foreach ($appointments as $appt) {
            $stmt->execute($appt);
            $apptIds[] = (int) $pdo->lastInsertId();
        }

        // ══════════════════════════════════════════════════════════════════
        // PHARMACISTS
        // ══════════════════════════════════════════════════════════════════
        $pdo->prepare('INSERT INTO pharmacists (user_id, organisation_id) VALUES (?,?)')
            ->execute([$userIds['pharma@kliniksehat.com'], $ksId]);
        $pdo->prepare('INSERT INTO pharmacists (user_id, organisation_id) VALUES (?,?)')
            ->execute([$userIds['pharma@hospitalprima.com'], $hpId]);

        $pharmaKsId = $userIds['pharma@kliniksehat.com'];
        $pharmaHpId = $userIds['pharma@hospitalprima.com'];

        // ══════════════════════════════════════════════════════════════════
        // MEDICINES
        // ══════════════════════════════════════════════════════════════════
        $medicines = [
            // Klinik Sehat KL medicines
            [$ksId, 'Paracetamol 500mg',      'Acetaminophen',      'Analgesic',    'tablet',    200, 30,  0.15,  'For fever and mild to moderate pain relief.'],
            [$ksId, 'Ibuprofen 400mg',         'Ibuprofen',          'NSAID',        'tablet',    150, 30,  0.30,  'Anti-inflammatory, antipyretic, analgesic.'],
            [$ksId, 'Amoxicillin 500mg',       'Amoxicillin',        'Antibiotic',   'capsule',   100, 20,  0.80,  'Broad-spectrum penicillin antibiotic.'],
            [$ksId, 'Cetirizine 10mg',         'Cetirizine HCl',     'Antihistamine','tablet',    120, 20,  0.25,  'For allergy relief and hay fever.'],
            [$ksId, 'Omeprazole 20mg',         'Omeprazole',         'PPI',          'capsule',   80,  15,  1.20,  'Proton pump inhibitor for acid reflux.'],
            [$ksId, 'Hydrocortisone Cream 1%', null,                 'Topical',      'tube',      30,  5,   8.50,  'For skin inflammation and eczema.'],
            [$ksId, 'Salbutamol Inhaler',      'Albuterol',          'Bronchodilator','inhaler',  15,  3,   18.00, 'Relieves bronchospasm in asthma.'],
            [$ksId, 'Atorvastatin 20mg',       'Atorvastatin',       'Statin',       'tablet',    60,  10,  1.50,  'Reduces LDL cholesterol levels.'],
            [$ksId, 'Metformin 500mg',         'Metformin HCl',      'Antidiabetic', 'tablet',    5,   10,  0.20,  'First-line therapy for type 2 diabetes.'],

            // Hospital Prima Penang medicines
            [$hpId, 'Paracetamol 500mg',      'Acetaminophen',      'Analgesic',    'tablet',    300, 50,  0.15,  'For fever and mild to moderate pain relief.'],
            [$hpId, 'Azithromycin 250mg',      'Azithromycin',       'Antibiotic',   'tablet',    80,  15,  2.50,  'Macrolide antibiotic for respiratory infections.'],
            [$hpId, 'Loratadine 10mg',         'Loratadine',         'Antihistamine','tablet',    100, 20,  0.35,  'Non-drowsy antihistamine for allergies.'],
            [$hpId, 'Prednisolone 5mg',        'Prednisolone',       'Corticosteroid','tablet',   60,  10,  0.50,  'For inflammatory and autoimmune conditions.'],
            [$hpId, 'Amlodipine 5mg',          'Amlodipine',         'Antihypertensive','tablet', 80,  15,  0.80,  'Calcium channel blocker for high blood pressure.'],
            [$hpId, 'ORS Sachets',             'Oral Rehydration',   'Electrolyte',  'sachet',    50,  10,  1.00,  'Oral rehydration salts for dehydration.'],
            [$hpId, 'Tramadol 50mg',           'Tramadol HCl',       'Analgesic',    'tablet',    8,   5,   2.00,  'Opioid pain reliever for moderate-severe pain.'],
        ];

        $medIds = [];
        $stmtMed = $pdo->prepare(
            'INSERT INTO medicines (organisation_id,name,generic_name,category,unit,stock_qty,reorder_level,unit_price,description)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmtMov = $pdo->prepare(
            'INSERT INTO stock_movements (medicine_id,type,quantity,reference,created_by) VALUES (?,?,?,?,?)'
        );
        foreach ($medicines as $m) {
            $stmtMed->execute($m);
            $medId = (int) $pdo->lastInsertId();
            $medIds[] = ['id' => $medId, 'org_id' => $m[0], 'price' => $m[7], 'unit' => $m[4]];
            if ($m[5] > 0) {
                $stmtMov->execute([$medId, 'in', $m[5], 'Initial stock', $m[0] === $ksId ? $pharmaKsId : $pharmaHpId]);
            }
        }

        // Key medicines by org for dispensing samples
        $ksMeds = array_values(array_filter($medIds, fn($m) => $m['org_id'] === $ksId));
        $hpMeds = array_values(array_filter($medIds, fn($m) => $m['org_id'] === $hpId));

        // ══════════════════════════════════════════════════════════════════
        // MEDICAL RECORDS (for the 5 completed appointments)
        // ══════════════════════════════════════════════════════════════════
        $recordData = [
            // Ali Hassan - Dr Ahmad (Cardiology) - appt index 0
            [$apptIds[0], $drAhmad, $userIds['ali@patient.com'],
             'Chest pain and breathlessness on exertion', 'Stable angina', 'Rest, lifestyle modification, medication',
             "Atorvastatin 20mg 1 tablet nightly\nAspirin 75mg 1 tablet daily",
             date('Y-m-d', strtotime('+3 months')), 'Monitor for any worsening symptoms.'],

            // Siti - Dr Sarah (Dermatology) - appt index 1
            [$apptIds[1], $drSarah, $userIds['siti@patient.com'],
             'Acne breakout on face and back', 'Moderate acne vulgaris', 'Topical retinoid + antibiotic wash',
             "Hydrocortisone Cream 1% apply twice daily on affected areas\nCetirizine 10mg 1 tablet at night",
             date('Y-m-d', strtotime('+6 weeks')), 'Avoid direct sunlight. Use sunscreen daily.'],

            // Raj - Dr Hafiz (GP) - appt index 2
            [$apptIds[2], $drHafiz, $userIds['raj@patient.com'],
             'High blood pressure, follow-up', 'Hypertension Stage 1', 'Lifestyle modification, medication adjustment',
             "Amlodipine 5mg 1 tablet daily in the morning\nORS Sachets 1 sachet as needed for hydration",
             date('Y-m-d', strtotime('+1 month')), 'Check BP daily. Reduce salt intake.'],

            // Lim - Dr Priya (Paediatrics) - appt index 3
            [$apptIds[3], $drPriya, $userIds['lim@patient.com'],
             'Child annual health check-up, 5 years old', 'Healthy child, mild seasonal allergy', 'Dietary advice, allergy management',
             "Loratadine 10mg 0.5 tablet daily if sneezing\nParacetamol 500mg use only when fever > 38.5°C",
             date('Y-m-d', strtotime('+6 months')), 'Growth and development on track.'],

            // Ali - Dr Sarah (Dermatology) - appt index 4
            [$apptIds[4], $drSarah, $userIds['ali@patient.com'],
             'Eczema flare-up on arms and legs', 'Atopic eczema, moderate', 'Emollient therapy, topical steroid',
             "Hydrocortisone Cream 1% apply twice daily\nCetirizine 10mg 1 tablet at night for itch",
             date('Y-m-d', strtotime('+4 weeks')), 'Moisturise at least twice daily. Avoid harsh soaps.'],
        ];

        $stmtRec = $pdo->prepare(
            'INSERT INTO medical_records (appointment_id,doctor_id,patient_id,chief_complaint,diagnosis,treatment,prescription,follow_up_date,notes)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $recordIds = [];
        foreach ($recordData as $r) {
            $stmtRec->execute($r);
            $recordIds[] = (int) $pdo->lastInsertId();
        }

        // ══════════════════════════════════════════════════════════════════
        // DISPENSINGS (tied to the medical records above)
        // ══════════════════════════════════════════════════════════════════
        $dispensings = [
            // Ali's cardiology record → Klinik Sehat pharmacist
            [
                'patient_id'        => $userIds['ali@patient.com'],
                'doctor_id'         => $drAhmad,
                'organisation_id'   => $ksId,
                'medical_record_id' => $recordIds[0],
                'dispensed_by'      => $pharmaKsId,
                'notes'             => 'Take atorvastatin at the same time each night.',
                'total_amount'      => 0,
                'items'             => [
                    ['med_idx' => 7, 'meds' => $ksMeds, 'qty' => 30, 'dosage' => '1 tablet nightly'],  // Atorvastatin
                    ['med_idx' => 0, 'meds' => $ksMeds, 'qty' => 30, 'dosage' => '1 tablet daily'],    // Paracetamol (as Aspirin placeholder)
                ],
            ],
            // Siti's dermatology record → Klinik Sehat pharmacist
            [
                'patient_id'        => $userIds['siti@patient.com'],
                'doctor_id'         => $drSarah,
                'organisation_id'   => $ksId,
                'medical_record_id' => $recordIds[1],
                'dispensed_by'      => $pharmaKsId,
                'notes'             => 'Apply cream sparingly. Avoid eyes and open wounds.',
                'total_amount'      => 0,
                'items'             => [
                    ['med_idx' => 5, 'meds' => $ksMeds, 'qty' => 2, 'dosage' => 'Apply twice daily'],   // Hydrocortisone
                    ['med_idx' => 3, 'meds' => $ksMeds, 'qty' => 14, 'dosage' => '1 tablet at night'],  // Cetirizine
                ],
            ],
            // Raj's hypertension record → Hospital Prima pharmacist
            [
                'patient_id'        => $userIds['raj@patient.com'],
                'doctor_id'         => $drHafiz,
                'organisation_id'   => $hpId,
                'medical_record_id' => $recordIds[2],
                'dispensed_by'      => $pharmaHpId,
                'notes'             => 'Monitor blood pressure daily and log readings.',
                'total_amount'      => 0,
                'items'             => [
                    ['med_idx' => 4, 'meds' => $hpMeds, 'qty' => 30, 'dosage' => '1 tablet daily morning'],  // Amlodipine
                    ['med_idx' => 5, 'meds' => $hpMeds, 'qty' => 5,  'dosage' => '1 sachet when needed'],    // ORS
                ],
            ],
            // Ali's eczema record → Klinik Sehat pharmacist
            [
                'patient_id'        => $userIds['ali@patient.com'],
                'doctor_id'         => $drSarah,
                'organisation_id'   => $ksId,
                'medical_record_id' => $recordIds[4],
                'dispensed_by'      => $pharmaKsId,
                'notes'             => '',
                'total_amount'      => 0,
                'items'             => [
                    ['med_idx' => 5, 'meds' => $ksMeds, 'qty' => 1, 'dosage' => 'Apply twice daily'],
                    ['med_idx' => 3, 'meds' => $ksMeds, 'qty' => 7, 'dosage' => '1 tablet at night'],
                ],
            ],
        ];

        $stmtDisp = $pdo->prepare(
            'INSERT INTO dispensings (patient_id,doctor_id,organisation_id,medical_record_id,dispensed_by,notes,total_amount)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmtDItem = $pdo->prepare(
            'INSERT INTO dispensing_items (dispensing_id,medicine_id,quantity,unit_price,dosage_instructions) VALUES (?,?,?,?,?)'
        );
        $stmtDeduct = $pdo->prepare('UPDATE medicines SET stock_qty = stock_qty - ? WHERE id = ?');
        $stmtMovOut = $pdo->prepare(
            'INSERT INTO stock_movements (medicine_id,type,quantity,reference,created_by) VALUES (?,?,?,?,?)'
        );

        foreach ($dispensings as $disp) {
            // Calculate total
            $total = 0;
            foreach ($disp['items'] as $lineItem) {
                $med    = $lineItem['meds'][$lineItem['med_idx']] ?? null;
                if (!$med) continue;
                $total += $med['price'] * $lineItem['qty'];
            }

            $stmtDisp->execute([
                $disp['patient_id'], $disp['doctor_id'], $disp['organisation_id'],
                $disp['medical_record_id'], $disp['dispensed_by'], $disp['notes'], $total
            ]);
            $dispId = (int) $pdo->lastInsertId();

            foreach ($disp['items'] as $lineItem) {
                $med = $lineItem['meds'][$lineItem['med_idx']] ?? null;
                if (!$med) continue;
                $stmtDItem->execute([$dispId, $med['id'], $lineItem['qty'], $med['price'], $lineItem['dosage']]);
                $stmtDeduct->execute([$lineItem['qty'], $med['id']]);
                $stmtMovOut->execute([$med['id'], 'out', $lineItem['qty'], 'Dispensing #' . $dispId, $disp['dispensed_by']]);
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // HEALTH PROFILES (Phase 4)
        // ══════════════════════════════════════════════════════════════════
        $healthProfiles = [
            [
                'user_id'                  => $aliId,
                'blood_type'               => 'A+',
                'allergies'                => 'Penicillin',
                'chronic_conditions'       => 'Ischemic Heart Disease, Hypertension',
                'current_medications'      => "Atorvastatin 20mg – 1 tablet nightly\nAspirin 75mg – 1 tablet daily",
                'emergency_contact_name'   => 'Zainab Hassan',
                'emergency_contact_phone'  => '+60 12-987 6543',
            ],
            [
                'user_id'                  => $sitiId,
                'blood_type'               => 'B+',
                'allergies'                => '',
                'chronic_conditions'       => '',
                'current_medications'      => '',
                'emergency_contact_name'   => 'Ahmad Razali',
                'emergency_contact_phone'  => '+60 13-111 2222',
            ],
            [
                'user_id'                  => $rajId,
                'blood_type'               => 'O+',
                'allergies'                => 'Sulfonamides (Sulfa drugs)',
                'chronic_conditions'       => 'Hypertension Stage 1',
                'current_medications'      => 'Amlodipine 5mg – 1 tablet daily morning',
                'emergency_contact_name'   => 'Kavitha Kumar',
                'emergency_contact_phone'  => '+60 14-333 4444',
            ],
            [
                'user_id'                  => $limId,
                'blood_type'               => 'AB+',
                'allergies'                => 'Peanuts',
                'chronic_conditions'       => '',
                'current_medications'      => '',
                'emergency_contact_name'   => 'Lim Boon Huat',
                'emergency_contact_phone'  => '+60 15-555 6666',
            ],
        ];

        $stmtHp = $pdo->prepare(
            'INSERT INTO health_profiles (user_id,blood_type,allergies,chronic_conditions,current_medications,emergency_contact_name,emergency_contact_phone)
             VALUES (:user_id,:blood_type,:allergies,:chronic_conditions,:current_medications,:emergency_contact_name,:emergency_contact_phone)'
        );
        foreach ($healthProfiles as $hp) {
            $stmtHp->execute($hp);
        }

        // ══════════════════════════════════════════════════════════════════
        // RATINGS (Phase 4) — rate 4 of the 5 completed appointments
        // Appointment indices: 0=Ali/Ahmad, 1=Siti/Sarah, 2=Raj/Hafiz, 3=Lim/Priya, 4=Ali/Sarah
        // Leave appt index 3 (Lim/Priya) unrated so demo can show the rate button
        // ══════════════════════════════════════════════════════════════════
        $ratingsData = [
            // appt 0: Ali → Dr Ahmad (Cardiology)
            [$apptIds[0], $drAhmad, $aliId,  5, 'Dr. Ahmad was very thorough and explained everything clearly. Highly recommended!'],
            // appt 1: Siti → Dr Sarah (Dermatology)
            [$apptIds[1], $drSarah, $sitiId, 4, 'Professional and caring. The waiting time was a bit long but the consultation was great.'],
            // appt 2: Raj → Dr Hafiz (GP)
            [$apptIds[2], $drHafiz, $rajId,  5, 'Very helpful and reassuring. Dr. Hafiz took time to listen and gave practical advice.'],
            // appt 4: Ali → Dr Sarah (eczema, online)
            [$apptIds[4], $drSarah, $aliId,  4, 'Good online session, easy to connect and the advice was clear. Follow-up plan is helpful.'],
        ];

        $stmtRat = $pdo->prepare(
            'INSERT INTO ratings (appointment_id,doctor_id,patient_id,rating,comment) VALUES (?,?,?,?,?)'
        );
        foreach ($ratingsData as $r) {
            $stmtRat->execute($r);
        }

        // ══════════════════════════════════════════════════════════════════
        // NOTIFICATIONS (Phase 4)
        // ══════════════════════════════════════════════════════════════════
        $notificationsData = [
            // Ali
            [$aliId,  'appointment', 'Appointment Confirmed',  'Your appointment with Dr. Ahmad Razif on ' . date('d M Y', strtotime('-20 days')) . ' has been confirmed.', 'user/appointments'],
            [$aliId,  'record',      'Medical Record Ready',   'Your medical record from Dr. Ahmad Razif is now available.',  'user/records'],
            [$aliId,  'dispensing',  'Medicines Dispensed',    'Your prescription has been dispensed by Klinik Sehat KL. Check your medicines history.', 'user/dispensary'],
            [$aliId,  'appointment', 'Appointment Confirmed',  'Your online appointment with Dr. Sarah Lee on ' . date('d M Y', strtotime('-5 days')) . ' has been confirmed.', 'user/appointments'],
            [$aliId,  'record',      'Medical Record Ready',   'Your medical record from Dr. Sarah Lee (eczema) is now available.',  'user/records'],

            // Siti
            [$sitiId, 'appointment', 'Appointment Confirmed',  'Your appointment with Dr. Sarah Lee on ' . date('d M Y', strtotime('-15 days')) . ' has been confirmed.', 'user/appointments'],
            [$sitiId, 'record',      'Medical Record Ready',   'Your medical record from Dr. Sarah Lee is now available.',  'user/records'],
            [$sitiId, 'dispensing',  'Medicines Dispensed',    'Your prescription has been dispensed by Klinik Sehat KL.',  'user/dispensary'],
            [$sitiId, 'appointment', 'Upcoming Appointment',   'Reminder: You have an appointment with Dr. Ahmad Razif on ' . date('d M Y', strtotime('+2 days')) . '.', 'user/appointments'],

            // Raj
            [$rajId,  'appointment', 'Appointment Confirmed',  'Your appointment with Dr. Mohd Hafiz on ' . date('d M Y', strtotime('-10 days')) . ' has been confirmed.', 'user/appointments'],
            [$rajId,  'record',      'Medical Record Ready',   'Your medical record from Dr. Mohd Hafiz is now available.', 'user/records'],
            [$rajId,  'dispensing',  'Medicines Dispensed',    'Your prescription has been dispensed by Hospital Prima Penang.', 'user/dispensary'],

            // Lim
            [$limId,  'appointment', 'Appointment Confirmed',  'Your appointment with Dr. Priya Nair on ' . date('d M Y', strtotime('-8 days')) . ' has been confirmed.', 'user/appointments'],
            [$limId,  'record',      'Medical Record Ready',   'Your child\'s medical record from Dr. Priya Nair is now available.', 'user/records'],
            [$limId,  'appointment', 'Upcoming Appointment',   'Reminder: You have an online appointment with Dr. Sarah Lee on ' . date('d M Y', strtotime('+5 days')) . '.', 'user/appointments'],
        ];

        $stmtNotif = $pdo->prepare(
            'INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)'
        );
        foreach ($notificationsData as $notif) {
            $stmtNotif->execute($notif);
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

            <!-- Pharmacists -->
            <div>
                <h3 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Pharmacists</h3>
                <div class="bg-teal-50 border border-teal-100 rounded-xl p-4 font-mono text-sm space-y-1.5">
                    <p><strong>pharma@kliniksehat.com</strong> — Nurul Aina (Klinik Sehat KL)</p>
                    <p><strong>pharma@hospitalprima.com</strong> — Rajan Pillai (Hospital Prima)</p>
                    <p class="text-gray-500 mt-2">All pharmacists password: <strong>Pharma@123</strong></p>
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
            <li>✅ 2 Pharmacists (one per organisation)</li>
            <li>✅ 4 Patient accounts</li>
            <li>✅ 10 Services across both organisations</li>
            <li>✅ Weekly schedules for all 5 doctors</li>
            <li>✅ 13 Appointments (completed, confirmed, pending, cancelled)</li>
            <li>✅ 16 Medicines across both organisations</li>
            <li>✅ 5 Medical records for completed appointments</li>
            <li>✅ 4 Sample dispensings with line items and stock movements</li>
            <li>✅ 4 Patient health profiles (blood type, allergies, conditions)</li>
            <li>✅ 4 Doctor ratings from patients (1 unrated for demo)</li>
            <li>✅ 15 Sample notifications across all patients</li>
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
