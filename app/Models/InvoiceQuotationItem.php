<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceQuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_quotation_id',
        'product_id',
        'variation_id',
        'product_name',
        'sku',
        'description',
        'image_url',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function invoiceQuotation()
    {
        return $this->belongsTo(InvoiceQuotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    // Calculate subtotal before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });
    }
}

