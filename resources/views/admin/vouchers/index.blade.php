@extends('admin.layout')

@section('title', 'Gift Vouchers Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-gift"></i> Gift Vouchers</h2>
                    <p class="text-muted mb-0">Manage and track all gift vouchers</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card stats-total">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Vouchers</p>
                            <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-gift"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card stats-active">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Active</p>
                            <h3 class="mb-0">{{ number_format($stats['active']) }}</h3>
                            <small class="text-success">${{ number_format($stats['total_value'], 2) }}</small>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card stats-redeemed">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Redeemed</p>
                            <h3 class="mb-0">{{ number_format($stats['redeemed']) }}</h3>
                            <small class="text-success">${{ number_format($stats['redeemed_value'], 2) }}</small>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-check2-square"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card stats-expired">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Expired</p>
                            <h3 class="mb-0">{{ number_format($stats['expired']) }}</h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vouchers.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Code, email, order..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="redeemed" {{ $status === 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Vouchers List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Purchased By</th>
                            <th>Order</th>
                            <th>Created</th>
                            <th>Redeemed</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                        <tr>
                            <td>
                                <code class="voucher-code">{{ $voucher->code }}</code>
                            </td>
                            <td>
                                <strong>{{ $voucher->currency_code }} {{ number_format($voucher->amount, 2) }}</strong>
                            </td>
                            <td>
                                @if($voucher->status === 'active')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Active
                                    </span>
                                @elseif($voucher->status === 'redeemed')
                                    <span class="badge bg-info">
                                        <i class="bi bi-check2-square"></i> Redeemed
                                    </span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($voucher->status) }}</span>
                                @endif

                                @if($voucher->expires_at && $voucher->expires_at < now() && $voucher->status !== 'redeemed')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-clock-history"></i> Expired
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($voucher->purchasedBy)
                                    <div>{{ $voucher->purchasedBy->name }}</div>
                                    <small class="text-muted">{{ $voucher->purchasedBy->email }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($voucher->order)
                                    <a href="{{ route('admin.orders.show', $voucher->order->order_number) }}" class="text-decoration-none">
                                        #{{ $voucher->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $voucher->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                @if($voucher->redeemed_at)
                                    <small>{{ $voucher->redeemed_at->format('M d, Y') }}</small>
                                    @if($voucher->redeemedBy)
                                        <br><small class="text-muted">by {{ $voucher->redeemedBy->name }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($voucher->expires_at)
                                    <small>{{ $voucher->expires_at->format('M d, Y') }}</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.vouchers.show', $voucher->id) }}"
                                   class="btn btn-sm btn-primary"
                                   title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">No vouchers found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vouchers->hasPages())
        <div class="card-footer">
            {{ $vouchers->links() }}
        </div>
        @endif
    </div>
</div>

<style>
.stats-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stats-card .stats-icon {
    font-size: 2.5rem;
    opacity: 0.2;
}

.stats-total .stats-icon { color: #062a6a; }
.stats-active .stats-icon { color: #28a745; }
.stats-redeemed .stats-icon { color: #17a2b8; }
.stats-expired .stats-icon { color: #dc3545; }

.voucher-code {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
</style>
@endsection

