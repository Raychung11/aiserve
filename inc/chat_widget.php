<?php
declare(strict_types=1);
?>
<div id="aiChatWidget" class="ai-chat-widget">
    <div id="aiChatPanel" class="ai-chat-panel">
        <div class="ai-chat-header">
            <div>
                <strong>AiServe Assistant</strong>
                <div class="ai-chat-sub">Ask about solutions, demos, industries, or AI customer service</div>
            </div>
            <button id="aiChatClose" class="ai-chat-close" type="button" aria-label="Close chat">×</button>
        </div>

        <div id="aiChatMessages" class="ai-chat-messages">
            <div class="ai-msg ai-msg-bot">
                Hello, I’m the AiServe Assistant. I can help you explore our services, demos, industries, and AI customer service solutions.
                <div class="ai-suggestions">
                    <button type="button" class="ai-suggestion" data-question="What industries do you support?">Industries</button>
                    <button type="button" class="ai-suggestion" data-question="Show me your demos">Demos</button>
                    <button type="button" class="ai-suggestion" data-question="How does your AI customer service work?">AI Customer Service</button>
                </div>
            </div>
        </div>

        <form id="aiChatForm" class="ai-chat-form">
            <input id="aiChatInput" type="text" placeholder="Type your question..." autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>

    <button id="aiChatToggle" class="ai-chat-toggle" type="button" aria-label="Open chat">Chat</button>
</div>

<style>
.ai-chat-widget{
    position:fixed;
    right:20px;
    bottom:20px;
    z-index:9999;
}

.ai-chat-toggle{
    min-height:52px;
    padding:0 18px;
    border:none;
    border-radius:999px;
    cursor:pointer;
    font-weight:700;
    color:#fff;
    background:linear-gradient(135deg,#6d28d9,#8b5cf6);
    box-shadow:0 16px 34px rgba(109,40,217,.22);
    position:relative;
    z-index:2;
}

.ai-chat-panel{
    position:absolute;
    right:0;
    bottom:64px;
    width:360px;
    max-width:calc(100vw - 24px);
    height:520px;
    max-height:70vh;
    border:1px solid #e7defc;
    border-radius:20px;
    background:#fff;
    overflow:hidden;
    box-shadow:0 22px 60px rgba(109,40,217,.16);
    display:none;
    flex-direction:column;
}

.ai-chat-panel.open{
    display:flex;
}

.ai-chat-header{
    padding:14px 16px;
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    border-bottom:1px solid #eee7ff;
    background:#faf8ff;
}

.ai-chat-sub{
    font-size:12px;
    color:#6f6487;
    margin-top:2px;
}

.ai-chat-close{
    border:none;
    background:transparent;
    font-size:24px;
    line-height:1;
    cursor:pointer;
    color:#6f6487;
    position:relative;
    z-index:3;
    padding:0;
}

.ai-chat-messages{
    flex:1;
    overflow:auto;
    padding:14px;
    background:#fff;
}

.ai-msg{
    max-width:88%;
    padding:12px 14px;
    border-radius:16px;
    margin-bottom:10px;
    line-height:1.65;
    white-space:pre-wrap;
}

.ai-msg-user{
    margin-left:auto;
    background:#6d28d9;
    color:#fff;
}

.ai-msg-bot{
    background:#f5f0ff;
    color:#1f1534;
}

.ai-chat-form{
    display:flex;
    gap:10px;
    padding:12px;
    border-top:1px solid #eee7ff;
    background:#fff;
}

.ai-chat-form input{
    flex:1;
    min-height:44px;
    border:1px solid #ddd6fe;
    border-radius:12px;
    padding:0 12px;
}

.ai-chat-form button{
    min-height:44px;
    padding:0 14px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-weight:700;
    color:#fff;
    background:linear-gradient(135deg,#6d28d9,#8b5cf6);
}

.ai-suggestions{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:10px;
}

.ai-suggestion{
    border:1px solid #ddd6fe;
    background:#fff;
    color:#6d28d9;
    border-radius:999px;
    padding:8px 10px;
    font-size:12px;
    cursor:pointer;
}

.ai-source-list{
    margin-top:8px;
    font-size:12px;
}

.ai-source-list a{
    color:#6d28d9;
}

@media (max-width: 640px){
    .ai-chat-widget{
        right:12px;
        bottom:12px;
        left:12px;
    }

    .ai-chat-panel{
        right:0;
        left:0;
        width:auto;
        height:65vh;
        max-width:none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('aiChatToggle');
    const panel = document.getElementById('aiChatPanel');
    const closeBtn = document.getElementById('aiChatClose');
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiChatInput');
    const messages = document.getElementById('aiChatMessages');

    if (!toggle || !panel || !closeBtn || !form || !input || !messages) {
        return;
    }

    function openPanel() {
        panel.classList.add('open');
    }

    function closePanel() {
        panel.classList.remove('open');
    }

    function appendMessage(text, who, sources) {
        const div = document.createElement('div');
        div.className = 'ai-msg ' + (who === 'user' ? 'ai-msg-user' : 'ai-msg-bot');
        div.textContent = text;

        if (who === 'bot' && Array.isArray(sources) && sources.length) {
            const src = document.createElement('div');
            src.className = 'ai-source-list';
            src.innerHTML = '<strong>Sources:</strong><br>' + sources.map(s =>
                `<a href="${s.url}" target="_blank" rel="noopener">${s.title || s.url}</a>`
            ).join('<br>');
            div.appendChild(src);
        }

        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    async function sendMessage(text) {
        appendMessage(text, 'user');
        appendMessage('Let me check that for you...', 'bot');
        const thinkingNode = messages.lastElementChild;

        try {
            const res = await fetch('/api/chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: text})
            });

            const data = await res.json();

            if (!data.ok) {
                thinkingNode.textContent = 'I’m sorry, I’m unable to respond properly at the moment. Please try again shortly or contact our team through the website contact page.';
                return;
            }

            thinkingNode.textContent = data.reply || 'I’m sorry, I’m unable to respond properly at the moment. Please contact our team through the website contact page.';
            if (Array.isArray(data.sources) && data.sources.length) {
                const src = document.createElement('div');
                src.className = 'ai-source-list';
                src.innerHTML = '<strong>Sources:</strong><br>' + data.sources.map(s =>
                    `<a href="${s.url}" target="_blank" rel="noopener">${s.title || s.url}</a>`
                ).join('<br>');
                thinkingNode.appendChild(src);
            }
        } catch (err) {
            thinkingNode.textContent = 'I’m sorry, the chat service is temporarily unavailable. Please try again later or contact our team directly.';
        }

        messages.scrollTop = messages.scrollHeight;
    }

    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        panel.classList.toggle('open');
    });

    closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closePanel();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        openPanel();
        sendMessage(text);
    });

    document.querySelectorAll('.ai-suggestion').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const q = btn.getAttribute('data-question') || '';
            if (q) {
                openPanel();
                sendMessage(q);
            }
        });
    });
});
</script>