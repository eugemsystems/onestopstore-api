<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReminderEmail extends Model
{
    protected $fillable = [
        'order_id',
        'order_number',
        'user_id',
        'email',
        'reminder_type',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that this reminder belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user that this reminder was sent to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reminder type label
     */
    public function getReminderTypeLabelAttribute(): string
    {
        return match($this->reminder_type) {
            'first' => 'First Reminder (12 hours)',
            'second' => 'Second Reminder (24 hours)',
            'cancellation' => 'Auto-Cancellation Notice',
            default => 'Unknown',
        };
    }

    /**
     * Get the status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'sent' => 'bg-success',
            'failed' => 'bg-danger',
            'pending' => 'bg-warning',
            default => 'bg-secondary',
        };
    }
}
