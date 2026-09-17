@extends('admin.layout')
@section('title', 'Daily Regional Feeds')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up-fill me-2" style="color:#2563eb"></i>Daily Regional Feeds</h4>
        <p class="text-muted mb-0 small">Scheduled Google Merchant XML feeds, regenerated daily and served as static files at a permanent URL</p>
    </div>
    @can('daily-feeds.run-now')
    <button type="button" class="btn btn-primary" id="runNowBtn">
        <i class="bi bi-play-fill me-1"></i>Run Now
    </button>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div id="runNowStatus" class="alert alert-info d-none">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="spinner-border spinner-border-sm" role="status" id="runNowSpinner"></div>
        <strong id="runNowStatusTitle">Generating feeds…</strong>
    </div>
    <ul class="mb-0 small" id="runNowStatusList"></ul>
</div>

@php
    $files = [
        'global_usd' => ['label' => 'Global (USD)', 'path' => 'dailyfeeds/global/usd/global-usd.xml', 'currency' => 'USD'],
        'global_zmw' => ['label' => 'Global (ZMW)', 'path' => 'dailyfeeds/global/zmw/global-zmw.xml', 'currency' => 'ZMW'],
        'zimbabwe_usd' => ['label' => 'Zimbabwe (USD)', 'path' => 'dailyfeeds/zimbabwe/zimbabwe.xml', 'currency' => 'USD'],
        'zambia_zmw' => ['label' => 'Zambia (ZMW)', 'path' => 'dailyfeeds/zambia/zambia.xml', 'currency' => 'ZMW'],
    ];
@endphp

{{-- Feed URLs + latest run status --}}
<div class="row g-3 mb-4">
    @foreach($files as $key => $meta)
        @php $run = $latestRuns[$key] ?? null; @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ $meta['label'] }}</h6>
                        @if($run)
                            @if($run->status === 'success')
                                <span class="badge bg-success">Success</span>
                            @elseif($run->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @elseif($run->status === 'skipped')
                                <span class="badge bg-secondary">Disabled</span>
                            @else
                                <span class="badge bg-warning text-dark">Running</span>
                            @endif
                        @else
                            <span class="badge bg-light text-dark border">Never run</span>
                        @endif
                    </div>
                    <a href="{{ url($meta['path']) }}" target="_blank" rel="noopener" class="d-block text-truncate small mb-2">
                        <i class="bi bi-link-45deg"></i> {{ url($meta['path']) }}
                    </a>
                    @if($run)
                        <div class="small text-muted">
                            <div>Records: <strong>{{ $run->records_written ?? '—' }}</strong></div>
                            <div>Last run: {{ $run->finished_at ?? $run->started_at }}</div>
                            <div>Duration: {{ $run->duration_seconds !== null ? $run->duration_seconds.'s' : '—' }}</div>
                            <div>File verified: {{ $run->file_verified ? 'Yes' : 'No' }}</div>
                            @if($run->error_message)
                                <div class="text-danger">{{ $run->error_message }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Settings --}}
@can('daily-feeds.update-settings')
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-gear-fill me-1"></i>Schedule & Filters</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.daily-feeds.update-settings') }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Scheduler run time (24h, server time)</label>
                    <input type="time" name="schedule_time" class="form-control" value="{{ $settings['schedule_time'] ?? '04:00' }}" required>
                    <div class="form-text">Next scheduler tick picks this up automatically — no deploy needed.</div>
                </div>
            </div>

            <div class="row g-3">
                @foreach($files as $key => $meta)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="{{ $key }}_enabled" name="{{ $key }}_enabled" value="1"
                                       {{ ($settings["{$key}_enabled"] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="{{ $key }}_enabled">{{ $meta['label'] }}</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Min price ({{ $meta['currency'] }})</label>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                           name="{{ $key }}_min_price" value="{{ $settings["{$key}_min_price"] ?? 0 }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Max products (0 = no limit)</label>
                                    <input type="number" step="1" min="0" class="form-control form-control-sm"
                                           name="{{ $key }}_limit" value="{{ $settings["{$key}_limit"] ?? 0 }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary mt-4">
                <i class="bi bi-save me-1"></i>Save Settings
            </button>
        </form>
    </div>
</div>
@endcan

{{-- Run history --}}
<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-clock-history me-1"></i>Run History</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Feed</th>
                        <th>Status</th>
                        <th>Records</th>
                        <th>Duration</th>
                        <th>Verified</th>
                        <th>Started</th>
                        <th>Finished</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $run)
                        <tr>
                            <td>{{ $fileLabels[$run->file_key] ?? $run->file_key }}</td>
                            <td>
                                @if($run->status === 'success')
                                    <span class="badge bg-success">Success</span>
                                @elseif($run->status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @elseif($run->status === 'skipped')
                                    <span class="badge bg-secondary">Disabled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Running</span>
                                @endif
                            </td>
                            <td>{{ $run->records_written ?? '—' }}</td>
                            <td>{{ $run->duration_seconds !== null ? $run->duration_seconds.'s' : '—' }}</td>
                            <td>{{ $run->file_verified ? 'Yes' : 'No' }}</td>
                            <td class="small text-muted">{{ $run->started_at }}</td>
                            <td class="small text-muted">{{ $run->finished_at ?? '—' }}</td>
                            <td class="small text-danger">{{ $run->error_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('daily-feeds.run-now')
<script>
document.getElementById('runNowBtn').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Starting…';

    const statusBox = document.getElementById('runNowStatus');
    const statusTitle = document.getElementById('runNowStatusTitle');
    const statusList = document.getElementById('runNowStatusList');
    statusBox.classList.remove('d-none');
    statusTitle.textContent = 'Generating feeds…';
    statusList.innerHTML = '';

    fetch('{{ route("admin.daily-feeds.run-now") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error('Failed to start run');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Running…';
        pollStatus(data.status_url, btn, statusTitle, statusList);
    })
    .catch(err => {
        statusTitle.textContent = 'Failed to start: ' + err.message;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Run Now';
    });
});

function pollStatus(url, btn, statusTitle, statusList) {
    const poll = () => {
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                statusList.innerHTML = data.runs.map(run => {
                    let badge = 'secondary', text = run.status;
                    if (run.status === 'success') badge = 'success';
                    else if (run.status === 'failed') badge = 'danger';
                    else if (run.status === 'running') badge = 'warning';
                    let extra = run.status === 'success'
                        ? `${run.records_written ?? 0} records in ${run.duration_seconds ?? '?'}s`
                        : (run.error_message || '');
                    return `<li><span class="badge bg-${badge}">${text}</span> ${run.label}: ${extra}</li>`;
                }).join('');

                const done = data.runs.filter(r => ['success', 'failed', 'skipped'].includes(r.status)).length;
                if (done >= data.expected_count) {
                    statusTitle.textContent = 'Done.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Run Now';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    setTimeout(poll, 2000);
                }
            })
            .catch(() => setTimeout(poll, 3000));
    };
    poll();
}
</script>
@endcan
@endsection
