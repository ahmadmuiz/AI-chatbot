<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — @yield('title', 'Dashboard') | AI Chatbot</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            --scrollbar-thumb: rgba(124,58,237,0.4);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            background-image: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(124,58,237,0.1) 0%, transparent 70%);
        }

        .admin-shell { display: flex; min-height: 100vh; }

        /* Sidebar */
        .admin-sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--glass-border);
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .brand-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            box-shadow: 0 0 20px rgba(124,58,237,0.4);
        }

        .brand-title { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .brand-sub { font-size: 11px; color: var(--accent-light); font-weight: 600; letter-spacing: 0.5px; padding-left: 46px; }

        .admin-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; }

        .nav-label {
            font-size: 10px; font-weight: 600; letter-spacing: 0.8px;
            text-transform: uppercase; color: var(--text-muted);
            padding: 8px 8px 6px;
        }

        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 13px; font-weight: 500;
            transition: all 0.15s;
        }

        .nav-link:hover { background: var(--glass); color: var(--text-primary); }
        .nav-link.active { background: rgba(124,58,237,0.18); color: var(--accent-light); border: 1px solid rgba(124,58,237,0.25); }
        .nav-link svg { opacity: 0.6; flex-shrink: 0; }
        .nav-link.active svg { opacity: 1; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--glass-border);
            display: flex; flex-direction: column; gap: 8px;
        }

        .footer-link {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 8px;
            text-decoration: none; color: var(--text-muted);
            font-size: 12px; transition: all 0.15s;
        }
        .footer-link:hover { background: var(--glass); color: var(--text-secondary); }

        /* Main content */
        .admin-main { flex: 1; display: flex; flex-direction: column; min-height: 100vh; overflow-x: hidden; }

        .admin-header {
            padding: 20px 32px;
            border-bottom: 1px solid var(--glass-border);
            background: rgba(10,10,15,0.8);
            backdrop-filter: blur(20px);
            position: sticky; top: 0; z-index: 10;
        }

        .admin-header h1 { font-size: 18px; font-weight: 700; color: var(--text-primary); }
        .admin-header p { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .admin-content { flex: 1; padding: 32px; }

        /* Cards */
        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--glass-border);
            display: flex; align-items: center; gap: 12px;
        }

        .card-title { font-size: 14px; font-weight: 600; color: var(--text-primary); }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.5px; text-transform: uppercase;
            color: var(--text-muted);
            background: rgba(124,58,237,0.05);
            border-bottom: 1px solid var(--glass-border);
        }
        .data-table td {
            padding: 12px 16px;
            font-size: 13px; color: var(--text-secondary);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-green  { background: rgba(16,185,129,0.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); }
        .badge-red    { background: rgba(239,68,68,0.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
        .badge-purple { background: rgba(124,58,237,0.2);  color: #a78bfa; border: 1px solid rgba(124,58,237,0.25); }
        .badge-gray   { background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid var(--glass-border); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            border: 1px solid transparent; text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        .btn-sm { padding: 5px 10px; font-size: 11px; border-radius: 6px; }
        .btn-primary { background: linear-gradient(135deg,#7c3aed,#5b21b6); color: white; box-shadow: 0 0 12px rgba(124,58,237,0.3); }
        .btn-primary:hover { box-shadow: 0 0 20px rgba(124,58,237,0.5); transform: translateY(-1px); }
        .btn-danger  { background: rgba(239,68,68,0.15); color: #fca5a5; border-color: rgba(239,68,68,0.25); }
        .btn-danger:hover  { background: rgba(239,68,68,0.25); border-color: rgba(239,68,68,0.4); }
        .btn-success { background: rgba(16,185,129,0.15); color: #6ee7b7; border-color: rgba(16,185,129,0.25); }
        .btn-success:hover { background: rgba(16,185,129,0.25); border-color: rgba(16,185,129,0.4); }
        .btn-ghost   { background: var(--glass); color: var(--text-secondary); border-color: var(--glass-border); }
        .btn-ghost:hover   { color: var(--text-primary); background: rgba(255,255,255,0.07); }

        /* Forms */
        .input {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 8px 12px;
            color: var(--text-primary);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input:focus { border-color: rgba(124,58,237,0.5); box-shadow: 0 0 0 3px rgba(124,58,237,0.08); }
        .input::placeholder { color: var(--text-muted); }
        select.input option { background: #111118; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }

        /* Pagination */
        .pagination { display: flex; gap: 4px; margin-top: 20px; justify-content: center; }
        .pagination a, .pagination span {
            padding: 6px 12px; border-radius: 6px; font-size: 12px;
            text-decoration: none; color: var(--text-secondary);
            background: var(--glass); border: 1px solid var(--glass-border);
            transition: all 0.15s;
        }
        .pagination a:hover { background: rgba(124,58,237,0.15); color: var(--accent-light); border-color: rgba(124,58,237,0.3); }
        .pagination .active span { background: rgba(124,58,237,0.25); color: var(--accent-light); border-color: rgba(124,58,237,0.4); }
        .pagination .disabled span { opacity: 0.4; cursor: not-allowed; }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center; z-index: 100;
            padding: 20px; opacity: 0; pointer-events: none; transition: opacity 0.2s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal-card {
            background: #111118; border: 1px solid var(--glass-border); border-radius: 20px;
            padding: 32px; width: 100%; max-width: 480px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.6), 0 0 60px rgba(124,58,237,0.08);
            transform: translateY(12px); transition: transform 0.2s;
        }
        .modal-overlay.open .modal-card { transform: translateY(0); }
        .modal-title { font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
        .modal-body { font-size: 13px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 20px; }

        .temp-password-box {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 10px;
            padding: 14px 18px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 20px;
            letter-spacing: 3px;
            color: #a78bfa;
            text-align: center;
            margin: 16px 0;
            user-select: all;
        }

        .modal-warning {
            font-size: 11px; color: #fca5a5; background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; padding: 8px 12px;
            margin-bottom: 20px;
        }

        .rows-info { font-size: 12px; color: var(--text-muted); margin-left: auto; }
    </style>
</head>
<body>
<div class="admin-shell">

    {{-- Sidebar --}}
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <div class="brand-row">
                <div class="brand-icon">⚙️</div>
                <div class="brand-title">Admin Panel</div>
            </div>
            <div class="brand-sub">AI Chatbot</div>
        </div>

        <nav class="admin-nav">
            <div class="nav-label">Management</div>
            <a href="{{ route('admin.users.index') }}"
               class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                User Management
            </a>
            <a href="{{ route('admin.audit.index') }}"
               class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Audit Logs
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('chat.index') }}" class="footer-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Back to Chat
            </a>
        </div>
    </aside>

    {{-- Main --}}
    <div class="admin-main">
        <div class="admin-header">
            <h1>@yield('title', 'Admin')</h1>
            <p>@yield('subtitle', 'Manage your application')</p>
        </div>
        <div class="admin-content">
            @yield('content')
        </div>
    </div>

</div>
@yield('modals')
@yield('scripts')
</body>
</html>
