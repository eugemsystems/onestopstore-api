@extends('admin.layout')

@section('title', 'My Commissions')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-cash-coin"></i> My Commissions
                    </h2>
                    <p class="text-muted">Track your earnings</p>
                </div>
                <div>
                    <a href="{{ route('admin.vendor.commissions.export', request()->all()) }}" class="btn btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Earnings</h6>
                    <h2 class="mb-0">${{ number_format($stats['total_earnings'], 2) }}</h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">This Month</h6>
                    <h2 class="mb-0">${{ number_format($stats['this_month'], 2) }}</h2>
                    <small>{{ now()->format('F Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Last Month</h6>
                    <h2 class="mb-0">${{ number_format($stats['last_month'], 2) }}</h2>
                    <small>{{ now()->subMonth()->format('F Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Orders</h6>
                    <h2 class="mb-0">{{ number_format($stats['total_orders']) }}</h2>
                    <small>Commission records</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vendor.commissions') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.vendor.commissions') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="5%"></th>
                            <th>Order #</th>
                            <th>Your Commission</th>
                            <th>Admin Commission</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                        <tr>
                            <td>
                                @if($commission->items && $commission->items->count() > 0)
                                <button class="btn btn-sm btn-link" type="button"
                                    onclick="toggleDetails('commission-{{ $commission->id }}')">
                                    <i class="bi bi-chevron-right" id="icon-commission-{{ $commission->id }}"></i>
                                </button>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $commission->order->order_number ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($commission->vendor_commission, 2) }}</strong>
                            </td>
                            <td>
                                <span class="text-muted">${{ number_format($commission->admin_commission, 2) }}</span>
                            </td>
                            <td>
                                <strong>${{ number_format($commission->vendor_commission + $commission->admin_commission, 2) }}</strong>
                            </td>
                            <td>{{ $commission->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $commission->order->order_number) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-eye"></i> View Order
                                </a>
                            </td>
                        </tr>
                        <!-- Per-Product Breakdown Row -->
                        @if($commission->items && $commission->items->count() > 0)
                        <tr id="details-commission-{{ $commission->id }}" style="display: none;">
                            <td colspan="7" class="bg-light">
                                <div class="p-3">
                                    <h6 class="mb-3"><i class="bi bi-box-seam"></i> Product Commission Breakdown</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Category</th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
                                                    <th>Subtotal</th>
                                                    <th>Rate %</th>
                                                    <th>Source</th>
                                                    <th>Admin Comm.</th>
                                                    <th>Your Comm.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($commission->items as $item)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $item->product_name }}</strong>
                                                    </td>
                                                    <td>
                                                        <code class="small">{{ $item->product_sku ?? 'N/A' }}</code>
                                                    </td>
                                                    <td>
                                                        @if($item->category_name)
                                                            <span class="badge bg-info">{{ $item->category_name }}</span>
                                                        @else
                                                            <span class="text-muted small">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $item->quantity }}</td>
                                                    <td>${{ number_format($item->product_price, 2) }}</td>
                                                    <td><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ number_format($item->commission_rate, 2) }}%</span>
                                                    </td>
                                                    <td>
                                                        @if($item->commission_source == 'category')
                                                            <span class="badge bg-success">Category</span>
                                                        @else
                                                            <span class="badge bg-secondary">Default</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted">${{ number_format($item->admin_commission, 2) }}</td>
                                                    <td class="text-success"><strong>${{ number_format($item->vendor_commission, 2) }}</strong></td>
                                                </tr>
                                                @endforeach
                                                <tr class="fw-bold table-active">
                                                    <td colspan="8" class="text-end">Total:</td>
                                                    <td class="text-muted">${{ number_format($commission->items->sum('admin_commission'), 2) }}</td>
                                                    <td class="text-success">${{ number_format($commission->items->sum('vendor_commission'), 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No commission records found</p>
                                <small class="text-muted">Commissions are earned when orders are delivered</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($commissions->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td></td>
                            <td>Page Total:</td>
                            <td>
                                <strong class="text-success">${{ number_format($commissions->sum('vendor_commission'), 2) }}</strong>
                            </td>
                            <td>
                                <span class="text-muted">${{ number_format($commissions->sum('admin_commission'), 2) }}</span>
                            </td>
                            <td>
                                <strong>${{ number_format($commissions->sum('vendor_commission') + $commissions->sum('admin_commission'), 2) }}</strong>
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

@push('scripts')
<script>
function toggleDetails(commissionId) {
    const detailsRow = document.getElementById('details-' + commissionId);
    const icon = document.getElementById('icon-' + commissionId);

    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
    } else {
        detailsRow.style.display = 'none';
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
    }
}
</script>
@endpush
@endsection

