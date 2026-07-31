@extends('admin.layout')

@section('title', 'Search Analytics')

@push('styles')
<style>
    .stat-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .badge-trending {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-graph-up"></i> Search Analytics</h2>
            <p class="text-muted mb-0">Insights from user search behavior</p>
        </div>
        <div>
            <div class="btn-group">
                <a href="?days=7" class="btn btn-sm {{ request('days', 30) == 7 ? 'btn-primary' : 'btn-outline-primary' }}">7 Days</a>
                <a href="?days=30" class="btn btn-sm {{ request('days', 30) == 30 ? 'btn-primary' : 'btn-outline-primary' }}">30 Days</a>
                <a href="?days=90" class="btn btn-sm {{ request('days', 30) == 90 ? 'btn-primary' : 'btn-outline-primary' }}">90 Days</a>
            </div>
            <div class="btn-group ms-2">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.search-analytics.export', ['type' => 'all', 'days' => $days]) }}">All Searches</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.search-analytics.export', ['type' => 'popular', 'days' => $days]) }}">Popular Searches</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.search-analytics.export', ['type' => 'no-results', 'days' => $days]) }}">No Results</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.search-analytics.export', ['type' => 'trending', 'days' => $days]) }}">Trending</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total Searches</h6>
                        <h3 class="mb-0">{{ number_format($totalSearches) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Success Rate</h6>
                        <h3 class="mb-0">{{ $successRate }}%</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Unique Searches</h6>
                        <h3 class="mb-0">{{ number_format($uniqueSearches) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Avg Results</h6>
                        <h3 class="mb-0">{{ number_format($avgResults, 1) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Popular Searches -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-fire text-danger"></i> Popular Searches</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Search Query</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($popularSearches as $index => $search)
                                <tr>
                                    <td>
                                        <span class="badge {{ $index < 3 ? 'bg-primary' : 'bg-secondary' }}">
                                            #{{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $search->normalized_query }}</strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($search->search_count) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Results Searches (Inventory Gaps) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-x-circle text-danger"></i> No Results (Inventory Gaps)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Search Query</th>
                                    <th class="text-end">Attempts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($noResultsSearches as $index => $search)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">#{{ $index + 1 }}</span>
                                    </td>
                                    <td><strong>{{ $search->normalized_query }}</strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-danger">{{ number_format($search->search_count) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trending Searches -->
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow text-success"></i> Trending Searches</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Search Query</th>
                                    <th class="text-end">Recent Count</th>
                                    <th class="text-end">Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trendingSearches as $index => $search)
                                <tr>
                                    <td>
                                        <span class="badge badge-trending">#{{ $index + 1 }}</span>
                                    </td>
                                    <td><strong>{{ $search['query'] }}</strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-info">{{ number_format($search['recent_count']) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-up"></i> {{ number_format($search['growth'] * 100, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Searches by Day Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-calendar3"></i> Searches by Day (Last 14 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="searchesByDayChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Searches by Hour Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-clock"></i> Searches by Hour (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="searchesByHourChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Categories -->
        @if($topCategories->isNotEmpty())
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-folder"></i> Top Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Searches</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCategories as $category => $count)
                                <tr>
                                    <td>{{ $category }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($count) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Top Brands -->
        @if($topBrands->isNotEmpty())
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-tag"></i> Top Brands</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Brand</th>
                                    <th class="text-end">Searches</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBrands as $brand => $count)
                                <tr>
                                    <td>{{ $brand }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($count) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Recent Searches -->
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Searches</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Query</th>
                                    <th>Results</th>
                                    <th>User</th>
                                    <th>Filters</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSearches as $search)
                                <tr>
                                    <td>
                                        <small>{{ $search->created_at->format('M d, H:i') }}</small>
                                    </td>
                                    <td><strong>{{ $search->query }}</strong></td>
                                    <td>
                                        <span class="badge {{ $search->results_count > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $search->results_count }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($search->user)
                                            <small>{{ $search->user->email }}</small>
                                        @else
                                            <small class="text-muted">Guest</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($search->filters)
                                            @php
                                                $activeFilters = array_filter($search->filters);
                                            @endphp
                                            @if(count($activeFilters) > 0)
                                                <small class="text-muted">
                                                    {{ implode(', ', array_keys($activeFilters)) }}
                                                </small>
                                            @else
                                                <small class="text-muted">None</small>
                                            @endif
                                        @else
                                            <small class="text-muted">None</small>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No recent searches</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    {{ $recentSearches->appends(request()->except('recent_page'))->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Searches by Day Chart
const dayLabels = {!! json_encode($searchesByDay->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!};
const dayData = {!! json_encode($searchesByDay->pluck('count')) !!};

new Chart(document.getElementById('searchesByDayChart'), {
    type: 'line',
    data: {
        labels: dayLabels,
        datasets: [{
            label: 'Searches',
            data: dayData,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Searches by Hour Chart
const hourLabels = {!! json_encode($searchesByHour->pluck('hour')->map(fn($h) => $h . ':00')) !!};
const hourData = {!! json_encode($searchesByHour->pluck('count')) !!};

new Chart(document.getElementById('searchesByHourChart'), {
    type: 'bar',
    data: {
        labels: hourLabels,
        datasets: [{
            label: 'Searches',
            data: hourData,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
