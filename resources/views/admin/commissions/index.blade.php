@extends('admin.layout')

@section('title', 'Commission History')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-cash-stack"></i> Commission History
                    </h2>
                    <p class="text-muted">View and manage all commission records</p>
                </div>
                <div>
                    <a href="{{ route('admin.commissions.export', request()->all()) }}" class="btn btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Admin Commission</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_admin_commission'], 2) }}</h3>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Vendor Commission</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_vendor_commission'], 2) }}</h3>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">This Month - Admin</h6>
                    <h3 class="mb-0">${{ number_format($stats['this_month_admin'], 2) }}</h3>
                    <small>{{ now()->format('F Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">This Month - Vendor</h6>
                    <h3 class="mb-0">${{ number_format($stats['this_month_vendor'], 2) }}</h3>
                    <small>{{ now()->format('F Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.commissions.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Store/Vendor</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->vendor->name ?? 'N/A' }})
                                </option>
                            @endforeach
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
                            <a href="{{ route('admin.commissions.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Store Name</th>
                            <th>Vendor Name</th>
                            <th>Admin Commission</th>
                            <th>Vendor Commission</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $commission->order->order_number) }}" target="_blank">
                                    <strong>{{ $commission->order->order_number ?? 'N/A' }}</strong>
                                </a>
                            </td>
                            <td>{{ $commission->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $commission->store->vendor->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-primary">${{ number_format($commission->admin_commission, 2) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success">${{ number_format($commission->vendor_commission, 2) }}</span>
                            </td>
                            <td>
                                <strong>${{ number_format($commission->admin_commission + $commission->vendor_commission, 2) }}</strong>
                            </td>
                            <td>{{ $commission->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.orders.show', $commission->order->order_number) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-eye"></i> View Order
                                    </a>
                                    @if($commission->items && $commission->items->count() > 0)
                                        <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#commission-items-{{ $commission->id }}" aria-expanded="false">
                                            <i class="fas fa-list"></i> Breakdown
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($commission->items && $commission->items->count() > 0)
                        <tr class="collapse" id="commission-items-{{ $commission->id }}">
                            <td colspan="8" class="bg-light">
                                <div class="card mb-0 border-0">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="fas fa-box"></i> Commission Breakdown by Product
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <th>Rate Applied</th>
                                                        <th>Source</th>
                                                        <th>Subtotal</th>
                                                        <th>Admin Commission</th>
                                                        <th>Vendor Commission</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($commission->items as $item)
                                                    <tr>
                                                        <td>
                                                            @if($item->product)
                                                                <a href="{{ route('admin.products.edit', $item->product_id) }}" target="_blank">
                                                                    {{ $item->product_name }}
                                                                </a>
                                                            @else
                                                                {{ $item->product_name }}
                                                            @endif
                                                            @if($item->product_sku)
                                                                <br><small class="text-muted">SKU: {{ $item->product_sku }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item->category_name)
                                                                <span class="badge bg-secondary">{{ $item->category_name }}</span>
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <strong>{{ number_format($item->commission_rate, 2) }}%</strong>
                                                        </td>
                                                        <td>
                                                            @if($item->commission_source == 'category')
                                                                <span class="badge bg-primary">Category</span>
                                                            @else
                                                                <span class="badge bg-warning">Default</span>
                                                            @endif
                                                        </td>
                                                        <td>${{ number_format($item->subtotal, 2) }}</td>
                                                        <td class="text-primary"><strong>${{ number_format($item->admin_commission, 2) }}</strong></td>
                                                        <td class="text-success"><strong>${{ number_format($item->vendor_commission, 2) }}</strong></td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                                        <td class="text-primary"><strong>${{ number_format($commission->admin_commission, 2) }}</strong></td>
                                                        <td class="text-success"><strong>${{ number_format($commission->vendor_commission, 2) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No commission records found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($commissions->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Page Total:</td>
                            <td>
                                <span class="badge bg-primary">${{ number_format($commissions->sum('admin_commission'), 2) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success">${{ number_format($commissions->sum('vendor_commission'), 2) }}</span>
                            </td>
                            <td>
                                <strong>${{ number_format($commissions->sum('admin_commission') + $commissions->sum('vendor_commission'), 2) }}</strong>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $commissions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

