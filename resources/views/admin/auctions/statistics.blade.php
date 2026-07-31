@extends('admin.layout')
@section('title', 'Auction Analytics - Admin')
@section('content')

<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">
            <i class="bi bi-bar-chart-line"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Auction Analytics</h2>
            <p class="text-muted mb-0 small">User behaviour on auction pages — last {{ $days }} days</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        @foreach([7,14,30,60] as $d)
            <a href="{{ route('admin.auctions.statistics', ['days' => $d]) }}"
               class="btn btn-sm {{ $days == $d ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $d }}d</a>
        @endforeach
        <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1"></i> Auctions
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['Page Views',    $totals['page_view']   ?? 0, 'bi-eye',          '#7c3aed'],
            ['Unique Visitors',$uniqueSessions,            'bi-people',        '#0ea5e9'],
            ['Bid Attempts',  $totals['bid_submit']  ?? 0, 'bi-hammer',        '#f59e0b'],
            ['Bids Won',      $totals['bid_success'] ?? 0, 'bi-trophy',        '#22c55e'],
            ['Bid Errors',    $totals['bid_error']   ?? 0, 'bi-exclamation-triangle','#ef4444'],
        ];
    @endphp
    @foreach($cards as [$label, $value, $icon, $color])
    <div class="col-6 col-md-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:{{ $color }}20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi {{ $icon }}" style="color:{{ $color }};font-size:1.25rem"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5">{{ number_format($value) }}</div>
                    <div class="text-muted small">{{ $label }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- Bid Conversion Funnel --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="bi bi-filter me-2"></i>Bid Conversion Funnel</div>
            <div class="card-body">
                @php
                    $funnelSteps = [
                        'page_view'  => ['Page Views', '#7c3aed'],
                        'bid_focus'  => ['Clicked Bid Input', '#0ea5e9'],
                        'bid_submit' => ['Submitted Bid', '#f59e0b'],
                        'bid_success'=> ['Bid Accepted', '#22c55e'],
                    ];
                    $base = max($funnel['page_view'] ?? 1, 1);
                @endphp
                @foreach($funnelSteps as $key => [$label, $color])
                    @php $pct = round(($funnel[$key] ?? 0) / $base * 100, 1); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold">{{ $label }}</span>
                            <span class="small text-muted">{{ number_format($funnel[$key] ?? 0) }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:6px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $color }};border-radius:6px"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top Auctions by page views --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="bi bi-eye me-2"></i>Top Auctions by Views</div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Auction</th><th class="text-end">Views</th><th></th></tr></thead>
                    <tbody>
                        @forelse($topByViews as $i => $row)
                        <tr>
                            <td class="text-muted">{{ $i+1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['views']) }}</td>
                            <td><a href="{{ route('admin.auctions.auction-stats', $row['id']) }}" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">Stats</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No data yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Daily trend chart --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold"><i class="bi bi-graph-up me-2"></i>Daily Event Trend</div>
            <div class="card-body"><canvas id="dailyChart" height="120"></canvas></div>
        </div>
    </div>

    {{-- Bid error breakdown --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Bid Error Breakdown</div>
            <div class="card-body p-0">
                @if($bidErrors->isEmpty())
                    <div class="text-center text-muted py-4">No bid errors 🎉</div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach($bidErrors as $err => $cnt)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="small">{{ Str::limit($err, 50) }}</span>
                        <span class="badge bg-danger rounded-pill">{{ $cnt }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- All event totals --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-list-check me-2"></i>All Event Totals</div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Event</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @forelse($totals as $evt => $cnt)
                <tr><td><code>{{ $evt }}</code></td><td class="text-end">{{ number_format($cnt) }}</td></tr>
                @empty
                <tr><td colspan="2" class="text-center text-muted py-3">No events recorded yet. Make sure the frontend is sending events.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const raw = @json($dailyTrend);
    const days = Object.keys(raw).sort();

    const eventColors = {
        page_view:   '#7c3aed',
        bid_focus:   '#0ea5e9',
        bid_submit:  '#f59e0b',
        bid_success: '#22c55e',
        bid_error:   '#ef4444',
        image_click: '#94a3b8',
    };

    // Collect unique events
    const allEvents = new Set();
    days.forEach(d => raw[d].forEach(e => allEvents.add(e.event)));

    const datasets = [...allEvents].filter(e => eventColors[e]).map(evt => ({
        label: evt.replace(/_/g, ' '),
        data: days.map(d => {
            const row = (raw[d] || []).find(e => e.event === evt);
            return row ? row.cnt : 0;
        }),
        borderColor: eventColors[evt] || '#94a3b8',
        backgroundColor: (eventColors[evt] || '#94a3b8') + '20',
        fill: false,
        tension: 0.3,
        pointRadius: 3,
    }));

    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: { labels: days, datasets },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,.05)' } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }
            }
        }
    });
})();
</script>
@endpush
