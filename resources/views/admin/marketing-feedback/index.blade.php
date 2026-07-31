@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Marketing Feedback</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.marketing-feedback.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="btn btn-success">
                <i class="bi bi-download me-2"></i>Export to CSV
            </a>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.marketing-feedback.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Responses</h5>
                    <h2 class="display-4">{{ $stats['total_responses'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-muted">Average Score</h5>
                    <h2 class="display-4">{{ $stats['average_score'] }}</h2>
                    <small class="text-muted">out of 4</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Excellent Ratings</h5>
                    <h2 class="display-4">{{ $stats['rating_distribution']['excellent'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Poor Ratings</h5>
                    <h2 class="display-4">{{ $stats['rating_distribution']['poor'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Rating Distribution Pie Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rating Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="ratingChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Source Distribution Pie Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Traffic Sources</h5>
                </div>
                <div class="card-body">
                    <canvas id="sourceChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Responses Line Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Responses Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Country Distribution Bar Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-globe"></i> Feedback by Country (Top 10)
                    </h5>
                </div>
                <div class="card-body">
                    @if($stats['country_distribution']->count() > 0)
                        <canvas id="countryChart" height="80"></canvas>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">No country data available yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Feedback</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Country</th>
                            <th>Rating</th>
                            <th>Source</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedback as $item)
                        <tr>
                            <td>{{ $item->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($item->order)
                                    <a href="{{ route('admin.orders.show', $item->order->order_number) }}">
                                        {{ $item->order_number }}
                                    </a>
                                @else
                                    {{ $item->order_number }}
                                @endif
                            </td>
                            <td>
                                {{ $item->user_name ?? $item->user?->name ?? 'Guest' }}
                                <br>
                                <small class="text-muted">{{ $item->user_email ?? $item->user?->email }}</small>
                            </td>
                            <td>
                                @if($item->country_name && $item->country_code)
                                    <span class="badge bg-info">
                                        <i class="bi bi-flag-fill"></i> {{ $item->country_code }}
                                    </span>
                                    <br>
                                    <small>{{ $item->country_name }}</small>
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($item->ordering_process_rating) {
                                        'excellent' => 'bg-success',
                                        'good' => 'bg-primary',
                                        'fair' => 'bg-warning',
                                        'poor' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ $item->rating_label }}
                                </span>
                            </td>
                            <td>{{ $item->source_label }}</td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#feedbackModal{{ $item->id }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <form action="{{ route('admin.marketing-feedback.destroy', $item->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      data-swal-confirm="Are you sure you want to delete this feedback?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <p class="text-muted mb-0">No feedback found for the selected date range.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $feedback->appends(['date_from' => $dateFrom, 'date_to' => $dateTo])->links() }}
            </div>
        </div>
    </div>

    <!-- Feedback Detail Modals -->
    @foreach($feedback as $item)
    <div class="modal fade" id="feedbackModal{{ $item->id }}" tabindex="-1" aria-labelledby="feedbackModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel{{ $item->id }}">
                        <i class="bi bi-chat-square-text me-2"></i>Feedback Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="35%">Submitted At:</th>
                                <td>{{ $item->created_at->format('F d, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Order Number:</th>
                                <td>
                                    @if($item->order)
                                        <a href="{{ route('admin.orders.show', $item->order_number) }}" target="_blank">
                                            {{ $item->order_number }} <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </a>
                                    @else
                                        {{ $item->order_number }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Customer Name:</th>
                                <td>{{ $item->user_name ?? $item->user?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Customer Email:</th>
                                <td>{{ $item->user_email ?? $item->user?->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Customer Phone:</th>
                                <td>{{ $item->user_phone ?? $item->user?->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>
                                    @if($item->country_name && $item->country_code)
                                        <span class="badge bg-info" style="font-size: 1rem;">
                                            <i class="bi bi-flag-fill"></i> {{ $item->country_code }}
                                        </span>
                                        <span class="ms-2">{{ $item->country_name }}</span>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ordering Process Rating:</th>
                                <td>
                                    @php
                                        $modalBadgeClass = match($item->ordering_process_rating) {
                                            'excellent' => 'bg-success',
                                            'good' => 'bg-primary',
                                            'fair' => 'bg-warning',
                                            'poor' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $modalBadgeClass }} fs-6">
                                        {{ $item->rating_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>How They Heard About Us:</th>
                                <td>
                                    <span class="badge bg-info text-dark fs-6">
                                        {{ $item->source_label }}
                                    </span>
                                </td>
                            </tr>
                            @if($item->additional_comments)
                            <tr>
                                <th>Additional Comments:</th>
                                <td>
                                    <div class="border rounded p-3 bg-light">
                                        {{ $item->additional_comments }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th>IP Address:</th>
                                <td>{{ $item->ip_address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>User Agent:</th>
                                <td><small class="text-muted">{{ $item->user_agent ?? 'N/A' }}</small></td>
                            </tr>
                            <tr>
                                <th>Device:</th>
                                <td>
                                    <i class="{{ $item->device_icon }} me-2 text-primary"></i>
                                    <span class="fw-bold">{{ $item->device_type }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if($item->order)
                        <a href="{{ route('admin.orders.show', $item->order_number) }}" class="btn btn-primary" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-2"></i>View Order
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
// Disable datalabels plugin globally - we'll enable it per chart
Chart.register(ChartDataLabels);
Chart.defaults.set('plugins.datalabels', {
    display: false
});

// Rating Distribution Pie Chart
const ratingCtx = document.getElementById('ratingChart').getContext('2d');
const ratingData = [
    {{ $stats['rating_distribution']['excellent'] ?? 0 }},
    {{ $stats['rating_distribution']['good'] ?? 0 }},
    {{ $stats['rating_distribution']['fair'] ?? 0 }},
    {{ $stats['rating_distribution']['poor'] ?? 0 }}
];
const ratingTotal = ratingData.reduce((sum, val) => sum + val, 0);

const ratingChart = new Chart(ratingCtx, {
    type: 'pie',
    data: {
        labels: ['Excellent', 'Good', 'Fair', 'Poor'],
        datasets: [{
            data: ratingData,
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(0, 123, 255, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const percentage = ratingTotal > 0 ? ((value / ratingTotal) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            },
            datalabels: {
                display: true,
                formatter: (value, ctx) => {
                    const percentage = ratingTotal > 0 ? ((value / ratingTotal) * 100).toFixed(1) : 0;
                    // Only show label if percentage is above 5% to avoid clutter
                    return percentage > 5 ? percentage + '%' : '';
                },
                color: '#fff',
                font: {
                    weight: 'bold',
                    size: 12
                }
            }
        }
    }
});

// Source Distribution Pie Chart
const sourceCtx = document.getElementById('sourceChart').getContext('2d');
const sourceData = {!! json_encode($stats['source_distribution']->pluck('count')) !!};
const sourceTotal = sourceData.reduce((sum, val) => sum + val, 0);

const sourceChart = new Chart(sourceCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($stats['source_distribution']->pluck('label')) !!},
        datasets: [{
            data: sourceData,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(199, 199, 199, 0.8)',
                'rgba(83, 102, 255, 0.8)',
                'rgba(255, 99, 255, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const percentage = sourceTotal > 0 ? ((value / sourceTotal) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            },
            datalabels: {
                display: true,
                formatter: (value, ctx) => {
                    const percentage = sourceTotal > 0 ? ((value / sourceTotal) * 100).toFixed(1) : 0;
                    // Only show label if percentage is above 5% to avoid clutter
                    return percentage > 5 ? percentage + '%' : '';
                },
                color: '#fff',
                font: {
                    weight: 'bold',
                    size: 12
                }
            }
        }
    }
});

// Daily Responses Line Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($stats['daily_responses']->pluck('date')) !!},
        datasets: [{
            label: 'Responses',
            data: {!! json_encode($stats['daily_responses']->pluck('count')) !!},
            borderColor: 'rgba(0, 123, 255, 1)',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 2,
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
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Country Distribution Bar Chart
@if($stats['country_distribution']->count() > 0)
const countryCtx = document.getElementById('countryChart').getContext('2d');
const countryChart = new Chart(countryCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($stats['country_distribution']->pluck('country')) !!},
        datasets: [{
            label: 'Responses',
            data: {!! json_encode($stats['country_distribution']->pluck('count')) !!},
            backgroundColor: 'rgba(17, 82, 156, 0.8)',
            borderColor: 'rgba(17, 82, 156, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Responses: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
@endif
</script>
@endpush
@endsection

