<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesepayTransaction extends Model
{
    use HasFactory;

    protected $table = 'pesepay_transactions';

    protected $fillable = [
        'order_id',
        'reference_number',
        'gateway_transaction_id',
        'status',
        'amount',
        'currency',
        'raw_response',
        'other_fields',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'amount' => 'float',
        'raw_response' => 'array',
        'other_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
