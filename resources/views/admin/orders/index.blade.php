@extends('admin.layout')

@section('title', 'Orders - Admin Panel')
@section('content')
{{-- Page Header --}}
<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap">
            <i class="bi bi-bag-check-fill"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Orders</h2>
            <p class="text-muted mb-0 small">Manage and track all customer orders</p>
        </div>
    </div>
    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-create-order">
        <i class="bi bi-plus-circle-fill me-1"></i> Create New Order
    </a>
</div>

{{-- Status Pills --}}
<div class="filter-status-card card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small fw-semibold me-1" style="white-space:nowrap">Status:</span>
            <a href="{{ route('admin.orders.index') }}" class="status-pill {{ !request('status') ? 'active' : '' }}" style="border-left: 3px solid #062a6a;">
                All <span class="status-pill-count">{{ $totalCount }}</span>
            </a>
            @php
                $statusColors = [
                    'pending'    => '#f59e0b',
                    'processing' => '#3b82f6',
                    'shipped'    => '#8b5cf6',
                    'delivered'  => '#10b981',
                    'completed'  => '#059669',
                    'cancelled'  => '#ef4444',
                    'refunded'   => '#f97316',
                    'on hold'    => '#6b7280',
                ];
            @endphp
            @foreach($statuses as $status)
                @php
                    $slugKey = strtolower($status->name);
                    $pillColor = $statusColors[$slugKey] ?? '#94a3b8';
                @endphp
                <a href="{{ route('admin.orders.index', ['status' => $status->id]) }}"
                   class="status-pill {{ request('status') == $status->id ? 'active' : '' }}"
                   style="border-left: 3px solid {{ $pillColor }};">
                    {{ $status->name }} <span class="status-pill-count">{{ $statusCounts[$status->id] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Search & Filters --}}
<div class="filter-card card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="" id="searchForm">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            {{-- Row 1 --}}
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <div class="filter-input-group">
                        <span class="filter-icon"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="searchInput" class="filter-input"
                               placeholder="Order #, customer, product…"
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="filter-input-group">
                        <span class="filter-icon"><i class="bi bi-diagram-3"></i></span>
                        <select name="branch" id="branchFilter" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branchMap as $keyword => $label)
                                <option value="{{ $keyword }}" {{ request('branch') === $keyword ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="filter-input-group">
                        <span class="filter-icon"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" name="start_date" id="startDate" class="filter-input"
                               value="{{ request('start_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="filter-input-group">
                        <span class="filter-icon"><i class="bi bi-calendar-check"></i></span>
                        <input type="date" name="end_date" id="endDate" class="filter-input"
                               value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Apply
                    </button>
                    @if(request()->hasAny(['search', 'start_date', 'end_date', 'branch']) || !empty(request('city')))
                    <a href="{{ route('admin.orders.index', ['status' => request('status')]) }}"
                       class="btn btn-outline-secondary" title="Clear all filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Row 2: City multi-select --}}
            <div class="row g-2">
                <div class="col-12">
                    <div class="filter-city-row">
                        <span class="filter-city-label">
                            <i class="bi bi-geo-alt-fill"></i> Filter by City
                        </span>
                        <div class="filter-city-select-wrap">
                            <select name="city[]" id="cityMultiFilter" multiple placeholder="Pick one or more cities…">
                                @foreach($cities as $cityOption)
                                    <option value="{{ $cityOption }}"
                                        {{ in_array($cityOption, (array) request('city', [])) ? 'selected' : '' }}>
                                        {{ $cityOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Orders Table --}}
<div class="orders-table-card card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table orders-table mb-0">
                <thead>
                    <tr>
                        <th>Order &amp; Customer</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Total &amp; Balance</th>
                        <th>Date</th>
                        <th width="60">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="order-row">
                            <td>
                                <a href="{{ route('admin.orders.show', $order->order_number) . '?_back=' . urlencode(request()->fullUrl()) }}" class="order-number-link">
                                    #{{ $order->order_number }}
                                </a>
                                <div class="order-customer-name">{{ $order->consumer->name ?? 'N/A' }}</div>
                                <div class="order-customer-email">{{ $order->consumer->email ?? '' }}</div>
                            </td>
                            <td>
                                @php
                                    $desc = $order->delivery_description ?? '';
                                    $branchBadge = null;
                                    if (str_contains($desc, 'Digital')) {
                                        $branchBadge = ['label' => 'Digital', 'class' => 'branch-badge-digital'];
                                    } elseif (str_contains($desc, 'Harare') || str_contains($desc, 'Rhodesville')) {
                                        $branchBadge = ['label' => 'Harare', 'class' => 'branch-badge-harare'];
                                    } elseif (str_contains($desc, 'Bulawayo')) {
                                        $branchBadge = ['label' => 'Bulawayo', 'class' => 'branch-badge-bulawayo'];
                                    } elseif (str_contains($desc, 'Mutare')) {
                                        $branchBadge = ['label' => 'Mutare', 'class' => 'branch-badge-mutare'];
                                    } elseif (str_contains($desc, 'Zambia')) {
                                        $branchBadge = ['label' => 'Zambia', 'class' => 'branch-badge-zambia'];
                                    } elseif (str_contains($desc, '15km radius') || str_contains($desc, 'Standard Delivery')) {
                                        $city = $order->shipping_address->city ?? null;
                                        $hdLabel = $city ? trim($city) . '-HD' : 'Home Delivery';
                                        $branchBadge = ['label' => $hdLabel, 'class' => 'branch-badge-home'];
                                    }
                                @endphp
                                @if($branchBadge)
                                    <span class="branch-badge {{ $branchBadge['class'] }}">
                                        <i class="bi bi-geo-alt-fill"></i> {{ $branchBadge['label'] }}
                                    </span>
                                @else
                                    @php
                                        $otherCity = $order->shipping_address->city ?? null;
                                        $otherLabel = $otherCity ? 'Other-' . trim($otherCity) : 'Other';
                                    @endphp
                                    <span class="branch-badge branch-badge-other">
                                        <i class="bi bi-question-circle"></i> {{ $otherLabel }}
                                    </span>
                                @endif
                                @if($order->shipping_address?->city)
                                    <div class="shipping-city-label">
                                        <i class="bi bi-building"></i> {{ $order->shipping_address->city }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}">
                                    {{ $order->order_status->name ?? 'Pending' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $exchangeRate = floatval($order->exchange_rate ?? 1);
                                    $totalUSD = floatval($order->total ?? 0);
                                    $convertedTotal = $totalUSD * $exchangeRate;
                                    $walletBalance = $order->consumer && $order->consumer->wallet ? number_format($order->consumer->wallet->balance ?? 0, 2) : '0.00';
                                    $pointsBalance = $order->consumer && $order->consumer->point ? number_format($order->consumer->point->balance ?? 0, 0) : '0';
                                @endphp
                                <div class="fw-bold">{{ $order->currency_symbol ?? 'R' }} {{ number_format($convertedTotal, 2) }}</div>
                                <small class="text-muted">
                                    <i class="bi bi-wallet2"></i> ${{ $walletBalance }} | <i class="bi bi-star-fill text-warning"></i> {{ $pointsBalance }}pts
                                </small>
                            </td>
                            <td><small>{{ $order->created_at->format('Y-m-d H:i') }}</small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#orderModal{{ $order->id }}"
                                        title="Quick View">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>No orders found</p>
                                    <span>Try adjusting your filters</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3 px-3 py-3">
            <div>
                Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders
            </div>
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Order Modals (Outside the table) -->
@foreach($orders as $order)
<div class="modal fade" id="orderModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(10,45,107,.3);">

            {{-- ── Gradient hero header ── --}}
            <div style="background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 100%);padding:18px 24px;color:#fff;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;opacity:.55;margin-bottom:4px;">
                            <i class="bi bi-receipt me-1"></i>Quick View
                        </div>
                        <h5 class="mb-1" style="font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px;">
                            #{{ $order->order_number }}
                        </h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}" style="font-size:.72rem;">
                                {{ $order->order_status->name ?? 'Pending' }}
                            </span>
                            <span style="opacity:.7;font-size:.78rem;"><i class="bi bi-calendar3 me-1"></i>{{ $order->created_at->format('d M Y, H:i') }}</span>
                            <span style="opacity:.7;font-size:.78rem;"><i class="bi bi-credit-card me-1"></i>{{ strtoupper($order->payment_method ?? '') }}</span>
                            @php $mpaid = ($order->payment_status ?? '') === 'paid'; @endphp
                            <span style="font-size:.72rem;padding:2px 9px;border-radius:20px;background:{{ $mpaid ? 'rgba(74,222,128,.2)' : 'rgba(251,191,36,.2)' }};color:{{ $mpaid ? '#86efac' : '#fde68a' }};border:1px solid {{ $mpaid ? 'rgba(74,222,128,.4)' : 'rgba(251,191,36,.4)' }};">
                                <i class="bi bi-{{ $mpaid ? 'check-circle-fill' : 'exclamation-circle-fill' }} me-1"></i>{{ ucfirst($order->payment_status ?? 'Pending') }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <a href="{{ route('admin.orders.show', $order->order_number) . '?_back=' . urlencode(request()->fullUrl()) }}"
                           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:10px;padding:6px 14px;font-size:.78rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                            <i class="bi bi-box-arrow-up-right"></i> Full Order
                        </a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="margin-top:2px;"></button>
                    </div>
                </div>
            </div>

            {{-- ── Modal body ── --}}
            <div class="modal-body" style="padding:20px 24px;background:#f8fafc;">
                <div class="row g-3">

                    {{-- Left 8/12: items --}}
                    <div class="col-md-8">

                        {{-- Order Info row --}}
                        <div style="background:#fff;border-radius:12px;padding:14px 16px;margin-bottom:14px;box-shadow:0 1px 6px rgba(0,0,0,.06);">
                            <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:10px;"><i class="bi bi-info-circle me-1"></i>Order Information</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:1px;">Order #</div>
                                    <div style="font-size:.84rem;font-weight:600;color:#0f172a;">#{{ $order->order_number }}</div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:1px;">Date</div>
                                    <div style="font-size:.84rem;font-weight:600;color:#0f172a;">{{ $order->created_at->format('d M Y, H:i') }}</div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:1px;">Payment</div>
                                    <div style="font-size:.84rem;font-weight:600;color:#0f172a;">{{ strtoupper($order->payment_method ?? '—') }}</div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:1px;">Currency</div>
                                    <div style="font-size:.84rem;font-weight:600;color:#0f172a;">{{ $order->currency ?? '' }} ({{ $order->currency_symbol ?? '' }}) &middot; Rate: {{ $order->exchange_rate ?? 1 }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);">
                            <div style="background:#062a6a;padding:10px 16px;display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-box-seam" style="color:#fff;font-size:.9rem;"></i>
                                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#fff;">Order Items ({{ count($order->products) }})</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" style="font-size:.82rem;">
                                    <thead>
                                        <tr style="background:#f8fafc;">
                                            <th style="padding:8px 10px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;width:52px;">Image</th>
                                            <th style="padding:8px 10px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;">Product</th>
                                            <th style="padding:8px 10px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;width:90px;">Price</th>
                                            <th style="padding:8px 10px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;width:50px;">Qty</th>
                                            <th style="padding:8px 10px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:1px solid #e2e8f0;width:90px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->products as $item)
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td style="padding:9px 10px;vertical-align:middle;">
                                                @if($item->product_thumbnail && isset($item->product_thumbnail->image_url))
                                                    <img src="{{ $item->product_thumbnail->image_url }}" alt="{{ $item->name }}"
                                                         style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                                                @else
                                                    <div style="width:44px;height:44px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;border:1px solid #e2e8f0;">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding:9px 10px;vertical-align:middle;">
                                                <div style="font-weight:700;color:#0f3d8c;font-size:.82rem;">{{ $item->pivot->product_name ?? $item->name }}</div>
                                                @if($item->sku)<div style="font-size:.7rem;"><code style="background:#eff6ff;color:#1a5cb8;border-radius:5px;padding:1px 5px;border:1px solid #bfdbfe;">{{ $item->sku }}</code></div>@endif
                                                @if($item->pivot->variation_id && $item->variations && count($item->variations) > 0)
                                                    <div class="mt-1">
                                                        @foreach($item->variations as $variation)
                                                            @if($item->pivot->variation_id == $variation->id)
                                                                @if(isset($variation->attribute_values) && count($variation->attribute_values) > 0)
                                                                    @foreach($variation->attribute_values as $attrValue)
                                                                        <span class="badge bg-success" style="font-size:.65rem;">{{ $attrValue->value }}</span>
                                                                    @endforeach
                                                                @else
                                                                    <span class="badge bg-success" style="font-size:.65rem;">{{ $variation->name }}</span>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding:9px 10px;vertical-align:middle;font-size:.82rem;">
                                                @php $mEx = floatval($order->exchange_rate ?? 1); $mPrice = floatval($item->pivot->single_price ?? 0) * $mEx; @endphp
                                                {{ $order->currency_symbol ?? 'R' }} {{ number_format($mPrice, 2) }}
                                            </td>
                                            <td style="padding:9px 10px;vertical-align:middle;font-weight:700;font-size:.82rem;">{{ $item->pivot->quantity }}</td>
                                            <td style="padding:9px 10px;vertical-align:middle;font-weight:700;color:#0f3d8c;font-size:.82rem;">
                                                @php $mSub = floatval($item->pivot->subtotal ?? 0) * $mEx; @endphp
                                                {{ $order->currency_symbol ?? 'R' }} {{ number_format($mSub, 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Right 4/12: summary + customer + address --}}
                    <div class="col-md-4 d-flex flex-column gap-3">

                        {{-- Summary --}}
                        <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);">
                            <div style="background:#062a6a;padding:10px 16px;display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-receipt-cutoff" style="color:#fff;font-size:.9rem;"></i>
                                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#fff;">Summary</span>
                            </div>
                            <div style="padding:12px 14px;">
                                @php
                                    $mSummary = $order->summary ?? null;
                                    $mSym = $order->currency_symbol ?? 'R';
                                    $mRate = floatval($order->exchange_rate ?? 1);
                                    if ($mSummary) {
                                        $mSubT  = ($mSummary['subtotal'] ?? $order->amount ?? 0) * $mRate;
                                        $mShip  = ($mSummary['shipping'] ?? $order->shipping_total ?? 0) * $mRate;
                                        $mFast  = ($mSummary['fast_shipping'] ?? $order->fast_shipping_total ?? 0) * $mRate;
                                        $mDel   = ($mSummary['delivery'] ?? $order->delivery_charge ?? 0) * $mRate;
                                        $mTax   = ($mSummary['tax'] ?? $order->tax_total ?? 0) * $mRate;
                                        $mGrand = $mSubT + $mShip + $mFast + $mDel + $mTax;
                                        $mFinal = ($mSummary['final_total'] ?? $order->total ?? 0) * $mRate;
                                        $mCoup  = abs($mSummary['coupon_discount'] ?? 0) * $mRate;
                                        $mPts   = abs($mSummary['points_used'] ?? 0) * $mRate;
                                        $mWal   = abs($mSummary['wallet_used'] ?? 0) * $mRate;
                                    } else {
                                        $mSubT  = ($order->amount ?? 0) * $mRate;
                                        $mShip  = ($order->shipping_total ?? 0) * $mRate;
                                        $mFast  = ($order->fast_shipping_total ?? 0) * $mRate;
                                        $mDel   = ($order->delivery_charge ?? 0) * $mRate;
                                        $mTax   = ($order->tax_total ?? 0) * $mRate;
                                        $mGrand = $mSubT + $mShip + $mFast + $mDel + $mTax;
                                        $mFinal = ($order->total ?? 0) * $mRate;
                                        $mCoup  = abs($order->coupon_total_discount ?? 0) * $mRate;
                                        $mPts   = abs($order->points_amount ?? 0) * $mRate;
                                        $mWal   = abs($order->wallet_balance ?? 0) * $mRate;
                                    }
                                @endphp
                                @php $mSumRow = fn($l,$v,$c='#475569') => "<div style='display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:.8rem;'><span style='color:#64748b;'>$l</span><span style='font-weight:600;color:$c;'>$v</span></div>"; @endphp
                                {!! $mSumRow('Subtotal', $mSym.' '.number_format($mSubT,2)) !!}
                                @if($mShip > 0){!! $mSumRow('Shipping', $mSym.' '.number_format($mShip,2)) !!}@endif
                                @if($mFast > 0){!! $mSumRow('Fast Shipping', $mSym.' '.number_format($mFast,2), '#d97706') !!}@endif
                                @if($mDel > 0){!! $mSumRow('Delivery', $mSym.' '.number_format($mDel,2)) !!}@endif
                                @if($mTax > 0){!! $mSumRow('Tax', $mSym.' '.number_format($mTax,2)) !!}@endif
                                <div style="display:flex;justify-content:space-between;padding:7px 0;border-top:2px solid #e2e8f0;margin-top:2px;font-size:.85rem;">
                                    <span style="font-weight:700;color:#0f172a;">Grand Total</span>
                                    <span style="font-weight:800;color:#0f3d8c;">{{ $mSym }} {{ number_format($mGrand,2) }}</span>
                                </div>
                                @if($mCoup > 0){!! $mSumRow('Coupon', '-'.$mSym.' '.number_format($mCoup,2), '#16a34a') !!}@endif
                                @if($mPts > 0){!! $mSumRow('Points', '-'.$mSym.' '.number_format($mPts,2), '#16a34a') !!}@endif
                                @if($mWal > 0){!! $mSumRow('Wallet', '-'.$mSym.' '.number_format($mWal,2), '#16a34a') !!}@endif
                                @if($mCoup > 0 || $mPts > 0 || $mWal > 0)
                                <div style="display:flex;justify-content:space-between;padding:7px 0;border-top:2px solid #e2e8f0;margin-top:2px;font-size:.85rem;">
                                    <span style="font-weight:700;color:#0f172a;">Amount Paid</span>
                                    <span style="font-weight:800;color:#16a34a;">{{ $mSym }} {{ number_format($mFinal,2) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.06);">
                            <div style="background:#062a6a;padding:10px 16px;display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-person-fill" style="color:#fff;font-size:.9rem;"></i>
                                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#fff;">Customer</span>
                            </div>
                            <div style="padding:12px 14px;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div style="width:34px;height:34px;border-radius:50%;background:#062a6a;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0;">{{ strtoupper(substr($order->consumer->name ?? 'G', 0, 1)) }}</div>
                                    <div>
                                        <div style="font-weight:700;font-size:.84rem;color:#0f172a;">{{ $order->consumer->name ?? 'Guest' }}</div>
                                        <div style="font-size:.74rem;color:#64748b;">{{ $order->consumer->email ?? '' }}</div>
                                        @if($order->consumer?->phone)<div style="font-size:.74rem;color:#64748b;"><i class="bi bi-telephone me-1"></i>{{ $order->consumer->phone }}</div>@endif
                                    </div>
                                </div>
                                @if($order->shipping_address)
                                <div style="border-top:1px solid #f1f5f9;padding-top:8px;font-size:.78rem;color:#475569;line-height:1.6;">
                                    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:3px;"><i class="bi bi-geo-alt me-1"></i>Shipping Address</div>
                                    @if($order->shipping_address->title)<strong>{{ $order->shipping_address->title }}</strong><br>@endif
                                    {{ $order->shipping_address->street ?? '' }}<br>
                                    {{ $order->shipping_address->city ?? '' }}@if($order->shipping_address->state), {{ $order->shipping_address->state->name ?? '' }}@endif<br>
                                    {{ $order->shipping_address->country->name ?? '' }}
                                </div>
                                @endif
                                @if($order->delivery_description)
                                <div style="border-top:1px solid #f1f5f9;padding-top:8px;margin-top:8px;font-size:.78rem;color:#475569;">
                                    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;margin-bottom:3px;"><i class="bi bi-truck me-1"></i>Delivery</div>
                                    {{ $order->delivery_description }}
                                    @if($order->delivery_interval)<div style="color:#64748b;"><i class="bi bi-clock me-1"></i>{{ $order->delivery_interval }}</div>@endif
                                </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Footer ── --}}
            <div style="padding:12px 24px;background:#fff;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                    style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:6px 16px;font-weight:600;font-size:.82rem;">
                    <i class="bi bi-x-circle me-1"></i> Close
                </button>
                <a href="{{ route('admin.orders.show', $order->order_number) . '?_back=' . urlencode(request()->fullUrl()) }}" class="btn btn-sm"
                    style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);border:none;color:#fff;border-radius:8px;padding:6px 18px;font-weight:700;font-size:.82rem;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Full Order
                </a>
            </div>

        </div>
    </div>
</div>
@endforeach
@endsection

@push('styles')
{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
<style>
/* ── Page Header ── */
.orders-page-header { margin-bottom: 1.2rem; }
.orders-icon-wrap {
    width: 48px; height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #062a6a 0%, #1565c0 100%);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(6,42,106,.25);
    flex-shrink: 0;
}
.orders-icon-wrap i { color: #fff; font-size: 1.4rem; }
.btn-create-order {
    border-radius: 10px;
    padding: 0.5rem 1.2rem;
    font-weight: 600;
    letter-spacing: .3px;
    box-shadow: 0 4px 12px rgba(6,42,106,.3);
}

/* ── Status Pills ── */
.filter-status-card { border-radius: 12px; }
.status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all .18s ease;
    white-space: nowrap;
}
.status-pill:hover { background: #e2e8f0; color: #1e293b; transform: translateY(-1px); }
.status-pill.active {
    background: linear-gradient(135deg, #062a6a 0%, #1565c0 100%);
    color: #fff; border-color: transparent;
    box-shadow: 0 3px 10px rgba(6,42,106,.3);
}
.status-pill-count {
    background: rgba(255,255,255,.25);
    border-radius: 20px;
    padding: 1px 7px;
    font-size: 0.72rem;
    font-weight: 700;
}
.status-pill:not(.active) .status-pill-count {
    background: #fff;
    color: #062a6a;
}

/* ── Filter Card ── */
.filter-card { border-radius: 12px; }
.filter-input-group {
    display: flex; align-items: center;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    overflow: hidden;
    transition: border-color .18s, box-shadow .18s;
}
.filter-input-group:focus-within {
    border-color: #062a6a;
    box-shadow: 0 0 0 3px rgba(6,42,106,.1);
    background: #fff;
}
.filter-icon {
    padding: 0 10px;
    color: #94a3b8;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.filter-input, .filter-select {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    outline: none !important;
    padding: 0.45rem 0.6rem 0.45rem 0;
    font-size: 0.875rem;
    width: 100%;
    color: #1e293b;
}
.filter-select { appearance: auto; cursor: pointer; }

/* ── City Filter Row ── */
.filter-city-row {
    display: flex; align-items: flex-start; gap: 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    padding: 8px 12px;
}
.filter-city-label {
    flex-shrink: 0;
    font-size: 0.8rem;
    font-weight: 600;
    color: #062a6a;
    padding-top: 6px;
    white-space: nowrap;
}
.filter-city-select-wrap { flex: 1; min-width: 0; }

/* Select2 Overrides — white text on navy tags */
.select2-container--bootstrap-5 .select2-selection--multiple {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    min-height: 32px;
    padding: 2px 4px;
}
.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
    background-color: #062a6a !important;
    border-color: #062a6a !important;
    color: #fff !important;
    font-size: 0.76rem;
    font-weight: 600;
    border-radius: 20px;
    padding: 2px 10px 2px 8px;
}
.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
    color: rgba(255,255,255,.8) !important;
    border-right: 1px solid rgba(255,255,255,.3) !important;
    padding: 0 5px 0 3px !important;
}
.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fff !important;
    background: transparent !important;
}
.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__placeholder {
    color: #94a3b8;
    font-size: 0.85rem;
}
.filter-city-select-wrap .select2-container { width: 100% !important; }
.select2-dropdown { border-color: #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.select2-container--bootstrap-5 .select2-results__option--highlighted { background-color: #062a6a !important; }

/* ── Orders Table ── */
.orders-table-card { border-radius: 12px; overflow: hidden; }
.orders-table thead tr { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
.orders-table thead th {
    font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: #64748b; padding: 12px 14px;
    border-bottom: 2px solid #e2e8f0;
}
.orders-table tbody td { padding: 12px 14px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.order-row { transition: background .14s; }
.order-row:hover { background: #f8fafc; }
.order-row:last-child td { border-bottom: none; }
.order-number-link {
    font-weight: 700; font-size: 0.9rem; color: #062a6a;
    text-decoration: none; letter-spacing: -.2px;
}
.order-number-link:hover { color: #1565c0; text-decoration: underline; }
.order-customer-name { font-size: 0.82rem; font-weight: 600; color: #334155; margin-top: 2px; }
.order-customer-email { font-size: 0.75rem; color: #94a3b8; }
.shipping-city-label { font-size: 0.72rem; color: #94a3b8; margin-top: 4px; }

/* ── Branch Badges ── */
.branch-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 50px;
    font-size: 0.72rem; font-weight: 700;
    white-space: nowrap; letter-spacing: .2px;
}
.branch-badge-harare  { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
.branch-badge-bulawayo{ background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.branch-badge-mutare  { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
.branch-badge-zambia  { background: #fce7f3; color: #be185d; border: 1px solid #f9a8d4; }
.branch-badge-home    { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
.branch-badge-digital { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
.branch-badge-other   { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

/* ── Empty State ── */
.empty-state { padding: 20px 0; }
.empty-state i { font-size: 3rem; color: #cbd5e1; display: block; margin-bottom: 12px; }
.empty-state p { font-size: 1rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
.empty-state span { font-size: 0.82rem; color: #94a3b8; }

/* ── Pagination ── */
.pagination { margin-bottom: 0; }
.pagination .page-link {
    color: #062a6a; border: 1px solid #e2e8f0;
    padding: 0.35rem 0.7rem; margin: 0 2px; border-radius: 8px;
    font-size: 0.82rem; transition: all .15s;
}
.pagination .page-link:hover { background: #f1f5f9; border-color: #cbd5e1; color: #062a6a; }
.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #062a6a 0%, #1565c0 100%);
    border-color: transparent; color: #fff;
    box-shadow: 0 3px 8px rgba(6,42,106,.3);
}
.pagination .page-item.disabled .page-link { color: #cbd5e1; background: #fff; border-color: #e2e8f0; }
.pagination .page-link:focus { box-shadow: 0 0 0 3px rgba(6,42,106,.15); }

/* ── Modal styles ── */
.summary-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 0.9rem; }
.summary-item.border-top { border-top: 2px solid #dee2e6 !important; }
.order-items-modal-table thead th { background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem; padding: 8px; }
.order-items-modal-table tbody td { padding: 10px 8px; vertical-align: middle; font-size: 0.85rem; }
.modal-body .card-header { padding: 8px 12px; }
.modal-body .card-header h6 { font-size: 0.9rem; font-weight: 600; }
.modal-body .card-body { padding: 12px; }
.modal-body .card-body p { margin-bottom: 8px; font-size: 0.9rem; }
</style>
@endpush

@push('scripts')
{{-- jQuery (required by Select2) + Select2 JS must load before init code --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {
        // Initialise Select2 on city multi-select — NO auto-submit on change
        // User picks all cities they want, then clicks Apply to apply
        $('#cityMultiFilter').select2({
            theme: 'bootstrap-5',
            placeholder: 'Filter by city…',
            allowClear: true,
            closeOnSelect: false,
            width: '100%',
        });
    });
</script>
@endpush

