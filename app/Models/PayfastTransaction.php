<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayfastTransaction extends Model
{
    use HasFactory;

    protected $table = 'payfast_transactions';

    protected $fillable = [
        // Package fields (used by orders and laybys)
        'user_id',
        'm_payment_id',
        'pf_payment_id',
        'payment_status',
        'item_name',
        'item_description',
        'amount_gross',
        'amount_fee',
        'amount_net',
        'custom_str1',
        'custom_str2',
        'custom_str3',
        'custom_str4',
        'custom_str5',
        'custom_int1',
        'custom_int2',
        'custom_int3',
        'custom_int4',
        'custom_int5',
        'name_first',
        'name_last',
        'email_address',
        'merchant_id',
        'signature',
        'response',
        // Legacy fields (if they exist)
        'gateway_transaction_id',
        'status',
        'amount',
        'currency',
        'raw_response',
        'other_fields',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount_gross' => 'decimal:2',
        'amount_fee' => 'decimal:2',
        'amount_net' => 'decimal:2',
        'custom_int1' => 'integer',
        'custom_int2' => 'integer',
        'custom_int3' => 'integer',
        'custom_int4' => 'integer',
        'custom_int5' => 'integer',
        'merchant_id' => 'integer',
        'response' => 'array',
        'amount' => 'float',
        'raw_response' => 'array',
        'other_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
