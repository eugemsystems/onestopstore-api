<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;

class OrderNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'note',
        'privacy',
        'created_by_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'created_by_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_by_id = $model->created_by_id ?: (Helpers::getCurrentUserId() ?? null);
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('privacy_visibility', function ($builder) {
            $user = Auth::user();
            if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole(RoleEnum::ADMIN)) {
                $builder->where('privacy', 'public');
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
