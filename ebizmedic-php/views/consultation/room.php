<?php
$roomName   = $roomName;   // passed from controller
$lastMsgId  = !empty($messages) ? (int) end($messages)['id'] : 0;
$csrfToken  = csrf_token();
$displayName = e(Auth::user()['name']);
$peerLabel  = $isDoctor ? e($appt['patient_name']) : 'Dr. ' . e($appt['doctor_name']);
?>
<script src="https://meet.jit.si/external_api.js"></script>

<div class="h-full flex flex-col bg-gray-900">

    <!-- Top bar -->
    <div class="flex-none h-14 bg-gray-800 border-b border-gray-700 flex items-center px-4 gap-3">

        <!-- Logo -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-heart-pulse text-white text-xs"></i>
            </div>
            <span class="text-white font-bold text-sm hidden sm:inline">eBizMedic</span>
        </div>
        <div class="h-5 w-px bg-gray-600 hidden sm:block flex-shrink-0"></div>

        <!-- Session info -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <span class="w-2 h-2 bg-green-400 rounded-full flex-shrink-0 animate-pulse"></span>
            <span class="text-gray-200 text-sm font-medium truncate">
                <?= $isDoctor ? 'Consulting: ' . e($appt['patient_name']) : 'Dr. ' . e($appt['doctor_name']) . ' — ' . e($appt['speciality'] ?? '') ?>
            </span>
        </div>

        <!-- Controls -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Call timer -->
            <span class="text-gray-400 text-xs font-mono hidden md:inline" id="callTimer">00:00</span>

            <!-- Recording button -->
            <button id="recBtn" onclick="toggleRecording()"
                    class="hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-gray-700 hover:bg-red-600 text-gray-300 hover:text-white rounded-lg text-xs font-medium transition-colors">
                <span id="recDot" class="w-2 h-2 rounded-full bg-gray-500"></span>
                <span id="recLabel">Record</span>
            </button>

            <!-- Toggle chat -->
            <button onclick="document.getElementById('chatPanel').classList.toggle('hidden')"
                    class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded-lg text-xs font-medium transition-colors">
                <i class="fa-solid fa-comments mr-1"></i><span class="hidden sm:inline">Chat</span>
            </button>

            <!-- End call -->
            <form method="POST" action="<?= url('consultation/end') ?>" id="endForm">
                <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                <?php if ($isDoctor): ?>
                <button type="button" onclick="confirmEnd()"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-notes-medical mr-1"></i><span class="hidden sm:inline">End &amp; Write Record</span><span class="sm:hidden">End</span>
                </button>
                <?php else: ?>
                <button type="button" onclick="confirmEnd()"
                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition-colors">
                    <i class="fa-solid fa-phone-slash mr-1"></i> Leave
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Recording banner -->
    <div id="recBanner" class="hidden flex-none bg-red-600 text-white text-xs font-semibold text-center py-1.5 flex items-center justify-center gap-2">
        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
        Recording in progress — your screen is being captured locally
    </div>

    <!-- Main area -->
    <div class="flex-1 flex overflow-hidden min-h-0">

        <!-- Jitsi container (IFrame API) -->
        <div id="jitsiContainer" class="flex-1 bg-black min-w-0 min-h-0"></div>

        <!-- Chat panel -->
        <div id="chatPanel" class="w-80 flex-none flex flex-col bg-gray-800 border-l border-gray-700">

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

            <div id="chatMessages" class="flex-1 overflow-y-auto px-3 py-3 space-y-3 min-h-0">
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

            <div class="flex-none p-3 border-t border-gray-700">
                <form id="chatForm" class="flex gap-2">
                    <input type="text" id="chatInput" autocomplete="off" placeholder="Type a message…"
                           class="flex-1 bg-gray-700 border border-gray-600 text-white text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-500"
                           maxlength="1000">
                    <button type="submit"
                            class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center flex-shrink-0 transition-colors">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
                <p class="text-[10px] text-gray-600 mt-1.5 text-center">Visible to doctor &amp; patient only</p>
            </div>
        </div>
    </div>
</div>

<!-- Confirm end dialog -->
<div id="endDialog" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center px-4">
    <div class="bg-gray-800 rounded-2xl border border-gray-700 p-6 w-full max-w-sm shadow-2xl">
        <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-phone-slash text-red-400 text-xl"></i>
        </div>
        <h3 class="text-white font-bold text-center text-lg mb-2">End Consultation?</h3>
        <p class="text-gray-400 text-sm text-center mb-6">
            <?= $isDoctor
                ? 'You will be taken to the medical record form to complete the consultation notes.'
                : 'You will be returned to your appointments page.' ?>
        </p>
        <?php if ($isDoctor && false): /* placeholder — recording info shown in JS */ ?>
        <?php endif; ?>
        <div class="flex gap-3">
            <button onclick="document.getElementById('endDialog').classList.add('hidden')"
                    class="flex-1 py-2.5 border border-gray-600 text-gray-300 rounded-xl text-sm hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button onclick="submitEnd()"
                    class="flex-1 py-2.5 <?= $isDoctor ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700' ?> text-white rounded-xl text-sm font-semibold transition-colors">
                <?= $isDoctor ? 'End & Write Record' : 'Leave Call' ?>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Config ───────────────────────────────────────────────────────────
    var ROOM_NAME  = '<?= $roomName ?>';
    var APPT_ID    = <?= (int)$appt['id'] ?>;
    var MY_ID      = <?= (int)$myId ?>;
    var MY_NAME    = <?= json_encode(Auth::user()['name']) ?>;
    var CSRF       = '<?= $csrfToken ?>';
    var CHAT_URL   = '<?= url('consultation/messages') ?>';
    var SEND_URL   = '<?= url('consultation/chat') ?>';
    var lastId     = <?= $lastMsgId ?>;
    var callStart  = Date.now();

    // ── Jitsi IFrame API ─────────────────────────────────────────────────
    var jitsiApi = new JitsiMeetExternalAPI('meet.jit.si', {
        roomName: ROOM_NAME,
        width:    '100%',
        height:   '100%',
        parentNode: document.getElementById('jitsiContainer'),
        configOverwrite: {
            prejoinPageEnabled:    false,
            disableDeepLinking:    true,
            startWithVideoMuted:   false,
            startWithAudioMuted:   false,
            toolbarButtons: [
                'microphone','camera','desktop','fullscreen',
                'tileview','select-background','fodeviceselection'
            ],
        },
        interfaceConfigOverwrite: {
            SHOW_JITSI_WATERMARK:       false,
            SHOW_WATERMARK_FOR_GUESTS:  false,
            SHOW_BRAND_WATERMARK:       false,
            MOBILE_APP_PROMO:           false,
            HIDE_INVITE_MORE_HEADER:    true,
        },
        userInfo: {
            displayName: MY_NAME,
        },
    });

    // ── Call timer ───────────────────────────────────────────────────────
    var timerEl = document.getElementById('callTimer');
    setInterval(function() {
        var s = Math.floor((Date.now() - callStart) / 1000);
        var m = Math.floor(s / 60); s %= 60;
        timerEl.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }, 1000);

    // ── Chat helpers ─────────────────────────────────────────────────────
    var msgs = document.getElementById('chatMessages');

    function escHtml(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderMsg(msg) {
        var mine = parseInt(msg.sender_id) === MY_ID;
        var time = msg.created_at
            ? msg.created_at.substring(11,16)
            : new Date().toTimeString().substring(0,5);
        var wrap = document.createElement('div');
        wrap.className = 'flex flex-col ' + (mine ? 'items-end' : 'items-start');
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

    msgs.scrollTop = msgs.scrollHeight;

    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var txt = document.getElementById('chatInput').value.trim();
        if (!txt) return;
        renderMsg({ sender_id: MY_ID, sender_name: MY_NAME, message: txt, created_at: null });
        document.getElementById('chatInput').value = '';
        var fd = new FormData();
        fd.append('appointment_id', APPT_ID);
        fd.append('message', txt);
        fd.append('_csrf', CSRF);
        fetch(SEND_URL, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(d){ if (d.id) lastId = Math.max(lastId, d.id); });
    });

    document.getElementById('chatInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
    });

    setInterval(function() {
        fetch(CHAT_URL + '?appointment_id=' + APPT_ID + '&after_id=' + lastId)
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.messages && d.messages.length) {
                    d.messages.forEach(function(m) {
                        if (parseInt(m.sender_id) !== MY_ID) renderMsg(m);
                        lastId = Math.max(lastId, parseInt(m.id));
                    });
                }
            }).catch(function(){});
    }, 4000);

    // ── End call dialog ──────────────────────────────────────────────────
    window.confirmEnd = function() {
        document.getElementById('endDialog').classList.remove('hidden');
    };
    window.submitEnd = function() {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        document.getElementById('endForm').submit();
    };

    function beforeUnloadHandler(e) { e.preventDefault(); e.returnValue = ''; }
    window.addEventListener('beforeunload', beforeUnloadHandler);

    // ── Screen recording (MediaRecorder) ─────────────────────────────────
    var recorder      = null;
    var recordChunks  = [];
    var recording     = false;
    var recBtn        = document.getElementById('recBtn');
    var recDot        = document.getElementById('recDot');
    var recLabel      = document.getElementById('recLabel');
    var recBanner     = document.getElementById('recBanner');

    recBtn.classList.remove('hidden');

    window.toggleRecording = function() {
        if (!recording) {
            navigator.mediaDevices.getDisplayMedia({ video: true, audio: true })
                .then(function(stream) {
                    recordChunks = [];
                    recorder = new MediaRecorder(stream, { mimeType: 'video/webm;codecs=vp9,opus' });
                    recorder.ondataavailable = function(e) {
                        if (e.data && e.data.size > 0) recordChunks.push(e.data);
                    };
                    recorder.onstop = function() {
                        var blob = new Blob(recordChunks, { type: 'video/webm' });
                        var url  = URL.createObjectURL(blob);
                        var a    = document.createElement('a');
                        a.href     = url;
                        a.download = 'ebizmedic-consultation-<?= $appt['id'] ?>-' + Date.now() + '.webm';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        // Reset UI
                        recording = false;
                        recDot.className   = 'w-2 h-2 rounded-full bg-gray-500';
                        recLabel.textContent = 'Record';
                        recBtn.className   = recBtn.className.replace('bg-red-600','bg-gray-700').replace('text-white','text-gray-300');
                        recBanner.classList.add('hidden');
                    };

                    // Stop recording when user stops sharing screen
                    stream.getVideoTracks()[0].addEventListener('ended', function() {
                        if (recording) stopRec();
                    });

                    recorder.start(1000);
                    recording = true;
                    recDot.className     = 'w-2 h-2 rounded-full bg-white animate-pulse';
                    recLabel.textContent = 'Stop';
                    recBtn.className     = recBtn.className.replace('bg-gray-700','bg-red-600').replace('text-gray-300','text-white');
                    recBanner.classList.remove('hidden');
                })
                .catch(function(err) {
                    if (err.name !== 'NotAllowedError') {
                        alert('Could not start recording: ' + err.message);
                    }
                });
        } else {
            stopRec();
        }
    };

    function stopRec() {
        if (recorder && recorder.state !== 'inactive') recorder.stop();
    }
})();
</script>
