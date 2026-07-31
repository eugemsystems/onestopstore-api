<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\CartAbandonment;
use App\Models\Analytics\UserEvent;
use App\Jobs\ProcessAnalyticsTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\GeoIp\GeoIpService;

class AnalyticsController extends Controller
{
    protected GeoIpService $geoIpService;

    public function __construct(GeoIpService $geoIpService)
    {
        $this->geoIpService = $geoIpService;
    }

    private function isDisabled(): bool
    {
        return !config('analytics.enabled', true);
    }

    /**
     * Track page view
     */
    public function trackPageView(Request $request)
    {
        if ($this->isDisabled()) return response()->json(['success' => true]);
        $validated = $request->validate([
            'session_id' => 'required|string',
            'url' => 'required|string',
            'path' => 'required|string',
            'page_title' => 'nullable|string',
            'referrer' => 'nullable|string',
            'utm_source' => 'nullable|string',
            'utm_medium' => 'nullable|string',
            'utm_campaign' => 'nullable|string',
            'utm_term' => 'nullable|string',
            'utm_content' => 'nullable|string',
            'duration' => 'nullable|integer',
        ]);

        $data = [
            'session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'url' => $validated['url'],
            'path' => $validated['path'],
            'page_title' => $validated['page_title'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'duration' => $validated['duration'] ?? null,
            // Defer heavy operations to job
            '_do_geo_lookup' => true,
            '_do_device_parse' => true,
        ];

        ProcessAnalyticsTracking::dispatch('page_view', $data)->onQueue('analytics');

        // Update session
        ProcessAnalyticsTracking::dispatch('session', [
            'session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'last_activity_at' => now(),
            'total_page_views' => 1,
            'duration' => $validated['duration'] ?? null,
        ])->onQueue('analytics');

        return response()->json(['success' => true]);
    }

    /**
     * Initialize or update session
     */
    public function trackSession(Request $request)
    {
        if ($this->isDisabled()) return response()->json(['success' => true]);

        $validated = $request->validate([
            'session_id' => 'required|string',
            'landing_page' => 'nullable|string',
            'referrer' => 'nullable|string',
            'heartbeat' => 'sometimes|boolean',
            'duration' => 'nullable|integer',
        ]);

        $data = [
            'session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'landing_page' => $validated['landing_page'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'started_at' => now(),
            'last_activity_at' => now(),
            'duration' => $validated['duration'] ?? null,
            'heartbeat' => (bool)($validated['heartbeat'] ?? false),
            // Defer heavy operations to job
            '_do_geo_lookup' => true,
            '_do_device_parse' => true,
        ];

        ProcessAnalyticsTracking::dispatch('session', $data)->onQueue('analytics');

        return response()->json(['success' => true]);
    }

    /**
     * Track user event (clicks, interactions, etc.)
     */
    public function trackEvent(Request $request)
    {
        if ($this->isDisabled()) return response()->json(['success' => true]);

        // Handle sendBeacon which sends data as text/plain
        $data = $request->all();
        if (empty($data) && $request->getContent()) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $validated = validator($data, [
            'session_id' => 'required|string',
            'event_type' => 'required|string',
            'event_category' => 'nullable|string',
            'event_name' => 'required|string',
            'event_data' => 'nullable|array',
            'page_url' => 'nullable|string',
            'element_id' => 'nullable|string',
            'element_class' => 'nullable|string',
            'element_text' => 'nullable|string',
            'value' => 'nullable|numeric',
        ])->validate();

        // Filter out high-frequency, low-value events to reduce load
        $ignoredEvents = ['page_visible', 'page_hidden', 'heartbeat'];
        if (in_array($validated['event_name'], $ignoredEvents)) {
            // Return success immediately without processing
            return response()->json(['success' => true]);
        }

        $payload = [
            'session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'event_type' => $validated['event_type'],
            'event_category' => $validated['event_category'] ?? null,
            'event_name' => $validated['event_name'],
            'event_data' => $validated['event_data'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'element_id' => $validated['element_id'] ?? null,
            'element_class' => $validated['element_class'] ?? null,
            'element_text' => $validated['element_text'] ?? null,
            'value' => $validated['value'] ?? null,
        ];

        // Dispatch to queue asynchronously with minimal priority
        ProcessAnalyticsTracking::dispatch('event', $payload)->onQueue('analytics');

        // Update session less frequently - only for important events
        if (!in_array($validated['event_name'], ['scroll', 'mousemove', 'resize'])) {
            ProcessAnalyticsTracking::dispatch('session', [
                'session_id' => $validated['session_id'],
                'user_id' => auth()->id(),
                'last_activity_at' => now(),
                'total_events' => 1,
            ])->onQueue('analytics');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track cart abandonment
     */
    public function trackCartAbandonment(Request $request)
    {
        if ($this->isDisabled()) return response()->json(['success' => true]);

        // Handle sendBeacon which sends data as text/plain
        $data = $request->all();
        if (empty($data) && $request->getContent()) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $validated = validator($data, [
            'session_id' => 'required|string',
            'email' => 'nullable|email',
            'cart_items' => 'required|array',
            'cart_value' => 'required|numeric',
            'currency' => 'nullable|string',
            'items_count' => 'required|integer',
            'abandonment_stage' => 'required|string',
            'abandonment_reason' => 'nullable|string',
        ])->validate();

        $payload = [
            'session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'email' => $validated['email'] ?? (auth()->user() ? auth()->user()->email : null),
            'cart_items' => $validated['cart_items'],
            'cart_value' => $validated['cart_value'],
            'currency' => $validated['currency'] ?? 'USD',
            'items_count' => $validated['items_count'],
            'abandonment_stage' => $validated['abandonment_stage'],
            'abandonment_reason' => $validated['abandonment_reason'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            // Defer heavy operations to job
            '_do_device_parse' => true,
        ];

        // Dispatch to queue
        ProcessAnalyticsTracking::dispatch('cart_abandonment', $payload)->onQueue('analytics');

        return response()->json(['success' => true]);
    }

    /**
     * Update session (queued)
     */
    private function updateSessionQueued($sessionId, $request, $extra = [])
    {
        $deviceInfo = $this->parseUserAgent($request->userAgent());

        $data = [
            'session_id' => $sessionId,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'platform' => $deviceInfo['platform'],
            'last_activity_at' => now(),
        ];

        if (!empty($extra['increment_page_views'])) {
            $data['total_page_views'] = 1;
        }

        if (!empty($extra['increment_events'])) {
            $data['total_events'] = 1;
        }

        if (isset($extra['duration'])) {
            $data['duration'] = $extra['duration'];
        }

        ProcessAnalyticsTracking::dispatch('session', $data);
    }

    /**
     * Get real-time active users count
     */
    public function getActiveUsers()
    {
        $activeUsers = Cache::remember('active_human_users_count', 30, function () {
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
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        $period = $request->input('period', '24h'); // 24h, 7d, 30d, 90d
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
        ];

        return response()->json($stats);
    }

    /**
     * Get time series data for charts
     */
    public function getTimeSeriesData(Request $request)
    {
        $period = $request->input('period', '7d');
        $startDate = $this->getStartDate($period);
        $groupBy = $period === '24h' ? 'hour' : 'day';

        $pageViews = $this->getTimeSeriesQuery(PageView::class, $startDate, $groupBy);
        $sessions = $this->getTimeSeriesQuery(UserSession::class, $startDate, $groupBy);
        $events = $this->getTimeSeriesQuery(UserEvent::class, $startDate, $groupBy);

        return response()->json([
            'page_views' => $pageViews,
            'sessions' => $sessions,
            'events' => $events,
        ]);
    }

    /**
     * Get cart abandonment details
     */
    public function getCartAbandonments(Request $request)
    {
        $abandonments = CartAbandonment::with('user')
            ->where('recovered', false)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($abandonments);
    }

    /**
     * Get session details
     */
    public function getSessionDetails($sessionId)
    {
        $session = UserSession::with(['pageViews', 'events'])
            ->where('session_id', $sessionId)
            ->firstOrFail();

        return response()->json($session);
    }

    // Helper methods

    private function parseUserAgent($userAgent)
    {
        $device_type = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';
        $platform = 'web';

        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $device_type = 'tablet';
        }

        // Detect browser
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect OS
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        }

        return compact('device_type', 'browser', 'os', 'platform');
    }

    private function updateSession($sessionId, $request)
    {
        $session = UserSession::where('session_id', $sessionId)->first();

        if ($session) {
            $session->increment('total_page_views');
            $session->update([
                'last_activity_at' => now(),
                'duration' => now()->diffInSeconds($session->started_at),
            ]);
        }
    }

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
        $dateFormat = $groupBy === 'hour'
            ? '%Y-%m-%d %H:00:00'
            : '%Y-%m-%d';

        return $model::human()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('count(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
