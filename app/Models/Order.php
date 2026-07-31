<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;

use App\Models\Concerns\ExcludesTempLaybyOrders;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use HasFactory, ExcludesTempLaybyOrders;

    protected $fillable = [
        'id',
        'total',
        'status',
        'amount',
        'store_id',
        'tax_total',
        'coupon_id',
        'parent_id',
        'invoice_url',
        'feedback_token',
        'feedback_token_expires_at',
        'consumer_id',
        'order_number',
        'delivered_at',
        'points_amount',
        'created_by_id',
        'payment_status',
        'is_gift_order',
        'currency',
        'currency_symbol',
        'exchange_rate',
        "wallet_balance",
        'shipping_total',
        'fast_shipping_total',
        'payment_method',
        'order_status_id',
        'delivery_interval',
        'billing_address_id',
        'shipping_address_id',
        'delivery_description',
        'coupon_total_discount',
        'delivery_price',
        'payfast_fee',
        'note',
        'qr_code_url',
    ];

    protected $with = [
        'consumer:id,name,email,country_code,phone',
        'order_status:id,name,sequence,slug',
        'status_histories.updated_by:id,name,email',
        'status_histories.old_status:id,name',
        'status_histories.new_status:id,name',
        'notes',
    ];

    protected $hidden = [
        'store',
        'deleted_at',
        'updated_at',
        'delivered_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'shipping_total' => 'float',
        'tax_total' => 'float',
        'delivery_price' => 'float',
        'payfast_fee' => 'float',
        'fast_shipping_total' => 'float',
        'total' => 'float',
        'consumer_id' => 'integer',
        'order_number' => 'integer',
        'store_id' => 'integer',
        'coupon_id' => 'integer',
        'order_status_id' => 'integer',
        'shipping_address_id' => 'integer',
        'billing_address_id' => 'integer',
        'points_amount' => 'float',
        'wallet_balance' => 'float',
        'coupon_total_discount' => 'float',
        'status' => 'integer',
        'created_by_id' => 'integer',
        'is_gift_order' => 'boolean',
        'currency' => 'string',
        'currency_symbol' => 'string',
        'exchange_rate' => 'float',
    ];

    protected $appends = ['summary'];

    /**
     * Exclude auction deposit orders from listings.
     * AUC_DEPOSIT: orders are internal payment records, not customer orders.
     * Call Order::excludeAuctionDeposits() in admin/API listing queries.
     */
    public function scopeExcludeAuctionDeposits(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('note')
              ->orWhere('note', 'NOT LIKE', 'AUC_DEPOSIT:%');
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'order_number';
    }

    /**
     * Get a detailed order summary with all amounts broken down correctly
     */
    public function getSummaryAttribute()
    {
        $subtotal = (float) ($this->amount ?? 0);
        $shipping = (float) ($this->shipping_total ?? 0);
        $fastShipping = (float) ($this->fast_shipping_total ?? 0);
        $delivery = (float) ($this->delivery_price ?? 0);
        $tax = (float) ($this->tax_total ?? 0);
        $coupon = abs((float) ($this->coupon_total_discount ?? 0));
        $points = abs((float) ($this->points_amount ?? 0));
        $wallet = abs((float) ($this->wallet_balance ?? 0));

        // Calculate grand total before any discounts/deductions
        $grandTotal = $subtotal + $shipping + $fastShipping + $delivery + $tax;

        // The order->total field already contains the amount paid (after all deductions)
        // But we recalculate to ensure consistency
        $finalTotal = max(0, $grandTotal - $coupon - $points - $wallet);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'fast_shipping' => $fastShipping,
            'delivery' => $delivery,
            'tax' => $tax,
            'grand_total' => $grandTotal, // Total before any deductions
            'coupon_discount' => $coupon,
            'points_used' => $points,
            'wallet_used' => $wallet,
            'total_discounts' => $coupon + $points + $wallet,
            'final_total' => $finalTotal, // Amount actually paid
            'amount_to_pay' => $finalTotal,
        ];
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->created_by_id = Helpers::getCurrentUserId();
            // Ensure currency fields are always present
            if (empty($model->currency)) {
                $model->currency = 'USD';
            }
            if (empty($model->currency_symbol)) {
                $model->currency_symbol = '$';
            }
            if (empty($model->exchange_rate) || $model->exchange_rate <= 0) {
                $model->exchange_rate = 1.0;
            }
        });
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('order')->id;
    }

    /**
     * @return HasMany
     */
    public function sub_orders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }

    /**
     * @return BelongsTo
     */
    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    /**
     * @return HasMany
     */
    public function reminderEmails(): HasMany
    {
        return $this->hasMany(OrderReminderEmail::class, 'order_id');
    }

    /**
     * @return HasMany
     */
    public function order_transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class, 'order_id');
    }

    /**
     * @return BelongsTo
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    /**
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * @return BelongsTo
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * @return BelongsTo
     */
    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany
     *
     * CRITICAL: withTrashed() ensures order items remain visible even after
     * the product is soft-deleted. Without it, Laravel's SoftDeletes global
     * scope silently filters out deleted products, making order items disappear.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_products')
            ->withTrashed()
            ->using(OrderProductPivot::class)
            ->withPivot(config('enums.order.pivot'));
    }

    /**
     * @return HasOne
     */
    public function shipping_address(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'shipping_address_id');
    }

    /**
     * @return HasOne
     */
    public function billing_address(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'billing_address_id');
    }

    /**
     * @return HasMany
     */
    public function commission_history(): HasMany
    {
        return $this->hasMany(CommissionHistory::class, 'order_id');
    }

    /**
     * @return HasOne
     */
    public function order_status(): HasOne
    {
        return $this->hasOne(OrderStatus::class, 'id', 'order_status_id');
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'order_id');
    }

    /**
     * @return HasMany
     */
    public function notes(): HasMany
    {
        $query = $this->hasMany(OrderNote::class, 'order_id')->orderBy('created_at', 'desc');

        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole(RoleEnum::ADMIN)) {
            $query->where('privacy', 'public');
        }

        return $query;
    }

    /**
     * @return HasMany
     */
    public function status_histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->orderBy('created_at', 'asc');
    }

    /**
     * Generate a unique feedback token for this order
     * Token expires in 30 days
     */
    public function generateFeedbackToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'feedback_token' => $token,
            'feedback_token_expires_at' => now()->addDays(30),
        ]);

        return $token;
    }

    /**
     * Get or generate feedback token
     */
    public function getFeedbackToken(): string
    {
        // If token exists and hasn't expired, return it
        if ($this->feedback_token && $this->feedback_token_expires_at && $this->feedback_token_expires_at > now()) {
            return $this->feedback_token;
        }

        // Otherwise, generate a new one
        return $this->generateFeedbackToken();
    }

    /**
     * Verify if feedback token is valid
     */
    public function verifyFeedbackToken(string $token): bool
    {
        return $this->feedback_token === $token
            && $this->feedback_token_expires_at
            && $this->feedback_token_expires_at > now();
    }

    public function apologyEmail(): HasOne
    {
        return $this->hasOne(OrderApologyEmail::class, 'order_id');
    }
}
