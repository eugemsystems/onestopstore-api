<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'old_status_id',
        'new_status_id',
        'updated_by_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function old_status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'old_status_id');
    }

    public function new_status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'new_status_id');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
