<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionAutoBid extends Model
{
    protected $fillable = [
        'auction_item_id',
        'user_id',
        'max_amount',
        'is_active',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public function auctionItem(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
