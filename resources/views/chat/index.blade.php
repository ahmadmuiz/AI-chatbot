<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Chatbot &mdash; {{ $chatSession->title }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #111118;
            --bg-sidebar: #0d0d14;
            --glass: rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.08);
            --accent: #7c3aed;
            --accent-glow: rgba(124,58,237,0.35);
            --accent-light: #a78bfa;
            --text-primary: #f1f0ff;
            --text-secondary: #9994b3;
            --text-muted: #5f5a78;
            --user-bubble: linear-gradient(135deg, #7c3aed, #5b21b6);
            --ai-bubble: rgba(255,255,255,0.05);
            --scrollbar-thumb: rgba(124,58,237,0.4);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(124,58,237,0.12) 0%, transparent 70%);
        }

        /* ── Layout ── */
        .app-shell {
            display: flex;
            height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 280px;
            min-width: 280px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            gap: 0;
            backdrop-filter: blur(20px);
        }

        .sidebar-header {
            padding: 24px 20px 16px;
            border-bottom: 1px solid var(--glass-border);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--user-bubble);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .brand-name {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-primary);
        }

        .brand-sub {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .new-chat-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .new-chat-btn:hover {
            background: rgba(124,58,237,0.15);
            border-color: rgba(124,58,237,0.4);
            color: var(--accent-light);
            box-shadow: 0 0 15px rgba(124,58,237,0.15);
        }

        .sessions-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px 12px;
        }

        .sessions-list::-webkit-scrollbar { width: 4px; }
        .sessions-list::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 2px; }

        .sessions-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 4px 8px 10px;
        }

        .session-item {
            display: flex;
            align-items: center;
            padding: 9px 12px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.15s;
            margin-bottom: 2px;
            gap: 8px;
        }

        .session-item svg { flex-shrink: 0; opacity: 0.5; }

        .session-item:hover {
            background: var(--glass);
            color: var(--text-primary);
        }

        .session-item.active {
            background: rgba(124,58,237,0.18);
            color: var(--accent-light);
            border: 1px solid rgba(124,58,237,0.25);
        }

        .session-item.active svg { opacity: 1; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--glass-border);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .user-email {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .logout-btn {
            margin-left: auto;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .logout-btn:hover { color: #f87171; background: rgba(248,113,113,0.1); }

        /* ── Main Chat ── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 18px 28px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(10,10,15,0.6);
            backdrop-filter: blur(20px);
        }

        .chat-header-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--user-bubble);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .chat-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .chat-subtitle {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Messages */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .messages-area::-webkit-scrollbar { width: 5px; }
        .messages-area::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 3px; }

        /* Welcome state */
        .welcome-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .welcome-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(124,58,237,0.25), rgba(79,70,229,0.25));
            border: 1px solid rgba(124,58,237,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 0 40px var(--accent-glow);
            animation: pulse-glow 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 40px var(--accent-glow); }
            50% { box-shadow: 0 0 60px rgba(124,58,237,0.55); }
        }

        .welcome-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.4px;
        }

        .welcome-subtitle {
            font-size: 14px;
            max-width: 360px;
            line-height: 1.6;
        }

        .welcome-hints {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 8px;
        }

        .hint-chip {
            padding: 8px 16px;
            border-radius: 20px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .hint-chip:hover {
            background: rgba(124,58,237,0.15);
            border-color: rgba(124,58,237,0.4);
            color: var(--accent-light);
        }

        /* Message bubbles */
        .message {
            display: flex;
            gap: 12px;
            max-width: 800px;
            animation: message-in 0.3s ease-out;
        }

        @keyframes message-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user { margin-left: auto; flex-direction: row-reverse; }

        .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .message.user .msg-avatar {
            background: var(--user-bubble);
            color: white;
            box-shadow: 0 0 12px var(--accent-glow);
        }

        .message.assistant .msg-avatar {
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(79,70,229,0.3));
            border: 1px solid rgba(124,58,237,0.3);
            font-size: 15px;
        }

        .msg-bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.65;
            max-width: calc(100% - 44px);
        }

        .message.user .msg-bubble {
            background: var(--user-bubble);
            color: white;
            border-radius: 16px 4px 16px 16px;
            box-shadow: 0 4px 20px var(--accent-glow);
        }

        .message.assistant .msg-bubble {
            background: var(--ai-bubble);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            border-radius: 4px 16px 16px 16px;
        }

        .msg-bubble p { margin-bottom: 8px; }
        .msg-bubble p:last-child { margin-bottom: 0; }

        .msg-bubble pre {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 12px;
            overflow-x: auto;
            margin: 8px 0;
            font-size: 12px;
            line-height: 1.5;
        }

        .msg-bubble code {
            background: rgba(124,58,237,0.2);
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 12px;
        }

        .msg-bubble pre code { background: none; padding: 0; }

        .msg-time {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 4px;
            padding: 0 4px;
        }

        .message.user .msg-time { text-align: right; }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            gap: 12px;
            max-width: 800px;
            animation: message-in 0.3s ease-out;
        }

        .typing-bubble {
            padding: 14px 18px;
            border-radius: 4px 16px 16px 16px;
            background: var(--ai-bubble);
            border: 1px solid var(--glass-border);
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .typing-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent-light);
            animation: typing-bounce 1.4s ease-in-out infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing-bounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }

        /* Input area */
        .input-area {
            padding: 16px 28px 24px;
            background: rgba(10,10,15,0.6);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 10px 12px;
            transition: all 0.2s;
        }

        .input-wrapper:focus-within {
            border-color: rgba(124,58,237,0.5);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.08), 0 0 20px rgba(124,58,237,0.1);
        }

        #message-input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            resize: none;
            max-height: 200px;
            line-height: 1.5;
            padding: 4px 6px;
        }

        #message-input::placeholder { color: var(--text-muted); }

        .send-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--user-bubble);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
            color: white;
            box-shadow: 0 0 15px var(--accent-glow);
        }

        .send-btn:hover { transform: scale(1.05); box-shadow: 0 0 25px var(--accent-glow); }
        .send-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .input-footer {
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 10px;
        }

        /* Error toast */
        .error-toast {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%);
            background: #450a0a;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            animation: message-in 0.3s ease-out;
            z-index: 99;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
        }
    </style>
</head>
<body>
<div class="app-shell">

    {{-- ─── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-icon">✦</div>
                <div>
                    <div class="brand-name">Claude AI</div>
                    <div class="brand-sub">Powered by Anthropic</div>
                </div>
            </div>

            <form action="{{ route('chat.store') }}" method="POST">
                @csrf
                <button type="submit" class="new-chat-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    New chat
                </button>
            </form>
        </div>

        <div class="sessions-list">
            <div class="sessions-label">Conversations</div>
            @foreach($sessions as $session)
                <a href="{{ route('chat.show', $session) }}"
                   class="session-item {{ $session->id === $chatSession->id ? 'active' : '' }}"
                   title="{{ $session->title }}">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    {{ Str::limit($session->title, 32) }}
                </a>
            @endforeach
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div style="min-width:0;flex:1">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-email">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Log out">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ─── Chat Main ─────────────────────────────────────────────────── --}}
    <div class="chat-main">

        <div class="chat-header">
            <div class="chat-header-icon">✦</div>
            <div>
                <div class="chat-title" id="chat-session-title">{{ $chatSession->title }}</div>
                <div class="chat-subtitle">claude-opus-4-5 &middot; AI Assistant</div>
            </div>
        </div>

        <div class="messages-area" id="messages-area">

            @if($messages->isEmpty())
                <div class="welcome-state" id="welcome-state">
                    <div class="welcome-icon">✦</div>
                    <div class="welcome-title">How can I help you today?</div>
                    <div class="welcome-subtitle">
                        I'm Claude, your AI assistant. Ask me anything — from coding to creative writing, analysis to casual conversation.
                    </div>
                    <div class="welcome-hints">
                        <div class="hint-chip" onclick="fillInput('Explain quantum computing in simple terms')">Explain quantum computing</div>
                        <div class="hint-chip" onclick="fillInput('Write a Python function to sort a list of dictionaries')">Write Python code</div>
                        <div class="hint-chip" onclick="fillInput('What are some productivity tips for developers?')">Productivity tips</div>
                        <div class="hint-chip" onclick="fillInput('Help me write a professional email')">Write an email</div>
                    </div>
                </div>
            @else
                @foreach($messages as $msg)
                    <div class="message {{ $msg->role }}">
                        <div class="msg-avatar">
                            @if($msg->role === 'user')
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @else
                                ✦
                            @endif
                        </div>
                        <div>
                            <div class="msg-bubble">{!! nl2br(e($msg->content)) !!}</div>
                            <div class="msg-time">{{ $msg->created_at->format('g:i A') }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>

        <div class="input-area">
            <div class="input-wrapper">
                <textarea
                    id="message-input"
                    placeholder="Message Claude…"
                    rows="1"
                    autofocus
                ></textarea>
                <button class="send-btn" id="send-btn" onclick="sendMessage()" title="Send message">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
            <div class="input-footer">Claude can make mistakes. Consider checking important information.</div>
        </div>
    </div>

</div>

<script>
    const SESSION_ID   = {{ $chatSession->id }};
    const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
    const messagesArea = document.getElementById('messages-area');
    const input        = document.getElementById('message-input');
    const sendBtn      = document.getElementById('send-btn');
    const welcomeState = document.getElementById('welcome-state');

    // Auto-resize textarea
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 200) + 'px';
    });

    // Send on Enter (Shift+Enter for newline)
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function fillInput(text) {
        input.value = text;
        input.focus();
        input.dispatchEvent(new Event('input'));
    }

    function scrollToBottom() {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function formatTime() {
        return new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    function addMessage(role, content, time) {
        // Remove welcome state if present
        if (welcomeState) welcomeState.remove();

        const isUser   = role === 'user';
        const name     = isUser ? '{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}' : '✦';
        const msgEl    = document.createElement('div');
        msgEl.className = `message ${role}`;

        // Escape HTML for display
        const escaped = content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');

        msgEl.innerHTML = `
            <div class="msg-avatar">${name}</div>
            <div>
                <div class="msg-bubble">${escaped}</div>
                <div class="msg-time">${time}</div>
            </div>
        `;

        messagesArea.appendChild(msgEl);
        scrollToBottom();
        return msgEl;
    }

    function addTypingIndicator() {
        const el = document.createElement('div');
        el.className = 'typing-indicator';
        el.id = 'typing-indicator';
        el.innerHTML = `
            <div class="msg-avatar" style="width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(124,58,237,0.3),rgba(79,70,229,0.3));border:1px solid rgba(124,58,237,0.3);font-size:15px;margin-top:2px;">✦</div>
            <div class="typing-bubble">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>`;
        messagesArea.appendChild(el);
        scrollToBottom();
        return el;
    }

    function showError(msg) {
        const toast = document.createElement('div');
        toast.className = 'error-toast';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        // Clear input and disable
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;

        const sentTime = formatTime();
        addMessage('user', text, sentTime);

        const typing = addTypingIndicator();

        try {
            const res = await fetch(`/chat/${SESSION_ID}/messages`, {
                method : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN' : CSRF_TOKEN,
                    'Accept'       : 'application/json',
                },
                body: JSON.stringify({ message: text }),
            });

            typing.remove();

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                showError(err.message || 'Something went wrong. Please try again.');
                sendBtn.disabled = false;
                return;
            }

            const data = await res.json();
            addMessage('assistant', data.message, formatTime());

            // Update session title in sidebar
            if (data.session_title) {
                const titleEl = document.getElementById('chat-session-title');
                if (titleEl) titleEl.textContent = data.session_title;

                const sidebarLink = document.querySelector(`.session-item.active`);
                if (sidebarLink) sidebarLink.textContent = data.session_title.substring(0, 32);
            }

        } catch (e) {
            typing.remove();
            showError('Network error. Check your connection and try again.');
        }

        sendBtn.disabled = false;
        input.focus();
    }

    // Initial scroll
    scrollToBottom();
</script>
</body>
</html>
