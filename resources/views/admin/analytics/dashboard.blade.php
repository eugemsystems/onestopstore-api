@extends('admin.layout')

@section('title', 'Analytics Dashboard - Admin Panel')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
    .analytics-card {
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        background: white;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    .analytics-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    .stat-box {
        padding: 2rem;
        border-radius: 12px;
        color: white;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
    }
    .stat-box::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        transform: translate(40%, -40%);
    }
    .stat-box.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-box.success { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-box.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-box.warning { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .stat-box.danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }

    .stat-value {
        font-size: 2.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    .stat-label {
        font-size: 0.95rem;
        opacity: 0.95;
        margin: 0;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }
    .stat-description {
        font-size: 0.75rem;
        opacity: 0.85;
        margin-top: 0.5rem;
        position: relative;
        z-index: 1;
        line-height: 1.3;
    }
    .stat-icon {
        font-size: 3.5rem;
        opacity: 0.25;
        position: absolute;
        right: 25px;
        top: 50%;
        transform: translateY(-50%);
    }
    .info-icon {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-left: 5px;
        cursor: help;
    }

    /* Device/Browser/OS Icons */
    .tech-icon {
        font-size: 1.5rem;
        margin-right: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .tech-icon.windows { background: linear-gradient(135deg, #0078d4 0%, #00bcf2 100%); }
    .tech-icon.mac { background: linear-gradient(135deg, #999 0%, #555 100%); }
    .tech-icon.linux { background: linear-gradient(135deg, #fcc624 0%, #f57c00 100%); }
    .tech-icon.android { background: linear-gradient(135deg, #3ddc84 0%, #00c853 100%); }
    .tech-icon.ios { background: linear-gradient(135deg, #147efb 0%, #0a64d9 100%); }

    .tech-icon.chrome { background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); }
    .tech-icon.firefox { background: linear-gradient(135deg, #ff7139 0%, #e66000 100%); }
    .tech-icon.safari { background: linear-gradient(135deg, #006cff 0%, #00aaff 100%); }
    .tech-icon.edge { background: linear-gradient(135deg, #0078d7 0%, #00a4ef 100%); }

    .tech-icon.mobile { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .tech-icon.tablet { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .tech-icon.desktop { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

    .tech-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .tech-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .tech-item-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .clickable-row {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .clickable-row:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
    }
    .chart-container {
        position: relative;
        padding: 2rem;
        background: white;
        border-radius: 12px;
    }
    .period-selector {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .active-users-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.85; }
    }
    .pulse-dot {
        width: 10px;
        height: 10px;
        background: #4ade80;
        border-radius: 50%;
        animation: pulse-dot 1.5s infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
        50% { box-shadow: 0 0 0 8px rgba(74, 222, 128, 0); }
    }
    .table-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #e0e0e0;
    }
    .device-icon {
        font-size: 1.5rem;
        margin-right: 10px;
        color: #667eea;
    }
    .list-group-item {
        border: none;
        border-bottom: 1px solid #f0f0f0;
        padding: 1rem 0;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .badge-custom {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    }

    /* Table Icon Enhancements - Official Brand Colors */
    #recentSessionsTable td i.bi {
        font-size: 1.4rem;
        vertical-align: middle;
        cursor: help;
    }

    /* Device Icons - Purple/Blue gradient theme */
    #recentSessionsTable td i.bi-phone-fill {
        color: #5856d6; /* iOS Purple */
    }

    #recentSessionsTable td i.bi-tablet-fill {
        color: #007aff; /* iOS Blue */
    }

    #recentSessionsTable td i.bi-laptop-fill {
        color: #64748b; /* Slate Blue */
    }

    /* Browser Icons - Official Brand Colors */
    #recentSessionsTable td i.bi-google {
        color: #4285f4; /* Google Blue */
    }

    #recentSessionsTable td i.bi-browser-firefox {
        color: #ff9500; /* Firefox Orange - Official */
    }

    #recentSessionsTable td i.bi-apple {
        color: #000000; /* Apple Black */
    }

    #recentSessionsTable td i.bi-microsoft-teams {
        color: #0078d4; /* Microsoft Blue */
    }

    #recentSessionsTable td i.bi-shield-fill-check {
        color: #fb542b; /* Brave Orange - Official */
    }

    #recentSessionsTable td i.bi-music-note-beamed {
        color: #ff1b2d; /* Opera Red - Official */
    }

    #recentSessionsTable td i.bi-browser-chrome {
        color: #5f6368; /* Chrome Gray */
    }

    /* OS Icons - Official Brand Colors */
    #recentSessionsTable td i.bi-windows {
        color: #0078d4; /* Windows Blue - Official */
    }

    #recentSessionsTable td i.bi-apple {
        color: #000000; /* Apple Black - Official */
    }

    #recentSessionsTable td i.bi-android2 {
        color: #3ddc84; /* Android Green - Official */
    }

    #recentSessionsTable td i.bi-ubuntu {
        color: #e95420; /* Ubuntu Orange - Official */
    }

    #recentSessionsTable td i.bi-pc-display {
        color: #6c757d; /* Generic Gray */
    }

    #recentSessionsTable td i.bi-geo-alt {
        color: #6c757d; /* Gray for unknown location */
    }

    #recentSessionsTable td img {
        vertical-align: middle;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        border-radius: 2px;
        cursor: help;
    }

    /* Center align icon columns */
    #recentSessionsTable thead th:nth-child(3),
    #recentSessionsTable thead th:nth-child(4),
    #recentSessionsTable thead th:nth-child(5),
    #recentSessionsTable thead th:nth-child(6),
    #recentSessionsTable tbody td:nth-child(3),
    #recentSessionsTable tbody td:nth-child(4),
    #recentSessionsTable tbody td:nth-child(5),
    #recentSessionsTable tbody td:nth-child(6) {
        text-align: center;
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header">
        <h2 class="mb-2"><i class="bi bi-graph-up me-2"></i>Analytics Dashboard</h2>
        <p class="mb-0 opacity-90">Comprehensive website analytics and visitor insights</p>
    </div>

    <!-- Period Selector -->
    <div class="period-selector">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-0">Website Traffic Overview</h5>
                <small class="text-muted">Real-time analytics and insights</small>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="active-users-badge">
                    <span class="pulse-dot"></span>
                    <span id="activeUsersCount">{{ $stats['overview']['active_users_now'] ?? 0 }}</span> Active Users
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.analytics.dashboard', ['period' => '24h']) }}"
                       class="btn btn-sm {{ $period === '24h' ? 'btn-primary' : 'btn-outline-primary' }}">24 Hours</a>
                    <a href="{{ route('admin.analytics.dashboard', ['period' => '7d']) }}"
                       class="btn btn-sm {{ $period === '7d' ? 'btn-primary' : 'btn-outline-primary' }}">7 Days</a>
                    <a href="{{ route('admin.analytics.dashboard', ['period' => '30d']) }}"
                       class="btn btn-sm {{ $period === '30d' ? 'btn-primary' : 'btn-outline-primary' }}">30 Days</a>
                    <a href="{{ route('admin.analytics.dashboard', ['period' => '90d']) }}"
                       class="btn btn-sm {{ $period === '90d' ? 'btn-primary' : 'btn-outline-primary' }}">90 Days</a>
                </div>
            </div>
        </div>
    </div>

    @if($stats)
    <!-- Overview Stats -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-box primary">
                <i class="bi bi-people-fill stat-icon"></i>
                <h2 class="stat-value">{{ number_format($stats['overview']['total_sessions']) }}</h2>
                <p class="stat-label">
                    Total Sessions
                    <i class="bi bi-info-circle info-icon"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="A session is a visit to your website. One user can have multiple sessions."></i>
                </p>
                <p class="stat-description">Number of visits to your website</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-box success">
                <i class="bi bi-eye-fill stat-icon"></i>
                <h2 class="stat-value">{{ number_format($stats['overview']['total_page_views']) }}</h2>
                <p class="stat-label">
                    Page Views
                    <i class="bi bi-info-circle info-icon"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Total number of pages viewed by all visitors. Multiple views of the same page count separately."></i>
                </p>
                <p class="stat-description">Total pages viewed by visitors</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-box info">
                <i class="bi bi-person-check-fill stat-icon"></i>
                <h2 class="stat-value">{{ number_format($stats['overview']['unique_visitors']) }}</h2>
                <p class="stat-label">
                    Unique Visitors
                    <i class="bi bi-info-circle info-icon"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Number of different people who visited your site. Each person is counted only once."></i>
                </p>
                <p class="stat-description">Individual people who visited</p>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-box warning">
                <i class="bi bi-cursor-fill stat-icon"></i>
                <h2 class="stat-value">{{ number_format($stats['overview']['total_events']) }}</h2>
                <p class="stat-label">
                    User Events
                    <i class="bi bi-info-circle info-icon"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Actions users took on your site like clicking buttons, adding to cart, searching, etc."></i>
                </p>
                <p class="stat-description">User actions & interactions</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="analytics-card">
                <div class="chart-container" style="position: relative; height: 500px;">
                    <h5 class="mb-4"><i class="bi bi-graph-up me-2"></i>Traffic Overview</h5>
                    <canvas id="trafficChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="analytics-card">
                <div class="chart-container" style="position: relative; height: 400px;">
                    <h5 class="mb-4"><i class="bi bi-phone me-2"></i>Device Types</h5>
                    <canvas id="deviceChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Pages and Search Queries -->
    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-file-text me-2"></i>Top Pages</h5>
                <div class="table-responsive">
                    <table id="topPagesTable" class="table table-hover display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th class="text-end">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_pages'] as $page)
                            <tr>
                                <td>
                                    <strong>{{ $page['page_title'] ?? 'Untitled' }}</strong><br>
                                    <small class="text-muted">{{ $page['path'] }}</small>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary badge-custom">{{ number_format($page['views']) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-search me-2"></i>Top Search Queries</h5>
                <div class="table-responsive">
                    <table id="searchQueriesTable" class="table table-hover display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Search Query</th>
                                <th>Searches</th>
                                <th>Avg Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_searches'] ?? [] as $search)
                            <tr>
                                <td>
                                    <i class="bi bi-search text-muted me-2"></i>
                                    <strong>{{ $search->search_query ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ number_format($search->count) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($search->avg_results ?? 0, 1) }} items</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-link-45deg me-2"></i>Top Referrers</h5>
                <div class="table-responsive">
                    <table id="referrersTable" class="table table-hover display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Referrer</th>
                                <th class="text-end">Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['referrers'] as $referrer)
                            <tr>
                                <td>{{ $referrer['referrer'] }}</td>
                                <td class="text-end">
                                    <span class="badge bg-info badge-custom">{{ number_format($referrer['count']) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sessions -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i>Recent Sessions <small class="text-muted">(Click to view details)</small></h5>
                <div class="table-responsive">
                    <table id="recentSessionsTable" class="table table-hover display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Started</th>
                                <th>Device</th>
                                <th>Browser</th>
                                <th>OS</th>
                                <th>Location</th>
                                <th>Page Views</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recentSessions = \App\Models\Analytics\UserSession::with('pageViews')
                                    ->human()
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get();
                            @endphp
                            @foreach($recentSessions as $session)
                            <tr class="clickable-row" onclick="window.location='{{ route('admin.analytics.session-details', $session->session_id) }}'">
                                <td>
                                    <code class="text-primary">{{ Str::limit($session->session_id, 20) }}</code>
                                </td>
                                <td data-order="{{ $session->created_at->timestamp }}">
                                    <small>{{ $session->created_at->format('M d, Y H:i') }}</small>
                                </td>
                                <td>
                                    @php
                                        $deviceType = strtolower($session->device_type ?? 'desktop');
                                        $iconClass = match($deviceType) {
                                            'mobile' => 'bi-phone-fill',
                                            'tablet' => 'bi-tablet-fill',
                                            default => 'bi-laptop-fill'
                                        };
                                    @endphp
                                    <i class="bi {{ $iconClass }}" title="{{ ucfirst($session->device_type) }}"></i>
                                </td>
                                <td>
                                    @php
                                        $browser = strtolower($session->browser ?? '');
                                        $browserIcon = match(true) {
                                            str_contains($browser, 'chrome') && !str_contains($browser, 'edge') => 'bi-google',
                                            str_contains($browser, 'firefox') => 'bi-browser-firefox',
                                            str_contains($browser, 'safari') && !str_contains($browser, 'chrome') => 'bi-apple',
                                            str_contains($browser, 'edge') => 'bi-microsoft-teams',
                                            str_contains($browser, 'opera') => 'bi-music-note-beamed',
                                            str_contains($browser, 'brave') => 'bi-shield-fill-check',
                                            default => 'bi-browser-chrome'
                                        };
                                    @endphp
                                    <i class="bi {{ $browserIcon }}" title="{{ $session->browser }}"></i>
                                </td>
                                <td>
                                    @php
                                        $os = strtolower($session->os ?? '');
                                        $osIcon = match(true) {
                                            str_contains($os, 'windows') => 'bi-windows',
                                            str_contains($os, 'mac') || str_contains($os, 'ios') => 'bi-apple',
                                            str_contains($os, 'android') => 'bi-android2',
                                            str_contains($os, 'linux') => 'bi-ubuntu',
                                            default => 'bi-pc-display'
                                        };
                                    @endphp
                                    <i class="bi {{ $osIcon }}" title="{{ $session->os }}"></i>
                                </td>
                                <td>
                                    @if($session->country)
                                        @php
                                            // Map country names to ISO 3166-1 alpha-2 codes
                                            $countryMap = [
                                                'United States' => 'us', 'United Kingdom' => 'gb', 'Canada' => 'ca',
                                                'Australia' => 'au', 'Germany' => 'de', 'France' => 'fr', 'Italy' => 'it',
                                                'Spain' => 'es', 'Netherlands' => 'nl', 'Belgium' => 'be', 'Sweden' => 'se',
                                                'Norway' => 'no', 'Denmark' => 'dk', 'Finland' => 'fi', 'Poland' => 'pl',
                                                'Russia' => 'ru', 'China' => 'cn', 'Japan' => 'jp', 'South Korea' => 'kr',
                                                'India' => 'in', 'Brazil' => 'br', 'Mexico' => 'mx', 'Argentina' => 'ar',
                                                'South Africa' => 'za', 'Nigeria' => 'ng', 'Kenya' => 'ke', 'Egypt' => 'eg',
                                                'Morocco' => 'ma', 'Ghana' => 'gh', 'Tanzania' => 'tz', 'Uganda' => 'ug',
                                                'Ethiopia' => 'et', 'Zimbabwe' => 'zw', 'Zambia' => 'zm', 'Botswana' => 'bw',
                                                'Namibia' => 'na', 'Angola' => 'ao', 'Mozambique' => 'mz', 'Malawi' => 'mw',
                                                'Rwanda' => 'rw', 'Senegal' => 'sn', 'Ivory Coast' => 'ci', 'Cameroon' => 'cm',
                                                'Turkey' => 'tr', 'Saudi Arabia' => 'sa', 'UAE' => 'ae', 'Israel' => 'il',
                                                'Singapore' => 'sg', 'Malaysia' => 'my', 'Thailand' => 'th', 'Vietnam' => 'vn',
                                                'Philippines' => 'ph', 'Indonesia' => 'id', 'Pakistan' => 'pk', 'Bangladesh' => 'bd',
                                                'New Zealand' => 'nz', 'Ireland' => 'ie', 'Portugal' => 'pt', 'Greece' => 'gr',
                                                'Switzerland' => 'ch', 'Austria' => 'at', 'Czech Republic' => 'cz', 'Hungary' => 'hu',
                                                'Romania' => 'ro', 'Ukraine' => 'ua', 'Chile' => 'cl', 'Colombia' => 'co',
                                                'Peru' => 'pe', 'Venezuela' => 've', 'Ecuador' => 'ec', 'Bolivia' => 'bo',
                                            ];

                                            $countryCode = $countryMap[$session->country] ?? strtolower(substr($session->country, 0, 2));
                                        @endphp
                                        <img src="https://flagcdn.com/20x15/{{ $countryCode }}.png"
                                             srcset="https://flagcdn.com/40x30/{{ $countryCode }}.png 2x, https://flagcdn.com/60x45/{{ $countryCode }}.png 3x"
                                             width="20" height="15" alt="{{ $session->country }}" title="{{ $session->country }}">
                                    @else
                                        <i class="bi bi-geo-alt text-muted" title="Unknown"></i>
                                    @endif
                                </td>
                                <td data-order="{{ $session->total_page_views ?? $session->pageViews->count() }}">
                                    <span class="badge bg-primary">{{ $session->total_page_views ?? $session->pageViews->count() }}</span>
                                </td>
                                <td data-order="{{ $session->duration ?? ($session->last_activity_at ? $session->last_activity_at->diffInSeconds($session->started_at) : 0) }}">
                                    @php
                                        $duration = $session->duration ?? ($session->last_activity_at ? $session->last_activity_at->diffInSeconds($session->started_at) : 0);
                                        $minutes = floor($duration / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    <small>{{ $minutes }}m {{ $seconds }}s</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.analytics.session-details', $session->session_id) }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices, Browsers, OS -->
    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-phone me-2"></i>Devices</h5>
                <div class="list-group list-group-flush">
                    @forelse($stats['devices'] as $device)
                    @php
                        $deviceType = strtolower($device['device_type'] ?? 'desktop');
                        $iconClass = match($deviceType) {
                            'mobile' => 'bi-phone-fill',
                            'tablet' => 'bi-tablet-fill',
                            default => 'bi-laptop-fill'
                        };
                    @endphp
                    <div class="tech-item">
                        <div class="tech-icon {{ $deviceType }}">
                            <i class="bi {{ $iconClass }}"></i>
                        </div>
                        <div class="tech-item-content">
                            <span class="fw-semibold text-capitalize">{{ $device['device_type'] ?? 'Unknown' }}</span>
                            <span class="badge bg-primary rounded-pill">{{ number_format($device['count']) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">No data available</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-globe me-2"></i>Browsers</h5>
                <div class="list-group list-group-flush">
                    @forelse($stats['browsers'] as $browser)
                    @php
                        $browserName = strtolower($browser['browser'] ?? 'unknown');
                        $iconClass = match(true) {
                            str_contains($browserName, 'chrome') => 'bi-google',
                            str_contains($browserName, 'firefox') => 'bi-browser-firefox',
                            str_contains($browserName, 'safari') => 'bi-browser-safari',
                            str_contains($browserName, 'edge') => 'bi-browser-edge',
                            str_contains($browserName, 'opera') => 'bi-browser-chrome',
                            default => 'bi-globe'
                        };
                        $browserClass = match(true) {
                            str_contains($browserName, 'chrome') => 'chrome',
                            str_contains($browserName, 'firefox') => 'firefox',
                            str_contains($browserName, 'safari') => 'safari',
                            str_contains($browserName, 'edge') => 'edge',
                            default => 'desktop'
                        };
                    @endphp
                    <div class="tech-item">
                        <div class="tech-icon {{ $browserClass }}">
                            <i class="bi {{ $iconClass }}"></i>
                        </div>
                        <div class="tech-item-content">
                            <span class="fw-semibold">{{ $browser['browser'] ?? 'Unknown' }}</span>
                            <span class="badge bg-success rounded-pill">{{ number_format($browser['count']) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">No data available</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-laptop me-2"></i>Operating Systems</h5>
                <div class="list-group list-group-flush">
                    @forelse($stats['os'] as $os)
                    @php
                        $osName = strtolower($os['os'] ?? 'unknown');
                        $iconClass = match(true) {
                            str_contains($osName, 'windows') => 'bi-windows',
                            str_contains($osName, 'mac') || str_contains($osName, 'ios') => 'bi-apple',
                            str_contains($osName, 'android') => 'bi-android2',
                            str_contains($osName, 'linux') => 'bi-ubuntu',
                            default => 'bi-laptop'
                        };
                        $osClass = match(true) {
                            str_contains($osName, 'windows') => 'windows',
                            str_contains($osName, 'mac') => 'mac',
                            str_contains($osName, 'ios') => 'ios',
                            str_contains($osName, 'android') => 'android',
                            str_contains($osName, 'linux') => 'linux',
                            default => 'desktop'
                        };
                    @endphp
                    <div class="tech-item">
                        <div class="tech-icon {{ $osClass }}">
                            <i class="bi {{ $iconClass }}"></i>
                        </div>
                        <div class="tech-item-content">
                            <span class="fw-semibold">{{ $os['os'] ?? 'Unknown' }}</span>
                            <span class="badge bg-info rounded-pill">{{ number_format($os['count']) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">No data available</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Abandonments -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="analytics-card">
                <div class="p-4">
                    <h5 class="mb-4"><i class="bi bi-cart-x me-2"></i>Cart Abandonment Analysis</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="stat-box danger">
                                <h3 class="stat-value">{{ number_format($stats['cart_abandonments']['total']) }}</h3>
                                <p class="stat-label">Total Abandonments</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box success">
                                <h3 class="stat-value">{{ number_format($stats['cart_abandonments']['recovered']) }}</h3>
                                <p class="stat-label">Recovered</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box warning">
                                <h3 class="stat-value">${{ number_format($stats['cart_abandonments']['total_value'], 2) }}</h3>
                                <p class="stat-label">Lost Revenue</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-box info">
                                @php
                                    $recoveryRate = $stats['cart_abandonments']['total'] > 0
                                        ? ($stats['cart_abandonments']['recovered'] / $stats['cart_abandonments']['total']) * 100
                                        : 0;
                                @endphp
                                <h3 class="stat-value">{{ number_format($recoveryRate, 1) }}%</h3>
                                <p class="stat-label">Recovery Rate</p>
                            </div>
                        </div>
                    </div>

                    @if(!empty($stats['cart_abandonments']['by_stage']) && count($stats['cart_abandonments']['by_stage']) > 0)
                    <div class="mt-4">
                        <h6 class="mb-3">Abandonment by Stage</h6>
                        <div class="row">
                            @foreach($stats['cart_abandonments']['by_stage'] as $stage)
                            <div class="col-md-3 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h4 class="text-primary mb-2">{{ number_format($stage['count']) }}</h4>
                                        <p class="text-muted mb-0 small text-capitalize">{{ str_replace('_', ' ', $stage['abandonment_stage']) }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Specials Button Analytics -->
    @php
        $sc = $stats['specials_clicks'];
        $scTotal   = $sc['total'];
        $scDesktop = $sc['desktop'];
        $scMobile  = $sc['mobile'];
        $scDaily   = $sc['daily'];
        $scDesktopPct = $scTotal > 0 ? round($scDesktop / $scTotal * 100) : 0;
        $scMobilePct  = $scTotal > 0 ? round($scMobile  / $scTotal * 100) : 0;
    @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="table-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-tag-fill me-2 text-warning"></i>
                        Specials Button Clicks
                        <span class="badge bg-warning text-dark ms-2" style="font-size:.7rem">{{ $period }}</span>
                    </h5>
                    @if($scTotal === 0)
                        <span class="badge bg-secondary">No clicks yet in this period</span>
                    @endif
                </div>

                <div class="row g-3 mb-4">
                    <!-- Total -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 text-center" style="background:linear-gradient(135deg,#f6d365,#fda085)">
                            <div style="font-size:2.4rem;font-weight:700;color:#fff;line-height:1">{{ number_format($scTotal) }}</div>
                            <div style="color:rgba(255,255,255,.9);font-size:.85rem;margin-top:.25rem">Total Clicks</div>
                        </div>
                    </div>
                    <!-- Desktop -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 text-center" style="background:linear-gradient(135deg,#4facfe,#00f2fe)">
                            <div style="font-size:2.4rem;font-weight:700;color:#fff;line-height:1">{{ number_format($scDesktop) }}</div>
                            <div style="color:rgba(255,255,255,.9);font-size:.85rem;margin-top:.25rem">
                                <i class="bi bi-display me-1"></i>Desktop
                                @if($scTotal > 0)<span class="ms-1 opacity-75">({{ $scDesktopPct }}%)</span>@endif
                            </div>
                        </div>
                    </div>
                    <!-- Mobile -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 text-center" style="background:linear-gradient(135deg,#a18cd1,#fbc2eb)">
                            <div style="font-size:2.4rem;font-weight:700;color:#fff;line-height:1">{{ number_format($scMobile) }}</div>
                            <div style="color:rgba(255,255,255,.9);font-size:.85rem;margin-top:.25rem">
                                <i class="bi bi-phone me-1"></i>Mobile
                                @if($scTotal > 0)<span class="ms-1 opacity-75">({{ $scMobilePct }}%)</span>@endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($scTotal > 0)
                    <!-- Desktop vs Mobile bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span><i class="bi bi-display me-1"></i>Desktop {{ $scDesktopPct }}%</span>
                            <span>Mobile {{ $scMobilePct }}% <i class="bi bi-phone ms-1"></i></span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:6px">
                            <div class="progress-bar" style="width:{{ $scDesktopPct }}%;background:linear-gradient(90deg,#4facfe,#00f2fe)" title="Desktop"></div>
                            <div class="progress-bar" style="width:{{ $scMobilePct }}%;background:linear-gradient(90deg,#a18cd1,#fbc2eb)" title="Mobile"></div>
                        </div>
                    </div>

                    <!-- Daily breakdown table -->
                    @if($scDaily->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Clicks</th>
                                    <th class="ps-3">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $maxDay = $scDaily->max('count') ?: 1; @endphp
                                @foreach($scDaily as $row)
                                <tr>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($row->day)->format('D, M j') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row->count) }}</td>
                                    <td class="ps-3" style="width:40%">
                                        <div class="progress" style="height:6px;border-radius:4px">
                                            <div class="progress-bar bg-warning" style="width:{{ round($row->count / $maxDay * 100) }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                @else
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-tag" style="font-size:2.5rem;opacity:.3"></i>
                        <p class="mt-2 mb-0 small">No specials button clicks recorded in this period.<br>Once users start clicking, their activity will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Events -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="table-card">
                <h5 class="mb-3"><i class="bi bi-lightning-fill me-2"></i>Top User Events</h5>
                <div class="table-responsive">
                    <table id="topEventsTable" class="table table-hover display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Type</th>
                                <th class="text-end">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_events'] as $event)
                            <tr>
                                <td class="fw-semibold">{{ $event['event_name'] }}</td>
                                <td><span class="badge bg-secondary">{{ $event['event_type'] }}</span></td>
                                <td class="text-end">
                                    <span class="badge bg-primary badge-custom">{{ number_format($event['count']) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works Info Panel -->
    <div class="alert alert-info alert-dismissible fade show" role="alert" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;">
            <h5 class="alert-heading mb-3">
                <i class="bi bi-info-circle me-2"></i>How Analytics Tracking Works
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h6><i class="bi bi-people-fill me-2"></i>Active Users</h6>
                    <ul class="mb-0 small">
                        <li>Counts users who viewed a page in the <strong>last 5 minutes</strong></li>
                        <li>Based on unique sessions (not IP addresses)</li>
                        <li>Updates automatically every <strong>30 seconds</strong></li>
                        <li>Session stays active as long as user browses (heartbeat every 60s)</li>
                        <li>Session expires after <strong>5 minutes</strong> of inactivity</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-cart-x me-2"></i>Cart Abandonment</h6>
                    <ul class="mb-0 small">
                        <li>Tracks when users add items to cart but don't checkout</li>
                        <li>Records abandonment when user:
                            <ul>
                                <li>Closes browser/tab with items in cart</li>
                                <li>Navigates away from cart page</li>
                                <li>Stays idle on cart page for <strong>5 minutes</strong></li>
                            </ul>
                        </li>
                        <li>Captures cart value, items, and abandonment stage</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

    @else
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>No analytics data available.</strong> Start tracking to see analytics.
    </div>
    @endif
</div>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
// Wait for both DOM and jQuery to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips (vanilla JS)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Use jQuery for DataTables initialization
jQuery(document).ready(function($) {
    // Initialize DataTables with common options
    const dataTableOptions = {
        pageLength: 10,
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching records found",
            emptyTable: "No data available in table"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    };

    // Initialize Top Pages Table
    if ($('#topPagesTable').length) {
        $('#topPagesTable').DataTable({
            ...dataTableOptions,
            order: [[1, 'desc']],
            columnDefs: [
                { targets: 1, orderable: true, className: 'text-end' }
            ]
        });
    }

    // Initialize Search Queries Table
    if ($('#searchQueriesTable').length) {
        $('#searchQueriesTable').DataTable({
            ...dataTableOptions,
            order: [[1, 'desc']],
            columnDefs: [
                { targets: [1, 2], orderable: true }
            ]
        });
    }

    // Initialize Referrers Table
    if ($('#referrersTable').length) {
        $('#referrersTable').DataTable({
            ...dataTableOptions,
            order: [[1, 'desc']],
            columnDefs: [
                { targets: 1, orderable: true, className: 'text-end' }
            ]
        });
    }

    // Initialize Recent Sessions Table
    if ($('#recentSessionsTable').length) {
        $('#recentSessionsTable').DataTable({
            ...dataTableOptions,
            order: [[1, 'desc']], // Sort by Started date descending
            pageLength: 25,
            columnDefs: [
                { targets: 0, orderable: true }, // Session ID
                { targets: 1, orderable: true, type: 'num' }, // Started (uses data-order attribute)
                { targets: 2, orderable: true }, // Device
                { targets: 3, orderable: true }, // Browser
                { targets: 4, orderable: true }, // OS
                { targets: 5, orderable: true }, // Location
                { targets: 6, orderable: true, type: 'num' }, // Page Views (uses data-order attribute)
                { targets: 7, orderable: true, type: 'num' }, // Duration (uses data-order attribute)
                { targets: 8, orderable: false } // Action buttons
            ]
        });
    }

    // Initialize Top Events Table
    if ($('#topEventsTable').length) {
        $('#topEventsTable').DataTable({
            ...dataTableOptions,
            order: [[2, 'desc']],
            columnDefs: [
                { targets: 2, orderable: true, className: 'text-end' }
            ]
        });
    }

    // Fetch and update active users count
    function updateActiveUsers() {
        fetch('{{ route('admin.analytics.active-users') }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('activeUsersCount').textContent = data.active_users || 0;
            })
            .catch(error => console.error('Error fetching active users:', error));
    }

    updateActiveUsers();
    setInterval(updateActiveUsers, 10000); // Update every 10 seconds for more real-time data
});

// Chart.js initialization (vanilla JS, doesn't need jQuery)
document.addEventListener('DOMContentLoaded', function() {
    // Disable datalabels plugin globally - we'll enable it per chart
    Chart.register(ChartDataLabels);
    Chart.defaults.set('plugins.datalabels', {
        display: false
    });

    @if(isset($timeseries) && $timeseries)
    // Traffic Chart - Enhanced bar chart instead of line chart
    const trafficCtx = document.getElementById('trafficChart');
    if (trafficCtx) {
        new Chart(trafficCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(collect($timeseries['page_views'])->pluck('period')) !!},
                datasets: [
                    {
                        label: 'Page Views',
                        data: {!! json_encode(collect($timeseries['page_views'])->pluck('count')) !!},
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgb(102, 126, 234)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: 'Sessions',
                        data: {!! json_encode(collect($timeseries['sessions'])->pluck('count')) !!},
                        backgroundColor: 'rgba(245, 87, 108, 0.8)',
                        borderColor: 'rgb(245, 87, 108)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: 'Events',
                        data: {!! json_encode(collect($timeseries['events'])->pluck('count')) !!},
                        backgroundColor: 'rgba(74, 172, 254, 0.8)',
                        borderColor: 'rgb(74, 172, 254)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2.5,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toLocaleString();
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    @endif

    @if(isset($stats) && $stats)
    // Device Chart - Enhanced pie chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        const deviceData = {!! json_encode($stats['devices']) !!};
        if (deviceData && deviceData.length > 0) {
            const total = deviceData.reduce((sum, d) => sum + parseInt(d.count), 0);
            new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: deviceData.map(d => (d.device_type || 'Unknown').charAt(0).toUpperCase() + (d.device_type || 'Unknown').slice(1)),
                    datasets: [{
                        data: deviceData.map(d => d.count),
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(245, 87, 108, 0.8)',
                            'rgba(74, 172, 254, 0.8)',
                            'rgba(250, 112, 154, 0.8)',
                            'rgba(250, 204, 21, 0.8)'
                        ],
                        borderColor: [
                            'rgb(102, 126, 234)',
                            'rgb(245, 87, 108)',
                            'rgb(74, 172, 254)',
                            'rgb(250, 112, 154)',
                            'rgb(250, 204, 21)'
                        ],
                        borderWidth: 2,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return {
                                                text: `${label}: ${value.toLocaleString()} (${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor[i],
                                                lineWidth: dataset.borderWidth,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            formatter: (value, ctx) => {
                                const percentage = ((value / total) * 100).toFixed(1);
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
        }
    }
    @endif
});
</script>
@endsection

