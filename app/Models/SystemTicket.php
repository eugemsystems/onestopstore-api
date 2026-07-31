<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'priority',
        'status',
        'category',
        'attachments',
        'created_by',
        'assigned_to',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Get the attachment models from the stored IDs
     * Also handles legacy format with path/name/type
     */
    public function getAttachmentsAttribute($value)
    {
        // Get the raw value from database
        $rawValue = $this->attributes['attachments'] ?? $value;

        if (!$rawValue || $rawValue === 'null' || $rawValue === '[]') {
            return collect([]);
        }

        // Decode JSON if needed
        $attachmentData = is_string($rawValue) ? json_decode($rawValue, true) : $rawValue;

        // Handle invalid JSON or empty values
        if (!is_array($attachmentData) || empty($attachmentData)) {
            return collect([]);
        }

        // Check if this is the old format (array of objects with 'path', 'name', 'type')
        $firstItem = reset($attachmentData);
        if (is_array($firstItem) && isset($firstItem['path'])) {
            // Old format - return as-is for backward compatibility
            return collect($attachmentData)->map(function($item) {
                return (object) [
                    'image_url' => asset('storage/' . $item['path']),
                    'name' => $item['name'] ?? 'File',
                    'mime_type' => $item['type'] ?? 'application/octet-stream',
                ];
            });
        }

        // New format - array of attachment IDs (numeric bigint)
        // Flatten in case of nested arrays and filter out non-numeric values
        $attachmentIds = collect($attachmentData)->flatten()->filter(function($id) {
            return is_numeric($id) && $id > 0;
        })->unique()->values()->toArray();

        if (empty($attachmentIds)) {
            return collect([]);
        }

        // Look up by numeric 'id' field
        return \App\Models\Attachment::whereIn('id', $attachmentIds)->get();
    }

    /**
     * Set the attachments attribute (store as JSON array of IDs)
     */
    public function setAttachmentsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['attachments'] = json_encode($value);
        } else {
            $this->attributes['attachments'] = $value;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            $ticket->ticket_number = 'TKT-' . strtoupper(uniqid());
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function activities()
    {
        return $this->hasMany(TicketActivity::class, 'ticket_id');
    }

    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
            default => 'Medium',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'testing' => 'Testing',
            'closed' => 'Closed',
            'reopened' => 'Reopened',
            default => 'Open',
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'success',
            'medium' => 'info',
            'high' => 'warning',
            'critical' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'open' => 'primary',
            'in_progress' => 'warning',
            'testing' => 'info',
            'closed' => 'success',
            'reopened' => 'danger',
            default => 'secondary',
        };
    }
}
