@extends('admin.layout')

@section('title', 'Layby Applications')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Layby Applications</h2>
                    <p class="text-muted">Manage customer layby applications and payments</p>
                </div>
                <div>
                    <a href="{{ route('admin.layby.settings') }}" class="btn btn-outline-primary">
                        <i class="bi bi-gear-fill"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Status Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'all']) }}">
                All Applications
                <span class="badge bg-secondary ms-1">{{ $statusCounts['all'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'pending']) }}">
                Pending
                <span class="badge bg-warning ms-1">{{ $statusCounts['pending'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'approved']) }}">
                Approved
                <span class="badge bg-info ms-1">{{ $statusCounts['approved'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'active' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'active']) }}">
                Active
                <span class="badge bg-primary ms-1">{{ $statusCounts['active'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'completed' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'completed']) }}">
                Completed
                <span class="badge bg-success ms-1">{{ $statusCounts['completed'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}"
               href="{{ route('admin.layby.index', ['status' => 'rejected']) }}">
                Rejected
                <span class="badge bg-danger ms-1">{{ $statusCounts['rejected'] ?? 0 }}</span>
            </a>
        </li>
    </ul>

    <!-- Search Form -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.layby.index') }}" class="row g-3">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="col-md-10">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search by customer name, surname, or email..."
                           value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Application Details</th>
                            <th>Customer</th>
                            <th>Product Details</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $application)
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    <a href="{{ route('admin.layby.show', $application->id) }}">
                                        {{ $application->application_number }}
                                    </a>
                                </div>
                                <b>Application Date: <br></b><small class="text-muted">{{ $application->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div>{{ $application?->user?->name }}</div>
                                <small class="text-muted">{{ $application?->user?->email }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-start">
                                    @if($application->product && $application->product->product_thumbnail)
                                    <img src="{{ $application->product->product_thumbnail->image_url }}"
                                         alt="{{ $application->product_name }}"
                                         style="width: 80px; height: 80px; object-fit: contain; border-radius: 4px;"
                                         class="me-2">
                                    @endif
                                    <div class="flex-grow-1">
                                        <div class="mb-1">{{ $application->product_name }}</div>
                                        @if($application->variation_display_name)
                                        <small class="text-muted d-block">{{ $application->variation_display_name }}</small>
                                        <br>
                                        @endif
                                        <div class="mt-1">
                                            <strong>{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</strong>
                                            <small class="text-muted">{{ $application->currency }}</small>
                                        </div>
                                        <small class="text-muted">{{ $application->duration_months }} months</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar success" role="progressbar"
                                         style="width: {{ $application->getProgressPercentage() }}%"
                                         aria-valuenow="{{ $application->getProgressPercentage() }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ number_format($application->getProgressPercentage(), 0) }}%
                                    </div>
                                </div>
                                <small class="text-muted d-block">
                                    Paid: {{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}
                                </small>
                                <small class="text-muted">
                                    Balance: {{ $application->currency_symbol }}{{ number_format($application->balance_remaining, 2) }}
                                </small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'approved' => 'info',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'secondary',
                                    ];
                                    $color = $statusColors[$application->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ ucfirst($application->status) }}</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.layby.show', $application->id) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($application->status !== 'completed')
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#cancelModal{{ $application->id }}">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No layby applications found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $applications->appends(['status' => $status])->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modals (Outside table to prevent flickering) -->
@foreach($applications as $application)
    @if($application->status !== 'completed')
    <div class="modal fade" id="cancelModal{{ $application->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $application->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.layby.cancel', $application->id) }}">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="cancelModalLabel{{ $application->id }}">
                            <i class="bi bi-x-circle"></i> Cancel Layby Application
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action will cancel the layby application and send an email notification to the customer.
                        </div>

                        <div class="mb-3">
                            <p><strong>Application:</strong> {{ $application->application_number }}</p>
                            <p><strong>Customer:</strong> {{ $application->user->name }}</p>
                            <p><strong>Product:</strong> {{ $application->product_name }}</p>
                        </div>

                        <div class="mb-3">
                            <label for="cancellation_reason{{ $application->id }}" class="form-label">
                                Reason for Cancellation <span class="text-danger">*</span>
                            </label>
                            <textarea
                                class="form-control"
                                id="cancellation_reason{{ $application->id }}"
                                name="cancellation_reason"
                                rows="4"
                                required
                                placeholder="E.g., No payment or deposit received within the required time period."
                            ></textarea>
                            <div class="form-text">
                                This reason will be included in the email sent to the customer.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> Close
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Cancel Layby
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection

