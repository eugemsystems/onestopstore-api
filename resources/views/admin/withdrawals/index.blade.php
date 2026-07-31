@extends('admin.layout')

@section('title', 'Withdrawal Requests')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-wallet2"></i> Withdrawal Requests
                    </h2>
                    <p class="text-muted">Manage vendor withdrawal requests</p>
                </div>
                <div>
                    <a href="{{ route('admin.withdrawals.export', request()->all()) }}" class="btn btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Pending Amount</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_pending'], 2) }}</h3>
                    <small>{{ $stats['pending_count'] }} request(s)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Approved Amount</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_approved'], 2) }}</h3>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Rejected Amount</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_rejected'], 2) }}</h3>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Processed</h6>
                    <h3 class="mb-0">${{ number_format($stats['total_approved'] + $stats['total_rejected'], 2) }}</h3>
                    <small>Approved + Rejected</small>
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
            <form method="GET" action="{{ route('admin.withdrawals.index') }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }} ({{ $vendor->store->store_name ?? 'N/A' }})
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
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
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
                            <th>ID</th>
                            <th>Vendor</th>
                            <th>Store</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Type</th>
                            <th>Requested Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $withdrawal)
                        <tr>
                            <td><strong>#{{ $withdrawal->id }}</strong></td>
                            <td>{{ $withdrawal->user->name ?? 'N/A' }}</td>
                            <td>{{ $withdrawal->user->store->store_name ?? 'N/A' }}</td>
                            <td><strong>${{ number_format($withdrawal->amount, 2) }}</strong></td>
                            <td>
                                @if($withdrawal->status == 'pending')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                @elseif($withdrawal->status == 'approved')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Approved
                                    </span>
                                @elseif($withdrawal->status == 'rejected')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Rejected
                                    </span>
                                @endif
                            </td>
                            <td>{{ $withdrawal->payment_type ?? 'N/A' }}</td>
                            <td>{{ $withdrawal->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($withdrawal->status == 'pending')
                                        <button class="btn btn-success" onclick="approveWithdrawal({{ $withdrawal->id }})">
                                            <i class="bi bi-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="rejectWithdrawal({{ $withdrawal->id }})">
                                            <i class="bi bi-x"></i> Reject
                                        </button>
                                    @else
                                        <button class="btn btn-outline-secondary" onclick="viewDetails({{ $withdrawal->id }})">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No withdrawal requests found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveWithdrawal(id) {
    Swal.fire({
        title: 'Approve Withdrawal?',
        html: `
            <div class="text-start">
                <p>Approve this withdrawal request?</p>
                <div class="mb-3">
                    <label class="form-label">Payment Reference (Optional)</label>
                    <input type="text" id="payment_reference" class="form-control" placeholder="e.g., TXN123456">
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea id="admin_notes" class="form-control" rows="2" placeholder="Internal notes..."></textarea>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check"></i> Approve & Process',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            const payment_reference = document.getElementById('payment_reference').value;
            const admin_notes = document.getElementById('admin_notes').value;

            return fetch(`/admin/withdrawals/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',  // Request JSON response
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payment_reference: payment_reference,
                    admin_notes: admin_notes
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Approval failed');
                    });
                }
                // Try to parse as JSON, if it fails, treat as success anyway
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // If not JSON, assume success (redirect page)
                        return { success: true };
                    }
                });
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showSuccess('Approved!', 'Withdrawal has been approved successfully').then(() => {
                window.location.reload();
            });
        }
    });
}

function rejectWithdrawal(id) {
    Swal.fire({
        title: 'Reject Withdrawal?',
        html: `
            <div class="text-start">
                <p>Please provide a reason for rejecting this withdrawal request:</p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejection_reason" class="form-control" rows="4" placeholder="e.g., Insufficient documentation, Invalid bank details..." required></textarea>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-x"></i> Reject Request',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            const rejection_reason = document.getElementById('rejection_reason').value.trim();

            if (!rejection_reason) {
                Swal.showValidationMessage('Rejection reason is required');
                return false;
            }

            return fetch(`/admin/withdrawals/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    rejection_reason: rejection_reason
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('Rejection failed');
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showSuccess('Rejected!', 'Withdrawal has been rejected').then(() => {
                window.location.reload();
            });
        }
    });
}

function viewDetails(id) {
    const loading = showLoadingToast('Loading details...');

    fetch(`/admin/withdrawals/${id}`)
        .then(response => response.json())
        .then(data => {
            loading.close();
            if (data.success) {
                const w = data.withdrawal;
                Swal.fire({
                    title: 'Withdrawal Details',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${w.id}</p>
                            <p><strong>Amount:</strong> $${w.amount}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${w.status === 'approved' ? 'success' : w.status === 'rejected' ? 'danger' : 'warning'}">${w.status}</span></p>
                            <p><strong>Payment Type:</strong> ${w.payment_type || 'N/A'}</p>
                            <p><strong>Message:</strong> ${w.message || 'N/A'}</p>
                            ${w.payment_reference ? `<p><strong>Reference:</strong> ${w.payment_reference}</p>` : ''}
                            ${w.rejection_reason ? `<p><strong>Rejection Reason:</strong> ${w.rejection_reason}</p>` : ''}
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonColor: '#667eea'
                });
            }
        })
        .catch(error => {
            loading.close();
            showError('Failed!', 'Could not load withdrawal details');
        });
}
</script>
@endpush
@endsection

