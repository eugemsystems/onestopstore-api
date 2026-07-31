@extends('admin.layout')

@section('title', 'Refunds - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-arrow-counterclockwise"></i> Refund Requests</h2>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filters Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.refunds.index') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="payment_type" class="form-label">Payment Type</label>
                                <select name="payment_type" id="payment_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="wallet" {{ request('payment_type') == 'wallet' ? 'selected' : '' }}>Wallet</option>
                                    <option value="paypal" {{ request('payment_type') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                    <option value="bank" {{ request('payment_type') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-9">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="Search by order number, customer name, or email..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <a href="{{ route('admin.refunds.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Refunds Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Payment Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($refunds as $refund)
                                    <tr>
                                        <td>
                                            @if($refund->order && $refund->order->order_number)
                                                <a href="{{ route('admin.orders.show', $refund->order->order_number) }}" class="fw-bold">
                                                    #{{ $refund->order->order_number }}
                                                </a>
                                            @else
                                                <span class="fw-bold">#{{ $refund->order_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $refund->user->name ?? 'N/A' }}</td>
                                        <td>{{ $refund->order->currency_symbol ?? '$' }}{{ number_format($refund->amount, 2) }}</td>
                                        <td>{{ Str::limit($refund->reason, 30) }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ strtoupper($refund->payment_type) }}</span>
                                        </td>
                                        <td>
                                            @if($refund->status === 'approved')
                                                <span class="badge bg-success">APPROVED</span>
                                            @elseif($refund->status === 'rejected')
                                                <span class="badge bg-danger">REJECTED</span>
                                            @else
                                                <span class="badge bg-warning text-dark">PENDING</span>
                                            @endif
                                        </td>
                                        <td>{{ $refund->created_at->format('M d, Y H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.refunds.show', $refund->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="text-muted mt-2">No refund requests found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($refunds->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $refunds->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
    transition: all 0.2s ease;
}
</style>
@endsection

