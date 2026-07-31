@extends('admin.layout')

@section('title', 'Overdue Order Products')

@php
// Helper function to shorten delivery descriptions
function shortenDeliveryDescription($description) {
    if (empty($description)) {
        return 'Other';
    }

    // Check for partial matches (case-insensitive)
    $lowerDesc = strtolower($description);

    if (strpos($lowerDesc, 'harare') !== false) {
        return 'Harare Branch';
    }
    if (strpos($lowerDesc, 'bulawayo') !== false) {
        return 'Bulawayo Branch';
    }
    if (strpos($lowerDesc, 'lusaka') !== false) {
        return 'Lusaka Branch';
    }
    if (strpos($lowerDesc, 'mutare') !== false) {
        return 'Mutare Branch';
    }
    if (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
        return 'Home Delivery';
    }

    // If no match found, return 'Other'
    return 'Other';
}

// Helper function to get badge color for each branch
function getBranchBadgeColor($branchName) {
    $colors = [
        'Harare Branch' => 'bg-primary',
        'Bulawayo Branch' => 'bg-success',
        'Lusaka Branch' => 'bg-warning text-dark',
        'Mutare Branch' => 'bg-danger',
        'Home Delivery' => 'bg-info',
    ];

    return $colors[$branchName] ?? 'bg-secondary';
}
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history"></i> Overdue Order Products (Past ETA)</h2>
    <a href="{{ route('admin.order-products-eta.export', request()->query()) }}" class="btn btn-success">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<!-- Branch Filter Buttons -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Filter by Collection Point / Delivery Method:</h6>
        <div class="d-flex flex-wrap gap-2">
            <!-- All Items Button -->
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->except('branch'), request()->query())) }}"
               class="btn {{ !request('branch') ? 'btn-dark' : 'btn-outline-dark' }}">
                <i class="bi bi-grid-3x3-gap"></i> All Items
                <span class="badge bg-white text-dark ms-1">{{ $stats['total_overdue'] }}</span>
            </a>

            <!-- Harare Branch -->
            @if(isset($branchCounts['Harare Branch']) && $branchCounts['Harare Branch'] > 0)
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->query(), ['branch' => 'Harare Branch'])) }}"
               class="btn {{ request('branch') === 'Harare Branch' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-geo-alt"></i> Harare Branch
                <span class="badge bg-white text-primary ms-1">{{ $branchCounts['Harare Branch'] }}</span>
            </a>
            @endif

            <!-- Bulawayo Branch -->
            @if(isset($branchCounts['Bulawayo Branch']) && $branchCounts['Bulawayo Branch'] > 0)
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->query(), ['branch' => 'Bulawayo Branch'])) }}"
               class="btn {{ request('branch') === 'Bulawayo Branch' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="bi bi-geo-alt"></i> Bulawayo Branch
                <span class="badge bg-white text-success ms-1">{{ $branchCounts['Bulawayo Branch'] }}</span>
            </a>
            @endif

            <!-- Lusaka Branch -->
            @if(isset($branchCounts['Lusaka Branch']) && $branchCounts['Lusaka Branch'] > 0)
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->query(), ['branch' => 'Lusaka Branch'])) }}"
               class="btn {{ request('branch') === 'Lusaka Branch' ? 'btn-warning' : 'btn-outline-warning' }}">
                <i class="bi bi-geo-alt"></i> Lusaka Branch
                <span class="badge bg-white text-warning ms-1">{{ $branchCounts['Lusaka Branch'] }}</span>
            </a>
            @endif

            <!-- Mutare Branch -->
            @if(isset($branchCounts['Mutare Branch']) && $branchCounts['Mutare Branch'] > 0)
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->query(), ['branch' => 'Mutare Branch'])) }}"
               class="btn {{ request('branch') === 'Mutare Branch' ? 'btn-danger' : 'btn-outline-danger' }}">
                <i class="bi bi-geo-alt"></i> Mutare Branch
                <span class="badge bg-white text-danger ms-1">{{ $branchCounts['Mutare Branch'] }}</span>
            </a>
            @endif

            <!-- Home Delivery -->
            @if(isset($branchCounts['Home Delivery']) && $branchCounts['Home Delivery'] > 0)
            <a href="{{ route('admin.order-products-eta.index', array_merge(request()->query(), ['branch' => 'Home Delivery'])) }}"
               class="btn {{ request('branch') === 'Home Delivery' ? 'btn-info' : 'btn-outline-info' }}">
                <i class="bi bi-truck"></i> Home Delivery
                <span class="badge bg-white text-info ms-1">{{ $branchCounts['Home Delivery'] }}</span>
            </a>
            @endif
        </div>
        @if(request('branch'))
        <div class="mt-2">
            <small class="text-muted">
                <i class="bi bi-filter-circle"></i> Filtered by: <strong>{{ request('branch') }}</strong>
            </small>
        </div>
        @endif
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center bg-danger text-white">
            <div class="card-body">
                <h3 class="card-title">{{ number_format($stats['total_overdue']) }}</h3>
                <p class="card-text">Total Overdue Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-warning text-white">
            <div class="card-body">
                <h3 class="card-title">${{ number_format($stats['total_value'], 2) }}</h3>
                <p class="card-text">Total Value</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <h3 class="card-title">{{ number_format($stats['avg_days_overdue'], 1) }}</h3>
                <p class="card-text">Avg Days Overdue</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.order-products-eta.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Product, SKU, Order #, Customer..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Min Days Overdue</label>
                <input type="number" name="days_overdue" class="form-control" placeholder="e.g., 7" value="{{ request('days_overdue') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Order Status</label>
                <select name="order_status_id" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($orderStatuses as $status)
                        <option value="{{ $status->id }}" {{ request('order_status_id') == $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('admin.order-products-eta.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>

        <div class="mt-3">
            <small class="text-muted">
                <strong>Excluded Order Statuses:</strong> {{ implode(', ', $excludedOrderStatuses) }}<br>
                <strong>Excluded Item Statuses:</strong> {{ implode(', ', $excludedItemStatuses) }}
            </small>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Collection Point / Delivery</th>
                        <th>ETA Date</th>
                        <th>Days Overdue</th>
                        <th>Item Status</th>
                        <th>Order Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdueItems as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $item->order_number) }}" target="_blank">
                                <strong>#{{ $item->order_number }}</strong>
                            </a>
                        </td>
                        <td>
                            <div><strong>{{ Str::limit($item->product_name, 40) }}</strong></div>
                            <small class="text-muted">SKU: {{ $item->product_sku }}</small>
                            @if($item->variation_display_name)
                                <div><small class="text-info">{{ $item->variation_display_name }}</small></div>
                            @endif
                            <div class="mt-1">
                                <span class="badge bg-light text-dark">Qty: {{ $item->quantity }}</span>
                                <span class="badge bg-light text-dark">Price: ${{ number_format($item->single_price, 2) }}</span>
                            </div>
                        </td>
                        <td>
                            <div>{{ $item->customer_name }}</div>
                            <small class="text-muted">{{ $item->customer_email }}</small>
                        </td>
                        <td>
                            @if($item->collection_point)
                                @php
                                    $shortName = shortenDeliveryDescription($item->collection_point);
                                    $badgeColor = getBranchBadgeColor($shortName);
                                @endphp
                                <span class="badge {{ $badgeColor }}">{{ $shortName }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-danger">
                                {{ \Carbon\Carbon::parse($item->eta)->format('M d, Y') }}
                            </span>
                        </td>
                        <td>
                            @if($item->days_overdue >= 30)
                                <span class="badge bg-danger">{{ $item->days_overdue }} days</span>
                            @elseif($item->days_overdue >= 14)
                                <span class="badge bg-warning">{{ $item->days_overdue }} days</span>
                            @else
                                <span class="badge bg-info">{{ $item->days_overdue }} days</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $item->item_status ?? 'Pending' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $item->order_status_name }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $item->order_number) }}"
                               class="btn btn-sm btn-primary"
                               target="_blank"
                               title="View Order">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <p class="mt-2">No overdue order products found! All items are on track or completed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $overdueItems->links() }}
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .badge {
        padding: 0.5em 0.75em;
        font-size: 0.875rem;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
    }

    /* Branch filter buttons styling */
    .gap-2 {
        gap: 0.5rem !important;
    }

    .btn .badge {
        font-size: 0.75rem;
        padding: 0.25em 0.5em;
        font-weight: 600;
    }

    /* Make active buttons stand out */
    .btn-primary:not(.btn-outline-primary),
    .btn-success:not(.btn-outline-success),
    .btn-warning:not(.btn-outline-warning),
    .btn-danger:not(.btn-outline-danger),
    .btn-info:not(.btn-outline-info),
    .btn-dark:not(.btn-outline-dark) {
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        font-weight: 600;
    }

    /* Branch button icons */
    .btn i.bi {
        margin-right: 0.25rem;
    }
</style>
@endpush
@endsection

