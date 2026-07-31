<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DpoZambiaTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'raw_response',
        'trans_id',
        'transaction_token',
        'result',
        'result_code',
        'result_explanation',
        'transaction_status',
        'ccd_approval',
        'company_ref',
        'transaction_currency',
        'payment_amount',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_country',
        'fraud_alert',
        'fraud_explanation',
        'date_created',
        'date_approved',
        'other_fields'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'raw_response' => 'array',
        'other_fields' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
