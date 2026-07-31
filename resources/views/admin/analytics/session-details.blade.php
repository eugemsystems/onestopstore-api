@extends('admin.layout')

@section('title', 'Session Details - Analytics')

@section('content')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #667eea, #764ba2);
    }
    .timeline-item {
        position: relative;
        padding-bottom: 2rem;
    }
    .timeline-dot {
        position: absolute;
        left: -22px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #667eea;
    }
    .timeline-dot.page-view { background: #667eea; }
    .timeline-dot.event { background: #f5576c; }
    .timeline-dot.cart { background: #fcc624; }

    .timeline-content {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid #667eea;
    }
    .timeline-item.event .timeline-content { border-left-color: #f5576c; }
    .timeline-item.cart .timeline-content { border-left-color: #fcc624; }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #667eea;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .info-item {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }

    .info-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 1.125rem;
        font-weight: 600;
        color: #212529;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-person-circle me-2"></i>Session Details</h2>
            <p class="text-muted mb-0">Detailed view of user session activity</p>
        </div>
        <a href="{{ route('admin.analytics.dashboard') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Session Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="stat-card">
                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Session Information</h5>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Session ID</div>
                        <div class="info-value"><code>{{ $session->session_id }}</code></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Started At</div>
                        <div class="info-value">{{ $session->created_at->format('M d, Y H:i:s') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Activity</div>
                        <div class="info-value">{{ $session->last_activity_at ? $session->last_activity_at->format('M d, Y H:i:s') : 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Duration</div>
                        <div class="info-value">
                            @php
                                $minutes = floor($stats['session_duration'] / 60);
                                $seconds = $stats['session_duration'] % 60;
                            @endphp
                            {{ $minutes }}m {{ $seconds }}s
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Device Type</div>
                        <div class="info-value">
                            @php
                                $deviceType = strtolower($session->device_type ?? 'desktop');
                                $iconClass = match($deviceType) {
                                    'mobile' => 'bi-phone-fill',
                                    'tablet' => 'bi-tablet-fill',
                                    default => 'bi-laptop-fill'
                                };
                            @endphp
                            <i class="bi {{ $iconClass }} me-2"></i>{{ ucfirst($session->device_type) }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Browser</div>
                        <div class="info-value">
                            @php
                                $browserName = strtolower($session->browser ?? 'unknown');
                                $iconClass = match(true) {
                                    str_contains($browserName, 'chrome') => 'bi-google',
                                    str_contains($browserName, 'firefox') => 'bi-browser-firefox',
                                    str_contains($browserName, 'safari') => 'bi-browser-safari',
                                    str_contains($browserName, 'edge') => 'bi-browser-edge',
                                    default => 'bi-globe'
                                };
                            @endphp
                            <i class="bi {{ $iconClass }} me-2"></i>{{ $session->browser }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Operating System</div>
                        <div class="info-value">
                            @php
                                $osName = strtolower($session->os ?? 'unknown');
                                $iconClass = match(true) {
                                    str_contains($osName, 'windows') => 'bi-windows',
                                    str_contains($osName, 'mac') || str_contains($osName, 'ios') => 'bi-apple',
                                    str_contains($osName, 'android') => 'bi-android2',
                                    str_contains($osName, 'linux') => 'bi-ubuntu',
                                    default => 'bi-laptop'
                                };
                            @endphp
                            <i class="bi {{ $iconClass }} me-2"></i>{{ $session->os }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">IP Address</div>
                        <div class="info-value">{{ $session->ip_address }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Landing Page</div>
                        <div class="info-value text-truncate" title="{{ $session->landing_page }}">
                            {{ Str::limit($session->landing_page, 30) }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Referrer</div>
                        <div class="info-value text-truncate" title="{{ $session->referrer }}">
                            {{ $session->referrer ? Str::limit($session->referrer, 30) : 'Direct' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted mb-2">Page Views</h6>
                <h3 class="mb-0 text-primary">{{ $stats['total_page_views'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #f5576c;">
                <h6 class="text-muted mb-2">Events</h6>
                <h3 class="mb-0 text-danger">{{ $stats['total_events'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #fcc624;">
                <h6 class="text-muted mb-2">Cart Abandonments</h6>
                <h3 class="mb-0 text-warning">{{ $stats['total_cart_abandonments'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #00c853;">
                <h6 class="text-muted mb-2">Pages/Minute</h6>
                <h3 class="mb-0 text-success">{{ number_format($stats['pages_per_minute'], 2) }}</h3>
            </div>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activity Timeline</h5>
                    <div class="text-muted small">
                        Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} activities
                    </div>
                </div>

                <div class="timeline">
                    @forelse($timeline as $activity)
                        <div class="timeline-item {{ $activity['type'] }}">
                            <div class="timeline-dot {{ $activity['type'] }}"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        @if($activity['type'] === 'page_view')
                                            <h6 class="mb-1">
                                                <i class="bi bi-eye-fill text-primary me-2"></i>
                                                Page View: {{ $activity['data']->page_title ?? 'Untitled' }}
                                            </h6>
                                            <p class="mb-1 text-muted small">{{ $activity['data']->url }}</p>
                                            <p class="mb-0 small">
                                                <span class="badge bg-light text-dark">{{ $activity['data']->path }}</span>
                                                @if($activity['data']->duration)
                                                    <span class="badge bg-info ms-2">Duration: {{ $activity['data']->duration }}s</span>
                                                @endif
                                            </p>
                                        @elseif($activity['type'] === 'event')
                                            <h6 class="mb-1">
                                                <i class="bi bi-lightning-fill text-danger me-2"></i>
                                                Event: {{ $activity['data']->event_name }}
                                            </h6>
                                            <p class="mb-1 text-muted small">Type: {{ $activity['data']->event_type }}</p>
                                            @if($activity['data']->event_category)
                                                <p class="mb-0 small">
                                                    <span class="badge bg-secondary">{{ $activity['data']->event_category }}</span>
                                                </p>
                                            @endif
                                            @if($activity['data']->event_data)
                                                <details class="mt-2">
                                                    <summary class="small text-muted" style="cursor: pointer;">View event data</summary>
                                                    <pre class="small mt-2 mb-0 bg-light p-2 rounded">{{ json_encode($activity['data']->event_data, JSON_PRETTY_PRINT) }}</pre>
                                                </details>
                                            @endif
                                        @elseif($activity['type'] === 'cart_abandonment')
                                            <h6 class="mb-1">
                                                <i class="bi bi-cart-x-fill text-warning me-2"></i>
                                                Cart Abandoned
                                            </h6>
                                            <p class="mb-1 text-muted small">Stage: {{ $activity['data']->abandonment_stage }}</p>
                                            <p class="mb-0 small">
                                                <span class="badge bg-warning text-dark">{{ $activity['data']->items_count }} items</span>
                                                <span class="badge bg-danger ms-2">Value: ${{ number_format($activity['data']->cart_value, 2) }}</span>
                                            </p>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $activity['timestamp']->format('H:i:s') }}</small>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No activity recorded for this session.</p>
                    @endforelse
                </div>

                <!-- Pagination Controls -->
                @if($pagination['total'] > $pagination['per_page'])
                <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                    <div class="text-muted small">
                        Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} activities
                    </div>

                    <nav aria-label="Timeline pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous Button -->
                            <li class="page-item {{ $pagination['prev_page'] ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $pagination['prev_page'] ? route('admin.analytics.session-details', ['sessionId' => $session->session_id, 'page' => $pagination['prev_page']]) : '#' }}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <!-- Page Numbers -->
                            @php
                                $start = max(1, $pagination['current_page'] - 2);
                                $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                            @endphp

                            @if($start > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ route('admin.analytics.session-details', ['sessionId' => $session->session_id, 'page' => 1]) }}">1</a>
                                </li>
                                @if($start > 2)
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                @endif
                            @endif

                            @for($i = $start; $i <= $end; $i++)
                                <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                    <a class="page-link" href="{{ route('admin.analytics.session-details', ['sessionId' => $session->session_id, 'page' => $i]) }}">{{ $i }}</a>
                                </li>
                            @endfor

                            @if($end < $pagination['last_page'])
                                @if($end < $pagination['last_page'] - 1)
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                @endif
                                <li class="page-item">
                                    <a class="page-link" href="{{ route('admin.analytics.session-details', ['sessionId' => $session->session_id, 'page' => $pagination['last_page']]) }}">{{ $pagination['last_page'] }}</a>
                                </li>
                            @endif

                            <!-- Next Button -->
                            <li class="page-item {{ $pagination['next_page'] ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $pagination['next_page'] ? route('admin.analytics.session-details', ['sessionId' => $session->session_id, 'page' => $pagination['next_page']]) : '#' }}" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

