<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyFeedRun extends Model
{
    protected $fillable = [
        'file_key',
        'batch_id',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'records_written',
        'file_path',
        'file_url',
        'file_verified',
        'file_size_bytes',
        'min_price_filter',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'file_verified' => 'boolean',
        'min_price_filter' => 'float',
    ];

    /**
     * The human-readable label for each supported file key.
     */
    public const LABELS = [
        'global_usd' => 'Global (USD)',
        'global_zmw' => 'Global (ZMW)',
        'zimbabwe_usd' => 'Zimbabwe (USD)',
        'zambia_zmw' => 'Zambia (ZMW)',
    ];

    /**
     * The most recent run for each of the 4 file keys (one row per key).
     */
    public static function latestPerFile()
    {
        return static::whereIn('id', function ($query) {
            $query->selectRaw('MAX(id)')
                ->from('daily_feed_runs')
                ->groupBy('file_key');
        })->get()->keyBy('file_key');
    }
}
