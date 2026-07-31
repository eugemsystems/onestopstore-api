<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount',
        'message',
        'status',
        'vendor_wallet_id',
        'is_used',
        'payment_type',
        'payment_details',
        'vendor_id',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'payment_reference',
        'admin_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'float',
        'message' => 'string',
        'order_id' => 'integer',
        'vendor_wallet_id' => 'integer',
        'vendor_id' => 'integer',
        'payment_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $with = [
        'user',
    ];

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('withdrawRequest')->id;
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
