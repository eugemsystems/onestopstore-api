<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class ReturnRequest extends Model
{
    // Map to the actual table name
    protected $table = 'returns';

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'return_reason',
        'sub_reason',
        'description',
        'preferred_outcome',
        'product_not_used',
        'in_original_packaging',
        'include_all_accessories',
        'status',
    ];

    // Relationships for eager loading
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
