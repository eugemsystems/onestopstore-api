@extends('admin.layout')

@section('title', 'Product Statistics')

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-graph-up-arrow text-primary"></i> Product Statistics</h2>
            <p class="text-muted mb-0">Comprehensive analytics across all {{ number_format($summary->total) }} products</p>
        </div>
        <div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
            <small class="text-muted ms-2">Cached 10 min</small>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 1 — KPI SUMMARY CARDS                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label'=>'Total Products',   'value'=>number_format($summary->total),            'icon'=>'bi-box-seam',           'color'=>'primary'],
                ['label'=>'Active',           'value'=>number_format($summary->active),            'icon'=>'bi-check-circle-fill',  'color'=>'success'],
                ['label'=>'Inactive',         'value'=>number_format($summary->inactive),          'icon'=>'bi-x-circle-fill',      'color'=>'danger'],
                ['label'=>'Pending Approval', 'value'=>number_format($summary->pending_approval),  'icon'=>'bi-hourglass-split',    'color'=>'warning'],
                ['label'=>'In Stock',         'value'=>number_format($byStockStatus->firstWhere('label','in_stock')?->cnt ?? 0), 'icon'=>'bi-check2-all', 'color'=>'success'],
                ['label'=>'Out of Stock',     'value'=>number_format($byStockStatus->firstWhere('label','out_of_stock')?->cnt ?? 0), 'icon'=>'bi-exclamation-triangle-fill', 'color'=>'danger'],
                ['label'=>'On Sale',          'value'=>number_format($summary->on_sale),           'icon'=>'bi-tag-fill',           'color'=>'warning'],
                ['label'=>'Featured',         'value'=>number_format($summary->featured),          'icon'=>'bi-star-fill',          'color'=>'warning'],
                ['label'=>'Free Shipping',    'value'=>number_format($summary->free_shipping),     'icon'=>'bi-truck',              'color'=>'info'],
                ['label'=>'Gift Cards',       'value'=>number_format($summary->gift_cards),        'icon'=>'bi-gift-fill',          'color'=>'secondary'],
                ['label'=>'Back Order',       'value'=>number_format($backOrderCount),             'icon'=>'bi-clock-history',      'color'=>'secondary'],
                ['label'=>'Same Day Delivery','value'=>number_format($sameDayCount),               'icon'=>'bi-lightning-charge-fill','color'=>'info'],
                ['label'=>'SA Only',          'value'=>number_format($summary->sa_only),           'icon'=>'bi-geo-alt-fill',       'color'=>'danger'],
                ['label'=>'Zambia Only',      'value'=>number_format($summary->zambia_only),       'icon'=>'bi-geo-alt-fill',       'color'=>'warning'],
                ['label'=>'Zimbabwe Only',    'value'=>number_format($summary->zimbabwe_only),     'icon'=>'bi-geo-alt-fill',       'color'=>'success'],
                ['label'=>'With Thumbnail',   'value'=>number_format($withThumbnail),              'icon'=>'bi-image-fill',         'color'=>'info'],
                ['label'=>'No Thumbnail',     'value'=>number_format($withoutThumbnail),           'icon'=>'bi-image',              'color'=>'danger'],
                ['label'=>'Returnable',       'value'=>number_format($summary->returnable),        'icon'=>'bi-arrow-return-left',  'color'=>'secondary'],
            ];
        @endphp
        @foreach($cards as $card)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body p-3 text-center">
                    <div class="mb-1">
                        <i class="bi {{ $card['icon'] }} text-{{ $card['color'] }}" style="font-size:1.5rem"></i>
                    </div>
                    <div class="fw-bold fs-5 lh-1">{{ $card['value'] }}</div>
                    <div class="text-muted" style="font-size:0.72rem">{{ $card['label'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 2 — STOCK STATUS + PRODUCT TYPE + REGIONAL                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">

        {{-- Stock Status Donut --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-circle-half text-primary"></i> Stock Status
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <canvas id="stockStatusChart" height="220"></canvas>
                    <div class="mt-3 w-100">
                        @foreach($byStockStatus as $row)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="text-capitalize">{{ str_replace('_',' ',$row->label) }}</span>
                            <span class="badge bg-secondary">{{ number_format($row->cnt) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Type Donut --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-diagram-2 text-success"></i> Product Type
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <canvas id="productTypeChart" height="220"></canvas>
                    <div class="mt-3 w-100 small text-muted text-center">
                        Simple products have no variations.<br>Classified products use attribute-based variations.
                    </div>
                </div>
            </div>
        </div>

        {{-- Regional Breakdown --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-globe2 text-info"></i> Regional Availability
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <canvas id="regionalChart" height="220"></canvas>
                    <div class="mt-3 w-100">
                        @foreach($regional as $row)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span>{{ $row['label'] }}</span>
                            <span class="badge bg-secondary">{{ number_format($row['cnt']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 3 — PRODUCTS ADDED PER MONTH (LINE CHART)                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-calendar3 text-primary"></i> Products Added Per Month <small class="text-muted fw-normal">(last 12 months)</small>
        </div>
        <div class="card-body">
            <canvas id="byMonthChart" height="80"></canvas>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 4 — TOP 30 CATEGORIES (HORIZONTAL BAR)                     --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-tags-fill text-warning"></i> Products by Category <small class="text-muted fw-normal">(Top 30)</small>
                </div>
                <div class="card-body">
                    <canvas id="byCategoryChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-list-ol text-warning"></i> Category Breakdown
                </div>
                <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr><th>#</th><th>Category</th><th class="text-end">Products</th></tr>
                        </thead>
                        <tbody>
                            @foreach($byCategory as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i+1 }}</td>
                                <td>{{ $row->label }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row->cnt) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 5 — TOP 20 BRANDS                                          --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-award-fill text-danger"></i> Products by Brand <small class="text-muted fw-normal">(Top 20)</small>
                </div>
                <div class="card-body">
                    <canvas id="byBrandChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-list-ol text-danger"></i> Brand Breakdown
                </div>
                <div class="card-body p-0" style="max-height:460px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr><th>#</th><th>Brand</th><th class="text-end">Products</th></tr>
                        </thead>
                        <tbody>
                            @foreach($byBrand as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i+1 }}</td>
                                <td>{{ $row->label }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row->cnt) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 6 — PRICE RANGES + DELIVERY TEXT                           --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-currency-dollar text-success"></i> Products by Price Range
                </div>
                <div class="card-body">
                    <canvas id="priceRangeChart" height="200"></canvas>
                    <div class="mt-3">
                        @foreach($priceRangeData as $row)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <span>{{ $row['label'] }}</span>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress" style="width:80px;height:6px">
                                    <div class="progress-bar bg-success" style="width:{{ $summary->active > 0 ? round($row['cnt']/$summary->active*100) : 0 }}%"></div>
                                </div>
                                <span class="fw-semibold text-end" style="min-width:60px">{{ number_format($row['cnt']) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-truck text-info"></i> Products by Delivery Text
                </div>
                <div class="card-body">
                    <canvas id="byDeliveryChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 7 — TOP STORES + DELIVERY TABLE                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-shop text-secondary"></i> Products by Store <small class="text-muted fw-normal">(Top 15)</small>
                </div>
                <div class="card-body">
                    <canvas id="byStoreChart" height="260"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-table text-secondary"></i> Full Delivery Text Breakdown
                </div>
                <div class="card-body p-0" style="max-height:480px;overflow-y:auto">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Delivery Text</th>
                                <th class="text-end">Count</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $deliveryTotal = $byDelivery->sum('cnt'); @endphp
                            @foreach($byDelivery as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i+1 }}</td>
                                <td>
                                    @if(str_contains(strtolower($row->label), 'same day'))
                                        <span class="badge bg-success me-1">⚡</span>
                                    @elseif(str_contains(strtolower($row->label), 'back order'))
                                        <span class="badge bg-warning text-dark me-1">⏳</span>
                                    @elseif(str_contains(strtolower($row->label), 'out of stock'))
                                        <span class="badge bg-danger me-1">✗</span>
                                    @elseif(str_contains(strtolower($row->label), 'tomorrow'))
                                        <span class="badge bg-info me-1">🌅</span>
                                    @endif
                                    {{ $row->label }}
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row->cnt) }}</td>
                                <td class="text-end text-muted">
                                    {{ $deliveryTotal > 0 ? number_format($row->cnt / $deliveryTotal * 100, 1) : 0 }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ number_format($deliveryTotal) }}</td>
                                <td class="text-end fw-bold">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION 8 — FEATURE FLAGS OVERVIEW                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-toggles text-primary"></i> Feature Flags &amp; Special Attributes
        </div>
        <div class="card-body">
            <canvas id="featureFlagsChart" height="60"></canvas>
            <div class="row mt-4 g-3 text-center">
                @php
                    $flags = [
                        ['label'=>'On Sale',       'value'=>$summary->on_sale,       'pct'=>$summary->total, 'color'=>'warning'],
                        ['label'=>'Featured',      'value'=>$summary->featured,      'pct'=>$summary->total, 'color'=>'primary'],
                        ['label'=>'Free Shipping', 'value'=>$summary->free_shipping, 'pct'=>$summary->total, 'color'=>'info'],
                        ['label'=>'COD',           'value'=>$summary->cod,           'pct'=>$summary->total, 'color'=>'secondary'],
                        ['label'=>'Gift Cards',    'value'=>$summary->gift_cards,    'pct'=>$summary->total, 'color'=>'success'],
                        ['label'=>'Returnable',    'value'=>$summary->returnable,    'pct'=>$summary->total, 'color'=>'danger'],
                        ['label'=>'SA Only',       'value'=>$summary->sa_only,       'pct'=>$summary->total, 'color'=>'danger'],
                        ['label'=>'Zambia Only',   'value'=>$summary->zambia_only,   'pct'=>$summary->total, 'color'=>'warning'],
                        ['label'=>'Zimbabwe Only', 'value'=>$summary->zimbabwe_only, 'pct'=>$summary->total, 'color'=>'success'],
                        ['label'=>'Has Thumbnail', 'value'=>$withThumbnail,          'pct'=>$summary->total, 'color'=>'info'],
                        ['label'=>'Back Order',    'value'=>$backOrderCount,         'pct'=>$summary->total, 'color'=>'secondary'],
                        ['label'=>'Same Day Del.', 'value'=>$sameDayCount,           'pct'=>$summary->total, 'color'=>'success'],
                    ];
                @endphp
                @foreach($flags as $flag)
                <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body p-2">
                            <div class="fw-bold text-{{ $flag['color'] }}">{{ number_format($flag['value']) }}</div>
                            <div class="small text-muted">{{ $flag['label'] }}</div>
                            <div class="progress mt-1" style="height:4px">
                                <div class="progress-bar bg-{{ $flag['color'] }}" style="width:{{ $flag['pct'] > 0 ? min(100,round($flag['value']/$flag['pct']*100,1)) : 0 }}%"></div>
                            </div>
                            <div class="small text-muted">{{ $flag['pct'] > 0 ? number_format($flag['value']/$flag['pct']*100,2) : 0 }}%</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
.stat-card { transition: transform 0.15s; cursor: default; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1) !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = [
    '#0d6efd','#198754','#dc3545','#ffc107','#0dcaf0','#6610f2','#fd7e14',
    '#20c997','#6f42c1','#d63384','#0dcaf0','#6c757d','#495057','#28a745',
    '#17a2b8','#e83e8c','#6f42c1','#fd7e14','#20c997','#343a40','#007bff',
    '#28a745','#dc3545','#ffc107','#17a2b8','#6c757d','#fd7e14','#e83e8c',
    '#6610f2','#20c997',
];

// ── Stock Status Donut ─────────────────────────────────────────────────────
new Chart(document.getElementById('stockStatusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! $byStockStatus->pluck('label')->map(fn($l) => ucwords(str_replace('_',' ',$l)))->toJson() !!},
        datasets: [{ data: {!! $byStockStatus->pluck('cnt')->toJson() !!}, backgroundColor: COLORS }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// ── Product Type Donut ────────────────────────────────────────────────────
new Chart(document.getElementById('productTypeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Simple', 'Classified (Variations)'],
        datasets: [{ data: [{{ $summary->simple }}, {{ $summary->has_variations }}], backgroundColor: ['#0d6efd','#fd7e14'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// ── Regional Donut ────────────────────────────────────────────────────────
new Chart(document.getElementById('regionalChart'), {
    type: 'doughnut',
    data: {
        labels: {!! $regional->pluck('label')->toJson() !!},
        datasets: [{ data: {!! $regional->pluck('cnt')->toJson() !!}, backgroundColor: ['#dc3545','#ffc107','#198754','#0d6efd'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// ── Products Added Per Month (Line) ───────────────────────────────────────
new Chart(document.getElementById('byMonthChart'), {
    type: 'line',
    data: {
        labels: {!! $byMonth->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products Added',
            data: {!! $byMonth->pluck('cnt')->toJson() !!},
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#0d6efd',
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } },
        }
    }
});

// ── By Category (Horizontal Bar) ──────────────────────────────────────────
new Chart(document.getElementById('byCategoryChart'), {
    type: 'bar',
    data: {
        labels: {!! $byCategory->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products',
            data: {!! $byCategory->pluck('cnt')->toJson() !!},
            backgroundColor: COLORS,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});

// ── By Brand (Horizontal Bar) ─────────────────────────────────────────────
new Chart(document.getElementById('byBrandChart'), {
    type: 'bar',
    data: {
        labels: {!! $byBrand->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products',
            data: {!! $byBrand->pluck('cnt')->toJson() !!},
            backgroundColor: COLORS,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});

// ── Price Range (Bar) ─────────────────────────────────────────────────────
new Chart(document.getElementById('priceRangeChart'), {
    type: 'bar',
    data: {
        labels: {!! $priceRangeData->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products',
            data: {!! $priceRangeData->pluck('cnt')->toJson() !!},
            backgroundColor: ['#20c997','#0d6efd','#6610f2','#fd7e14','#dc3545','#6f42c1'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});

// ── By Delivery Text (Horizontal Bar, top 20) ─────────────────────────────
@php
    $topDelivery = $byDelivery->take(20);
@endphp
new Chart(document.getElementById('byDeliveryChart'), {
    type: 'bar',
    data: {
        labels: {!! $topDelivery->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products',
            data: {!! $topDelivery->pluck('cnt')->toJson() !!},
            backgroundColor: COLORS,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});

// ── By Store (Horizontal Bar) ─────────────────────────────────────────────
new Chart(document.getElementById('byStoreChart'), {
    type: 'bar',
    data: {
        labels: {!! $byStore->pluck('label')->toJson() !!},
        datasets: [{
            label: 'Products',
            data: {!! $byStore->pluck('cnt')->toJson() !!},
            backgroundColor: COLORS,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});

// ── Feature Flags (Grouped Bar) ───────────────────────────────────────────
new Chart(document.getElementById('featureFlagsChart'), {
    type: 'bar',
    data: {
        labels: ['On Sale','Featured','Free Shipping','COD','Gift Cards','Returnable','SA Only','Zambia Only','Zimbabwe Only','Has Thumbnail','Back Order','Same Day Del.'],
        datasets: [{
            label: 'Count',
            data: [
                {{ $summary->on_sale }}, {{ $summary->featured }}, {{ $summary->free_shipping }},
                {{ $summary->cod }}, {{ $summary->gift_cards }}, {{ $summary->returnable }},
                {{ $summary->sa_only }}, {{ $summary->zambia_only }}, {{ $summary->zimbabwe_only }},
                {{ $withThumbnail }}, {{ $backOrderCount }}, {{ $sameDayCount }}
            ],
            backgroundColor: COLORS,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
        }
    }
});
</script>
@endpush

@endsection
