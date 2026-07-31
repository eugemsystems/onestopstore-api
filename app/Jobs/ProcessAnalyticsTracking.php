<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\UserEvent;
use App\Models\Analytics\CartAbandonment;
use App\Services\GeoIp\GeoIpService;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;

class ProcessAnalyticsTracking implements ShouldQueue, Silenced
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $type;
    public $data;

    // Set job timeout to prevent hanging
    public $timeout = 30;

    // Retry failed jobs
    public $tries = 2;
    public $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Bot detection only when the payload carries a user-agent (page_view / session jobs).
            // Event-type jobs have no user_agent — the originating session already passed bot
            // detection, and calling isBot(null, null) in a queue worker context returns the
            // worker's own (empty) user-agent, which always looks like a bot and silently drops
            // every event.
            $userAgent = $this->data['user_agent'] ?? null;
            $ip        = $this->data['ip_address'] ?? null;

            if ($userAgent !== null) {
                $botDetection = app(\App\Services\BotDetectionService::class);
                if ($botDetection->isBot($userAgent, $ip)) {
                    return;
                }
            }

            // Mark as human
            $this->data['is_bot'] = false;

            // Process deferred operations if needed
            $this->processDeferredOperations();

            switch ($this->type) {
                case 'page_view':
                    $this->trackPageView();
                    break;
                case 'session':
                    $this->trackSession();
                    break;
                case 'event':
                    $this->trackEvent();
                    break;
                case 'cart_abandonment':
                    $this->trackCartAbandonment();
                    break;
            }
        } catch (\Exception $e) {
            \Log::error('Analytics tracking job failed: ' . $e->getMessage(), [
                'type' => $this->type,
                'data' => $this->data,
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't fail the job for non-critical errors
            // This prevents retries and queue backup
        }
    }

    /**
     * Process deferred operations like GeoIP and device parsing
     */
    private function processDeferredOperations()
    {
        // Handle device parsing
        if (!empty($this->data['_do_device_parse']) && !empty($this->data['user_agent'])) {
            $deviceInfo = $this->parseUserAgent($this->data['user_agent']);
            $this->data = array_merge($this->data, $deviceInfo);
            unset($this->data['_do_device_parse']);
        }

        // Handle GeoIP lookup
        if (!empty($this->data['_do_geo_lookup']) && !empty($this->data['ip_address'])) {
            try {
                $geoService = app(GeoIpService::class);
                $geo = $geoService->lookup($this->data['ip_address']);
                $this->data['country'] = $geo['country'] ?? null;
                $this->data['city'] = $geo['city'] ?? null;
            } catch (\Exception $e) {
                // Silently fail GeoIP lookup - not critical
                \Log::debug('GeoIP lookup failed: ' . $e->getMessage());
            }
            unset($this->data['_do_geo_lookup']);
        }
    }

    /**
     * Parse user agent string
     */
    private function parseUserAgent($userAgent)
    {
        $device_type = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';
        $platform = 'web';

        if (preg_match('/mobile|android|iphone|ipod/i', $userAgent)) {
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

    private function trackPageView()
    {
        $data = $this->data;
        // Truncate long-URL fields to stay within varchar(500)
        foreach (['url', 'path', 'referrer', 'user_agent', 'page_title'] as $field) {
            if (isset($data[$field]) && strlen($data[$field]) > 500) {
                $data[$field] = substr($data[$field], 0, 500);
            }
        }
        PageView::create($data);
    }

    private function trackSession()
    {
        $payload = $this->data;
        $incrementPageView = $payload['total_page_views'] ?? null;
        $incrementEvent = $payload['total_events'] ?? null;
        $heartbeat = $payload['heartbeat'] ?? false;
        unset($payload['total_page_views'], $payload['total_events'], $payload['heartbeat']);

        try {
            // Single upsert — avoids the SELECT + try-CREATE + catch + SELECT + UPDATE
            // race-condition pattern which was doing 2-3 round-trips per session update.
            $session = UserSession::firstOrCreate(
                ['session_id' => $payload['session_id']],
                [
                    'user_id'          => $payload['user_id'] ?? null,
                    'ip_address'       => $payload['ip_address'] ?? null,
                    'user_agent'       => isset($payload['user_agent'])   ? substr($payload['user_agent'],   0, 500) : null,
                    'device_type'      => $payload['device_type'] ?? null,
                    'browser'          => $payload['browser'] ?? null,
                    'os'               => $payload['os'] ?? null,
                    'platform'         => $payload['platform'] ?? null,
                    'landing_page'     => isset($payload['landing_page']) ? substr($payload['landing_page'], 0, 500) : null,
                    'referrer'         => isset($payload['referrer'])     ? substr($payload['referrer'],     0, 500) : null,
                    'country'          => $payload['country'] ?? null,
                    'city'             => $payload['city'] ?? null,
                    'last_activity_at' => $payload['last_activity_at'] ?? now(),
                    'total_page_views' => $incrementPageView ?? 0,
                    'total_events'     => $incrementEvent ?? 0,
                    'duration'         => isset($payload['duration']) ? max(0, (int) $payload['duration']) : null,
                    'started_at'       => now(),
                ]
            );

            // If the record was just created there is nothing more to update
            if ($session->wasRecentlyCreated) {
                return;
            }

            // Build updates array efficiently for existing session
            $updates = [];

            // Only update non-null values
            foreach (['ip_address', 'user_agent', 'device_type', 'browser', 'os', 'platform', 'landing_page', 'referrer', 'country', 'city'] as $field) {
                if (isset($payload[$field]) && !is_null($payload[$field])) {
                    // Truncate string fields that have a varchar(500) limit
                    $val = $payload[$field];
                    $updates[$field] = (is_string($val) && strlen($val) > 500) ? substr($val, 0, 500) : $val;
                }
            }

            if ($heartbeat || isset($payload['last_activity_at'])) {
                $updates['last_activity_at'] = now();
            }

            // Calculate duration - ensure it's always a non-negative integer
            if (!empty($payload['duration'])) {
                $durationValue = max(0, (int) $payload['duration']);
                if ($session->started_at) {
                    $updates['duration'] = max($durationValue, $session->duration ?? 0);
                } else {
                    $updates['duration'] = $durationValue;
                }
            } elseif ($session->started_at) {
                $calculatedDuration = now()->diffInSeconds($session->started_at);
                $updates['duration'] = max(0, (int) $calculatedDuration);
            }

            if ($incrementPageView) {
                $updates['total_page_views'] = ($session->total_page_views ?? 0) + 1;
            }

            if ($incrementEvent) {
                $updates['total_events'] = ($session->total_events ?? 0) + 1;
            }

            // Only save if there are actual updates
            if (!empty($updates)) {
                $session->fill($updates);
                $session->save();
            }
        } catch (\Exception $e) {
            // Log error but don't fail the job
            Log::error('Analytics session tracking failed', [
                'session_id' => $payload['session_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            // Don't rethrow - we don't want to fail the entire job for analytics
        }
    }

    private function trackEvent()
    {
        UserEvent::create($this->data);
    }

    private function trackCartAbandonment()
    {
        // Prevent duplicates: check if we already have an abandonment for this session+stage in the last hour
        // This prevents the same cart being recorded multiple times when user refreshes or navigates
        $existing = CartAbandonment::where('session_id', $this->data['session_id'])
            ->where('abandonment_stage', $this->data['abandonment_stage'])
            ->where('recovered', false)
            ->where('created_at', '>=', now()->subHour())
            ->first();

        if ($existing) {
            // Update the existing record instead of creating a duplicate
            $existing->update([
                'cart_items' => $this->data['cart_items'],
                'cart_value' => $this->data['cart_value'],
                'items_count' => $this->data['items_count'],
                'updated_at' => now(),
            ]);
        } else {
            // Create new abandonment record
            CartAbandonment::create($this->data);
        }
    }
}
