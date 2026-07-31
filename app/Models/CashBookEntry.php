<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBookEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch',
        'entry_date',
        'entry_time',
        'remark',
        'party',
        'category_id',
        'mode',
        'entered_by',
        'cash_in',
        'cash_out',
        'balance',
        'reference_number',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(CashBookCategory::class, 'category_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function order()
    {
        // Simple relationship - only use when reference_type is 'order'
        return $this->belongsTo(Order::class, 'reference_id');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('cash_in', '>', 0);
    }

    public function scopeExpense($query)
    {
        return $query->where('cash_out', '>', 0);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByMode($query, $mode)
    {
        return $query->where('mode', $mode);
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->cash_in - $this->cash_out;
    }

    public function getTypeAttribute()
    {
        if ($this->cash_in > 0) {
            return 'income';
        } elseif ($this->cash_out > 0) {
            return 'expense';
        }
        return 'neutral';
    }
}
