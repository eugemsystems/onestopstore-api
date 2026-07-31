<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'consumer_id',
        'balance',
        'non_cashable_balance',
    ];

    protected $casts = [
        'consumer_id' => 'integer',
        'balance' => 'float',
        'non_cashable_balance' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('wallet')->id;
    }

    /**
     * Get the consumer (user) that owns the wallet
     *
     * @return BelongsTo
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id')->orderBy('created_at','desc');
    }
}
