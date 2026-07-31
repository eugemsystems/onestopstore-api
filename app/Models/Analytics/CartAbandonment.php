<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Order;

class CartAbandonment extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'analytics';

    protected $fillable = [
        'session_id',
        'user_id',
        'email',
        'cart_items',
        'cart_value',
        'currency',
        'items_count',
        'abandonment_stage',
        'abandonment_reason',
        'recovered',
        'order_id',
        'recovered_at',
        'ip_address',
        'device_type',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'cart_value' => 'decimal:2',
        'items_count' => 'integer',
        'recovered' => 'boolean',
        'recovered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
