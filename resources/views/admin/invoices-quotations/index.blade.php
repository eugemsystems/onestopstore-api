@extends('admin.layout')

@section('title', 'Invoices & Quotations - Admin Panel')

@push('styles')
<style>
/* ── Page Header ── */
.iq-page-header {
    background: linear-gradient(135deg, #0a2d6b 0%, #1a5cb8 60%, #2563eb 100%);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(10,45,107,.22);
}
.iq-header-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #fff;
    margin-right: 16px; flex-shrink: 0;
}
.iq-page-header h2 { color:#fff; font-weight:800; margin:0 0 2px; font-size:1.35rem; }
.iq-page-header p  { color:rgba(255,255,255,.65); margin:0; font-size:.82rem; }
.iq-header-actions { display:flex; gap:10px; }

/* ── Type pills (document type badges) ── */
.doc-type-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 11px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
    white-space: nowrap;
}
.doc-type-pill i { font-size: .72rem; }

/* ── Status pills ── */
.status-pill-iq {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
}

/* ── Filter bar ── */
.iq-filter-bar {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 14px 18px;
    margin-bottom: 18px;
    box-shadow: 0 1px 6px rgba(0,0,0,.04);
}
.filter-input-group {
    position: relative;
    display: flex;
    align-items: center;
}
.filter-icon {
    position: absolute;
    left: 11px;
    color: #94a3b8;
    font-size: .85rem;
    z-index: 1;
}
.filter-input, .filter-select {
    padding-left: 32px !important;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: .82rem;
    height: 36px;
    width: 100%;
    background: #f8fafc;
    color: #0f172a;
    outline: none;
    transition: border-color .15s;
}
.filter-input:focus, .filter-select:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}

/* ── Documents table ── */
.iq-table-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,.06);
    overflow: hidden;
}
.iq-table-header {
    background: linear-gradient(135deg, #0a2d6b, #1a5cb8);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.iq-table-header i    { color:#fff; font-size:.9rem; }
.iq-table-header span { color:#fff; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.iq-table { width:100%; border-collapse:collapse; }
.iq-table th {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #64748b;
    padding: 10px 14px; border-bottom: 2px solid #f1f5f9;
    white-space: nowrap; background: #fafafa;
}
.iq-table td {
    font-size: .83rem; padding: 11px 14px;
    border-bottom: 1px solid #f8fafc;
    color: #334155; vertical-align: middle;
}
.iq-table tbody tr:hover { background: #f8fafc; }
.iq-table tbody tr.is-autosave { background: #f0fdf4; }
.iq-table tbody tr.is-autosave:hover { background: #dcfce7; }

/* ── Action buttons ── */
.iq-action-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center; justify-content: center;
    font-size: .85rem;
    border: 1px solid transparent;
    text-decoration: none;
    transition: all .15s;
    cursor: pointer;
    background: none;
}
.iq-action-btn:hover { transform: translateY(-1px); }
.iq-btn-view    { background:#eff6ff; color:#3b82f6; border-color:#bfdbfe; }
.iq-btn-view:hover { background:#dbeafe; color:#2563eb; }
.iq-btn-pdf     { background:#f0fdf4; color:#10b981; border-color:#bbf7d0; }
.iq-btn-pdf:hover { background:#dcfce7; color:#059669; }
.iq-btn-edit    { background:#fffbeb; color:#f59e0b; border-color:#fde68a; }
.iq-btn-edit:hover { background:#fef3c7; color:#d97706; }
.iq-btn-delete  { background:#fef2f2; color:#ef4444; border-color:#fecaca; }
.iq-btn-delete:hover { background:#fee2e2; color:#dc2626; }
.iq-btn-resume  { background:#f0fdf4; color:#10b981; border-color:#bbf7d0; font-size:.72rem; padding:0 12px; width:auto; gap:4px; }
.iq-btn-resume:hover { background:#dcfce7; }

/* ── Create dropdown ── */
.btn-create-iq {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.35);
    color: #fff;
    border-radius: 10px;
    padding: 8px 18px;
    font-size: .82rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}
.btn-create-iq:hover { background: rgba(255,255,255,.28); color:#fff; }
.btn-stats-iq {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2);
    color: rgba(255,255,255,.8);
    border-radius: 10px;
    padding: 8px 14px;
    font-size: .82rem;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: background .15s;
}
.btn-stats-iq:hover { background: rgba(255,255,255,.2); color:#fff; }

.doc-number-link {
    font-weight: 700;
    color: #0f3d8c;
    text-decoration: none;
    font-size: .85rem;
}
.doc-number-link:hover { color: #1a5cb8; text-decoration: underline; }
.draft-tag {
    display: inline-block;
    background: #f0fdf4; color: #16a34a;
    font-size: .62rem; font-weight: 700;
    padding: 1px 7px; border-radius: 20px;
    border: 1px solid #bbf7d0;
    vertical-align: middle;
    margin-left: 4px;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="iq-page-header">
    <div class="d-flex align-items-center">
        <div class="iq-header-icon">
            <i class="bi bi-file-earmark-text-fill"></i>
        </div>
        <div>
            <h2>Invoices &amp; Quotations</h2>
            <p>Create, manage and track all financial documents</p>
        </div>
    </div>
    <div class="iq-header-actions">
        @can('invoice-quotation.stats')
        <a href="{{ route('admin.invoices-quotations.stats') }}" class="btn-stats-iq">
            <i class="bi bi-bar-chart-line"></i> Statistics
        </a>
        @endcan
        @can('invoice-quotation.create')
        <div class="dropdown">
            <button class="btn-create-iq dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-plus-circle-fill"></i> Create New
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px;padding:8px;">
                <li>
                    <a class="dropdown-item rounded-2 py-2" href="{{ route('admin.invoices-quotations.create', ['type' => 'invoice']) }}" style="font-size:.83rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#eff6ff;color:#3b82f6;margin-right:8px;font-size:.8rem;"><i class="bi bi-receipt"></i></span>
                        Invoice
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2" href="{{ route('admin.invoices-quotations.create', ['type' => 'quotation']) }}" style="font-size:.83rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#f0fdf4;color:#10b981;margin-right:8px;font-size:.8rem;"><i class="bi bi-file-text"></i></span>
                        Quotation
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2" href="{{ route('admin.invoices-quotations.create', ['type' => 'proforma']) }}" style="font-size:.83rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#fffbeb;color:#f59e0b;margin-right:8px;font-size:.8rem;"><i class="bi bi-file-earmark-ruled"></i></span>
                        Proforma Invoice
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2" href="{{ route('admin.invoices-quotations.create', ['type' => 'receipt']) }}" style="font-size:.83rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#f0fdf4;color:#059669;margin-right:8px;font-size:.8rem;"><i class="bi bi-receipt-cutoff"></i></span>
                        Receipt
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2" href="{{ route('admin.invoices-quotations.create', ['type' => 'delivery_note']) }}" style="font-size:.83rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#faf5ff;color:#8b5cf6;margin-right:8px;font-size:.8rem;"><i class="bi bi-truck"></i></span>
                        Delivery Note
                    </a>
                </li>
            </ul>
        </div>
        @endcan
    </div>
</div>

{{-- ── Filter Bar ── --}}
<div class="iq-filter-bar">
    <form method="GET" action="{{ route('admin.invoices-quotations.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <div class="filter-input-group">
                <span class="filter-icon"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="filter-input" placeholder="Doc #, customer name, email…" value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-input-group">
                <span class="filter-icon"><i class="bi bi-file-earmark-text"></i></span>
                <select name="type" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="invoice"       {{ request('type') == 'invoice'       ? 'selected' : '' }}>Invoice</option>
                    <option value="quotation"     {{ request('type') == 'quotation'     ? 'selected' : '' }}>Quotation</option>
                    <option value="proforma"      {{ request('type') == 'proforma'      ? 'selected' : '' }}>Proforma</option>
                    <option value="receipt"       {{ request('type') == 'receipt'       ? 'selected' : '' }}>Receipt</option>
                    <option value="delivery_note" {{ request('type') == 'delivery_note' ? 'selected' : '' }}>Delivery Note</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-input-group">
                <span class="filter-icon"><i class="bi bi-circle-half"></i></span>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="autosave"  {{ request('status') == 'autosave'  ? 'selected' : '' }}>Auto-Saved</option>
                    <option value="draft"     {{ request('status') == 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="sent"      {{ request('status') == 'sent'      ? 'selected' : '' }}>Sent</option>
                    <option value="paid"      {{ request('status') == 'paid'      ? 'selected' : '' }}>Paid</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="expired"   {{ request('status') == 'expired'   ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-input-group">
                <span class="filter-icon"><i class="bi bi-currency-exchange"></i></span>
                <select name="currency" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Currencies</option>
                    <option value="USD" {{ request('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="ZWL" {{ request('currency') == 'ZWL' ? 'selected' : '' }}>ZWL</option>
                    <option value="ZMW" {{ request('currency') == 'ZMW' ? 'selected' : '' }}>ZMW</option>
                </select>
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1" style="border-radius:8px;font-size:.82rem;height:36px;">
                <i class="bi bi-funnel-fill me-1"></i>Apply
            </button>
            @if(request()->hasAny(['search','type','status','currency']))
            <a href="{{ route('admin.invoices-quotations.index') }}" class="btn btn-outline-secondary" style="border-radius:8px;height:36px;display:flex;align-items:center;" title="Clear filters">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- ── Documents Table ── --}}
<div class="iq-table-card">
    <div class="iq-table-header">
        <i class="bi bi-table"></i>
        <span>Documents</span>
        @if($documents->total() > 0)
        <span style="margin-left:auto;font-size:.65rem;color:rgba(255,255,255,.6);">{{ $documents->total() }} total</span>
        @endif
    </div>
    <div style="overflow-x:auto;">
        <table class="iq-table">
            <thead>
                <tr>
                    <th>Document &amp; Type</th>
                    <th>Customer</th>
                    <th>Amount &amp; Date</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                @php
                    $typeConfig = match($doc->document_type) {
                        'invoice'       => ['bg'=>'#eff6ff', 'txt'=>'#3b82f6', 'icon'=>'bi-receipt',            'label'=>'Invoice'],
                        'quotation'     => ['bg'=>'#f0fdf4', 'txt'=>'#10b981', 'icon'=>'bi-file-text',          'label'=>'Quotation'],
                        'proforma'      => ['bg'=>'#fffbeb', 'txt'=>'#f59e0b', 'icon'=>'bi-file-earmark-ruled', 'label'=>'Proforma'],
                        'receipt'       => ['bg'=>'#f0fdf4', 'txt'=>'#059669', 'icon'=>'bi-receipt-cutoff',     'label'=>'Receipt'],
                        'delivery_note' => ['bg'=>'#faf5ff', 'txt'=>'#8b5cf6', 'icon'=>'bi-truck',              'label'=>'Delivery Note'],
                        default         => ['bg'=>'#f1f5f9', 'txt'=>'#64748b', 'icon'=>'bi-file-earmark',       'label'=>ucfirst($doc->document_type)],
                    };
                    $statusConfig = match($doc->status) {
                        'autosave'  => ['bg'=>'#f1f5f9', 'txt'=>'#475569', 'icon'=>'bi-clock-history', 'label'=>'Auto-Saved'],
                        'draft'     => ['bg'=>'#f1f5f9', 'txt'=>'#64748b', 'icon'=>'bi-pencil-square', 'label'=>'Draft'],
                        'sent'      => ['bg'=>'#eff6ff', 'txt'=>'#3b82f6', 'icon'=>'bi-send',          'label'=>'Sent'],
                        'paid'      => ['bg'=>'#f0fdf4', 'txt'=>'#10b981', 'icon'=>'bi-check-circle',  'label'=>'Paid'],
                        'cancelled' => ['bg'=>'#fef2f2', 'txt'=>'#ef4444', 'icon'=>'bi-x-circle',      'label'=>'Cancelled'],
                        'expired'   => ['bg'=>'#fff7ed', 'txt'=>'#f59e0b', 'icon'=>'bi-hourglass-split','label'=>'Expired'],
                        default     => ['bg'=>'#f1f5f9', 'txt'=>'#64748b', 'icon'=>'bi-question-circle','label'=>ucfirst($doc->status)],
                    };
                    $isOwnAutosave = $doc->status == 'autosave' && $doc->created_by == auth()->id();
                @endphp
                <tr class="{{ $doc->status == 'autosave' ? 'is-autosave' : '' }}">
                    {{-- Document & Type --}}
                    <td>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @if($doc->status == 'autosave')
                                <span class="doc-number-link" style="color:#334155;">{{ $doc->document_number }}</span>
                                @if($isOwnAutosave)
                                <span class="draft-tag">Your Draft</span>
                                @endif
                            @else
                                <a href="{{ route('admin.invoices-quotations.show', $doc->id) }}" class="doc-number-link">
                                    {{ $doc->document_number }}
                                </a>
                            @endif
                        </div>
                        <span class="doc-type-pill" style="background:{{ $typeConfig['bg'] }};color:{{ $typeConfig['txt'] }};">
                            <i class="bi {{ $typeConfig['icon'] }}"></i>{{ $typeConfig['label'] }}
                        </span>
                    </td>
                    {{-- Customer --}}
                    <td>
                        <div style="font-weight:600;color:#0f172a;font-size:.84rem;">{{ $doc->customer_name }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;">{{ $doc->customer_email }}</div>
                    </td>
                    {{-- Amount & Date --}}
                    <td>
                        <div style="font-weight:700;font-size:.88rem;color:#0f172a;">
                            {{ $doc->currency_code }} {{ number_format($doc->total_amount, 2) }}
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;">{{ $doc->issue_date->format('d M Y') }}</div>
                    </td>
                    {{-- Status --}}
                    <td>
                        <span class="status-pill-iq" style="background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['txt'] }};">
                            <i class="bi {{ $statusConfig['icon'] }}" style="font-size:.68rem;"></i>
                            {{ $statusConfig['label'] }}
                        </span>
                    </td>
                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px;">
                            @if($doc->status == 'autosave')
                                @if($isOwnAutosave)
                                    @can('invoice-quotation.create')
                                    <a href="{{ route('admin.invoices-quotations.create', ['type' => $doc->document_type, 'resume' => $doc->id]) }}"
                                       class="iq-action-btn iq-btn-resume" title="Resume Editing">
                                        <i class="bi bi-play-circle-fill"></i> Resume
                                    </a>
                                    @endcan
                                @else
                                    <span style="font-size:.72rem;color:#94a3b8;font-style:italic;">
                                        by {{ $doc->creator->name ?? 'Unknown' }}
                                    </span>
                                @endif
                                @can('invoice-quotation.delete')
                                @if($doc->created_by == auth()->id())
                                <form action="{{ route('admin.invoices-quotations.destroy', $doc->id) }}" method="POST"
                                      class="d-inline" data-swal-confirm="Delete this auto-saved draft?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="iq-action-btn iq-btn-delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            @else
                                @can('invoice-quotation.show')
                                <a href="{{ route('admin.invoices-quotations.show', $doc->id) }}"
                                   class="iq-action-btn iq-btn-view" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endcan
                                @can('invoice-quotation.download-pdf')
                                <a href="{{ route('admin.invoices-quotations.pdf', $doc->id) }}"
                                   class="iq-action-btn iq-btn-pdf" title="Download PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                @endcan
                                @can('invoice-quotation.edit')
                                <a href="{{ route('admin.invoices-quotations.edit', $doc->id) }}"
                                   class="iq-action-btn iq-btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('invoice-quotation.delete')
                                <form action="{{ route('admin.invoices-quotations.destroy', $doc->id) }}" method="POST"
                                      class="d-inline" data-swal-confirm="Are you sure?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="iq-action-btn iq-btn-delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:52px 20px;">
                        <div style="width:70px;height:70px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;color:#cbd5e1;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <p style="color:#94a3b8;font-size:.9rem;margin-bottom:16px;">No documents found</p>
                        @can('invoice-quotation.create')
                        <a href="{{ route('admin.invoices-quotations.create', ['type' => 'invoice']) }}"
                           class="btn btn-primary" style="border-radius:10px;font-size:.82rem;">
                            <i class="bi bi-plus-circle me-1"></i> Create Your First Document
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
    <div style="padding:14px 20px;border-top:1px solid #f1f5f9;">
        {{ $documents->links() }}
    </div>
    @endif
</div>

@endsection
