<?php
class ActivityLog {
    public static function record(string $action, string $description = '', int $tenantId = 0, int $userId = 0): void {
        try {
            Database::insert('activity_logs', [
                'tenant_id'   => $tenantId ?: (Auth::tenantId() ?: 0),
                'user_id'     => $userId   ?: (Auth::check() ? Auth::user()['id'] : 0),
                'action'      => $action,
                'description' => $description,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // never break the app over logging
        }
    }
}
