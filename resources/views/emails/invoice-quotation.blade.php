{{-- Invoice / Quotation email — admin@raines.africa (client can reply) --}}
@include('emails.partials.layout', [
    'preheader'     => $document->getDocumentTypeLabel() . ' ' . $document->document_number . ' from Raines Africa',
    'emailTitle'    => $document->getDocumentTypeLabel() . ' — ' . $document->document_number,
    'isInteractive' => true,
])

<div class="email-heading-strip">
    <h1>{{ $document->getDocumentTypeLabel() }}</h1>
    <p>Reference: {{ $document->document_number }}</p>
</div>

<p>Dear {{ $document->customer_name }},</p>

@if($customMessage)
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px 20px;margin:16px 0;font-size:14px;line-height:1.8;white-space:pre-wrap;">{{ $customMessage }}</div>
@endif

{{-- Document details --}}
<div class="info-card" style="margin-top:20px;">
    <div class="row">
        <span class="label">Document Number</span>
        <span class="value">{{ $document->document_number }}</span>
    </div>
    <div class="row">
        <span class="label">Issue Date</span>
        <span class="value">{{ $document->issue_date->format('M d, Y') }}</span>
    </div>
    @if($document->due_date)
    <div class="row">
        <span class="label">Due Date</span>
        <span class="value">{{ $document->due_date->format('M d, Y') }}</span>
    </div>
    @endif
    @if($document->valid_until)
    <div class="row">
        <span class="label">Valid Until</span>
        <span class="value">{{ $document->valid_until->format('M d, Y') }}</span>
    </div>
    @endif
    <div class="row">
        <span class="label">Currency</span>
        <span class="value">{{ $document->currency_code }}</span>
    </div>
    <div class="row">
        <span class="label">Items</span>
        <span class="value">{{ $document->items->count() }} item(s)</span>
    </div>
</div>

{{-- Amount summary --}}
<div style="background:#fff9f9;border:1px solid #fde8e8;border-radius:8px;padding:20px;margin:20px 0;text-align:center;">
    <p style="font-size:13px;color:#6b7280;margin:0 0 4px;">Total Amount</p>
    <p style="font-size:32px;font-weight:700;color:#C0392B;margin:0;">
        {{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}
    </p>
</div>

{{-- Breakdown --}}
<table class="summary-table">
    <tr>
        <td>Subtotal</td>
        <td>{{ $document->currency_code }} {{ number_format($document->subtotal, 2) }}</td>
    </tr>
    @if($document->discount_amount > 0)
    <tr>
        <td style="color:#C0392B;">Discount</td>
        <td style="color:#C0392B;">– {{ $document->currency_code }} {{ number_format($document->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if($document->include_vat && $document->vat_amount > 0)
    <tr>
        <td>VAT ({{ number_format($document->vat_percentage, 2) }}%)</td>
        <td>{{ $document->currency_code }} {{ number_format($document->vat_amount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td>Total</td>
        <td>{{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}</td>
    </tr>
</table>

{{-- Attachment notice --}}
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin:20px 0;font-size:14px;font-weight:600;color:#1e40af;text-align:center;">
    📎 The complete {{ strtolower($document->getDocumentTypeLabel()) }} is attached as a PDF
</div>

@if($document->notes)
<div class="highlight-box">
    <strong>Notes:</strong><br>{{ $document->notes }}
</div>
@endif

<p style="margin-top:24px;">
    If you have any questions or need clarification, please reply to this email or contact us below.
</p>

<p>Best regards,<br><strong>{{ $senderName }}</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => true])
