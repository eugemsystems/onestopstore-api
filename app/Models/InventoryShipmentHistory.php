<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryShipmentHistory extends Model
{
    use HasFactory;

    protected $table = 'inventory_shipment_history';

    protected $fillable = [
        'shipment_id',
        'user_id',
        'action',
        'changes',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'changes' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Append computed attributes to JSON
     */
    protected $appends = [
        'action_badge_color',
        'action_text',
    ];

    /**
     * Relationships
     */
    public function shipment()
    {
        return $this->belongsTo(InventoryShipment::class, 'shipment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get action badge color
     */
    public function getActionBadgeColorAttribute()
    {
        return match($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'restored' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get formatted action text
     */
    public function getActionTextAttribute()
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            default => ucfirst($this->action),
        };
    }
}

