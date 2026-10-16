@extends('admin.layout')

@section('title', 'Staff Performance - Admin Panel')

@push('styles')
@include('admin.staff-performance._styles')
@endpush

@section('content')
<div class="sp-hero">
    <div>
        <h2><i class="bi bi-trophy-fill me-2"></i>Staff Performance</h2>
        <p>Paid orders &amp; documents — excludes unpaid and cancelled orders. Ranked by USD revenue.</p>
    </div>
    <div class="sp-hero-stats">
        <div><div class="v">{{ number_format($grand['orders']) }}</div><div class="l">Paid orders</div></div>
        <div><div class="v">${{ number_format($grand['usd'], 2) }}</div><div class="l">Total (USD)</div></div>
    </div>
</div>

@include('admin.staff-performance._filter', ['action' => route('admin.staff-performance.index')])

<div class="sp-card">
    <div style="overflow-x:auto;">
        <table class="sp-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Staff Member</th>
                    <th style="text-align:center;">Paid Orders</th>
                    <th style="text-align:right;">Revenue (by currency)</th>
                    <th style="text-align:right;">USD (recorded)</th>
                    <th style="text-align:right;">Avg Order (USD)</th>
                    <th>Order Status Breakdown</th>
                    <th style="text-align:center;">Quotes</th>
                    <th style="text-align:center;">Invoices</th>
                    <th style="text-align:center;">Proformas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $i => $s)
                @php $url = route('admin.staff-performance.show', array_merge(['user' => $s['id']], request()->only('period','month','year'))); @endphp
                <tr onclick="window.location='{{ $url }}'" style="cursor:pointer;">
                    <td>
                        @if($i < 3 && $s['usd_total'] > 0)
                            <span class="medal m{{ $i + 1 }}">{{ $i + 1 }}</span>
                        @else
                            <span style="color:#94a3b8;">{{ $i + 1 }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:700;color:#0f172a;">{{ $s['name'] }}</div>
                        <div style="font-size:.7rem;color:#94a3b8;">{{ $s['email'] }}</div>
                    </td>
                    <td style="text-align:center;font-size:1.05rem;font-weight:800;color:#0f3d8c;">{{ number_format($s['total_paid_orders']) }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        @forelse($s['revenue_by_currency'] as $rc)
                            <div style="font-weight:{{ $loop->first ? 700 : 500 }};color:{{ $loop->first ? '#10b981' : '#64748b' }};">
                                {{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 2) }}
                                <span style="font-size:.65rem;color:#94a3b8;">({{ $rc->orders_count }})</span>
                            </div>
                        @empty <span style="color:#cbd5e1;">—</span> @endforelse
                    </td>
                    <td style="text-align:right;font-weight:800;color:#0f172a;">${{ number_format($s['usd_total'], 2) }}</td>
                    <td style="text-align:right;color:#64748b;">${{ number_format($s['avg_order_usd'], 2) }}</td>
                    <td>
                        @forelse($s['orders_by_status'] as $name => $cnt)
                            @include('admin.staff-performance._status-pill', ['name' => $name, 'count' => $cnt])
                        @empty <span style="color:#cbd5e1;">—</span> @endforelse
                    </td>
                    <td style="text-align:center;">{{ $s['quotations'] ?: '—' }}</td>
                    <td style="text-align:center;">{{ $s['invoices'] ?: '—' }}</td>
                    <td style="text-align:center;">{{ $s['proformas'] ?: '—' }}</td>
                    <td><a href="{{ $url }}" class="sp-link">Audit orders →</a></td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;padding:40px;color:#94a3b8;">No staff found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p style="font-size:.72rem;color:#94a3b8;margin-top:10px;">
    Revenue is credited to whoever made the first payment-confirming status change on an order (delivery and cancellation changes do not count).
    Order totals are recorded in USD; local-currency amounts are converted for display using the order's exchange rate. Click a staff member to see every order behind their number.
</p>
@endsection
