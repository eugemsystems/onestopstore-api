@extends('admin.layout')
@section('title', 'Auctions - Admin Panel')

@push('styles')
<style>
/* ═══════════════════════════════════════════════
   AUCTION INDEX PAGE — PREMIUM DESIGN
═══════════════════════════════════════════════ */

/* Hero Headers */
.auction-hero-header {
    background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
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
.auction-hero-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(168,85,247,.35) 0%, transparent 70%);
    pointer-events: none;
}
.auction-hero-header::after {
    content: '🔨';
    position: absolute;
    right: 140px; top: 50%;
    transform: translateY(-50%);
    font-size: 5rem;
    opacity: .07;
    pointer-events: none;
}
.auction-hero-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #7e22ce, #a855f7);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    box-shadow: 0 8px 20px rgba(168,85,247,.4);
    flex-shrink: 0;
}
.auction-hero-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -.3px; }
.auction-hero-sub   { color: #94a3b8; font-size: .875rem; margin: 4px 0 0; }
.auction-hero-btn {
    padding: 11px 22px;
    background: linear-gradient(135deg, #7e22ce, #a855f7);
    color: #fff;
    border: none; border-radius: 10px;
    font-weight: 700; font-size: .875rem;
    display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none; white-space: nowrap;
    box-shadow: 0 4px 14px rgba(168,85,247,.4);
    transition: opacity .2s, transform .2s;
    flex-shrink: 0;
}
.auction-hero-btn:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

/* Stats row */
.auction-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.auction-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    display: flex; align-items: center; gap: 14px;
    transition: transform .2s, box-shadow .2s;
}
.auction-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.auction-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.stat-live     { background: #ecfdf5; color: #10b981; }
.stat-draft    { background: #f8fafc; color: #64748b; }
.stat-ended    { background: #eff6ff; color: #3b82f6; }
.stat-total    { background: #faf5ff; color: #8b5cf6; }
.auction-stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
.auction-stat-label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

/* Filter panel */
.auction-filter-panel {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
}
.auction-status-pill {
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
.auction-status-pill:hover { border-color: #a855f7; color: #7e22ce; background: #faf5ff; }
.auction-status-pill.active { background: linear-gradient(135deg,#7e22ce,#a855f7); color: #fff; border-color: transparent; box-shadow: 0 3px 10px rgba(168,85,247,.3); }
.pill-count { font-size: .7rem; background: rgba(255,255,255,.25); padding: 2px 7px; border-radius: 20px; }
.auction-status-pill:not(.active) .pill-count { background: #e2e8f0; color: #475569; }

/* Search field */
.auction-search-wrap { position: relative; flex: 1; min-width: 200px; }
.auction-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.auction-search-field {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 2px solid #e2e8f0; border-radius: 10px;
    font-size: .875rem; transition: border-color .2s;
    background: #f8fafc;
}
.auction-search-field:focus { outline: none; border-color: #a855f7; background: #fff; }

/* Table card */
.auction-table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f4f8;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    overflow: hidden;
}
.auction-table-card .table { margin: 0; font-size: .83rem; }
.auction-table-card .table thead th {
    background: linear-gradient(135deg,#0f0c29,#302b63);
    color: #94a3b8;
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: 13px 16px;
    border: none;
    white-space: nowrap;
    font-weight: 600;
}
.auction-table-card .table thead th:first-child { color: #fff; }
.auction-table-card .table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f4f8;
    vertical-align: middle;
}
/* Zebra striping */
.auction-table-card .table tbody tr:nth-child(odd)  { background: #ffffff; }
.auction-table-card .table tbody tr:nth-child(even) { background: #f8f6ff; }
/* Hover — both stripe rows get the same highlight */
.auction-table-card .table tbody tr:hover td { background: #ede9fe !important; transition: background .15s; }
.auction-table-card .table tbody tr:last-child td { border-bottom: none; }

/* Thumbnail */
.auction-img-cell { display: flex; align-items: center; gap: 12px; }
.auction-thumb {
    width: 52px; height: 52px; object-fit: cover;
    border-radius: 10px; border: 2px solid #e2e8f0;
    flex-shrink: 0;
}
.auction-thumb-ph {
    width: 52px; height: 52px;
    background: linear-gradient(135deg,#f1f5f9,#e2e8f0);
    border-radius: 10px; border: 2px dashed #cbd5e1;
    display: flex; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 1.2rem; flex-shrink: 0;
}
.auction-item-title { font-weight: 700; color: #0f172a; font-size: .875rem; margin: 0 0 3px; line-height: 1.3; }
.auction-item-sub   { font-size: .75rem; color: #94a3b8; margin: 0; }

/* Condition badges */
.cond-badge {
    font-size: .68rem; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: .4px;
    white-space: nowrap;
}
.cond-damaged     { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.cond-refurbished { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.cond-asis        { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

/* Status badges */
.status-badge-pill {
    font-size: .7rem; font-weight: 700; padding: 4px 11px;
    border-radius: 20px; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 5px;
}
.sb-draft     { background: #f1f5f9; color: #64748b; }
.sb-active    { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.sb-ended     { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.sb-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Bid amount */
.bid-amount-cell { font-weight: 800; color: #0f172a; font-size: .95rem; }
.bid-start-price  { font-size: .72rem; color: #94a3b8; }

/* Countdown */
.auction-cd {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .75rem; font-weight: 700; margin-top: 6px;
    background: #faf5ff; color: #7e22ce;
    padding: 3px 10px; border-radius: 20px;
    border: 1px solid #e9d5ff;
}
.auction-cd.urgent { background: #fef2f2; color: #dc2626; border-color: #fecaca; animation: pulse 1s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.55} }

/* Winner badge */
.winner-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: linear-gradient(135deg,#ecfdf5,#d1fae5);
    color: #059669; font-size: .75rem; font-weight: 700;
    padding: 4px 10px; border-radius: 20px;
    border: 1px solid #a7f3d0;
}

/* Action buttons */
.auction-actions { display: flex; gap: 5px; }
.action-btn {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; transition: all .15s;
    text-decoration: none; border: none; cursor: pointer;
}
.action-btn-edit   { background: #eff6ff; color: #3b82f6; }
.action-btn-stop   { background: #fffbeb; color: #d97706; }
.action-btn-delete { background: #fef2f2; color: #dc2626; }
.action-btn:hover  { transform: scale(1.12); }

/* Empty state */
.auction-empty {
    padding: 80px 20px; text-align: center; color: #94a3b8;
}
.auction-empty-icon { font-size: 4rem; display: block; margin-bottom: 16px; opacity: .5; }
.auction-empty h3   { color: #475569; font-size: 1.1rem; margin-bottom: 6px; }

/* Pagination */
.auction-pagination {
    padding: 16px 20px;
    border-top: 1px solid #f0f4f8;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
}
.auction-pagination-info { font-size: .8rem; color: #94a3b8; }

/* Per-page selector */
.perpage-label { font-size: .8rem; color: #64748b; font-weight: 600; white-space: nowrap; }
.perpage-select {
    padding: 7px 28px 7px 12px;
    border: 2px solid #e2e8f0; border-radius: 10px;
    font-size: .8rem; font-weight: 600; color: #0f172a;
    background: #f8fafc;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    cursor: pointer;
    transition: border-color .2s;
}
.perpage-select:focus { outline: none; border-color: #a855f7; background-color: #fff; }
.perpage-wrap { display: flex; align-items: center; gap: 8px; margin-left: auto; }
</style>
@endpush

@section('content')

{{-- ── Hero Header ─────────────────────────────────────────── --}}
<div class="auction-hero-header">
    <div class="d-flex align-items-center gap-3">
        <div class="auction-hero-icon">🔨</div>
        <div>
            <h2 class="auction-hero-title">Auctions</h2>
            <p class="auction-hero-sub">Sell transit-damaged items at auction prices</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.auctions.statistics') }}" class="auction-hero-btn" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);box-shadow:none">
            <i class="bi bi-bar-chart-line"></i> Analytics
        </a>
        <a href="{{ route('admin.auctions.bidders') }}" class="auction-hero-btn" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);box-shadow:none">
            <i class="bi bi-people-fill"></i> Bidders
        </a>
        <a href="{{ route('admin.auctions.settings') }}" class="auction-hero-btn" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);box-shadow:none">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
        <a href="{{ route('admin.auctions.create') }}" class="auction-hero-btn">
            <i class="bi bi-plus-circle-fill"></i> New Auction
        </a>
    </div>
</div>

{{-- ── Flash Messages ──────────────────────────────────────── --}}
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

{{-- ── Stats Row ───────────────────────────────────────────── --}}
<div class="auction-stats-row">
    <div class="auction-stat-card">
        <div class="auction-stat-icon stat-total"><i class="bi bi-hammer"></i></div>
        <div>
            <div class="auction-stat-value">{{ $statusCounts['all'] }}</div>
            <div class="auction-stat-label">Total</div>
        </div>
    </div>
    <div class="auction-stat-card">
        <div class="auction-stat-icon stat-live"><i class="bi bi-lightning-fill"></i></div>
        <div>
            <div class="auction-stat-value">{{ $statusCounts['active'] }}</div>
            <div class="auction-stat-label">Live Now</div>
        </div>
    </div>
    <div class="auction-stat-card">
        <div class="auction-stat-icon stat-draft"><i class="bi bi-file-earmark-text"></i></div>
        <div>
            <div class="auction-stat-value">{{ $statusCounts['draft'] }}</div>
            <div class="auction-stat-label">Drafts</div>
        </div>
    </div>
    <div class="auction-stat-card">
        <div class="auction-stat-icon stat-ended"><i class="bi bi-flag-fill"></i></div>
        <div>
            <div class="auction-stat-value">{{ $statusCounts['ended'] }}</div>
            <div class="auction-stat-label">Ended</div>
        </div>
    </div>
    <div class="auction-stat-card">
        <div class="auction-stat-icon" style="background:#fff3f3;color:#dc2626"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="auction-stat-value">{{ $statusCounts['cancelled'] }}</div>
            <div class="auction-stat-label">Cancelled</div>
        </div>
    </div>
</div>

{{-- ── Filters ─────────────────────────────────────────────── --}}
<div class="auction-filter-panel">
    @php
        $tabs      = ['all'=>'All','draft'=>'Draft','active'=>'Live','ended'=>'Ended','cancelled'=>'Cancelled'];
        $icons     = ['all'=>'bi-grid','draft'=>'bi-file-earmark','active'=>'bi-lightning-fill','ended'=>'bi-flag-fill','cancelled'=>'bi-x-circle'];
        $cur       = request('status', 'all');
        $curPerPage = request('per_page', 20);
    @endphp
    @foreach($tabs as $key => $label)
        <a href="{{ route('admin.auctions.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => request('search'), 'per_page' => $curPerPage != 20 ? $curPerPage : null])) }}"
           class="auction-status-pill {{ $cur === $key ? 'active' : '' }}">
            <i class="bi {{ $icons[$key] }}"></i>
            {{ $label }}
            <span class="pill-count">{{ $statusCounts[$key] }}</span>
        </a>
    @endforeach

    {{-- Search + Per-page --}}
    <form method="GET" action="{{ route('admin.auctions.index') }}" class="auction-search-wrap d-flex gap-2" id="auction-filter-form">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <input type="hidden" name="per_page" id="per_page_hidden" value="{{ $curPerPage }}">
        <div class="auction-search-wrap">
            <i class="bi bi-search auction-search-icon"></i>
            <input type="text" name="search" class="auction-search-field" placeholder="Search auctions…" value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn btn-sm auction-hero-btn" style="padding:8px 16px;box-shadow:none">
            <i class="bi bi-funnel-fill"></i> Filter
        </button>
    </form>

    {{-- Per-page selector --}}
    <div class="perpage-wrap">
        <span class="perpage-label">Show</span>
        <select class="perpage-select" id="perpage-select" onchange="changePerPage(this.value)">
            @foreach([10, 25, 50, 75, 100, 0] as $opt)
                <option value="{{ $opt }}" {{ (int)$curPerPage === $opt ? 'selected' : '' }}>
                    {{ $opt === 0 ? 'All' : $opt }}
                </option>
            @endforeach
        </select>
        <span class="perpage-label">per page</span>
    </div>
</div>

@php $showingAll = $perPage === 0; @endphp
{{-- ── Bulk Action Bar (hidden until checkboxes selected) ────── --}}
<div id="bulkActionBar" style="display:none; background:linear-gradient(135deg,#1e293b,#334155); border-radius:12px; padding:12px 16px; margin-bottom:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
    <div style="display:flex; align-items:center; gap:8px;">
        <span style="background:#a855f7; color:#fff; border-radius:50px; font-size:.75rem; font-weight:800; padding:3px 12px;" id="bulkCount">0</span>
        <span style="font-size:.85rem; font-weight:600; color:#cbd5e1;">selected</span>
    </div>
    <form method="POST" action="{{ route('admin.auctions.bulk-action') }}" id="bulkForm" style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
        @csrf
        <input type="hidden" name="ids" id="bulkIds" value="">

        <select name="action" id="bulkActionSelect" class="form-select form-select-sm" style="width:160px; font-size:.8rem;" required>
            <option value="">Select action...</option>
            <option value="activate">✅ Activate</option>
            <option value="draft">📝 Move to Draft</option>
            <option value="cancel">🚫 Cancel</option>
            <option value="delete">🗑 Delete</option>
            <option value="change_branch">📍 Change Branch</option>
            <option value="extend_time">⏰ Extend Time</option>
        </select>

        <select name="branch" id="bulkBranch" class="form-select form-select-sm" style="width:120px; font-size:.8rem; display:none;">
            <option value="All">All</option>
            <option value="Harare">Harare</option>
            <option value="Mutare">Mutare</option>
            <option value="Bulawayo">Bulawayo</option>
            <option value="Zambia">Zambia</option>
        </select>

        <input type="number" name="extend_hours" id="bulkExtendHours" class="form-control form-control-sm" style="width:80px; font-size:.8rem; display:none;" placeholder="Hours" min="1" max="168">

        <button type="submit" class="btn btn-sm btn-primary" style="font-size:.8rem;" data-swal-confirm="Apply this action to the selected auctions?">
            Apply
        </button>
    </form>
</div>

{{-- ── Table ───────────────────────────────────────────────── --}}
<div class="auction-table-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:30px"><input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)" title="Select all"></th>
                    <th style="width:40px" title="Drag to reorder"><i class="bi bi-grip-vertical text-secondary"></i></th>
                    <th>Item</th>
                    <th>Status &amp; Condition</th>
                    <th>Schedule</th>
                    <th>Bidding &amp; Winner</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="auction-sortable-body" data-offset="{{ $showingAll ? 0 : max(0, ($auctions->currentPage() - 1) * $perPage) }}">
            @forelse($auctions as $auction)
                @php
                    $sbClass  = ['draft'=>'sb-draft','active'=>'sb-active','ended'=>'sb-ended','cancelled'=>'sb-cancelled'];
                    $sbIcon   = ['draft'=>'bi-circle','active'=>'bi-circle-fill','ended'=>'bi-flag-fill','cancelled'=>'bi-x-circle-fill'];
                    $condClass = [
                        'damaged'              => 'cond-damaged',
                        'boxed-damaged'        => 'cond-damaged',
                        'no-box'               => 'cond-damaged',
                        'returned'             => 'cond-damaged',
                        'dented'               => 'cond-damaged',
                        'missing-accessories'  => 'cond-damaged',
                        'refurbished'          => 'cond-refurbished',
                        'as-is'                => 'cond-asis',
                    ];
                    $condLabels = [
                        'damaged'              => 'Damaged',
                        'boxed-damaged'        => 'Damaged Box',
                        'no-box'               => 'No Box',
                        'returned'             => 'Returned',
                        'dented'               => 'Dented',
                        'missing-accessories'  => 'Missing Accessories',
                        'refurbished'          => 'Refurbished',
                        'as-is'                => 'As-Is',
                    ];
                    $branchColors = ['Harare'=>'#f3e8ff:#7e22ce','Mutare'=>'#eff6ff:#1d4ed8','Bulawayo'=>'#f0fdf4:#15803d','Zambia'=>'#fff7ed:#c2410c'];
                    [$brBg,$brClr] = explode(':', $branchColors[$auction->branch] ?? '#f8fafc:#64748b');
                @endphp
                <tr data-id="{{ $auction->id }}">
                    {{-- 0. Checkbox --}}
                    <td style="width:30px;text-align:center;vertical-align:middle;">
                        <input type="checkbox" class="auction-checkbox" value="{{ $auction->id }}" onclick="updateBulkBar()">
                    </td>
                    {{-- 0b. Drag handle --}}
                    <td class="drag-handle" style="cursor:grab;color:#94a3b8;text-align:center;width:40px" title="Drag to reorder">
                        <i class="bi bi-grip-vertical" style="font-size:1.1rem"></i>
                    </td>
                    {{-- 1. Item --}}
                    <td>
                        <div class="auction-img-cell">
                            @if($auction->thumbnail)
                                <img src="{{ $auction->thumbnail }}" alt="" class="auction-thumb">
                            @else
                                <div class="auction-thumb-ph"><i class="bi bi-image"></i></div>
                            @endif
                            <div>
                                <p class="auction-item-title">{{ Str::limit($auction->title, 40) }}</p>
                                @if($auction->branch)
                                    <span style="display:inline-flex;align-items:center;gap:3px;background:{{ $brBg }};color:{{ $brClr }};border-radius:20px;padding:2px 9px;font-size:.65rem;font-weight:700;margin-bottom:3px">
                                        📍 {{ $auction->branch }}
                                    </span>
                                @endif
                                @if($auction->product)
                                    <p class="auction-item-sub"><i class="bi bi-link-45deg"></i> {{ Str::limit($auction->product->name, 28) }}</p>
                                @else
                                    <p class="auction-item-sub" style="color:#e2e8f0">No linked product</p>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- 2. Status & Condition --}}
                    <td>
                        <span class="status-badge-pill {{ $sbClass[$auction->status] ?? 'sb-draft' }}">
                            <i class="bi {{ $sbIcon[$auction->status] ?? 'bi-circle' }}" style="font-size:.55rem"></i>
                            {{ ucfirst($auction->status) }}
                        </span>
                        <div class="mt-1">
                            <span class="cond-badge {{ $condClass[$auction->condition] ?? 'cond-asis' }}">
                                {{ $condLabels[$auction->condition] ?? ucfirst(str_replace('-', ' ', $auction->condition)) }}
                            </span>
                        </div>
                        @if($auction->isActive())
                            <div class="mt-1">
                                <span class="auction-cd {{ $auction->timeRemainingSeconds() < 300 ? 'urgent' : '' }}"
                                      data-ends="{{ $auction->ends_at->toIso8601String() }}">
                                    <i class="bi bi-clock"></i>
                                    <span class="cd-text">–</span>
                                </span>
                            </div>
                        @endif
                    </td>

                    {{-- 3. Schedule --}}
                    <td style="min-width:150px">
                        <div style="display:flex;flex-direction:column;gap:6px">
                            {{-- Start --}}
                            <div>
                                <div style="font-size:.62rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;font-weight:600">Start</div>
                                <div style="font-size:.8rem;color:#0f172a;font-weight:700">{{ $auction->starts_at?->format('d M Y') ?? '–' }}</div>
                                <div style="font-size:.72rem;color:#64748b">{{ $auction->starts_at?->format('H:i') ?? '' }}</div>
                            </div>
                            <div style="height:1px;background:#f0f4f8;border-radius:1px"></div>
                            {{-- End --}}
                            <div>
                                <div style="font-size:.62rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;font-weight:600">End</div>
                                <div style="font-size:.8rem;color:#0f172a;font-weight:700">{{ $auction->ends_at?->format('d M Y') ?? '–' }}</div>
                                <div style="font-size:.72rem;color:#64748b">{{ $auction->ends_at?->format('H:i') ?? '' }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- 4. Bidding & Winner --}}
                    <td style="min-width:150px">
                        {{-- Current bid + count --}}
                        <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:3px">
                            <div class="bid-amount-cell">${{ number_format($auction->current_bid ?? $auction->starting_price, 2) }}</div>
                            <span style="fontSize:.72rem;color:#7e22ce;font-weight:700">{{ $auction->bids_count }} bid{{ $auction->bids_count !== 1 ? 's' : '' }}</span>
                        </div>
                        <div class="bid-start-price mb-1">Start: ${{ number_format($auction->starting_price, 2) }}</div>
                        {{-- Winner --}}
                        @if($auction->winner)
                            <span class="winner-badge">🏆 {{ Str::limit($auction->winner->name, 14) }}</span>
                            <div style="font-size:.7rem;color:#94a3b8;margin-top:2px">${{ number_format($auction->winner_bid, 2) }}</div>
                        @elseif($auction->status === 'ended')
                            <span style="font-size:.72rem;color:#94a3b8">No winner</span>
                        @endif
                    </td>

                    {{-- 5. Actions --}}
                    <td>
                        <div class="auction-actions">
                            <a href="{{ route('admin.auctions.show', $auction) }}" class="action-btn" style="background:#f5f3ff;color:#7e22ce" title="View">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <a href="{{ route('admin.auctions.participants', $auction) }}" class="action-btn" style="background:#ecfdf5;color:#059669" title="Participants & Payments">
                                <i class="bi bi-people-fill"></i>
                            </a>
                            <a href="{{ route('admin.auctions.auction-stats', $auction) }}" class="action-btn" style="background:#f0f9ff;color:#0ea5e9" title="Analytics">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </a>
                            <a href="{{ route('admin.auctions.edit', $auction) }}" class="action-btn action-btn-edit" title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            @if($auction->status === 'active')
                                <form action="{{ route('admin.auctions.end', $auction) }}" method="POST" data-swal-confirm="End this auction now and set winner?">
                                    @csrf
                                    <button type="submit" class="action-btn action-btn-stop" title="End Auction">
                                        <i class="bi bi-stop-circle-fill"></i>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.auctions.destroy', $auction) }}" method="POST" data-swal-confirm="Delete this auction permanently?">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn action-btn-delete" title="Delete">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="auction-empty">
                            <span class="auction-empty-icon">🔨</span>
                            <h3>No auctions yet</h3>
                            <p class="mb-3">Create your first auction to start selling damaged items</p>
                            <a href="{{ route('admin.auctions.create') }}" class="auction-hero-btn" style="display:inline-flex">
                                <i class="bi bi-plus-circle-fill"></i> Create Auction
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($showingAll || $auctions->hasPages())
        <div class="auction-pagination">
            <div class="auction-pagination-info">
                @if($showingAll)
                    Showing <strong>all {{ $auctions->total() }}</strong> auction{{ $auctions->total() !== 1 ? 's' : '' }}
                @else
                    Showing <strong>{{ $auctions->firstItem() }}–{{ $auctions->lastItem() }}</strong>
                    of <strong>{{ $auctions->total() }}</strong> auctions
                @endif
            </div>
            @if(!$showingAll)
                {{ $auctions->withQueryString()->links() }}
            @endif
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function changePerPage(value) {
    // Build current URL params, override per_page, reset to page 1
    const params = new URLSearchParams(window.location.search);
    if (value == 0) {
        params.set('per_page', 0);
    } else {
        params.set('per_page', value);
    }
    params.delete('page'); // reset to first page
    window.location.href = '{{ route('admin.auctions.index') }}?' + params.toString();
}
</script>
<script>
// ── Countdown timers ─────────────────────────────────────────────
document.querySelectorAll('.auction-cd[data-ends]').forEach(el => {
    const ends = new Date(el.dataset.ends);
    const text = el.querySelector('.cd-text');
    const tick = () => {
        const diff = Math.max(0, Math.floor((ends - Date.now()) / 1000));
        if (diff === 0) { text.textContent = 'Ended'; el.classList.remove('urgent'); return; }
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        text.textContent = h > 0
            ? `${h}h ${String(m).padStart(2,'0')}m`
            : `${m}m ${String(s).padStart(2,'0')}s`;
        if (diff < 300) el.classList.add('urgent');
    };
    tick();
    setInterval(tick, 1000);
});

// ── Drag-and-drop sort order ──────────────────────────────────────
const tbody = document.getElementById('auction-sortable-body');
if (tbody) {
    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            const rows   = tbody.querySelectorAll('tr[data-id]');
            const offset = parseInt(tbody.dataset.offset || 0);
            const order  = Array.from(rows).map((row, index) => ({
                id: parseInt(row.dataset.id),
                sort_order: offset + index
            }));

            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;background:#302b63;color:#fff;padding:10px 18px;border-radius:10px;font-size:.85rem;box-shadow:0 4px 20px rgba(0,0,0,.25);display:flex;align-items:center;gap:8px';
            toast.innerHTML = '<span style="animation:spin 1s linear infinite;display:inline-block">⟳</span> Saving order…';
            document.body.appendChild(toast);

            fetch('{{ route('admin.auctions.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ order }),
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    toast.innerHTML = '✅ Order saved!';
                    toast.style.background = '#059669';
                } else {
                    throw new Error('Server returned failure');
                }
                setTimeout(() => toast.remove(), 2000);
            })
            .catch(err => {
                toast.innerHTML = '❌ Failed to save: ' + err.message;
                toast.style.background = '#dc2626';
                setTimeout(() => toast.remove(), 4000);
            });
        }
    });
}

// ── Bulk action logic ──
function toggleAllCheckboxes(master) {
    document.querySelectorAll('.auction-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.auction-checkbox:checked');
    const bar = document.getElementById('bulkActionBar');
    const countEl = document.getElementById('bulkCount');
    const idsEl = document.getElementById('bulkIds');

    if (checked.length > 0) {
        bar.style.display = 'flex';
        countEl.textContent = checked.length;
        idsEl.value = JSON.stringify(Array.from(checked).map(cb => parseInt(cb.value)));
    } else {
        bar.style.display = 'none';
    }
}

// Show/hide branch and extend fields based on action
document.getElementById('bulkActionSelect')?.addEventListener('change', function() {
    document.getElementById('bulkBranch').style.display = this.value === 'change_branch' ? '' : 'none';
    document.getElementById('bulkExtendHours').style.display = this.value === 'extend_time' ? '' : 'none';
});

// Convert JSON ids array to individual form inputs before submit
document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
    const idsJson = document.getElementById('bulkIds').value;
    if (!idsJson) { e.preventDefault(); return; }
    const ids = JSON.parse(idsJson);
    // Remove old hidden inputs
    this.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
    // Add individual inputs for Laravel array
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
        this.appendChild(input);
    });
    // Remove the JSON field
    document.getElementById('bulkIds').removeAttribute('name');
});
</script>
<style>
.sortable-ghost { opacity: .4; background: #ede9fe !important; }
.drag-handle:active { cursor: grabbing; }
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush
