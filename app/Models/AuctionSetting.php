<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionSetting extends Model
{
    protected $fillable = [
        'require_email_verification',
        'bid_fee_enabled',
        'bid_fee_amount',
        'delivery_enabled',
        'delivery_price',
        'delivery_radius_km',
        'hours_to_pay',
        'reminder_1_hours',
        'reminder_2_hours',
    ];

    protected $casts = [
        'require_email_verification' => 'boolean',
        'bid_fee_enabled'            => 'boolean',
        'bid_fee_amount'             => 'decimal:2',
        'delivery_enabled'           => 'boolean',
        'delivery_price'             => 'decimal:2',
        'delivery_radius_km'         => 'integer',
        'hours_to_pay'               => 'integer',
        'reminder_1_hours'           => 'integer',
        'reminder_2_hours'           => 'integer',
    ];

    /**
     * Always returns the single settings row (or defaults if none exists).
     */
    public static function current(): static
    {
        return static::firstOrCreate([], [
            'require_email_verification' => true,
            'bid_fee_enabled'            => false,
            'bid_fee_amount'             => 0.00,
            'delivery_enabled'           => false,
            'delivery_price'             => 0.00,
            'delivery_radius_km'         => 15,
            'hours_to_pay'               => 48,
            'reminder_1_hours'           => 12,
            'reminder_2_hours'           => 24,
        ]);
    }
}
