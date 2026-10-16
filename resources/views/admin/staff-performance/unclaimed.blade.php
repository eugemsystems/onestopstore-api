@extends('admin.layout')

@section('title', 'Unclaimed Orders - Staff Performance')

@push('styles')
@include('admin.staff-performance._styles')
@endpush

@section('content')
@php $canAssign = auth()->user()->can('staff-performance.assign'); @endphp

<div class="sp-hero">
    <div>
        <a href="{{ route('admin.staff-performance.index', request()->only('period','month','year','date_from','date_to')) }}" style="color:rgba(255,255,255,.8);font-size:.75rem;text-decoration:none;">← Staff performance</a>
        <h2 style="margin-top:4px;"><i class="bi bi-inbox-fill me-2"></i>Unclaimed Orders</h2>
        <p>Paid orders (e.g. placed online) that no staff member is credited for. Assign them so the revenue counts.</p>
    </div>
    <div class="sp-hero-stats">
        <div><div class="v">{{ number_format($totals->cnt) }}</div><div class="l">Unclaimed orders</div></div>
        <div><div class="v">${{ number_format($totals->usd, 2) }}</div><div class="l">Total (USD)</div></div>
    </div>
</div>

@if(session('success'))<div style="background:#f0fdf4;color:#166534;border-radius:10px;padding:10px 16px;margin-bottom:14px;font-size:.85rem;">{{ session('success') }}</div>@endif
@if($errors->any())<div style="background:#fef2f2;color:#991b1b;border-radius:10px;padding:10px 16px;margin-bottom:14px;font-size:.85rem;">{{ $errors->first() }}</div>@endif

@include('admin.staff-performance._filter', ['action' => route('admin.staff-performance.unclaimed')])

<form method="GET" action="{{ route('admin.staff-performance.unclaimed') }}" class="sp-filter">
    @foreach(request()->only('period','month','year','date_from','date_to') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <input type="text" name="q" value="{{ $search }}" placeholder="Order # or customer…" style="border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:.8rem;min-width:220px;">
    <select name="method" style="border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:.8rem;background:#f8fafc;">
        <option value="">All payment methods</option>
        @foreach($methods as $m)<option value="{{ $m }}" {{ $method === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach
    </select>
    <button type="submit" class="sp-btn">Search</button>
    @if($search !== '' || $method !== '')<a href="{{ route('admin.staff-performance.unclaimed', request()->only('period','month','year','date_from','date_to')) }}" class="sp-link">Clear</a>@endif
</form>

<form method="POST" action="{{ route('admin.staff-performance.assign') }}" id="assignForm">
    @csrf
    @if($canAssign)
    <div class="sp-filter" style="position:sticky;top:0;z-index:5;">
        <span class="sp-lbl"><span id="selCount">0</span> selected</span>
        <select name="staff_id" required style="border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;font-size:.8rem;background:#f8fafc;">
            <option value="">— Assign to staff —</option>
            @foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
        </select>
        <button type="submit" class="sp-btn" onclick="return document.querySelectorAll('.ord-cb:checked').length ? confirm('Assign the selected orders to this staff member?') : (alert('Select at least one order.'), false)">Assign selected</button>
        <span style="font-size:.72rem;color:#94a3b8;">Tip: tick the header box to select every order on this page.</span>
    </div>
    @endif

    <div class="sp-card">
        <div class="sp-card-h">{{ number_format($orders->total()) }} unclaimed order(s) — {{ $label }}</div>
        <div style="overflow-x:auto;">
            <table class="sp-table">
                <thead>
                    <tr>
                        @if($canAssign)<th style="width:30px;"><input type="checkbox" id="cbAll"></th>@endif
                        <th>Order</th>
                        <th>Placed</th>
                        <th>Customer</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="text-align:right;">Amount (local)</th>
                        <th style="text-align:right;">USD (recorded)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        @if($canAssign)<td><input type="checkbox" class="ord-cb" name="order_ids[]" value="{{ $o->id }}"></td>@endif
                        <td><a href="{{ route('admin.orders.show', $o->order_number) }}" target="_blank" style="font-weight:700;color:#0f3d8c;text-decoration:none;">#{{ $o->order_number }}</a></td>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y H:i') }}</td>
                        <td><div>{{ $o->customer_name ?? 'N/A' }}</div><div style="font-size:.68rem;color:#94a3b8;">{{ $o->customer_email }}</div></td>
                        <td><span class="pill">{{ $o->payment_method ?? '—' }}</span></td>
                        <td>@include('admin.staff-performance._status-pill', ['name' => $o->status_name, 'count' => ''])</td>
                        <td style="text-align:right;white-space:nowrap;font-weight:700;">{{ $o->symbol }} {{ number_format($o->local_total, 2) }}<div style="font-size:.65rem;color:#94a3b8;font-weight:500;">{{ $o->currency }}</div></td>
                        <td style="text-align:right;font-weight:700;">${{ number_format($o->usd_total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">Nothing unclaimed for {{ $label }} 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 18px;">{{ $orders->links() }}</div>
    </div>
</form>

<script>
(function () {
    var all = document.getElementById('cbAll'), cbs = document.querySelectorAll('.ord-cb'), n = document.getElementById('selCount');
    function upd() { if (n) n.textContent = document.querySelectorAll('.ord-cb:checked').length; }
    if (all) all.addEventListener('change', function () { cbs.forEach(function (c) { c.checked = all.checked; }); upd(); });
    cbs.forEach(function (c) { c.addEventListener('change', upd); });
})();
</script>
@endsection
