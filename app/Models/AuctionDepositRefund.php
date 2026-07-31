<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionDepositRefund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'auction_item_id',
        'auction_bid_deposit_id',
        'deposit_amount',
        'winner_bid_amount',
        'refund_amount',
        'reason_type',
        'reason',
        'refund_method',
        'status',
        'admin_notes',
        'processed_at',
        'wallet_credited_at',
    ];

    protected $casts = [
        'deposit_amount'      => 'decimal:2',
        'winner_bid_amount'   => 'decimal:2',
        'refund_amount'       => 'decimal:2',
        'processed_at'        => 'datetime',
        'wallet_credited_at'  => 'datetime',
    ];

    protected $with = [
        'user:id,name,email',
        'user.paymentAccounts',
        'auction:id,title,winner_bid,winner_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class, 'auction_item_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(AuctionBidDeposit::class, 'auction_bid_deposit_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
