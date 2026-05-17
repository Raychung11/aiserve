<?php
$jitsiUrl = 'https://meet.jit.si/' . urlencode($roomName)
          . '#config.prejoinPageEnabled=false'
          . '&config.disableDeepLinking=true'
          . '&config.startWithVideoMuted=false'
          . '&config.startWithAudioMuted=false'
          . '&config.toolbarButtons=["microphone","camera","desktop","fullscreen","fodeviceselection","hangup","tileview","select-background"]'
          . '&userInfo.displayName=' . urlencode(Auth::user()['name']);

$lastMsgId = !empty($messages) ? (int) end($messages)['id'] : 0;
$csrfToken  = csrf_token();
?>
<div class="h-full flex flex-col bg-gray-900">

    <!-- Top bar -->
    <div class="flex-none h-14 bg-gray-800 border-b border-gray-700 flex items-center px-4 gap-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-heart-pulse text-white text-xs"></i>
            </div>
            <span class="text-white font-bold text-sm hidden sm:inline">eBizMedic</span>
        </div>
        <div class="h-5 w-px bg-gray-600 hidden sm:block"></div>
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse flex-shrink-0"></span>
            <span class="text-gray-200 text-sm font-medium truncate">
                <?= $isDoctor ? 'Consulting: ' . e($appt['patient_name']) : 'Dr. ' . e($appt['doctor_name']) . ' — ' . e($appt['speciality'] ?? '') ?>
            </span>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="text-gray-400 text-xs hidden md:inline" id="callTimer">00:00</span>
            <button onclick="document.getElementById('chatPanel').classList.toggle('hidden')"
                    class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded-lg text-xs font-medium transition-colors">
                <i class="fa-solid fa-comments mr-1"></i><span class="hidden sm:inline">Chat</span>
            </button>
            <form method="POST" action="<?= url('consultation/end') ?>" id="endForm">
                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                <?php if ($isDoctor): ?>
                <button type="submit"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-colors">
                    <i class="fa-solid fa-notes-medical mr-1"></i> End & Write Record
                </button>
                <?php else: ?>
                <button type="submit"
                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition-colors">
                    <i class="fa-solid fa-phone-slash mr-1"></i> Leave
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Main area -->
    <div class="flex-1 flex overflow-hidden min-h-0">

        <!-- Jitsi video -->
        <div class="flex-1 bg-black relative min-w-0">
            <iframe id="jitsiFrame"
                    src="<?= e($jitsiUrl) ?>"
                    allow="camera; microphone; fullscreen; display-capture; autoplay"
                    class="w-full h-full border-0"
                    style="min-height:0">
            </iframe>
        </div>

        <!-- Chat panel -->
        <div id="chatPanel" class="w-80 flex-none flex flex-col bg-gray-800 border-l border-gray-700">

            <!-- Chat header -->
            <div class="flex-none px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-comments text-blue-400 text-sm"></i>
                    <span class="text-white font-semibold text-sm">Consultation Chat</span>
                </div>
                <button onclick="document.getElementById('chatPanel').classList.add('hidden')"
                        class="text-gray-500 hover:text-gray-300 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Messages -->
            <div id="chatMessages" class="flex-1 overflow-y-auto px-3 py-3 space-y-3 min-h-0">

                <!-- System message -->
                <div class="text-center">
                    <span class="text-xs text-gray-500 bg-gray-700 px-3 py-1 rounded-full">
                        <?= date('H:i', strtotime($appt['appointment_time'])) ?> — Consultation started
                    </span>
                </div>

                <?php foreach ($messages as $msg): ?>
                <?php $mine = (int)$msg['sender_id'] === $myId; ?>
                <div class="flex flex-col <?= $mine ? 'items-end' : 'items-start' ?>">
                    <?php if (!$mine): ?>
                    <span class="text-xs text-gray-400 mb-1 ml-1"><?= e($msg['sender_name']) ?></span>
                    <?php endif; ?>
                    <div class="max-w-[90%] px-3 py-2 rounded-2xl text-sm <?= $mine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-gray-700 text-gray-200 rounded-bl-sm' ?>">
                        <?= nl2br(e($msg['message'])) ?>
                    </div>
                    <span class="text-[10px] text-gray-500 mt-1 mx-1"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Input -->
            <div class="flex-none p-3 border-t border-gray-700">
                <form id="chatForm" class="flex gap-2">
                    <input type="text" id="chatInput" name="message" autocomplete="off"
                           placeholder="Type a message…"
                           class="flex-1 bg-gray-700 border border-gray-600 text-white text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-500"
                           maxlength="1000">
                    <button type="submit"
                            class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center flex-shrink-0 transition-colors">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
                <p class="text-[10px] text-gray-600 mt-1.5 text-center">Messages are visible to doctor &amp; patient only</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var APPT_ID   = <?= (int)$appt['id'] ?>;
    var MY_ID     = <?= (int)$myId ?>;
    var CSRF      = '<?= $csrfToken ?>';
    var CHAT_URL  = '<?= url('consultation/messages') ?>';
    var SEND_URL  = '<?= url('consultation/chat') ?>';
    var END_URL   = '<?= url('consultation/end') ?>';
    var lastId    = <?= $lastMsgId ?>;
    var msgs      = document.getElementById('chatMessages');
    var chatInput = document.getElementById('chatInput');
    var callStart = Date.now();

    // ── Call timer ───────────────────────────────────────────────────────
    var timerEl = document.getElementById('callTimer');
    setInterval(function() {
        var s = Math.floor((Date.now() - callStart) / 1000);
        var m = Math.floor(s / 60); s %= 60;
        timerEl.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }, 1000);

    // ── Render a message bubble ──────────────────────────────────────────
    function renderMsg(msg) {
        var mine = parseInt(msg.sender_id) === MY_ID;
        var wrap = document.createElement('div');
        wrap.className = 'flex flex-col ' + (mine ? 'items-end' : 'items-start');

        var time = msg.created_at ? msg.created_at.substring(11, 16) : new Date().toTimeString().substring(0,5);

        wrap.innerHTML =
            (!mine ? '<span class="text-xs text-gray-400 mb-1 ml-1">' + escHtml(msg.sender_name) + '</span>' : '') +
            '<div class="max-w-[90%] px-3 py-2 rounded-2xl text-sm ' +
                (mine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-gray-700 text-gray-200 rounded-bl-sm') + '">' +
                escHtml(msg.message).replace(/\n/g,'<br>') +
            '</div>' +
            '<span class="text-[10px] text-gray-500 mt-1 mx-1">' + time + '</span>';

        msgs.appendChild(wrap);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Scroll to bottom on load
    msgs.scrollTop = msgs.scrollHeight;

    // ── Chat form submit ─────────────────────────────────────────────────
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var txt = chatInput.value.trim();
        if (!txt) return;

        // Optimistic render
        renderMsg({ sender_id: MY_ID, sender_name: 'You', message: txt, created_at: null });
        chatInput.value = '';

        var fd = new FormData();
        fd.append('appointment_id', APPT_ID);
        fd.append('message', txt);
        fd.append('_token', CSRF);

        fetch(SEND_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.id) lastId = Math.max(lastId, d.id); });
    });

    // ── Poll for new messages every 4 s ─────────────────────────────────
    function poll() {
        fetch(CHAT_URL + '?appointment_id=' + APPT_ID + '&after_id=' + lastId)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.messages && d.messages.length) {
                    d.messages.forEach(function(m) {
                        if (parseInt(m.sender_id) !== MY_ID) renderMsg(m);
                        lastId = Math.max(lastId, parseInt(m.id));
                    });
                }
            })
            .catch(function() {});
    }
    setInterval(poll, 4000);

    // ── Enter key sends ──────────────────────────────────────────────────
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
    });

    // ── Warn before leaving ──────────────────────────────────────────────
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '';
    });
    document.getElementById('endForm').addEventListener('submit', function() {
        window.removeEventListener('beforeunload', function(){});
    });
})();
</script>
