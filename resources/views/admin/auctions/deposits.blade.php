@extends('admin.layout')
@section('title', 'Auction Deposits - Admin Panel')

@push('styles')
<style>
/* ═══════════════════════════════════════════════
   DEPOSITS PAGE — PREMIUM DESIGN
═══════════════════════════════════════════════ */

.dep-hero {
    background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}
.dep-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(37,99,235,.25) 0%, transparent 70%);
    pointer-events: none;
}
.dep-hero-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    box-shadow: 0 8px 20px rgba(59,130,246,.4);
    flex-shrink: 0;
}
.dep-hero-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0; }
.dep-hero-sub   { color: #94a3b8; font-size: .875rem; margin: 4px 0 0; }
.dep-hero-btn {
    padding: 10px 20px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; border-radius: 10px;
    font-weight: 600; font-size: .875rem;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
    transition: background .2s;
}
.dep-hero-btn:hover { background: rgba(255,255,255,.2); color: #fff; }

/* Stats */
.dep-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.dep-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    display: flex; align-items: center; gap: 14px;
}
.dep-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.dep-stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
.dep-stat-label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Filter panel */
.dep-filter-panel {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;
}
.dep-filter-group { display: flex; flex-direction: column; gap: 5px; }
.dep-filter-label { font-size: .72rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .4px; }
.dep-filter-input {
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: .83rem;
    background: #f8fafc;
    transition: border-color .2s;
    min-width: 140px;
}
.dep-filter-input:focus { outline: none; border-color: #3b82f6; background: #fff; }
.dep-filter-btn {
    padding: 8px 18px;
    border: none; border-radius: 10px;
    font-weight: 700; font-size: .83rem;
    background: linear-gradient(135deg,#1d4ed8,#3b82f6);
    color: #fff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: opacity .2s;
    align-self: flex-end;
}
.dep-filter-btn:hover { opacity: .88; }
.dep-filter-reset {
    padding: 8px 14px;
    border: 2px solid #e2e8f0; border-radius: 10px;
    font-size: .83rem; font-weight: 600;
    background: #fff; color: #64748b;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    align-self: flex-end;
    transition: border-color .2s;
}
.dep-filter-reset:hover { border-color: #94a3b8; color: #374151; }

/* Table */
.dep-table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    overflow: hidden;
}
.dep-table-card .table { margin: 0; font-size: .83rem; }
.dep-table-card .table thead th {
    background: linear-gradient(135deg,#1a1a2e,#16213e);
    color: #94a3b8;
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: 13px 16px;
    border: none;
    white-space: nowrap;
    font-weight: 600;
}
.dep-table-card .table thead th:first-child { color: #fff; }
.dep-table-card .table tbody td { padding: 12px 16px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.dep-table-card .table tbody tr:nth-child(odd)  { background: #fff; }
.dep-table-card .table tbody tr:nth-child(even) { background: #eff6ff; }
.dep-table-card .table tbody tr:hover td { background: #dbeafe !important; transition: background .15s; }
.dep-table-card .table tbody tr:last-child td { border-bottom: none; }

/* Method badges */
.method-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .7rem; font-weight: 700;
    padding: 3px 9px; border-radius: 20px;
    white-space: nowrap;
}
.method-admin    { background: #ede9fe; color: #7c3aed; border: 1px solid #ddd6fe; }
.method-payfast  { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.method-paynow   { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.method-stripe   { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.method-other    { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
.method-refunded { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Pagination */
.dep-pagination {
    padding: 16px 20px;
    border-top: 1px solid #f0f4f8;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
}
.dep-pagination-info { font-size: .8rem; color: #94a3b8; }

/* Empty */
.dep-empty { padding: 80px 20px; text-align: center; color: #94a3b8; }
.dep-empty-icon { font-size: 4rem; display: block; margin-bottom: 16px; opacity: .5; }
</style>
@endpush

@section('content')

{{-- ── Hero Header ─────────────────────────────────────────────── --}}
<div class="dep-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="dep-hero-icon">💰</div>
        <div>
            <h2 class="dep-hero-title">Auction Deposits</h2>
            <p class="dep-hero-sub">All deposit payments recorded across auctions</p>
        </div>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="dep-hero-btn">
        <i class="bi bi-arrow-left"></i> Auctions
    </a>
</div>

{{-- ── Stats ────────────────────────────────────────────────────── --}}
<div class="dep-stats-row">
    <div class="dep-stat-card">
        <div class="dep-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-receipt"></i></div>
        <div>
            <div class="dep-stat-value">{{ $stats['total'] }}</div>
            <div class="dep-stat-label">Total Deposits</div>
        </div>
    </div>
    <div class="dep-stat-card">
        <div class="dep-stat-icon" style="background:#ecfdf5;color:#059669"><i class="bi bi-cash-coin"></i></div>
        <div>
            <div class="dep-stat-value">${{ number_format($stats['total_amount'], 2) }}</div>
            <div class="dep-stat-label">Total Value</div>
        </div>
    </div>
    <div class="dep-stat-card">
        <div class="dep-stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-shield-check-fill"></i></div>
        <div>
            <div class="dep-stat-value">{{ $stats['admin_manual'] }}</div>
            <div class="dep-stat-label">Admin Added</div>
        </div>
    </div>
    <div class="dep-stat-card">
        <div class="dep-stat-icon" style="background:#fef2f2;color:#dc2626"><i class="bi bi-arrow-counterclockwise"></i></div>
        <div>
            <div class="dep-stat-value">{{ $stats['refunded'] }}</div>
            <div class="dep-stat-label">Refunded</div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="dep-filter-panel">
    <form method="GET" action="{{ route('admin.auctions.deposits') }}" class="d-flex flex-wrap align-items-end gap-2 w-100">

        {{-- Search --}}
        <div class="dep-filter-group" style="flex:1;min-width:160px">
            <label class="dep-filter-label">Search User</label>
            <input type="text" name="search" class="dep-filter-input"
                   placeholder="Name or email…" value="{{ request('search') }}">
        </div>

        {{-- Auction --}}
        <div class="dep-filter-group">
            <label class="dep-filter-label">Auction</label>
            <select name="auction_id" class="dep-filter-input">
                <option value="">All Auctions</option>
                @foreach($auctionOptions as $ao)
                    <option value="{{ $ao->id }}" {{ request('auction_id') == $ao->id ? 'selected' : '' }}>
                        {{ \Illuminate\Support\Str::limit($ao->title, 40) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Payment method --}}
        <div class="dep-filter-group">
            <label class="dep-filter-label">Method</label>
            <select name="method" class="dep-filter-input">
                <option value="">All Methods</option>
                <option value="admin_manual" {{ request('method') === 'admin_manual' ? 'selected' : '' }}>Admin Manual</option>
                <option value="payfast"      {{ request('method') === 'payfast'      ? 'selected' : '' }}>PayFast</option>
                <option value="paynow"       {{ request('method') === 'paynow'       ? 'selected' : '' }}>PayNow</option>
                <option value="stripe"       {{ request('method') === 'stripe'       ? 'selected' : '' }}>Stripe</option>
            </select>
        </div>

        {{-- Refund status --}}
        <div class="dep-filter-group">
            <label class="dep-filter-label">Status</label>
            <select name="status" class="dep-filter-input">
                <option value="">All</option>
                <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>Paid</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>

        {{-- Date from --}}
        <div class="dep-filter-group">
            <label class="dep-filter-label">From</label>
            <input type="date" name="from" class="dep-filter-input" value="{{ request('from') }}">
        </div>

        {{-- Date to --}}
        <div class="dep-filter-group">
            <label class="dep-filter-label">To</label>
            <input type="date" name="to" class="dep-filter-input" value="{{ request('to') }}">
        </div>

        <button type="submit" class="dep-filter-btn">
            <i class="bi bi-funnel-fill"></i> Filter
        </button>
        @if(request()->hasAny(['search','auction_id','method','status','from','to']))
            <a href="{{ route('admin.auctions.deposits') }}" class="dep-filter-reset">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        @endif
    </form>
</div>

{{-- ── Table ────────────────────────────────────────────────────── --}}
<div class="dep-table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Auction</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Refunded At</th>
                </tr>
            </thead>
            <tbody>
            @forelse($deposits as $dep)
                @php
                    $method = $dep->payment_method ?? 'other';
                    $methodClass = match($method) {
                        'admin_manual' => 'method-admin',
                        'payfast'      => 'method-payfast',
                        'paynow'       => 'method-paynow',
                        'stripe'       => 'method-stripe',
                        default        => 'method-other',
                    };
                    $methodLabel = match($method) {
                        'admin_manual' => '🛡 Admin',
                        'payfast'      => '💳 PayFast',
                        'paynow'       => '📱 PayNow',
                        'stripe'       => '💳 Stripe',
                        default        => ucfirst(str_replace('_', ' ', $method)),
                    };
                @endphp
                <tr>
                    {{-- # --}}
                    <td style="color:#94a3b8;font-size:.75rem">{{ $dep->id }}</td>

                    {{-- User --}}
                    <td>
                        @if($dep->user)
                            <div style="font-weight:700;color:#0f172a;font-size:.83rem">{{ $dep->user->name }}</div>
                            <div style="font-size:.7rem;color:#94a3b8">{{ $dep->user->email }}</div>
                        @else
                            <span style="color:#94a3b8;font-size:.8rem">—</span>
                        @endif
                    </td>

                    {{-- Auction --}}
                    <td>
                        @if($dep->auctionItem)
                            <a href="{{ route('admin.auctions.show', $dep->auctionItem) }}"
                               style="color:#2563eb;text-decoration:none;font-size:.83rem;font-weight:600">
                                {{ \Illuminate\Support\Str::limit($dep->auctionItem->title, 36) }}
                            </a>
                            <div style="font-size:.7rem;color:#94a3b8;margin-top:2px">
                                ID #{{ $dep->auction_item_id }}
                            </div>
                        @else
                            <span style="color:#94a3b8;font-size:.8rem">Deleted auction</span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td style="font-weight:800;color:#0f172a;font-size:.95rem">
                        ${{ number_format($dep->amount, 2) }}
                    </td>

                    {{-- Method --}}
                    <td>
                        <span class="method-badge {{ $methodClass }}">{{ $methodLabel }}</span>
                        @if($dep->order_id)
                            <div style="font-size:.68rem;color:#94a3b8;margin-top:4px">Order #{{ $dep->order_id }}</div>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($dep->refunded_at)
                            <span class="method-badge method-refunded"><i class="bi bi-arrow-counterclockwise"></i> Refunded</span>
                        @elseif($dep->paid_at)
                            <span class="method-badge" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0">
                                <i class="bi bi-check-circle-fill"></i> Paid
                            </span>
                        @else
                            <span class="method-badge method-other">
                                <i class="bi bi-hourglass"></i> Pending
                            </span>
                        @endif
                    </td>

                    {{-- Paid At --}}
                    <td style="font-size:.78rem;color:#475569;white-space:nowrap">
                        {{ $dep->paid_at?->format('d M Y H:i') ?? '—' }}
                    </td>

                    {{-- Refunded At --}}
                    <td style="font-size:.78rem;color:#dc2626;white-space:nowrap">
                        {{ $dep->refunded_at?->format('d M Y H:i') ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="dep-empty">
                            <span class="dep-empty-icon">💰</span>
                            <h3>No deposits found</h3>
                            <p>Try adjusting the filters above.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($deposits->hasPages())
        <div class="dep-pagination">
            <div class="dep-pagination-info">
                Showing <strong>{{ $deposits->firstItem() }}–{{ $deposits->lastItem() }}</strong>
                of <strong>{{ $deposits->total() }}</strong> deposits
            </div>
            {{ $deposits->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
