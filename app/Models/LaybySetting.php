<?php

namespace App\Models;

use App\Observers\LaybySettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([LaybySettingObserver::class])]
class LaybySetting extends Model
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
     * Uses cache with 1 hour TTL
     */
    public static function get(string $key, $default = null)
    {
        $allSettings = Cache::rememberForever('layby_settings', function () {
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
        Cache::forget('layby_settings');
    }

    /**
     * Get all settings as key-value array
     * Uses the same cache as get() method
     */
    public static function all_settings(): array
    {
        return Cache::rememberForever('layby_settings', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('layby_settings');
    }
}

