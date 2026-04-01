<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
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

    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/atom-one-dark.min.css">

    <style>
        :root,
        html[data-theme="dark"] {
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
            --modal-bg: #111118;
            --code-bg: rgba(0,0,0,0.6);
            --header-area-bg: rgba(10,10,15,0.85);
            --body-gradient: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(124,58,237,0.12) 0%, transparent 70%);
        }

        html[data-theme="light"] {
            --bg-primary: #f5f3ff;
            --bg-secondary: #ede9ff;
            --bg-sidebar: #e8e2ff;
            --glass: rgba(0,0,0,0.04);
            --glass-border: rgba(0,0,0,0.1);
            --accent: #7c3aed;
            --accent-glow: rgba(124,58,237,0.15);
            --accent-light: #6d28d9;
            --text-primary: #1a1228;
            --text-secondary: #4b4070;
            --text-muted: #8b7fb0;
            --user-bubble: linear-gradient(135deg, #7c3aed, #5b21b6);
            --ai-bubble: rgba(0,0,0,0.05);
            --scrollbar-thumb: rgba(124,58,237,0.25);
            --modal-bg: #ffffff;
            --code-bg: #1e1b2e;
            --header-area-bg: rgba(240,236,255,0.92);
            --body-gradient: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(124,58,237,0.08) 0%, transparent 70%);
        }

        html[data-theme="midnight"] {
            --bg-primary: #000000;
            --bg-secondary: #060608;
            --bg-sidebar: #030305;
            --glass: rgba(255,255,255,0.03);
            --glass-border: rgba(255,255,255,0.06);
            --accent: #8b5cf6;
            --accent-glow: rgba(139,92,246,0.45);
            --accent-light: #c4b5fd;
            --text-primary: #f0ebff;
            --text-secondary: #7c71a5;
            --text-muted: #3d3558;
            --user-bubble: linear-gradient(135deg, #8b5cf6, #6d28d9);
            --ai-bubble: rgba(255,255,255,0.03);
            --scrollbar-thumb: rgba(139,92,246,0.35);
            --modal-bg: #0a0a12;
            --code-bg: rgba(0,0,0,0.85);
            --header-area-bg: rgba(0,0,0,0.9);
            --body-gradient: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(139,92,246,0.15) 0%, transparent 70%);
        }

        html[data-theme="ocean"] {
            --bg-primary: #070e1c;
            --bg-secondary: #0b1428;
            --bg-sidebar: #060c18;
            --glass: rgba(255,255,255,0.04);
            --glass-border: rgba(56,189,248,0.12);
            --accent: #0ea5e9;
            --accent-glow: rgba(14,165,233,0.35);
            --accent-light: #38bdf8;
            --text-primary: #e0f2ff;
            --text-secondary: #6ea8c8;
            --text-muted: #2e5a72;
            --user-bubble: linear-gradient(135deg, #0ea5e9, #0369a1);
            --ai-bubble: rgba(255,255,255,0.04);
            --scrollbar-thumb: rgba(14,165,233,0.35);
            --modal-bg: #0b1428;
            --code-bg: rgba(0,0,0,0.6);
            --header-area-bg: rgba(7,14,28,0.88);
            --body-gradient: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(14,165,233,0.12) 0%, transparent 70%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            background-image: var(--body-gradient);
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
            background: var(--header-area-bg);
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

        .message > div:not(.msg-avatar) {
            min-width: 0;
            flex: 1;
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
            word-break: break-word;
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

        /* ── Markdown & Code Rendering ── */
        .msg-bubble {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .msg-bubble p {
            margin-bottom: 12px;
            line-height: 1.65;
        }

        .msg-bubble p:last-child {
            margin-bottom: 0;
        }

        .msg-bubble h1, .msg-bubble h2, .msg-bubble h3, .msg-bubble h4, .msg-bubble h5, .msg-bubble h6 {
            margin-top: 16px;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--accent-light);
        }

        .msg-bubble h1 { font-size: 22px; }
        .msg-bubble h2 { font-size: 20px; }
        .msg-bubble h3 { font-size: 18px; }
        .msg-bubble h4 { font-size: 16px; }
        .msg-bubble h5 { font-size: 14px; }
        .msg-bubble h6 { font-size: 13px; }

        .msg-bubble ul, .msg-bubble ol {
            margin: 12px 0;
            margin-left: 24px;
            line-height: 1.8;
        }

        .msg-bubble li {
            margin-bottom: 6px;
        }

        .msg-bubble blockquote {
            border-left: 3px solid var(--accent);
            padding-left: 12px;
            margin: 12px 0;
            color: var(--text-secondary);
            font-style: italic;
        }

        .msg-bubble table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
        }

        .msg-bubble table th,
        .msg-bubble table td {
            border: 1px solid var(--glass-border);
            padding: 8px 12px;
            text-align: left;
        }

        .msg-bubble table th {
            background: rgba(124,58,237,0.15);
            font-weight: 600;
            color: var(--accent-light);
        }

        .msg-bubble table tr:nth-child(even) {
            background: rgba(124,58,237,0.05);
        }

        .msg-bubble strong, .msg-bubble b {
            font-weight: 600;
            color: var(--accent-light);
        }

        .msg-bubble em, .msg-bubble i {
            font-style: italic;
            color: var(--text-secondary);
        }

        .msg-bubble a {
            color: var(--accent-light);
            text-decoration: underline;
            transition: all 0.2s;
        }

        .msg-bubble a:hover {
            color: #ddd6fe;
            text-decoration-thickness: 2px;
        }

        .msg-bubble hr {
            border: none;
            border-top: 1px solid var(--glass-border);
            margin: 16px 0;
        }

        /* Code blocks */
        .msg-bubble pre {
            background: var(--code-bg);
            border: 1px solid rgba(124,58,237,0.2);
            border-radius: 8px;
            padding: 14px;
            overflow-x: auto;
            margin: 12px 0;
            font-size: 12px;
            line-height: 1.6;
            position: relative;
        }

        .msg-bubble pre code {
            background: none;
            padding: 0;
            color: var(--text-primary);
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
        }

        /* Inline code */
        .msg-bubble code:not(pre code) {
            background: rgba(124,58,237,0.25);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 13px;
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
            color: var(--accent-light);
        }

        /* Syntax highlighting */
        .hljs {
            background: none;
            color: inherit;
        }

        .hljs-string { color: #a6e22e; }
        .hljs-number { color: #ae81ff; }
        .hljs-literal { color: #ae81ff; }
        .hljs-attr { color: #a6e22e; }
        .hljs-title { color: #75b5ff; }
        .hljs-function { color: #75b5ff; }
        .hljs-params { color: var(--text-primary); }
        .hljs-built_in { color: #66d9ef; }
        .hljs-keyword { color: #f92672; }
        .hljs-comment { color: #75715e; }
        .hljs-variable { color: #a6e22e; }

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
            background: var(--header-area-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
        }

        .attachments-preview {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            max-height: 120px;
            overflow-y: auto;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(124,58,237,0.15);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 8px;
            font-size: 12px;
            color: var(--accent-light);
        }

        .attachment-item .remove-btn {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .attachment-item .remove-btn:hover { opacity: 1; }

        .attachment-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
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
            min-height: 72px;
            max-height: 240px;
            line-height: 1.6;
            padding: 4px 6px;
            align-self: stretch;
        }

        #message-input::placeholder { color: var(--text-muted); }

        .file-btn, .send-btn {
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

        .file-btn:hover, .send-btn:hover { transform: scale(1.05); box-shadow: 0 0 25px var(--accent-glow); }
        .file-btn:disabled, .send-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        #file-input {
            display: none;
        }

        /* File attachment display in messages */
        .msg-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .attachment-preview {
            max-width: 100%;
            border-radius: 8px;
            overflow: hidden;
        }

        .attachment-preview img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 8px;
            display: block;
        }

        .attachment-doc {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
        }

        .attachment-doc:hover {
            background: rgba(255,255,255,0.08);
            color: var(--text-primary);
        }

        .model-switcher {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .model-switcher-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .model-pills {
            display: flex;
            gap: 6px;
        }

        .model-pill {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--glass-border);
            background: var(--glass);
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .model-pill:hover {
            border-color: rgba(124,58,237,0.4);
            color: var(--text-secondary);
        }

        .model-pill.active-claude {
            background: rgba(124,58,237,0.2);
            border-color: rgba(124,58,237,0.5);
            color: #a78bfa;
        }

        .model-pill.active-gemini {
            background: rgba(16,185,129,0.15);
            border-color: rgba(16,185,129,0.4);
            color: #6ee7b7;
        }

        .model-pill.switching {
            opacity: 0.5;
            pointer-events: none;
        }

        .input-footer {
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 8px;
        }

        /* ── Provider Modal ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal-card {
            background: var(--modal-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.6), 0 0 60px rgba(124,58,237,0.1);
            transform: translateY(12px);
            transition: transform 0.2s;
        }

        .modal-overlay.open .modal-card {
            transform: translateY(0);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .modal-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .provider-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .provider-option {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }

        .provider-option:hover {
            background: rgba(124,58,237,0.1);
            border-color: rgba(124,58,237,0.4);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(124,58,237,0.15);
        }

        .provider-option-icon {
            font-size: 26px;
            line-height: 1;
        }

        .provider-option-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .provider-option-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.15s;
        }

        .modal-close:hover { background: rgba(248,113,113,0.1); color: #f87171; border-color: #f87171; }

        .session-provider-badge {
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 600;
            margin-left: auto;
            flex-shrink: 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-claude { background: rgba(124,58,237,0.2); color: #a78bfa; border: 1px solid rgba(124,58,237,0.25); }
        .badge-gemini { background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); }

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

        /* ── Light theme specific overrides ── */
        html[data-theme="light"] .message.user .msg-bubble {
            color: white;
        }

        html[data-theme="light"] .msg-bubble {
            color: var(--text-primary);
        }

        html[data-theme="light"] .msg-bubble code:not(pre code) {
            background: rgba(124,58,237,0.12);
            color: #6d28d9;
            border-color: rgba(124,58,237,0.2);
        }

        html[data-theme="light"] .msg-bubble pre code {
            color: #e0d7ff;
        }

        html[data-theme="light"] .error-toast {
            background: #fff0f0;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        html[data-theme="light"] .modal-overlay {
            background: rgba(0,0,0,0.35);
        }

        /* ── Theme Palette ── */
        .theme-palette {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 12px;
            border-bottom: 1px solid var(--glass-border);
        }

        .theme-palette-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-right: 2px;
        }

        .theme-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            flex-shrink: 0;
            position: relative;
        }

        .theme-swatch:hover {
            transform: scale(1.2);
        }

        .theme-swatch.active {
            border-color: var(--accent-light);
            box-shadow: 0 0 0 2px var(--accent-glow);
        }

        .theme-swatch[data-swatch="dark"]     { background: #0a0a0f; }
        .theme-swatch[data-swatch="light"]    { background: linear-gradient(135deg, #f5f3ff, #c4b5fd); }
        .theme-swatch[data-swatch="midnight"] { background: #000000; border-color: rgba(255,255,255,0.15); }
        .theme-swatch[data-swatch="ocean"]    { background: linear-gradient(135deg, #070e1c, #0ea5e9); }

        .theme-swatch[data-swatch="midnight"].active { border-color: #c4b5fd; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
        }

        /* ── File Download Chips ── */
        .msg-file-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .file-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px 5px 8px;
            background: rgba(124,58,237,0.12);
            border: 1px solid rgba(124,58,237,0.28);
            border-radius: 20px;
            font-size: 11px;
            color: var(--accent-light);
            text-decoration: none;
            transition: all 0.18s;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-chip:hover {
            background: rgba(124,58,237,0.25);
            border-color: rgba(124,58,237,0.5);
            color: #ddd6fe;
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(124,58,237,0.2);
        }

        .file-chip-icon { font-size: 13px; flex-shrink: 0; }
        .file-chip-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-chip-size { opacity: 0.6; flex-shrink: 0; font-size: 10px; }

        /* ── Memory Modal ── */
        .memory-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 300;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .memory-modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .memory-modal-card {
            background: var(--modal-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 640px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.6), 0 0 60px rgba(124,58,237,0.1);
            display: flex;
            flex-direction: column;
            gap: 16px;
            transform: translateY(12px);
            transition: transform 0.2s;
            position: relative;
        }

        .memory-modal-overlay.open .memory-modal-card {
            transform: translateY(0);
        }

        .memory-modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .memory-modal-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(79,70,229,0.3));
            border: 1px solid rgba(124,58,237,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        #memory-textarea {
            width: 100%;
            min-height: 220px;
            max-height: 400px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            line-height: 1.65;
            padding: 14px 16px;
            resize: vertical;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        #memory-textarea:focus {
            border-color: rgba(124,58,237,0.5);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.08);
        }

        .memory-save-btn {
            align-self: flex-end;
            padding: 9px 22px;
            border-radius: 10px;
            background: var(--user-bubble);
            color: white;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .memory-save-btn:hover { transform: translateY(-1px); box-shadow: 0 0 24px var(--accent-glow); }

        .memory-hint {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.6;
            padding: 10px 14px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
        }

        /* ── Copy Button ── */
        .msg-wrapper {
            position: relative;
        }

        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s, background 0.2s, color 0.2s, border-color 0.2s;
            padding: 0;
            flex-shrink: 0;
        }

        .msg-wrapper:hover .copy-btn {
            opacity: 1;
        }

        .copy-btn:hover {
            background: rgba(124,58,237,0.2);
            color: var(--accent-light);
            border-color: rgba(124,58,237,0.4);
        }

        .copy-btn.copied {
            background: rgba(16,185,129,0.15);
            color: #6ee7b7;
            border-color: rgba(16,185,129,0.3);
            opacity: 1;
        }

        /* ── Export Dropdown ── */
        .export-wrapper {
            position: absolute;
            top: 8px;
            right: 42px; /* sit left of the copy button */
        }

        .export-btn {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s, background 0.2s, color 0.2s, border-color 0.2s;
            padding: 0;
            font-size: 12px;
        }

        .msg-wrapper:hover .export-btn { opacity: 1; }

        .export-btn:hover {
            background: rgba(124,58,237,0.2);
            color: var(--accent-light);
            border-color: rgba(124,58,237,0.4);
        }

        .export-dropdown {
            position: absolute;
            top: 34px;
            right: 0;
            background: var(--modal-bg);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 6px;
            display: none;
            flex-direction: column;
            gap: 3px;
            min-width: 150px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
            z-index: 50;
        }

        .export-wrapper.open .export-dropdown { display: flex; }

        .export-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
            border: none;
            background: none;
            text-align: left;
            width: 100%;
            font-family: 'Inter', sans-serif;
            transition: all 0.15s;
            text-decoration: none;
        }

        .export-option:hover {
            background: rgba(124,58,237,0.12);
            color: var(--accent-light);
        }

        /* ── File size warning ── */
        .attachment-item.oversized {
            background: rgba(239,68,68,0.12);
            border-color: rgba(239,68,68,0.4);
            color: #fca5a5;
        }

        .attachment-item.oversized .size-warning {
            font-size: 10px;
            color: #f87171;
            font-weight: 600;
        }

        .upload-warning-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            font-size: 12px;
            color: #fca5a5;
            margin-bottom: 8px;
            animation: message-in 0.2s ease-out;
        }

        /* ── File count badge ── */
        .file-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 8px var(--accent-glow);
        }

        .file-btn { position: relative; }
    </style>
</head>
<body>
<div class="app-shell">

    {{-- ─── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="sidebar">
        <div class="theme-palette">
            <span class="theme-palette-label">Theme</span>
            <div class="theme-swatch active" data-swatch="dark"    onclick="setTheme('dark')"     title="Dark"></div>
            <div class="theme-swatch"        data-swatch="light"   onclick="setTheme('light')"    title="Light"></div>
            <div class="theme-swatch"        data-swatch="midnight" onclick="setTheme('midnight')" title="Midnight"></div>
            <div class="theme-swatch"        data-swatch="ocean"   onclick="setTheme('ocean')"    title="Ocean"></div>
        </div>

        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-icon">✦</div>
                <div>
                    <div class="brand-name">Claude AI</div>
                    <div class="brand-sub">Powered by Anthropic</div>
                </div>
            </div>

            <button type="button" class="new-chat-btn" onclick="openProviderModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                New chat
            </button>
        </div>

        <div class="sessions-list">
            <div class="sessions-label">Conversations</div>
            @foreach($sessions as $session)
                <a href="{{ route('chat.show', $session) }}"
                   class="session-item {{ $session->id === $chatSession->id ? 'active' : '' }}"
                   title="{{ $session->title }}">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">{{ Str::limit($session->title, 24) }}</span>
                    <span class="session-provider-badge {{ $session->ai_provider === 'gemini' ? 'badge-gemini' : 'badge-claude' }}">
                        {{ $session->ai_provider === 'gemini' ? 'Gemini' : 'Claude' }}
                    </span>
                </a>
            @endforeach
        </div>

        <div class="sidebar-footer">
            @if(auth()->user()->is_admin)
            <a href="{{ route('admin.users.index') }}" style="
                display:flex;align-items:center;gap:8px;
                padding:8px 10px;border-radius:8px;
                text-decoration:none;color:#a78bfa;
                font-size:12px;font-weight:500;
                background:rgba(124,58,237,0.1);
                border:1px solid rgba(124,58,237,0.2);
                margin-bottom:8px;
                transition:all 0.15s;
            " onmouseover="this.style.background='rgba(124,58,237,0.2)'" onmouseout="this.style.background='rgba(124,58,237,0.1)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                </svg>
                Admin Panel
            </a>
            @endif
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
            <div style="flex:1;min-width:0">
                <div class="chat-title" id="chat-session-title">{{ $chatSession->title }}</div>
                <div class="chat-subtitle">
                    @if($chatSession->ai_provider === 'gemini')
                        <span title="Using Google Gemini">🌟 Gemini</span>
                    @else
                        <span title="Using Claude via AWS Bedrock">🧠 Claude</span>
                    @endif
                    &middot; AI Assistant
                </div>
            </div>
            {{-- Memory / System Prompt button --}}
            <button id="memory-btn" onclick="openMemoryModal()" title="Configure AI Memory / System Prompt" style="
                display:flex;align-items:center;gap:6px;
                padding:7px 14px;border-radius:10px;
                background:var(--glass);border:1px solid var(--glass-border);
                color:var(--text-secondary);font-size:12px;font-weight:500;
                cursor:pointer;transition:all 0.2s;font-family:'Inter',sans-serif;
                white-space:nowrap;
                {{ $chatSession->system_prompt ? 'border-color:rgba(124,58,237,0.4);color:var(--accent-light);background:rgba(124,58,237,0.1);' : '' }}
            " onmouseover="this.style.background='rgba(124,58,237,0.15)';this.style.borderColor='rgba(124,58,237,0.4)';this.style.color='var(--accent-light)'" onmouseout="resetMemoryBtnStyle(this)">
                🧠
                <span>Memory</span>
                @if($chatSession->system_prompt)
                    <span style="width:7px;height:7px;border-radius:50%;background:#a78bfa;display:inline-block"></span>
                @endif
            </button>
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
                            @if($msg->role === 'user')
                                <div class="msg-bubble">
                                    {!! nl2br(e($msg->content)) !!}
                                </div>
                            @else
                                <div class="msg-wrapper">
                                    <div class="msg-bubble">
                                        {!! \App\Services\MarkdownRenderer::render($msg->content) !!}
                                    </div>
                                    {{-- Copy button --}}
                                    <button class="copy-btn" title="Copy response" onclick="copyMessage(this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                        </svg>
                                    </button>
                                    {{-- Export dropdown --}}
                                    <div class="export-wrapper" id="export-{{ $msg->id }}">
                                        <button class="export-btn" title="Export as document"
                                            onclick="toggleExport('export-{{ $msg->id }}')">
                                            ↓
                                        </button>
                                        <div class="export-dropdown">
                                            <a class="export-option" href="{{ route('chat.message.export', [$msg->id, 'txt']) }}" download>📄 Plain Text (.txt)</a>
                                            <a class="export-option" href="{{ route('chat.message.export', [$msg->id, 'docx']) }}" download>📝 Word Document (.docx)</a>
                                            <a class="export-option" href="{{ route('chat.message.export', [$msg->id, 'pdf']) }}" download>📕 PDF Document (.pdf)</a>
                                            <a class="export-option" href="{{ route('chat.message.export', [$msg->id, 'xlsx']) }}" download>📊 Excel Spreadsheet (.xlsx)</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($msg->attachments->isNotEmpty())
                                <div class="msg-file-chips">
                                    @foreach($msg->attachments as $attachment)
                                        @php
                                            $icon = match(true) {
                                                str_starts_with($attachment->mime_type, 'image/')       => '🖼️',
                                                $attachment->mime_type === 'application/pdf'            => '📕',
                                                str_contains($attachment->mime_type, 'word') || str_contains($attachment->mime_type, 'document') => '📝',
                                                str_contains($attachment->mime_type, 'sheet') || str_contains($attachment->mime_type, 'csv')    => '📊',
                                                str_contains($attachment->mime_type, 'json')            => '📋',
                                                default => '📄',
                                            };
                                            $sizeFormatted = $attachment->file_size < 1048576
                                                ? round($attachment->file_size / 1024, 1) . ' KB'
                                                : round($attachment->file_size / 1048576, 1) . ' MB';
                                        @endphp
                                        <a href="{{ route('chat.attachment.download', $attachment->id) }}"
                                           class="file-chip"
                                           title="Download {{ $attachment->original_filename }}"
                                           download="{{ $attachment->original_filename }}">
                                            <span class="file-chip-icon">{{ $icon }}</span>
                                            <span class="file-chip-name">{{ $attachment->original_filename }}</span>
                                            <span class="file-chip-size">({{ $sizeFormatted }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="msg-time">{{ $msg->created_at->format('g:i A') }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>

        <div class="input-area">
            <div class="attachments-preview" id="attachments-preview"></div>
            <div class="input-wrapper">
                <textarea
                    id="message-input"
                    placeholder="Message {{ $chatSession->ai_provider === 'gemini' ? 'Gemini' : 'Claude' }}…"
                    rows="3"
                    autofocus
                ></textarea>
                <div style="display:flex;flex-direction:column;gap:6px;align-self:flex-end">
                    <button class="file-btn" id="file-btn" onclick="triggerFileInput()" title="Attach files">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                        </svg>
                    </button>
                    <button class="send-btn" id="send-btn" onclick="sendMessage()" title="Send message">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </div>
            </div>
            <input type="file" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt,.csv,.xlsx,.json,.pptx,.odt">
            <div id="upload-hint" style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                📎 Up to 5 files &nbsp;·&nbsp; Max {{ round(intval(ini_get('upload_max_filesize')) ?: 50) }} MB each &nbsp;·&nbsp; Drop files anywhere
            </div>

            <div class="model-switcher">
                <span class="model-switcher-label">Model:</span>
                <div class="model-pills">
                    <button type="button"
                        id="pill-claude"
                        class="model-pill {{ $chatSession->ai_provider !== 'gemini' ? 'active-claude' : '' }}"
                        onclick="switchModel('claude')">
                        🧠 Claude
                    </button>
                    <button type="button"
                        id="pill-gemini"
                        class="model-pill {{ $chatSession->ai_provider === 'gemini' ? 'active-gemini' : '' }}"
                        onclick="switchModel('gemini')">
                        🌟 Gemini
                    </button>
                </div>
            </div>

            <div class="input-footer" id="input-footer">
                {{ $chatSession->ai_provider === 'gemini' ? 'Gemini' : 'Claude' }} can make mistakes. Consider checking important information.
            </div>
        </div>
    </div>

</div>

{{-- ─── Provider Selection Modal ──────────────────────────────────── --}}
<div class="modal-overlay" id="provider-modal" onclick="handleModalClick(event)">
    <div class="modal-card" style="position:relative">
        <button class="modal-close" onclick="closeProviderModal()" title="Close">✕</button>
        <div class="modal-title">Choose AI Model</div>
        <div class="modal-subtitle">Select the AI provider for your new chat session.</div>
        <div class="provider-options">
            <button type="button" class="provider-option" onclick="startChat('claude')">
                <div class="provider-option-icon">🧠</div>
                <div class="provider-option-name">Claude</div>
                <div class="provider-option-desc">Anthropic's model via AWS Bedrock. Great for reasoning, coding &amp; analysis.</div>
            </button>
            <button type="button" class="provider-option" onclick="startChat('gemini')">
                <div class="provider-option-icon">🌟</div>
                <div class="provider-option-name">Gemini</div>
                <div class="provider-option-desc">Google's Gemini model. Excellent for multimodal tasks &amp; large context.</div>
            </button>
        </div>
    </div>
</div>

<form id="new-chat-form" action="{{ route('chat.store') }}" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="ai_provider" id="new-chat-provider" value="">
</form>

{{-- ─── Memory / System Prompt Modal ───────────────────────────────── --}}
<div class="memory-modal-overlay" id="memory-modal" onclick="handleMemoryModalClick(event)">
    <div class="memory-modal-card">
        <button class="modal-close" onclick="closeMemoryModal()" title="Close">✕</button>
        <div class="memory-modal-header">
            <div class="memory-modal-icon">🧠</div>
            <div>
                <div class="modal-title">AI Memory</div>
                <div class="modal-subtitle">Set a custom system prompt to shape how the AI behaves in this session.</div>
            </div>
        </div>
        <div class="memory-hint">
            💡 <strong>Tip:</strong> Describe the AI's role, tone, and any constraints. For example: <em>"You are a senior software architect. Answer only in bullet points with code examples."</em>
        </div>
        <textarea id="memory-textarea" placeholder="Enter your system prompt / AI memory here...">{{ $chatSession->system_prompt ?? '' }}</textarea>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <button type="button" onclick="clearMemory()" style="
                padding:7px 14px;border-radius:8px;background:none;
                border:1px solid var(--glass-border);color:var(--text-muted);
                font-size:12px;cursor:pointer;transition:all 0.2s;font-family:'Inter',sans-serif;
            " onmouseover="this.style.borderColor='#f87171';this.style.color='#f87171'" onmouseout="this.style.borderColor='var(--glass-border)';this.style.color='var(--text-muted)'">Clear</button>
            <button class="memory-save-btn" onclick="saveMemory()">Save Memory</button>
        </div>
    </div>
</div>

<script>
    const SESSION_ID   = {{ $chatSession->id }};
    const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
    const messagesArea = document.getElementById('messages-area');
    const input        = document.getElementById('message-input');
    const sendBtn      = document.getElementById('send-btn');
    const fileBtn      = document.getElementById('file-btn');
    const fileInput    = document.getElementById('file-input');
    const welcomeState = document.getElementById('welcome-state');
    const previewArea  = document.getElementById('attachments-preview');

    // PHP upload limits injected from server
    const PHP_PER_FILE_LIMIT = {{ intval(ini_get('upload_max_filesize')) ?: 50 }} * 1024 * 1024; // bytes
    const PHP_POST_LIMIT     = {{ intval(ini_get('post_max_size')) ?: 8 }} * 1024 * 1024;        // bytes
    const MAX_FILES          = 5;

    let selectedFiles  = [];
    let hasOversized   = false;

    // Auto-resize textarea
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(Math.max(input.scrollHeight, 72), 240) + 'px';
    });

    // Send on Enter (Shift+Enter for newline)
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // File input handler — MERGE new files with existing selection
    fileInput.addEventListener('change', (e) => {
        const incoming = Array.from(e.target.files);
        selectedFiles = [...selectedFiles, ...incoming].slice(0, MAX_FILES);
        fileInput.value = ''; // reset so same file can be re-added
        updatePreview();
    });

    // Drag and drop support
    document.addEventListener('dragover', (e) => {
        e.preventDefault();
        messagesArea.style.background = 'rgba(124,58,237,0.1)';
    });

    document.addEventListener('dragleave', (e) => {
        if (e.target === document) {
            messagesArea.style.background = 'transparent';
        }
    });

    document.addEventListener('drop', (e) => {
        e.preventDefault();
        messagesArea.style.background = 'transparent';
        const files = Array.from(e.dataTransfer.files);
        selectedFiles = [...selectedFiles, ...files].slice(0, MAX_FILES);
        updatePreview();
    });

    function triggerFileInput() {
        fileInput.click();
    }

    function updatePreview() {
        previewArea.innerHTML = '';
        hasOversized = false;

        if (selectedFiles.length === 0) {
            updateFileBadge();
            return;
        }

        // Check total post size
        const totalSize = selectedFiles.reduce((s, f) => s + f.size, 0);
        const totalOversized = totalSize > PHP_POST_LIMIT;

        // Warning banner for total size
        if (totalOversized) {
            const banner = document.createElement('div');
            banner.className = 'upload-warning-banner';
            banner.innerHTML = `⚠️ Total size <strong>${formatFileSize(totalSize)}</strong> exceeds server limit of <strong>${formatFileSize(PHP_POST_LIMIT)}</strong>. Please remove some files.`;
            previewArea.appendChild(banner);
        }

        selectedFiles.forEach((file, idx) => {
            const isOver = file.size > PHP_PER_FILE_LIMIT;
            if (isOver) hasOversized = true;

            const item = document.createElement('div');
            item.className = 'attachment-item' + (isOver ? ' oversized' : '');

            const icon = getFileIcon(file.type);
            const size = formatFileSize(file.size);
            const warnHtml = isOver
                ? `<span class="size-warning">⚠ Exceeds ${formatFileSize(PHP_PER_FILE_LIMIT)} limit</span>`
                : '';

            item.innerHTML = `
                <span class="attachment-icon">${icon}</span>
                <span>${file.name.substring(0, 25)}</span>
                <span style="font-size: 10px; opacity: 0.7;">(${size})</span>
                ${warnHtml}
                <button type="button" class="remove-btn" onclick="removeFile(${idx})" title="Remove file">✕</button>
            `;

            previewArea.appendChild(item);
        });

        // Show total/max count
        const summary = document.createElement('div');
        summary.style.cssText = 'font-size:11px;color:var(--text-muted);padding:2px 4px;width:100%;text-align:right;';
        summary.textContent = `${selectedFiles.length} / ${MAX_FILES} files selected · Total: ${formatFileSize(totalSize)}`;
        previewArea.appendChild(summary);

        // Block send if oversized
        sendBtn.disabled = hasOversized || totalOversized;
        if (hasOversized || totalOversized) {
            sendBtn.title = 'Remove oversized files before sending';
        } else {
            sendBtn.title = 'Send message';
        }

        updateFileBadge();
    }

    function updateFileBadge() {
        let badge = fileBtn.querySelector('.file-count-badge');
        if (selectedFiles.length > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'file-count-badge';
                fileBtn.appendChild(badge);
            }
            badge.textContent = selectedFiles.length;
        } else if (badge) {
            badge.remove();
        }
    }

    function removeFile(idx) {
        selectedFiles.splice(idx, 1);
        updatePreview();
    }

    function getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType === 'application/pdf') return '📕';
        if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
        if (mimeType.includes('sheet') || mimeType.includes('csv') || mimeType.includes('excel')) return '📊';
        if (mimeType.includes('presentation')) return '📊';
        return '📄';
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

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

    function renderMarkdown(markdown) {
        // Load marked from CDN
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';

        return new Promise((resolve) => {
            script.onload = () => {
                // Configure marked options
                marked.setOptions({
                    breaks: true,
                    gfm: true,
                    headerIds: true,
                });

                // Render markdown to HTML
                let html = marked.parse(markdown);

                // Sanitize: remove script tags and dangerous attributes
                const temp = document.createElement('div');
                temp.innerHTML = html;

                // Remove script tags
                temp.querySelectorAll('script').forEach(el => el.remove());

                // Add syntax highlighting
                temp.querySelectorAll('pre code').forEach(block => {
                    const language = block.className.replace('language-', '');
                    if (language) {
                        highlightCode(block);
                    }
                });

                resolve(temp.innerHTML);
            };

            // Only add script if not already loaded
            if (!window.marked) {
                document.head.appendChild(script);
            } else {
                script.onload();
            }
        });
    }

    function highlightCode(codeBlock) {
        const hlScript = document.createElement('script');
        hlScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js';

        hlScript.onload = () => {
            window.hljs.highlightElement(codeBlock);
        };

        if (!window.hljs) {
            document.head.appendChild(hlScript);
        } else {
            window.hljs.highlightElement(codeBlock);
        }
    }

    async function addMessage(role, content, time) {
        // Remove welcome state if present
        if (welcomeState) welcomeState.remove();

        const isUser   = role === 'user';
        const name     = isUser ? '{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}' : '✦';
        const msgEl    = document.createElement('div');
        msgEl.className = `message ${role}`;

        let bubbleContent;

        if (isUser) {
            // For user messages, escape HTML and simple formatting
            bubbleContent = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        } else {
            // For AI messages, render as markdown
            try {
                bubbleContent = await renderMarkdown(content);
            } catch (e) {
                // Fallback to escaped text if rendering fails
                bubbleContent = content
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\n/g, '<br>');
            }
        }

        const copyBtnHtml = !isUser ? `
            <button class="copy-btn" title="Copy response" onclick="copyMessage(this)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>` : '';

        const bubbleWrapper = !isUser
            ? `<div class="msg-wrapper"><div class="msg-bubble">${bubbleContent}</div>${copyBtnHtml}</div>`
            : `<div class="msg-bubble">${bubbleContent}</div>`;

        msgEl.innerHTML = `
            <div class="msg-avatar">${name}</div>
            <div>
                ${bubbleWrapper}
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

    async function copyMessage(btn) {
        const bubble = btn.closest('.msg-wrapper').querySelector('.msg-bubble');
        const text = bubble.innerText;
        try {
            await navigator.clipboard.writeText(text);
            btn.classList.add('copied');
            btn.title = 'Copied!';
            btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.title = 'Copy response';
                btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;
            }, 2000);
        } catch (e) {
            showError('Could not copy to clipboard.');
        }
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text && selectedFiles.length === 0) return;

        // Block if any file exceeds limits
        if (hasOversized) {
            showError('Please remove files that exceed the size limit before sending.');
            return;
        }

        // Prepare FormData for multipart request (needed for files)
        const formData = new FormData();
        formData.append('message', text);

        // Add selected files
        selectedFiles.forEach(file => {
            formData.append('attachments[]', file);
        });

        // Snapshot files before clearing (for post-send chip rendering)
        const sentFiles = [...selectedFiles];

        // Clear input and disable
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;
        fileBtn.disabled = true;

        const sentTime = formatTime();
        const userMsgEl = await addMessage('user', text || '(Sent file)', sentTime);

        // Clear preview and files
        selectedFiles = [];
        previewArea.innerHTML = '';
        fileInput.value = '';

        const typing = addTypingIndicator();

        try {
            const res = await fetch(`/chat/${SESSION_ID}/messages`, {
                method : 'POST',
                headers: {
                    'X-CSRF-TOKEN' : CSRF_TOKEN,
                    'Accept'       : 'application/json',
                },
                body: formData,
            });

            typing.remove();

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                showError(err.message || 'Something went wrong. Please try again.');
                sendBtn.disabled = false;
                fileBtn.disabled = false;
                return;
            }

            const data = await res.json();
            const aiMsgEl = await addMessage('assistant', data.message, formatTime());

            // Attach export dropdown to dynamically added AI messages
            if (aiMsgEl) attachExportDropdown(aiMsgEl, data.message_id);

            // Render file download chips on the user message
            if (data.attachments && data.attachments.length > 0) {
                addFileChips(userMsgEl, data.attachments);
            }

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
        fileBtn.disabled = false;
        input.focus();
    }

    // Initial scroll
    scrollToBottom();

    // ── File Download Chips ─────────────────────────────────────────────
    function getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType === 'application/pdf') return '📕';
        if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
        if (mimeType.includes('sheet') || mimeType.includes('csv')) return '📊';
        if (mimeType.includes('json')) return '📋';
        return '📄';
    }

    function addFileChips(msgEl, attachments) {
        const container = msgEl.querySelector('div:not(.msg-avatar)');
        if (!container) return;

        const chipsDiv = document.createElement('div');
        chipsDiv.className = 'msg-file-chips';

        attachments.forEach(att => {
            const sizeKb = att.file_size < 1048576
                ? (att.file_size / 1024).toFixed(1) + ' KB'
                : (att.file_size / 1048576).toFixed(1) + ' MB';

            const chip = document.createElement('a');
            chip.href = att.download_url;
            chip.className = 'file-chip';
            chip.title = 'Download ' + att.original_filename;
            chip.download = att.original_filename;
            chip.innerHTML = `
                <span class="file-chip-icon">${getFileIcon(att.mime_type)}</span>
                <span class="file-chip-name">${att.original_filename}</span>
                <span class="file-chip-size">(${sizeKb})</span>
            `;
            chipsDiv.appendChild(chip);
        });

        // Insert chips after the bubble (before the timestamp)
        const timeEl = container.querySelector('.msg-time');
        if (timeEl) {
            container.insertBefore(chipsDiv, timeEl);
        } else {
            container.appendChild(chipsDiv);
        }
    }

    // ── Model Switcher ──────────────────────────────────────────────────
    const UPDATE_PROVIDER_URL = '{{ route('chat.update-provider', $chatSession) }}';
    let currentProvider = '{{ $chatSession->ai_provider ?? 'claude' }}';

    async function switchModel(provider) {
        if (provider === currentProvider) return;

        const pillClaude = document.getElementById('pill-claude');
        const pillGemini = document.getElementById('pill-gemini');
        pillClaude.classList.add('switching');
        pillGemini.classList.add('switching');

        try {
            const res = await fetch(UPDATE_PROVIDER_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ai_provider: provider }),
            });

            if (!res.ok) throw new Error();

            currentProvider = provider;

            // Update pills
            pillClaude.className = 'model-pill' + (provider === 'claude' ? ' active-claude' : '');
            pillGemini.className = 'model-pill' + (provider === 'gemini' ? ' active-gemini' : '');

            // Update header subtitle
            const subtitle = document.querySelector('.chat-subtitle');
            if (subtitle) {
                subtitle.innerHTML = provider === 'gemini'
                    ? '<span title="Using Google Gemini">🌟 Gemini</span> &middot; AI Assistant'
                    : '<span title="Using Claude via AWS Bedrock">🧠 Claude</span> &middot; AI Assistant';
            }

            // Update textarea placeholder and footer
            const providerName = provider === 'gemini' ? 'Gemini' : 'Claude';
            input.placeholder = `Message ${providerName}…`;
            const footer = document.getElementById('input-footer');
            if (footer) footer.textContent = `${providerName} can make mistakes. Consider checking important information.`;

            // Update sidebar badge for active session
            const activeBadge = document.querySelector('.session-item.active .session-provider-badge');
            if (activeBadge) {
                activeBadge.textContent = providerName;
                activeBadge.className = 'session-provider-badge ' + (provider === 'gemini' ? 'badge-gemini' : 'badge-claude');
            }

        } catch {
            showError('Failed to switch model. Please try again.');
        } finally {
            pillClaude.classList.remove('switching');
            pillGemini.classList.remove('switching');
        }
    }

    // ── Provider Modal ──────────────────────────────────────────────────
    function openProviderModal() {
        document.getElementById('provider-modal').classList.add('open');
    }

    function closeProviderModal() {
        document.getElementById('provider-modal').classList.remove('open');
    }

    function handleModalClick(e) {
        if (e.target === document.getElementById('provider-modal')) {
            closeProviderModal();
        }
    }

    function startChat(provider) {
        document.getElementById('new-chat-provider').value = provider;
        document.getElementById('new-chat-form').submit();
    }

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeProviderModal();
    });

    // ── Theme Switcher ──────────────────────────────────────────────────
    const THEMES = ['dark', 'light', 'midnight', 'ocean'];

    function setTheme(theme) {
        if (!THEMES.includes(theme)) return;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('ai-chatbot-theme', theme);

        // Update active swatch
        document.querySelectorAll('.theme-swatch').forEach(s => {
            s.classList.toggle('active', s.dataset.swatch === theme);
        });
    }

    // Apply saved theme on load
    (function () {
        const saved = localStorage.getItem('ai-chatbot-theme');
        if (saved && THEMES.includes(saved)) {
            setTheme(saved);
        } else {
            const defaultSwatch = document.querySelector('.theme-swatch[data-swatch="dark"]');
            if (defaultSwatch) defaultSwatch.classList.add('active');
        }
    })();

    // ── Memory Modal ────────────────────────────────────────────────────
    const UPDATE_MEMORY_URL = '{{ route('chat.update-memory', $chatSession) }}';
    let hasMemory = {{ $chatSession->system_prompt ? 'true' : 'false' }};

    function openMemoryModal() {
        document.getElementById('memory-modal').classList.add('open');
        document.getElementById('memory-textarea').focus();
    }

    function closeMemoryModal() {
        document.getElementById('memory-modal').classList.remove('open');
    }

    function handleMemoryModalClick(e) {
        if (e.target === document.getElementById('memory-modal')) closeMemoryModal();
    }

    function clearMemory() {
        document.getElementById('memory-textarea').value = '';
    }

    function resetMemoryBtnStyle(btn) {
        if (hasMemory) {
            btn.style.background = 'rgba(124,58,237,0.1)';
            btn.style.borderColor = 'rgba(124,58,237,0.4)';
            btn.style.color = 'var(--accent-light)';
        } else {
            btn.style.background = 'var(--glass)';
            btn.style.borderColor = 'var(--glass-border)';
            btn.style.color = 'var(--text-secondary)';
        }
    }

    async function saveMemory() {
        const prompt = document.getElementById('memory-textarea').value.trim();
        const btn = document.querySelector('.memory-save-btn');
        const orig = btn.textContent;
        btn.textContent = 'Saving…';
        btn.disabled = true;

        try {
            const res = await fetch(UPDATE_MEMORY_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ system_prompt: prompt }),
            });

            if (!res.ok) throw new Error();

            hasMemory = prompt.length > 0;

            // Update memory button indicator
            const memBtn = document.getElementById('memory-btn');
            const dot = memBtn.querySelector('span[style]');
            if (hasMemory && !dot) {
                const newDot = document.createElement('span');
                newDot.style.cssText = 'width:7px;height:7px;border-radius:50%;background:#a78bfa;display:inline-block';
                memBtn.appendChild(newDot);
            } else if (!hasMemory && dot) {
                dot.remove();
            }

            btn.textContent = 'Saved ✓';
            setTimeout(() => { btn.textContent = orig; btn.disabled = false; closeMemoryModal(); }, 1000);

        } catch {
            btn.textContent = orig;
            btn.disabled = false;
            showError('Failed to save memory. Please try again.');
        }
    }

    // Close memory modal on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeMemoryModal(); closeProviderModal(); }
    });
</script>
</body>
</html>
