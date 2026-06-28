<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';
registerDebugShutdown();
requireResidentLogin();

$db  = getDB();
$rid = (int)$_SESSION['resident_id'];
$cid = (int)$_SESSION['resident_company_id'];
$residentName = $_SESSION['resident_name'] ?? 'Resident';

// ── Load most recent thread for this resident ─────────────────────────────────
$threadStmt = $db->prepare(
    'SELECT * FROM support_messages
     WHERE company_id=? AND resident_id=? AND parent_id IS NULL
     ORDER BY created_at DESC
     LIMIT 1'
);
$threadStmt->execute([$cid, $rid]);
$thread = $threadStmt->fetch() ?: null;

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act  = $_POST['_action'] ?? '';
    $body = trim($_POST['body'] ?? '');

    if ($act === 'send') {
        if ($body === '') {
            flashSet('danger', 'Please enter a message.');
            header('Location: support.php'); exit;
        }

        if (!$thread) {
            // Create the thread root with the first message
            $db->prepare(
                'INSERT INTO support_messages
                 (company_id, resident_id, parent_id, sender_type, sender_id, body, status, is_read, is_escalated, created_at)
                 VALUES (?,?,NULL,\'resident\',NULL,?,\'open\',0,0,NOW())'
            )->execute([$cid, $rid, $body]);

            // Re-fetch so we can redirect cleanly
        } else {
            // Thread already exists: check if closed; if so, refuse (UI prevents, but guard server-side)
            if ($thread['status'] === 'closed') {
                flashSet('danger', 'This conversation is closed. Please start a new one.');
                header('Location: support.php'); exit;
            }
            // Insert reply
            $db->prepare(
                'INSERT INTO support_messages
                 (company_id, resident_id, parent_id, sender_type, sender_id, body, is_read, is_escalated, created_at)
                 VALUES (?,?,?,\'resident\',NULL,?,0,0,NOW())'
            )->execute([$cid, $rid, (int)$thread['id'], $body]);
        }

        header('Location: support.php'); exit;
    }

    if ($act === 'new_thread') {
        // Resident starts a fresh conversation (previous thread was closed)
        $db->prepare(
            'INSERT INTO support_messages
             (company_id, resident_id, parent_id, sender_type, sender_id, body, status, is_read, is_escalated, created_at)
             VALUES (?,?,NULL,\'resident\',NULL,?,\'open\',0,0,NOW())'
        )->execute([$cid, $rid, 'New conversation started by ' . $residentName . '.']);

        flashSet('success', 'A new conversation has been started.');
        header('Location: support.php'); exit;
    }
}

// ── Reload thread after possible insert ───────────────────────────────────────
$threadStmt->execute([$cid, $rid]);
$thread = $threadStmt->fetch() ?: null;

// ── Load messages ─────────────────────────────────────────────────────────────
$messages = [];
if ($thread) {
    $threadId = (int)$thread['id'];

    // Mark staff/AI messages as read
    $db->prepare(
        "UPDATE support_messages
         SET is_read=1
         WHERE (id=? OR parent_id=?) AND company_id=? AND sender_type!='resident' AND is_read=0"
    )->execute([$threadId, $threadId, $cid]);

    $msgStmt = $db->prepare(
        "SELECT sm.*, us.name AS staff_name
         FROM support_messages sm
         LEFT JOIN users us ON us.id = sm.sender_id AND sm.sender_type = 'staff'
         WHERE (sm.id=? OR sm.parent_id=?) AND sm.company_id=?
         ORDER BY sm.created_at ASC"
    );
    $msgStmt->execute([$threadId, $threadId, $cid]);
    $messages = $msgStmt->fetchAll();
}

// ── Page setup ────────────────────────────────────────────────────────────────
$pageTitle  = 'Support';
$activePage = 'support';

// Extra JS: auto-scroll chat to bottom, focus textarea
$extraJs = <<<'ENDJS'
<script>
(function () {
  var wrap = document.getElementById('chatMessages');
  if (wrap) wrap.scrollTop = wrap.scrollHeight;

  var ta = document.getElementById('msgBody');
  if (ta) {
    ta.addEventListener('keydown', function (e) {
      // Ctrl+Enter or Cmd+Enter submits
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        ta.closest('form').submit();
      }
    });
  }
})();
</script>
ENDJS;

include __DIR__ . '/layout.php';
?>

<style>
/* ── Chat layout ────────────────────────────────────────────────────── */
.chat-shell {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 200px);
  min-height: 420px;
  max-height: 780px;
}
.chat-header {
  flex-shrink: 0;
  padding: 1rem 1.25rem .85rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
  scroll-behavior: smooth;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }

.chat-footer {
  flex-shrink: 0;
  border-top: 1px solid #e2e8f0;
  padding: .9rem 1.25rem;
  background: #fff;
  border-radius: 0 0 14px 14px;
}

/* ── Bubbles ─────────────────────────────────────────────────────────── */
.bubble-wrap { display: flex; margin-bottom: 1rem; }
.bubble-wrap.resident { justify-content: flex-end; }
.bubble-wrap.staff    { justify-content: flex-start; }
.bubble-wrap.ai       { justify-content: flex-start; }

.bubble {
  max-width: 75%;
  padding: .6rem .9rem;
  border-radius: 16px;
  font-size: .85rem;
  line-height: 1.55;
  word-break: break-word;
}
.bubble-resident {
  background: var(--brand);
  color: #fff;
  border-radius: 16px 4px 16px 16px;
}
.bubble-staff {
  background: #f1f5f9;
  color: #1e293b;
  border-radius: 4px 16px 16px 16px;
}
.bubble-ai {
  background: #e0f2fe;
  color: #0c4a6e;
  border-radius: 4px 16px 16px 16px;
}

.bubble-meta {
  font-size: .69rem;
  color: #94a3b8;
  margin-bottom: .25rem;
}
.bubble-wrap.resident .bubble-meta { text-align: right; }

/* ── Empty state ──────────────────────────────────────────────────────── */
.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

@media (max-width: 575.98px) {
  .chat-shell { height: calc(100vh - 180px); max-height: none; }
  .bubble { max-width: 88%; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title">Support</div>
    <p class="page-sub mb-0">Chat with our team &mdash; we typically reply within a few hours</p>
  </div>
  <?php if ($thread && $thread['status'] === 'open'): ?>
  <span class="s-badge badge-open" style="font-size:.75rem;">
    <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>Active
  </span>
  <?php endif; ?>
</div>

<div class="card-box p-0 chat-shell">

  <?php if (!$thread): ?>
  <!-- ── No thread yet ──────────────────────────────────────────────── -->
  <div class="chat-empty">
    <i class="bi bi-chat-heart" style="font-size:3rem;color:var(--brand);opacity:.4;"></i>
    <p class="fw-bold mt-3 mb-1" style="font-size:.95rem;color:#0f172a;">Start a Conversation</p>
    <p class="text-muted mb-4" style="font-size:.82rem;max-width:300px;">
      Send us a message and a member of our team will get back to you as soon as possible.
    </p>
    <form method="POST" action="support.php" style="width:100%;max-width:440px;">
      <?= csrfField() ?>
      <input type="hidden" name="_action" value="send">
      <div class="mb-3">
        <textarea id="msgBody" name="body" class="form-control"
                  rows="4" required
                  placeholder="Hi, I need help with&hellip;"
                  style="border-radius:12px;font-size:.88rem;resize:none;"></textarea>
      </div>
      <button type="submit" class="btn btn-brand w-100">
        <i class="bi bi-send-fill me-2"></i>Send Message
      </button>
    </form>
  </div>

  <?php elseif ($thread['status'] === 'closed'): ?>
  <!-- ── Thread is closed ──────────────────────────────────────────── -->
  <div class="chat-header">
    <div>
      <div class="fw-bold" style="font-size:.92rem;">Conversation</div>
      <div style="font-size:.75rem;color:#94a3b8;">
        Started <?= dateDisplay($thread['created_at']) ?>
      </div>
    </div>
    <span class="s-badge" style="background:#f1f5f9;color:#64748b;font-size:.72rem;">Closed</span>
  </div>

  <!-- Messages (read-only) -->
  <div id="chatMessages" class="chat-messages">
    <?php foreach ($messages as $msg):
      $type = $msg['sender_type'];
    ?>
    <div class="bubble-wrap <?= e($type) ?>">
      <div>
        <div class="bubble-meta">
          <?php if ($type === 'ai'): ?>
            <i class="bi bi-robot me-1"></i>AI Assistant &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php elseif ($type === 'staff'): ?>
            <i class="bi bi-headset me-1"></i><?= e($msg['staff_name'] ?? 'Support Team') ?> &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php else: ?>
            <?= e($residentName) ?> &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php endif; ?>
        </div>
        <div class="bubble bubble-<?= e($type === 'resident' ? 'resident' : ($type === 'ai' ? 'ai' : 'staff')) ?>">
          <?= nl2br(e($msg['body'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="chat-footer text-center">
    <p class="text-muted mb-3" style="font-size:.82rem;">
      This conversation has been closed. Start a new conversation if you need further help.
    </p>
    <form method="POST" action="support.php" style="display:inline;">
      <?= csrfField() ?>
      <input type="hidden" name="_action" value="new_thread">
      <button type="submit" class="btn btn-brand btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Start New Conversation
      </button>
    </form>
  </div>

  <?php else: ?>
  <!-- ── Active thread ─────────────────────────────────────────────── -->
  <div class="chat-header">
    <div>
      <div class="fw-bold" style="font-size:.92rem;">
        <i class="bi bi-chat-dots-fill me-1 text-brand"></i>Support Chat
      </div>
      <div style="font-size:.75rem;color:#94a3b8;">
        Started <?= dateDisplay($thread['created_at']) ?>
        &middot; <?= count($messages) ?> message<?= count($messages) !== 1 ? 's' : '' ?>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="s-badge badge-open" style="font-size:.72rem;">Open</span>
    </div>
  </div>

  <!-- Message list -->
  <div id="chatMessages" class="chat-messages">

    <?php if (count($messages) <= 1): ?>
    <div class="text-center mb-4">
      <span style="font-size:.78rem;color:#cbd5e1;background:#f8fafc;border-radius:20px;padding:.25rem .85rem;display:inline-block;">
        Conversation started &mdash; we will reply shortly
      </span>
    </div>
    <?php endif; ?>

    <?php foreach ($messages as $msg):
      $type = $msg['sender_type'];
      $isResident = $type === 'resident';
      $isAi       = $type === 'ai';
      $bubbleCls  = $isResident ? 'bubble-resident' : ($isAi ? 'bubble-ai' : 'bubble-staff');
    ?>
    <div class="bubble-wrap <?= e($type) ?>">
      <div>
        <div class="bubble-meta">
          <?php if ($isAi): ?>
            <i class="bi bi-robot me-1"></i>AI Assistant &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php elseif (!$isResident): ?>
            <i class="bi bi-headset me-1"></i><?= e($msg['staff_name'] ?? 'Support Team') ?> &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php else: ?>
            <?= e($residentName) ?> &middot; <?= datetimeDisplay($msg['created_at']) ?>
          <?php endif; ?>
        </div>
        <div class="bubble <?= e($bubbleCls) ?>">
          <?= nl2br(e($msg['body'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Reply form -->
  <div class="chat-footer">
    <form method="POST" action="support.php">
      <?= csrfField() ?>
      <input type="hidden" name="_action" value="send">
      <div class="d-flex gap-2 align-items-end">
        <textarea id="msgBody" name="body" class="form-control form-control-sm"
                  rows="2" required
                  placeholder="Type a message&hellip; (Ctrl+Enter to send)"
                  style="resize:none;border-radius:10px;flex:1;font-size:.87rem;"></textarea>
        <button type="submit" class="btn btn-brand btn-sm px-3"
                style="height:fit-content;border-radius:10px;white-space:nowrap;">
          <i class="bi bi-send-fill me-1"></i>Send
        </button>
      </div>
      <div style="font-size:.7rem;color:#cbd5e1;margin-top:.4rem;">
        Press Ctrl+Enter to send quickly &middot; Our team typically replies within a few hours
      </div>
    </form>
  </div>

  <?php endif; ?>

</div>

<?php include __DIR__ . '/layout_end.php'; ?>
