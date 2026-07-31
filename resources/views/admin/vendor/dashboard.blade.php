@extends('admin.layout')

@section('title', 'Vendor Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-shop"></i> Vendor Dashboard
            </h2>
            <p class="text-muted">Welcome back, {{ $store->store_name }}!</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Row 1: Products & Orders -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">Total Products</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_products']) }}</h2>
                            <small>{{ $stats['active_products'] }} active</small>
                        </div>
                        <i class="bi bi-box-seam" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="{{ route('admin.vendor.products') }}" class="text-white text-decoration-none">
                        View Products <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">Total Orders</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_orders']) }}</h2>
                            <small>{{ $stats['pending_orders'] }} pending</small>
                        </div>
                        <i class="bi bi-cart-check" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="{{ route('admin.vendor.orders') }}" class="text-white text-decoration-none">
                        View Orders <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">Total Earnings</h6>
                            <h2 class="mb-0">${{ number_format($stats['total_earnings'], 2) }}</h2>
                            <small>This month: ${{ number_format($stats['this_month_earnings'], 2) }}</small>
                        </div>
                        <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="{{ route('admin.vendor.commissions') }}" class="text-white text-decoration-none">
                        View Commissions <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2">Wallet Balance</h6>
                            <h2 class="mb-0">${{ number_format($stats['wallet_balance'], 2) }}</h2>
                            <small>Pending: ${{ number_format($stats['pending_withdrawals'], 2) }}</small>
                        </div>
                        <i class="bi bi-wallet2" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="{{ route('admin.vendor.withdrawals.index') }}" class="text-white text-decoration-none">
                        Request Withdrawal <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Row 2: Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i>
                    <h4 class="mt-2 mb-0">{{ $stats['pending_products'] }}</h4>
                    <small class="text-muted">Pending Approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                    <h4 class="mt-2 mb-0">{{ $stats['pending_orders'] }}</h4>
                    <small class="text-muted">Orders to Process</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <h4 class="mt-2 mb-0">{{ $stats['completed_orders'] }}</h4>
                    <small class="text-muted">Completed Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                    <h4 class="mt-2 mb-0">${{ number_format($stats['this_month_earnings'], 2) }}</h4>
                    <small class="text-muted">This Month Earnings</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Recent Orders
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.vendor.orders') }}?search={{ $order->order_number }}">
                                            <strong>{{ $order->order_number }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $order->consumer->name ?? 'N/A' }}</td>
                                    <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $order->order_status->name == 'delivered' ? 'success' : ($order->order_status->name == 'cancelled' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($order->order_status->name ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No orders yet</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($recentOrders->isNotEmpty())
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.vendor.orders') }}" class="btn btn-outline-primary">
                            View All Orders <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Monthly Earnings Chart -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Earnings Trend
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <canvas id="earningsChart" height="250"></canvas>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('admin.vendor.commissions') }}" class="btn btn-outline-info btn-sm">
                            View Detailed Report <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add New Product
                        </a>
                        <a href="{{ route('admin.vendor.withdrawals.index') }}" class="btn btn-warning">
                            <i class="bi bi-wallet2"></i> Request Withdrawal
                        </a>
                        <a href="{{ route('admin.vendor.products') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-box-seam"></i> Manage Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Earnings Chart
const ctx = document.getElementById('earningsChart').getContext('2d');
const monthlyData = @json($monthlyEarnings);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Earnings ($)',
            data: monthlyData.map(item => item.earnings),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '$' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection

