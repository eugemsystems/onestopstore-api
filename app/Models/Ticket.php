<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'subject',
        'description',
        'priority',
        'status',
        'category',
        'assigned_to',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $appends = ['unread_count'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-' . strtoupper(Str::random(3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_admin']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Helper methods
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'waiting_customer', 'waiting_admin']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function canBeReopened(): bool
    {
        return $this->status === 'closed' || $this->status === 'resolved';
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function markAsClosed(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    public function getUnreadCountAttribute(): int
    {
        // For now, return 0. Can be enhanced with read tracking
        return 0;
    }
}

