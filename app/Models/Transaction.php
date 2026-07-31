<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'point_id',
        'order_id',
        'detail',
        'amount',
        'type',
        'from',
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'point_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'float',
        'from' => 'integer',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('wallet')->id;
    }

    /**
     * Get the wallet that owns the transaction
     *
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the order associated with the transaction
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the point associated with the transaction
     *
     * @return BelongsTo
     */
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'point_id');
    }

    /**
     * Get the user who created this transaction
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from');
    }

    /**
     * Legacy alias for createdBy relationship
     *
     * @return BelongsTo
     */
    public function from(): BelongsTo
    {
        return $this->createdBy();
    }
}
