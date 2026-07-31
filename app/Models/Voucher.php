<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'amount',
        'currency_code',
        'product_id',
        'order_id',
        'purchased_by',
        'redeemed_by',
        'status',
        'redeemed_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function purchasedBy()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function redeemedBy()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired()
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRedeemed()
    {
        return $this->status === 'redeemed';
    }

    public function canRedeem()
    {
        return $this->isActive() && !$this->isExpired() && !$this->isRedeemed();
    }

    // Static helper to generate unique code
    public static function generateUniqueCode($length = 12)
    {
        do {
            // Generate code with format: XXXX-XXXX-XXXX
            $code = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    // Redeem voucher for a user
    public function redeem(User $user)
    {
        if (!$this->canRedeem()) {
            throw new \Exception('This voucher cannot be redeemed.');
        }

        // Get or create wallet for user
        $wallet = \App\Models\Wallet::firstOrCreate(
            ['consumer_id' => $user->id],
            ['balance' => 0, 'non_cashable_balance' => 0]
        );

        // Add to user's non-cashable balance (gift card credit - can only be used for purchases, not withdrawn)
        $wallet->non_cashable_balance = $wallet->non_cashable_balance + $this->amount;
        $wallet->save();

        // Create wallet transaction for audit trail with proper fields
        \App\Models\Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $this->amount,
            'type' => 'credit',
            'detail' => 'Gift Card Voucher Redeemed - Code: ' . $this->code . ' - Amount: ' . $this->currency_code . ' ' . number_format($this->amount, 2) . ' credited to wallet (non-cashable)',
            'from' => $user->id, // User who redeemed the voucher
        ]);

        // Update voucher status
        $this->update([
            'status' => 'redeemed',
            'redeemed_by' => $user->id,
            'redeemed_at' => now(),
        ]);

        return $this;
    }
}

