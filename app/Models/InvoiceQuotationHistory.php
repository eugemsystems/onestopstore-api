<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceQuotationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_quotation_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function invoiceQuotation()
    {
        return $this->belongsTo(InvoiceQuotation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper method to get action icon
    public function getActionIcon()
    {
        return match($this->action) {
            'created' => 'bi-plus-circle',
            'updated' => 'bi-pencil',
            'status_changed' => 'bi-arrow-repeat',
            'converted_to_invoice' => 'bi-file-earmark-arrow-up',
            'converted_to_order' => 'bi-cart-check',
            'email_sent' => 'bi-envelope-check',
            'deleted' => 'bi-trash',
            default => 'bi-clock-history',
        };
    }

    // Helper method to get action color
    public function getActionColor()
    {
        return match($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'status_changed' => 'warning',
            'converted_to_invoice' => 'primary',
            'converted_to_order' => 'success',
            'email_sent' => 'info',
            'deleted' => 'danger',
            default => 'secondary',
        };
    }
}

