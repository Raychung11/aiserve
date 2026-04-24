<?php
class BillplzService {
    public static function createBill(array $data): array {
        $payload = http_build_query([
            'collection_id'     => $data['collection_id'] ?? BILLPLZ_COLLECTION_ID,
            'email'             => $data['email'],
            'mobile'            => $data['phone'] ?? '',
            'name'              => $data['name'],
            'amount'            => (int) round($data['amount'] * 100),
            'description'       => $data['description'],
            'callback_url'      => $data['callback_url'] ?? APP_URL . '/billplz-callback',
            'redirect_url'      => $data['redirect_url'] ?? APP_URL . '/subscription',
            'reference_1_label' => $data['ref_label'] ?? 'Reference',
            'reference_1'       => $data['reference'] ?? '',
        ]);

        $ch = curl_init(BILLPLZ_BASE_URL . '/bills');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => BILLPLZ_API_KEY . ':',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => json_decode($response, true)];
        }
        $body = json_decode($response, true);
        return ['success' => false, 'error' => $body['error']['message'] ?? 'Payment gateway error.'];
    }

    public static function verifyWebhook(array $data): bool {
        if (!BILLPLZ_X_SIGNATURE) return false;
        ksort($data);
        $source   = implode('|', array_map(fn($k,$v) => "$k$v", array_keys($data), array_values($data)));
        $computed = hash_hmac('sha256', $source, BILLPLZ_X_SIGNATURE);
        return hash_equals($computed, $data['x_signature'] ?? '');
    }

    public static function createSubscriptionBill(array $tenant, array $user, string $plan, float $amount): array {
        return self::createBill([
            'email'        => $user['email'],
            'phone'        => $user['phone'] ?? '',
            'name'         => $user['name'],
            'amount'       => $amount,
            'description'  => 'STRHub AI — ' . ucfirst($plan) . ' Plan Subscription',
            'ref_label'    => 'Tenant ID',
            'reference'    => 'TENANT-' . $tenant['id'],
            'redirect_url' => APP_URL . '/subscription?paid=1&plan=' . $plan,
        ]);
    }
}
