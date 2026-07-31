@extends('admin.layout')
@section('title', 'Won Auctions')

@push('styles')
<style>
.stat-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;text-align:center; }
.stat-card .stat-value { font-size:1.8rem;font-weight:800;line-height:1; }
.stat-card .stat-label { font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-top:4px; }
.badge-paid { background:#ecfdf5;color:#059669;border:1px solid #a7f3d0; }
.badge-unpaid { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }
.badge-banned { background:#fef2f2;color:#991b1b;border:1px solid #fca5a5; }
.badge-pending { background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0; }
.badge-confirmed { background:#ecfdf5;color:#059669;border:1px solid #a7f3d0; }
.badge-ready_for_collection { background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe; }
.badge-out_for_delivery { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa; }
.badge-collected { background:#f0fdf4;color:#16a34a;border:1px solid #86efac; }
.badge-delivered { background:#f0fdf4;color:#15803d;border:1px solid #86efac; }
.item-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:.75rem;transition:box-shadow .2s; }
.item-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-trophy me-2" style="color:#f59e0b"></i>Won Auctions</h4>
        <p class="text-muted mb-0 small">All auctions with winners — track payments, fulfillment, and revenue</p>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Auctions
    </a>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-value" style="color:#0f172a">{{ $stats['total_won'] }}</div>
            <div class="stat-label">Total Won</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-value" style="color:#059669">{{ $stats['total_paid'] }}</div>
            <div class="stat-label">Paid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-value" style="color:#dc2626">{{ $stats['total_unpaid'] }}</div>
            <div class="stat-label">Unpaid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-value" style="color:#7e22ce">${{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Search title, winner..." value="{{ request('search') }}">

            <select name="payment" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
                <option value="">All Payments</option>
                <option value="paid" {{ request('payment')=='paid'?'selected':'' }}>Paid</option>
                <option value="unpaid" {{ request('payment')=='unpaid'?'selected':'' }}>Unpaid</option>
            </select>

            <select name="fulfillment" class="form-select form-select-sm" style="width:170px" onchange="this.form.submit()">
                <option value="">All Fulfillment</option>
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','ready_for_collection'=>'Ready','out_for_delivery'=>'Out for Delivery','collected'=>'Collected','delivered'=>'Delivered'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('fulfillment')==$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>

            <select name="branch" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach(['Harare','Mutare','Bulawayo','Zambia'] as $b)
                    <option value="{{ $b }}" {{ request('branch')==$b?'selected':'' }}>{{ $b }}</option>
                @endforeach
            </select>

            <input type="date" name="from" class="form-control form-control-sm" style="width:140px" value="{{ request('from') }}" placeholder="From" title="From date">
            <input type="date" name="to" class="form-control form-control-sm" style="width:140px" value="{{ request('to') }}" placeholder="To" title="To date">

            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['search','payment','fulfillment','branch','from','to']))
                <a href="{{ route('admin.auctions.won') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Results --}}
@forelse($items as $item)
    <div class="item-card">
        <div class="d-flex align-items-start gap-3">
            {{-- Thumbnail --}}
            <div style="width:64px;height:64px;border-radius:8px;overflow:hidden;flex-shrink:0;background:#f1f5f9;border:1px solid #e2e8f0;">
                @if($item->thumbnail)
                    <img src="{{ $item->thumbnail }}" alt="" style="width:100%;height:100%;object-fit:contain;">
                @else
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🔨</div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <a href="{{ route('admin.auctions.show', $item->id) }}" class="fw-bold text-dark text-decoration-none" style="font-size:.9rem;">
                        {{ Str::limit($item->title, 60) }}
                    </a>
                    <span class="badge bg-light text-muted border" style="font-size:.65rem;">Lot #{{ $item->id }}</span>
                    @if($item->branch)
                        <span class="badge" style="background:#f5f3ff;color:#7e22ce;border:1px solid #ddd6fe;font-size:.65rem;">📍 {{ $item->branch }}</span>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.8rem;">
                    {{-- Winner --}}
                    <span class="text-muted">
                        <i class="bi bi-person me-1"></i>{{ $item->winner->name ?? '—' }}
                        <span class="text-muted">({{ $item->winner->email ?? '' }})</span>
                    </span>

                    {{-- Won date --}}
                    <span class="text-muted">
                        <i class="bi bi-calendar me-1"></i>{{ $item->ends_at ? \Carbon\Carbon::parse($item->ends_at)->format('d M Y H:i') : '—' }}
                    </span>
                </div>
            </div>

            {{-- Amounts --}}
            <div class="text-end flex-shrink-0" style="min-width:120px;">
                <div style="font-size:1.1rem;font-weight:800;color:#0f172a;">${{ number_format($item->winner_bid, 2) }}</div>
                @if($item->deposit_paid > 0)
                    <div style="font-size:.7rem;color:#64748b;">Deposit: ${{ number_format($item->deposit_paid, 2) }}</div>
                @endif
                @if($item->payment_amount > 0)
                    <div style="font-size:.7rem;color:#059669;">Paid: ${{ number_format($item->payment_amount, 2) }}</div>
                @endif
            </div>
        </div>

        {{-- Status badges --}}
        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
            {{-- Payment status --}}
            @if($item->is_paid)
                <span class="badge badge-paid" style="font-size:.7rem;border-radius:20px;padding:3px 10px;">✓ Paid</span>
            @else
                <span class="badge badge-unpaid" style="font-size:.7rem;border-radius:20px;padding:3px 10px;">✗ Unpaid</span>
            @endif

            {{-- Fulfillment status --}}
            @if($item->fulfillment_status)
                <span class="badge badge-{{ $item->fulfillment_status }}" style="font-size:.7rem;border-radius:20px;padding:3px 10px;">
                    {{ ucwords(str_replace('_', ' ', $item->fulfillment_status)) }}
                </span>
            @endif

            {{-- Ban status --}}
            @if($item->is_banned)
                <span class="badge badge-banned" style="font-size:.7rem;border-radius:20px;padding:3px 10px;">🚫 Banned</span>
            @endif

            {{-- Bid count --}}
            <span class="badge bg-light text-muted border" style="font-size:.65rem;">{{ $item->bid_count }} bids</span>

            {{-- Actions --}}
            <div class="ms-auto d-flex gap-1">
                <a href="{{ route('admin.auctions.show', $item->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;padding:2px 8px;">View</a>
                @if(!$item->is_paid)
                    <form method="POST" action="{{ route('admin.auctions.mark-paid', $item->id) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="fulfillment_method" value="collection">
                        <button type="submit" class="btn btn-sm btn-outline-success" style="font-size:.72rem;padding:2px 8px;" data-swal-confirm="Mark as paid (cash/office)?">Mark Paid</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5">
        <div style="font-size:3rem;opacity:.3;margin-bottom:12px;">🏆</div>
        <h5 class="text-muted">No won auctions found</h5>
        <p class="text-muted small">Adjust your filters or check back after auctions end.</p>
    </div>
@endforelse

{{-- Pagination --}}
@if($items->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links() }}
    </div>
@endif
@endsection
