<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
registerDebugShutdown();
requireOperatorLogin();

$db  = getDB();
$cid = companyId();
$uid = $_SESSION['user_id'] ?? null;

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = $_POST['_action'] ?? '';

    if ($act === 'reply') {
        $threadId  = (int)($_POST['thread_id']  ?? 0);
        $body      = trim($_POST['body']         ?? '');
        $escalated = isset($_POST['escalate']) ? 1 : 0;

        if (!$threadId || $body === '') {
            flashSet('danger', 'Message is required.'); header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        // Verify thread belongs to this company
        $chk = $db->prepare('SELECT id, resident_id FROM support_messages WHERE id=? AND company_id=? AND parent_id IS NULL');
        $chk->execute([$threadId, $cid]);
        $thread = $chk->fetch();
        if (!$thread) { flashSet('danger', 'Thread not found.'); header('Location: support.php'); exit; }

        $db->prepare(
            'INSERT INTO support_messages (company_id,resident_id,parent_id,sender_type,sender_id,body,is_escalated)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$cid, $thread['resident_id'], $threadId, 'staff', $uid, $body, $escalated]);

        if ($escalated) {
            $db->prepare('UPDATE support_messages SET is_escalated=1 WHERE id=?')->execute([$threadId]);
        }
        flashSet('success', 'Reply sent.');
        header('Location: support.php?thread=' . $threadId); exit;
    }

    if ($act === 'close') {
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $db->prepare('UPDATE support_messages SET status=? WHERE id=? AND company_id=? AND parent_id IS NULL')
           ->execute(['closed', $threadId, $cid]);
        flashSet('success', 'Conversation closed.');
        header('Location: support.php'); exit;
    }

    if ($act === 'reopen') {
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $db->prepare('UPDATE support_messages SET status=? WHERE id=? AND company_id=? AND parent_id IS NULL')
           ->execute(['open', $threadId, $cid]);
        flashSet('success', 'Conversation reopened.');
        header('Location: support.php?thread=' . $threadId); exit;
    }
}

// ── Thread view ───────────────────────────────────────────────────────────────
$activeThread = null;
$messages     = [];
$threadId     = (int)($_GET['thread'] ?? 0);
if ($threadId) {
    $stmt = $db->prepare(
        'SELECT sm.*, res.name AS resident_name, res.email AS resident_email
         FROM support_messages sm
         JOIN residents res ON res.id=sm.resident_id
         WHERE sm.id=? AND sm.company_id=? AND sm.parent_id IS NULL'
    );
    $stmt->execute([$threadId, $cid]);
    $activeThread = $stmt->fetch() ?: null;

    if ($activeThread) {
        // Mark unread staff messages as read
        $db->prepare(
            'UPDATE support_messages SET is_read=1 WHERE (id=? OR parent_id=?) AND company_id=? AND sender_type=\'resident\' AND is_read=0'
        )->execute([$threadId, $threadId, $cid]);

        $msgStmt = $db->prepare(
            'SELECT sm.*, us.full_name AS staff_name FROM support_messages sm
             LEFT JOIN users us ON us.id=sm.sender_id AND sm.sender_type=\'staff\'
             WHERE (sm.id=? OR sm.parent_id=?) AND sm.company_id=?
             ORDER BY sm.created_at ASC'
        );
        $msgStmt->execute([$threadId, $threadId, $cid]);
        $messages = $msgStmt->fetchAll();
    }
}

// ── Thread list ───────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'open';
$validStatuses = ['open', 'closed', 'all'];
if (!in_array($filterStatus, $validStatuses, true)) $filterStatus = 'open';

$listSql = 'SELECT sm.*, res.name AS resident_name,
                   (SELECT COUNT(*) FROM support_messages r WHERE r.parent_id=sm.id) AS reply_count,
                   (SELECT COUNT(*) FROM support_messages r WHERE r.parent_id=sm.id AND r.sender_type=\'resident\' AND r.is_read=0) AS unread_count,
                   (SELECT MAX(r.created_at) FROM support_messages r WHERE r.parent_id=sm.id) AS last_reply_at
            FROM support_messages sm
            JOIN residents res ON res.id=sm.resident_id
            WHERE sm.company_id=? AND sm.parent_id IS NULL';
$listParams = [$cid];
if ($filterStatus !== 'all') { $listSql .= ' AND sm.status=?'; $listParams[] = $filterStatus; }
$listSql .= ' ORDER BY sm.is_escalated DESC, COALESCE(last_reply_at, sm.created_at) DESC LIMIT 60';
$listStmt = $db->prepare($listSql);
$listStmt->execute($listParams);
$threads = $listStmt->fetchAll();

// ── Counts for tabs ───────────────────────────────────────────────────────────
$openCount = (int)$db->prepare(
    'SELECT COUNT(*) FROM support_messages WHERE company_id=? AND parent_id IS NULL AND status=\'open\''
)->execute([$cid]) ? $db->query("SELECT COUNT(*) FROM support_messages WHERE company_id=$cid AND parent_id IS NULL AND status='open'")->fetchColumn() : 0;

$unreadTotal = (int)$db->prepare(
    'SELECT COUNT(*) FROM support_messages WHERE company_id=? AND sender_type=\'resident\' AND is_read=0'
)->execute([$cid]) ? $db->query("SELECT COUNT(*) FROM support_messages WHERE company_id=$cid AND sender_type='resident' AND is_read=0")->fetchColumn() : 0;

$pageTitle  = 'Support Chat';
$activePage = 'support';
include __DIR__ . '/layout.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <p class="page-sub mb-0">Resident support conversations</p>
  </div>
  <?php if ($unreadTotal > 0): ?>
  <span class="s-badge badge-open"><?= $unreadTotal ?> unread</span>
  <?php endif; ?>
</div>

<!-- AI stub notice -->
<div class="alert" style="background:#f0f9ff;border:1px solid #bae6fd;color:#075985;border-radius:10px;font-size:.84rem;padding:.75rem 1rem;margin-bottom:1.25rem;">
  <i class="bi bi-robot me-2"></i>
  <strong>AI Auto-Reply:</strong> Connect an LLM API in <code>helpers.php → aiAutoReply()</code> to automatically handle common resident queries. Manual staff replies always take precedence.
</div>

<div class="row g-3" style="height:calc(100vh - 260px);min-height:400px;">
  <!-- Thread list -->
  <div class="col-lg-4" style="height:100%;overflow:hidden;display:flex;flex-direction:column;">
    <div class="card-box" style="flex:1;overflow:hidden;display:flex;flex-direction:column;padding-bottom:0;">
      <!-- Status tabs -->
      <div class="d-flex gap-2 mb-3" style="flex-shrink:0;">
        <?php foreach (['open'=>'Open','closed'=>'Closed','all'=>'All'] as $s => $lbl): ?>
        <a href="support.php?status=<?= $s ?>"
           class="btn btn-sm <?= $filterStatus === $s ? 'btn-brand' : 'btn-outline-secondary' ?>"
           style="font-size:.78rem;">
          <?= $lbl ?>
          <?php if ($s === 'open' && $openCount > 0): ?>
          <span style="background:rgba(255,255,255,.25);border-radius:9px;padding:0 6px;font-size:.7rem;"><?= $openCount ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <!-- List -->
      <div style="flex:1;overflow-y:auto;margin:0 -1.25rem;padding:0 .5rem;">
        <?php if ($threads): ?>
        <?php foreach ($threads as $t):
          $isActive = $activeThread && $activeThread['id'] == $t['id'];
          $hasUnread = (int)$t['unread_count'] > 0;
        ?>
        <a href="support.php?thread=<?= $t['id'] ?>&status=<?= $filterStatus ?>"
           class="d-block px-3 py-2"
           style="border-radius:8px;text-decoration:none;background:<?= $isActive ? 'var(--brand-light,#f3e8ff)' : 'transparent' ?>;margin-bottom:2px;">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fw-semibold" style="font-size:.84rem;color:<?= $isActive ? 'var(--brand)' : '#1e293b' ?>;">
              <?= e($t['resident_name']) ?>
              <?php if ($t['is_escalated']): ?>
              <span style="color:#b91c1c;font-size:.7rem;">&uarr; Escalated</span>
              <?php endif; ?>
            </div>
            <div style="font-size:.7rem;color:#94a3b8;white-space:nowrap;"><?= datetimeDisplay($t['last_reply_at'] ?: $t['created_at']) ?></div>
          </div>
          <div style="font-size:.77rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">
            <?= e(mb_substr($t['body'], 0, 80)) ?>
          </div>
          <div class="d-flex gap-2 mt-1">
            <?php if ($hasUnread): ?>
            <span style="background:#b91c1c;color:#fff;border-radius:9px;padding:1px 7px;font-size:.67rem;"><?= $t['unread_count'] ?> new</span>
            <?php endif; ?>
            <span style="font-size:.7rem;color:#94a3b8;"><?= (int)$t['reply_count'] ?> repl<?= $t['reply_count'] != 1 ? 'ies' : 'y' ?></span>
          </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="text-center py-5">
          <i class="bi bi-chat-dots" style="font-size:2rem;color:#cbd5e1;"></i>
          <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">No <?= $filterStatus !== 'all' ? $filterStatus . ' ' : '' ?>conversations.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Conversation view -->
  <div class="col-lg-8" style="height:100%;overflow:hidden;display:flex;flex-direction:column;">
    <?php if ($activeThread): ?>
    <div class="card-box" style="flex:1;overflow:hidden;display:flex;flex-direction:column;padding-bottom:0;">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-3" style="flex-shrink:0;">
        <div>
          <div class="fw-bold"><?= e($activeThread['resident_name']) ?></div>
          <div style="font-size:.78rem;color:#94a3b8;"><?= e($activeThread['resident_email'] ?? '') ?></div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <?php if ($activeThread['is_escalated']): ?>
          <span class="s-badge badge-void" style="font-size:.72rem;">Escalated</span>
          <?php endif; ?>
          <?php if ($activeThread['status'] === 'open'): ?>
          <form method="POST" action="support.php" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="_action" value="close">
            <input type="hidden" name="thread_id" value="<?= $activeThread['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">Close</button>
          </form>
          <?php else: ?>
          <form method="POST" action="support.php" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="_action" value="reopen">
            <input type="hidden" name="thread_id" value="<?= $activeThread['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">Reopen</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Messages -->
      <div id="msgScroll" style="flex:1;overflow-y:auto;margin:0 -1.25rem;padding:.5rem 1.25rem 1rem;">
        <?php foreach ($messages as $msg):
          $isStaff = $msg['sender_type'] === 'staff';
          $isAi    = $msg['sender_type'] === 'ai';
        ?>
        <div class="d-flex <?= $isStaff || $isAi ? 'justify-content-end' : 'justify-content-start' ?> mb-3">
          <div style="max-width:72%;">
            <div style="font-size:.7rem;color:#94a3b8;margin-bottom:.2rem;<?= $isStaff || $isAi ? 'text-align:right' : '' ?>">
              <?php if ($isAi): ?>
              <i class="bi bi-robot me-1"></i>AI Assistant
              <?php elseif ($isStaff): ?>
              <?= e($msg['staff_name'] ?? 'Staff') ?>
              <?php else: ?>
              <?= e($activeThread['resident_name']) ?>
              <?php endif; ?>
              &middot; <?= datetimeDisplay($msg['created_at']) ?>
            </div>
            <div style="border-radius:<?= $isStaff || $isAi ? '16px 4px 16px 16px' : '4px 16px 16px 16px' ?>;
                        padding:.6rem .9rem;font-size:.84rem;
                        background:<?= $isStaff ? 'var(--brand,#9333ea)' : ($isAi ? '#e0f2fe' : '#f1f5f9') ?>;
                        color:<?= $isStaff ? '#fff' : ($isAi ? '#0c4a6e' : '#1e293b') ?>;">
              <?= nl2br(e($msg['body'])) ?>
              <?php if ($msg['is_escalated'] && !$isStaff && !$isAi): ?>
              <div style="font-size:.7rem;color:#b91c1c;margin-top:.3rem;">&uarr; Marked for escalation</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Reply form -->
      <?php if ($activeThread['status'] === 'open'): ?>
      <div style="flex-shrink:0;border-top:1px solid #f1f5f9;padding:1rem 0 .5rem;">
        <form method="POST" action="support.php">
          <?= csrfField() ?>
          <input type="hidden" name="_action" value="reply">
          <input type="hidden" name="thread_id" value="<?= $activeThread['id'] ?>">
          <div class="d-flex gap-2 align-items-end">
            <textarea name="body" class="form-control form-control-sm" rows="2" placeholder="Type a reply&hellip;" required
                      style="resize:none;flex:1;border-radius:10px;"></textarea>
            <div class="d-flex flex-column gap-1">
              <button type="submit" class="btn btn-brand btn-sm" style="white-space:nowrap;">Send</button>
              <button type="submit" name="escalate" value="1" class="btn btn-sm" style="white-space:nowrap;background:#fee2e2;color:#b91c1c;border:none;font-size:.75rem;">
                Escalate
              </button>
            </div>
          </div>
        </form>
      </div>
      <?php else: ?>
      <div style="flex-shrink:0;border-top:1px solid #f1f5f9;padding:.75rem 0;text-align:center;">
        <span style="font-size:.82rem;color:#94a3b8;">This conversation is closed.</span>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="card-box" style="flex:1;display:flex;align-items:center;justify-content:center;">
      <div class="text-center">
        <i class="bi bi-chat-dots" style="font-size:3rem;color:#cbd5e1;"></i>
        <p class="text-muted mt-3 mb-0" style="font-size:.9rem;">Select a conversation to view</p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
$extraJs = <<<JS
<script>
(function() {
  const el = document.getElementById('msgScroll');
  if (el) el.scrollTop = el.scrollHeight;
})();
</script>
JS;
include __DIR__ . '/layout_end.php';
?>
