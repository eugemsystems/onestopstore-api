@extends('admin.layout')

@section('title', $user->name . ' – Staff Performance')

@push('styles')
@include('admin.staff-performance._styles')
@endpush

@section('content')
@php $maxUsd = max(1, (float) $trend->max('usd')); @endphp

<div class="sp-hero">
    <div>
        <a href="{{ route('admin.staff-performance.index', ['period' => $period, 'month' => $month, 'year' => $year]) }}" style="color:rgba(255,255,255,.8);font-size:.75rem;text-decoration:none;">← All staff</a>
        <h2 style="margin-top:4px;">{{ $user->name }}</h2>
        <p>{{ $user->email }} — order audit for {{ $label }}</p>
    </div>
    <div class="sp-hero-stats">
        <div><div class="v">{{ number_format($summary['total_paid_orders']) }}</div><div class="l">Paid orders</div></div>
        <div><div class="v">${{ number_format($summary['usd_total'], 2) }}</div><div class="l">USD equivalent</div></div>
    </div>
</div>

@include('admin.staff-performance._filter', ['action' => route('admin.staff-performance.show', $user->id)])

<div class="kpis">
    <div class="kpi"><div class="l">Paid orders credited</div><div class="v">{{ number_format($summary['total_paid_orders']) }}</div></div>
    <div class="kpi"><div class="l">Revenue (USD eq.)</div><div class="v">${{ number_format($summary['usd_total'], 2) }}</div></div>
    <div class="kpi"><div class="l">Average order (USD)</div><div class="v">${{ number_format($summary['avg_order_usd'], 2) }}</div></div>
    <div class="kpi">
        <div class="l">By currency</div>
        @forelse($summary['revenue_by_currency'] as $rc)
            <div class="s"><b>{{ $rc->currency_symbol ?? $rc->currency }} {{ number_format($rc->total_amount, 2) }}</b> · {{ $rc->orders_count }} orders</div>
        @empty <div class="s">—</div> @endforelse
    </div>
    <div class="kpi">
        <div class="l">Documents</div>
        <div class="s">Quotes {{ $summary['quotations'] }} · Invoices {{ $summary['invoices'] }} · Proformas {{ $summary['proformas'] }}</div>
    </div>
    <div class="kpi">
        <div class="l">Status breakdown</div>
        @forelse($summary['orders_by_status'] as $n => $c)@include('admin.staff-performance._status-pill', ['name' => $n, 'count' => $c])@empty <div class="s">—</div> @endforelse
    </div>
</div>

@if($trend->isNotEmpty())
<div class="sp-card">
    <div class="sp-card-h">{{ $period === 'year' ? 'Monthly' : 'Daily' }} revenue (USD equivalent)</div>
    <div style="padding:14px 18px;">
        @foreach($trend as $t)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;font-size:.74rem;">
            <span style="width:80px;color:#64748b;">{{ $t->bucket }}</span>
            <div class="bar" style="flex:1;"><i style="width:{{ round($t->usd / $maxUsd * 100) }}%"></i></div>
            <span style="width:150px;text-align:right;"><b>${{ number_format($t->usd, 2) }}</b> · {{ $t->cnt }} orders</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="sp-card">
    <div class="sp-card-h">Orders credited to {{ $user->name }} — {{ number_format($orders->total()) }} total</div>
    <div style="overflow-x:auto;">
        <table class="sp-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Placed</th>
                    <th>Customer</th>
                    <th style="text-align:right;">Amount</th>
                    <th style="text-align:right;">Rate</th>
                    <th style="text-align:right;">USD Eq.</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Credit proof (who confirmed it)</th>
                    <th>Status history</th>
                    <th>Review flags</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $o->order_number) }}" target="_blank" style="font-weight:700;color:#0f3d8c;text-decoration:none;">#{{ $o->order_number }}</a></td>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y H:i') }}</td>
                    <td>
                        <div>{{ $o->customer_name ?? 'N/A' }}</div>
                        <div style="font-size:.68rem;color:#94a3b8;">{{ $o->customer_email }}</div>
                        @if($o->creator_name)<div style="font-size:.68rem;color:#94a3b8;">Created by {{ $o->creator_name }}</div>@endif
                    </td>
                    <td style="text-align:right;white-space:nowrap;font-weight:700;">{{ $o->symbol }} {{ number_format($o->total, 2) }}<div style="font-size:.65rem;color:#94a3b8;font-weight:500;">{{ $o->currency }}</div></td>
                    <td style="text-align:right;color:#64748b;">{{ rtrim(rtrim(number_format($o->exchange_rate ?: 1, 4), '0'), '.') }}</td>
                    <td style="text-align:right;font-weight:700;">${{ number_format($o->usd_total, 2) }}</td>
                    <td>
                        <span class="pill ok">{{ $o->payment_status }}</span>
                        <div style="font-size:.68rem;color:#94a3b8;">{{ $o->payment_method ?? '—' }}</div>
                    </td>
                    <td>@include('admin.staff-performance._status-pill', ['name' => $o->status_name, 'count' => ''])</td>
                    <td style="font-size:.72rem;">
                        @if($o->credit)
                            <b>{{ $o->credit->old_status ?? '—' }} → {{ $o->credit->new_status }}</b><br>
                            {{ \Carbon\Carbon::parse($o->credit->created_at)->format('d M Y H:i:s') }}
                        @else —
                        @endif
                    </td>
                    <td>
                        <ul class="hist">
                            @foreach($o->history as $h)
                                <li class="{{ $o->credit && $o->credit->id === $h->id ? 'credit' : '' }}">
                                    {{ \Carbon\Carbon::parse($h->created_at)->format('d M H:i') }} · {{ $h->old_status ?? '—' }} → {{ $h->new_status }} · {{ $h->by_name ?? 'System' }}
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @forelse($o->flags as $f)<span class="pill warn">{{ $f }}</span>@empty<span style="color:#cbd5e1;">—</span>@endforelse
                        @foreach($o->edits as $e)
                            <div style="font-size:.66rem;color:#dc2626;margin-top:3px;">
                                {{ \Carbon\Carbon::parse($e['at'])->format('d M H:i') }} {{ $e['by'] }}: {{ $e['description'] }}
                                @if(!empty($e['old']) || !empty($e['new']))
                                    ({{ collect($e['new'])->map(fn($v, $k) => $k . ': ' . ($e['old'][$k] ?? '∅') . ' → ' . (is_scalar($v) ? $v : json_encode($v)))->implode(', ') }})
                                @endif
                            </div>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;padding:40px;color:#94a3b8;">No paid orders credited to this staff member for {{ $label }}</td></tr>
                @endforelse
            </tbody>
            @if($orders->count())
            <tfoot>
                <tr style="background:#f8fafc;font-weight:800;">
                    <td colspan="5" style="text-align:right;">Period total ({{ $summary['total_paid_orders'] }} orders)</td>
                    <td style="text-align:right;">${{ number_format($summary['usd_total'], 2) }}</td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div style="padding:12px 18px;">{{ $orders->links() }}</div>
</div>
<p style="font-size:.72rem;color:#94a3b8;">
    Green history line = the status change that earned this staff member the credit. Flags highlight orders worth a second look (logged edits, self-created orders, instant confirmations).
</p>
@endsection
