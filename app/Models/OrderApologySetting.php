<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderApologySetting extends Model
{
    protected $table = 'order_apology_settings';

    protected $fillable = [
        'cooldown_days',
        'auto_send_enabled',
        'auto_send_time',
    ];

    protected $casts = [
        'cooldown_days'      => 'integer',
        'auto_send_enabled'  => 'boolean',
    ];

    public static function current(): static
    {
        return static::firstOrCreate([], [
            'cooldown_days'     => 4,
            'auto_send_enabled' => false,
            'auto_send_time'    => '09:00',
        ]);
    }
}
