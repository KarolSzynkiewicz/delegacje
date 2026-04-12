<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user')->orderByDesc('created_at');

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->paginate(40)->withQueryString();

        $modelTypes = array_keys(config('audit.model_labels', []));
        $eventTypes = array_keys(config('audit.event_labels', []));

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit-logs.index', compact('logs', 'modelTypes', 'eventTypes', 'users'));
    }
}
