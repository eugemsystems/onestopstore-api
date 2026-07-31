@extends('admin.layout')
@section('title', 'Auction Bidder Bans')

@push('styles')
<style>
.item-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:.75rem; }
.item-card.active-ban { border-color:#fca5a5;background:#fff8f8; }
.item-card.lifted { border-color:#bbf7d0;background:#f0fdf4; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-slash-circle me-2" style="color:#dc2626"></i>Auction Bidder Bans</h4>
        <p class="text-muted mb-0 small">Users banned for non-payment of won auctions</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.auctions.unpaid') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-hourglass-split me-1"></i>Unpaid Auctions
        </a>
        <a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="active" {{ request('status')=='active'?'selected':'' }}>Active Bans</option>
                <option value="lifted" {{ request('status')=='lifted'?'selected':'' }}>Lifted Bans</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width:200px"
                   placeholder="User name or email…" value="{{ request('search') }}">
            <button class="btn btn-sm btn-primary">Filter</button>
            @if(request()->hasAny(['status','search']))
                <a href="{{ route('admin.auctions.bans') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
            <span class="ms-auto text-muted small">{{ $bans->total() }} record(s)</span>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@forelse($bans as $ban)
    <div class="item-card {{ $ban->lifted_at ? 'lifted' : 'active-ban' }}">
        <div class="row g-3 align-items-center">

            {{-- User --}}
            <div class="col-md-3">
                <div class="fw-bold">{{ $ban->user->name ?? '—' }}</div>
                <div class="small text-muted">{{ $ban->user->email ?? '' }}</div>
                <div class="mt-1">
                    @if($ban->lifted_at)
                        <span class="badge bg-success">✅ Ban Lifted</span>
                    @else
                        <span class="badge bg-danger">🚫 Banned</span>
                    @endif
                </div>
            </div>

            {{-- Auction --}}
            <div class="col-md-3">
                <div class="small text-muted mb-1">Auction Item</div>
                @if($ban->auctionItem)
                    <div class="fw-bold">
                        <a href="{{ route('admin.auctions.show', $ban->auctionItem) }}">
                            {{ $ban->auctionItem->title }}
                        </a>
                    </div>
                    <div class="small text-muted">
                        Lot #{{ $ban->auction_item_id }} | Winning bid: ${{ number_format($ban->auctionItem->winner_bid, 2) }}
                    </div>
                @else
                    <div class="text-muted small">Auction #{{ $ban->auction_item_id }}</div>
                @endif
            </div>

            {{-- Dates --}}
            <div class="col-md-3">
                <div class="small text-muted">Banned: <strong>{{ $ban->banned_at->format('d M Y, H:i') }}</strong></div>
                @if($ban->lifted_at)
                    <div class="small text-muted">Lifted: <strong>{{ $ban->lifted_at->format('d M Y, H:i') }}</strong></div>
                    @if($ban->liftedBy)
                        <div class="small text-muted">By: {{ $ban->liftedBy->name }}</div>
                    @endif
                    @if($ban->lift_reason)
                        <div class="small mt-1 text-muted fst-italic">"{{ $ban->lift_reason }}"</div>
                    @endif
                @endif
            </div>

            {{-- Action --}}
            <div class="col-md-3 text-end">
                @if(!$ban->lifted_at)
                    <button class="btn btn-sm btn-outline-success"
                        data-bs-toggle="modal" data-bs-target="#liftModal{{ $ban->id }}">
                        <i class="bi bi-unlock me-1"></i>Lift Ban
                    </button>
                @else
                    <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Lift Ban Modal --}}
    @if(!$ban->lifted_at)
    <div class="modal fade" id="liftModal{{ $ban->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-unlock me-2"></i>Lift Ban — {{ $ban->user->name ?? 'User' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.auctions.bans.lift', $ban) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            You are lifting the auction bidding ban for <strong>{{ $ban->user->name ?? 'this user' }}</strong>
                            which was applied for failing to pay for <strong>{{ $ban->auctionItem->title ?? 'Lot #'.$ban->auction_item_id }}</strong>.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for lifting ban <span class="text-danger">*</span></label>
                            <textarea name="lift_reason" class="form-control" rows="3"
                                placeholder="e.g. Customer provided valid proof of payment issue…" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-unlock me-1"></i>Confirm Lift Ban
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-check-circle" style="font-size:2.5rem;color:#22c55e;opacity:.6"></i>
        <p class="mt-2">No bans found.</p>
    </div>
@endforelse

{{ $bans->withQueryString()->links() }}
@endsection
