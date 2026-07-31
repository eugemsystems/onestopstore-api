<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionEvent extends Model
{
    protected $connection = 'analytics';

    public $timestamps = false;

    protected $fillable = [
        'auction_item_id',
        'event',
        'meta',
        'session_id',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    // Note: no belongsTo relation since auction_items lives on a different DB connection
}
