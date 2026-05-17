<?php

class NotificationController
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $userId = Auth::id();

        $notifications = Database::query(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 60',
            [$userId]
        );

        // Mark all as read on page view
        Database::execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);

        view('layouts/app', [
            'pageTitle'     => 'Notifications',
            'content'       => 'shared/notifications',
            'notifications' => $notifications,
        ]);
    }

    public function markRead(): void
    {
        Database::execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [Auth::id()]);
        $back = $_SERVER['HTTP_REFERER'] ?? url('notifications');
        header('Location: ' . $back);
        exit;
    }
}
