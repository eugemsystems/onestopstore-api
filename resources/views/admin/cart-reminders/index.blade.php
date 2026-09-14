@extends('admin.layout')

@section('title', 'Cart Reminders')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">🛒 Cart Reminders</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cart-reminders.settings') }}" class="btn btn-primary">
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
                    <i class="bi bi-cart-x fs-1 text-warning mb-2"></i>
                    <h5 class="text-muted">Users With Items In Cart</h5>
                    <h2 class="display-4">{{ $stats['total_users_with_carts'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-basket fs-1 text-info mb-2"></i>
                    <h5 class="text-muted">Total Cart Items</h5>
                    <h2 class="display-4">{{ $stats['total_carts'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-envelope-check fs-1 text-success mb-2"></i>
                    <h5 class="text-muted">Reminders Sent Today</h5>
                    <h2 class="display-4">{{ $stats['reminders_sent_today'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-week fs-1 text-primary mb-2"></i>
                    <h5 class="text-muted">Reminders Sent This Week</h5>
                    <h2 class="display-4">{{ $stats['reminders_sent_week'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Cart Contents -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Current Cart Contents (by User)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Sub Total</th>
                            <th>Cart Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carts as $consumerId => $cartItems)
                            @php
                                $consumer = $cartItems->first()->consumer ?? null;
                                $cartStartedAt = $cartItems->min('created_at');
                                $rowCount = $cartItems->count();
                            @endphp
                            @foreach($cartItems as $index => $item)
                            <tr>
                                @if($index === 0)
                                <td rowspan="{{ $rowCount }}">
                                    {{ $consumer->name ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $consumer->email ?? '' }}</small>
                                </td>
                                @endif
                                <td>{{ $item->product->name ?? 'N/A' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->sub_total, 2) }}</td>
                                @if($index === 0)
                                <td rowspan="{{ $rowCount }}">{{ \Illuminate\Support\Carbon::parse($cartStartedAt)->diffForHumans() }}</td>
                                @endif
                            </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-cart fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">No items currently in any cart</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($carts->hasPages())
                <div class="mt-3">
                    {{ $carts->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.cart-reminders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
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
                            <th>Customer</th>
                            <th>Reminder #</th>
                            <th>Items</th>
                            <th>Status</th>
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
                                {{ $reminder->user->name ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $reminder->email }}</small>
                            </td>
                            <td>#{{ $reminder->reminder_number }}</td>
                            <td>{{ $reminder->item_count }}</td>
                            <td>
                                @php
                                    $statusClass = match($reminder->status) {
                                        'sent' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'pending' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($reminder->status) }}
                                </span>
                                @if($reminder->status === 'failed' && $reminder->error_message)
                                    <br><small class="text-danger">{{ Str::limit($reminder->error_message, 50) }}</small>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">No reminder emails found</p>
                                <small class="text-muted">Reminder emails will appear here once the system starts processing abandoned carts</small>
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
            <li><strong>First Reminder:</strong> Sent automatically after the configured hours since the cart was started (default: 12 hours)</li>
            <li><strong>Follow-up Reminders:</strong> Sent at the configured spacing until the maximum reminder count is reached (default: every 24 hours, up to 3 reminders)</li>
            <li><strong>Email Tracking:</strong> All sent emails are logged here for your records</li>
        </ul>
        <a href="{{ route('admin.cart-reminders.settings') }}" class="btn btn-sm btn-primary mt-2">
            Configure Timing Settings
        </a>
    </div>
</div>
@endsection
