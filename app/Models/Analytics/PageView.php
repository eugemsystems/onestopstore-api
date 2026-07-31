<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PageView extends Model
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
        'url',
        'path',
        'page_title',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'duration',
        'is_bot',
    ];

    protected $casts = [
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

    public function session()
    {
        return $this->belongsTo(UserSession::class, 'session_id', 'session_id');
    }
}
