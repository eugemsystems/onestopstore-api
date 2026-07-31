@extends('admin.layout')

@section('title', 'Late Orders — Admin Panel')

@section('content')

{{-- Page Header --}}
<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="admin-page-icon" style="background:linear-gradient(135deg,#b91c1c,#7f1d1d)">
            <i class="bi bi-exclamation-triangle-fill text-white"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Late Orders</h2>
            <p class="text-muted mb-0 small">Paid orders with at least one item past its ETA — send apology emails</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-danger fs-6 px-3 py-2">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}</span>
        <a href="{{ route('admin.late-orders.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i>Settings
        </a>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Search --}}
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.late-orders.index') }}">
            <div class="input-group" style="max-width:480px">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0 ps-0"
                       placeholder="Order #, customer name or email…"
                       value="{{ $search }}">
                <button type="submit" class="btn btn-primary px-3">Search</button>
                @if($search)
                    <a href="{{ route('admin.late-orders.index') }}" class="btn btn-outline-secondary px-3">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        @if($orders->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
                <p class="mt-3 mb-0 fs-5 fw-semibold">No late orders found</p>
                <p class="small">All active paid orders are within their ETAs. Great work!</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-center">Overdue Items</th>
                        <th>Last Apology Sent</th>
                        <th>Next Eligible</th>
                        <th class="text-center">Sent Count</th>
                        <th class="pe-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    @php
                        $apology      = $order->apologyEmail;
                        $overdueCount = $overdueCounts[$order->id] ?? 0;
                        $canSend      = !$apology || $apology->canSendNow();
                        $daysUntil    = $apology ? $apology->daysUntilNextSend() : 0;
                    @endphp
                    <tr>
                        {{-- Order number --}}
                        <td class="ps-3">
                            <a href="{{ route('admin.orders.show', $order->order_number) }}"
                               class="fw-semibold text-decoration-none text-primary"
                               target="_blank">
                                #{{ $order->order_number }}
                            </a>
                            <div class="small text-muted">{{ $order->created_at->format('d M Y') }}</div>
                        </td>

                        {{-- Customer --}}
                        <td>
                            <div class="fw-semibold">{{ $order->consumer->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $order->consumer->email ?? '' }}</div>
                        </td>

                        {{-- Order status --}}
                        <td>
                            <span class="badge rounded-pill bg-secondary text-white px-2">
                                {{ $order->order_status->name ?? '—' }}
                            </span>
                        </td>

                        {{-- Overdue item count --}}
                        <td class="text-center">
                            <span class="badge bg-danger rounded-pill px-2 py-1 fs-6">
                                {{ $overdueCount }}
                            </span>
                        </td>

                        {{-- Last sent --}}
                        <td>
                            @if($apology && $apology->last_sent_at)
                                <div class="small fw-semibold">{{ $apology->last_sent_at->format('d M Y H:i') }}</div>
                                <div class="small text-muted">{{ $apology->last_sent_at->diffForHumans() }}</div>
                            @else
                                <span class="text-muted small">Never sent</span>
                            @endif
                        </td>

                        {{-- Next eligible --}}
                        <td>
                            @if(!$apology || !$apology->last_sent_at)
                                <span class="badge bg-success rounded-pill px-2">Ready now</span>
                            @elseif($canSend)
                                <span class="badge bg-success rounded-pill px-2">Ready now</span>
                            @else
                                <span class="badge bg-warning text-dark rounded-pill px-2">
                                    In {{ $daysUntil }} {{ Str::plural('day', $daysUntil) }}
                                </span>
                                @if($apology->next_send_at)
                                    <div class="small text-muted">{{ $apology->next_send_at->format('d M Y') }}</div>
                                @endif
                            @endif
                        </td>

                        {{-- Sent count --}}
                        <td class="text-center">
                            @if($apology && $apology->sent_count > 0)
                                <span class="badge bg-info text-dark rounded-pill px-2">{{ $apology->sent_count }}×</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Action --}}
                        <td class="pe-3 text-end">
                            @if($canSend)
                                <button type="button"
                                        class="btn btn-sm btn-danger send-apology-btn"
                                        data-order-id="{{ $order->id }}"
                                        data-order-number="{{ $order->order_number }}"
                                        data-customer="{{ $order->consumer->name ?? 'the customer' }}"
                                        data-overdue="{{ $overdueCount }}">
                                    <i class="bi bi-envelope-exclamation-fill me-1"></i>Send Apology
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cooldown active — wait {{ $daysUntil }} more {{ Str::plural('day', $daysUntil) }}">
                                    <i class="bi bi-clock me-1"></i>Cooling down
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
        <div class="px-3 py-3 border-top">
            {{ $orders->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

{{-- Hidden forms (one per order) for POST --}}
@foreach($orders as $order)
<form id="apology-form-{{ $order->id }}"
      method="POST"
      action="{{ route('admin.late-orders.send-apology', $order->id) }}"
      style="display:none">
    @csrf
</form>
@endforeach

@endsection

@push('scripts')
<script>
document.querySelectorAll('.send-apology-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const orderId      = this.dataset.orderId;
        const orderNumber  = this.dataset.orderNumber;
        const customer     = this.dataset.customer;
        const overdueCount = this.dataset.overdue;

        Swal.fire({
            title: 'Send Apology Email?',
            html: `Send an apology email to <strong>${customer}</strong> for order <strong>#${orderNumber}</strong>?<br>
                   <span class="text-danger fw-semibold">${overdueCount} overdue item${overdueCount > 1 ? 's' : ''}</span> will be included.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="bi bi-envelope-fill me-1"></i>Yes, Send It',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('apology-form-' + orderId).submit();
            }
        });
    });
});
</script>
@endpush
