@extends('admin.layout')

@section('title', 'Returns - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-box-arrow-in-left"></i> Return Requests</h2>
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
                    <form method="GET" action="{{ route('admin.returns.index') }}" id="filterForm">
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
                                <label for="preferred_outcome" class="form-label">Preferred Outcome</label>
                                <select name="preferred_outcome" id="preferred_outcome" class="form-select">
                                    <option value="">All Outcomes</option>
                                    <option value="refund" {{ request('preferred_outcome') == 'refund' ? 'selected' : '' }}>Refund</option>
                                    <option value="replacement" {{ request('preferred_outcome') == 'replacement' ? 'selected' : '' }}>Replacement</option>
                                    <option value="exchange" {{ request('preferred_outcome') == 'exchange' ? 'selected' : '' }}>Exchange</option>
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
                                <input type="text" name="search" id="search" class="form-control" placeholder="Search by order number, customer name, product name..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Returns Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Reason</th>
                                    <th>Preferred Outcome</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returns as $return)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $return->order->order_number ?? $return->order_id) }}" class="fw-bold">
                                                #{{ $return->order->order_number ?? $return->order_id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($return->product)
                                                <div class="d-flex align-items-center">
                                                    @if($return->product->product_thumbnail)
                                                        <img src="{{ $return->product->product_thumbnail->image_url }}"
                                                             alt="{{ $return->product->name }}"
                                                             class="me-2"
                                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                    @endif
                                                    <span>{{ Str::limit($return->product->name, 30) }}</span>
                                                </div>
                                            @else
                                                Product #{{ $return->product_id }}
                                            @endif
                                        </td>
                                        <td>{{ $return->user->name ?? 'N/A' }}</td>
                                        <td>{{ Str::limit($return->return_reason, 30) }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ strtoupper($return->preferred_outcome) }}</span>
                                        </td>
                                        <td>
                                            @if($return->status === 'approved')
                                                <span class="badge bg-success">APPROVED</span>
                                            @elseif($return->status === 'rejected')
                                                <span class="badge bg-danger">REJECTED</span>
                                            @else
                                                <span class="badge bg-warning text-dark">PENDING</span>
                                            @endif
                                        </td>
                                        <td>{{ $return->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.returns.show', $return->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="text-muted mt-2">No return requests found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($returns->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $returns->links() }}
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

