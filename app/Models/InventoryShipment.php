<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class InventoryShipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order',
        'title',
        'quantity',
        'destination',
        'status',
        'transporter',
        'date',
        'eta',
        'f_status',
        'signed_by',
        'received_by',
        'srs',
        'notes',
        'created_by',
        'updated_by',
        'sticker_downloaded_at',
        'waybill_downloaded_at',
    ];

    protected $casts = [
        'date'                  => 'date',
        'eta'                   => 'date',
        'quantity'              => 'integer',
        'sticker_downloaded_at' => 'datetime',
        'waybill_downloaded_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Track who created the record
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        // Track who updated the record and log changes
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }

            // Log the changes
            $model->logChanges('updated');
        });

        // Log deletion
        static::deleting(function ($model) {
            $model->logChanges('deleted');
        });

        // Log restoration
        static::restored(function ($model) {
            $model->logChanges('restored');
        });

        // Log creation after it's saved
        static::created(function ($model) {
            $model->logChanges('created');
        });
    }

    /**
     * Log changes to history table
     */
    protected function logChanges($action)
    {
        $changes = [];
        $oldValues = [];
        $newValues = [];

        if ($action === 'updated') {
            $dirtyAttributes = $this->getDirty();
            $originalAttributes = $this->getOriginal();

            foreach ($dirtyAttributes as $key => $newValue) {
                if (in_array($key, ['updated_at', 'updated_by'])) {
                    continue; // Skip these meta fields
                }

                $oldValue = $originalAttributes[$key] ?? null;

                if ($oldValue != $newValue) {
                    $changes[] = [
                        'field' => $key,
                        'from' => $oldValue,
                        'to' => $newValue,
                    ];
                    $oldValues[$key] = $oldValue;
                    $newValues[$key] = $newValue;
                }
            }
        } elseif ($action === 'created') {
            $newValues = $this->getAttributes();
        } elseif ($action === 'deleted') {
            $oldValues = $this->getOriginal();
        } elseif ($action === 'restored') {
            $newValues = $this->getAttributes();
        }

        // Only log if there are actual changes or it's a creation/deletion
        if (!empty($changes) || in_array($action, ['created', 'deleted', 'restored'])) {
            InventoryShipmentHistory::create([
                'shipment_id' => $this->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'changes' => !empty($changes) ? json_encode($changes) : null,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
            ]);
        }
    }

    /**
     * Relationships
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function history()
    {
        return $this->hasMany(InventoryShipmentHistory::class, 'shipment_id')->orderBy('created_at', 'desc');
    }

    /**
     * Scopes for filtering
     */
    public function scopeByDestination($query, $destination)
    {
        if ($destination) {
            return $query->where('destination', $destination);
        }
        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeByTransporter($query, $transporter)
    {
        if ($transporter) {
            return $query->where('transporter', 'LIKE', "%{$transporter}%");
        }
        return $query;
    }

    public function scopeByFStatus($query, $fStatus)
    {
        if ($fStatus) {
            return $query->where('f_status', 'LIKE', "%{$fStatus}%");
        }
        return $query;
    }

    public function scopeBySignedBy($query, $signedBy)
    {
        if ($signedBy) {
            return $query->where('signed_by', $signedBy);
        }
        return $query;
    }

    public function scopeByReceivedBy($query, $receivedBy)
    {
        if ($receivedBy) {
            return $query->where('received_by', $receivedBy);
        }
        return $query;
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            return $query->where('date', '>=', $startDate);
        } elseif ($endDate) {
            return $query->where('date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'Received' => 'success',
            'Not yet' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get formatted display for mobile view
     */
    public function getMobileDisplayAttribute()
    {
        return [
            'title' => $this->title,
            'info' => sprintf('%s • %s • Qty: %d',
                $this->destination,
                $this->status,
                $this->quantity
            ),
            'dates' => sprintf('Date: %s | ETA: %s',
                $this->date?->format('M d, Y') ?? 'N/A',
                $this->eta?->format('M d, Y') ?? 'N/A'
            ),
        ];
    }
}

