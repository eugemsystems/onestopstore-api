<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'platform',
        'latest_version',
        'latest_build',
        'minimum_version',
        'force_update',
        'release_notes',
        'store_url',
        'released_at',
    ];

    protected $casts = [
        'force_update' => 'boolean',
        'latest_build' => 'integer',
        'released_at'  => 'datetime',
    ];
}
