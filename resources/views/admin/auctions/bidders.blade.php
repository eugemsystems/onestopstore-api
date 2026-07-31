@extends('admin.layout')
@section('title', 'Auction Bidders - Admin Panel')

@push('styles')
<style>
/* ═══════════════════════════════════════════════
   BIDDERS PAGE — PREMIUM DESIGN
═══════════════════════════════════════════════ */

.bidders-hero-header {
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
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
.bidders-hero-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16,185,129,.3) 0%, transparent 70%);
    pointer-events: none;
}
.bidders-hero-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #059669, #10b981);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    box-shadow: 0 8px 20px rgba(16,185,129,.4);
    flex-shrink: 0;
}
.bidders-hero-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -.3px; }
.bidders-hero-sub   { color: #94a3b8; font-size: .875rem; margin: 4px 0 0; }
.hero-back-btn {
    padding: 10px 20px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff;
    border-radius: 10px;
    font-weight: 600; font-size: .875rem;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
    transition: background .2s;
}
.hero-back-btn:hover { background: rgba(255,255,255,.2); color: #fff; }
.hero-add-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg,#059669,#10b981);
    border: none;
    color: #fff;
    border-radius: 10px;
    font-weight: 700; font-size: .875rem;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: opacity .2s, transform .2s;
    box-shadow: 0 4px 14px rgba(16,185,129,.4);
}
.hero-add-btn:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

/* Stats cards */
.bidders-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.bidder-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    display: flex; align-items: center; gap: 14px;
}
.bidder-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.bidder-stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
.bidder-stat-label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Tabs */
.bidder-filter-panel {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
}
.bidder-tab-pill {
    padding: 7px 16px;
    border-radius: 50px;
    font-size: .8rem; font-weight: 600;
    border: 2px solid transparent;
    background: #f8fafc; color: #64748b;
    cursor: pointer; text-decoration: none;
    transition: all .2s;
    white-space: nowrap;
    display: inline-flex; align-items: center; gap: 6px;
}
.bidder-tab-pill:hover { border-color: #10b981; color: #059669; background: #f0fdf4; }
.bidder-tab-pill.active { background: linear-gradient(135deg,#059669,#10b981); color: #fff; border-color: transparent; box-shadow: 0 3px 10px rgba(16,185,129,.3); }
.pill-count { font-size: .7rem; background: rgba(255,255,255,.25); padding: 2px 7px; border-radius: 20px; }
.bidder-tab-pill:not(.active) .pill-count { background: #e2e8f0; color: #475569; }

/* Search */
.bidder-search-wrap { position: relative; flex: 1; min-width: 200px; }
.bidder-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.bidder-search-field {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 2px solid #e2e8f0; border-radius: 10px;
    font-size: .875rem; transition: border-color .2s;
    background: #f8fafc;
}
.bidder-search-field:focus { outline: none; border-color: #10b981; background: #fff; }

/* Table */
.bidder-table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    overflow: hidden;
}
.bidder-table-card .table { margin: 0; font-size: .83rem; }
.bidder-table-card .table thead th {
    background: linear-gradient(135deg,#0f2027,#203a43);
    color: #94a3b8;
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: 13px 16px;
    border: none;
    white-space: nowrap;
    font-weight: 600;
}
.bidder-table-card .table thead th:first-child { color: #fff; }
.bidder-table-card .table tbody td { padding: 14px 16px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
.bidder-table-card .table tbody tr:nth-child(odd)  { background: #fff; }
.bidder-table-card .table tbody tr:nth-child(even) { background: #f0fdf4; }
.bidder-table-card .table tbody tr:hover td { background: #dcfce7 !important; transition: background .15s; }
.bidder-table-card .table tbody tr:last-child td { border-bottom: none; }

/* Approved/Not badge */
.approved-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .72rem; font-weight: 700; padding: 4px 11px; border-radius: 20px;
}
.approved-yes  { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.approved-no   { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

/* Toggle button */
.toggle-approve-btn {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer;
    transition: all .2s;
    display: inline-flex; align-items: center; gap: 5px;
}
.toggle-approve-btn.grant  { background: #dcfce7; color: #15803d; }
.toggle-approve-btn.grant:hover  { background: #059669; color: #fff; }
.toggle-approve-btn.revoke { background: #fee2e2; color: #dc2626; }
.toggle-approve-btn.revoke:hover { background: #dc2626; color: #fff; }

/* Add deposit btn */
.add-deposit-btn {
    padding: 5px 11px;
    border-radius: 8px;
    font-size: .73rem; font-weight: 700;
    background: #eff6ff; color: #2563eb;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap;
}
.add-deposit-btn:hover { background: #2563eb; color: #fff; }

/* Modal overrides */
.deposit-modal .modal-content {
    border: none;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.deposit-modal .modal-header {
    background: linear-gradient(135deg,#0f2027,#203a43);
    border-radius: 18px 18px 0 0;
    border: none;
    padding: 20px 24px;
}
.deposit-modal .modal-title { color: #fff; font-weight: 800; }
.deposit-modal .modal-header .btn-close { filter: invert(1); }
.deposit-modal .modal-body { padding: 24px; }
.deposit-modal .modal-footer { padding: 16px 24px; border-top: 1px solid #f0f4f8; }
.deposit-field label { font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: 6px; display: block; }
.deposit-field input,
.deposit-field select {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: .875rem;
    transition: border-color .2s, box-shadow .2s;
    background: #f8fafc;
}
.deposit-field input:focus,
.deposit-field select:focus {
    outline: none;
    border-color: #10b981;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(16,185,129,.1);
}

/* Autocomplete results */
.user-search-results {
    position: absolute;
    z-index: 2000;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    display: none;
}
.user-search-result-item {
    padding: 9px 14px;
    cursor: pointer;
    font-size: .83rem;
    border-bottom: 1px solid #f0f4f8;
    transition: background .15s;
}
.user-search-result-item:last-child { border-bottom: none; }
.user-search-result-item:hover { background: #f0fdf4; }
.user-search-result-item .user-name { font-weight: 700; color: #0f172a; }
.user-search-result-item .user-email { font-size: .73rem; color: #94a3b8; }

/* Confirm submit btn */
.btn-deposit-submit {
    padding: 11px 24px;
    background: linear-gradient(135deg,#059669,#10b981);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: .875rem;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: opacity .2s, transform .2s;
    box-shadow: 0 4px 14px rgba(16,185,129,.3);
}
.btn-deposit-submit:hover { opacity: .9; transform: translateY(-1px); }

/* Empty state */
.bidder-empty { padding: 80px 20px; text-align: center; color: #94a3b8; }
.bidder-empty-icon { font-size: 4rem; display: block; margin-bottom: 16px; opacity: .5; }

/* Pagination */
.bidder-pagination {
    padding: 16px 20px;
    border-top: 1px solid #f0f4f8;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
}
.bidder-pagination-info { font-size: .8rem; color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- ── Hero Header ─────────────────────────────────────────────── --}}
<div class="bidders-hero-header">
    <div class="d-flex align-items-center gap-3">
        <div class="bidders-hero-icon">👥</div>
        <div>
            <h2 class="bidders-hero-title">Active Bidders</h2>
            <p class="bidders-hero-sub">Manage users approved to bid and manually record deposits</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="hero-add-btn" data-bs-toggle="modal" data-bs-target="#addDepositModal">
            <i class="bi bi-plus-circle-fill"></i> Add Bidder Deposit
        </button>
        <a href="{{ route('admin.auctions.index') }}" class="hero-back-btn">
            <i class="bi bi-arrow-left"></i> Auctions
        </a>
    </div>
</div>

{{-- ── Flash Messages ──────────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:12px">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:12px">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Stats Row ────────────────────────────────────────────────── --}}
<div class="bidders-stats-row">
    <div class="bidder-stat-card">
        <div class="bidder-stat-icon" style="background:#f0fdf4;color:#10b981"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="bidder-stat-value">{{ $counts['all'] }}</div>
            <div class="bidder-stat-label">Total Bidders</div>
        </div>
    </div>
    <div class="bidder-stat-card">
        <div class="bidder-stat-icon" style="background:#ecfdf5;color:#059669"><i class="bi bi-shield-check-fill"></i></div>
        <div>
            <div class="bidder-stat-value">{{ $counts['approved'] }}</div>
            <div class="bidder-stat-label">Admin Approved</div>
        </div>
    </div>
    <div class="bidder-stat-card">
        <div class="bidder-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-cash-coin"></i></div>
        <div>
            <div class="bidder-stat-value">{{ $counts['depositors'] }}</div>
            <div class="bidder-stat-label">Paid Deposit</div>
        </div>
    </div>
    <div class="bidder-stat-card">
        <div class="bidder-stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="bidder-stat-value">{{ $counts['not_approved'] }}</div>
            <div class="bidder-stat-label">Deposit Only</div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="bidder-filter-panel">
    @php
        $tabs = ['all' => 'All', 'approved' => 'Admin Approved', 'depositors' => 'Paid Deposit', 'not_approved' => 'Deposit Only'];
        $tabIcons = ['all' => 'bi-people', 'approved' => 'bi-shield-check', 'depositors' => 'bi-cash-coin', 'not_approved' => 'bi-hourglass'];
    @endphp
    @foreach($tabs as $key => $label)
        <a href="{{ route('admin.auctions.bidders', array_filter(['tab' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
           class="bidder-tab-pill {{ $tab === $key ? 'active' : '' }}">
            <i class="bi {{ $tabIcons[$key] }}"></i>
            {{ $label }}
            <span class="pill-count">{{ $counts[$key] }}</span>
        </a>
    @endforeach

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.auctions.bidders') }}" class="bidder-search-wrap d-flex gap-2">
        @if(request('tab'))<input type="hidden" name="tab" value="{{ request('tab') }}">@endif
        <div class="bidder-search-wrap">
            <i class="bi bi-search bidder-search-icon"></i>
            <input type="text" name="search" class="bidder-search-field" placeholder="Search by name or email…" value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn btn-sm" style="padding:8px 16px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:10px;font-weight:700">
            <i class="bi bi-funnel-fill"></i> Filter
        </button>
    </form>
</div>

{{-- ── Info Banner --}}
<div class="alert alert-info border-0 mb-3" style="border-radius:12px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-left:4px solid #3b82f6 !important">
    <i class="bi bi-info-circle-fill me-2 text-primary"></i>
    <strong>How bidder access works:</strong>
    <strong>Admin Approved</strong> users can bid on any auction without a deposit.
    <strong>Manual Deposit</strong> grants access to one specific auction.
    Use <strong>"Add Bidder Deposit"</strong> to record a deposit on behalf of a customer without going through the payment gateway.
</div>

{{-- ── Table ────────────────────────────────────────────────────── --}}
<div class="bidder-table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Paid Deposits</th>
                    <th>Bid Access</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    {{-- 1. User --}}
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0f2027,#203a43);display:flex;align-items:center;justify-content:center;color:#10b981;font-weight:800;font-size:.9rem;flex-shrink:0">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:700;color:#0f172a;font-size:.875rem">{{ $user->name }}</div>
                                <div style="font-size:.7rem;color:#94a3b8">ID #{{ $user->id }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- 2. Email --}}
                    <td style="font-size:.83rem;color:#334155">
                        <a href="mailto:{{ $user->email }}" style="color:#2563eb;text-decoration:none">{{ $user->email }}</a>
                    </td>

                    {{-- 3. Phone --}}
                    <td style="font-size:.83rem;color:#475569">
                        {{ $user->phone ? '+' . ($user->country_code ?? '') . ' ' . $user->phone : '—' }}
                    </td>

                    {{-- 4. Paid Deposits --}}
                    <td>
                        @if($user->paid_deposit_count > 0)
                            <span style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;padding:3px 10px;font-size:.75rem;font-weight:700">
                                <i class="bi bi-cash-coin"></i> {{ $user->paid_deposit_count }} deposit{{ $user->paid_deposit_count !== 1 ? 's' : '' }}
                            </span>
                        @else
                            <span style="color:#94a3b8;font-size:.8rem">No deposits</span>
                        @endif
                    </td>

                    {{-- 5. Bid Access --}}
                    <td>
                        @if($user->auction_approved)
                            <span class="approved-badge approved-yes">
                                <i class="bi bi-shield-check-fill"></i> Admin Approved
                            </span>
                        @else
                            <span class="approved-badge approved-no">
                                <i class="bi bi-cash-coin"></i> Deposit Only
                            </span>
                        @endif
                    </td>

                    {{-- 6. Actions --}}
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            {{-- Toggle global approval --}}
                            <form action="{{ route('admin.auctions.bidders.toggle', $user) }}" method="POST" style="display:inline">
                                @csrf
                                @if($user->auction_approved)
                                    <button type="submit" class="toggle-approve-btn revoke"
                                            data-swal-confirm="Revoke admin bidding approval for {{ addslashes($user->name) }}?">
                                        <i class="bi bi-shield-x"></i> Revoke
                                    </button>
                                @else
                                    <button type="submit" class="toggle-approve-btn grant"
                                            data-swal-confirm="Grant admin bidding approval to {{ addslashes($user->name) }}? They can bid on any auction without paying a deposit.">
                                        <i class="bi bi-shield-check"></i> Approve
                                    </button>
                                @endif
                            </form>

                            {{-- Quick: Add deposit for this user --}}
                            <button type="button" class="add-deposit-btn"
                                    data-bs-toggle="modal" data-bs-target="#addDepositModal"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-email="{{ $user->email }}">
                                <i class="bi bi-cash-stack"></i> Add Deposit
                            </button>

                            {{-- Edit user --}}
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm" style="background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:.75rem">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="bidder-empty">
                            <span class="bidder-empty-icon">👥</span>
                            <h3>No bidders found</h3>
                            <p>No users have paid a deposit or been granted bidding approval yet.</p>
                            <button class="hero-add-btn mt-2" data-bs-toggle="modal" data-bs-target="#addDepositModal" style="margin:auto">
                                <i class="bi bi-plus-circle-fill"></i> Add First Bidder
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
        <div class="bidder-pagination">
            <div class="bidder-pagination-info">
                Showing <strong>{{ $users->firstItem() }}–{{ $users->lastItem() }}</strong>
                of <strong>{{ $users->total() }}</strong> bidders
            </div>
            {{ $users->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- ════════════════════════════════════════════════════════════════
     ADD BIDDER DEPOSIT MODAL
════════════════════════════════════════════════════════════════ --}}
<div class="modal fade deposit-modal" id="addDepositModal" tabindex="-1" aria-labelledby="addDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepositModalLabel">
                    <i class="bi bi-cash-stack me-2"></i> Add Bidder Deposit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('admin.auctions.bidders.add-deposit') }}" method="POST" id="addDepositForm">
                @csrf
                <div class="modal-body">
                    <p style="font-size:.83rem;color:#64748b;margin-bottom:20px">
                        Manually record a deposit for a user on a specific auction.
                        This will let them bid immediately without going through the payment gateway.
                    </p>

                    {{-- User search / select --}}
                    <div class="deposit-field mb-3" style="position:relative">
                        <label for="deposit-user-search"><i class="bi bi-person-fill me-1 text-success"></i> Customer *</label>
                        <input type="text"
                               id="deposit-user-search"
                               class="deposit-field"
                               placeholder="Search by name or email…"
                               autocomplete="off">
                        <input type="hidden" name="user_id" id="deposit-user-id" required>
                        <div id="deposit-user-results" class="user-search-results"></div>
                        <div id="deposit-user-selected" style="display:none;margin-top:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px;font-size:.83rem">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong id="deposit-user-selected-name"></strong>
                            <span id="deposit-user-selected-email" style="color:#64748b;margin-left:6px"></span>
                            <button type="button" id="deposit-user-clear" style="background:none;border:none;color:#dc2626;cursor:pointer;float:right;font-size:.8rem">✕ Change</button>
                        </div>
                    </div>

                    {{-- Auction select --}}
                    <div class="deposit-field mb-3">
                        <label for="deposit-auction-id"><i class="bi bi-hammer me-1 text-warning"></i> Auction *</label>
                        <select name="auction_item_id" id="deposit-auction-id" required>
                            <option value="">— Select an Auction —</option>
                            @foreach($auctions as $auction)
                                <option value="{{ $auction->id }}"
                                        data-status="{{ $auction->status }}">
                                    @if($auction->status === 'active')🟢@else⚪@endif
                                    {{ $auction->title }}
                                    ({{ $auction->status === 'active' ? 'Live' : 'Draft' }})
                                    @if($auction->ends_at) — ends {{ $auction->ends_at->format('d M H:i') }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Amount --}}
                    <div class="deposit-field mb-3">
                        <label for="deposit-amount"><i class="bi bi-currency-dollar me-1 text-primary"></i> Deposit Amount (USD) *</label>
                        <input type="number"
                               name="amount"
                               id="deposit-amount"
                               min="0.01"
                               step="0.01"
                               value="{{ $defaultDepositAmount > 0 ? $defaultDepositAmount : '' }}"
                               placeholder="e.g. {{ $defaultDepositAmount > 0 ? $defaultDepositAmount : '10.00' }}"
                               required>
                        @if($defaultDepositAmount > 0)
                            <div style="font-size:.73rem;color:#10b981;margin-top:4px">
                                <i class="bi bi-info-circle"></i> Default bid fee is ${{ number_format($defaultDepositAmount, 2) }}
                            </div>
                        @endif
                    </div>

                    {{-- Notes (optional) --}}
                    <div class="deposit-field">
                        <label for="deposit-notes"><i class="bi bi-chat-left-text me-1"></i> Notes (optional)</label>
                        <input type="text"
                               name="notes"
                               id="deposit-notes"
                               placeholder="e.g. Paid in office — cash">
                    </div>
                </div>

                <div class="modal-footer" style="justify-content:space-between">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="color:#64748b;background:#f1f5f9;border:none;border-radius:8px;padding:9px 18px;font-weight:600">
                        Cancel
                    </button>
                    <button type="submit" class="btn-deposit-submit" id="deposit-submit-btn" disabled>
                        <i class="bi bi-check-circle-fill"></i> Record Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const searchInput   = document.getElementById('deposit-user-search');
    const hiddenInput   = document.getElementById('deposit-user-id');
    const resultsBox    = document.getElementById('deposit-user-results');
    const selectedBox   = document.getElementById('deposit-user-selected');
    const selectedName  = document.getElementById('deposit-user-selected-name');
    const selectedEmail = document.getElementById('deposit-user-selected-email');
    const clearBtn      = document.getElementById('deposit-user-clear');
    const submitBtn     = document.getElementById('deposit-submit-btn');
    const auctionSelect = document.getElementById('deposit-auction-id');

    let searchTimeout;

    function checkReady() {
        submitBtn.disabled = !(hiddenInput.value && auctionSelect.value);
    }

    auctionSelect.addEventListener('change', checkReady);

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) { resultsBox.style.display = 'none'; return; }

        searchTimeout = setTimeout(() => {
            fetch(`{{ route('admin.auctions.bidders.search-users') }}?q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(users => {
                resultsBox.innerHTML = '';
                if (!users.length) {
                    resultsBox.innerHTML = '<div class="user-search-result-item" style="color:#94a3b8">No users found</div>';
                } else {
                    users.forEach(u => {
                        const item = document.createElement('div');
                        item.className = 'user-search-result-item';
                        item.innerHTML = `<div class="user-name">${u.name}</div><div class="user-email">${u.email}</div>`;
                        item.addEventListener('click', () => selectUser(u));
                        resultsBox.appendChild(item);
                    });
                }
                resultsBox.style.display = 'block';
            });
        }, 300);
    });

    function selectUser(u) {
        hiddenInput.value = u.id;
        searchInput.style.display = 'none';
        resultsBox.style.display  = 'none';
        selectedName.textContent  = u.name;
        selectedEmail.textContent = u.email;
        selectedBox.style.display = 'block';
        checkReady();
    }

    clearBtn.addEventListener('click', () => {
        hiddenInput.value = '';
        searchInput.value = '';
        searchInput.style.display = '';
        selectedBox.style.display = 'none';
        searchInput.focus();
        checkReady();
    });

    // Pre-fill when opened via "Add Deposit" row button
    document.getElementById('addDepositModal').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn) return;
        const userId    = btn.dataset.userId;
        const userName  = btn.dataset.userName;
        const userEmail = btn.dataset.userEmail;
        if (userId) {
            selectUser({ id: userId, name: userName, email: userEmail });
        }
    });

    // Reset modal on close
    document.getElementById('addDepositModal').addEventListener('hidden.bs.modal', function () {
        hiddenInput.value = '';
        searchInput.value = '';
        searchInput.style.display = '';
        selectedBox.style.display = 'none';
        resultsBox.style.display  = 'none';
        auctionSelect.value       = '';
        submitBtn.disabled        = true;
    });

    // Close results when clicking outside
    document.addEventListener('click', e => {
        if (!e.target.closest('#deposit-user-search') && !e.target.closest('#deposit-user-results')) {
            resultsBox.style.display = 'none';
        }
    });
}());
</script>
@endpush
