<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReceivingTemp extends Model
{
    protected $table = 'inventory_receiving_temp';

    protected $fillable = [
        'user_id',
        'shipment_id',
        'order_number',
        'product_name',
        'quantity',
        'destination',
        'qr_data',
        'scanned_at',
    ];

    protected $casts = [
        'qr_data' => 'array',
        'scanned_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * Get the user who scanned this item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the inventory shipment
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(InventoryShipment::class, 'shipment_id');
    }
}
