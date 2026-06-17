<?php

class InvoiceController
{
    public function __construct()
    {
        Auth::require();
    }

    public function show(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $role = Auth::role();
        $uid  = Auth::id();

        $dispensing = Database::queryOne(
            'SELECT d.*, u.name AS patient_name, u.phone AS patient_phone,
                    du.name AS dispensed_by_name, o.name AS org_name,
                    o.address AS org_address, o.phone AS org_phone, o.city AS org_city
             FROM dispensings d
             JOIN users u  ON d.patient_id  = u.id
             JOIN users du ON d.dispensed_by = du.id
             JOIN organisations o ON d.organisation_id = o.id
             WHERE d.id = ?',
            [$id]
        );

        if (!$dispensing) { flash('error', 'Invoice not found.'); redirect(''); }

        // Access control
        $allowed = match($role) {
            'admin'        => true,
            'user'         => $dispensing['patient_id'] == $uid,
            'pharmacist'   => (bool) Database::queryOne(
                                 'SELECT id FROM pharmacists WHERE user_id = ? AND organisation_id = ?',
                                 [$uid, $dispensing['organisation_id']]
                             ),
            'organisation' => (bool) Database::queryOne(
                                 'SELECT id FROM organisations WHERE user_id = ? AND id = ?',
                                 [$uid, $dispensing['organisation_id']]
                             ),
            'medic'        => $dispensing['doctor_id'] && (bool) Database::queryOne(
                                 'SELECT id FROM doctors WHERE user_id = ? AND id = ?',
                                 [$uid, $dispensing['doctor_id']]
                             ),
            default        => false,
        };

        if (!$allowed) { flash('error', 'Access denied.'); redirect(''); }

        $items = Database::query(
            'SELECT di.*, m.name AS medicine_name, m.unit, m.generic_name
             FROM dispensing_items di
             JOIN medicines m ON di.medicine_id = m.id
             WHERE di.dispensing_id = ?',
            [$id]
        );

        view('layouts/app', [
            'pageTitle'  => 'Invoice #' . str_pad($id, 6, '0', STR_PAD_LEFT),
            'content'    => 'shared/invoice',
            'dispensing' => $dispensing,
            'items'      => $items,
        ]);
    }
}
