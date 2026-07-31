@extends('admin.layout')

@push('styles')
<style>
/* ── Card header (original colour) ───────────────── */
.ord-card-header {
    background-color: #062a6a;
    color: #fff;
    padding: 9px 16px;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    border-bottom: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ord-card-header h4,.ord-card-header h5,.ord-card-header h6 { margin:0; font-size:inherit; font-weight:inherit; letter-spacing:inherit; text-transform:inherit; color:#fff; }

/* Info rows inside cards */
.ord-info-row { display:flex; align-items:flex-start; gap:10px; padding:7px 0; border-bottom:1px solid #f1f5f9; }
.ord-info-row:last-child { border-bottom:none; padding-bottom:0; }
.ord-info-icon { width:28px; height:28px; border-radius:7px; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#062a6a; font-size:.82rem; flex-shrink:0; }
.ord-info-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; margin-bottom:1px; }
.ord-info-val { font-size:.84rem; font-weight:600; color:#1e293b; }

/* Customer avatar */
.ord-avatar { width:40px; height:40px; border-radius:50%; background:#062a6a; display:flex; align-items:center; justify-content:center; font-size:1rem; font-weight:800; color:#fff; flex-shrink:0; }

/* Compact tracking chips (inside hero) */
.ord-track-chips { display:flex; align-items:center; overflow-x:auto; gap:0; padding-bottom:2px; flex-wrap:nowrap; }
.ord-track-chips::-webkit-scrollbar { height:2px; }
.ord-track-chips::-webkit-scrollbar-thumb { background:rgba(255,255,255,.3); }
.ord-track-chip { display:flex; align-items:center; gap:5px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:20px; padding:4px 11px; font-size:.72rem; font-weight:600; color:rgba(255,255,255,.55); white-space:nowrap; transition:all .2s; }
.ord-track-chip.chip-active { background:#fff; border-color:#fff; color:#062a6a; font-weight:800; box-shadow:0 2px 12px rgba(0,0,0,.15); }
.ord-track-chip.chip-cancelled { background:rgba(239,68,68,.25); border-color:#fca5a5; color:#fca5a5; }
.ord-track-chip .chip-icon { font-size:.75rem; }
.ord-track-chip-date { font-size:.65rem; opacity:.75; margin-left:2px; }
.ord-track-sep { width:20px; height:1px; background:rgba(255,255,255,.2); flex-shrink:0; margin:0 2px; }

/* Unified detail panel */
.ord-unified-panel { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); overflow:hidden; position:sticky; top:20px; max-height:calc(100vh - 40px); overflow-y:auto; }
.ord-unified-panel::-webkit-scrollbar { width:4px; }
.ord-unified-panel::-webkit-scrollbar-track { background:transparent; }
.ord-unified-panel::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:4px; }
.ord-psec { padding:16px 20px; border-bottom:1px solid #f1f5f9; }
.ord-psec:last-child { border-bottom:none; }
.ord-psec-label { font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
.ord-sum-row { display:flex; justify-content:space-between; align-items:center; font-size:.85rem; padding:5px 0; border-bottom:1px solid #f8fafc; color:#475569; }
.ord-sum-row:last-child { border-bottom:none; }
.ord-sum-total { font-weight:700; color:#0f172a; font-size:.92rem; border-top:2px solid #e2e8f0; border-bottom:none; padding-top:8px; margin-top:4px; }
.ord-sum-final { font-weight:800; color:#062a6a; font-size:.95rem; background:#eff6ff; border-radius:8px; padding:8px 10px; margin-top:4px; border-bottom:none; }
.ord-mini-tile { background:#f8fafc; border-radius:10px; padding:10px 12px; text-align:center; border-left:3px solid #e2e8f0; }
.ord-mini-tile-label { font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
.ord-mini-tile-val { font-size:1rem; font-weight:800; }

.ord-card-header-green {
    background: linear-gradient(135deg, #0f7040 0%, #1db954 100%);
    color: #fff;
    padding: 14px 22px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    border-bottom: none;
    border-radius: 0;
}
.ord-card-header-green h4,.ord-card-header-green h5,.ord-card-header-green h6 { margin:0; font-size:inherit; font-weight:inherit; color:#fff; }
.ord-card-header-amber {
    background: linear-gradient(135deg, #92400e 0%, #d4a017 100%);
    color: #fff;
    padding: 14px 22px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    border-bottom: none;
    border-radius: 0;
}
.ord-card-header-amber h4,.ord-card-header-amber h5,.ord-card-header-amber h6 { margin:0; font-size:inherit; font-weight:inherit; color:#fff; }

/* Cards — compact */
.card { border:none !important; border-radius:12px !important; box-shadow:0 1px 8px rgba(0,0,0,.07) !important; overflow:hidden !important; margin-bottom:14px !important; }
.card-body { padding:14px 16px !important; }
.card:hover { box-shadow:0 4px 18px rgba(0,0,0,.10) !important; transition:box-shadow .2s; }
/* Items table compact */
.order-items-table thead th { background:#f8fafc !important; font-size:.72rem !important; padding:8px 10px !important; }
.order-items-table td { padding:10px !important; vertical-align:middle !important; font-size:.84rem !important; }
.order-items-table .product-image-wrapper img { width:44px; height:44px; object-fit:cover; border-radius:8px; }
.order-items-table .placeholder-image { width:44px; height:44px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1.1rem; }
/* Notes redesign */
.ord-note-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.ord-note-item:last-child { border-bottom:none; padding-bottom:0; }
.ord-note-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; margin-top:2px; }
.ord-note-dot.public { background:#dcfce7; color:#16a34a; }
.ord-note-dot.private { background:#f1f5f9; color:#64748b; }
.ord-note-body { flex:1; min-width:0; }
.ord-note-meta { font-size:.68rem; color:#94a3b8; display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:4px; }
.ord-note-text { font-size:.84rem; color:#334155; line-height:1.5; }

/* Tracking timeline */
.tracking-item.active   .tracking-icon { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; border-color:transparent !important; box-shadow:0 4px 18px rgba(15,61,140,.38) !important; transform:scale(1.12) !important; }
.tracking-item.active   .tracking-icon i { color:#fff !important; }
.tracking-item.cancelled .tracking-icon { background:linear-gradient(135deg,#991b1b,#dc2626) !important; border-color:transparent !important; }
.tracking-item.cancelled .tracking-icon i { color:#fff !important; }
.tracking-item.active   .tracking-status { color:#0f3d8c !important; font-weight:800 !important; }
.tracking-item.cancelled .tracking-status { color:#dc2626 !important; font-weight:800 !important; }

/* Table */
.sub-order-header td { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; color:#fff !important; }
.product-name { font-weight:700 !important; color:#0f3d8c !important; font-size:.85rem !important; }
.product-sku code { background:#eff6ff !important; color:#1a5cb8 !important; border-radius:5px !important; border:1px solid #bfdbfe !important; font-size:.72rem !important; }
.grand-total, .grand-total strong { color:#0f3d8c !important; font-weight:800 !important; }

/* Notes */
.note-card { background:linear-gradient(135deg,#fffbeb,#fef9c3) !important; border:1px solid #fde68a !important; border-radius:12px !important; }
.note-card:hover { box-shadow:0 4px 16px rgba(234,179,8,.22) !important; }

/* Modals */
.modal-content { border-radius:14px !important; overflow:hidden !important; }
.modal-header[style*="062a6a"] { background:linear-gradient(135deg,#0a2d6b,#1a5cb8) !important; color:#fff !important; }
</style>
@endpush

@section('title', 'Order Details - #' . $order->order_number)


@section('content')
@php
    // Currency conversion helper
    // All amounts in database are in USD, convert to display currency
    $exchangeRate = floatval($order->exchange_rate ?? 1.0);
    $currencySymbol = $order->currency_symbol ?? '$';

    // Helper function to convert and format amounts
    $formatAmount = function($amountUSD) use ($exchangeRate, $currencySymbol) {
        $converted = $amountUSD * $exchangeRate;
        return $currencySymbol . ' ' . number_format($converted, 2);
    };
@endphp

{{-- ═══════════════ PAGE HERO ═══════════════ --}}
<div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);border-radius:14px;padding:18px 24px;margin-bottom:16px;color:#fff;box-shadow:0 4px 20px rgba(10,45,107,.28);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div style="font-size:.75rem;font-weight:600;letter-spacing:.8px;text-transform:uppercase;opacity:.7;margin-bottom:6px;">
                <i class="bi bi-receipt me-1"></i> Order Details
            </div>
            <h2 style="font-size:2rem;font-weight:800;margin:0 0 10px;color:#fff;letter-spacing:-.5px;">
                #{{ $order->order_number }}
            </h2>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}"
                      style="font-size:.82rem;padding:5px 14px;border-radius:20px;font-weight:700;">
                    {{ $order->order_status->name ?? 'Pending' }}
                </span>
                <span style="opacity:.75;font-size:.85rem;">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ $order->created_at->format('d M Y, H:i') }}
                </span>
                <span style="opacity:.75;font-size:.85rem;">
                    <i class="bi bi-credit-card me-1"></i>
                    {{ strtoupper($order->payment_method ?? '') }}
                </span>
                <span style="font-size:.85rem;font-weight:700;background:rgba(255,255,255,.15);padding:4px 12px;border-radius:20px;">
                    <i class="bi bi-wallet2 me-1"></i>
                    {{ $order->currency_symbol }}{{ number_format(($order->amount ?? 0) * ($order->exchange_rate ?? 1), 2) }}
                </span>
            </div>
        </div>
        <div class="d-flex flex-column gap-2 align-items-end">
            {{-- Nav buttons --}}
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.orders.qr-codes.show', $order->order_number) }}"
                   style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:10px;padding:6px 14px;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .2s;"
                   onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="bi bi-qr-code"></i> QR Codes
                </a>
                <a href="{{ route('admin.orders.index') }}" onclick="history.back(); return false;"
                   style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8);border-radius:10px;padding:6px 14px;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .2s;"
                   onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='rgba(255,255,255,.08)'">
                    <i class="bi bi-arrow-left"></i> All Orders
                </a>
            </div>
            {{-- Admin edit actions — own row --}}
            <div class="d-flex gap-2 align-items-center flex-wrap">
                @can('order.edit')
                <button type="button" data-bs-toggle="modal" data-bs-target="#updatePaymentMethodModal"
                    style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:10px;padding:5px 12px;font-size:.78rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
                    <i class="bi bi-credit-card"></i> Payment <i class="bi bi-pencil-fill" style="font-size:.65rem;opacity:.75;"></i>
                </button>
                @endcan
                @can('order.update-status')
                <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}" onsubmit="return handleStatusUpdate(event)" class="d-inline-flex align-items-center gap-1">
                    @csrf @method('PUT')
                    <select name="order_status_id" id="statusSelectHero"
                        style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:10px;padding:4px 10px;font-size:.76rem;font-weight:600;cursor:pointer;">
                        @foreach($statuses as $status)
                        <option value="{{ $status->id }}" {{ $order->order_status_id == $status->id ? 'selected' : '' }}
                            style="color:#0f172a;background:#fff;">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:10px;padding:4px 10px;font-size:.76rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                        <i class="bi bi-check2"></i> Update
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── Compact detail chips row ── --}}
    <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.1);display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
        {{-- Items --}}
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);">
            <i class="bi bi-box-seam" style="color:#a5b4fc;"></i>
            {{ $order->products->sum('pivot.quantity') }} item(s) · {{ $order->products->count() }} product(s)
        </span>
        {{-- Payment status --}}
        @php $paid = ($order->payment_status ?? '') === 'paid'; @endphp
        <span style="display:inline-flex;align-items:center;gap:5px;background:{{ $paid ? 'rgba(74,222,128,.15)' : 'rgba(251,191,36,.15)' }};border:1px solid {{ $paid ? 'rgba(74,222,128,.4)' : 'rgba(251,191,36,.4)' }};border-radius:20px;padding:3px 10px;font-size:.75rem;color:{{ $paid ? '#86efac' : '#fde68a' }};">
            <i class="bi bi-{{ $paid ? 'check-circle-fill' : 'exclamation-circle-fill' }}"></i>
            {{ ucfirst($order->payment_status ?? 'Pending') }}
        </span>
        {{-- Currency --}}
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);">
            <i class="bi bi-currency-exchange" style="color:#fbbf24;"></i>
            {{ strtoupper($order->currency ?? $order->currency_symbol ?? '') }}
            @if($order->exchange_rate && $order->exchange_rate != 1)<span style="opacity:.6;">· Rate: {{ $order->exchange_rate }}</span>@endif
        </span>
        {{-- Shipping --}}
        @if($order->shipping_total > 0 || $order->delivery_charge > 0)
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);">
            <i class="bi bi-truck" style="color:#5eead4;"></i>
            Shipping: {{ $formatAmount(($order->shipping_total ?? 0) + ($order->delivery_charge ?? 0)) }}
        </span>
        @endif
        {{-- Tax --}}
        @if($order->tax_total > 0)
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);">
            <i class="bi bi-receipt" style="color:#fca5a5;"></i>
            VAT: {{ $formatAmount($order->tax_total) }}
        </span>
        @endif
        {{-- Invoice --}}
        @if($order->invoice_url)
        <a href="{{ $order->invoice_url }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);text-decoration:none;">
            <i class="bi bi-file-earmark-pdf" style="color:#fca5a5;"></i> Invoice
        </a>
        @endif
        {{-- Coupon --}}
        @if($order->coupon_code)
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;font-size:.75rem;color:rgba(255,255,255,.85);">
            <i class="bi bi-tag-fill" style="color:#86efac;"></i>
            {{ $order->coupon_code }}
        </span>
        @endif
    </div>


    {{-- Tracking chips inside hero — per-status colours --}}
    @php
        $currentStatus   = $order->order_status;
        $currentSlug     = strtolower(trim(str_replace([' ', '_'], '-', $currentStatus->slug ?? $currentStatus->name)));
        $currentSequence = $currentStatus->sequence ?? 0;
        $statusIcons2 = [
            'pending'=>'hourglass-split','processing'=>'gear-fill','shipped'=>'box-seam',
            'out-for-delivery'=>'truck','delivered'=>'check-circle-fill','completed'=>'patch-check-fill',
            'cancelled'=>'x-circle-fill','ready-for-collection'=>'bag-check-fill',
        ];
        // Per-status colour palette (bg / border / text) — shown on dark hero background
        $chipPalette = [
            'pending'              => ['rgba(251,191,36,.22)', 'rgba(251,191,36,.55)', '#fbbf24'],
            'processing'           => ['rgba(96,165,250,.22)', 'rgba(96,165,250,.55)', '#93c5fd'],
            'shipped'              => ['rgba(167,139,250,.22)', 'rgba(167,139,250,.55)', '#c4b5fd'],
            'out-for-delivery'     => ['rgba(45,212,191,.22)', 'rgba(45,212,191,.55)', '#5eead4'],
            'delivered'            => ['rgba(74,222,128,.22)', 'rgba(74,222,128,.55)', '#86efac'],
            'completed'            => ['rgba(52,211,153,.22)', 'rgba(52,211,153,.55)', '#6ee7b7'],
            'cancelled'            => ['rgba(248,113,113,.22)', 'rgba(248,113,113,.55)', '#fca5a5'],
            'ready-for-collection' => ['rgba(129,140,248,.22)', 'rgba(129,140,248,.55)', '#a5b4fc'],
        ];
        $trackingStatuses = ($currentSlug === 'cancelled')
            ? collect([$currentStatus])
            : $statuses->filter(fn($s) => $s->sequence <= $currentSequence && strtolower(trim(str_replace([' ','_'],'-',$s->slug??$s->name))) !== 'cancelled')->sortBy('sequence');
    @endphp
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,.1);">
        <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.35);margin-bottom:7px;"><i class="bi bi-signpost-split me-1"></i>Order Progress</div>
        <div class="ord-track-chips">
            @foreach($trackingStatuses as $status)
            @php
                $sl  = strtolower(trim(str_replace([' ','_'],'-',$status->slug??$status->name)));
                $ico = $statusIcons2[$sl] ?? 'circle-fill';
                $act = $status->id == $currentStatus->id;
                $pal = $chipPalette[$sl] ?? ['rgba(255,255,255,.1)','rgba(255,255,255,.25)','rgba(255,255,255,.7)'];
                // Active chip: solid white bg, brand-blue text
                $chipStyle = $act
                    ? 'background:#fff;border:1.5px solid #fff;color:#062a6a;font-weight:800;box-shadow:0 2px 10px rgba(0,0,0,.18);'
                    : "background:{$pal[0]};border:1.5px solid {$pal[1]};color:{$pal[2]};";
            @endphp
            <div class="ord-track-chip" style="{{ $chipStyle }}">
                <i class="bi bi-{{ $ico }} chip-icon"></i>
                {{ $status->name }}
                @if($act && $order->updated_at)<span class="ord-track-chip-date" style="color:{{ $act ? '#475569' : 'inherit' }}">&middot; {{ $order->updated_at->format('d M H:i') }}</span>@endif
            </div>
            @if(!$loop->last)<div class="ord-track-sep"></div>@endif
            @endforeach
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Details -->
    <div class="col-md-8">
    
        <!-- Order Items -->
        <div class="mb-3 table-responsive">
                    <table class="table orders-items">
                        <thead>
                            <tr>
                                <th style="width: 8%">Image</th>
                                <th style="width: 30%">Product Details</th>
                                <th style="width: 30%">Item Info</th>
                                <th style="width: 12%">Price</th>
                                <th style="width: 8%">Qty</th>
                                <th style="width: 12%">Subtotal</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->products as $item)
                                <tr>
                                    <td>
                                        <div class="product-image-wrapper">
                                            @if($item->product_thumbnail && isset($item->product_thumbnail->image_url))
                                                <img src="{{ $item->product_thumbnail->image_url }}" alt="{{ $item->name }}" class="img-fluid rounded">
                                            @elseif($item->product_thumbnail && isset($item->product_thumbnail->original_url))
                                                <img src="{{ $item->product_thumbnail->original_url }}" alt="{{ $item->name }}" class="img-fluid rounded">
                                            @else
                                                <div class="placeholder-image">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-details">
                                            <div class="product-name">{{ $item->pivot->product_name ?? $item->name }}</div>

                                            @if($item->sku)
                                                <div class="product-sku">
                                                    <small class="text-muted">SKU: <code>{{ $item->sku }}</code></small>
                                                </div>
                                            @endif

                                            {{-- Estimated Delivery Text with Icons and Colors --}}
                                            @if($item->pivot->estimated_delivery_text)
                                                <div class="estimated-delivery mt-1">
                                                    @php
                                                        $deliveryText = $item->pivot->estimated_delivery_text;
                                                        $deliveryLower = strtolower($deliveryText);

                                                        // Define icons and colors based on delivery text
                                                        if (str_contains($deliveryLower, 'get it tomorrow') || str_contains($deliveryLower, 'tomorrow')) {
                                                            $icon = 'lightning-fill';
                                                            $bgColor = 'bg-warning';
                                                            $textColor = 'text-dark';
                                                        } elseif (str_contains($deliveryLower, 'today')) {
                                                            $icon = 'clock-fill';
                                                            $bgColor = 'bg-danger';
                                                            $textColor = 'text-white';
                                                        } elseif (str_contains($deliveryLower, 'fast') || str_contains($deliveryLower, 'express')) {
                                                            $icon = 'rocket-takeoff-fill';
                                                            $bgColor = 'bg-primary';
                                                            $textColor = 'text-white';
                                                        } elseif (str_contains($deliveryLower, 'next day')) {
                                                            $icon = 'calendar-check-fill';
                                                            $bgColor = 'bg-success';
                                                            $textColor = 'text-white';
                                                        } elseif (str_contains($deliveryLower, '2-3 days') || str_contains($deliveryLower, '2 to 3')) {
                                                            $icon = 'calendar2-week';
                                                            $bgColor = 'bg-info';
                                                            $textColor = 'text-dark';
                                                        } elseif (str_contains($deliveryLower, 'week') || str_contains($deliveryLower, 'days')) {
                                                            $icon = 'calendar3';
                                                            $bgColor = 'bg-secondary';
                                                            $textColor = 'text-white';
                                                        } else {
                                                            $icon = 'truck';
                                                            $bgColor = 'bg-light';
                                                            $textColor = 'text-dark';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $bgColor }} {{ $textColor }}">
                                                        <i class="bi bi-{{ $icon }}"></i> {{ $deliveryText }}
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Multi-Attribute Variation Display --}}
                                            @if($item->pivot->variation_id)
                                                <div class="product-variations mt-1">
                                                    @php
                                                        // PRIORITY 1: Use variation_display_name from pivot (contains combined name like "Grey, Brown - S")
                                                        $displayName = $item->pivot->variation_display_name ?? null;

                                                        if ($displayName) {
                                                            // Split ONLY by " - " (space-hyphen-space) to separate different attributes
                                                            // This preserves comma-separated values like "Grey, Brown" as a single badge
                                                            $parts = array_filter(array_map('trim', explode(' - ', $displayName)));
                                                        } else {
                                                            // FALLBACK: Use variation object attributes or name
                                                            $parts = [];
                                                            $variation = collect($item->variations ?? [])->firstWhere('id', $item->pivot->variation_id);

                                                            if ($variation && isset($variation->attribute_values) && count($variation->attribute_values) > 0) {
                                                                $parts = collect($variation->attribute_values)->pluck('value')->toArray();
                                                            } elseif ($variation && isset($variation->name)) {
                                                                $parts = [$variation->name];
                                                            }
                                                        }
                                                    @endphp

                                                    @if(count($parts) > 0)
                                                        @foreach($parts as $part)
                                                            <span class="badge bg-success">{{ $part }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="item-info">
                                            @if($item->pivot->item_status)
                                                <div class="mb-1">
                                                    <small><strong>Status:</strong></small>
                                                    <span class="badge"
                                                          style="background-color: {{ \App\Helpers\OrderStatusColors::hex($item->pivot->item_status) }}; color: {{ \App\Helpers\OrderStatusColors::textColor(\App\Helpers\OrderStatusColors::hex($item->pivot->item_status)) }};">
                                                        {{ ucwords(str_replace('_', ' ', $item->pivot->item_status)) }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if($item->pivot->shipping_speed)
                                                <div class="mb-1">
                                                    <small><strong>Shipping:</strong></small>
                                                    <span class="badge bg-{{ $item->pivot->shipping_speed == 'express' ? 'danger' : ($item->pivot->shipping_speed == 'fast' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($item->pivot->shipping_speed) }}
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Show Fast Shipping if it was applied during order creation -->
                                            @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping)
                                                <div class="mb-1">
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-lightning-fill"></i> Fast Shipping Applied
                                                    </span>
                                                    @if(isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                                        <br><small class="text-muted">Cost: {{ $formatAmount($item->pivot->fast_shipping_cost) }} × {{ $item->pivot->quantity }}</small>
                                                    @endif
                                                </div>
                                            @endif

                                            @if($item->pivot->eta)
                                                <div class="mb-1">
                                                    <small><strong>ETA:</strong></small>
                                                    <span class="text-primary">
                                                        <i class="bi bi-calendar-event"></i> {{ \Carbon\Carbon::parse($item->pivot->eta)->format('Y-m-d') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-pricing">
                                            @php
                                                // Always use frozen pivot snapshot — these are the prices paid at order time.
                                                // Never read live product/variation prices here, as they may have changed.
                                                $paidPrice       = (float) ($item->pivot->single_price ?? 0);
                                                $snapshotOrig    = (float) ($item->pivot->product_price ?? 0);
                                                $snapshotSale    = (float) ($item->pivot->product_sale_price ?? 0);
                                                $hadSaleAtOrder  = $snapshotSale > 0 && $snapshotSale < $snapshotOrig;
                                            @endphp

                                            @if($hadSaleAtOrder)
                                                <div class="original-price text-decoration-line-through text-muted">
                                                    {{ $formatAmount($snapshotOrig) }}
                                                </div>
                                                <div class="sale-price text-danger fw-bold">
                                                    {{ $formatAmount($paidPrice) }}
                                                </div>
                                            @else
                                                <div class="regular-price">
                                                    {{ $formatAmount($paidPrice) }}
                                                </div>
                                            @endif

                                            @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping && isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                                <div class="fast-shipping-price text-warning mt-1">
                                                    <small>
                                                        <i class="bi bi-lightning-fill"></i> +{{ $formatAmount($item->pivot->fast_shipping_cost) }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item->pivot->quantity }}</td>
                                    <td class="fw-bold">
                                        {{ $formatAmount($item->pivot->subtotal ?? 0) }}
                                        @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping && isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                            <br><small class="text-warning">
                                                (incl. fast shipping: {{ $formatAmount($item->pivot->fast_shipping_cost * $item->pivot->quantity) }})
                                            </small>
                                        @endif
                                    </td>

                                </tr>
                            @endforeach

                            {{-- Display Sub-Order Products --}}
                            @if($order->sub_orders && $order->sub_orders->count() > 0)
                                @foreach($order->sub_orders as $subOrder)
                                    @if($subOrder->products && $subOrder->products->count() > 0)
                                        {{-- Sub-Order Header Row --}}
                                        <tr class="sub-order-header">
                                            <td colspan="6" style="background: linear-gradient(135deg, #11529c 0%, #1a6bcc 100%); color: white; padding: 10px;">
                                                <i class="bi bi-shop"></i>
                                                <strong>
                                                    @if($subOrder->store_id)
                                                        Vendor Order #{{ $subOrder->order_number }}
                                                    @else
                                                        Additional Items #{{ $subOrder->order_number }}
                                                    @endif
                                                </strong>
                                            </td>
                                        </tr>

                                        {{-- Sub-Order Products --}}
                                        @foreach($subOrder->products as $item)
                                            <tr style="background-color: #f8f9fa;">
                                                <td>
                                                    <div class="product-image-wrapper">
                                                        @if($item->product_thumbnail && isset($item->product_thumbnail->image_url))
                                                            <img src="{{ $item->product_thumbnail->image_url }}" alt="{{ $item->name }}" class="img-fluid rounded">
                                                        @elseif($item->product_thumbnail && isset($item->product_thumbnail->original_url))
                                                            <img src="{{ $item->product_thumbnail->original_url }}" alt="{{ $item->name }}" class="img-fluid rounded">
                                                        @else
                                                            <div class="placeholder-image">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="product-details">
                                                        <div class="product-name">{{ $item->pivot->product_name ?? $item->name }}</div>

                                                        @if($item->sku)
                                                            <div class="product-sku">
                                                                <small class="text-muted">SKU: <code>{{ $item->sku }}</code></small>
                                                            </div>
                                                        @endif

                                                        {{-- Estimated Delivery Text with Icons and Colors --}}
                                                        @if($item->pivot->estimated_delivery_text)
                                                            <div class="estimated-delivery mt-1">
                                                                @php
                                                                    $deliveryText = $item->pivot->estimated_delivery_text;
                                                                    $deliveryLower = strtolower($deliveryText);

                                                                    // Define icons and colors based on delivery text
                                                                    if (str_contains($deliveryLower, 'get it tomorrow') || str_contains($deliveryLower, 'tomorrow')) {
                                                                        $icon = 'lightning-fill';
                                                                        $bgColor = 'bg-warning';
                                                                        $textColor = 'text-dark';
                                                                    } elseif (str_contains($deliveryLower, 'today')) {
                                                                        $icon = 'clock-fill';
                                                                        $bgColor = 'bg-danger';
                                                                        $textColor = 'text-white';
                                                                    } elseif (str_contains($deliveryLower, 'fast') || str_contains($deliveryLower, 'express')) {
                                                                        $icon = 'rocket-takeoff-fill';
                                                                        $bgColor = 'bg-primary';
                                                                        $textColor = 'text-white';
                                                                    } elseif (str_contains($deliveryLower, 'next day')) {
                                                                        $icon = 'calendar-check-fill';
                                                                        $bgColor = 'bg-success';
                                                                        $textColor = 'text-white';
                                                                    } elseif (str_contains($deliveryLower, '2-3 days') || str_contains($deliveryLower, '2 to 3')) {
                                                                        $icon = 'calendar2-week';
                                                                        $bgColor = 'bg-info';
                                                                        $textColor = 'text-dark';
                                                                    } elseif (str_contains($deliveryLower, 'week') || str_contains($deliveryLower, 'days')) {
                                                                        $icon = 'calendar3';
                                                                        $bgColor = 'bg-secondary';
                                                                        $textColor = 'text-white';
                                                                    } else {
                                                                        $icon = 'truck';
                                                                        $bgColor = 'bg-light';
                                                                        $textColor = 'text-dark';
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $bgColor }} {{ $textColor }}">
                                                                    <i class="bi bi-{{ $icon }}"></i> {{ $deliveryText }}
                                                                </span>
                                                            </div>
                                                        @endif

                                                        {{-- Multi-Attribute Variation Display --}}
                                                        @if($item->pivot->variation_id)
                                                            <div class="product-variations mt-1">
                                                                @php
                                                                    $displayName = $item->pivot->variation_display_name ?? null;
                                                                    if ($displayName) {
                                                                        $parts = array_filter(array_map('trim', explode(' - ', $displayName)));
                                                                    } else {
                                                                        $parts = [];
                                                                        $variation = collect($item->variations ?? [])->firstWhere('id', $item->pivot->variation_id);
                                                                        if ($variation && isset($variation->attribute_values) && count($variation->attribute_values) > 0) {
                                                                            $parts = collect($variation->attribute_values)->pluck('value')->toArray();
                                                                        } elseif ($variation && isset($variation->name)) {
                                                                            $parts = [$variation->name];
                                                                        }
                                                                    }
                                                                @endphp

                                                                @if(count($parts) > 0)
                                                                    @foreach($parts as $part)
                                                                        <span class="badge bg-success">{{ $part }}</span>
                                                                    @endforeach
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="item-info">
                                                        @if($item->pivot->item_status)
                                                            <div class="mb-1">
                                                                <small><strong>Status:</strong></small>
                                                                <span class="badge"
                                                                      style="background-color: {{ \App\Helpers\OrderStatusColors::hex($item->pivot->item_status) }}; color: {{ \App\Helpers\OrderStatusColors::textColor(\App\Helpers\OrderStatusColors::hex($item->pivot->item_status)) }};">
                                                                    {{ ucwords(str_replace('_', ' ', $item->pivot->item_status)) }}
                                                                </span>
                                                            </div>
                                                        @endif

                                                        @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping)
                                                            <div class="mb-1">
                                                                <span class="badge bg-warning text-dark">
                                                                    <i class="bi bi-lightning-fill"></i> Fast Shipping Applied
                                                                </span>
                                                                @if(isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                                                    <br><small class="text-muted">Cost: {{ $formatAmount($item->pivot->fast_shipping_cost) }} × {{ $item->pivot->quantity }}</small>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        @if($item->pivot->eta)
                                                            <div class="mb-1">
                                                                <small><strong>ETA:</strong></small>
                                                                <span class="text-primary">
                                                                    <i class="bi bi-calendar-event"></i> {{ \Carbon\Carbon::parse($item->pivot->eta)->format('Y-m-d') }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="product-pricing">
                                                        {{ $formatAmount($item->pivot->single_price ?? 0) }}
                                                        @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping && isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                                            <div class="fast-shipping-price text-warning mt-1">
                                                                <small>
                                                                    <i class="bi bi-lightning-fill"></i> +{{ $formatAmount($item->pivot->fast_shipping_cost) }}
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $item->pivot->quantity }}</td>
                                                <td class="fw-bold">
                                                    {{ $formatAmount($item->pivot->subtotal ?? 0) }}
                                                    @if(isset($item->pivot->has_fast_shipping) && $item->pivot->has_fast_shipping && isset($item->pivot->fast_shipping_cost) && $item->pivot->fast_shipping_cost > 0)
                                                        <br><small class="text-warning">
                                                            (incl. fast shipping: {{ $formatAmount($item->pivot->fast_shipping_cost * $item->pivot->quantity) }})
                                                        </small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            @php
                                // Calculate fast shipping total from all order products
                                $fastShippingTotal = $order->products->sum(function($product) {
                                    if (isset($product->pivot->has_fast_shipping) && $product->pivot->has_fast_shipping) {
                                        $cost = $product->pivot->fast_shipping_cost ?? 0;
                                        $quantity = $product->pivot->quantity ?? 1;
                                        return $cost * $quantity;
                                    }
                                    return 0;
                                });
                            @endphp

                            <tr>
                                <td></td>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td colspan="2"><strong>{{ $formatAmount($order->amount ?? 0) }}</strong></td>
                            </tr>
{{--                            @if($order->delivery_price > 0)shipping_total--}}
{{--                                <tr>--}}
{{--                                    <td></td>--}}
{{--                                    <td colspan="4" class="text-end">Delivery Fee:</td>--}}
{{--                                    <td colspan="2">{{ $order->currency_symbol ?? 'R' }} {{ number_format($order->delivery_price, 2) }}</td>--}}
{{--                                </tr>--}}
{{--                            @endif--}}
                            @if($fastShippingTotal > 0)
                                <tr>
                                    <td></td>
                                    <td colspan="4" class="text-end">
                                        <i class="bi bi-lightning-fill text-warning"></i> Fast Shipping Total:
                                    </td>
                                    <td colspan="2" class="fast-shipping-total text-warning"><strong>{{ $formatAmount($fastShippingTotal) }}</strong></td>
                                </tr>
                            @endif
{{--                            @if($order->tax_total > 0)--}}
{{--                                <tr>--}}
{{--                                    <td></td>--}}
{{--                                    <td colspan="4" class="text-end">Tax:</td>--}}
{{--                                    <td colspan="2">{{ $order->currency_symbol ?? 'R' }} {{ number_format($order->tax_total, 2) }}</td>--}}
{{--                                </tr>--}}
{{--                            @endif--}}
                            <tr class="table-active">
                                <td></td>
                                <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                <td colspan="2" class="grand-total"><strong>{{ $formatAmount(($order->amount ?? 0) + $fastShippingTotal) }}</strong></td>
                            </tr>

                        </tfoot>
                    </table>
                </div>

        <!-- Order Notes -->
        <div class="card mb-3">
            <div class="ord-card-header">
                <h5 class="mb-0"><i class="bi bi-journal-text"></i> Order Notes</h5>
            </div>
            <div class="card-body">
                @can('order-note.create')
                <form method="POST" action="{{ route('admin.orders.notes.store') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a note…" required style="font-size:.84rem;"></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="privacy" id="privacyPrivate" value="private" checked>
                                <label class="form-check-label small" for="privacyPrivate"><i class="bi bi-lock-fill"></i> Private</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="privacy" id="privacyPublic" value="public">
                                <label class="form-check-label small" for="privacyPublic"><i class="bi bi-globe"></i> Public</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Add Note</button>
                    </div>
                </form>
                @endcan

                {{-- Notes timeline --}}
                @if(count($order->notes ?? []) > 0)
                    <div class="pt-1">
                        @foreach($order->notes ?? [] as $note)
                        <div class="ord-note-item" data-note-id="{{ $note->id }}">
                            <div class="ord-note-dot {{ $note->privacy }}">
                                <i class="bi bi-{{ $note->privacy == 'public' ? 'globe' : 'lock-fill' }}"></i>
                            </div>
                            <div class="ord-note-body">
                                <div class="ord-note-text">{{ $note->note }}</div>
                                <div class="ord-note-meta">
                                    <span class="badge bg-{{ $note->privacy == 'public' ? 'success' : 'secondary' }} py-0" style="font-size:.62rem;">{{ strtoupper($note->privacy) }}</span>
                                    <span>{{ $note->created_by->name ?? 'System' }}</span>
                                    <span>{{ $note->created_at->format('d M Y H:i') }}</span>
                                    <div class="ms-auto d-flex gap-1">
                                        @can('order-note.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 edit-note-btn" style="font-size:.72rem;" data-note-id="{{ $note->id }}"><i class="bi bi-pencil"></i></button>
                                        @endcan
                                        @can('order-note.delete')
                                        <form method="POST" action="{{ route('admin.orders.notes.destroy', $note->id) }}" style="display:inline;" onsubmit="return handleDeleteNote(event)">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                                        </form>
                                        @endcan
                                    </div>
                                </div>
                                @can('order-note.edit')
                                <form method="POST" action="{{ route('admin.orders.notes.update', $note->id) }}" class="edit-note-form mt-2" style="display:none;">
                                    @csrf @method('PUT')
                                    <textarea name="note" class="form-control form-control-sm mb-2" rows="2" required>{{ $note->note }}</textarea>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="privacy" value="private" {{ $note->privacy == 'private' ? 'checked' : '' }}>
                                                <label class="form-check-label small">Private</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="privacy" value="public" {{ $note->privacy == 'public' ? 'checked' : '' }}>
                                                <label class="form-check-label small">Public</label>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button type="submit" class="btn btn-sm btn-success py-0"><i class="bi bi-check-circle"></i> Save</button>
                                            <button type="button" class="btn btn-sm btn-secondary py-0 cancel-edit-btn"><i class="bi bi-x-circle"></i></button>
                                        </div>
                                    </div>
                                </form>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                <p class="text-muted small mb-0">No notes yet.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════ UNIFIED DETAIL PANEL ═══════ --}}
    <div class="col-md-4">
        <div class="ord-unified-panel">

            {{-- ● Summary (TOP) --}}
            <div class="ord-psec">
                <div class="ord-psec-label"><i class="bi bi-receipt-cutoff"></i> Summary</div>
                @php
                    $calculatedFastShippingTotal = $order->products->sum(function($product) {
                        if (isset($product->pivot->has_fast_shipping) && $product->pivot->has_fast_shipping) {
                            return ($product->pivot->fast_shipping_cost ?? 0) * ($product->pivot->quantity ?? 1);
                        }
                        return 0;
                    });
                    $summary = $order->summary ?? null;
                    if ($summary) {
                        $subtotal      = $summary['subtotal'] ?? $order->amount ?? 0;
                        $shipping      = $summary['shipping'] ?? $order->shipping_total ?? 0;
                        $fastShipping  = $calculatedFastShippingTotal;
                        $delivery      = $summary['delivery'] ?? $order->delivery_charge ?? 0;
                        $tax           = $summary['tax'] ?? $order->tax_total ?? 0;
                        $grandTotal    = $subtotal + $shipping + $fastShipping + $delivery + $tax;
                        $couponDiscount= abs($summary['coupon_discount'] ?? $order->coupon_total_discount ?? 0);
                        $pointsUsed    = abs($summary['points_used'] ?? $order->points_amount ?? 0);
                        $walletUsed    = abs($summary['wallet_used'] ?? $order->wallet_balance ?? 0);
                    } else {
                        $subtotal      = $order->amount ?? 0;
                        $shipping      = $order->shipping_total ?? 0;
                        $fastShipping  = $calculatedFastShippingTotal;
                        $delivery      = $order->delivery_charge ?? 0;
                        $tax           = $order->tax_total ?? 0;
                        $grandTotal    = $subtotal + $shipping + $fastShipping + $delivery + $tax;
                        $couponDiscount= abs($order->coupon_total_discount ?? 0);
                        $pointsUsed    = abs($order->points_amount ?? 0);
                        $walletUsed    = abs($order->wallet_balance ?? 0);
                    }
                    $totalDiscounts = $couponDiscount + $pointsUsed + $walletUsed;
                    $finalTotal = max(0, $grandTotal - $totalDiscounts);
                @endphp
                <div class="ord-sum-row"><span>Subtotal</span><span>{{ $formatAmount($subtotal) }}</span></div>
                @if($shipping > 0)<div class="ord-sum-row"><span>Shipping</span><span>{{ $formatAmount($shipping) }}</span></div>@endif
                @if($fastShipping > 0)<div class="ord-sum-row" style="color:#d97706;"><span><i class="bi bi-lightning-fill"></i> Fast Shipping</span><span>{{ $formatAmount($fastShipping) }}</span></div>@endif
                @if($delivery > 0)<div class="ord-sum-row"><span>Delivery</span><span>{{ $formatAmount($delivery) }}</span></div>@endif
                @if($tax > 0)<div class="ord-sum-row"><span>Tax (VAT)</span><span>{{ $formatAmount($tax) }}</span></div>@endif
                <div class="ord-sum-row ord-sum-total"><span>Grand Total</span><span>{{ $formatAmount($grandTotal) }}</span></div>
                @if($couponDiscount > 0)<div class="ord-sum-row" style="color:#16a34a;"><span>Coupon</span><span>-{{ $formatAmount($couponDiscount) }}</span></div>@endif
                @if($pointsUsed > 0)<div class="ord-sum-row" style="color:#16a34a;"><span>Points</span><span>-{{ $formatAmount($pointsUsed) }}</span></div>@endif
                @if($walletUsed > 0)<div class="ord-sum-row" style="color:#16a34a;"><span>Wallet</span><span>-{{ $formatAmount($walletUsed) }}</span></div>@endif
                @if($totalDiscounts > 0)
                <div class="ord-sum-row ord-sum-final"><span>Amount Paid</span><span>{{ $formatAmount($finalTotal) }}</span></div>
                @endif
                @if($order->invoice_url)
                <a href="{{ $order->invoice_url }}" target="_blank" class="btn btn-sm btn-primary w-100 mt-2">
                    <i class="bi bi-file-earmark-pdf"></i> Download Invoice
                </a>
                @endif
            </div>

            {{-- ● Customer --}}
            <div class="ord-psec">
                <div class="ord-psec-label"><i class="bi bi-person-fill"></i> Customer</div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ord-avatar">{{ strtoupper(substr($order->consumer->name ?? 'G', 0, 1)) }}</div>
                    <div style="min-width:0;">
                        <div style="font-weight:700;font-size:.9rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $order->consumer->name ?? 'Guest' }}</div>
                        @if($order->consumer?->company_name)
                        <div style="font-size:.78rem;font-weight:600;color:#1d4ed8;margin-bottom:1px;"><i class="bi bi-building-fill me-1"></i>{{ $order->consumer->company_name }}</div>
                        @endif
                        <div style="font-size:.76rem;color:#64748b;">{{ $order->consumer->email ?? 'No email' }}</div>
                        @if($order->consumer?->phone)
                        <div style="font-size:.76rem;color:#64748b;"><i class="bi bi-telephone me-1"></i>{{ $order->consumer->phone }}</div>
                        @endif
                    </div>
                </div>
                @if($order->consumer)
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="ord-mini-tile" style="border-color:#16a34a;">
                            <div class="ord-mini-tile-label" style="color:#16a34a;">Wallet</div>
                            <div class="ord-mini-tile-val" style="color:#15803d;">${{ number_format($order->consumer->wallet->balance ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ord-mini-tile" style="border-color:#d97706;">
                            <div class="ord-mini-tile-label" style="color:#d97706;">Points</div>
                            <div class="ord-mini-tile-val" style="color:#92400e;">{{ number_format($order->consumer->point->balance ?? 0, 0) }} pts</div>
                        </div>
                    </div>
                </div>
                @endif
                {{-- Collapsible Address --}}
                @if($order->shipping_address || $order->delivery_description || $order->delivery_interval)
                <div>
                    <button class="btn btn-sm w-100 d-flex align-items-center justify-content-between" type="button"
                        data-bs-toggle="collapse" data-bs-target="#addrCollapse" aria-expanded="false"
                        style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;font-size:.78rem;color:#475569;font-weight:600;">
                        <span><i class="bi bi-geo-alt me-1"></i> Delivery & Address</span>
                        <i class="bi bi-chevron-down" style="font-size:.7rem;"></i>
                    </button>
                    <div class="collapse mt-2" id="addrCollapse">
                        @if($order->shipping_address)
                        <div class="d-flex gap-2 mb-2">
                            <i class="bi bi-house-door" style="color:#062a6a;margin-top:2px;flex-shrink:0;"></i>
                            <div style="font-size:.82rem;line-height:1.6;color:#334155;">
                                @if($order->shipping_address->title)<strong>{{ $order->shipping_address->title }}</strong><br>@endif
                                {{ $order->shipping_address->street ?? '' }}<br>
                                {{ $order->shipping_address->city ?? '' }}@if($order->shipping_address->state), {{ $order->shipping_address->state->name ?? '' }}@endif<br>
                                {{ $order->shipping_address->country->name ?? '' }} @if($order->shipping_address->pincode){{ $order->shipping_address->pincode }}@endif
                                @if($order->shipping_address->phone)<br><i class="bi bi-telephone"></i> {{ $order->shipping_address->country_code ?? '' }} {{ $order->shipping_address->phone }}@endif
                            </div>
                        </div>
                        @endif
                        @if($order->delivery_description || $order->delivery_interval)
                        <div class="d-flex gap-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <i class="bi bi-{{ str_contains(strtolower($order->delivery_description ?? ''), 'pickup') ? 'shop' : 'truck' }}" style="color:#062a6a;margin-top:2px;flex-shrink:0;"></i>
                            <div style="font-size:.82rem;color:#334155;">
                                @if($order->delivery_description)<div>{{ $order->delivery_description }}</div>@endif
                                @if($order->delivery_interval)<div style="color:#64748b;"><i class="bi bi-clock me-1"></i>{{ $order->delivery_interval }}</div>@endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- ● Order Info (read-only: number, date, payment, status) --}}
            <div class="ord-psec">
                <div class="ord-psec-label"><i class="bi bi-info-circle-fill"></i> Order Details</div>
                <div class="ord-info-row">
                    <div class="ord-info-icon"><i class="bi bi-hash"></i></div>
                    <div><div class="ord-info-label">Order Number</div><div class="ord-info-val">#{{ $order->order_number }}</div></div>
                </div>
                <div class="ord-info-row">
                    <div class="ord-info-icon"><i class="bi bi-calendar3"></i></div>
                    <div><div class="ord-info-label">Date</div><div class="ord-info-val">{{ $order->created_at->format('d M Y, H:i') }}</div></div>
                </div>
                <div class="ord-info-row">
                    <div class="ord-info-icon"><i class="bi bi-credit-card"></i></div>
                    <div><div class="ord-info-label">Payment</div><div class="ord-info-val">{{ strtoupper($order->payment_method) }}</div></div>
                </div>
                <div class="ord-info-row">
                    <div class="ord-info-icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="ord-info-label">Payment Status</div>
                        <div class="ord-info-val">
                            <span class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }}" style="font-size:.72rem;">{{ ucfirst($order->payment_status ?? 'Pending') }}</span>
                        </div>
                    </div>
                </div>
                <div class="ord-info-row" style="border-bottom:none;">
                    <div class="ord-info-icon"><i class="bi bi-bag"></i></div>
                    <div>
                        <div class="ord-info-label">Order Status</div>
                        <div class="ord-info-val">
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}" style="font-size:.72rem;">{{ $order->order_status->name ?? 'Pending' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ● Collection QR --}}
            @php
                $currentStatusName = strtolower(trim($order->order_status->name ?? ''));
                $isReadyForCollection = $currentStatusName === 'ready for collection';
            @endphp
            @if($isReadyForCollection)
            <div class="ord-psec" style="background:#f0fdf4;">
                <div class="ord-psec-label" style="color:#16a34a;"><i class="bi bi-qr-code"></i> Collection QR Code</div>
                <p class="mb-2" style="font-size:.82rem;font-weight:600;">Scan to mark as collected</p>
                @php
                    $qrData = json_encode(['type'=>'order_collection','order_number'=>$order->order_number,'order_id'=>$order->id,'auto_collect'=>true,'customer_name'=>$order->consumer->name??'Customer','timestamp'=>now()->timestamp], JSON_UNESCAPED_SLASHES);
                    $qrCodeBase64 = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(1)->errorCorrection('H')->generate($qrData);
                    $qrUrl = 'data:image/png;base64,'.base64_encode($qrCodeBase64);
                @endphp
                <img src="{{ $qrUrl }}" alt="Collection QR Code" style="width:100%;max-width:200px;display:block;margin:0 auto 10px;border-radius:8px;border:2px solid #16a34a;">
                <div style="font-size:.76rem;color:#166534;background:#dcfce7;border-radius:8px;padding:7px 10px;">
                    <i class="bi bi-info-circle me-1"></i> Customer can scan on screen. Scanning marks order as collected.
                </div>
            </div>
            @endif

            {{-- ● Order Note --}}
            @if($order->note)
            <div class="ord-psec" style="background:#fefce8;">
                <div class="ord-psec-label" style="color:#a16207;"><i class="bi bi-sticky-fill"></i> Order Note</div>
                <p style="font-size:.84rem;color:#1c1917;margin:0;">{{ $order->note }}</p>
            </div>
            @endif

            {{-- ● Status History --}}
            @if($order->status_histories && $order->status_histories->count() > 0)
            <div class="ord-psec">
                <div class="ord-psec-label"><i class="bi bi-clock-history"></i> Status History</div>
                <div style="max-height:200px;overflow-y:auto;">
                    @foreach($order->status_histories as $history)
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="width:6px;height:6px;border-radius:50%;background:#062a6a;margin-top:6px;flex-shrink:0;"></div>
                        <div style="font-size:.78rem;">
                            <div style="color:#94a3b8;margin-bottom:2px;">{{ $history->created_at->format('d M Y, H:i') }} &middot; {{ $history->updated_by->name ?? 'System' }}</div>
                            <span style="background:#e2e8f0;border-radius:4px;padding:1px 6px;font-size:.7rem;">{{ $history->old_status->name ?? 'N/A' }}</span>
                            <i class="bi bi-arrow-right" style="font-size:.65rem;color:#94a3b8;margin:0 3px;"></i>
                            <span style="background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:1px 6px;font-size:.7rem;">{{ $history->new_status->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- end .ord-unified-panel --}}
    </div>
</div>

<!-- Update Payment Method Modal -->
@can('order.edit')
<div class="modal fade" id="updatePaymentMethodModal" tabindex="-1" aria-labelledby="updatePaymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(10,45,107,.3);">
            <form method="POST" action="{{ route('admin.orders.update-payment-method', $order->id) }}" onsubmit="return handlePaymentMethodUpdate(event)">
                @csrf @method('PUT')

                {{-- Gradient header --}}
                <div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);padding:20px 24px;color:#fff;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;opacity:.6;margin-bottom:4px;">
                                <i class="bi bi-receipt me-1"></i>Order #{{ $order->order_number }}
                            </div>
                            <h5 class="mb-0" style="font-size:1rem;font-weight:800;color:#fff;">
                                <i class="bi bi-credit-card me-2"></i>Update Payment Method
                            </h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="modal-body" style="padding:20px 24px;background:#fff;">
                    <p style="font-size:.82rem;color:#64748b;margin-bottom:16px;">Select the new payment method for this order. The customer should be notified of any changes.</p>

                    {{-- Current --}}
                    <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;">
                        <i class="bi bi-credit-card" style="color:#062a6a;font-size:1rem;"></i>
                        <div>
                            <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;">Current</div>
                            <div style="font-size:.88rem;font-weight:700;color:#0f172a;">{{ strtoupper($order->payment_method ?? '—') }}</div>
                        </div>
                    </div>

                    <label style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#475569;margin-bottom:8px;display:block;">New Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select" required
                        style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-size:.88rem;font-weight:600;color:#0f172a;">
                        <option value="cod"           {{ $order->payment_method == 'cod'           ? 'selected' : '' }}>💵 Cash on Delivery (COD)</option>
                        <option value="bank_transfer" {{ $order->payment_method == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                        <option value="paypal"        {{ $order->payment_method == 'paypal'        ? 'selected' : '' }}>🅿️ PayPal</option>
                        <option value="payfast"       {{ $order->payment_method == 'payfast'       ? 'selected' : '' }}>⚡ PayFast</option>
                        <option value="yoco"          {{ $order->payment_method == 'yoco'          ? 'selected' : '' }}>💳 Yoco</option>
                        <option value="pdo_zambia"    {{ $order->payment_method == 'pdo_zambia'    ? 'selected' : '' }}>🇿🇲 PDO Zambia</option>
                        <option value="pese"          {{ $order->payment_method == 'pese'          ? 'selected' : '' }}>📱 Pese Pay</option>
                    </select>

                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-top:14px;font-size:.78rem;color:#1e40af;">
                        <i class="bi bi-info-circle me-1"></i> Changing the payment method updates the order record. Notify the customer if needed.
                    </div>
                </div>

                {{-- Footer --}}
                <div style="padding:14px 24px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:7px 16px;font-weight:600;font-size:.82rem;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm" id="updatePaymentBtn"
                        style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border:none;color:#fff;border-radius:8px;padding:7px 18px;font-weight:700;font-size:.82rem;">
                        <i class="bi bi-check-circle me-1"></i> Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan


@endsection

@push('styles')
<style>
    /* Order Items Table Styles */
    .order-items-table {
        margin-bottom: 0;
    }

    .order-items-table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        vertical-align: middle;
    }

    .order-items-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .order-items-table td {
        vertical-align: middle;
        padding: 15px 10px;
    }

    /* Product Image Styles */
    .product-image-wrapper {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }

    .product-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .placeholder-image {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #adb5bd;
    }

    /* Product Details Styles */
    .product-details {
        line-height: 1.6;
    }

    .product-name {
        font-weight: 600;
        color: #062a6a;
        margin-bottom: 4px;
    }

    .product-sku {
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .product-sku code {
        background-color: #e9ecef;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
        color: #495057;
    }

    /* Estimated Delivery Styles */
    .estimated-delivery {
        margin-top: 6px;
        animation: fadeIn 0.3s ease-in;
    }

    .estimated-delivery .badge {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .estimated-delivery .badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .estimated-delivery .badge i {
        font-size: 0.9rem;
    }

    /* Special animation for "Get It Tomorrow" badge */
    .estimated-delivery .badge.bg-warning {
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        50% {
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }
    }

    /* Variations Styles */
    .product-variations {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .product-variations .badge {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 4px 8px;
    }

    /* Pricing Styles */
    .product-pricing {
        line-height: 1.5;
    }

    .original-price {
        font-size: 0.9rem;
        margin-bottom: 2px;
    }

    .sale-price {
        font-size: 1rem;
    }

    .regular-price {
        font-size: 1rem;
        color: #062a6a;
        font-weight: 500;
    }

    /* Item Info Styles */
    .item-info {
        font-size: 0.9rem;
        line-height: 1.8;
    }

    .item-info .badge {
        font-size: 0.75rem;
        margin-left: 4px;
    }

    .item-info small {
        color: #6c757d;
    }

    /* Order Summary Styles */
    .order-summary-list {
        margin-bottom: 0;
    }

    .order-summary-list li {
        padding: 8px 0;
        font-size: 0.95rem;
    }

    .order-summary-list li span {
        color: #495057;
    }

    .order-summary-list li strong {
        color: #062a6a;
    }

    .order-summary-list .text-success strong {
        color: #28a745 !important;
    }

    .order-summary-list .text-primary strong {
        color: #062a6a !important;
    }

    /* Order Notes Styles */
    .note-card {
        background: #fff9db;
        border: 1px solid #ffe58f;
        border-radius: 8px;
        padding: 15px;
    }

    .note-content {
        white-space: pre-wrap;
        word-break: break-word;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .note-footer {
        border-top: 1px solid #ffe58f;
        padding-top: 10px;
    }

    .note-actions {
        display: flex;
        gap: 5px;
    }

    /* Order Status Tracking Timeline */
    .order-tracking-timeline {
        padding: 20px 0;
    }

    .tracking-list {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
    }

    .tracking-list::before {
        content: '';
        position: absolute;
        top: 24px;
        left: 0;
        right: 0;
        height: 3px;
        background: #e9ecef;
        z-index: 0;
    }

    .tracking-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .tracking-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #e9ecef;
        border: 3px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .tracking-icon i {
        font-size: 20px;
        color: #6c757d;
    }

    .tracking-item.active .tracking-icon {
        background: #062a6a;
        border-color: #062a6a;
    }

    .tracking-item.active .tracking-icon i {
        color: white;
    }

    .tracking-item.cancelled .tracking-icon {
        background: #dc3545;
        border-color: #dc3545;
    }

    .tracking-item.cancelled .tracking-icon i {
        color: white;
    }

    .tracking-content {
        text-align: center;
    }

    .tracking-status {
        font-weight: 600;
        font-size: 0.9rem;
        color: #495057;
        margin-bottom: 4px;
    }

    .tracking-item.active .tracking-status {
        color: #062a6a;
        font-weight: 700;
    }

    .tracking-item.cancelled .tracking-status {
        color: #dc3545;
        font-weight: 700;
    }

    .tracking-date {
        font-size: 0.75rem;
        color: #6c757d;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .tracking-list {
            flex-direction: column;
            align-items: flex-start;
        }

        .tracking-list::before {
            top: 0;
            left: 24px;
            bottom: 0;
            width: 3px;
            height: auto;
            right: auto;
        }

        .tracking-item {
            flex-direction: row;
            width: 100%;
            margin-bottom: 20px;
        }

        .tracking-icon {
            margin-right: 15px;
            margin-bottom: 0;
        }

        .tracking-content {
            text-align: left;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Order Notes - Edit functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Edit note buttons
        document.querySelectorAll('.edit-note-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const noteCard = this.closest('.note-card');
                const noteContent = noteCard.querySelector('.note-content');
                const noteHeader = noteCard.querySelector('.note-header');
                const noteFooter = noteCard.querySelector('.note-footer');
                const editForm = noteCard.querySelector('.edit-note-form');

                // Hide content, show form
                noteContent.style.display = 'none';
                noteHeader.style.display = 'none';
                noteFooter.style.display = 'none';
                editForm.style.display = 'block';
            });
        });

        // Cancel edit buttons
        document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const noteCard = this.closest('.note-card');
                const noteContent = noteCard.querySelector('.note-content');
                const noteHeader = noteCard.querySelector('.note-header');
                const noteFooter = noteCard.querySelector('.note-footer');
                const editForm = noteCard.querySelector('.edit-note-form');

                // Show content, hide form
                noteContent.style.display = 'block';
                noteHeader.style.display = 'flex';
                noteFooter.style.display = 'flex';
                editForm.style.display = 'none';
            });
        });


        // Fast Shipping Toggle functionality
        document.querySelectorAll('.fast-shipping-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const orderId = this.getAttribute('data-order-id');
                const productId = this.getAttribute('data-product-id');
                const orderProductId = this.getAttribute('data-order-product-id');
                const hasFastShipping = this.checked ? 1 : 0;

                // Show loading state
                const label = this.nextElementSibling;
                const originalText = label.innerHTML;
                label.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Updating...';
                this.disabled = true;

                // Send AJAX request to update fast shipping
                fetch(`/admin/orders/${orderId}/toggle-fast-shipping`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        order_product_id: orderProductId,
                        product_id: productId,
                        has_fast_shipping: hasFastShipping
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the totals on the page
                        if (data.order) {
                            updateOrderTotals(data.order);
                        }

                        // Show success message
                        showNotification('Fast shipping updated successfully!', 'success');
                    } else {
                        // Revert checkbox state on error
                        this.checked = !this.checked;
                        showNotification(data.message || 'Failed to update fast shipping', 'error');
                    }

                    // Restore label
                    label.innerHTML = originalText;
                    this.disabled = false;
                })
                .catch(error => {

                    // Revert checkbox state on error
                    this.checked = !this.checked;
                    showNotification('An error occurred while updating fast shipping', 'error');

                    // Restore label
                    label.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });
    });

    // Function to update order totals on the page
    function updateOrderTotals(order) {
        const symbol = '{{ $order->currency_symbol ?? "R" }}';

        // Update fast shipping total if element exists
        const fastShippingElement = document.querySelector('.fast-shipping-total');
        if (fastShippingElement && order.fast_shipping_total !== undefined) {
            fastShippingElement.textContent = `${symbol} ${parseFloat(order.fast_shipping_total).toFixed(2)}`;
        }

        // Update grand total if element exists
        const grandTotalElement = document.querySelector('.grand-total');
        if (grandTotalElement && order.total !== undefined) {
            grandTotalElement.textContent = `${symbol} ${parseFloat(order.total).toFixed(2)}`;
        }

        // Reload the page to show updated totals
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }


    // Function to show notification messages
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // SweetAlert handler for delete note
    function handleDeleteNote(event) {
        event.preventDefault();
        confirmDelete('Delete this note?', 'This cannot be undone').then((result) => {
            if (result.isConfirmed) {
                submitFormWithLoading(event.target, {
                    successTitle: 'Deleted!',
                    successText: 'Note has been deleted'
                });
            }
        });
        return false;
    }

    // SweetAlert handler for order status update
    function handleStatusUpdate(event) {
        event.preventDefault();

        const form = event.target;
        const select = form.querySelector('[name="order_status_id"]');
        const selectedOption = select.options[select.selectedIndex];
        const statusName = selectedOption.text;

        confirmAction(
            `Change Order Status?`,
            `Update order status to "${statusName}"?`,
            'Yes, update'
        ).then((result) => {
            if (result.isConfirmed) {
                submitFormWithLoading(form, {
                    successTitle: 'Updated!',
                    successText: 'Order status has been updated successfully'
                });
            }
        });

        return false;
    }

    // SweetAlert handler for payment method update
    function handlePaymentMethodUpdate(event) {
        event.preventDefault();

        const form = event.target;
        const select = form.querySelector('#payment_method');
        const selectedOption = select.options[select.selectedIndex];
        const methodName = selectedOption.text;
        const submitBtn = document.getElementById('updatePaymentBtn');
        const originalText = submitBtn.innerHTML;

        confirmAction(
            'Update Payment Method?',
            `Change payment method to "${methodName}"?`,
            'Yes, update'
        ).then((result) => {
            if (result.isConfirmed) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: new FormData(form)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Update failed');
                    return response;
                })
                .then(() => {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('updatePaymentMethodModal'));
                    if (modal) modal.hide();

                    showSuccess('Updated!', 'Payment method has been updated successfully').then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    showError('Failed!', error.message);
                });
            }
        });

        return false;
    }
</script>
@endpush

