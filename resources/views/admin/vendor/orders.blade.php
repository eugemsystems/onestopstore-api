@extends('admin.layout')

@section('title', 'My Orders')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-cart-check"></i> My Orders
            </h2>
            <p class="text-muted">Orders containing your products</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Orders</h6>
                    <h2 class="mb-0">{{ number_format($stats['total_orders']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Pending</h6>
                    <h2 class="mb-0">{{ number_format($stats['pending']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Completed</h6>
                    <h2 class="mb-0">{{ number_format($stats['completed']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Cancelled</h6>
                    <h2 class="mb-0">{{ number_format($stats['cancelled']) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vendor.orders') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Order Status</label>
                        <select name="order_status_id" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('order_status_id') == 1 ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ request('order_status_id') == 2 ? 'selected' : '' }}>Processing</option>
                            <option value="3" {{ request('order_status_id') == 3 ? 'selected' : '' }}>Shipped</option>
                            <option value="5" {{ request('order_status_id') == 5 ? 'selected' : '' }}>Delivered</option>
                            <option value="6" {{ request('order_status_id') == 6 ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search Order</label>
                        <input type="text" name="search" class="form-control" placeholder="Order number..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.vendor.orders') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Your Products</th>
                            <th>Your Commission</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_number }}</strong>
                            </td>
                            <td>{{ $order->consumer->name ?? 'N/A' }}</td>
                            <td>
                                <small>
                                    @foreach($order->products as $product)
                                        <div>• {{ $product->name }} (x{{ $product->pivot->quantity }})</div>
                                    @endforeach
                                </small>
                            </td>
                            <td>
                                @if(isset($commissions[$order->id]))
                                    <strong class="text-success">${{ number_format($commissions[$order->id], 2) }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = match($order->order_status->name ?? '') {
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        'shipped' => 'info',
                                        default => 'warning'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ ucfirst($order->order_status->name ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No orders found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

