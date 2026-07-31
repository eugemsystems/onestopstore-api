<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaybyApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_number',
        'user_id',
        'product_id',
        'variation_id',
        'selected_attribute_ids',
        'variation_display_name',
        'id_document_path', // Legacy - kept for backward compatibility
        'id_document_attachment_id', // New - references laravel-media attachments
        'id_document_type',
        'id_document_number',
        'id_document_reminder_count',
        'id_document_last_reminder_at',
        'product_name',
        'product_price',
        'currency',
        'currency_symbol',
        'exchange_rate',
        'duration_months',
        'deposit_amount',
        'monthly_amount',
        'total_amount',
        'status',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'approved_by',
        'total_paid',
        'balance_remaining',
        'last_payment_at',
        'completed_at',
        'order_id',
    ];

    protected $casts = [
        'selected_attribute_ids' => 'array',
        'product_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'completed_at' => 'datetime',
        'id_document_last_reminder_at' => 'datetime',
        'id_document_reminder_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->application_number)) {
                $model->application_number = 'LB-' . strtoupper(uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LaybyPayment::class);
    }

    public function completedPayments(): HasMany
    {
        return $this->hasMany(LaybyPayment::class)->where('payment_status', 'completed');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function idDocumentAttachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'id_document_attachment_id');
    }

    // Helper methods
    public function isApproved(): bool
    {
        return $this->status === 'approved' || $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_amount <= 0) return 0;
        return round(($this->total_paid / $this->total_amount) * 100, 2);
    }

    public function getRemainingMonths(): int
    {
        $paidMonths = floor($this->total_paid / $this->monthly_amount);
        return max(0, $this->duration_months - $paidMonths);
    }
}

