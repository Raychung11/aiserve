<?php
require_once __DIR__.'/../includes/auth_check.php';
$activePage = 'reservations';
$pageTitle  = 'Reservations';

// ── helpers ────────────────────────────────────────────────────────────────
function platformLabel(string $p): string {
    return match($p) {
        'airbnb'      => 'Airbnb',
        'booking_com' => 'Booking.com',
        'agoda'       => 'Agoda',
        'expedia'     => 'Expedia',
        'direct'      => 'Direct',
        default       => 'Other',
    };
}
function statusBadge(string $s): string {
    $map = [
        'pending'     => 'badge-amber',
        'confirmed'   => 'badge-str',
        'checked_in'  => 'badge-green',
        'checked_out' => 'badge-secondary',
        'cancelled'   => 'badge-red',
        'no_show'     => 'badge-red',
    ];
    $cls = $map[$s] ?? 'badge-secondary';
    return '<span class="'.$cls.'">'.ucfirst(str_replace('_',' ',$s)).'</span>';
}

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── create / edit reservation ──────────────────────────────────────────
    if (in_array($action, ['create','edit'], true)) {
        $roomRate   = (float)($_POST['room_rate']    ?? 0);
        $cleanFee   = (float)($_POST['cleaning_fee'] ?? 0);
        $otaRate    = (float)($_POST['ota_commission_rate'] ?? 0);
        $sstRate    = (float)($_POST['sst_rate']     ?? 0);
        $gross      = $roomRate + $cleanFee;
        $otaAmt     = round($gross * $otaRate / 100, 2);
        $sstAmt     = round($roomRate * $sstRate / 100, 2);
        $deposit    = (float)($_POST['security_deposit'] ?? 0);
        $checkIn    = $_POST['check_in']  ?? '';
        $checkOut   = $_POST['check_out'] ?? '';
        $nights     = ($checkIn && $checkOut)
                        ? (int)max(1, (strtotime($checkOut) - strtotime($checkIn)) / 86400)
                        : (int)($_POST['nights'] ?? 1);
        $fields = [
            'tenant_id'           => $_tenantId,
            'property_id'         => (int)($_POST['property_id'] ?? 0),
            'confirmation_code'   => trim($_POST['confirmation_code'] ?? '') ?: null,
            'platform'            => $_POST['platform'] ?? 'direct',
            'guest_name'          => trim($_POST['guest_name'] ?? ''),
            'guest_email'         => trim($_POST['guest_email'] ?? '') ?: null,
            'guest_phone'         => trim($_POST['guest_phone'] ?? '') ?: null,
            'guest_ic'            => trim($_POST['guest_ic']    ?? '') ?: null,
            'check_in'            => $checkIn,
            'check_out'           => $checkOut,
            'nights'              => $nights,
            'adults'              => (int)($_POST['adults']   ?? 1),
            'children'            => (int)($_POST['children'] ?? 0),
            'room_rate'           => $roomRate,
            'cleaning_fee'        => $cleanFee,
            'ota_commission_rate' => $otaRate,
            'ota_tax_amount'      => $otaAmt,
            'sst_rate'            => $sstRate,
            'sst_amount'          => $sstAmt,
            'total_charges'       => $gross,
            'security_deposit'    => $deposit,
            'deposit_collected'   => (float)($_POST['deposit_collected'] ?? 0),
            'deposit_refunded'    => (float)($_POST['deposit_refunded']  ?? 0),
            'status'              => $_POST['status'] ?? 'confirmed',
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];
        if (!$fields['guest_name'] || !$fields['property_id']) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'Guest name and property are required.'];
            header('Location: '.APP_URL.'/reservations'); exit;
        }
        if ($action === 'create') {
            $fields['created_by'] = $_user['id'];
            $fields['created_at'] = date('Y-m-d H:i:s');
            // recalc outstanding
            $fields['payment_received']    = 0;
            $fields['outstanding_balance'] = $gross;
            $rid = Database::insert('reservations', $fields);
            ActivityLog::record('reservation.create', 'Reservation created: '.$fields['guest_name'], $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Reservation created.'];
            header('Location: '.APP_URL.'/reservations?view='.$rid); exit;
        } else {
            $rid = (int)($_POST['id'] ?? 0);
            // recalc payment totals
            $paid = (float)Database::query(
                "SELECT COALESCE(SUM(amount),0) FROM reservation_payments WHERE reservation_id=? AND tenant_id=?",
                [$rid, $_tenantId]
            )->fetchColumn();
            $fields['payment_received']    = $paid;
            $fields['outstanding_balance'] = round($gross - $paid, 2);
            Database::update('reservations', $fields, 'id=? AND tenant_id=?', [$rid, $_tenantId]);
            ActivityLog::record('reservation.update', 'Reservation updated #'.$rid, $_tenantId, $_user['id']);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Reservation updated.'];
            header('Location: '.APP_URL.'/reservations?view='.$rid); exit;
        }
    }

    // ── delete reservation ─────────────────────────────────────────────────
    if ($action === 'delete') {
        $rid = (int)$_POST['id'];
        Database::delete('reservation_charges',  'reservation_id=? AND tenant_id=?', [$rid, $_tenantId]);
        Database::delete('reservation_payments', 'reservation_id=? AND tenant_id=?', [$rid, $_tenantId]);
        Database::delete('reservations',         'id=? AND tenant_id=?',             [$rid, $_tenantId]);
        ActivityLog::record('reservation.delete', "Deleted reservation #$rid", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Reservation deleted.'];
        header('Location: '.APP_URL.'/reservations'); exit;
    }

    // ── add charge ─────────────────────────────────────────────────────────
    if ($action === 'add_charge') {
        $rid = (int)$_POST['reservation_id'];
        Database::insert('reservation_charges', [
            'tenant_id'      => $_tenantId,
            'reservation_id' => $rid,
            'charge_date'    => $_POST['charge_date'] ?: date('Y-m-d'),
            'charge_type'    => trim($_POST['charge_type'] ?? 'Other'),
            'amount'         => (float)$_POST['amount'],
            'remarks'        => trim($_POST['remarks'] ?? '') ?: null,
            'created_by'     => $_user['id'],
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Charge added.'];
        header('Location: '.APP_URL.'/reservations?view='.$rid.'&tab=charges'); exit;
    }

    // ── delete charge ──────────────────────────────────────────────────────
    if ($action === 'delete_charge') {
        $cid = (int)$_POST['charge_id'];
        $rid = (int)$_POST['reservation_id'];
        Database::delete('reservation_charges', 'id=? AND tenant_id=?', [$cid, $_tenantId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Charge removed.'];
        header('Location: '.APP_URL.'/reservations?view='.$rid.'&tab=charges'); exit;
    }

    // ── add payment ────────────────────────────────────────────────────────
    if ($action === 'add_payment') {
        $rid = (int)$_POST['reservation_id'];
        $amt = (float)$_POST['amount'];
        Database::insert('reservation_payments', [
            'tenant_id'      => $_tenantId,
            'reservation_id' => $rid,
            'payment_date'   => $_POST['payment_date'] ?: date('Y-m-d'),
            'amount'         => $amt,
            'payment_method' => $_POST['payment_method'] ?? 'bank_transfer',
            'reference_no'   => trim($_POST['reference_no'] ?? '') ?: null,
            'notes'          => trim($_POST['notes']       ?? '') ?: null,
            'created_by'     => $_user['id'],
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        // recalc balances on reservation
        $res = Database::query("SELECT total_charges FROM reservations WHERE id=? AND tenant_id=?", [$rid, $_tenantId])->fetch();
        $totalPaid = (float)Database::query(
            "SELECT COALESCE(SUM(amount),0) FROM reservation_payments WHERE reservation_id=? AND tenant_id=?",
            [$rid, $_tenantId]
        )->fetchColumn();
        Database::update('reservations', [
            'payment_received'    => $totalPaid,
            'outstanding_balance' => round(($res['total_charges'] ?? 0) - $totalPaid, 2),
        ], 'id=? AND tenant_id=?', [$rid, $_tenantId]);
        ActivityLog::record('reservation.payment', "Payment RM $amt recorded for reservation #$rid", $_tenantId, $_user['id']);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Payment recorded.'];
        header('Location: '.APP_URL.'/reservations?view='.$rid.'&tab=payments'); exit;
    }

    // ── delete payment ─────────────────────────────────────────────────────
    if ($action === 'delete_payment') {
        $pid = (int)$_POST['payment_id'];
        $rid = (int)$_POST['reservation_id'];
        Database::delete('reservation_payments', 'id=? AND tenant_id=?', [$pid, $_tenantId]);
        // recalc
        $res = Database::query("SELECT total_charges FROM reservations WHERE id=? AND tenant_id=?", [$rid, $_tenantId])->fetch();
        $totalPaid = (float)Database::query(
            "SELECT COALESCE(SUM(amount),0) FROM reservation_payments WHERE reservation_id=? AND tenant_id=?",
            [$rid, $_tenantId]
        )->fetchColumn();
        Database::update('reservations', [
            'payment_received'    => $totalPaid,
            'outstanding_balance' => round(($res['total_charges'] ?? 0) - $totalPaid, 2),
        ], 'id=? AND tenant_id=?', [$rid, $_tenantId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Payment removed.'];
        header('Location: '.APP_URL.'/reservations?view='.$rid.'&tab=payments'); exit;
    }

    // ── collect / refund deposit ───────────────────────────────────────────
    if ($action === 'collect_deposit' || $action === 'refund_deposit') {
        $rid = (int)$_POST['reservation_id'];
        $amt = (float)$_POST['deposit_amount'];
        $field = $action === 'collect_deposit' ? 'deposit_collected' : 'deposit_refunded';
        Database::query("UPDATE reservations SET $field=? WHERE id=? AND tenant_id=?", [$amt, $rid, $_tenantId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>ucfirst(str_replace('_',' ',$action)).' updated.'];
        header('Location: '.APP_URL.'/reservations?view='.$rid); exit;
    }
}

if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); } else { $flash = []; }

// ── properties dropdown ────────────────────────────────────────────────────
$allProperties = Database::query(
    "SELECT id, property_name FROM properties WHERE tenant_id=? ORDER BY property_name",
    [$_tenantId]
)->fetchAll();

// ── detail view ────────────────────────────────────────────────────────────
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$activeTab = $_GET['tab'] ?? 'charges';
$res = null; $charges = []; $payments = [];
if ($viewId) {
    $res = Database::query(
        "SELECT r.*, p.property_name FROM reservations r
         LEFT JOIN properties p ON p.id=r.property_id
         WHERE r.id=? AND r.tenant_id=?",
        [$viewId, $_tenantId]
    )->fetch();
    if (!$res) { header('Location: '.APP_URL.'/reservations'); exit; }
    $charges  = Database::query("SELECT * FROM reservation_charges  WHERE reservation_id=? AND tenant_id=? ORDER BY charge_date DESC",  [$viewId, $_tenantId])->fetchAll();
    $payments = Database::query("SELECT * FROM reservation_payments WHERE reservation_id=? AND tenant_id=? ORDER BY payment_date DESC", [$viewId, $_tenantId])->fetchAll();
}

// ── list view data ─────────────────────────────────────────────────────────
$fProp     = (int)($_GET['property_id'] ?? 0);
$fPlatform = $_GET['platform'] ?? '';
$fStatus   = $_GET['status']   ?? '';
$fMonth    = $_GET['month']    ?? '';

$where  = 'r.tenant_id=?';
$params = [$_tenantId];
if ($fProp)     { $where .= ' AND r.property_id=?';   $params[] = $fProp; }
if ($fPlatform) { $where .= ' AND r.platform=?';       $params[] = $fPlatform; }
if ($fStatus)   { $where .= ' AND r.status=?';         $params[] = $fStatus; }
if ($fMonth)    { $where .= ' AND DATE_FORMAT(r.check_in,"%Y-%m")=?'; $params[] = $fMonth; }

$reservations = Database::query(
    "SELECT r.*, p.property_name FROM reservations r
     LEFT JOIN properties p ON p.id=r.property_id
     WHERE $where ORDER BY r.check_in DESC LIMIT 200",
    $params
)->fetchAll();

// KPIs
$kpi = Database::query(
    "SELECT COUNT(*) total,
            SUM(CASE WHEN status='confirmed'  THEN 1 ELSE 0 END) confirmed,
            SUM(CASE WHEN status='checked_in' THEN 1 ELSE 0 END) checked_in,
            COALESCE(SUM(total_charges),0) gross_total,
            COALESCE(SUM(outstanding_balance),0) outstanding
     FROM reservations WHERE tenant_id=?", [$_tenantId]
)->fetch();
