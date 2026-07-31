@extends('admin.layout')

@section('title', $document->getDocumentTypeLabel() . ' — ' . $document->document_number)

@push('styles')
<style>
/* ── Page Header ── */
.iq-show-header {
    background: linear-gradient(135deg, #0a2d6b 0%, #1a5cb8 60%, #2563eb 100%);
    border-radius: 16px;
    padding: 22px 26px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 8px 32px rgba(10,45,107,.22);
}
.iq-show-header-left { display:flex; align-items:center; gap:16px; }
.iq-show-icon {
    width:52px; height:52px;
    background:rgba(255,255,255,.15);
    border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; color:#fff; flex-shrink:0;
}
.iq-show-header h2  { color:#fff; font-weight:800; margin:0 0 3px; font-size:1.2rem; }
.iq-show-header p   { color:rgba(255,255,255,.6); margin:0; font-size:.78rem; }
.iq-show-header-actions { display:flex; gap:8px; flex-wrap:wrap; }
.iq-hdr-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px; border-radius:10px; font-size:.78rem; font-weight:700;
    border:1px solid transparent; text-decoration:none; cursor:pointer;
    transition:all .15s;
}
.iq-hdr-btn-email  { background:rgba(255,255,255,.18); color:#fff; border-color:rgba(255,255,255,.3); }
.iq-hdr-btn-email:hover  { background:rgba(255,255,255,.28); color:#fff; }
.iq-hdr-btn-pdf    { background:#f0fdf4; color:#059669; border-color:#bbf7d0; }
.iq-hdr-btn-pdf:hover    { background:#dcfce7; }
.iq-hdr-btn-edit   { background:#fffbeb; color:#d97706; border-color:#fde68a; }
.iq-hdr-btn-edit:hover   { background:#fef3c7; }
.iq-hdr-btn-back   { background:rgba(255,255,255,.1); color:rgba(255,255,255,.7); border-color:rgba(255,255,255,.2); }
.iq-hdr-btn-back:hover   { background:rgba(255,255,255,.18); color:#fff; }

/* ── Layout panels ── */
.iq-panel {
    background:#fff;
    border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
    margin-bottom:18px;
}
.iq-panel-header {
    background:linear-gradient(135deg,#0a2d6b,#1a5cb8);
    padding:11px 18px;
    display:flex; align-items:center; gap:8px;
}
.iq-panel-header i    { color:#fff; font-size:.9rem; }
.iq-panel-header span { color:#fff; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.iq-panel-header-actions { margin-left:auto; }
.iq-panel-body { padding:18px; }

/* ── Sidebar sections (unified card) ── */
.iq-sidebar-section {
    padding:16px 18px;
    border-bottom: 2px solid #c6c6c6;
}
.iq-sidebar-section:last-child { border-bottom:none; }
.iq-section-label {
    font-size:1rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.6px; color:#94a3b8; margin-bottom:10px;
    display:flex; align-items:center; gap:5px;
}
.iq-data-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:5px 0; font-size:.82rem;
}
.iq-data-row .label { color:#64748b; }
.iq-data-row .value { font-weight:600; color:#0f172a; text-align:right; }
.iq-data-row .value.green  { color:#10b981; }
.iq-data-row .value.red    { color:#ef4444; }
.iq-data-row .value.blue   { color:#3b82f6; }
.iq-total-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 0 0; margin-top:6px; border-top:2px solid #f1f5f9;
}
.iq-total-row .label { font-size:.88rem; font-weight:700; color:#0f172a; }
.iq-total-row .value { font-size:1.05rem; font-weight:800; color:#10b981; }

/* ── Status pill ── */
.status-pill-iq {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 14px; border-radius:20px;
    font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.3px;
}
.doc-type-pill-sm {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px;
    font-size:.65rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.3px;
}

/* ── Status update form ── */
.iq-status-select {
    width:100%; padding:7px 12px; border:1px solid #e2e8f0;
    border-radius:8px; font-size:.82rem; color:#0f172a;
    background:#f8fafc; margin-bottom:10px; outline:none;
}
.iq-status-select:focus { border-color:#3b82f6; background:#fff; }
.iq-action-btn-full {
    width:100%; padding:8px 0; border-radius:8px;
    font-size:.78rem; font-weight:700;
    border:1px solid transparent; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:6px;
    text-decoration:none; transition:all .15s;
}
.iq-btn-update  { background:#eff6ff; color:#3b82f6; border-color:#bfdbfe; }
.iq-btn-update:hover  { background:#dbeafe; }
.iq-btn-convert { background:#f0fdf4; color:#059669; border-color:#bbf7d0; margin-top:8px; }
.iq-btn-convert:hover { background:#dcfce7; }
.iq-btn-order   { background:#fffbeb; color:#d97706; border-color:#fde68a; margin-top:8px; }
.iq-btn-order:hover   { background:#fef3c7; }

/* ── History timeline ── */
.iq-timeline-item {
    display:flex; gap:10px; padding:10px 0;
    border-bottom:1px dashed #f1f5f9;
}
.iq-timeline-item:last-child { border-bottom:none; }
.iq-timeline-dot {
    width:28px; height:28px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.7rem;
}
.iq-timeline-text { font-size:.78rem; color:#334155; font-weight:600; line-height:1.3; }
.iq-timeline-meta { font-size:.68rem; color:#94a3b8; margin-top:2px; }

/* ── Document iframe ── */
.doc-iframe-wrap {
    background:#f8fafc;
    border-radius:0;
}
.doc-iframe-wrap iframe {
    width:100%; height:820px; border:none; display:block;
}
</style>
@endpush

@section('content')

@php
    $typeConfig = match($document->document_type) {
        'invoice'       => ['bg'=>'#eff6ff','txt'=>'#3b82f6','icon'=>'bi-receipt',            'label'=>'Invoice'],
        'quotation'     => ['bg'=>'#f0fdf4','txt'=>'#10b981','icon'=>'bi-file-text',          'label'=>'Quotation'],
        'proforma'      => ['bg'=>'#fffbeb','txt'=>'#f59e0b','icon'=>'bi-file-earmark-ruled', 'label'=>'Proforma'],
        'receipt'       => ['bg'=>'#f0fdf4','txt'=>'#059669','icon'=>'bi-receipt-cutoff',     'label'=>'Receipt'],
        'delivery_note' => ['bg'=>'#faf5ff','txt'=>'#8b5cf6','icon'=>'bi-truck',              'label'=>'Delivery Note'],
        default         => ['bg'=>'#f1f5f9','txt'=>'#64748b','icon'=>'bi-file-earmark',       'label'=>ucfirst($document->document_type)],
    };
    $statusConfig = match($document->status) {
        'autosave'  => ['bg'=>'#f1f5f9','txt'=>'#475569','icon'=>'bi-clock-history', 'label'=>'Auto-Saved'],
        'draft'     => ['bg'=>'#f1f5f9','txt'=>'#64748b','icon'=>'bi-pencil-square', 'label'=>'Draft'],
        'sent'      => ['bg'=>'#eff6ff','txt'=>'#3b82f6','icon'=>'bi-send',          'label'=>'Sent'],
        'paid'      => ['bg'=>'#f0fdf4','txt'=>'#10b981','icon'=>'bi-check-circle',  'label'=>'Paid'],
        'cancelled' => ['bg'=>'#fef2f2','txt'=>'#ef4444','icon'=>'bi-x-circle',      'label'=>'Cancelled'],
        'expired'   => ['bg'=>'#fff7ed','txt'=>'#f59e0b','icon'=>'bi-hourglass-split','label'=>'Expired'],
        default     => ['bg'=>'#f1f5f9','txt'=>'#64748b','icon'=>'bi-question-circle','label'=>ucfirst($document->status)],
    };
@endphp

{{-- ── Page Header ── --}}
<div class="iq-show-header">
    <div class="iq-show-header-left">
        <div class="iq-show-icon">
            <i class="bi {{ $typeConfig['icon'] }}"></i>
        </div>
        <div>
            <h2>{{ $document->document_number }}</h2>
            <p>
                <span class="doc-type-pill-sm" style="background:{{ $typeConfig['bg'] }};color:{{ $typeConfig['txt'] }};">
                    <i class="bi {{ $typeConfig['icon'] }}" style="font-size:.65rem;"></i>{{ $typeConfig['label'] }}
                </span>
                &nbsp;&bull;&nbsp;
                <span class="status-pill-iq" style="background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['txt'] }};padding:2px 10px;font-size:.65rem;">
                    <i class="bi {{ $statusConfig['icon'] }}" style="font-size:.62rem;"></i>{{ $statusConfig['label'] }}
                </span>
                &nbsp;&bull;&nbsp; {{ $document->issue_date->format('d M Y') }}
            </p>
        </div>
    </div>
    <div class="iq-show-header-actions">
        @can('invoice-quotation.index')
        <a href="{{ route('admin.invoices-quotations.index') }}" onclick="history.back(); return false;" class="iq-hdr-btn iq-hdr-btn-back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        @endcan
        @can('invoice-quotation.edit')
        <a href="{{ route('admin.invoices-quotations.edit', $document->id) }}" class="iq-hdr-btn iq-hdr-btn-edit">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <button type="button" class="iq-hdr-btn iq-hdr-btn-email" data-bs-toggle="modal" data-bs-target="#emailModal">
            <i class="bi bi-envelope"></i> Send Email
        </button>
        @endcan
        @can('invoice-quotation.download-pdf')
        <a href="{{ route('admin.invoices-quotations.pdf', $document->id) }}" class="iq-hdr-btn iq-hdr-btn-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        @endcan
    </div>
</div>

{{-- ── Main Layout ── --}}
<div class="row g-3">

    {{-- ══ Left: Document Preview ══ --}}
    <div class="col-lg-8">
        <div class="iq-panel">
            <div class="iq-panel-header">
                <i class="bi bi-file-earmark-text"></i>
                <span>Document Preview</span>
                @can('invoice-quotation.download-pdf')
                <div class="iq-panel-header-actions">
                    <a href="{{ route('admin.invoices-quotations.pdf', $document->id) }}"
                       style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:4px 12px;font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;"
                       target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> Open PDF
                    </a>
                </div>
                @endcan
            </div>
            <div class="doc-iframe-wrap">
                <iframe src="{{ route('admin.invoices-quotations.preview', $document->id) }}"></iframe>
            </div>
        </div>
    </div>

    {{-- ══ Right: Unified Sidebar Panel ══ --}}
    <div class="col-lg-4">
        <div class="iq-panel">
            <div class="iq-panel-header">
                <i class="bi bi-layout-sidebar-reverse"></i>
                <span>Document Details</span>
            </div>

            {{-- ── Status & Actions ── --}}
            <div class="iq-sidebar-section">
                <div class="iq-section-label"><i class="bi bi-circle-half"></i> Status</div>
                <div class="mb-3">
                    <span class="status-pill-iq" style="background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['txt'] }};">
                        <i class="bi {{ $statusConfig['icon'] }}"></i>{{ $statusConfig['label'] }}
                    </span>
                </div>
                @can('invoice-quotation.edit')
                <form action="{{ route('admin.invoices-quotations.update-status', $document->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="status" class="iq-status-select" required>
                        <option value="draft"     {{ $document->status == 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="sent"      {{ $document->status == 'sent'      ? 'selected' : '' }}>Sent</option>
                        <option value="paid"      {{ $document->status == 'paid'      ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ $document->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="expired"   {{ $document->status == 'expired'   ? 'selected' : '' }}>Expired</option>
                    </select>
                    <button type="submit" class="iq-action-btn-full iq-btn-update">
                        <i class="bi bi-check2"></i> Update Status
                    </button>
                </form>
                @if($document->document_type == 'quotation' && $document->status != 'cancelled')
                <form action="{{ route('admin.invoices-quotations.convert-to-invoice', $document->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="iq-action-btn-full iq-btn-convert"
                            data-swal-confirm="Convert this quotation to an invoice?">
                        <i class="bi bi-file-earmark-arrow-up"></i> Convert to Invoice
                    </button>
                </form>
                @endif
                @if($document->status == 'paid')
                <form action="{{ route('admin.invoices-quotations.convert-to-order', $document->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="iq-action-btn-full iq-btn-order"
                            data-swal-confirm="Convert to an order? A user account will be created if needed.">
                        <i class="bi bi-cart-check"></i> Convert to Order
                    </button>
                </form>
                @endif

                {{-- ── Convert Document Type ── --}}
                <div style="margin-top:12px;padding-top:12px;border-top:1px dashed #e2e8f0;">
                    <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:8px;display:flex;align-items:center;gap:4px;">
                        <i class="bi bi-arrow-left-right"></i> Convert Document Type
                    </div>
                    <form action="{{ route('admin.invoices-quotations.convert-type', $document->id) }}" method="POST"
                          data-swal-confirm="Convert this document to the selected type? The document number will change.">
                        @csrf @method('PATCH')
                        <select name="target_type" class="iq-status-select" required>
                            <option value="" disabled selected>Select target type…</option>
                            @if($document->document_type !== 'invoice')
                            <option value="invoice"><i class="bi bi-receipt"></i> Invoice</option>
                            @endif
                            @if($document->document_type !== 'quotation')
                            <option value="quotation">Quotation</option>
                            @endif
                            @if($document->document_type !== 'proforma')
                            <option value="proforma">Proforma Invoice</option>
                            @endif
                            @if($document->document_type !== 'receipt')
                            <option value="receipt">Receipt</option>
                            @endif
                            @if($document->document_type !== 'delivery_note')
                            <option value="delivery_note">Delivery Note</option>
                            @endif
                        </select>
                        <button type="submit" class="iq-action-btn-full" style="background:#faf5ff;color:#8b5cf6;border-color:#ddd6fe;">
                            <i class="bi bi-arrow-left-right"></i> Convert
                        </button>
                    </form>
                </div>
                @else
                <p style="font-size:.75rem;color:#94a3b8;margin:0;">You don't have permission to update status</p>
                @endcan
            </div>

            {{-- ── Financial Summary ── --}}
            <div class="iq-sidebar-section">
                <div class="iq-section-label"><i class="bi bi-calculator"></i> Summary</div>
                <div class="iq-data-row">
                    <span class="label">Subtotal</span>
                    <span class="value">{{ $document->currency_code }} {{ number_format($document->subtotal, 2) }}</span>
                </div>
                @if($document->discount_amount > 0)
                <div class="iq-data-row">
                    <span class="label">Discount</span>
                    <span class="value red">− {{ $document->currency_code }} {{ number_format($document->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($document->include_vat && $document->vat_amount > 0)
                <div class="iq-data-row">
                    <span class="label">VAT ({{ number_format($document->vat_percentage, 0) }}%)</span>
                    <span class="value">{{ $document->currency_code }} {{ number_format($document->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($document->shipping_total > 0)
                <div class="iq-data-row">
                    <span class="label"><i class="bi bi-box-seam" style="font-size:.72rem;"></i> Shipping</span>
                    <span class="value blue">{{ $document->currency_code }} {{ number_format($document->shipping_total, 2) }}</span>
                </div>
                @endif
                @if($document->delivery_price > 0)
                <div class="iq-data-row">
                    <span class="label"><i class="bi bi-truck" style="font-size:.72rem;"></i> Delivery</span>
                    <span class="value blue">{{ $document->currency_code }} {{ number_format($document->delivery_price, 2) }}</span>
                </div>
                @endif
                <div class="iq-total-row">
                    <span class="label">Total</span>
                    <span class="value">{{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}</span>
                </div>
                @if($document->delivery_method)
                <div style="margin-top:8px;font-size:.72rem;color:#94a3b8;">
                    <i class="bi bi-info-circle"></i> {{ $document->delivery_description ?? $document->delivery_method }}
                </div>
                @endif
            </div>

            {{-- ── Customer ── --}}
            <div class="iq-sidebar-section">
                <div class="iq-section-label"><i class="bi bi-person"></i> Customer</div>
                <div style="font-weight:700;font-size:.88rem;color:#0f172a;margin-bottom:6px;">{{ $document->customer_name }}</div>
                @if($document->customer_email)
                <div style="font-size:.78rem;color:#64748b;margin-bottom:4px;">
                    <i class="bi bi-envelope" style="width:14px;"></i> {{ $document->customer_email }}
                </div>
                @endif
                @if($document->customer_phone)
                <div style="font-size:.78rem;color:#64748b;margin-bottom:4px;">
                    <i class="bi bi-phone" style="width:14px;"></i> {{ $document->customer_phone }}
                </div>
                @endif
                @if($document->customer_address)
                <div style="font-size:.75rem;color:#94a3b8;margin-top:6px;">
                    <i class="bi bi-geo-alt" style="width:14px;"></i> {{ $document->customer_address }}
                </div>
                @endif
            </div>

            {{-- ── Document Meta ── --}}
            <div class="iq-sidebar-section">
                <div class="iq-section-label"><i class="bi bi-info-circle"></i> Document Info</div>
                <div class="iq-data-row">
                    <span class="label">Type</span>
                    <span class="value">
                        <span class="doc-type-pill-sm" style="background:{{ $typeConfig['bg'] }};color:{{ $typeConfig['txt'] }};">
                            <i class="bi {{ $typeConfig['icon'] }}" style="font-size:.62rem;"></i>{{ $typeConfig['label'] }}
                        </span>
                    </span>
                </div>
                <div class="iq-data-row">
                    <span class="label">Items</span>
                    <span class="value">{{ $document->items->count() }} line item{{ $document->items->count() != 1 ? 's' : '' }}</span>
                </div>
                <div class="iq-data-row">
                    <span class="label">Currency</span>
                    <span class="value">{{ $document->currency_code }}</span>
                </div>
                <div class="iq-data-row">
                    <span class="label">Issue Date</span>
                    <span class="value">{{ $document->issue_date->format('d M Y') }}</span>
                </div>
                @if($document->due_date)
                <div class="iq-data-row">
                    <span class="label">Due Date</span>
                    <span class="value {{ $document->due_date->isPast() && $document->status != 'paid' ? 'red' : '' }}">
                        {{ $document->due_date->format('d M Y') }}
                    </span>
                </div>
                @endif
                @if($document->valid_until)
                <div class="iq-data-row">
                    <span class="label">Valid Until</span>
                    <span class="value">{{ $document->valid_until->format('d M Y') }}</span>
                </div>
                @endif
                @if($document->creator)
                <div class="iq-data-row">
                    <span class="label">Created by</span>
                    <span class="value">{{ $document->creator->name }}</span>
                </div>
                @endif
                <div class="iq-data-row">
                    <span class="label">Created</span>
                    <span class="value" style="color:#64748b;font-weight:500;">{{ $document->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>

            {{-- ── Change History ── --}}
            <div class="iq-sidebar-section">
                <div class="iq-section-label" style="justify-content:space-between;">
                    <span style="display:flex;align-items:center;gap:5px;"><i class="bi bi-clock-history"></i> History</span>
                    @if($document->histories->count() > 5)
                    <a href="#" data-bs-toggle="modal" data-bs-target="#historyModal"
                       style="font-size:.65rem;color:#3b82f6;font-weight:700;text-decoration:none;">
                        View all {{ $document->histories->count() }}
                    </a>
                    @endif
                </div>
                @forelse($document->histories->take(5) as $history)
                <div class="iq-timeline-item">
                    <div class="iq-timeline-dot" style="background:{{ match($history->getActionColor()) { 'success' => '#f0fdf4', 'danger' => '#fef2f2', 'warning' => '#fffbeb', 'info' => '#eff6ff', default => '#f1f5f9' } }};color:{{ match($history->getActionColor()) { 'success' => '#10b981', 'danger' => '#ef4444', 'warning' => '#f59e0b', 'info' => '#3b82f6', default => '#64748b' } }};">
                        <i class="bi {{ $history->getActionIcon() }}"></i>
                    </div>
                    <div>
                        <div class="iq-timeline-text">{{ $history->description }}</div>
                        <div class="iq-timeline-meta">
                            {{ $history->user->name ?? 'System' }} &bull; {{ $history->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:16px 0;color:#94a3b8;font-size:.78rem;">
                    <i class="bi bi-clock-history" style="font-size:1.2rem;display:block;margin-bottom:4px;"></i>
                    No history yet
                </div>
                @endforelse
            </div>

        </div>{{-- end .iq-panel --}}
    </div>

</div>{{-- end row --}}

{{-- ── History Modal ── --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);">
                <h5 class="modal-title text-white" style="font-size:.95rem;font-weight:700;">
                    <i class="bi bi-clock-history me-2"></i>Complete Change History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @foreach($document->histories as $history)
                <div style="display:flex;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;align-items:flex-start;">
                    <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.78rem;background:{{ match($history->getActionColor()) { 'success' => '#f0fdf4', 'danger' => '#fef2f2', 'warning' => '#fffbeb', 'info' => '#eff6ff', default => '#f1f5f9' } }};color:{{ match($history->getActionColor()) { 'success' => '#10b981', 'danger' => '#ef4444', 'warning' => '#f59e0b', 'info' => '#3b82f6', default => '#64748b' } }};">
                        <i class="bi {{ $history->getActionIcon() }}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.82rem;font-weight:600;color:#0f172a;">{{ $history->description }}</div>
                        @if($history->field_name)
                        <div style="font-size:.72rem;color:#64748b;margin-top:2px;">
                            Field: <strong>{{ ucwords(str_replace('_',' ',$history->field_name)) }}</strong>
                            @if($history->old_value) &nbsp;From: <strong>{{ $history->old_value }}</strong>@endif
                            @if($history->new_value) &nbsp;→ <strong>{{ $history->new_value }}</strong>@endif
                        </div>
                        @endif
                        <div style="font-size:.68rem;color:#94a3b8;margin-top:3px;">
                            {{ $history->user->name ?? 'System' }} &bull; {{ $history->created_at->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Email Modal ── --}}
<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <form action="{{ route('admin.invoices-quotations.send-email', $document->id) }}" method="POST" id="emailForm">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);">
                    <h5 class="modal-title text-white" style="font-size:.95rem;font-weight:700;">
                        <i class="bi bi-envelope me-2"></i>Send {{ $document->getDocumentTypeLabel() }} via Email
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3" style="border-radius:10px;font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ $document->document_number }}</strong> will be attached as PDF &mdash;
                        Total: {{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700;color:#64748b;">
                            Recipient Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" class="form-control" style="border-radius:8px;font-size:.82rem;"
                               value="{{ $document->customer_email }}" placeholder="customer@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700;color:#64748b;">Sender Name</label>
                        <input type="text" name="sender_name" class="form-control" style="border-radius:8px;font-size:.82rem;"
                               value="Raines Africa" placeholder="Your Company Name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:700;color:#64748b;">
                            Message <span class="text-danger">*</span>
                        </label>
                        <textarea name="message" id="emailMessage" class="form-control" rows="6"
                                  style="border-radius:8px;font-size:.82rem;" required
                                  placeholder="Enter your message…">Please find attached {{ $document->getDocumentTypeLabel() }} #{{ $document->document_number }} for your reference.

We appreciate your business and look forward to serving you.

If you have any questions, please don't hesitate to contact us.</textarea>
                        <small class="text-muted" style="font-size:.7rem;"><span id="charCount">0</span>/2000 characters</small>
                    </div>
                    <div class="alert alert-secondary" style="border-radius:10px;font-size:.78rem;">
                        <i class="bi bi-paperclip me-1"></i>
                        <strong>Attachment:</strong> 📄 {{ strtolower($document->document_number) }}.pdf
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="sendEmailBtn">
                        <i class="bi bi-send me-1"></i>Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Email char counter
const emailMsg = document.getElementById('emailMessage');
const charCount = document.getElementById('charCount');
if (emailMsg) {
    const update = () => {
        const n = emailMsg.value.length;
        charCount.textContent = n;
        emailMsg.classList.toggle('is-invalid', n > 2000);
    };
    emailMsg.addEventListener('input', update);
    update();
}

// Email form loading state
const emailForm = document.getElementById('emailForm');
if (emailForm) {
    emailForm.addEventListener('submit', () => {
        const btn = document.getElementById('sendEmailBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
    });
}

// PDF download loading state
document.querySelectorAll('a[href*="/pdf"]').forEach(link => {
    link.addEventListener('click', function () {
        const orig = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating…';
        this.classList.add('disabled');
        setTimeout(() => {
            this.innerHTML = orig;
            this.classList.remove('disabled');
        }, 4000);
    });
});
</script>
@endpush

@endsection
