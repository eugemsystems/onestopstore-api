<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappJobTitle extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function agents(): HasMany
    {
        return $this->hasMany(WhatsappAgent::class, 'job_title_id');
    }
}
