<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/NotificationManager.php';

$pageTitle = 'Notifications';
$userId    = $currentUser['id'];

// Mark single or all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
    if (!empty($_POST['mark_all'])) {
        NotificationManager::markAllRead($userId);
    } elseif (!empty($_POST['notif_id'])) {
        NotificationManager::markRead((int)$_POST['notif_id'], $userId);
    }
    header('Location: ' . APP_URL . '/notifications'); exit;
}

$notifications = NotificationManager::getForUser($userId, 50);
$unreadCount   = NotificationManager::getUnreadCount($userId);
$csrf          = Auth::csrfToken();

$typeIcon = [
    'action_plan'        => ['bi-clipboard2-check-fill', '#7c3aed', '#ede9fe'],
    'overdue_submission' => ['bi-exclamation-triangle-fill', '#dc2626', '#fee2e2'],
    'kpi_alert'          => ['bi-graph-up-arrow', '#d97706', '#fef3c7'],
    'system'             => ['bi-bell-fill', '#0ea5e9', '#e0f2fe'],
    'gap_alert'          => ['bi-shield-exclamation', '#dc2626', '#fee2e2'],
    'report'             => ['bi-file-earmark-text-fill', '#16a34a', '#dcfce7'],
];

include __DIR__ . '/../includes/header.php';
?>
<style>
.notif-list { display:flex; flex-direction:column; gap:8px; }
.notif-item { background:#fff; border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 18px;
              display:flex; align-items:flex-start; gap:14px; transition:background .15s; }
.notif-item.unread { border-left:4px solid #0ea5e9; background:#f8fbff; }
.notif-item:hover { background:#f8fafc; }
.notif-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center;
              justify-content:center; font-size:16px; flex-shrink:0; }
.notif-body { flex:1; min-width:0; }
.notif-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:3px; }
.notif-msg   { font-size:13px; color:#64748b; line-height:1.5; }
.notif-time  { font-size:11px; color:#94a3b8; margin-top:4px; }
.notif-actions { display:flex; flex-direction:column; gap:6px; align-items:flex-end; flex-shrink:0; }
.unread-dot { width:8px; height:8px; border-radius:50%; background:#0ea5e9; }
</style>

<div class="app-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title">Notifications
        <?php if ($unreadCount > 0): ?>
        <span class="badge bg-danger ms-2" style="font-size:12px"><?= $unreadCount ?></span>
        <?php endif; ?>
      </div>
      <div class="topbar-sub">Your activity feed</div>
    </div>
    <?php if ($unreadCount > 0): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="mark_all" value="1">
      <button type="submit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-check2-all me-1"></i>Mark all read
      </button>
    </form>
    <?php endif; ?>
  </div>
  <div class="content-body">

    <?php if (empty($notifications)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-bell-slash" style="font-size:3rem;opacity:.3"></i>
      <p class="mt-3">No notifications yet.</p>
    </div>
    <?php else: ?>
    <div class="notif-list">
      <?php foreach ($notifications as $n):
          [$icon, $color, $bg] = $typeIcon[$n['type']] ?? ['bi-bell-fill', '#0ea5e9', '#e0f2fe'];
          $timeAgo = self_time_ago(strtotime($n['created_at']));
      ?>
      <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
        <div class="notif-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div class="notif-body">
          <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><i class="bi bi-clock me-1"></i><?= $timeAgo ?></div>
        </div>
        <div class="notif-actions">
          <?php if (!$n['is_read']): ?>
          <div class="unread-dot" title="Unread"></div>
          <?php endif; ?>
          <?php if (!$n['is_read']): ?>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
            <button type="submit" class="btn btn-xs btn-outline-secondary" style="font-size:11px;padding:2px 8px">Read</button>
          </form>
          <?php endif; ?>
          <?php if ($n['link']): ?>
          <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px">View</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
function self_time_ago(int $timestamp): string {
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('d M Y', $timestamp);
}
?>
