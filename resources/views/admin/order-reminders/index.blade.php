@extends('admin.layout')

@section('title', 'Order Reminders')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">📧 Order Reminders</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.order-reminders.settings') }}" class="btn btn-primary">
                <i class="bi bi-gear me-2"></i>Settings
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-envelope-check fs-1 text-success mb-2"></i>
                    <h5 class="text-muted">Today's Reminders</h5>
                    <h2 class="display-4">{{ $stats['today_reminders'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-primary mb-2"></i>
                    <h5 class="text-muted">Success Rate</h5>
                    <h2 class="display-4">{{ $stats['success_rate'] }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle fs-1 text-danger mb-2"></i>
                    <h5 class="text-muted">Failed Emails</h5>
                    <h2 class="display-4">{{ $stats['total_failed'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-trash fs-1 text-warning mb-2"></i>
                    <h5 class="text-muted">Auto-Cancelled Today</h5>
                    <h2 class="display-4">{{ $stats['today_cancelled'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.order-reminders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="reminder_type" class="form-label">Type</label>
                    <select class="form-select" id="reminder_type" name="reminder_type">
                        <option value="all" {{ request('reminder_type') === 'all' ? 'selected' : '' }}>All Types</option>
                        <option value="first" {{ request('reminder_type') === 'first' ? 'selected' : '' }}>First Reminder</option>
                        <option value="second" {{ request('reminder_type') === 'second' ? 'selected' : '' }}>Second Reminder</option>
                        <option value="cancellation" {{ request('reminder_type') === 'cancellation' ? 'selected' : '' }}>Cancellation</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reminders Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Reminder Emails Log</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Reminder Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reminders as $reminder)
                        <tr>
                            <td>
                                <small>
                                    {{ $reminder->sent_at ? $reminder->sent_at->format('M d, Y H:i') : $reminder->created_at->format('M d, Y H:i') }}
                                </small>
                            </td>
                            <td>
                                @if($reminder->order)
                                    <a href="{{ route('admin.orders.show', $reminder->order_number) }}" target="_blank">
                                        #{{ $reminder->order_number }}
                                    </a>
                                @else
                                    #{{ $reminder->order_number }}
                                @endif
                            </td>
                            <td>
                                {{ $reminder->user->name ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $reminder->email }}</small>
                            </td>
                            <td>
                                @php
                                    $typeIcon = match($reminder->reminder_type) {
                                        'first' => 'bi-envelope text-info',
                                        'second' => 'bi-envelope-exclamation text-warning',
                                        'cancellation' => 'bi-x-circle text-danger',
                                        default => 'bi-envelope'
                                    };
                                @endphp
                                <i class="{{ $typeIcon }} me-1"></i>
                                {{ $reminder->reminder_type_label }}
                            </td>
                            <td>
                                <span class="badge {{ $reminder->status_badge_class }}">
                                    {{ ucfirst($reminder->status) }}
                                </span>
                                @if($reminder->status === 'failed' && $reminder->error_message)
                                    <br><small class="text-danger">{{ Str::limit($reminder->error_message, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($reminder->status === 'failed')
                                    <form action="{{ route('admin.order-reminders.resend', $reminder->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" title="Resend Email">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($reminder->order)
                                    <a href="{{ route('admin.orders.show', $reminder->order_number) }}"
                                       class="btn btn-sm btn-info"
                                       title="View Order"
                                       target="_blank">
                                        <i class="bi bi-box-seam"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">No reminder emails found</p>
                                <small class="text-muted">Reminder emails will appear here once the system starts processing pending orders</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($reminders->hasPages())
                <div class="mt-3">
                    {{ $reminders->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-4">
        <h5><i class="bi bi-info-circle me-2"></i>How It Works</h5>
        <ul class="mb-0">
            <li><strong>First Reminder:</strong> Sent automatically after the configured hours (default: 12 hours)</li>
            <li><strong>Second Reminder:</strong> Final reminder sent before cancellation (default: 24 hours)</li>
            <li><strong>Auto-Cancellation:</strong> Orders are automatically cancelled if still pending (default: 72 hours)</li>
            <li><strong>Email Tracking:</strong> All sent emails are logged here for your records</li>
        </ul>
        <a href="{{ route('admin.order-reminders.settings') }}" class="btn btn-sm btn-primary mt-2">
            Configure Timing Settings
        </a>
    </div>
</div>
@endsection

