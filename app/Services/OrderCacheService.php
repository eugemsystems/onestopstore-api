<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Order Cache Service
 * Handles caching for orders with automatic invalidation
 */
class OrderCacheService
{
    // Cache keys
    const CACHE_PREFIX = 'orders';
    const CACHE_LIST_PREFIX = 'orders_list';
    const CACHE_DETAIL_PREFIX = 'order_detail';
    const CACHE_STATUS_PREFIX = 'order_statuses';
    const CACHE_COUNTS_PREFIX = 'order_counts';

    // Cache TTL (Time To Live) in seconds
    const TTL_LIST = 300;        // 5 minutes for order lists
    const TTL_DETAIL = 600;      // 10 minutes for order details
    const TTL_STATUS = 1800;     // 30 minutes for order statuses (rarely change)
    const TTL_COUNTS = 120;      // 2 minutes for counts

    /**
     * Get cached order list or fetch and cache
     */
    public function getOrderList($params, $callback)
    {
        $cacheKey = $this->generateListCacheKey($params);

        return Cache::remember($cacheKey, self::TTL_LIST, function() use ($callback) {
            return $callback();
        });
    }

    /**
     * Get cached order detail or fetch and cache
     */
    public function getOrderDetail($orderNumber, $callback)
    {
        $cacheKey = self::CACHE_DETAIL_PREFIX . ':' . $orderNumber;

        return Cache::remember($cacheKey, self::TTL_DETAIL, function() use ($callback, $orderNumber) {
            return $callback();
        });
    }

    /**
     * Get cached order statuses
     */
    public function getOrderStatuses($callback)
    {
        return Cache::remember(self::CACHE_STATUS_PREFIX, self::TTL_STATUS, function() use ($callback) {
            return $callback();
        });
    }

    /**
     * Get cached order counts by status
     */
    public function getOrderCounts($statusId, $callback)
    {
        $cacheKey = self::CACHE_COUNTS_PREFIX . ':' . ($statusId ?? 'all');

        return Cache::remember($cacheKey, self::TTL_COUNTS, function() use ($callback) {
            return $callback();
        });
    }

    /**
     * Invalidate all order caches when an order is created/updated
     */
    public function invalidateOrderCaches($orderNumber = null)
    {
        // Clear all order list caches
        $this->clearCachesByPattern(self::CACHE_LIST_PREFIX . ':*');

        // Clear specific order detail cache if order number provided
        if ($orderNumber) {
            Cache::forget(self::CACHE_DETAIL_PREFIX . ':' . $orderNumber);
        }

        // Clear order counts cache
        $this->clearCachesByPattern(self::CACHE_COUNTS_PREFIX . ':*');
    }

    /**
     * Invalidate order statuses cache (rarely needed)
     */
    public function invalidateOrderStatuses()
    {
        Cache::forget(self::CACHE_STATUS_PREFIX);
    }

    /**
     * Generate cache key for order list based on parameters
     * CRITICAL: Must include user_id and role to prevent cache leakage between users
     */
    private function generateListCacheKey($params)
    {
        // SECURITY FIX: Include current user ID and role in cache key
        // This prevents User A from seeing User B's cached orders
        $currentUserId = \App\Helpers\Helpers::getCurrentUserId() ?? 'guest';
        $currentRole = \App\Helpers\Helpers::getCurrentRoleName() ?? 'guest';

        $keyParts = [
            self::CACHE_LIST_PREFIX,
            'user_' . $currentUserId,      // CRITICAL: Separate cache per user
            'role_' . $currentRole,         // CRITICAL: Separate cache per role
            $params['paginate'] ?? '10',
            $params['page'] ?? '1',
            $params['order_status_id'] ?? 'all',
            $params['search'] ?? '',
            $params['start_date'] ?? '',
            $params['end_date'] ?? '',
            $params['field'] ?? 'created_at',
            $params['sort'] ?? 'desc',
        ];

        $cacheKey = implode(':', array_filter($keyParts));

        return $cacheKey;
    }

    /**
     * Clear caches by pattern (works with Redis, for file cache use tags)
     */
    private function clearCachesByPattern($pattern)
    {
        $driver = config('cache.default');

        if ($driver === 'redis') {
            // Redis supports pattern matching
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            // For file/database cache, we need to manually track keys or use tags
            // Laravel 10+ supports cache tags for file driver
            try {
                Cache::tags([self::CACHE_PREFIX])->flush();
            } catch (\Exception $e) {
                // Fallback: just log, don't break the application
                Log::warning('Cache pattern clear failed - using flush', ['pattern' => $pattern]);
            }
        }
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmUpCache()
    {

        // This can be called from a scheduled command to pre-populate cache
        // Example: Cache recent orders, popular statuses, etc.
    }

    /**
     * Clear all order-related caches (use sparingly)
     */
    public function clearAllOrderCaches()
    {
        try {
            Cache::tags([self::CACHE_PREFIX])->flush();
        } catch (\Exception $e) {
            // Fallback for cache drivers that don't support tags
            $this->clearCachesByPattern(self::CACHE_PREFIX . '*');
            Cache::forget(self::CACHE_STATUS_PREFIX);
        }
    }
}

