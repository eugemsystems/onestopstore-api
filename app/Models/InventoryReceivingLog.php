<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReceivingLog extends Model
{
    protected $table = 'inventory_receiving_logs';

    protected $fillable = [
        'user_id',
        'branch',
        'shipment_id',
        'order_number',
        'product_name',
        'quantity',
        'destination',
        'scanned_at',
        'saved_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'saved_at'   => 'datetime',
        'quantity'   => 'integer',
    ];

    /**
     * Get the user who scanned this item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the inventory shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(InventoryShipment::class, 'shipment_id');
    }
}
