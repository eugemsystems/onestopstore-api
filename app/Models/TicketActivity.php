<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'comment',
        'attachments',
        'old_value',
        'new_value',
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

    public function ticket()
    {
        return $this->belongsTo(SystemTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute()
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'commented' => 'Commented',
            'status_changed' => 'Changed Status',
            'assigned' => 'Assigned',
            'closed' => 'Closed',
            'reopened' => 'Reopened',
            default => 'Updated',
        };
    }
}
