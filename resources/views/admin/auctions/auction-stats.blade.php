@extends('admin.layout')
@section('title', 'Auction Stats: {{ $auction->title }} - Admin')
@section('content')

<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">{{ Str::limit($auction->title, 50) }}</h2>
            <p class="text-muted mb-0 small">Per-auction analytics</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.auctions.show', $auction) }}" class="btn btn-outline-secondary">
            <i class="bi bi-hammer me-1"></i> Auction
        </a>
        <a href="{{ route('admin.auctions.statistics') }}" class="btn btn-outline-secondary">
            <i class="bi bi-bar-chart-line me-1"></i> All Stats
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['Page Views',    $totals['page_view']   ?? 0, 'bi-eye',          '#7c3aed'],
            ['Bid Clicks',    $totals['bid_focus']   ?? 0, 'bi-cursor',        '#0ea5e9'],
            ['Bid Attempts',  $totals['bid_submit']  ?? 0, 'bi-hammer',        '#f59e0b'],
            ['Bids Accepted', $totals['bid_success'] ?? 0, 'bi-trophy',        '#22c55e'],
            ['Bid Errors',    $totals['bid_error']   ?? 0, 'bi-exclamation-triangle','#ef4444'],
            ['Image Clicks',  $totals['image_click'] ?? 0, 'bi-images',        '#64748b'],
        ];
    @endphp
    @foreach($cards as [$label, $value, $icon, $color])
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:{{ $color }}20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi {{ $icon }}" style="color:{{ $color }};font-size:1.1rem"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6">{{ number_format($value) }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $label }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- Funnel --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="bi bi-filter me-2"></i>Bid Conversion Funnel</div>
            <div class="card-body">
                @php
                    $steps = [
                        'page_view'  => ['Viewed Page',     '#7c3aed'],
                        'bid_focus'  => ['Clicked Bid Input','#0ea5e9'],
                        'bid_submit' => ['Submitted Bid',   '#f59e0b'],
                        'bid_success'=> ['Bid Accepted',    '#22c55e'],
                    ];
                    $base = max($funnel['page_view'], 1);
                @endphp
                @foreach($steps as $key => [$label, $color])
                    @php $pct = round(($funnel[$key]) / $base * 100, 1); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">{{ $label }}</span>
                            <span class="small text-muted">{{ number_format($funnel[$key]) }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:6px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $color }};border-radius:6px"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Errors + Image + Tab --}}
    <div class="col-md-7">
        <div class="row g-3 h-100">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Bid Errors</div>
                    <div class="card-body p-0">
                        @if($errors->isEmpty())
                            <div class="text-center text-muted py-3">No bid errors 🎉</div>
                        @else
                        <ul class="list-group list-group-flush">
                            @foreach($errors as $err => $cnt)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <span class="small">{{ Str::limit($err, 60) }}</span>
                                <span class="badge bg-danger rounded-pill">{{ $cnt }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold small"><i class="bi bi-images me-1"></i>Image Clicks</div>
                    <div class="card-body p-0">
                        @if($imageClicks->isEmpty())
                            <div class="text-center text-muted py-3 small">No clicks</div>
                        @else
                        <ul class="list-group list-group-flush">
                            @foreach($imageClicks as $img => $cnt)
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="small">{{ $img }}</span>
                                <span class="badge bg-secondary rounded-pill">{{ $cnt }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold small"><i class="bi bi-tab me-1"></i>Tab Switches</div>
                    <div class="card-body p-0">
                        @if($tabSwitches->isEmpty())
                            <div class="text-center text-muted py-3 small">No switches</div>
                        @else
                        <ul class="list-group list-group-flush">
                            @foreach($tabSwitches as $tab => $cnt)
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span class="small">{{ $tab }}</span>
                                <span class="badge bg-info text-dark rounded-pill">{{ $cnt }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Activity timeline chart --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-calendar3 me-2"></i>Daily Activity</div>
    <div class="card-body"><canvas id="activityChart" height="100"></canvas></div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const raw = @json($dailyActivity);
    const days = Object.keys(raw).sort();
    const eventColors = {
        page_view: '#7c3aed', bid_focus: '#0ea5e9',
        bid_submit: '#f59e0b', bid_success: '#22c55e',
        bid_error: '#ef4444', image_click: '#94a3b8',
    };
    const allEvents = new Set();
    days.forEach(d => raw[d].forEach(e => allEvents.add(e.event)));

    const datasets = [...allEvents].map(evt => ({
        label: evt.replace(/_/g,' '),
        data: days.map(d => { const r = (raw[d]||[]).find(e=>e.event===evt); return r?r.cnt:0; }),
        borderColor: eventColors[evt]||'#94a3b8',
        backgroundColor:(eventColors[evt]||'#94a3b8')+'20',
        fill:false, tension:0.3, pointRadius:3,
    }));

    new Chart(document.getElementById('activityChart'), {
        type:'line',
        data:{ labels:days, datasets },
        options:{
            responsive:true,
            plugins:{ legend:{ position:'bottom' } },
            scales:{
                x:{ grid:{ color:'rgba(0,0,0,.05)' } },
                y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,.05)' } }
            }
        }
    });
})();
</script>
@endpush
