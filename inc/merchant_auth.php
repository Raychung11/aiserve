<?php
require_once __DIR__ . '/auth.php';
auth_start_session();
auth_require_role(['merchant', 'super_admin'], APP_URL . '/login');

// For merchants (not admins), check merchant status
if (auth_role() === 'merchant') {
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM merchants WHERE user_id = ?");
    $stmt->execute([auth_id()]);
    $merchant = $stmt->fetch();
    if (!$merchant || $merchant['status'] !== 'active') {
        // Allow viewing profile/settings but block product/order management
        // Set a global flag
        $GLOBALS['merchant_pending'] = true;
    }
}
