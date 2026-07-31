<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Permission gate
        if (!auth()->user()->can('activity-log.view')) {
            abort(403, 'Unauthorized');
        }

        // ── Filters ─────────────────────────────────────────────────
        $userId    = $request->input('user_id');
        $event     = $request->input('event');
        $logName   = $request->input('log_name');
        $search    = $request->input('search');
        $dateFrom  = $request->filled('date_from') ? \Carbon\Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $dateTo    = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->input('date_to'))->endOfDay()     : null;

        // ── Query ────────────────────────────────────────────────────
        $query = ActivityLog::with(['causer', 'subject'])
            ->when($userId,   fn($q) => $q->where('causer_type', User::class)->where('causer_id', $userId))
            ->when($event,    fn($q) => $q->where('event', $event))
            ->when($logName,  fn($q) => $q->where('log_name', $logName))
            ->when($search,   fn($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->where('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        // ── Filter dropdown data ──────────────────────────────────────
        $allUsers  = User::whereHas('roles', fn($q) => $q->whereNotIn('name', ['consumer']))
            ->select('id', 'name', 'email')
            ->orderBy('name')->get();

        $logNames  = ActivityLog::distinct()->orderBy('log_name')->pluck('log_name');
        $events    = ActivityLog::distinct()->orderBy('event')->pluck('event');

        // ── Summary stats ────────────────────────────────────────────
        $stats = [
            'total'   => ActivityLog::count(),
            'today'   => ActivityLog::whereDate('created_at', today())->count(),
            'week'    => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
            'users'   => ActivityLog::distinct('causer_id')->whereNotNull('causer_id')->count('causer_id'),
        ];

        return view('admin.activity-log.index', compact(
            'query', 'allUsers', 'logNames', 'events', 'stats',
            'userId', 'event', 'logName', 'search', 'dateFrom', 'dateTo'
        ));
    }
}
