<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFeedDefinition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category_slugs',
        'skus',
        'currency',
        'min_price',
        'shard_count',
        'created_by',
    ];

    protected $casts = [
        'category_slugs' => 'array',
        'skus' => 'array',
        'min_price' => 'float',
        'shard_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
