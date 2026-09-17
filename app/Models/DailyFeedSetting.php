<?php

namespace App\Models;

use App\Observers\DailyFeedSettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([DailyFeedSettingObserver::class])]
class DailyFeedSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key
     * Uses cache with no expiry (cleared on any write via the observer)
     */
    public static function get(string $key, $default = null)
    {
        $allSettings = Cache::rememberForever('daily_feed_settings', function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $allSettings[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear the entire settings cache (observer will re-cache)
        Cache::forget('daily_feed_settings');
    }

    /**
     * Get all settings as key-value array
     * Uses the same cache as get() method
     */
    public static function all_settings(): array
    {
        return Cache::rememberForever('daily_feed_settings', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('daily_feed_settings');
    }
}
