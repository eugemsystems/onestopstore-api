<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappAgent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'whatsapp_number',
        'job_title_id',
        'branch',
        'profile_picture_url',
        'chat_enabled',
        'available_from',
        'available_to',
        'available_days',
        'sort_order',
    ];

    protected $casts = [
        'chat_enabled'   => 'boolean',
        'available_days' => 'array',
    ];

    protected $appends = ['is_available_now'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(WhatsappJobTitle::class, 'job_title_id');
    }

    /**
     * Returns true if the agent is currently within their available window.
     * Times stored in DB are local (Africa/Harare, UTC+2) — compare in that timezone.
     */
    public function getIsAvailableNowAttribute(): bool
    {
        if (!$this->chat_enabled) {
            return false;
        }

        $tz  = config('app.local_timezone', 'Africa/Harare');
        $now = Carbon::now($tz);

        // Check day of week
        if (!empty($this->available_days)) {
            $dayAbbr = $now->format('D'); // Mon, Tue, Wed …
            if (!in_array($dayAbbr, $this->available_days)) {
                return false;
            }
        }

        // Check time window (both times must be set)
        if ($this->available_from && $this->available_to) {
            $from = Carbon::createFromFormat('H:i', substr($this->available_from, 0, 5), $tz);
            $to   = Carbon::createFromFormat('H:i', substr($this->available_to,   0, 5), $tz);

            // Handle overnight windows (e.g. 22:00 – 06:00)
            if ($to->lt($from)) {
                $to->addDay();
            }

            if (!$now->between($from, $to)) {
                return false;
            }
        }

        return true;
    }
}
