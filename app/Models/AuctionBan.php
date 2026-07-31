<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBan extends Model
{
    protected $fillable = [
        'user_id',
        'auction_item_id',
        'banned_at',
        'lifted_at',
        'lifted_by',
        'lift_reason',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
        'lifted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auctionItem(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class);
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function isActive(): bool
    {
        return $this->lifted_at === null;
    }

    /**
     * Check if a given user has an active auction ban.
     */
    public static function isUserBanned(int $userId): bool
    {
        return static::where('user_id', $userId)
            ->whereNull('lifted_at')
            ->exists();
    }

    /**
     * Get the active ban record for a user, if any.
     */
    public static function getActiveBan(int $userId): ?static
    {
        return static::where('user_id', $userId)
            ->whereNull('lifted_at')
            ->with('auctionItem')
            ->latest()
            ->first();
    }
}
