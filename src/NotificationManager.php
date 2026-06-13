<?php
class NotificationManager {

    public static function create(int $userId, string $type, string $title, string $message, ?string $link = null, ?int $companyId = null): int {
        return Database::insert('notifications', [
            'user_id'    => $userId,
            'company_id' => $companyId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => 0,
        ]);
    }

    public static function getForUser(int $userId, int $limit = 20): array {
        return Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int)$limit,
            [$userId]
        );
    }

    public static function getUnreadCount(int $userId): int {
        $row = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public static function markRead(int $notificationId, int $userId): void {
        Database::update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }

    public static function markAllRead(int $userId): void {
        Database::update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
    }

    /** Notify all consultants/managers who have access to a company. */
    public static function notifyCompanyAccess(int $companyId, string $type, string $title, string $message, ?string $link = null): void {
        $users = Database::fetchAll(
            'SELECT DISTINCT user_id FROM user_companies WHERE company_id = ?',
            [$companyId]
        );
        foreach ($users as $u) {
            self::create((int)$u['user_id'], $type, $title, $message, $link, $companyId);
        }
    }

    public static function deleteOld(int $daysOld = 60): void {
        Database::query(
            'DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$daysOld]
        );
    }
}
