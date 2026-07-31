<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\OrderApologySetting;

class OrderApologyEmail extends Model
{
    protected $table = 'order_apology_emails';

    protected $fillable = [
        'order_id',
        'sent_count',
        'last_sent_at',
        'next_send_at',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
        'sent_count'   => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function canSendNow(): bool
    {
        if (!$this->last_sent_at) {
            return true;
        }
        $cooldown = OrderApologySetting::current()->cooldown_days;
        return now()->diffInDays($this->last_sent_at, true) >= $cooldown;
    }

    public function daysUntilNextSend(): int
    {
        if (!$this->next_send_at) return 0;
        $days = (int) ceil(now()->diffInDays($this->next_send_at, false));
        return max(0, $days);
    }
}
