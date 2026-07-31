<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Trait to exclude temporary layby payment orders from queries.
 *
 * These are bridge records created temporarily for gateway webhooks
 * and should never appear in normal order listings/queries.
 *
 * Usage: Add `use ExcludesTempLaybyOrders;` to the Order model.
 *
 * To include temp orders in a specific query:
 * Order::withTempLaybyOrders()->get()
 *
 * Similar to SoftDeletes, this trait automatically applies a global scope.
 */
trait ExcludesTempLaybyOrders
{
    /**
     * Boot the excludes temp layby orders trait for a model.
     */
    public static function bootExcludesTempLaybyOrders(): void
    {
        static::addGlobalScope(new ExcludeTempLaybyScope);
    }

    /**
     * Get a new query builder that includes temp layby orders.
     */
    public static function withTempLaybyOrders(): Builder
    {
        return static::query()->withoutGlobalScope(ExcludeTempLaybyScope::class);
    }

    /**
     * Determine if the model instance is a temporary layby order.
     */
    public function isTempLaybyOrder(): bool
    {
        return $this->note && str_starts_with($this->note, 'TEMP_LAYBY_PAYMENT:');
    }

    /**
     * Determine if the model instance is an auction deposit order.
     */
    public function isAuctionDepositOrder(): bool
    {
        return $this->note && str_starts_with($this->note, 'AUC_DEPOSIT:');
    }
}

/**
 * Scope to exclude temporary layby payment orders.
 */
class ExcludeTempLaybyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(function ($query) {
            $query->whereNull('note')
                  ->orWhere(function ($q) {
                      $q->where('note', 'NOT LIKE', 'TEMP_LAYBY_PAYMENT:%')
                        ->where('note', 'NOT LIKE', 'AUC_DEPOSIT:%')
                        ->where('note', 'NOT LIKE', 'AUC_WIN:%');
                  });
        });
    }
}

