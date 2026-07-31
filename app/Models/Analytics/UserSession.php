<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserSession extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'analytics';

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'platform',
        'country',
        'city',
        'landing_page',
        'referrer',
        'started_at',
        'last_activity_at',
        'ended_at',
        'total_page_views',
        'total_events',
        'duration',
        'is_bot',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_page_views' => 'integer',
        'total_events' => 'integer',
        'duration' => 'integer',
        'is_bot' => 'boolean',
    ];

    /**
     * Scope to exclude bot traffic from reporting queries.
     */
    public function scopeHuman($query)
    {
        return $query->where('is_bot', false);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pageViews()
    {
        return $this->hasMany(PageView::class, 'session_id', 'session_id');
    }

    public function events()
    {
        return $this->hasMany(UserEvent::class, 'session_id', 'session_id');
    }
}
