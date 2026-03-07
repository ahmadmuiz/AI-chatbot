<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with(['user', 'actor'])->orderByDesc('created_at');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs   = $query->paginate(50)->withQueryString();
        $events = AuditLog::distinct()->orderBy('event')->pluck('event');

        return view('admin.audit.index', compact('logs', 'events'));
    }
}
