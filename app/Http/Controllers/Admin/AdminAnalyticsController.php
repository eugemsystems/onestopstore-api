<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\CartAbandonment;
use App\Models\Analytics\UserEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminAnalyticsController extends BaseAdminController
{
    protected string $permissionPrefix = 'analytics';

    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $this->checkPermission('dashboard');
        $period = $request->input('period', '7d');
        $startDate = $this->getStartDate($period);

        $stats = [
            'overview' => [
                'total_sessions' => UserSession::human()->where('created_at', '>=', $startDate)->count(),
                'total_page_views' => PageView::human()->where('created_at', '>=', $startDate)->count(),
                'total_events' => UserEvent::where('created_at', '>=', $startDate)->count(),
                'unique_visitors' => UserSession::human()->where('created_at', '>=', $startDate)
                    ->distinct('ip_address')->count('ip_address'),
                'active_users_now' => UserSession::human()
                    ->where('last_activity_at', '>=', now()->subMinutes(5))->count(),
            ],
            'devices' => PageView::human()->where('created_at', '>=', $startDate)
                ->select('device_type', DB::raw('count(*) as count'))
                ->groupBy('device_type')
                ->get(),
            'browsers' => PageView::human()->where('created_at', '>=', $startDate)
                ->select('browser', DB::raw('count(*) as count'))
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'os' => PageView::human()->where('created_at', '>=', $startDate)
                ->select('os', DB::raw('count(*) as count'))
                ->groupBy('os')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'top_pages' => PageView::human()->where('created_at', '>=', $startDate)
                ->select('path', 'page_title', DB::raw('count(*) as views'))
                ->groupBy('path', 'page_title')
                ->orderByDesc('views')
                ->limit(10)
                ->get(),
            'top_searches' => UserEvent::where('created_at', '>=', $startDate)
                ->where('event_name', 'search')
                ->whereNotNull('event_data')
                ->whereRaw("event_data->>'query' IS NOT NULL")
                ->whereRaw("event_data->>'query' != ''")
                ->select(
                    DB::raw("event_data->>'query' as search_query"),
                    DB::raw('count(*) as count'),
                    DB::raw("ROUND(AVG((event_data->>'results')::numeric), 0) as avg_results")
                )
                ->groupBy(DB::raw("event_data->>'query'"))
                ->orderByDesc('count')
                ->limit(20)
                ->get(),
            'referrers' => PageView::human()->where('created_at', '>=', $startDate)
                ->whereNotNull('referrer')
                ->select('referrer', DB::raw('count(*) as count'))
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'countries' => PageView::human()->where('created_at', '>=', $startDate)
                ->whereNotNull('country')
                ->select('country', DB::raw('count(*) as count'))
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'cart_abandonments' => [
                'total' => CartAbandonment::where('created_at', '>=', $startDate)->count(),
                'recovered' => CartAbandonment::where('created_at', '>=', $startDate)
                    ->where('recovered', true)->count(),
                'total_value' => CartAbandonment::where('created_at', '>=', $startDate)
                    ->sum('cart_value'),
                'by_stage' => CartAbandonment::where('created_at', '>=', $startDate)
                    ->select('abandonment_stage', DB::raw('count(*) as count'))
                    ->groupBy('abandonment_stage')
                    ->get(),
            ],
            'top_events' => UserEvent::where('created_at', '>=', $startDate)
                ->whereNotIn('event_name', ['page_hidden', 'page_visible', 'page_exit'])
                ->where('event_name', 'NOT LIKE', 'scroll_depth_%')
                ->select('event_name', 'event_type', DB::raw('count(*) as count'))
                ->groupBy('event_name', 'event_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'specials_clicks' => [
                'total' => UserEvent::where('created_at', '>=', $startDate)
                    ->where('event_name', 'specials_button_click')
                    ->count(),
                'desktop' => UserEvent::where('created_at', '>=', $startDate)
                    ->where('event_name', 'specials_button_click')
                    ->where('element_id', 'specials-btn-desktop')
                    ->count(),
                'mobile' => UserEvent::where('created_at', '>=', $startDate)
                    ->where('event_name', 'specials_button_click')
                    ->where('element_id', 'specials-btn-mobile')
                    ->count(),
                'daily' => UserEvent::where('created_at', '>=', $startDate)
                    ->where('event_name', 'specials_button_click')
                    ->select(
                        DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as day"),
                        DB::raw('count(*) as count')
                    )
                    ->groupBy('day')
                    ->orderBy('day')
                    ->get(),
            ],
        ];

        // Time series data
        $groupBy = $period === '24h' ? 'hour' : 'day';
        $timeseries = [
            'page_views' => $this->getTimeSeriesQuery(PageView::class, $startDate, $groupBy),
            'sessions' => $this->getTimeSeriesQuery(UserSession::class, $startDate, $groupBy),
            'events' => $this->getTimeSeriesQuery(UserEvent::class, $startDate, $groupBy),
        ];

        return view('admin.analytics.dashboard', compact('stats', 'timeseries', 'period'));
    }

    /**
     * Get active users (AJAX endpoint)
     */
    public function getActiveUsers()
    {
        // Cache for only 5 seconds for near real-time updates
        $activeUsers = Cache::remember('active_human_users_count', 5, function () {
            $fiveMinutesAgo = now()->subMinutes(5);
            return UserSession::human()
                ->where('last_activity_at', '>=', $fiveMinutesAgo)
                ->count();
        });

        return response()->json([
            'active_users' => $activeUsers,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get detailed session information
     */
    public function sessionDetails($sessionId, Request $request)
    {
        $this->checkPermission('sessions');

        $session = UserSession::where('session_id', $sessionId)->firstOrFail();

        // Abort if this is a bot session
        if ($session->is_bot) {
            abort(404, 'Session not found');
        }

        // Get all page views for this session
        $pageViews = PageView::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Get all events for this session
        $events = UserEvent::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Get cart abandonments if any
        $cartAbandonments = CartAbandonment::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $stats = [
            'total_page_views' => $pageViews->count(),
            'total_events' => $events->count(),
            'total_cart_abandonments' => $cartAbandonments->count(),
            'session_duration' => $session->duration ?? ($session->last_activity_at ? $session->last_activity_at->diffInSeconds($session->started_at) : 0),
            'pages_per_minute' => $pageViews->count() > 0 && $session->duration ? ($pageViews->count() / ($session->duration / 60)) : 0,
        ];

        // Create timeline of all activities
        $allActivities = collect();

        foreach ($pageViews as $view) {
            $allActivities->push([
                'type' => 'page_view',
                'timestamp' => $view->created_at,
                'data' => $view
            ]);
        }

        foreach ($events as $event) {
            $allActivities->push([
                'type' => 'event',
                'timestamp' => $event->created_at,
                'data' => $event
            ]);
        }

        foreach ($cartAbandonments as $abandonment) {
            $allActivities->push([
                'type' => 'cart_abandonment',
                'timestamp' => $abandonment->created_at,
                'data' => $abandonment
            ]);
        }

        // Sort all activities by timestamp
        $allActivities = $allActivities->sortBy('timestamp');

        // Paginate the timeline manually
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $timeline = $allActivities->slice($offset, $perPage);
        $total = $allActivities->count();
        $lastPage = (int) ceil($total / $perPage);

        // Create pagination object
        $pagination = [
            'current_page' => (int) $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'has_more' => $currentPage < $lastPage,
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null,
        ];

        return view('admin.analytics.session-details', compact('session', 'pageViews', 'events', 'cartAbandonments', 'stats', 'timeline', 'pagination'));
    }

    /**
     * Get cart abandonments (AJAX endpoint)
     */
    public function getCartAbandonments(Request $request)
    {
        $this->checkPermission('cart-abandonments');

        $abandonments = CartAbandonment::with('user')
            ->where('recovered', false)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($abandonments);
    }

    // Helper methods
    private function getStartDate($period)
    {
        return match($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }

    private function getTimeSeriesQuery($model, $startDate, $groupBy)
    {
        // PostgreSQL uses TO_CHAR instead of DATE_FORMAT
        $dateFormat = $groupBy === 'hour'
            ? 'YYYY-MM-DD HH24:00:00'
            : 'YYYY-MM-DD';

        return $model::human()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, '$dateFormat') as period"),
                DB::raw('count(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
