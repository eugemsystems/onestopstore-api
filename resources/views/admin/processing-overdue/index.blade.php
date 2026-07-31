@extends('admin.layout')

@section('title', 'Processing Overdue - Admin Panel')

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
    <h2><i class="bi bi-clock-history"></i> Processing Overdue (3+ Days)</h2>
    <div>
        <span class="badge bg-warning text-dark fs-6">
            <i class="bi bi-exclamation-triangle"></i>
            @if($tab === 'items')
                {{ $orderProducts->total() }} Items Overdue
            @else
                {{ $orders->total() }} Orders Overdue
            @endif
        </span>
    </div>
</div>

<!-- Branch Filter Buttons -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Filter by Branch:</h6>
        <div class="d-flex flex-wrap gap-2">
            <!-- All Branches Button -->
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->except('branch'), request()->query())) }}"
               class="btn {{ !request('branch') ? 'btn-dark' : 'btn-outline-dark' }}">
                <i class="bi bi-grid-3x3-gap"></i> All Branches
                <span class="badge bg-white text-dark ms-1">{{ $stats['total_overdue'] }}</span>
            </a>

            <!-- Harare Branch -->
            @if(isset($branchCounts['Harare Branch']) && $branchCounts['Harare Branch'] > 0)
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->query(), ['branch' => 'Harare Branch'])) }}"
               class="btn {{ request('branch') === 'Harare Branch' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-geo-alt"></i> Harare Branch
                <span class="badge bg-white text-primary ms-1">{{ $branchCounts['Harare Branch'] }}</span>
            </a>
            @endif

            <!-- Bulawayo Branch -->
            @if(isset($branchCounts['Bulawayo Branch']) && $branchCounts['Bulawayo Branch'] > 0)
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->query(), ['branch' => 'Bulawayo Branch'])) }}"
               class="btn {{ request('branch') === 'Bulawayo Branch' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="bi bi-geo-alt"></i> Bulawayo Branch
                <span class="badge bg-white text-success ms-1">{{ $branchCounts['Bulawayo Branch'] }}</span>
            </a>
            @endif

            <!-- Lusaka Branch -->
            @if(isset($branchCounts['Lusaka Branch']) && $branchCounts['Lusaka Branch'] > 0)
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->query(), ['branch' => 'Lusaka Branch'])) }}"
               class="btn {{ request('branch') === 'Lusaka Branch' ? 'btn-warning' : 'btn-outline-warning' }}">
                <i class="bi bi-geo-alt"></i> Lusaka Branch
                <span class="badge bg-white text-warning ms-1">{{ $branchCounts['Lusaka Branch'] }}</span>
            </a>
            @endif

            <!-- Mutare Branch -->
            @if(isset($branchCounts['Mutare Branch']) && $branchCounts['Mutare Branch'] > 0)
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->query(), ['branch' => 'Mutare Branch'])) }}"
               class="btn {{ request('branch') === 'Mutare Branch' ? 'btn-danger' : 'btn-outline-danger' }}">
                <i class="bi bi-geo-alt"></i> Mutare Branch
                <span class="badge bg-white text-danger ms-1">{{ $branchCounts['Mutare Branch'] }}</span>
            </a>
            @endif

            <!-- Home Delivery -->
            @if(isset($branchCounts['Home Delivery']) && $branchCounts['Home Delivery'] > 0)
            <a href="{{ route('admin.processing-overdue.index', array_merge(request()->query(), ['branch' => 'Home Delivery'])) }}"
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

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="processingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $tab === 'orders' ? 'active' : '' }}"
           href="{{ route('admin.processing-overdue.index', ['tab' => 'orders']) }}">
            <i class="bi bi-cart"></i> Overdue Orders
            @if(isset($orders))
                <span class="badge bg-danger ms-1">{{ $orders->total() }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $tab === 'items' ? 'active' : '' }}"
           href="{{ route('admin.processing-overdue.index', ['tab' => 'items']) }}">
            <i class="bi bi-box"></i> Overdue Items
            @if(isset($orderProducts))
                <span class="badge bg-danger ms-1">{{ $orderProducts->total() }}</span>
            @endif
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="processingTabContent">
    @if($tab === 'orders')
        <!-- Tab 1: Overdue Orders -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cart-x"></i> Orders in Processing Status for More Than 3 Days</h5>
                <small class="text-muted">Last updated before: {{ $threeDaysAgo->format('Y-m-d H:i') }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Collection Point</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th>Total</th>
                                <th>Last Updated</th>
                                <th>Days Overdue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $daysOverdue = floor($order->updated_at->diffInDays(now()));
                                    $urgencyClass = $daysOverdue > 7 ? 'danger' : ($daysOverdue > 5 ? 'warning' : 'info');

                                    // Shorten delivery description
                                    $collectionPoint = $order->delivery_description ?? 'N/A';
                                    $lowerDesc = strtolower($collectionPoint);
                                    if (strpos($lowerDesc, 'harare') !== false) {
                                        $shortPoint = 'Harare Branch';
                                        $badgeColor = 'bg-primary';
                                    } elseif (strpos($lowerDesc, 'bulawayo') !== false) {
                                        $shortPoint = 'Bulawayo Branch';
                                        $badgeColor = 'bg-success';
                                    } elseif (strpos($lowerDesc, 'lusaka') !== false) {
                                        $shortPoint = 'Lusaka Branch';
                                        $badgeColor = 'bg-warning text-dark';
                                    } elseif (strpos($lowerDesc, 'mutare') !== false) {
                                        $shortPoint = 'Mutare Branch';
                                        $badgeColor = 'bg-danger';
                                    } elseif (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
                                        $shortPoint = 'Home Delivery';
                                        $badgeColor = 'bg-info';
                                    } else {
                                        $shortPoint = 'Other';
                                        $badgeColor = 'bg-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->order_number) }}"
                                           class="fw-bold text-decoration-none">
                                            #{{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $order->consumer->name ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $order->consumer->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeColor }}">{{ $shortPoint }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $order->order_status->name ?? 'Processing' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $order->products->count() }} items</span>
                                    </td>
                                    <td>{{ $order->currency_symbol ?? '$' }}{{ number_format($order->total, 2) }}</td>
                                    <td>
                                        <small>{{ $order->updated_at->format('Y-m-d H:i') }}</small><br>
                                        <small class="text-muted">{{ $order->updated_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $urgencyClass }}">
                                            <i class="bi bi-exclamation-circle"></i> {{ $daysOverdue }} days
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->order_number) }}"
                                           class="btn btn-sm btn-primary"
                                           title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No overdue orders found! All orders are up to date.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($orders->hasPages())
                    <div class="mt-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Tab 2: Overdue Order Items -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Order Items with Processing Status for More Than 3 Days</h5>
                <small class="text-muted">Last updated before: {{ $threeDaysAgo->format('Y-m-d H:i') }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Collection Point</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                                <th>Item Status</th>
                                <th>Last Updated</th>
                                <th>Days Overdue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orderProducts as $item)
                                @php
                                    $itemUpdated = \Carbon\Carbon::parse($item->item_updated_at);
                                    $daysOverdue = floor($itemUpdated->diffInDays(now()));
                                    $urgencyClass = $daysOverdue > 7 ? 'danger' : ($daysOverdue > 5 ? 'warning' : 'info');

                                    // Shorten delivery description
                                    $collectionPoint = $item->delivery_description ?? 'N/A';
                                    $lowerDesc = strtolower($collectionPoint);
                                    if (strpos($lowerDesc, 'harare') !== false) {
                                        $shortPoint = 'Harare Branch';
                                        $badgeColor = 'bg-primary';
                                    } elseif (strpos($lowerDesc, 'bulawayo') !== false) {
                                        $shortPoint = 'Bulawayo Branch';
                                        $badgeColor = 'bg-success';
                                    } elseif (strpos($lowerDesc, 'lusaka') !== false) {
                                        $shortPoint = 'Lusaka Branch';
                                        $badgeColor = 'bg-warning text-dark';
                                    } elseif (strpos($lowerDesc, 'mutare') !== false) {
                                        $shortPoint = 'Mutare Branch';
                                        $badgeColor = 'bg-danger';
                                    } elseif (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
                                        $shortPoint = 'Home Delivery';
                                        $badgeColor = 'bg-info';
                                    } else {
                                        $shortPoint = 'Other';
                                        $badgeColor = 'bg-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $item->order_number) }}"
                                           class="fw-bold text-decoration-none">
                                            #{{ $item->order_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->variation_id)
                                            <br><small class="text-muted">Variation ID: {{ $item->variation_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->customer_name ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $item->customer_email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeColor }}">{{ $shortPoint }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $item->quantity }}</span>
                                    </td>
                                    <td>${{ number_format($item->single_price, 2) }}</td>
                                    <td><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                    <td>
                                        <span class="badge bg-primary">{{ ucfirst($item->item_status) }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $itemUpdated->format('Y-m-d H:i') }}</small><br>
                                        <small class="text-muted">{{ $itemUpdated->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $urgencyClass }}">
                                            <i class="bi bi-exclamation-circle"></i> {{ $daysOverdue }} days
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $item->order_number) }}"
                                           class="btn btn-sm btn-primary"
                                           title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No overdue items found! All items are up to date.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($orderProducts->hasPages())
                    <div class="mt-3">
                        {{ $orderProducts->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Statistics Card -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Information</h5>
                <ul class="mb-0">
                    <li><strong>Overdue Criteria:</strong> Items or orders that have been in "Processing" status for more than 3 days.</li>
                    <li><strong>Orders Tab:</strong> Shows complete orders stuck in processing status.</li>
                    <li><strong>Items Tab:</strong> Shows individual order items with processing status (more granular view).</li>
                    <li><strong>Urgency Levels:</strong>
                        <span class="badge bg-info ms-1">3-5 days</span>
                        <span class="badge bg-warning ms-1">5-7 days</span>
                        <span class="badge bg-danger ms-1">7+ days</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
    }
    .nav-tabs .nav-link:hover {
        color: #0d6efd;
    }
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
    }
    .badge {
        font-size: 0.75rem;
    }
</style>
@endpush

