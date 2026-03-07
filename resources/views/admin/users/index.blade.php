@extends('admin.layout')

@section('title', 'User Management')
@section('subtitle', 'Search, disable, enable, and reset passwords for user accounts')

@section('content')

{{-- Success / error alerts --}}
@if(session('success'))
    <div class="alert alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <div>
            {{ session('success') }}
            @if(session('temp_password'))
                &nbsp;— See the generated password below.
            @endif
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ $errors->first() }}
    </div>
@endif

{{-- Search --}}
<form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:10px;margin-bottom:20px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" value="{{ $search }}" class="input" placeholder="Search name or email…" style="flex:1;min-width:220px;max-width:400px">
    <button type="submit" class="btn btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        Search
    </button>
    @if($search)
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Clear</a>
    @endif
    <span class="rows-info">{{ $users->total() }} user(s) found</span>
</form>

{{-- Users table --}}
<div class="card">
    <div class="card-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-light)" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span class="card-title">All Users</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td style="color:var(--text-muted)">{{ $user->id }}</td>
                    <td style="color:var(--text-primary);font-weight:500">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span class="badge badge-gray" style="margin-left:4px;font-size:9px">You</span>
                        @endif
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->is_admin)
                            <span class="badge badge-purple">⚙️ Admin</span>
                        @else
                            <span class="badge badge-gray">User</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-green">● Active</span>
                        @else
                            <span class="badge badge-red">● Disabled</span>
                            @if($user->disabled_at)
                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px">
                                    since {{ $user->disabled_at->format('M j, Y') }}
                                </div>
                            @endif
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $user->created_at->format('M j, Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            {{-- Disable / Enable --}}
                            @if($user->id !== auth()->id())
                                @if($user->is_active)
                                    <form method="POST" action="{{ route('admin.users.disable', $user) }}" onsubmit="return confirm('Disable account for {{ addslashes($user->name) }}?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                            </svg>
                                            Disable
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.enable', $user) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            Enable
                                        </button>
                                    </form>
                                @endif
                            @endif

                            {{-- Reset Password --}}
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                                  onsubmit="return confirm('Reset password for {{ addslashes($user->name) }}? They will receive a temporary password.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-ghost">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                    Reset PW
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">
                        No users found{{ $search ? " matching &quot;" . e($search) . "&quot;" : '' }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
        <div style="padding:16px 20px;border-top:1px solid var(--glass-border)">
            {{ $users->links('pagination::simple-default') }}
        </div>
    @endif
</div>
@endsection

{{-- Temp Password Modal --}}
@section('modals')
@if(session('temp_password'))
<div class="modal-overlay open" id="pw-modal">
    <div class="modal-card">
        <div class="modal-title">🔐 Temporary Password Generated</div>
        <div class="modal-body">
            A new temporary password has been set for <strong style="color:var(--text-primary)">{{ session('reset_user') }}</strong>.
            Share this securely — it will <strong style="color:#fca5a5">not be shown again</strong>.
        </div>
        <div class="temp-password-box" id="temp-pw" onclick="selectTempPw()" title="Click to select">
            {{ session('temp_password') }}
        </div>
        <div class="modal-warning">
            ⚠️ This password is shown only once. The user must change it on next login.
        </div>
        <div style="display:flex;gap:10px">
            <button class="btn btn-primary" onclick="copyTempPw()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span id="copy-label">Copy Password</span>
            </button>
            <button class="btn btn-ghost" onclick="document.getElementById('pw-modal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    function selectTempPw() {
        const range = document.createRange();
        range.selectNodeContents(document.getElementById('temp-pw'));
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
    }
    async function copyTempPw() {
        const text = document.getElementById('temp-pw').innerText.trim();
        await navigator.clipboard.writeText(text);
        document.getElementById('copy-label').textContent = 'Copied!';
        setTimeout(() => document.getElementById('copy-label').textContent = 'Copy Password', 2000);
    }
</script>
@endsection
