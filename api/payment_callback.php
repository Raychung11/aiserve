<?php
/**
 * Payment Gateway Callback / Webhook
 * POST /api/payment_callback
 *
 * MVP: Handles manual payment confirmation webhook.
 * Architecture is prepared for FPX / Billplz / Toyyibpay integration.
 * In production, verify the HMAC signature from the gateway before processing.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Get raw body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

// TODO: Verify HMAC/signature from gateway
// $secret = get_setting('payment_webhook_secret', '');
// $sig    = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
// if (!hash_equals(hash_hmac('sha256', $raw, $secret), $sig)) { http_response_code(403); exit; }

$transaction_ref = $data['transaction_ref'] ?? $data['billcode'] ?? $data['order_id'] ?? '';
$status          = $data['status'] ?? $data['status_id'] ?? '';
$amount_paid     = $data['amount'] ?? $data['amount_paid'] ?? '0';

error_log('Payment callback received: ref=' . $transaction_ref . ' status=' . $status);

if (!$transaction_ref) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction reference.']);
    exit;
}

$db = getDB();

// Look up the payment transaction
$stmt = $db->prepare("SELECT * FROM payment_transactions WHERE transaction_ref=? AND status='pending'");
$stmt->execute([$transaction_ref]);
$txn = $stmt->fetch();

if (!$txn) {
    // Already processed or not found
    echo json_encode(['ok' => true, 'message' => 'Transaction not found or already processed.']);
    exit;
}

$purchase_id = (int)$txn['purchase_id'];
$user_id     = (int)$txn['user_id'];

// Normalize status
$is_success = in_array((string)$status, ['1', 'success', 'paid', 'completed', '200'], true);

if ($is_success) {
    $db->beginTransaction();
    try {
        // Mark payment transaction as paid
        $db->prepare("UPDATE payment_transactions SET status='paid', gateway_response=?, updated_at=NOW() WHERE id=?")
           ->execute([json_encode($data), $txn['id']]);

        // Look up the gold purchase
        $gp = $db->prepare("SELECT * FROM gold_purchases WHERE id=? AND status='pending'");
        $gp->execute([$purchase_id]);
        $purchase = $gp->fetch();

        if ($purchase) {
            $price_snap   = (string)$purchase['price_per_g_snapshot'];
            $points_earned = gold_points_from_rm((string)$purchase['rm_amount'], $price_snap);
            $grams_earned  = gold_grams_from_points($points_earned);
            $rm_val        = (string)$purchase['rm_amount'];

            // Get wallet
            $wallet = get_wallet($user_id);
            if (!$wallet) throw new \RuntimeException('Wallet not found for user ' . $user_id);

            // Credit wallet
            $ledger_id = ledger_credit(
                (int)$wallet['id'],
                $user_id,
                $points_earned,
                $grams_earned,
                $rm_val,
                $price_snap,
                'gold_purchase',
                $purchase_id,
                'Pembelian emas via gateway (ref: ' . $transaction_ref . ')'
            );

            // Mark purchase as completed
            $db->prepare("UPDATE gold_purchases SET status='completed', points_earned=?, grams_earned=?, ledger_entry_id=?, updated_at=NOW() WHERE id=?")
               ->execute([$points_earned, $grams_earned, $ledger_id, $purchase_id]);

            // Process referral commissions
            process_referral_commissions($user_id, 'gold_purchase', $purchase_id, $points_earned, $price_snap);

            audit_log($user_id, 'system', 'payment_confirmed_gateway', 'gold_purchases', $purchase_id, ['status'=>'pending'], ['status'=>'completed','ref'=>$transaction_ref]);
        }

        $db->commit();
        echo json_encode(['ok' => true, 'message' => 'Payment processed successfully.']);
    } catch (\Throwable $e) {
        $db->rollBack();
        error_log('Payment callback processing error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Processing error. Will retry.']);
    }
} else {
    // Failed payment
    $db->prepare("UPDATE payment_transactions SET status='failed', gateway_response=?, updated_at=NOW() WHERE id=?")
       ->execute([json_encode($data), $txn['id']]);
    $db->prepare("UPDATE gold_purchases SET status='cancelled', updated_at=NOW() WHERE id=?")
       ->execute([$purchase_id]);

    echo json_encode(['ok' => true, 'message' => 'Payment marked as failed.']);
}
