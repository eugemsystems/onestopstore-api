<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaybyPayment extends Model
{
    protected $fillable = [
        'layby_application_id',
        'payment_number',
        'amount',
        'currency',
        'currency_symbol',
        'exchange_rate',
        'payment_method',
        'transaction_id',
        'gateway_reference',
        'payment_status',
        'payment_note',
        'payment_meta',
        'paid_at',
        'captured_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'payment_meta' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_number)) {
                $model->payment_number = 'LBP-' . strtoupper(uniqid());
            }
        });
    }

    public function laybyApplication(): BelongsTo
    {
        return $this->belongsTo(LaybyApplication::class);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}

