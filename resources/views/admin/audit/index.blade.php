@extends('admin.layout')

@section('title', 'Audit Logs')
@section('subtitle', 'Track all user and admin activity across the chatbot')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('admin.audit.index') }}"
      style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">

    <input type="text" name="user_id" value="{{ request('user_id') }}" class="input"
           placeholder="User ID…" style="width:100px">

    <select name="event" class="input" style="width:180px">
        <option value="">All Events</option>
        @foreach($events as $evt)
            <option value="{{ $evt }}" @selected(request('event') === $evt)>{{ $evt }}</option>
        @endforeach
    </select>

    <input type="date" name="from" value="{{ request('from') }}" class="input" style="width:150px">
    <span style="color:var(--text-muted);font-size:13px">to</span>
    <input type="date" name="to" value="{{ request('to') }}" class="input" style="width:150px">

    <button type="submit" class="btn btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
        Filter
    </button>

    @if(request()->hasAny(['user_id', 'event', 'from', 'to']))
        <a href="{{ route('admin.audit.index') }}" class="btn btn-ghost">Clear</a>
    @endif

    <span class="rows-info">{{ $logs->total() }} events</span>
</form>

{{-- Logs table --}}
<div class="card">
    <div class="card-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-light)" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <span class="card-title">Activity Log</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Event</th>
                <th>User (Subject)</th>
                <th>Actor</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;font-size:11px;color:var(--text-muted)">
                        {{ $log->created_at->format('M j, Y') }}<br>
                        <span style="font-size:10px">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        @php
                            $eventColor = match(true) {
                                str_starts_with($log->event, 'user.login')   => 'badge-green',
                                str_starts_with($log->event, 'user.logout')  => 'badge-gray',
                                str_starts_with($log->event, 'user.disabled')=> 'badge-red',
                                str_starts_with($log->event, 'user.enabled') => 'badge-green',
                                str_starts_with($log->event, 'user.password')=> 'badge-purple',
                                str_starts_with($log->event, 'chat.')        => 'badge-purple',
                                default => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $eventColor }}" style="font-size:10px">{{ $log->event }}</span>
                    </td>
                    <td>
                        @if($log->user)
                            <div style="font-size:13px;color:var(--text-primary)">{{ $log->user->name }}</div>
                            <div style="font-size:10px;color:var(--text-muted)">{{ $log->user->email }}</div>
                        @else
                            <span style="color:var(--text-muted);font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->actor && $log->actor->id !== $log->user_id)
                            <div style="font-size:12px;color:var(--accent-light)">{{ $log->actor->name }}</div>
                        @elseif($log->actor)
                            <span style="color:var(--text-muted);font-size:11px">Self</span>
                        @else
                            <span style="color:var(--text-muted);font-size:12px">—</span>
                        @endif
                    </td>
                    <td style="max-width:280px;font-size:12px;line-height:1.4">
                        {{ Str::limit($log->description, 80) }}
                    </td>
                    <td style="font-size:11px;color:var(--text-muted);font-family:monospace">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td>
                        @if($log->metadata)
                            <button class="btn btn-sm btn-ghost"
                                    onclick="toggleMeta({{ $log->id }})" title="View metadata">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
                                </svg>
                                JSON
                            </button>
                        @else
                            <span style="color:var(--text-muted);font-size:11px">—</span>
                        @endif
                    </td>
                </tr>
                @if($log->metadata)
                    <tr id="meta-{{ $log->id }}" style="display:none">
                        <td colspan="7" style="background:rgba(0,0,0,0.35);padding:12px 16px">
                            <pre style="font-size:11px;color:var(--accent-light);font-family:monospace;white-space:pre-wrap;word-break:break-all">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                        No audit log entries found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
        <div style="padding:16px 20px;border-top:1px solid var(--glass-border)">
            {{ $logs->links('pagination::simple-default') }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function toggleMeta(id) {
    const row = document.getElementById('meta-' + id);
    if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endsection
