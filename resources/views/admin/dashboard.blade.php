@extends('admin.layout')

@section('title', 'Dashboard - Admin Panel')

@push('styles')
<style>
/* ── KPI Cards ── */
.kpi-card {
    background:#fff;
    border-radius:14px;
    padding:20px 22px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    border-left:4px solid transparent;
    display:flex;
    align-items:center;
    gap:16px;
    transition:transform .15s,box-shadow .15s;
}
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 24px rgba(0,0,0,.1); }
.kpi-icon {
    width:52px;height:52px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.5rem;flex-shrink:0;
}
.kpi-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:2px; }
.kpi-value { font-size:1.65rem;font-weight:800;color:#0f172a;line-height:1; }
.kpi-sub   { font-size:.72rem;color:#64748b;margin-top:4px; }
.kpi-up    { color:#16a34a; }
.kpi-down  { color:#dc2626; }

/* ── Chart panels ── */
.chart-card {
    background:#fff;border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);overflow:hidden;
}
.chart-header {
    background:linear-gradient(135deg,#0a2d6b,#1a5cb8);
    padding:12px 18px;display:flex;align-items:center;gap:8px;
}
.chart-header i   { color:#fff;font-size:.95rem; }
.chart-header span{ font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#fff; }
.chart-body { padding:18px; }

/* ── Tables ── */
.dash-table { width:100%;border-collapse:collapse; }
.dash-table th { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:8px 10px;border-bottom:2px solid #f1f5f9;white-space:nowrap; }
.dash-table td { font-size:.82rem;padding:9px 10px;border-bottom:1px solid #f8fafc;color:#334155;vertical-align:middle; }
.dash-table tbody tr:hover { background:#f8fafc; }

/* ── Hero ── */
.dash-hero {
    background:linear-gradient(135deg,#0a2d6b 0%,#1a5cb8 60%,#2563eb 100%);
    border-radius:16px;padding:28px 32px;margin-bottom:24px;
    display:flex;align-items:center;justify-content:space-between;
    box-shadow:0 8px 32px rgba(10,45,107,.25);
}
.dash-hero-text h2 { font-size:1.55rem;font-weight:800;color:#fff;margin:0 0 4px; }
.dash-hero-text p  { font-size:.85rem;color:rgba(255,255,255,.7);margin:0; }
.dash-hero-stats   { display:flex;gap:28px; }
.dash-hero-stat    { text-align:center; }
.dash-hero-stat .v { font-size:1.5rem;font-weight:800;color:#fff; }
.dash-hero-stat .l { font-size:.65rem;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.6); }

/* ── Status pills ── */
.status-pill {
    display:inline-block;padding:2px 10px;border-radius:20px;
    font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;
}
/* ── Date filter bar ── */
.filter-bar {
    background:#fff;border-radius:12px;padding:12px 18px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:20px;
    display:flex;align-items:center;flex-wrap:wrap;gap:8px;
}
.filter-bar .range-btn {
    padding:5px 14px;border-radius:20px;border:1px solid #e2e8f0;
    font-size:.75rem;font-weight:600;cursor:pointer;background:#f8fafc;
    color:#475569;transition:all .15s;
}
.filter-bar .range-btn:hover,.filter-bar .range-btn.active {
    background:#0a2d6b;color:#fff;border-color:#0a2d6b;
}
.filter-bar input[type=date] {
    border:1px solid #e2e8f0;border-radius:8px;padding:4px 10px;
    font-size:.75rem;color:#475569;background:#f8fafc;
}
.filter-bar select {
    border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;
    font-size:.75rem;color:#475569;background:#f8fafc;
}
</style>
@endpush

@section('content')

{{-- ══════════════════ ADMIN ADVANCED DASHBOARD ══════════════════ --}}
@if($isAdmin)

@php
    $statusColors = [
        'pending'    => '#f59e0b',
        'processing' => '#3b82f6',
        'completed'  => '#10b981',
        'delivered'  => '#10b981',
        'cancelled'  => '#ef4444',
        'refunded'   => '#8b5cf6',
    ];
    $pmLabels = array_keys($paymentMethods);
    $pmCounts = array_values($paymentMethods);
    $rangeLabel = match($range) {
        '24h'   => 'Past 24 Hours',
        '7d'    => 'Past 7 Days',
        '30d'   => 'Past 30 Days',
        '90d'   => 'Past 90 Days',
        '365d'  => 'Past Year',
        'custom'=> 'Custom Range',
        default => 'All Time',
    };
@endphp

{{-- ── Date & Staff Filter Bar ── --}}
<form method="GET" action="{{ route('admin.dashboard') }}" id="filterForm" class="filter-bar">
    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-right:4px;">Period:</span>
    @foreach(['24h'=>'24h','7d'=>'7 Days','30d'=>'30 Days','90d'=>'90 Days','365d'=>'1 Year','all'=>'All Time'] as $val=>$lbl)
        {{-- Preset buttons clear custom date inputs so they don't get sent with the request --}}
        <button type="submit" name="range" value="{{ $val }}"
            class="range-btn {{ $range===$val ? 'active' : '' }}"
            onclick="document.getElementById('dfrom').value='';document.getElementById('dto').value=''">
            {{ $lbl }}
        </button>
    @endforeach
    <button type="button" class="range-btn {{ $range==='custom' ? 'active' : '' }}"
        onclick="document.getElementById('customDates').style.display=(document.getElementById('customDates').style.display==='none'?'flex':'none')">
        Custom
    </button>
    <div id="customDates" style="display:{{ $range==='custom' ? 'flex' : 'none' }};align-items:center;gap:6px;flex-wrap:wrap;">
        <input type="date" name="date_from" id="dfrom" value="{{ $range==='custom' && $dateFrom ? $dateFrom->format('Y-m-d') : '' }}" />
        <span style="font-size:.75rem;color:#94a3b8;">→</span>
        <input type="date" name="date_to"   id="dto"   value="{{ $range==='custom' && $dateTo  ? $dateTo->format('Y-m-d')  : '' }}" />
        <button type="submit" name="range" value="custom" class="range-btn">Apply</button>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
        @if($range !== 'all')
            <a href="{{ route('admin.dashboard') }}" style="font-size:.72rem;color:#ef4444;text-decoration:none;font-weight:600;">✕ Reset</a>
        @endif
    </div>
</form>


{{-- Hero --}}
<div class="dash-hero">
    <div class="dash-hero-text">
        <h2><i class="bi bi-speedometer2 me-2"></i>Command Centre</h2>
        <p>{{ now()->format('l, d F Y') }} — Real-time system overview</p>
    </div>
    <div class="dash-hero-stats">
        <div class="dash-hero-stat">
            <div class="v">{{ number_format($stats['total_orders']) }}</div>
            <div class="l">Total Orders</div>
        </div>
        <div class="dash-hero-stat">
            <div class="v">
                @foreach($revenueByCurrency as $rc)
                    <div style="font-size:{{ $loop->first ? '1.5rem' : '1rem' }};line-height:1.2;">{{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 0) }}</div>
                @endforeach
            </div>
            <div class="l">All-time Revenue</div>
        </div>
        <div class="dash-hero-stat">
            <div class="v">{{ number_format($stats['total_customers']) }}</div>
            <div class="l">Customers</div>
        </div>
    </div>
</div>

{{-- ── KPI Row ── --}}
<div class="row g-3 mb-4">
    {{-- Total Orders --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#3b82f6;">
            <div class="kpi-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-cart3"></i></div>
            <div>
                <div class="kpi-label">Orders</div>
                <div class="kpi-value">{{ number_format($stats['total_orders']) }}</div>
                <div class="kpi-sub">{{ $stats['pending_orders'] }} pending</div>
            </div>
        </div>
    </div>
    {{-- Revenue --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#10b981;">
            <div class="kpi-icon" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="kpi-label">All-time Revenue</div>
                @foreach($revenueByCurrency as $rc)
                <div style="font-size:{{ $loop->first ? '1.1rem' : '.85rem' }};font-weight:{{ $loop->first ? 800 : 600 }};color:{{ $loop->first ? '#0f172a' : '#64748b' }};line-height:1.3;">
                    {{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 0) }}
                </div>
                @endforeach
                @if($revenueByCurrency->isEmpty())<div class="kpi-sub">No paid orders yet</div>@endif
            </div>
        </div>
    </div>
    {{-- This Month --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#8b5cf6;">
            <div class="kpi-icon" style="background:#faf5ff;color:#8b5cf6;"><i class="bi bi-calendar-month"></i></div>
            <div>
                <div class="kpi-label">This Month</div>
                @foreach($thisMonthByCurrency as $rc)
                <div style="font-size:{{ $loop->first ? '1.1rem' : '.85rem' }};font-weight:{{ $loop->first ? 800 : 600 }};color:{{ $loop->first ? '#0f172a' : '#64748b' }};line-height:1.3;">
                    {{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 0) }}
                </div>
                @endforeach
                @if($thisMonthByCurrency->isEmpty())<div class="kpi-sub" style="color:#94a3b8;">No paid orders yet</div>@endif
                @if($stats['rev_change'] !== null)
                <div class="kpi-sub {{ $stats['rev_change'] >= 0 ? 'kpi-up' : 'kpi-down' }}" style="margin-top:4px;">
                    <i class="bi bi-arrow-{{ $stats['rev_change'] >= 0 ? 'up' : 'down' }}-short"></i>{{ abs($stats['rev_change']) }}% vs last month
                </div>
                @endif
            </div>
        </div>
    </div>
    {{-- Customers --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-icon" style="background:#fffbeb;color:#f59e0b;"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="kpi-label">Customers</div>
                <div class="kpi-value">{{ number_format($stats['total_customers']) }}</div>
                <div class="kpi-sub">+{{ $stats['new_customers_month'] }} this month</div>
            </div>
        </div>
    </div>
    {{-- Products --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#06b6d4;">
            <div class="kpi-icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="kpi-label">Products</div>
                <div class="kpi-value">{{ number_format($stats['total_products']) }}</div>
                <div class="kpi-sub">{{ $stats['active_products'] }} active</div>
            </div>
        </div>
    </div>
    {{-- Refunds --}}
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card" style="border-left-color:#ef4444;">
            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;"><i class="bi bi-arrow-return-left"></i></div>
            <div>
                <div class="kpi-label">Refunds</div>
                <div class="kpi-value">{{ number_format($stats['total_refunds']) }}</div>
                <div class="kpi-sub">{{ $stats['completed_orders'] }} completed orders</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Row 1 ── --}}
<div class="row g-3 mb-4">
    {{-- Revenue Line Chart --}}
    <div class="col-lg-8">
        <div class="chart-card h-100">
            <div class="chart-header">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Monthly Revenue — Last 12 Months (per currency)</span>
            </div>
            <div class="chart-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    {{-- Order Status Doughnut --}}
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="chart-header">
                <i class="bi bi-pie-chart-fill"></i>
                <span>Orders by Status</span>
            </div>
            <div class="chart-body d-flex align-items-center justify-content-center" style="min-height:240px;">
                <canvas id="statusChart" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Row 2 ── --}}
<div class="row g-3 mb-4">
    {{-- Monthly Orders Bar --}}
    <div class="col-lg-8">
        <div class="chart-card h-100">
            <div class="chart-header">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Monthly Order Volume — Last 12 Months</span>
            </div>
            <div class="chart-body">
                <canvas id="ordersChart" height="100"></canvas>
            </div>
        </div>
    </div>
    {{-- Payment Method Pie --}}
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="chart-header">
                <i class="bi bi-credit-card-2-front-fill"></i>
                <span>Payment Methods</span>
            </div>
            <div class="chart-body d-flex align-items-center justify-content-center" style="min-height:240px;">
                <canvas id="paymentChart" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Bottom Row ── --}}
<div class="row g-3 mb-4">
    {{-- Top Products — Tabbed (Paid / Demand) --}}
    <div class="col-lg-6">
        <div class="chart-card h-100">
            <div class="chart-header" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-trophy-fill"></i>
                    <span>Top 10 Products</span>
                    <span style="margin-left:auto;font-size:.65rem;opacity:.7;">{{ $rangeLabel }}</span>
                </div>
                <div style="display:flex;gap:4px;">
                    <button onclick="showTab('tabPaid','tabDemand',this)" id="btnPaid"
                        style="padding:3px 12px;border-radius:20px;border:none;font-size:.68rem;font-weight:700;
                               background:rgba(255,255,255,.25);color:#fff;cursor:pointer;">✅ Paid Sales</button>
                    <button onclick="showTab('tabDemand','tabPaid',this)" id="btnDemand"
                        style="padding:3px 12px;border-radius:20px;border:none;font-size:.68rem;font-weight:700;
                               background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);cursor:pointer;">🛒 Pending Demand</button>
                </div>
            </div>
            <div class="chart-body p-0">
                @php
                    $prodTableTpl = function($rows, $emptyMsg) { return $rows; };
                @endphp
                {{-- Paid Tab --}}
                <div id="tabPaid" style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead><tr><th>#</th><th>Product</th><th>SKU</th><th style="text-align:right;">Qty Sold</th><th style="text-align:right;">Revenue</th></tr></thead>
                        <tbody>
                            @forelse($topProductsPaid as $i => $p)
                            <tr>
                                <td>@if($i<3)<span style="font-size:.9rem;">{{ ['🥇','🥈','🥉'][$i] }}</span>@else<span style="color:#94a3b8;">{{ $i+1 }}</span>@endif</td>
                                <td style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->name }}</td>
                                <td style="color:#94a3b8;font-family:monospace;font-size:.75rem;">{{ $p->sku }}</td>
                                <td style="text-align:right;font-weight:700;color:#0f3d8c;">{{ number_format($p->total_qty) }}</td>
                                <td style="text-align:right;color:#10b981;font-weight:600;">{{ number_format($p->total_revenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No paid sales in this period</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Demand Tab --}}
                <div id="tabDemand" style="display:none;overflow-x:auto;">
                    <table class="dash-table">
                        <thead><tr><th>#</th><th>Product</th><th>SKU</th><th style="text-align:right;">Qty Ordered</th><th style="text-align:right;">Value</th></tr></thead>
                        <tbody>
                            @forelse($topProductsDemand as $i => $p)
                            <tr>
                                <td>@if($i<3)<span style="font-size:.9rem;">{{ ['🥇','🥈','🥉'][$i] }}</span>@else<span style="color:#94a3b8;">{{ $i+1 }}</span>@endif</td>
                                <td style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->name }}</td>
                                <td style="color:#94a3b8;font-family:monospace;font-size:.75rem;">{{ $p->sku }}</td>
                                <td style="text-align:right;font-weight:700;color:#f59e0b;">{{ number_format($p->total_qty) }}</td>
                                <td style="text-align:right;color:#f59e0b;font-weight:600;">{{ number_format($p->total_revenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No pending/unpaid demand in this period</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Paid Orders --}}
    <div class="col-lg-6">
        <div class="chart-card h-100">
            <div class="chart-header">
                <i class="bi bi-clock-history"></i>
                <span>Recent Paid Orders</span>
                <span style="margin-left:auto;font-size:.65rem;opacity:.7;">{{ $rangeLabel }}</span>
            </div>
            <div class="chart-body p-0">
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th style="text-align:right;">Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->order_number) }}"
                                       style="font-weight:700;color:#0f3d8c;text-decoration:none;">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td>{{ $order->consumer->name ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $sName = strtolower($order->order_status->name ?? 'pending');
                                        $sColor = match(true) {
                                            str_contains($sName,'complet') || str_contains($sName,'deliver') => '#10b981',
                                            str_contains($sName,'cancel') => '#ef4444',
                                            str_contains($sName,'process') => '#3b82f6',
                                            default => '#f59e0b',
                                        };
                                    @endphp
                                    <span class="status-pill" style="background:{{ $sColor }}20;color:{{ $sColor }};">
                                        {{ $order->order_status->name ?? 'Pending' }}
                                    </span>
                                </td>
                                <td style="text-align:right;font-weight:600;">
                                    {{ $order->currency_symbol ?? 'R' }} {{ number_format($order->total * ($order->exchange_rate ?? 1), 2) }}
                                </td>
                                <td style="color:#94a3b8;font-size:.75rem;">{{ $order->created_at->format('d M H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No orders yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Staff Performance Leaderboard ── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="chart-card" id="staff-card">
            <div class="chart-header" style="background:linear-gradient(135deg,#0a2d6b,#1a5cb8);flex-wrap:wrap;gap:6px;">
                <i class="bi bi-person-badge-fill"></i>
                <span>Staff Performance — Paid Orders &amp; Documents</span>
                <span style="font-size:.65rem;opacity:.7;">(Excludes unpaid orders)</span>
                <form method="GET" action="{{ route('admin.dashboard') }}" style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                    {{-- Always preserve the current period so selecting a staff member doesn't reset the date --}}
                    <input type="hidden" name="range" value="{{ $range }}">
                    @if($range === 'custom')
                        @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">@endif
                        @if($dateTo)<input type="hidden"  name="date_to"   value="{{ $dateTo->format('Y-m-d')  }}">@endif
                    @endif

                    <select name="staff_user" onchange="this.form.submit()"
                        style="border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:4px 10px;
                               font-size:.75rem;color:#fff;background:rgba(255,255,255,.12);cursor:pointer;">
                        <option value="" style="color:#000;" {{ is_null($selectedStaffUser) ? 'selected' : '' }}>— Select Staff —</option>
                        <option value="all" style="color:#000;" {{ $selectedStaffUser === 'all' ? 'selected' : '' }}>All Staff</option>
                        @foreach($staffUsers as $su)
                            <option value="{{ $su->id }}" {{ $selectedStaffUser == $su->id ? 'selected' : '' }}
                                style="color:#000;">{{ $su->name }}</option>
                        @endforeach
                    </select>
                    @if($selectedStaffUser)
                        <a href="{{ route('admin.dashboard', array_filter(['range'=>$range,'date_from'=>optional($dateFrom)->format('Y-m-d'),'date_to'=>$range==='custom'?optional($dateTo)->format('Y-m-d'):null])) }}"
                           style="font-size:.72rem;color:rgba(255,200,200,.9);text-decoration:none;font-weight:600;">✕ All</a>
                    @endif
                </form>
            </div>
            <div class="chart-body p-0">
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Staff Member</th>
                                <th style="text-align:center;">Paid Orders</th>
                                <th style="text-align:right;">Revenue</th>
                                <th>Order Status Breakdown</th>
                                <th style="text-align:center;">Quotations</th>
                                <th style="text-align:center;">Invoices</th>
                                <th style="text-align:center;">Proformas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffPerformance as $i => $staff)
                            @php
                                $statusPillColors = [
                                    'pending'    => ['bg'=>'#fff7ed','txt'=>'#f59e0b'],
                                    'processing' => ['bg'=>'#eff6ff','txt'=>'#3b82f6'],
                                    'completed'  => ['bg'=>'#f0fdf4','txt'=>'#10b981'],
                                    'delivered'  => ['bg'=>'#f0fdf4','txt'=>'#10b981'],
                                    'cancelled'  => ['bg'=>'#fef2f2','txt'=>'#ef4444'],
                                    'refunded'   => ['bg'=>'#faf5ff','txt'=>'#8b5cf6'],
                                ];
                            @endphp
                            <tr>
                                <td style="color:#94a3b8;font-size:.8rem;">{{ $i + 1 }}</td>
                                <td>
                                    <div style="font-weight:700;color:#0f172a;font-size:.88rem;">{{ $staff['name'] }}</div>
                                    <div style="font-size:.7rem;color:#94a3b8;">{{ $staff['email'] }}</div>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-size:1.1rem;font-weight:800;color:#0f3d8c;">
                                        {{ number_format($staff['total_paid_orders']) }}
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @forelse($staff['revenue_by_currency'] as $rc)
                                    <div style="font-weight:{{ $loop->first ? 700 : 500 }};color:{{ $loop->first ? '#10b981' : '#64748b' }};font-size:{{ $loop->first ? '.88rem' : '.75rem' }};">
                                        {{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 2) }}
                                    </div>
                                    @empty
                                    <span style="color:#cbd5e1;">—</span>
                                    @endforelse
                                </td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                        @forelse($staff['orders_by_status'] as $statusName => $row)
                                        @php
                                            $sKey   = strtolower($statusName);
                                            $colors = $statusPillColors[$sKey] ?? ['bg'=>'#f1f5f9','txt'=>'#64748b'];
                                        @endphp
                                        <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:.67rem;font-weight:700;background:{{ $colors['bg'] }};color:{{ $colors['txt'] }};">
                                            {{ $statusName }}
                                            <span style="background:{{ $colors['txt'] }};color:#fff;border-radius:50%;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:.6rem;">{{ $row->cnt }}</span>
                                        </span>
                                        @empty
                                        <span style="color:#94a3b8;font-size:.75rem;">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    @if($staff['quotations'] > 0)
                                        <span style="background:#eff6ff;color:#3b82f6;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">
                                            {{ $staff['quotations'] }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($staff['invoices'] > 0)
                                        <span style="background:#f0fdf4;color:#10b981;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">
                                            {{ $staff['invoices'] }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($staff['proformas'] > 0)
                                        <span style="background:#faf5ff;color:#8b5cf6;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">
                                            {{ $staff['proformas'] }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">
                                    <i class="bi bi-person-lines-fill" style="font-size:2rem;display:block;margin-bottom:8px;color:#cbd5e1;"></i>
                                    Select a staff member above to view their performance
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels  = @json($monthLabels);
    const orders  = @json($monthlyOrders);
    const revByCurrency = @json($monthlyRevenueByCurrency);

    // ── Chart defaults ──
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';

    // ── Colour palette per currency ──
    const palette = [
        '#1a5cb8', '#10b981', '#f59e0b', '#ef4444',
        '#8b5cf6', '#06b6d4', '#f97316', '#64748b',
    ];

    // Build one dataset per currency
    const revenueDatasets = Object.entries(revByCurrency).map(([currency, data], i) => ({
        label: currency + ' Revenue',
        data,
        borderColor: palette[i % palette.length],
        backgroundColor: palette[i % palette.length] + '14',
        borderWidth: 2.5,
        pointBackgroundColor: palette[i % palette.length],
        pointRadius: 4,
        tension: 0.4,
        fill: Object.keys(revByCurrency).length === 1, // fill only if single currency
    }));

    // ── Revenue Line Chart ──
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels, datasets: revenueDatasets },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: revenueDatasets.length > 1,
                    position: 'top',
                    labels: { boxWidth: 12, padding: 12 }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + Number(ctx.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    }
                }
            },
            scales: {
                y: {
                    ticks: { callback: v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }) },
                    grid: { color: '#f1f5f9' }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Order Status Doughnut ──
    const statusLabels = @json(array_keys($orderStatuses));
    const statusData   = @json(array_values($orderStatuses));
    const statusTotal  = statusData.reduce((a,b) => a+b, 0);
    const statusPalette = ['#f59e0b','#3b82f6','#10b981','#ef4444','#8b5cf6','#06b6d4','#f97316'];
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{ data: statusData,
                backgroundColor: statusPalette.slice(0, statusLabels.length),
                borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12,
                    generateLabels: chart => chart.data.labels.map((lbl,i) => ({
                        text: `${lbl}: ${statusData[i]} (${statusTotal>0?((statusData[i]/statusTotal)*100).toFixed(1):0}%)`,
                        fillStyle: statusPalette[i % statusPalette.length],
                        hidden: false, index: i
                    }))
                }},
                tooltip: { callbacks: { label: ctx =>
                    `${ctx.label}: ${ctx.raw} (${statusTotal>0?((ctx.raw/statusTotal)*100).toFixed(1):0}%)`
                }}
            },
            cutout: '62%'
        }
    });

    // ── Monthly Orders Bar Chart ──
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Orders',
                data: orders,
                backgroundColor: 'rgba(37,99,235,.75)',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Payment Method Pie ──
    const pmLabels = @json($pmLabels);
    const pmData   = @json($pmCounts);
    const pmTotal  = pmData.reduce((a,b) => a+b, 0);
    const pmColors = ['#0f3d8c','#1a5cb8','#2563eb','#60a5fa','#93c5fd','#bfdbfe'];
    new Chart(document.getElementById('paymentChart'), {
        type: 'pie',
        data: {
            labels: pmLabels.map(l => l.replace(/_/g,' ').toUpperCase()),
            datasets: [{ data: pmData,
                backgroundColor: pmColors.slice(0, pmLabels.length),
                borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12,
                    generateLabels: chart => chart.data.labels.map((lbl,i) => ({
                        text: `${lbl}: ${pmData[i]} (${pmTotal>0?((pmData[i]/pmTotal)*100).toFixed(1):0}%)`,
                        fillStyle: pmColors[i % pmColors.length],
                        hidden: false, index: i
                    }))
                }},
                tooltip: { callbacks: { label: ctx =>
                    `${ctx.label}: ${ctx.raw} (${pmTotal>0?((ctx.raw/pmTotal)*100).toFixed(1):0}%)`
                }}
            }
        }
    });

    // ── Tab switcher for Top Products ──
    window.showTab = function(show, hide, btn) {
        document.getElementById(show).style.display='block';
        document.getElementById(hide).style.display='none';
        // update button styles
        btn.style.background='rgba(255,255,255,.25)';
        btn.style.color='#fff';
        const other = document.getElementById(show==='tabPaid'?'btnDemand':'btnPaid');
        other.style.background='rgba(255,255,255,.08)';
        other.style.color='rgba(255,255,255,.7)';
    };
    // ── Auto-scroll to staff card when a user filter is active ──
    (function() {
        if (new URLSearchParams(location.search).get('staff_user')) {
            const el = document.getElementById('staff-card');
            if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 120);
        }
    })();
})();
</script>
@endpush

{{-- ══════════════════ NON-ADMIN SIMPLE DASHBOARD ══════════════════ --}}
@else

<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Orders</h6>
                        <h2>{{ number_format($stats['total_orders']) }}</h2>
                    </div>
                    <i class="bi bi-cart" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Pending Orders</h6>
                        <h2>{{ number_format($stats['pending_orders']) }}</h2>
                    </div>
                    <i class="bi bi-clock" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Revenue</h6>
                        @forelse($revenueByCurrency as $rc)
                            <h2 style="font-size:{{ $loop->first ? '1.5rem' : '1rem' }};">
                                {{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 0) }}
                            </h2>
                        @empty
                            <h2>—</h2>
                        @endforelse
                    </div>
                    <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Customers</h6>
                        <h2>{{ number_format($stats['total_customers']) }}</h2>
                    </div>
                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order #</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->order_number) }}" class="fw-bold text-decoration-none">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td>{{ $order->consumer->name ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $order->order_status->name ?? 'pending')) }}">
                                {{ $order->order_status->name ?? 'Pending' }}
                            </span>
                        </td>
                        <td>
                            {{ $order->currency_symbol ?? 'R' }} {{ number_format($order->total * ($order->exchange_rate ?? 1), 2) }}
                        </td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->order_number) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No recent orders</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endif

@endsection
