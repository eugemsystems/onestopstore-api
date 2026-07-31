<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserEvent extends Model
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
        'event_type',
        'event_category',
        'event_name',
        'event_data',
        'page_url',
        'element_id',
        'element_class',
        'element_text',
        'value',
        'is_bot',
    ];

    protected $casts = [
        'event_data' => 'array',
        'value' => 'float',
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
