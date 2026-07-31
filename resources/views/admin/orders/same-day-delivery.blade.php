@extends('admin.layout')

@section('title', 'Same Day Delivery Orders - Admin Panel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-rocket-fill text-danger"></i> Same Day Delivery Orders</h2>
        <p class="text-muted mb-0">Orders containing products with "Same Day" delivery</p>
    </div>
    <div>
        <span class="badge bg-danger fs-5">{{ $totalCount }} Orders</span>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-danger mb-3" style="background:#fff5f5;border-color:#fca5a5;color:#7f1d1d">
    <i class="bi bi-rocket-fill"></i>
    <strong>Note:</strong> This page shows orders with products whose delivery text starts with <strong>"Same Day"</strong> and with <strong>item status: Processing</strong>.
    <br>
    <small class="mt-1 d-block">Excluded order statuses: Pending, Cancelled, Collected, Ready for Delivery, Delivered.</small>
</div>

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.same-day-delivery') }}" class="row g-3">
            <div class="col-md-8">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by order number, customer name, or email..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
            @if(request('search'))
            <div class="col-12">
                <a href="{{ route('admin.orders.same-day-delivery') }}" class="btn btn-sm btn-outline-secondary">
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
                        <th>Same Day Products</th>
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
                                    $sdProducts = $order->products->filter(function($product) {
                                        return str_starts_with(strtolower($product->estimated_delivery_text ?? ''), 'same day')
                                            && $product->pivot->item_status == 'processing';
                                    });
                                    $sdCount = $sdProducts->count();
                                @endphp
                                <span class="badge bg-danger">
                                    <i class="bi bi-rocket-fill"></i> {{ $sdCount }} {{ $sdCount === 1 ? 'Product' : 'Products' }}
                                </span>
                                @if($sdCount > 0)
                                    <button type="button"
                                            class="btn btn-sm btn-link p-0 ms-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#sdModal{{ $order->id }}"
                                            title="View Products">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                @php
                                    $exchangeRate = floatval($order->exchange_rate ?? 1);
                                    $convertedTotal = floatval($order->total ?? 0) * $exchangeRate;
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
                                <p class="text-muted mt-2">No Same Day Delivery orders found</p>
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

<!-- Product Modals -->
@foreach($orders as $order)
    @php
        $sdProducts = $order->products->filter(function($product) {
            return str_starts_with(strtolower($product->estimated_delivery_text ?? ''), 'same day')
                && $product->pivot->item_status == 'processing';
        });
        $sdCount = $sdProducts->count();
    @endphp
    @if($sdCount > 0)
        <div class="modal fade" id="sdModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-rocket-fill text-danger"></i> Same Day Products — Order #{{ $order->order_number }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group">
                            @foreach($sdProducts as $product)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        @if($product->product_thumbnail && ($product->product_thumbnail->image_url ?? $product->product_thumbnail->original_url))
                                            <img src="{{ $product->product_thumbnail->image_url ?? $product->product_thumbnail->original_url }}"
                                                 alt="{{ $product->name }}"
                                                 class="me-3 rounded"
                                                 style="width:60px;height:60px;object-fit:cover"
                                                 onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                                        @else
                                            <div class="me-3 d-flex align-items-center justify-content-center bg-light rounded" style="width:60px;height:60px">
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
                                                      style="background-color:{{ \App\Helpers\OrderStatusColors::hex($product->pivot->item_status) }};color:{{ \App\Helpers\OrderStatusColors::textColor(\App\Helpers\OrderStatusColors::hex($product->pivot->item_status)) }}">
                                                    {{ ucwords(str_replace('_', ' ', $product->pivot->item_status)) }}
                                                </span>
                                            @endif
                                            <span class="badge bg-danger ms-1">
                                                <i class="bi bi-rocket-fill"></i> {{ $product->estimated_delivery_text }}
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
    .status-badge { display:inline-block; padding:.25rem .75rem; font-size:.875rem; font-weight:500; border-radius:.25rem; text-transform:capitalize; }
    .status-pending    { background:#ffc107; color:#000; }
    .status-processing { background:#0dcaf0; color:#000; }
    .status-shipped    { background:#0d6efd; color:#fff; }
    .status-delivered  { background:#198754; color:#fff; }
    .status-cancelled  { background:#dc3545; color:#fff; }
    .status-refunded   { background:#6c757d; color:#fff; }
    .list-group-item:hover { background:#f8f9fa; }
</style>
@endsection
