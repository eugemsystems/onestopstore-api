<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AuctionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sort_order',
        'title',
        'branch',
        'description',
        'condition',
        'product_id',
        'images',
        'starting_price',
        'reserve_price',
        'current_bid',
        'bid_count',
        'min_bid_increment',
        'status',
        'starts_at',
        'ends_at',
        'auto_extend_minutes',
        'winner_id',
        'winner_bid',
        'order_id',
        'created_by',
        // Fulfillment fields
        'fulfillment_method',
        'delivery_cost',
        'delivery_address',
        'fulfillment_status',
        'fulfilled_at',
        // Payment reminder fields
        'payment_deadline',
        'reminder_1_sent_at',
        'reminder_2_sent_at',
    ];

    protected $casts = [
        'images'             => 'array',
        'starting_price'     => 'decimal:2',
        'reserve_price'      => 'decimal:2',
        'current_bid'        => 'decimal:2',
        'winner_bid'         => 'decimal:2',
        'min_bid_increment'  => 'decimal:2',
        'delivery_cost'        => 'float',
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'fulfilled_at'         => 'datetime',
        'payment_deadline'     => 'datetime',
        'reminder_1_sent_at'   => 'datetime',
        'reminder_2_sent_at'   => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)->latest();
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('starts_at', '<=', now())
                     ->where('ends_at', '>', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')
                     ->where('starts_at', '>', now());
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at <= now()
            && $this->ends_at > now();
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'active' && $this->starts_at > now();
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended' || ($this->ends_at && $this->ends_at <= now());
    }

    public function minimumNextBid(): float
    {
        $base = $this->current_bid ?? $this->starting_price;
        return (float) $base + (float) $this->min_bid_increment;
    }

    public function timeRemainingSeconds(): int
    {
        if (!$this->ends_at || $this->isEnded()) {
            return 0;
        }
        return max(0, (int) now()->diffInSeconds($this->ends_at, false));
    }

    /**
     * Rewrite legacy /storage/auction-images/ URLs to use the /api/auction-files/
     * streaming route, which bypasses nginx symlink issues on production.
     * Old format: https://old-domain/storage/auction-images/file.jpg
     * New format: https://app.url/api/auction-files/auction-images/file.jpg
     */
    public function getImagesAttribute($value): array
    {
        $raw = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($raw)) {
            return [];
        }

        $base = rtrim(config('app.url'), '/');

        return array_values(array_map(function (string $url) use ($base): string {
            // Already using the new auction-files route — leave as-is
            if (str_contains($url, '/api/auction-files/')) {
                return $url;
            }

            // Legacy: extract just the relative path after /storage/
            if (preg_match('#/storage/(auction-images/.+)$#', $url, $m)) {
                return $base . '/api/auction-files/' . $m[1];
            }

            return $url; // Unknown format — return unchanged
        }, $raw));
    }

    /**
     * Returns the first image URL or null.
     */
    public function getThumbnailAttribute(): ?string
    {
        $images = $this->images ?? [];
        return !empty($images) ? $images[0] : null;
    }
}
