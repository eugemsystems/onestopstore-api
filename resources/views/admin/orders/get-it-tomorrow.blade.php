@extends('admin.layout')

@section('title', 'Get It Tomorrow Orders - Admin Panel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-lightning-fill text-warning"></i> Get It Tomorrow Orders</h2>
        <p class="text-muted mb-0">Orders containing products with "Get It Tomorrow" delivery</p>
    </div>
    <div>
        <span class="badge bg-primary fs-5">{{ $totalCount }} Orders</span>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Note:</strong> This page shows orders with products marked as "Get It Tomorrow" and with <strong>item status: Processing</strong>.
    <br>
    <small class="mt-1 d-block">Excluded order statuses: Pending, Cancelled, Collected, Ready for Delivery, Delivered.</small>
</div>

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.get-it-tomorrow') }}" class="row g-3">
            <div class="col-md-6">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by order number, customer name, or email..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="delivery_filter" class="form-select">
                    <option value="">All Delivery Types</option>
                    <option value="Get It Tomorrow" {{ request('delivery_filter') == 'Get It Tomorrow' ? 'selected' : '' }}>⚡ Get It Tomorrow</option>
                    <option value="same day delivery" {{ request('delivery_filter') == 'same day delivery' ? 'selected' : '' }}>Same Day Delivery</option>
                    <option value="Available now" {{ request('delivery_filter') == 'Available now' ? 'selected' : '' }}>Available Now</option>
                    <option value="3-5 Days" {{ request('delivery_filter') == '3-5 Days' ? 'selected' : '' }}>3-5 Days</option>
                    <optgroup label="Ships in Work Days">
                        <option value="Ships in 3-5 work days" {{ request('delivery_filter') == 'Ships in 3-5 work days' ? 'selected' : '' }}>Ships in 3-5 work days</option>
                        <option value="Ships in 4-6 work days" {{ request('delivery_filter') == 'Ships in 4-6 work days' ? 'selected' : '' }}>Ships in 4-6 work days</option>
                        <option value="Ships in 5-7 work days" {{ request('delivery_filter') == 'Ships in 5-7 work days' ? 'selected' : '' }}>Ships in 5-7 work days</option>
                        <option value="Ships in 6-8 work days" {{ request('delivery_filter') == 'Ships in 6-8 work days' ? 'selected' : '' }}>Ships in 6-8 work days</option>
                        <option value="Ships in 7-9 work days" {{ request('delivery_filter') == 'Ships in 7-9 work days' ? 'selected' : '' }}>Ships in 7-9 work days</option>
                        <option value="Ships in 8-10 work days" {{ request('delivery_filter') == 'Ships in 8-10 work days' ? 'selected' : '' }}>Ships in 8-10 work days</option>
                        <option value="Ships in 9-11 work days" {{ request('delivery_filter') == 'Ships in 9-11 work days' ? 'selected' : '' }}>Ships in 9-11 work days</option>
                        <option value="Ships in 9-12 work days" {{ request('delivery_filter') == 'Ships in 9-12 work days' ? 'selected' : '' }}>Ships in 9-12 work days</option>
                        <option value="Ships in 9-16 work days" {{ request('delivery_filter') == 'Ships in 9-16 work days' ? 'selected' : '' }}>Ships in 9-16 work days</option>
                        <option value="Ships in 10-12 work days" {{ request('delivery_filter') == 'Ships in 10-12 work days' ? 'selected' : '' }}>Ships in 10-12 work days</option>
                        <option value="Ships in 11-13 work days" {{ request('delivery_filter') == 'Ships in 11-13 work days' ? 'selected' : '' }}>Ships in 11-13 work days</option>
                        <option value="Ships in 12-14 work days" {{ request('delivery_filter') == 'Ships in 12-14 work days' ? 'selected' : '' }}>Ships in 12-14 work days</option>
                        <option value="Ships in 12-17 work days" {{ request('delivery_filter') == 'Ships in 12-17 work days' ? 'selected' : '' }}>Ships in 12-17 work days</option>
                        <option value="Ships in 13-15 work days" {{ request('delivery_filter') == 'Ships in 13-15 work days' ? 'selected' : '' }}>Ships in 13-15 work days</option>
                        <option value="Ships in 14-16 work days" {{ request('delivery_filter') == 'Ships in 14-16 work days' ? 'selected' : '' }}>Ships in 14-16 work days</option>
                        <option value="Ships in 15-17 work days" {{ request('delivery_filter') == 'Ships in 15-17 work days' ? 'selected' : '' }}>Ships in 15-17 work days</option>
                        <option value="Ships in 16-18 work days" {{ request('delivery_filter') == 'Ships in 16-18 work days' ? 'selected' : '' }}>Ships in 16-18 work days</option>
                        <option value="Ships in 17-19 work days" {{ request('delivery_filter') == 'Ships in 17-19 work days' ? 'selected' : '' }}>Ships in 17-19 work days</option>
                        <option value="Ships in 18-20 work days" {{ request('delivery_filter') == 'Ships in 18-20 work days' ? 'selected' : '' }}>Ships in 18-20 work days</option>
                        <option value="Ships in 20-22 work days" {{ request('delivery_filter') == 'Ships in 20-22 work days' ? 'selected' : '' }}>Ships in 20-22 work days</option>
                        <option value="Ships in 32-34 work days" {{ request('delivery_filter') == 'Ships in 32-34 work days' ? 'selected' : '' }}>Ships in 32-34 work days</option>
                    </optgroup>
                    <option value="Supplier out of stock" {{ request('delivery_filter') == 'Supplier out of stock' ? 'selected' : '' }}>Supplier Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
            @if(request('search') || request('delivery_filter'))
            <div class="col-12">
                <a href="{{ route('admin.orders.get-it-tomorrow') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Get It Tomorrow Products</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="fw-bold text-decoration-none">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td>
                                {{ $order->consumer->name ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $order->consumer->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}">
                                    {{ $order->order_status->name ?? 'Pending' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    // Filter products by:
                                    // 1. Product has estimated_delivery_text = "Get It Tomorrow"
                                    // 2. Order item status is "processing"
                                    $gitProducts = $order->products->filter(function($product) {
                                        return $product->estimated_delivery_text == 'Get It Tomorrow'
                                            && $product->pivot->item_status == 'processing';
                                    });
                                    $gitCount = $gitProducts->count();
                                @endphp
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-lightning-fill"></i> {{ $gitCount }} {{ $gitCount === 1 ? 'Product' : 'Products' }}
                                </span>
                                @if($gitCount > 0)
                                    <button type="button"
                                            class="btn btn-sm btn-link p-0 ms-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#productsModal{{ $order->id }}"
                                            title="View Products">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                @php
                                    $exchangeRate = floatval($order->exchange_rate ?? 1);
                                    $totalUSD = floatval($order->total ?? 0);
                                    $convertedTotal = $totalUSD * $exchangeRate;
                                @endphp
                                <div class="fw-bold">{{ $order->currency_symbol ?? 'R' }} {{ number_format($convertedTotal, 2) }}</div>
                            </td>
                            <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order->order_number) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="View Order">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No "Get It Tomorrow" orders found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders
            </div>
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Modals Section - Placed outside table to prevent flickering -->
@foreach($orders as $order)
    @php
        // Filter products by both estimated_delivery_text and processing status
        $gitProducts = $order->products->filter(function($product) {
            return $product->estimated_delivery_text == 'Get It Tomorrow'
                && $product->pivot->item_status == 'processing';
        });
        $gitCount = $gitProducts->count();
    @endphp
    @if($gitCount > 0)
        <div class="modal fade" id="productsModal{{ $order->id }}" tabindex="-1" aria-labelledby="productsModalLabel{{ $order->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productsModalLabel{{ $order->id }}">
                            <i class="bi bi-lightning-fill text-warning"></i> Get It Tomorrow Products - Order #{{ $order->order_number }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group">
                            @foreach($gitProducts as $product)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        @if($product->product_thumbnail && $product->product_thumbnail->image_url)
                                            <img src="{{ $product->product_thumbnail->image_url }}"
                                                 alt="{{ $product->name }}"
                                                 class="me-3 rounded"
                                                 style="width: 60px; height: 60px; object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                                        @elseif($product->product_thumbnail && $product->product_thumbnail->original_url)
                                            <img src="{{ $product->product_thumbnail->original_url }}"
                                                 alt="{{ $product->name }}"
                                                 class="me-3 rounded"
                                                 style="width: 60px; height: 60px; object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                                        @else
                                            <div class="me-3 d-flex align-items-center justify-content-center bg-light rounded"
                                                 style="width: 60px; height: 60px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $product->name }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-upc"></i> SKU: {{ $product->sku ?? 'N/A' }} |
                                                <i class="bi bi-box"></i> Qty: {{ $product->pivot->quantity ?? 0 }}
                                            </small>
                                            <br>
                                            @if($product->pivot->item_status)
                                                <span class="badge"
                                                      style="background-color: {{ \App\Helpers\OrderStatusColors::hex($product->pivot->item_status) }}; color: {{ \App\Helpers\OrderStatusColors::textColor(\App\Helpers\OrderStatusColors::hex($product->pivot->item_status)) }};">
                                                    {{ ucwords(str_replace('_', ' ', $product->pivot->item_status)) }}
                                                </span>
                                            @endif
                                            <span class="badge bg-warning text-dark mt-1">
                                                <i class="bi bi-lightning-fill"></i> Get It Tomorrow
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <strong class="fs-5">{{ $order->currency_symbol ?? 'R' }} {{ number_format(($product->pivot->single_price ?? 0) * ($product->pivot->quantity ?? 1), 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('admin.orders.show', $order->order_number) }}" class="btn btn-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View Full Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

<style>
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.25rem;
        text-transform: capitalize;
    }

    .status-pending { background-color: #ffc107; color: #000; }
    .status-processing { background-color: #0dcaf0; color: #000; }
    .status-shipped { background-color: #0d6efd; color: #fff; }
    .status-delivered { background-color: #198754; color: #fff; }
    .status-cancelled { background-color: #dc3545; color: #fff; }
    .status-refunded { background-color: #6c757d; color: #fff; }

    /* Modal optimization to prevent flickering */
    .modal {
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }

    .modal.fade .modal-dialog {
        transition: transform 0.2s ease-out;
    }

    .modal-backdrop {
        transition: opacity 0.15s linear;
    }

    /* Smooth button hover effect */
    .btn-link:hover {
        transform: scale(1.1);
        transition: transform 0.15s ease;
    }

    /* List group item hover effect */
    .list-group-item {
        transition: background-color 0.15s ease;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
