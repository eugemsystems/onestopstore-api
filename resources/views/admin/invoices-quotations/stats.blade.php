@extends('admin.layout')

@section('title', 'Invoice & Quotation Statistics - Admin Panel')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-bar-chart-line"></i> Invoice & Quotation Statistics</h2>
        <small class="text-muted">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</small>
    </div>
    <a href="{{ route('admin.invoices-quotations.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

{{-- Date Range Filter --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.invoices-quotations.stats') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('admin.invoices-quotations.stats') }}" class="btn btn-outline-secondary ms-1">Reset</a>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.invoices-quotations.stats', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">This Month</a>
                <a href="{{ route('admin.invoices-quotations.stats', ['date_from' => now()->subMonths(3)->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">3 Months</a>
                <a href="{{ route('admin.invoices-quotations.stats', ['date_from' => now()->subYear()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">1 Year</a>
                <a href="{{ route('admin.invoices-quotations.stats', ['date_from' => now()->startOfYear()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">YTD</a>
            </div>
        </form>
    </div>
</div>

{{-- Count KPI Cards (currency-agnostic) --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100" style="border-left:4px solid #667eea;">
            <div class="card-body">
                <div style="font-size:2rem;font-weight:700;color:#667eea;">{{ number_format($totalDocs) }}</div>
                <div class="text-muted small">Total Documents</div>
                <div class="mt-1 small">avg {{ $avgItemsPerDoc }} items/doc</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100" style="border-left:4px solid {{ $overdueCount > 0 ? '#dc3545' : '#6c757d' }};">
            <div class="card-body">
                <div style="font-size:2rem;font-weight:700;color:{{ $overdueCount > 0 ? '#dc3545' : '#6c757d' }};">{{ number_format($overdueCount) }}</div>
                <div class="text-muted small">Overdue (Sent &amp; Unpaid)</div>
                @if($totalQuotations > 0)
                <div class="mt-1 small">{{ $conversionRate }}% quot. conversion</div>
                @endif
            </div>
        </div>
    </div>
    {{-- Per-currency revenue summary cards --}}
    @foreach($byCurrency as $cRow)
    <div class="col-6 col-md-3">
        <div class="card text-center h-100" style="border-left:4px solid #28a745;">
            <div class="card-body">
                <div class="fw-bold text-muted small mb-1">{{ $cRow->currency_code }}</div>
                <div style="font-size:1.5rem;font-weight:700;color:#28a745;">{{ number_format($cRow->paid_revenue, 2) }}</div>
                <div class="text-muted small">Paid Revenue</div>
                <div class="mt-1 small">
                    Total: {{ number_format($cRow->total_revenue, 2) }}<br>
                    VAT: {{ number_format($cRow->total_vat, 2) }} &bull;
                    Disc: {{ number_format($cRow->total_discount, 2) }}<br>
                    Avg/doc: {{ number_format($cRow->avg_value, 2) }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- By Type & By Status Charts (count only, currency-agnostic) --}}
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">Documents by Type</div>
            <div class="card-body d-flex flex-column align-items-center">
                <canvas id="chartType" style="max-height:200px;"></canvas>
                <div class="mt-3 w-100">
                    @foreach($byType as $row)
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ ucfirst(str_replace('_',' ',$row->document_type)) }}</span>
                        <span class="fw-bold">{{ number_format($row->count) }} docs</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Documents by Status</div>
            <div class="card-body d-flex flex-column align-items-center">
                <canvas id="chartStatus" style="max-height:200px;"></canvas>
                <div class="mt-3 w-100">
                    @foreach($byStatus as $row)
                    @php $statusColors = ['draft'=>'secondary','sent'=>'info','paid'=>'success','cancelled'=>'danger','expired'=>'warning']; @endphp
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="badge bg-{{ $statusColors[$row->status] ?? 'secondary' }}">{{ ucfirst($row->status) }}</span>
                        <span>{{ number_format($row->count) }} docs</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header">Docs by Currency</div>
            <div class="card-body d-flex flex-column align-items-center">
                <canvas id="chartCurrency" style="max-height:200px;"></canvas>
                <div class="mt-3 w-100">
                    @foreach($byCurrency as $row)
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-bold">{{ $row->currency_code }}</span>
                        <span>{{ number_format($row->count) }} docs</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Revenue by Type & Status per Currency --}}
@foreach($byCurrency as $cRow)
<div class="card mb-3">
    <div class="card-header fw-bold">
        <i class="bi bi-currency-exchange"></i> {{ $cRow->currency_code }} — Revenue Breakdown
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- By Type --}}
            <div class="col-md-6">
                <div class="small fw-bold text-muted mb-2">By Document Type</div>
                @foreach($byTypeCurrency->get($cRow->currency_code, collect()) as $t)
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small">{{ ucfirst(str_replace('_',' ',$t->document_type)) }} ({{ $t->count }})</span>
                    <span class="small fw-bold">{{ $cRow->currency_code }} {{ number_format($t->revenue, 2) }}</span>
                </div>
                @php $pct = $cRow->total_revenue > 0 ? ($t->revenue / $cRow->total_revenue) * 100 : 0; @endphp
                <div class="progress mb-2" style="height:4px;">
                    <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                </div>
                @endforeach
            </div>
            {{-- By Status --}}
            <div class="col-md-6">
                <div class="small fw-bold text-muted mb-2">By Status</div>
                @php $statusColors2 = ['draft'=>'secondary','sent'=>'info','paid'=>'success','cancelled'=>'danger','expired'=>'warning']; @endphp
                @foreach($byStatusCurrency->get($cRow->currency_code, collect()) as $s)
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-{{ $statusColors2[$s->status] ?? 'secondary' }}">{{ ucfirst($s->status) }} ({{ $s->count }})</span>
                    <span class="small fw-bold">{{ $cRow->currency_code }} {{ number_format($s->revenue, 2) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Monthly Trend — one chart per currency, all in one row --}}
<div class="card mb-4">
    <div class="card-header">Monthly Trend — Last 12 Months (per Currency)</div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($monthlyTrend as $currency => $rows)
            <div class="col-12 col-md-4">
                <div class="text-center text-muted small fw-bold mb-1">{{ $currency }}</div>
                <canvas id="chartMonthly_{{ $currency }}" style="height:130px;"></canvas>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Per-User Stats --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people"></i> Per-User Breakdown</span>
        <small class="text-muted">{{ count($perUser) }} user(s) &bull; Revenue shown per currency</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th class="text-center">Total Docs</th>
                        <th>Revenue (per currency)</th>
                        <th>Type Breakdown</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perUser as $userId => $userRows)
                    @php
                        $creator = $userRows->first()->creator;
                        $totalUserDocs = $userRows->sum('doc_count');
                        $userTypes = $perUserByType->get($userId, collect());
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $creator->name ?? 'Unknown' }}</div>
                            <small class="text-muted">{{ $creator->email ?? '' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill">{{ number_format($totalUserDocs) }}</span>
                        </td>
                        <td>
                            @foreach($userRows as $uRow)
                            <div class="small">
                                <span class="fw-bold text-muted">{{ $uRow->currency_code }}</span>
                                &nbsp;Total: <strong>{{ number_format($uRow->total_revenue, 2) }}</strong>
                                &nbsp;Paid: <span class="text-success fw-bold">{{ number_format($uRow->paid_revenue, 2) }}</span>
                                <span class="text-muted">({{ $uRow->doc_count }} docs)</span>
                            </div>
                            @endforeach
                        </td>
                        <td>
                            @foreach($userTypes as $t)
                            <span class="badge bg-secondary me-1">{{ ucfirst(str_replace('_',' ',$t->document_type)) }}: {{ $t->count }}</span>
                            @endforeach
                        </td>
                        <td>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($userRows->max('last_activity'))->format('M d, Y H:i') }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No data for this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Top Products per Currency --}}
@foreach($topProducts as $currency => $products)
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-box-seam"></i> Top Products — {{ $currency }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th class="text-center">Total Qty</th>
                        <th class="text-center">Appearances</th>
                        <th class="text-end">Revenue ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $i => $prod)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-bold">{{ $prod->product_name }}</td>
                        <td class="text-center">{{ number_format($prod->total_qty, 2) }}</td>
                        <td class="text-center">{{ number_format($prod->doc_count) }}</td>
                        <td class="text-end">{{ number_format($prod->total_revenue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const palette = ['#667eea','#764ba2','#f5576c','#4facfe','#43e97b','#fa709a','#fee140','#a18cd1'];

    // By Type
    @php
        $typeLabels = $byType->pluck('document_type')->map(fn($t) => ucfirst(str_replace('_',' ',$t)))->toJson();
        $typeData   = $byType->pluck('count')->toJson();
    @endphp
    new Chart(document.getElementById('chartType'), {
        type: 'doughnut',
        data: { labels: {!! $typeLabels !!}, datasets: [{ data: {!! $typeData !!}, backgroundColor: palette, borderWidth:2 }] },
        options: { plugins: { legend: { position:'bottom', labels:{ boxWidth:12 } } }, cutout:'65%' }
    });

    // By Status
    @php
        $statusLabels = $byStatus->pluck('status')->map(fn($s) => ucfirst($s))->toJson();
        $statusData   = $byStatus->pluck('count')->toJson();
        $statusColors = $byStatus->pluck('status')->map(fn($s) => match($s) {
            'paid'=>'#28a745','sent'=>'#17a2b8','draft'=>'#6c757d','cancelled'=>'#dc3545','expired'=>'#ffc107',default=>'#adb5bd'
        })->toJson();
    @endphp
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels: {!! $statusLabels !!}, datasets: [{ data: {!! $statusData !!}, backgroundColor: {!! $statusColors !!}, borderWidth:2 }] },
        options: { plugins: { legend: { position:'bottom', labels:{ boxWidth:12 } } }, cutout:'65%' }
    });

    // By Currency (doc count)
    @php
        $currencyLabels = $byCurrency->pluck('currency_code')->toJson();
        $currencyData   = $byCurrency->pluck('count')->toJson();
    @endphp
    new Chart(document.getElementById('chartCurrency'), {
        type: 'doughnut',
        data: { labels: {!! $currencyLabels !!}, datasets: [{ data: {!! $currencyData !!}, backgroundColor: palette, borderWidth:2 }] },
        options: { plugins: { legend: { position:'bottom', labels:{ boxWidth:12 } } }, cutout:'65%' }
    });

    // Monthly Trend — one per currency
    @foreach($monthlyTrend as $currency => $rows)
    @php
        $mMonths = $rows->pluck('month')->toJson();
        $mCounts = $rows->pluck('count')->toJson();
        $mRev    = $rows->pluck('revenue')->map(fn($v) => round($v, 2))->toJson();
    @endphp
    (function() {
        const el = document.getElementById('chartMonthly_{{ $currency }}');
        if (!el) return;
        new Chart(el, {
            data: {
                labels: {!! $mMonths !!},
                datasets: [
                    { type:'bar', label:'Documents', data:{!! $mCounts !!}, backgroundColor:'rgba(102,126,234,0.6)', borderColor:'#667eea', borderWidth:1, yAxisID:'yDocs' },
                    { type:'line', label:'Revenue ({{ $currency }})', data:{!! $mRev !!}, borderColor:'#28a745', backgroundColor:'rgba(40,167,69,0.1)', borderWidth:2, pointRadius:4, tension:0.3, fill:true, yAxisID:'yRev' }
                ]
            },
            options: {
                responsive:true, interaction:{ mode:'index' },
                scales: {
                    yDocs:{ type:'linear', position:'left',  title:{ display:true, text:'Documents' }, beginAtZero:true },
                    yRev: { type:'linear', position:'right', title:{ display:true, text:'Revenue ({{ $currency }})' }, beginAtZero:true, grid:{ drawOnChartArea:false } }
                },
                plugins:{ legend:{ position:'top' } }
            }
        });
    })();
    @endforeach
});
</script>
@endpush
@endsection
