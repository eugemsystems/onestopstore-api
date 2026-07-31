<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name', 'event', 'description',
        'subject_type', 'subject_id',
        'causer_type', 'causer_id',
        'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /** The entity that was acted upon */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** The user/admin who performed the action */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /** Convenience: old values from properties */
    public function getOldAttribute(): array
    {
        return $this->properties['old'] ?? [];
    }

    /** Convenience: new values from properties */
    public function getNewAttribute(): array
    {
        return $this->properties['attributes'] ?? [];
    }

    /** Scope: filter by causer */
    public function scopeForUser($query, $userId)
    {
        return $query->where('causer_type', \App\Models\User::class)
                     ->where('causer_id', $userId);
    }

    /** Scope: filter by subject type */
    public function scopeForSubject($query, string $type)
    {
        return $query->where('subject_type', $type);
    }

    /** Scope: date range */
    public function scopeInRange($query, $from, $to)
    {
        if ($from) $query->where('created_at', '>=', $from);
        if ($to)   $query->where('created_at', '<=', $to);
        return $query;
    }
}
