<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBid extends Model
{
    protected $fillable = [
        'auction_item_id',
        'user_id',
        'amount',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class, 'auction_item_id');
    }

    // Alias used by eager-loading: with('auctionItem')
    public function auctionItem(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class, 'auction_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
