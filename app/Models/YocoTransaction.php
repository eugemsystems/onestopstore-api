<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YocoTransaction extends Model
{
    use HasFactory;

    protected $table = 'yoco_transactions';

    protected $fillable = [
        'order_id',
        'order_number',
        'gateway_transaction_id',
        'status',
        'amount_cents',
        'currency',
        'raw_response',
        'other_fields',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'amount_cents' => 'integer',
        'raw_response' => 'array',
        'other_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
