@extends('admin.layout')
@section('title', 'Auction Detail — ' . $auction->title)

@push('styles')
<style>
/* ══════════════════════════════════════════════
   AUCTION DETAIL — PREMIUM ADMIN VIEW
══════════════════════════════════════════════ */
.auc-hero {
    background: linear-gradient(135deg,#0f0c29,#302b63,#24243e);
    border-radius: 18px; padding: 28px 32px; margin-bottom: 28px;
    position: relative; overflow: hidden;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;
    flex-wrap: wrap;
}
.auc-hero::before {
    content:'🔨'; position:absolute; right:40px; top:50%; transform:translateY(-50%);
    font-size:6rem; opacity:.07; pointer-events:none;
}
.auc-hero-icon {
    width:56px; height:56px; border-radius:16px;
    background:linear-gradient(135deg,#7e22ce,#a855f7);
    display:flex; align-items:center; justify-content:center;
    font-size:1.6rem; box-shadow:0 8px 20px rgba(168,85,247,.4); flex-shrink:0;
}
.auc-hero-title { font-size:1.5rem; font-weight:800; color:#fff; margin:0; letter-spacing:-.3px; }
.auc-hero-sub   { color:#94a3b8; font-size:.875rem; margin:4px 0 0; }

/* Stat cards row */
.auc-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:28px; }
.auc-stat {
    background:#fff; border-radius:14px; border:1px solid #f0f4f8;
    box-shadow:0 2px 8px rgba(0,0,0,.04); padding:18px 20px;
    display:flex; align-items:center; gap:14px;
}
.auc-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }

/* Cards */
.auc-card {
    background:#fff; border-radius:16px; border:1px solid #f0f4f8;
    box-shadow:0 4px 16px rgba(0,0,0,.06); margin-bottom:24px; overflow:hidden;
}
.auc-card-header {
    padding:16px 22px; border-bottom:1px solid #f0f4f8;
    font-weight:700; font-size:.9rem; color:#0f172a;
    display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:space-between;
}
.auc-card-header .auc-card-title { display:flex; align-items:center; gap:8px; }
.auc-card-body { padding:22px; }

/* Info grid */
.auc-info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px 24px; }
.auc-info-row { display:flex; flex-direction:column; gap:3px; }
.auc-info-label { font-size:.7rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
.auc-info-value { font-size:.9rem; color:#0f172a; font-weight:600; }

/* Images */
.auc-images { display:flex; gap:10px; flex-wrap:wrap; }
.auc-img { width:90px; height:90px; object-fit:cover; border-radius:10px; border:2px solid #e2e8f0; cursor:pointer; transition:transform .2s; }
.auc-img:hover { transform:scale(1.06); }
.auc-img-ph { width:90px; height:90px; border-radius:10px; background:#f1f5f9; border:2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1.5rem; }

/* Badges */
.status-pill { font-size:.75rem; font-weight:700; padding:5px 13px; border-radius:20px; white-space:nowrap; display:inline-flex; align-items:center; gap:5px; }
.sp-draft     { background:#f1f5f9; color:#64748b; }
.sp-active    { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.sp-ended     { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.sp-cancelled { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.cond-badge   { font-size:.7rem; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; }
.cond-damaged   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.cond-refurbished { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
.cond-asis    { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

/* Winner banner */
.winner-banner {
    background:linear-gradient(135deg,#ecfdf5,#d1fae5);
    border:2px solid #a7f3d0; border-radius:14px;
    padding:20px 24px; display:flex; align-items:center; gap:20px; flex-wrap:wrap;
    margin-bottom:24px;
}
.winner-trophy { font-size:2.5rem; }
.winner-name   { font-size:1.1rem; font-weight:800; color:#059669; }
.winner-detail { font-size:.82rem; color:#000000; }
.winner-bid    { font-size:1.8rem; font-weight:900; color:#000000; margin-left:auto; }

/* Bid history table */
.bid-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.bid-table thead th {
    background:linear-gradient(135deg,#0f0c29,#302b63); color:#94a3b8;
    font-size:.7rem; text-transform:uppercase; letter-spacing:.6px;
    padding:12px 16px; border:none; white-space:nowrap; font-weight:600;
}
.bid-table thead th:first-child { color:#fff; border-radius:8px 0 0 0; }
.bid-table thead th:last-child  { border-radius:0 8px 0 0; }
.bid-table tbody td { padding:13px 16px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
.bid-table tbody tr:last-child td { border-bottom:none; }
.bid-table tbody tr:hover { background:#faf5ff; }
.bid-table tbody tr.winning-row { background:linear-gradient(135deg,#f0fdf4,#ecfdf5) !important; }
.bid-rank { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; }
.rank-1 { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; box-shadow:0 2px 8px rgba(251,191,36,.3); }
.rank-2 { background:#f1f5f9; color:#475569; }
.rank-3 { background:#fef3c7; color:#b45309; }
.rank-n { background:#f8fafc; color:#94a3b8; }
.bid-amount { font-size:1rem; font-weight:800; color:#0f172a; }
.bid-amount.top { background:linear-gradient(135deg,#7e22ce,#a855f7); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.bidder-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#7e22ce,#a855f7); display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:.78rem; font-weight:700; flex-shrink:0; }
.bid-empty { padding:60px 20px; text-align:center; color:#94a3b8; }
.bid-empty-icon { font-size:3rem; display:block; margin-bottom:12px; opacity:.5; }

/* Live countdown */
.live-timer { display:inline-flex; align-items:center; gap:6px; background:#faf5ff; border:1px solid #e9d5ff; border-radius:20px; padding:5px 14px; font-size:.8rem; font-weight:700; color:#7e22ce; }
.live-timer.urgent { background:#fef2f2; border-color:#fecaca; color:#dc2626; animation:pulse 1s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.55} }

/* Action buttons */
.auc-action-btn {
    padding:9px 18px; border-radius:10px; font-weight:700; font-size:.82rem;
    display:inline-flex; align-items:center; gap:6px; text-decoration:none;
    transition:opacity .2s, transform .2s; border:none; cursor:pointer;
}
.auc-action-btn:hover { transform:translateY(-1px); opacity:.9; }
.btn-edit   { background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; box-shadow:0 4px 12px rgba(59,130,246,.3); }
.btn-end    { background:linear-gradient(135deg,#d97706,#f59e0b); color:#fff; box-shadow:0 4px 12px rgba(245,158,11,.3); }
.btn-delete { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; box-shadow:0 4px 12px rgba(239,68,68,.3); }
.btn-back   { background:#f1f5f9; color:#475569; }
.btn-paid   { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,.3); }
</style>
@endpush

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────── --}}
<div class="auc-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="auc-hero-icon">🔨</div>
        <div>
            <h1 class="auc-hero-title">{{ $auction->title }}</h1>
            <p class="auc-hero-sub">
                Lot #{{ $auction->id }}
                @if($auction->branch)
                    &nbsp;·&nbsp; 📍 {{ $auction->branch }}
                @endif
                &nbsp;·&nbsp; Listed by {{ $auction->createdBy->name ?? '—' }}
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('admin.auctions.participants', $auction) }}" class="auc-action-btn" style="background:linear-gradient(135deg,#0f172a,#1e293b);color:#c4b5fd;box-shadow:0 4px 12px rgba(124,58,237,.2);">
            <i class="bi bi-people-fill"></i> Participants
        </a>
        <a href="{{ route('admin.auctions.edit', $auction) }}" class="auc-action-btn btn-edit">
            <i class="bi bi-pencil-fill"></i> Edit
        </a>
        @if($auction->status === 'active')
            <form action="{{ route('admin.auctions.end', $auction) }}" method="POST"
                  data-swal-confirm="End this auction now and set winner?" class="d-inline">
                @csrf
                <button type="submit" class="auc-action-btn btn-end">
                    <i class="bi bi-stop-circle-fill"></i> End Auction
                </button>
            </form>
        @endif
        <form action="{{ route('admin.auctions.destroy', $auction) }}" method="POST"
              data-swal-confirm="Permanently delete this auction?" class="d-inline">
            @csrf @method('DELETE')
            <button type="submit" class="auc-action-btn btn-delete">
                <i class="bi bi-trash-fill"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.auctions.index') }}" class="auc-action-btn btn-back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- ── Flash Messages ───────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:12px">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Stats Row ────────────────────────────────────────────── --}}
<div class="auc-stats">
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#ecfdf5;color:#10b981"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">${{ number_format($auction->current_bid ?? $auction->starting_price, 2) }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Current Bid</div>
        </div>
    </div>
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#faf5ff;color:#8b5cf6"><i class="bi bi-hand-index-thumb"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">{{ $bids->count() }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Total Bids</div>
        </div>
    </div>
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-calendar-event"></i></div>
        <div>
            <div style="font-size:.95rem;font-weight:800;color:#0f172a">{{ $auction->ends_at?->format('d M Y') ?? '—' }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">End Date</div>
        </div>
    </div>
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#fef2f2;color:#dc2626"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">${{ number_format($auction->starting_price, 2) }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Starting Price</div>
        </div>
    </div>
    @if($auction->reserve_price)
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#fffbeb;color:#d97706"><i class="bi bi-shield-lock"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">${{ number_format($auction->reserve_price, 2) }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Reserve Price</div>
        </div>
    </div>
    @endif
    <div class="auc-stat">
        <div class="auc-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-plus-circle"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">${{ number_format($auction->min_bid_increment, 2) }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Min Increment</div>
        </div>
    </div>
</div>

{{-- ── Winner Banner ────────────────────────────────────────── --}}
@if($auction->winner)
@php
    $winnerOrder     = $auction->order_id ? \App\Models\Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)->find($auction->order_id) : null;
    $isPaid          = $winnerOrder && in_array(strtolower((string)$winnerOrder->payment_status), ['paid','complete','completed','success','approved','captured']);
    $winnerDeposit   = \App\Models\AuctionBidDeposit::where('user_id', $auction->winner_id)->where('auction_item_id', $auction->id)->whereNotNull('paid_at')->first();
    $depositPaid     = $winnerDeposit ? (float)$winnerDeposit->amount : 0;
    $deliveryCost    = (float)($auction->delivery_cost ?? 0);
    $winnerBid       = (float)$auction->winner_bid;
    $balanceDue      = max(0, $winnerBid + $deliveryCost - $depositPaid);
    $symbol          = config('app.currency_symbol', '$');
    $auctionSetting  = \App\Models\AuctionSetting::current();
    $settingDelivery = (float)($auctionSetting->delivery_price ?? 0);
    // Check if the winner has an active auction ban
    $winnerBan       = \App\Models\AuctionBan::getActiveBan($auction->winner_id);
    $isBanned        = $winnerBan !== null;
@endphp
<div class="winner-banner" style="{{ $isBanned ? 'background:linear-gradient(135deg,#fff5f5,#fee2e2);border-color:#fca5a5;' : '' }}">
    <div class="winner-trophy">{{ $isBanned ? '🚫' : '🏆' }}</div>
    <div>
        <div class="winner-name" style="{{ $isBanned ? 'color:#dc2626;' : '' }}">{{ $auction->winner->name }}</div>
        <div class="winner-detail">{{ $auction->winner->email }}</div>
        <div class="winner-detail mt-1">Won on {{ $auction->ends_at?->format('d M Y H:i') }}</div>
        @if($isBanned)
            <span class="status-pill mt-2" style="background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;font-size:.7rem;">🚫 Bidder Banned — Non-Payment</span>
        @elseif($isPaid)
            <span class="status-pill sp-active mt-2" style="font-size:.7rem">✅ Payment Confirmed</span>
            @if($auction->fulfillment_method)
                <span class="status-pill sp-draft mt-1" style="font-size:.65rem">
                    {{ $auction->fulfillment_method === 'delivery' ? '🚚 Delivery' : '🏪 Collection' }}
                </span>
            @endif
        @endif
    </div>

    {{-- Payment Breakdown --}}
    <div style="margin:0 auto;background:#fff;border:1px solid #a7f3d0;border-radius:12px;padding:14px 20px;min-width:230px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
        <div style="font-size:.68rem;color:#059669;text-transform:uppercase;letter-spacing:.5px;font-weight:700;margin-bottom:10px;">Payment Breakdown</div>
        <div style="display:flex;justify-content:space-between;font-size:.84rem;color:#475569;padding:4px 0;">
            <span>Winning Bid</span><span style="font-weight:700;color:#0f172a;">{{ $symbol }}{{ number_format($winnerBid, 2) }}</span>
        </div>
        @if($deliveryCost > 0)
        <div style="display:flex;justify-content:space-between;font-size:.84rem;color:#475569;padding:4px 0;">
            <span>Delivery Fee</span><span style="font-weight:700;color:#0f172a;">+ {{ $symbol }}{{ number_format($deliveryCost, 2) }}</span>
        </div>
        @endif
        @if($depositPaid > 0)
        <div style="display:flex;justify-content:space-between;font-size:.84rem;color:#059669;padding:4px 0;border-top:1px dashed #a7f3d0;margin-top:4px;">
            <span>✅ Deposit Applied</span><span style="font-weight:700;">− {{ $symbol }}{{ number_format($depositPaid, 2) }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:900;color:#fff;background:linear-gradient(135deg,#059669,#10b981);border-radius:8px;padding:8px 12px;margin-top:8px;">
            <span>{{ $isPaid ? 'Paid' : 'Balance Due' }}</span>
            <span>{{ $symbol }}{{ number_format($balanceDue, 2) }}</span>
        </div>
    </div>

    <div class="d-flex flex-column align-items-end gap-2">
        @if($isBanned && !$isPaid)
            <span class="auc-action-btn" style="background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;cursor:not-allowed;" title="Cannot mark as paid — winner is banned for non-payment">
                🚫 Bidder Banned
            </span>
            <div style="font-size:.72rem;color:#dc2626;text-align:right;max-width:180px;line-height:1.4;">
                Payment blocked. <a href="{{ route('admin.auctions.bans') }}" style="color:#dc2626;text-decoration:underline;">Manage bans →</a>
            </div>
        @elseif(!$isPaid && $auction->status === 'ended')
            <button type="button" class="auc-action-btn btn-paid" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                <i class="bi bi-check-circle-fill"></i> Mark as Paid
            </button>
        @elseif($isPaid)
            <span class="auc-action-btn" style="background:#059669;color:#fff;cursor:default;">✅ Paid</span>
        @endif
    </div>
</div>

{{-- Mark as Paid Modal --}}
@if(!$isPaid && $auction->status === 'ended')
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(135deg,#064e3b,#059669);border:none;">
        <h5 class="modal-title text-white fw-bold" id="markPaidModalLabel">💵 Record Office Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.auctions.mark-paid', $auction) }}" method="POST">
        @csrf
        <div class="modal-body" style="padding:24px;">
          {{-- Winner --}}
          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
            <div style="font-size:.78rem;color:#64748b;margin-bottom:4px;">Winner</div>
            <div style="font-weight:700;color:#0f172a;">{{ $auction->winner->name }}</div>
            <div style="font-size:.82rem;color:#64748b;">{{ $auction->winner->email }}</div>
          </div>
          {{-- Summary --}}
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:.85rem;">
            <div style="font-weight:700;font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Payment Summary</div>
            <div style="display:flex;justify-content:space-between;padding:4px 0;">
              <span style="color:#64748b;">Winning Bid</span><span style="font-weight:700;">{{ $symbol }}{{ number_format($winnerBid, 2) }}</span>
            </div>
            <div id="deliveryFeeRow" style="display:none;justify-content:space-between;padding:4px 0;">
              <span style="color:#64748b;">Delivery Fee</span><span style="font-weight:700;">+ {{ $symbol }}{{ number_format($settingDelivery, 2) }}</span>
            </div>
            @if($depositPaid > 0)
            <div style="display:flex;justify-content:space-between;padding:4px 0;color:#059669;">
              <span>✅ Deposit Applied</span><span style="font-weight:700;">− {{ $symbol }}{{ number_format($depositPaid, 2) }}</span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;padding:8px 0 2px;border-top:2px solid #e2e8f0;margin-top:6px;font-size:1rem;font-weight:900;">
              <span>Balance to Collect</span>
              <span id="balanceDisplay"
                    data-base="{{ $balanceDue }}"
                    data-delivery="{{ $settingDelivery }}"
                    data-symbol="{{ $symbol }}">{{ $symbol }}{{ number_format($balanceDue, 2) }}</span>
            </div>
          </div>
          {{-- Method --}}
          <div class="mb-3">
            <label class="form-label fw-bold">Fulfillment Method <span class="text-danger">*</span></label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="fulfillment_method" id="fmCollection" value="collection" checked onchange="toggleDelivery()">
                <label class="form-check-label" for="fmCollection">🏪 Collection</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="fulfillment_method" id="fmDelivery" value="delivery" onchange="toggleDelivery()">
                <label class="form-check-label" for="fmDelivery">🚚 Delivery (+ {{ $symbol }}{{ number_format($settingDelivery, 2) }})</label>
              </div>
            </div>
          </div>
          <div id="deliverySection" style="display:none;">
            <div class="mb-3">
              <label class="form-label fw-bold" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
              <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" placeholder="Full delivery address…"></textarea>
            </div>
          </div>
          @if($errors->any())
            <div class="alert alert-danger py-2 small mb-0">{{ $errors->first() }}</div>
          @endif
        </div>
        <div class="modal-footer" style="border-top:1px solid #f0f4f8;">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;font-weight:700;">
            <i class="bi bi-check-circle-fill me-1"></i> Confirm Payment &amp; Send Receipt
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endif



<div class="row g-4">
<div class="col-lg-8">

    {{-- ── Bid History ──────────────────────────────────────── --}}
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title">
                <i class="bi bi-list-ol" style="color:#a855f7"></i> Bid History
            </div>
            <span style="font-size:.78rem;color:#94a3b8;font-weight:400">{{ $bids->count() }} bid{{ $bids->count() !== 1 ? 's' : '' }} total</span>
        </div>
        @if($bids->isEmpty())
            <div class="bid-empty">
                <span class="bid-empty-icon">🔕</span>
                <p>No bids have been placed yet.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="bid-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bidder</th>
                        <th>Email</th>
                        <th>Amount</th>
                        <th>Placed At</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bids as $i => $bid)
                    @php $rank = $i + 1; @endphp
                    <tr class="{{ $rank === 1 ? 'winning-row' : '' }}">
                        <td>
                            <div class="bid-rank {{ $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-n')) }}">
                                {{ $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : $rank)) }}
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bidder-avatar">{{ strtoupper(substr($bid->user->name ?? '?', 0, 2)) }}</div>
                                <div>
                                    <div style="font-weight:700;color:#0f172a;font-size:.85rem">{{ $bid->user->name ?? 'Unknown' }}</div>
                                    @if($rank === 1)
                                        <div style="font-size:.7rem;color:#059669;font-weight:700">🏆 Winning Bid</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="color:#64748b;font-size:.8rem">{{ $bid->user->email ?? '—' }}</td>
                        <td>
                            <div class="bid-amount {{ $rank === 1 ? 'top' : '' }}">${{ number_format($bid->amount, 2) }}</div>
                        </td>
                        <td style="color:#64748b;font-size:.8rem;white-space:nowrap">
                            {{ $bid->created_at->format('d M Y') }}<br>
                            <span style="color:#94a3b8">{{ $bid->created_at->format('H:i:s') }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Description ─────────────────────────────────────── --}}
    @if($auction->description)
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title"><i class="bi bi-file-text" style="color:#a855f7"></i> Description</div>
        </div>
        <div class="auc-card-body" style="color:#475569;line-height:1.7;font-size:.9rem">
            {!! $auction->description !!}
        </div>
    </div>
    @endif

</div>{{-- /col-lg-8 --}}

<div class="col-lg-4">

    {{-- ── Auction Details ──────────────────────────────────── --}}
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title"><i class="bi bi-info-circle" style="color:#a855f7"></i> Auction Details</div>
        </div>
        <div class="auc-card-body">
            <div class="auc-info-grid">
                <div class="auc-info-row">
                    <span class="auc-info-label">Status</span>
                    @php $spClass = ['draft'=>'sp-draft','active'=>'sp-active','ended'=>'sp-ended','cancelled'=>'sp-cancelled']; @endphp
                    <span class="status-pill {{ $spClass[$auction->status] ?? 'sp-draft' }}">
                        <i class="bi {{ $auction->status==='active' ? 'bi-circle-fill' : 'bi-circle' }}" style="font-size:.5rem"></i>
                        {{ ucfirst($auction->status) }}
                    </span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">Condition</span>
                    @php
                        $cc = [
                            'damaged'             => 'cond-damaged',
                            'boxed-damaged'       => 'cond-damaged',
                            'no-box'              => 'cond-damaged',
                            'returned'            => 'cond-damaged',
                            'dented'              => 'cond-damaged',
                            'missing-accessories' => 'cond-damaged',
                            'refurbished'         => 'cond-refurbished',
                            'as-is'               => 'cond-asis',
                        ];
                        $cl = [
                            'damaged'             => 'Damaged',
                            'boxed-damaged'       => 'Damaged Box',
                            'no-box'              => 'No Box',
                            'returned'            => 'Returned',
                            'dented'              => 'Dented',
                            'missing-accessories' => 'Missing Accessories',
                            'refurbished'         => 'Refurbished',
                            'as-is'               => 'As-Is',
                        ];
                    @endphp
                    <span class="cond-badge {{ $cc[$auction->condition] ?? 'cond-asis' }}">{{ $cl[$auction->condition] ?? ucfirst(str_replace('-', ' ', $auction->condition)) }}</span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">Branch</span>
                    <span class="auc-info-value">📍 {{ $auction->branch ?: '—' }}</span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">Start Date</span>
                    <span class="auc-info-value">{{ $auction->starts_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">End Date</span>
                    <span class="auc-info-value">{{ $auction->ends_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
                @if($auction->isActive())
                <div class="auc-info-row" style="grid-column:1/-1">
                    <span class="auc-info-label">Time Remaining</span>
                    <span class="live-timer" id="countdown" data-ends="{{ $auction->ends_at->toIso8601String() }}">
                        <i class="bi bi-clock"></i> <span id="cd-text">–</span>
                    </span>
                </div>
                @endif
                <div class="auc-info-row">
                    <span class="auc-info-label">Auto Extend</span>
                    <span class="auc-info-value">{{ $auction->auto_extend_minutes > 0 ? $auction->auto_extend_minutes . ' min' : 'Disabled' }}</span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">Created By</span>
                    <span class="auc-info-value">{{ $auction->createdBy->name ?? '—' }}</span>
                </div>
                <div class="auc-info-row">
                    <span class="auc-info-label">Created</span>
                    <span class="auc-info-value">{{ $auction->created_at->format('d M Y') }}</span>
                </div>
                @if($auction->order_id)
                <div class="auc-info-row" style="grid-column:1/-1">
                    <span class="auc-info-label">Linked Order</span>
                    <span class="auc-info-value">#{{ $auction->order_id }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Images ───────────────────────────────────────────── --}}
    @if(!empty($auction->images))
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title"><i class="bi bi-images" style="color:#a855f7"></i> Photos ({{ count($auction->images) }})</div>
        </div>
        <div class="auc-card-body">
            <div class="auc-images">
                @foreach($auction->images as $img)
                    <a href="{{ $img }}" target="_blank">
                        <img src="{{ $img }}" alt="" class="auc-img">
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title"><i class="bi bi-images" style="color:#a855f7"></i> Photos</div>
        </div>
        <div class="auc-card-body text-center text-muted py-4">
            <div class="auc-img-ph mx-auto mb-2"><i class="bi bi-image"></i></div>
            No photos uploaded
        </div>
    </div>
    @endif

    {{-- ── Linked Product ───────────────────────────────────── --}}
    @if($auction->product)
    <div class="auc-card">
        <div class="auc-card-header">
            <div class="auc-card-title"><i class="bi bi-box-seam" style="color:#a855f7"></i> Linked Product</div>
        </div>
        <div class="auc-card-body">
            <div class="fw-bold mb-1">{{ $auction->product->name }}</div>
            <div class="text-muted small mb-2">Slug: {{ $auction->product->slug }}</div>
            <a href="{{ url('/en/products/' . $auction->product->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Product
            </a>
        </div>
    </div>
    @endif

</div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}

@endsection

@push('scripts')
<script>
const cdEl = document.getElementById('countdown');
if (cdEl) {
    const ends = new Date(cdEl.dataset.ends);
    const text  = document.getElementById('cd-text');
    const tick = () => {
        const diff = Math.max(0, Math.floor((ends - Date.now()) / 1000));
        if (diff === 0) { text.textContent = 'Ended'; cdEl.classList.remove('urgent'); return; }
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        text.textContent = h > 0
            ? `${h}h ${String(m).padStart(2,'0')}m`
            : `${m}m ${String(s).padStart(2,'0')}s`;
        if (diff < 300) cdEl.classList.add('urgent');
    };
    tick();
    setInterval(tick, 1000);
}
</script>
<script>
// Mark as Paid Modal — delivery toggle
function toggleDelivery() {
    const isDelivery = document.getElementById('fmDelivery').checked;
    const section    = document.getElementById('deliverySection');
    const feeRow     = document.getElementById('deliveryFeeRow');
    const addrInput  = document.getElementById('delivery_address');
    const balance    = document.getElementById('balanceDisplay');

    if (section)   section.style.display   = isDelivery ? 'block' : 'none';
    if (feeRow)    feeRow.style.display    = isDelivery ? 'flex'  : 'none';
    if (addrInput) addrInput.required      = isDelivery;

    // Update balance display dynamically
    if (balance) {
        const base     = parseFloat(balance.dataset.base     || balance.dataset.base);
        const delivery = parseFloat(balance.dataset.delivery || '0');
        // We parse values from PHP-rendered data attributes (set below)
        const baseVal     = parseFloat(balance.getAttribute('data-base')     || '0');
        const deliveryVal = parseFloat(balance.getAttribute('data-delivery') || '0');
        const symbol      = balance.getAttribute('data-symbol') || '$';
        const newBalance  = isDelivery ? baseVal + deliveryVal : baseVal;
        balance.textContent = symbol + newBalance.toFixed(2);
    }
}
</script>
@endpush

