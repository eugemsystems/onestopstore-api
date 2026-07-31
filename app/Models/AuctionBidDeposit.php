<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBidDeposit extends Model
{
    protected $fillable = [
        'user_id',
        'auction_item_id',
        'amount',
        'payment_method',
        'order_id',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'paid_at'      => 'datetime',
        'refunded_at'  => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auctionItem(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class, 'auction_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }
}
