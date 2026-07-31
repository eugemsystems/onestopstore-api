@extends('admin.layout')
@section('title', 'Auction Fulfilled Items')

@push('styles')
<style>
.badge-pending          { background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0; }
.badge-ready_for_collection  { background:#ecfdf5;color:#059669;border:1px solid #a7f3d0; }
.badge-out_for_delivery { background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe; }
.badge-collected        { background:#f0fdf4;color:#16a34a;border:1px solid #86efac; }
.badge-delivered        { background:#f0fdf4;color:#15803d;border:1px solid #86efac; }
.badge-collection       { background:#faf5ff;color:#7e22ce;border:1px solid #ddd6fe; }
.badge-delivery         { background:#fff7ed;color:#c2410c;border:1px solid #fed7aa; }
.item-card              { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:.75rem; }
.item-card:hover        { box-shadow:0 4px 16px rgba(0,0,0,.07); }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2" style="color:#7e22ce"></i>Auction Fulfilled Items</h4>
        <p class="text-muted mb-0 small">Won auctions with confirmed payment — manage collection and delivery</p>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Auctions
    </a>
</div>

{{-- Flash alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
        <i class="bi bi-check-circle me-2"></i>{!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="fulfillment_status" class="form-select form-select-sm" style="width:170px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['pending'=>'Pending','ready_for_collection'=>'Ready for Collection','out_for_delivery'=>'Out for Delivery','collected'=>'Collected','delivered'=>'Delivered'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('fulfillment_status')==$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="method" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                <option value="">All Methods</option>
                <option value="collection" {{ request('method')=='collection'?'selected':'' }}>Collection</option>
                <option value="delivery"   {{ request('method')=='delivery'?'selected':'' }}>Delivery</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Winner or auction…" value="{{ request('search') }}">
            <button class="btn btn-sm btn-primary">Filter</button>
            @if(request()->hasAny(['fulfillment_status','method','search']))
                <a href="{{ route('admin.auctions.fulfilled') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
            <span class="ms-auto text-muted small">{{ $items->total() }} item(s)</span>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@forelse($items as $item)
    <div class="item-card">
        <div class="row g-3 align-items-start">

            {{-- Thumbnail + title --}}
            <div class="col-md-1 d-none d-md-block">
                @php $thumb = is_array($item->images) ? ($item->images[0] ?? null) : null; @endphp
                @if($thumb)
                    <img src="{{ $thumb }}" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                @else
                    <div style="width:60px;height:60px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:1.4rem;">🔨</div>
                @endif
            </div>

            {{-- Auction + winner info --}}
            <div class="col-md-3">
                <div class="fw-bold">{{ $item->title }}</div>
                <div class="text-muted small">Lot #{{ $item->id }}</div>
                <div class="mt-1 small"><i class="bi bi-person me-1"></i><strong>{{ $item->winner->name ?? '—' }}</strong></div>
                <div class="small text-muted">{{ $item->winner->email ?? '' }}</div>
                <div class="mt-1 small text-muted">Ended: {{ $item->ends_at?->format('d M Y H:i') ?? '—' }}</div>
            </div>

            {{-- Amounts --}}
            <div class="col-md-2">
                <div class="small text-muted mb-1">Winning Bid</div>
                <div class="fw-bold">${{ number_format($item->winner_bid, 2) }}</div>
                @if($item->delivery_cost > 0)
                    <div class="small text-muted mt-1">+ Delivery: ${{ number_format($item->delivery_cost, 2) }}</div>
                    <div class="small fw-bold text-dark">Total: ${{ number_format((float)$item->winner_bid + (float)$item->delivery_cost, 2) }}</div>
                @endif
            </div>

            {{-- Fulfillment choice --}}
            <div class="col-md-2">
                <div class="small text-muted mb-1">Fulfillment</div>
                <span class="badge badge-{{ $item->fulfillment_method ?? 'collection' }} px-2 py-1" style="border-radius:20px;border-style:solid;border-width:1px;font-size:.78rem">
                    {{ $item->fulfillment_method === 'delivery' ? '🚚 Delivery' : '🏪 Collection' }}
                </span>
                @if($item->delivery_address)
                    <div class="mt-1 small text-secondary" style="max-width:180px;word-break:break-word">
                        <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($item->delivery_address, 80) }}
                    </div>
                @endif
            </div>

            {{-- Fulfillment status + actions --}}
            <div class="col-md-4">
                <form method="POST" action="{{ route('admin.auctions.update-status', $item) }}" id="statusForm{{ $item->id }}">
                    @csrf
                    @method('PATCH')

                    {{-- Fulfillment Status --}}
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1" style="color:#64748b;">Fulfillment Status</label>
                        <select name="fulfillment_status" class="form-select form-select-sm" onchange="document.getElementById('statusForm{{ $item->id }}').submit()">
                            @foreach([
                                'confirmed'            => '✅ Confirmed Payment',
                                'ready_for_collection' => '🏪 Ready for Collection',
                                'out_for_delivery'     => '🚚 Out for Delivery',
                                'collected'            => '📦 Collected',
                                'delivered'            => '✔️ Delivered',
                            ] as $v => $l)
                                <option value="{{ $v }}" {{ ($item->fulfillment_status ?? 'confirmed') === $v ? 'selected' : '' }}>
                                    {{ $l }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Order Status --}}
                    @if($item->order)
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1" style="color:#64748b;">Order Status</label>
                        <select name="order_status_id" class="form-select form-select-sm" onchange="document.getElementById('statusForm{{ $item->id }}').submit()">
                            @foreach(\App\Models\OrderStatus::orderBy('sequence')->get() as $os)
                                <option value="{{ $os->id }}" {{ optional($item->order)->order_status_id == $os->id ? 'selected' : '' }}>
                                    {{ $os->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </form>

                {{-- Order link --}}
                @if($item->order)
                    <div class="small text-muted mt-1">Order: <a href="{{ route('admin.orders.show', $item->order_id) }}" target="_blank">#{{ $item->order->order_number }}</a></div>
                @endif

                {{-- Quick notify action (send email when updating to ready/delivery) --}}
                @if(!in_array($item->fulfillment_status, ['collected','delivered']))
                    <form method="POST" action="{{ route('admin.auctions.mark-ready', $item) }}" class="mt-2">
                        @csrf
                        @if($item->fulfillment_method !== 'delivery')
                            <button type="submit" name="action" value="ready_for_collection"
                                data-swal-confirm="Mark as Ready for Collection and notify the winner via email?"
                                class="btn btn-sm btn-outline-success w-100">
                                <i class="bi bi-bell me-1"></i>Notify: Ready for Collection
                            </button>
                        @else
                            <button type="submit" name="action" value="out_for_delivery"
                                data-swal-confirm="Mark as Out for Delivery and notify the winner via email?"
                                class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-truck me-1"></i>Notify: Out for Delivery
                            </button>
                        @endif
                    </form>
                @else
                    <span class="badge bg-success small mt-1"><i class="bi bi-check-all me-1"></i>Complete</span>
                @endif
            </div>

        </div>
    </div>
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3"></i>
        <p class="mt-2">No paid auction items found. Items appear here once the winner has completed payment.</p>
    </div>
@endforelse

{{ $items->withQueryString()->links() }}
@endsection
