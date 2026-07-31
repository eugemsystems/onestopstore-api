@extends('admin.layout')
@section('title', 'Participants — ' . $auction->title)

@push('styles')
<style>
/* ══════════════════════════════════════════
   AUCTION PARTICIPANTS — PAYMENT HISTORY
══════════════════════════════════════════ */
.ph-hero {
    background: linear-gradient(135deg,#0f0c29,#302b63,#24243e);
    border-radius: 18px; padding: 26px 32px; margin-bottom: 28px;
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 20px; flex-wrap: wrap; position: relative; overflow: hidden;
}
.ph-hero::before {
    content:'👥'; position:absolute; right:36px; top:50%; transform:translateY(-50%);
    font-size:6rem; opacity:.07; pointer-events:none;
}
.ph-hero-title { font-size:1.4rem; font-weight:800; color:#fff; margin:0; letter-spacing:-.3px; }
.ph-hero-sub   { color:#94a3b8; font-size:.875rem; margin:4px 0 0; }

/* Stats */
.ph-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:28px; }
.ph-stat  { background:#fff; border-radius:14px; border:1px solid #f0f4f8; box-shadow:0 2px 8px rgba(0,0,0,.04); padding:18px 20px; display:flex; align-items:center; gap:14px; }
.ph-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }

/* Participant cards */
.par-card {
    background:#fff; border-radius:16px; border:1px solid #f0f4f8;
    box-shadow:0 4px 16px rgba(0,0,0,.06); margin-bottom:22px; overflow:hidden;
}
.par-card.winner-card { border:2px solid #a7f3d0; box-shadow:0 4px 24px rgba(16,185,129,.12); }
.par-card-header {
    padding:16px 22px; border-bottom:1px solid #f0f4f8;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
}
.par-avatar { width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg,#7e22ce,#a855f7); display:flex; align-items:center; justify-content:center; color:#fff; font-size:.95rem; font-weight:800; flex-shrink:0; }
.par-avatar.gold { background:linear-gradient(135deg,#d97706,#f59e0b); }
.par-name  { font-size:1rem; font-weight:800; color:#0f172a; }
.par-email { font-size:.78rem; color:#64748b; }

/* Badges */
.badge-pill { font-size:.7rem; font-weight:700; padding:4px 11px; border-radius:20px; white-space:nowrap; }
.bp-winner   { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.bp-paid     { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.bp-pending  { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
.bp-refunded { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.bp-no-dep   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

/* Payment timeline */
.pay-timeline { padding:20px 22px; }
.pay-row {
    display:flex; gap:16px; align-items:flex-start;
    padding:14px 16px; border-radius:12px; margin-bottom:10px;
    border:1px solid #f0f4f8; background:#fafbff;
    transition:background .15s;
}
.pay-row:last-child { margin-bottom:0; }
.pay-row:hover { background:#f5f3ff; }
.pay-dot { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.dot-deposit  { background:#eff6ff; }
.dot-win      { background:#ecfdf5; }
.dot-refund   { background:#f1f5f9; }
.pay-label    { font-size:.8rem; font-weight:700; color:#0f172a; }
.pay-sub      { font-size:.72rem; color:#94a3b8; margin-top:2px; }
.pay-amount   { margin-left:auto; font-size:1rem; font-weight:900; color:#0f172a; white-space:nowrap; }
.pay-debit    { color:#dc2626; }
.pay-credit   { color:#059669; }
.pay-order    { font-size:.68rem; color:#94a3b8; font-family:monospace; margin-top:2px; }

/* Bid mini-table */
.bid-mini { width:100%; border-collapse:collapse; font-size:.8rem; }
.bid-mini thead th { background:#f8fafc; color:#94a3b8; text-transform:uppercase; font-size:.68rem; letter-spacing:.5px; padding:8px 14px; font-weight:700; }
.bid-mini tbody td { padding:9px 14px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
.bid-mini tbody tr:last-child td { border-bottom:none; }
.bid-mini tbody tr:hover { background:#faf5ff; }

/* Collapsible */
.par-toggle { background:none; border:none; color:#7c3aed; font-size:.78rem; font-weight:700; cursor:pointer; padding:0; text-decoration:underline; }

/* Mobile */
@media(max-width:576px){
    .pay-row { flex-wrap:wrap; }
    .pay-amount { margin-left:0; }
}
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="ph-hero">
    <div>
        <h1 class="ph-hero-title">👥 Participants</h1>
        <p class="ph-hero-sub">
            {{ $auction->title }} &nbsp;·&nbsp; Lot #{{ $auction->id }}
            &nbsp;·&nbsp;
            <span style="text-transform:capitalize">{{ $auction->status }}</span>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('admin.auctions.show', $auction) }}"
           style="padding:9px 18px;border-radius:10px;font-weight:700;font-size:.82rem;background:#f1f5f9;color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- Stats --}}
@php
    $totalDeposits = $participants->filter(fn($p) => $p->deposit_paid)->count();
    $totalRefunded = $participants->filter(fn($p) => $p->deposit_refunded)->count();
    $totalRevenue  = $participants->filter(fn($p) => $p->deposit_paid)->sum('deposit_amount');
@endphp
<div class="ph-stats">
    <div class="ph-stat">
        <div class="ph-stat-icon" style="background:#faf5ff;color:#8b5cf6"><i class="bi bi-people-fill"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">{{ $participants->count() }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Participants</div>
        </div>
    </div>
    <div class="ph-stat">
        <div class="ph-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">{{ $totalDeposits }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Deposits Paid</div>
        </div>
    </div>
    <div class="ph-stat">
        <div class="ph-stat-icon" style="background:#f1f5f9;color:#64748b"><i class="bi bi-arrow-return-left"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">{{ $totalRefunded }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Refunded</div>
        </div>
    </div>
    <div class="ph-stat">
        <div class="ph-stat-icon" style="background:#ecfdf5;color:#10b981"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:900;color:#0f172a">${{ number_format($totalRevenue, 2) }}</div>
            <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Deposit Revenue</div>
        </div>
    </div>
</div>

@if($participants->isEmpty())
    <div style="background:#fff;border-radius:16px;border:1px solid #f0f4f8;padding:60px;text-align:center;color:#94a3b8;">
        <div style="font-size:3rem;margin-bottom:12px;">🔕</div>
        <p>No bids have been placed on this auction yet.</p>
    </div>
@endif

{{-- Participant cards --}}
@foreach($participants as $idx => $p)
@php
    $initials = strtoupper(substr($p->user->name ?? '?', 0, 2));
@endphp
<div class="par-card {{ $p->is_winner ? 'winner-card' : '' }}">

    {{-- Header --}}
    <div class="par-card-header">
        <div class="par-avatar {{ $p->is_winner ? 'gold' : '' }}">{{ $initials }}</div>
        <div style="flex:1;min-width:0">
            <div class="par-name">
                {{ $p->user->name ?? 'Unknown User' }}
                @if($p->is_winner) <span class="ms-1">🏆</span> @endif
            </div>
            <div class="par-email">{{ $p->user->email ?? '—' }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($p->is_winner)
                <span class="badge-pill bp-winner">🏆 Winner</span>
            @endif
            @if(!$p->deposit)
                <span class="badge-pill bp-no-dep">No Deposit</span>
            @elseif($p->deposit_paid && $p->deposit_refunded)
                <span class="badge-pill bp-refunded">↩ Refunded</span>
            @elseif($p->deposit_paid)
                <span class="badge-pill bp-paid">✓ Deposit Paid</span>
            @else
                <span class="badge-pill bp-pending">⏳ Deposit Pending</span>
            @endif
            <span style="font-size:.78rem;color:#64748b">{{ $p->bid_count }} bid{{ $p->bid_count !== 1 ? 's' : '' }}</span>
            <span style="font-size:1rem;font-weight:900;color:#7c3aed">${{ number_format($p->highest_bid, 2) }}</span>
        </div>
    </div>

    {{-- Payment Timeline --}}
    <div class="pay-timeline">
        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px">
            <i class="bi bi-clock-history me-1"></i> Payment History
        </div>

        @if(!$p->deposit)
            <div style="color:#94a3b8;font-size:.82rem;padding:10px 0">No deposit record found for this participant.</div>
        @else
            {{-- Deposit paid --}}
            @if($p->deposit_paid)
            <div class="pay-row">
                <div class="pay-dot dot-deposit"><i class="bi bi-cash text-primary"></i></div>
                <div style="flex:1;min-width:0">
                    <div class="pay-label">Deposit Paid</div>
                    <div class="pay-sub">{{ $p->deposit->paid_at?->format('d M Y H:i') ?? '—' }}</div>
                    @if($p->deposit_order)
                        <div class="pay-order">
                            Order #{{ $p->deposit_order->order_number }}
                            &nbsp;·&nbsp; {{ $p->deposit_order->payment_method }}
                            &nbsp;·&nbsp; <span style="text-transform:capitalize">{{ $p->deposit_order->payment_status }}</span>
                        </div>
                    @endif
                </div>
                <div class="pay-amount pay-debit">-${{ number_format($p->deposit_amount, 2) }}</div>
            </div>
            @else
            <div class="pay-row">
                <div class="pay-dot dot-deposit" style="background:#fffbeb"><i class="bi bi-clock text-warning"></i></div>
                <div style="flex:1">
                    <div class="pay-label">Deposit Pending</div>
                    <div class="pay-sub">Payment not yet confirmed</div>
                    @if($p->deposit_order)
                        <div class="pay-order">Order #{{ $p->deposit_order->order_number }} · {{ $p->deposit_order->payment_status }}</div>
                    @endif
                </div>
                <div class="pay-amount" style="color:#d97706">${{ number_format($p->deposit_amount, 2) }}</div>
            </div>
            @endif

            {{-- Winner: deposit deducted from final payment --}}
            @if($p->is_winner && $p->winner_order)
            <div class="pay-row">
                <div class="pay-dot dot-win"><i class="bi bi-trophy text-success"></i></div>
                <div style="flex:1;min-width:0">
                    <div class="pay-label">Auction Won — Final Payment</div>
                    <div class="pay-sub">{{ $p->winner_order->created_at?->format('d M Y H:i') ?? '—' }}</div>
                    <div class="pay-order">
                        Order #{{ $p->winner_order->order_number }}
                        &nbsp;·&nbsp; {{ $p->winner_order->payment_method }}
                        &nbsp;·&nbsp; <span style="text-transform:capitalize">{{ $p->winner_order->payment_status }}</span>
                    </div>
                    @if($p->deposit_paid)
                        <div class="pay-sub mt-1" style="color:#059669">
                            Deposit of ${{ number_format($p->deposit_amount, 2) }} deducted from total
                        </div>
                    @endif
                </div>
                <div>
                    <div class="pay-amount pay-debit">-${{ number_format($p->winner_order->total, 2) }}</div>
                    @if($p->deposit_paid)
                        <div style="font-size:.7rem;color:#059669;text-align:right;margin-top:2px;">
                            (incl. -${{ number_format($p->deposit_amount, 2) }} deposit credit)
                        </div>
                    @endif
                </div>
            </div>
            @elseif($p->is_winner)
            <div class="pay-row">
                <div class="pay-dot dot-win"><i class="bi bi-trophy text-success"></i></div>
                <div style="flex:1">
                    <div class="pay-label">🏆 Won — Final Payment Pending</div>
                    <div class="pay-sub">Winning bid: ${{ number_format($p->highest_bid, 2) }}</div>
                </div>
                <div class="pay-amount" style="color:#d97706">Awaiting Payment</div>
            </div>
            @endif

            {{-- Loser: deposit refunded --}}
            @if(!$p->is_winner && $p->deposit_refunded)
            <div class="pay-row">
                <div class="pay-dot dot-refund"><i class="bi bi-arrow-return-left text-secondary"></i></div>
                <div style="flex:1">
                    <div class="pay-label">Deposit Refunded to Wallet</div>
                    <div class="pay-sub">{{ $p->deposit->refunded_at?->format('d M Y H:i') ?? '—' }}</div>
                </div>
                <div class="pay-amount pay-credit">+${{ number_format($p->deposit_amount, 2) }}</div>
            </div>
            @elseif(!$p->is_winner && $p->deposit_paid && !$p->deposit_refunded)
            <div class="pay-row">
                <div class="pay-dot" style="background:#fef2f2"><i class="bi bi-hourglass-split text-danger"></i></div>
                <div style="flex:1">
                    <div class="pay-label">Deposit Refund Pending</div>
                    <div class="pay-sub">Will be refunded when auction is fully closed</div>
                </div>
                <div class="pay-amount" style="color:#94a3b8">+${{ number_format($p->deposit_amount, 2) }}</div>
            </div>
            @endif
        @endif

        {{-- Net summary for participant --}}
        @if($p->deposit_paid)
        @php
            $netPaid = (float) $p->deposit_amount;
            if ($p->is_winner && $p->winner_order) $netPaid += (float) $p->winner_order->total;
            if ($p->deposit_refunded) $netPaid -= (float) $p->deposit_amount;
        @endphp
        <div style="display:flex;justify-content:flex-end;margin-top:12px;padding-top:10px;border-top:1px dashed #e2e8f0;">
            <div style="font-size:.78rem;color:#64748b;margin-right:12px;align-self:center">Net paid to auction:</div>
            <div style="font-size:1.15rem;font-weight:900;color:#0f172a">${{ number_format($netPaid, 2) }}</div>
        </div>
        @endif
    </div>

    {{-- Bids toggle --}}
    <div style="padding:0 22px 16px">
        <button class="par-toggle" onclick="toggleBids(this)">▾ Show {{ $p->bid_count }} Bid{{ $p->bid_count !== 1 ? 's' : '' }}</button>
        <div class="bid-list" style="display:none;margin-top:10px">
            <div class="table-responsive">
                <table class="bid-mini">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Amount</th>
                            <th>Placed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($p->bids->values() as $bi => $bid)
                        <tr>
                            <td style="color:#94a3b8">{{ $bi + 1 }}</td>
                            <td style="font-weight:800;color:{{ $bi === 0 ? '#7c3aed' : '#0f172a' }}">
                                ${{ number_format($bid->amount, 2) }}
                                @if($bi === 0)<span style="font-size:.65rem;color:#a855f7;margin-left:6px">HIGHEST</span>@endif
                            </td>
                            <td style="color:#64748b;font-size:.78rem">{{ $bid->created_at->format('d M Y H:i:s') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endforeach

@endsection

@push('scripts')
<script>
function toggleBids(btn) {
    const list = btn.nextElementSibling;
    const hidden = list.style.display === 'none';
    list.style.display = hidden ? 'block' : 'none';
    btn.textContent = (hidden ? '▴ Hide' : '▾ Show') + btn.textContent.replace(/[▴▾]\s*(Show|Hide)/,'');
}
</script>
@endpush
