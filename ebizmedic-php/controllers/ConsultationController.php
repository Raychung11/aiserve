<?php

class ConsultationController
{
    public function __construct()
    {
        Auth::require();
    }

    // ── Shared helpers ────────────────────────────────────────────────────

    private function loadAppointment(int $id): ?array
    {
        $appt = Database::queryOne(
            'SELECT a.*, u.name AS patient_name, u.phone AS patient_phone,
                    du.name AS doctor_name, d.speciality, d.user_id AS doctor_user_id,
                    o.name AS org_name
             FROM appointments a
             JOIN users u    ON a.patient_id  = u.id
             JOIN doctors d  ON a.doctor_id   = d.id
             JOIN users du   ON d.user_id     = du.id
             LEFT JOIN organisations o ON a.organisation_id = o.id
             WHERE a.id = ? AND a.type = "online"',
            [$id]
        );
        if (!$appt) return null;

        $userId = Auth::id();
        $isPatient = (int)$appt['patient_id']    === $userId;
        $isDoctor  = (int)$appt['doctor_user_id'] === $userId;
        if (!$isPatient && !$isDoctor) return null;

        return $appt;
    }

    private function roomName(int $apptId): string
    {
        return 'ebizmedic-' . $apptId . '-' . substr(md5($apptId . SESSION_SECRET), 0, 10);
    }

    // ── GET: lobby ────────────────────────────────────────────────────────

    public function lobby(): void
    {
        $apptId = (int) ($_GET['appointment_id'] ?? 0);
        $appt   = $this->loadAppointment($apptId);
        if (!$appt) {
            flash('error', 'Consultation not found or access denied.');
            redirect(Auth::dashboardPath());
        }

        $isDoctor = (int)$appt['doctor_user_id'] === Auth::id();

        view('layouts/public', [
            'pageTitle' => 'Consultation Lobby',
            'content'   => 'consultation/lobby',
            'appt'      => $appt,
            'isDoctor'  => $isDoctor,
        ]);
    }

    // ── GET: room ─────────────────────────────────────────────────────────

    public function room(): void
    {
        $apptId = (int) ($_GET['appointment_id'] ?? 0);
        $appt   = $this->loadAppointment($apptId);
        if (!$appt) {
            flash('error', 'Consultation not found or access denied.');
            redirect(Auth::dashboardPath());
        }

        $isDoctor = (int)$appt['doctor_user_id'] === Auth::id();
        $roomName = $this->roomName($apptId);

        $messages = Database::query(
            'SELECT cm.id, cm.sender_id, cm.message, cm.created_at, u.name AS sender_name
             FROM consultation_messages cm
             JOIN users u ON cm.sender_id = u.id
             WHERE cm.appointment_id = ?
             ORDER BY cm.created_at ASC',
            [$apptId]
        );

        view('layouts/minimal', [
            'pageTitle' => 'Online Consultation — ' . ($isDoctor ? $appt['patient_name'] : 'Dr. ' . $appt['doctor_name']),
            'content'   => 'consultation/room',
            'appt'      => $appt,
            'isDoctor'  => $isDoctor,
            'roomName'  => $roomName,
            'messages'  => $messages,
            'myId'      => Auth::id(),
            'myName'    => Auth::user()['name'],
        ]);
    }

    // ── GET: messages (JSON poll) ─────────────────────────────────────────

    public function messages(): void
    {
        header('Content-Type: application/json');
        $apptId  = (int) ($_GET['appointment_id'] ?? 0);
        $afterId = (int) ($_GET['after_id']       ?? 0);

        $appt = $this->loadAppointment($apptId);
        if (!$appt) { echo json_encode(['messages' => []]); return; }

        $msgs = Database::query(
            'SELECT cm.id, cm.sender_id, cm.message, cm.created_at, u.name AS sender_name
             FROM consultation_messages cm
             JOIN users u ON cm.sender_id = u.id
             WHERE cm.appointment_id = ? AND cm.id > ?
             ORDER BY cm.created_at ASC',
            [$apptId, $afterId]
        );

        echo json_encode(['messages' => $msgs]);
    }

    // ── POST: send chat message ───────────────────────────────────────────

    public function sendMessage(): void
    {
        header('Content-Type: application/json');
        if (!csrf_verify()) { echo json_encode(['ok' => false, 'error' => 'csrf']); return; }

        $apptId = (int)  ($_POST['appointment_id'] ?? 0);
        $msg    = trim(   $_POST['message']         ?? '');

        if (!$msg) { echo json_encode(['ok' => false]); return; }

        $appt = $this->loadAppointment($apptId);
        if (!$appt) { echo json_encode(['ok' => false, 'error' => 'not found']); return; }

        $newId = Database::insert(
            'INSERT INTO consultation_messages (appointment_id, sender_id, message) VALUES (?,?,?)',
            [$apptId, Auth::id(), $msg]
        );

        echo json_encode(['ok' => true, 'id' => $newId]);
    }

    // ── POST: end call ────────────────────────────────────────────────────

    public function endCall(): void
    {
        if (!csrf_verify()) { redirect(Auth::dashboardPath()); }

        $apptId = (int) ($_POST['appointment_id'] ?? 0);
        $appt   = $this->loadAppointment($apptId);
        if (!$appt) { redirect(Auth::dashboardPath()); }

        $isDoctor = (int)$appt['doctor_user_id'] === Auth::id();

        if ($isDoctor) {
            $existing = Database::queryOne(
                'SELECT id FROM medical_records WHERE appointment_id = ?', [$apptId]
            );
            if ($existing) {
                flash('success', 'Consultation ended. Medical record already on file.');
                redirect('medic/records');
            } else {
                flash('success', 'Consultation ended. Please complete the medical record.');
                redirect('medic/records/create?appointment_id=' . $apptId);
            }
        } else {
            flash('success', 'Consultation ended. Thank you for using eBizMedic!');
            redirect('user/appointments');
        }
    }
}
