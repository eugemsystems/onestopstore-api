<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceQuotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices_quotations';

    protected $fillable = [
        'document_number',
        'document_type',
        'currency_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'user_id',
        'subtotal',
        'discount_amount',
        'discount_type',
        'discount_value',
        'vat_amount',
        'vat_percentage',
        'include_vat',
        'shipping_total',
        'delivery_method',
        'delivery_description',
        'delivery_price',
        'delivery_interval',
        'collection_point',
        'total_amount',
        'notes',
        'terms_conditions',
        'issue_date',
        'due_date',
        'valid_until',
        'status',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'delivery_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'include_vat' => 'boolean',
        'issue_date' => 'date',
        'due_date' => 'date',
        'valid_until' => 'date',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(InvoiceQuotationItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function histories()
    {
        return $this->hasMany(InvoiceQuotationHistory::class)->orderBy('created_at', 'desc');
    }

    // Helper method to log history
    public function logHistory($action, $description, $fieldName = null, $oldValue = null, $newValue = null, $metadata = [])
    {
        return $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    // Helper Methods
    public function getDocumentTypeLabel()
    {
        return match($this->document_type) {
            'invoice' => 'Invoice',
            'quotation' => 'Quotation',
            'receipt' => 'Receipt',
            'proforma' => 'Proforma Invoice',
            'delivery_note' => 'Delivery Note',
            default => ucfirst($this->document_type),
        };
    }

    public function getDocumentNumberPrefix()
    {
        return match($this->document_type) {
            'invoice' => 'INV',
            'quotation' => 'QUO',
            'receipt' => 'REC',
            'proforma' => 'PRO',
            'delivery_note' => 'DEL',
            default => 'DOC',
        };
    }

    public static function generateDocumentNumber($type)
    {
        $prefix = match($type) {
            'invoice' => 'INV',
            'quotation' => 'QUO',
            'receipt' => 'REC',
            'proforma' => 'PRO',
            'delivery_note' => 'DEL',
            default => 'DOC',
        };

        $year = date('Y');
        $like  = "{$prefix}-{$year}-%";

        // Search ALL rows — including autosaves — so a number held by an in-progress
        // autosave is never reissued to a different document.
        // We extract the trailing numeric part and take the MAX to be safe against
        // non-sequential gaps and any ordering quirks.
        $maxRow = self::where('document_number', 'LIKE', $like)
            ->withTrashed()           // also count soft-deleted docs
            ->selectRaw("MAX(CAST(SPLIT_PART(document_number, '-', 3) AS INTEGER)) as max_seq")
            ->value('max_seq');

        $number = ($maxRow !== null) ? ((int) $maxRow + 1) : 1;

        return sprintf('%s-%s-%03d', $prefix, $year, $number);
    }

    public function calculateTotals()
    {
        // Calculate subtotal from items
        $this->subtotal = $this->items()->sum('subtotal');

        // Calculate discount
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = ($this->subtotal * $this->discount_value) / 100;
        } else {
            $this->discount_amount = $this->discount_value;
        }

        // Calculate amount after discount
        $amountAfterDiscount = $this->subtotal - $this->discount_amount;

        // Add shipping fee (rule-based) and delivery fee (method-based)
        $shippingTotal = $this->shipping_total ?? 0;
        $deliveryPrice = $this->delivery_price ?? 0;

        // Calculate VAT on the full taxable amount: products (after discount) + shipping + delivery
        if ($this->include_vat) {
            $vatBase = $amountAfterDiscount + $shippingTotal + $deliveryPrice;
            $this->vat_amount = ($vatBase * $this->vat_percentage) / 100;
        } else {
            $this->vat_amount = 0;
        }

        // Calculate total: subtotal - discount + shipping + delivery + VAT (which already covers all three)
        $this->total_amount = $amountAfterDiscount + $shippingTotal + $deliveryPrice + $this->vat_amount;

        return $this;
    }
}

