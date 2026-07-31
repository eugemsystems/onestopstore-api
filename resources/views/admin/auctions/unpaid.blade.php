@extends('admin.layout')
@section('title', 'Unpaid Won Auctions')

@push('styles')
<style>
.item-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:.75rem;transition:box-shadow .15s; }
.item-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.item-card.overdue { border-color:#fca5a5;background:#fff5f5; }
.badge-pending        { background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0; }
.badge-reminder1_sent { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa; }
.badge-reminder2_sent { background:#fef2f2;color:#b91c1c;border:1px solid #fecaca; }
.badge-overdue        { background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-weight:700; }
.countdown { font-size:.78rem;font-weight:700; }
.countdown.warn { color:#b45309; }
.countdown.danger { color:#dc2626; }
.countdown.ok { color:#059669; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-hourglass-split me-2" style="color:#dc2626"></i>Unpaid Won Auctions</h4>
        <p class="text-muted mb-0 small">Won auctions awaiting payment — {{ $items->total() }} item(s)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.auctions.bans') }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-slash-circle me-1"></i>Manage Bans
        </a>
        <a href="{{ route('admin.auctions.fulfilled') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-box-seam me-1"></i>Fulfilled Items
        </a>
        <a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

{{-- Cron tip --}}
<div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Payment reminders are sent automatically by cron every 30 minutes (<code>auction:send-reminders</code>).
    Users are banned hourly if overdue (<code>auction:ban-overdue</code>).
    Settings: Reminder 1 at <strong>{{ $setting->reminder_1_hours }}h</strong>,
    Reminder 2 at <strong>{{ $setting->reminder_2_hours }}h</strong>,
    Deadline: <strong>{{ $setting->hours_to_pay }}h</strong> after win.
    <a href="{{ route('admin.auctions.settings') }}" class="ms-1">Change →</a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending"       {{ request('status')=='pending'       ?'selected':'' }}>Pending (no reminders)</option>
                <option value="reminder1_sent" {{ request('status')=='reminder1_sent'?'selected':'' }}>Reminder 1 Sent</option>
                <option value="reminder2_sent" {{ request('status')=='reminder2_sent'?'selected':'' }}>Reminder 2 Sent</option>
                <option value="overdue"        {{ request('status')=='overdue'       ?'selected':'' }}>Overdue</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width:200px"
                   placeholder="Winner or auction…" value="{{ request('search') }}">
            <button class="btn btn-sm btn-primary">Filter</button>
            @if(request()->hasAny(['status','search']))
                <a href="{{ route('admin.auctions.unpaid') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@forelse($items as $item)
    @php
        $wonAt    = $item->ends_at;
        $deadline = $item->payment_deadline ?? ($wonAt ? $wonAt->copy()->addHours($setting->hours_to_pay ?? 48) : null);
        $isOverdue = $deadline && now()->isAfter($deadline);
        $hoursLeft = $deadline ? max(0, now()->diffInHours($deadline, false)) : null;
        $reminderStatus =
            ($item->reminder_2_sent_at ? 'reminder2_sent' :
            ($item->reminder_1_sent_at ? 'reminder1_sent' : 'pending'));
        $badgeClass = $isOverdue ? 'overdue' : $reminderStatus;
        $badgeLabel = match($badgeClass) {
            'overdue'        => '🚨 Overdue',
            'reminder2_sent' => '📧 Reminder 2 Sent',
            'reminder1_sent' => '📧 Reminder 1 Sent',
            default          => '⏳ Pending',
        };
    @endphp
    <div class="item-card {{ $isOverdue ? 'overdue' : '' }}">
        <div class="row g-3 align-items-center">

            {{-- Auction info --}}
            <div class="col-md-3">
                <div class="fw-bold"><a href="{{ route('admin.auctions.show', $item) }}">{{ $item->title }}</a></div>
                <div class="text-muted small">Lot #{{ $item->id }}</div>
                <div class="mt-1 small text-muted">Won: {{ $item->ends_at?->format('d M Y, H:i') ?? '—' }}</div>
            </div>

            {{-- Winner --}}
            <div class="col-md-2">
                <div class="small fw-bold"><i class="bi bi-person me-1"></i>{{ $item->winner->name ?? '—' }}</div>
                <div class="small text-muted">{{ $item->winner->email ?? '' }}</div>
            </div>

            {{-- Amount --}}
            <div class="col-md-1">
                <div class="small text-muted">Winning Bid</div>
                <div class="fw-bold">${{ number_format($item->winner_bid, 2) }}</div>
            </div>

            {{-- Deadline + countdown --}}
            <div class="col-md-2">
                @if($deadline)
                    <div class="small text-muted">Deadline</div>
                    <div class="small fw-bold">{{ $deadline->format('d M Y, H:i') }}</div>
                    @if($isOverdue)
                        <div class="countdown danger">🚨 OVERDUE</div>
                    @else
                        <div class="countdown {{ $hoursLeft <= 6 ? 'danger' : ($hoursLeft <= 12 ? 'warn' : 'ok') }}">
                            ⏱ {{ $hoursLeft }}h remaining
                        </div>
                    @endif
                @else
                    <div class="small text-muted">—</div>
                @endif
            </div>

            {{-- Reminder status --}}
            <div class="col-md-2">
                <span class="badge badge-{{ $badgeClass }} px-2 py-1" style="border-radius:20px;border-style:solid;border-width:1px;font-size:.78rem">
                    {{ $badgeLabel }}
                </span>
                @if($item->reminder_1_sent_at)
                    <div class="small text-muted mt-1">R1: {{ $item->reminder_1_sent_at->format('d M H:i') }}</div>
                @endif
                @if($item->reminder_2_sent_at)
                    <div class="small text-muted">R2: {{ $item->reminder_2_sent_at->format('d M H:i') }}</div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="col-md-2 text-end">
                <form method="POST" action="{{ route('admin.auctions.send-reminder', $item) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning"
                        data-swal-confirm="Send a payment reminder email to {{ $item->winner->name ?? 'winner' }} now?">
                        <i class="bi bi-envelope me-1"></i>Send Reminder
                    </button>
                </form>
                <a href="{{ route('admin.auctions.show', $item) }}" class="btn btn-sm btn-outline-secondary mt-1">
                    <i class="bi bi-eye"></i>
                </a>
            </div>

        </div>
    </div>
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-check-circle" style="font-size:2.5rem;color:#22c55e;opacity:.6"></i>
        <p class="mt-2">No unpaid won auctions found. All winners have paid! 🎉</p>
    </div>
@endforelse

{{ $items->withQueryString()->links() }}
@endsection
