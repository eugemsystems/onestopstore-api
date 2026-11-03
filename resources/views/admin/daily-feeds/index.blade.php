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
                    @if(in_array($key, $shardedKeys, true))
                        @php
                            $shardCount = (int) ($run->shard_count ?? ($settings["{$key}_shard_count"] ?? 20));
                        @endphp
                        <details class="mb-2">
                            <summary class="small">
                                <i class="bi bi-link-45deg"></i> {{ $shardCount }} files (registered once in Merchant Center — URLs never change)
                            </summary>
                            <div style="max-height:180px; overflow-y:auto;" class="mt-1">
                                @for($i = 1; $i <= $shardCount; $i++)
                                    <a href="{{ url(str_replace('{n}', $i, $pathTemplates[$key])) }}" target="_blank" rel="noopener" class="d-block text-truncate small">
                                        {{ url(str_replace('{n}', $i, $pathTemplates[$key])) }}
                                    </a>
                                @endfor
                            </div>
                        </details>
                    @else
                        <a href="{{ url($meta['path']) }}" target="_blank" rel="noopener" class="d-block text-truncate small mb-2">
                            <i class="bi bi-link-45deg"></i> {{ url($meta['path']) }}
                        </a>
                    @endif
                    @if($run)
                        <div class="small text-muted">
                            <div>Records: <strong>{{ $run->records_written ?? '—' }}</strong>{{ $run->shard_count ? ' across '.$run->shard_count.' files' : '' }}</div>
                            <div>Last run: {{ $run->finished_at ?? $run->started_at }}</div>
                            <div>Duration: {{ $run->duration_seconds !== null ? $run->duration_seconds.'s' : '—' }}</div>
                            <div>Files verified: {{ $run->file_verified ? 'Yes' : 'No' }}</div>
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

{{-- Custom Feed Links: admin-picked category slugs + SKUs. Static files, same as the
     4 fixed feeds above — regenerated only when "Run Now" is clicked (never on request) --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-link-45deg me-1"></i>Custom Feed Links</span>
        @can('daily-feeds.custom-feed-create')
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newCustomFeedModal">
            <i class="bi bi-plus-lg me-1"></i>New Custom Feed
        </button>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Categories</th>
                        <th>SKUs</th>
                        <th>Currency</th>
                        <th>Link</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="customFeedsTableBody">
                    @forelse($customFeeds as $feed)
                        @php $lastRun = $latestRuns[$feed->slug] ?? null; @endphp
                        <tr data-feed-id="{{ $feed->id }}">
                            <td>{{ $feed->name }}</td>
                            <td class="small text-muted">{{ count($feed->category_slugs ?? []) }} categor{{ count($feed->category_slugs ?? []) === 1 ? 'y' : 'ies' }}</td>
                            <td class="small text-muted">{{ count($feed->skus ?? []) }} SKU{{ count($feed->skus ?? []) === 1 ? '' : 's' }}</td>
                            <td>
                                {{ $feed->currency }}
                                @if($feed->min_price > 0)
                                    <div class="text-muted small">min {{ $feed->currency }} {{ number_format($feed->min_price, 2) }}</div>
                                @endif
                                @if($feed->shard_count > 1)
                                    <div class="text-muted small">{{ $feed->shard_count }} files</div>
                                @endif
                            </td>
                            <td>
                                @if($feed->shard_count > 1)
                                    <details>
                                        <summary class="small">{{ $feed->shard_count }} file links</summary>
                                        <div style="max-height:140px; overflow-y:auto;" class="mt-1">
                                            @for($i = 1; $i <= $feed->shard_count; $i++)
                                                @php $shardUrl = route('custom-feeds.show-shard', ['slug' => $feed->slug, 'shard' => $i]); @endphp
                                                <div class="d-flex align-items-center">
                                                    <a href="{{ $shardUrl }}" target="_blank" rel="noopener" class="d-inline-block text-truncate small" style="max-width:220px;">
                                                        {{ $shardUrl }}
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1" title="Copy link" onclick="copyCustomFeedLink('{{ $shardUrl }}', this)">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            @endfor
                                        </div>
                                    </details>
                                @else
                                    <a href="{{ route('custom-feeds.show', ['slug' => $feed->slug]) }}" target="_blank" rel="noopener" class="d-inline-block text-truncate small" style="max-width:220px;">
                                        {{ route('custom-feeds.show', ['slug' => $feed->slug]) }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1" title="Copy link" onclick="copyCustomFeedLink('{{ route('custom-feeds.show', ['slug' => $feed->slug]) }}', this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="small" data-feed-status>
                                @if(!$lastRun)
                                    <span class="text-muted">Never generated</span>
                                @elseif($lastRun->status === 'success')
                                    <span class="badge bg-success">Generated</span>
                                    <span class="text-muted">{{ $lastRun->records_written }} products, {{ $lastRun->finished_at }}</span>
                                @elseif($lastRun->status === 'running')
                                    <span class="badge bg-warning text-dark">Running…</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                    <span class="text-muted">{{ $lastRun->error_message }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @can('daily-feeds.custom-feed-run')
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="runNowCustomFeed({{ $feed->id }}, this)" title="Generate / regenerate this feed's file now">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                @endcan
                                @can('daily-feeds.custom-feed-delete')
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCustomFeed({{ $feed->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr id="customFeedsEmptyRow"><td colspan="7" class="text-center text-muted py-4">No custom feed links yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('daily-feeds.custom-feed-create')
<div class="modal fade" id="newCustomFeedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-link-45deg me-1"></i>New Custom Feed Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="customFeedAlert"></div>
                <div class="alert alert-info py-2 small">
                    Creating a link does not generate the file. After creating it, click
                    <i class="bi bi-play-fill"></i> <strong>Run Now</strong> on the row to generate it — and again any
                    time you need fresh data (new upload, price change, etc.).
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" id="customFeedName" class="form-control" placeholder="e.g. Black Friday Picks">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category Slugs</label>
                        <textarea id="customFeedCategorySlugs" class="form-control font-monospace" rows="8" placeholder="electronics&#10;home-appliances&#10;..."></textarea>
                        <small class="text-muted">One category slug per line. All products in these categories (and their subcategories) are included.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product SKUs</label>
                        <textarea id="customFeedSkus" class="form-control font-monospace" rows="8" placeholder="ABC123&#10;DEF456&#10;..."></textarea>
                        <small class="text-muted">One SKU per line. Added on top of the categories above — no need to also add their category.</small>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Currency</label>
                        <select id="customFeedCurrency" class="form-select">
                            @foreach($currencies as $code)
                                <option value="{{ $code }}" {{ $code === 'USD' ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Also filters out products restricted to another region: ZMW → Zambia-only + unrestricted, ZAR → South-Africa-only + unrestricted, other currencies → Zimbabwe-only + unrestricted.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Min Price</label>
                        <input type="number" step="0.01" min="0" id="customFeedMinPrice" class="form-control" placeholder="0.00">
                        <small class="text-muted">In the currency above. Products cheaper than this are left out. Leave blank/0 for no minimum.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Split into how many files</label>
                        <input type="number" step="1" min="1" max="200" id="customFeedShardCount" class="form-control" value="1">
                        <small class="text-muted">Only if the selection has too many products for one file. Keep this fixed once you've registered the URLs in Google Ads — changing it changes the URL list.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createCustomFeedBtn" onclick="createCustomFeed()">
                    <i class="bi bi-check-lg me-1"></i>Create Link
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

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
                                @if(in_array($key, $shardedKeys, true))
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Split into how many files</label>
                                        <input type="number" step="1" min="1" max="200" class="form-control form-control-sm"
                                               name="{{ $key }}_shard_count" value="{{ $settings["{$key}_shard_count"] ?? 20 }}">
                                        <div class="form-text">Keep this fixed once you've registered the URLs in Merchant Center — changing it changes the URL list.</div>
                                    </div>
                                @endif
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
                        <th>Files</th>
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
                            <td>{{ $run->shard_count ?? 1 }}</td>
                            <td>{{ $run->duration_seconds !== null ? $run->duration_seconds.'s' : '—' }}</td>
                            <td>{{ $run->file_verified ? 'Yes' : 'No' }}</td>
                            <td class="small text-muted">{{ $run->started_at }}</td>
                            <td class="small text-muted">{{ $run->finished_at ?? '—' }}</td>
                            <td class="small text-danger">{{ $run->error_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No runs yet.</td></tr>
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
                        ? `${run.records_written ?? 0} records${run.shard_count ? ' across ' + run.shard_count + ' files' : ''} in ${run.duration_seconds ?? '?'}s`
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

<script>
function copyCustomFeedLink(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const icon = btn.querySelector('i');
        const original = icon.className;
        icon.className = 'bi bi-check-lg text-success';
        setTimeout(() => { icon.className = original; }, 1500);
    });
}

async function createCustomFeed() {
    const alertBox = document.getElementById('customFeedAlert');
    const btn = document.getElementById('createCustomFeedBtn');
    alertBox.innerHTML = '';

    const name = document.getElementById('customFeedName').value.trim();
    const categorySlugs = document.getElementById('customFeedCategorySlugs').value.trim();
    const skus = document.getElementById('customFeedSkus').value.trim();
    const currency = document.getElementById('customFeedCurrency').value;
    const minPrice = document.getElementById('customFeedMinPrice').value;
    const shardCount = document.getElementById('customFeedShardCount').value;

    if (!name) {
        alertBox.innerHTML = '<div class="alert alert-warning py-2">Please enter a name.</div>';
        return;
    }
    if (!categorySlugs && !skus) {
        alertBox.innerHTML = '<div class="alert alert-warning py-2">Enter at least one category slug or SKU.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating…';

    try {
        const res = await fetch('{{ route("admin.daily-feeds.custom-feeds.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, category_slugs: categorySlugs, skus, currency, min_price: minPrice, shard_count: shardCount }),
        });
        const json = await res.json();

        if (!json.success) {
            alertBox.innerHTML = `<div class="alert alert-danger py-2">${json.message || 'Failed to create feed.'}</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Create Link';
            return;
        }

        if (json.unknown_categories && json.unknown_categories.length > 0) {
            alertBox.innerHTML = `<div class="alert alert-warning py-2">Created, but these category slugs don't exist and were ignored: ${json.unknown_categories.join(', ')}</div>`;
            setTimeout(() => window.location.reload(), 2500);
        } else {
            window.location.reload();
        }
    } catch (err) {
        alertBox.innerHTML = '<div class="alert alert-danger py-2">Request failed. Please try again.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Create Link';
    }
}

async function runNowCustomFeed(id, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const statusCell = document.querySelector(`tr[data-feed-id="${id}"] td[data-feed-status]`);

    try {
        const res = await fetch(`{{ url('admin/daily-feeds/custom-feeds') }}/${id}/run-now`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        const json = await res.json();

        if (!json.success) {
            if (statusCell) {
                statusCell.innerHTML = `<span class="badge bg-danger">Failed</span> <span class="text-muted">${json.message || 'Generation failed.'}</span>`;
            } else {
                alert(json.message || 'Generation failed.');
            }
            return;
        }

        if (statusCell) {
            statusCell.innerHTML = `<span class="badge bg-success">Generated</span> <span class="text-muted">${json.run.records_written ?? 0} products, ${json.run.finished_at}</span>`;
        }
    } catch (err) {
        if (statusCell) {
            statusCell.innerHTML = '<span class="badge bg-danger">Failed</span> <span class="text-muted">Request failed.</span>';
        } else {
            alert('Request failed. Please try again.');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

async function deleteCustomFeed(id) {
    if (!confirm('Delete this custom feed link? Anyone using the URL will get a 404.')) return;

    try {
        const res = await fetch(`{{ url('admin/daily-feeds/custom-feeds') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            document.querySelector(`tr[data-feed-id="${id}"]`)?.remove();
        } else {
            alert(json.message || 'Failed to delete feed.');
        }
    } catch (err) {
        alert('Request failed. Please try again.');
    }
}
</script>
@endsection
