<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartReminder extends Model
{
    protected $fillable = [
        'user_id',
        'reminder_number',
        'cart_started_at',
        'email',
        'status',
        'error_message',
        'item_count',
        'sent_at',
    ];

    protected $casts = [
        'cart_started_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that this reminder was sent to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
