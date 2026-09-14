@extends('admin.layout')

@section('title', 'Database Backups')

@push('styles')
<style>
    .db-icon-circle {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        flex-shrink: 0;
    }
    .db-icon-circle.status-healthy { background: rgba(25, 135, 84, 0.12); color: #198754; }
    .db-icon-circle.status-stale { background: rgba(220, 53, 69, 0.12); color: #dc3545; }
    .db-icon-circle.status-disabled { background: rgba(108, 117, 125, 0.12); color: #6c757d; }
    .db-icon-circle.status-unconfigured { background: rgba(255, 193, 7, 0.15); color: #b8860b; }

    .backup-file-card {
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .backup-file-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important;
    }
    .backup-file-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    .backup-file-icon.uploaded { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .backup-file-icon.remote { background: rgba(13, 202, 240, 0.12); color: #0dcaf0; }
    .backup-filename {
        font-size: 0.82rem;
        word-break: break-all;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">🗄️ Database Backups</h2>
            <p class="text-muted mb-0">Dump databases to this server, review them, then choose which to push off-server to Cloudflare R2</p>
        </div>
        <form action="{{ route('admin.backups.run') }}" method="POST" onsubmit="return confirm('Dump all enabled targets now? This runs in the background and can take a while for large databases.');">
            @csrf
            @can('backups.run')
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-database-fill-down"></i> Dump All Now
            </button>
            @endcan
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @unless($r2Configured)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Cloudflare R2 is not configured yet — set <code>R2_ACCESS_KEY_ID</code>, <code>R2_SECRET_ACCESS_KEY</code>,
            <code>R2_BUCKET</code> and <code>R2_ENDPOINT</code> in <code>.env</code> to enable pushing backups off-server.
            Local dumps still work without it.
        </div>
    @endunless

    @if($listError)
        <div class="alert alert-danger"><i class="bi bi-exclamation-octagon"></i> {{ $listError }}</div>
    @endif

    @unless($encrypted)
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Backups are currently <strong>not encrypted</strong>. Since these databases contain customer PII, set
            <code>BACKUP_ENCRYPTION_KEY</code> in <code>.env</code> (generate one with <code>openssl rand -base64 32</code>)
            to encrypt dumps at creation time. Save that key somewhere safe outside the server too —
            it's required to restore a backup, and losing it makes existing encrypted backups unrecoverable.
        </div>
    @endunless

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Off-Server Storage Used (R2 free tier)</p>
                    <h3 class="mb-2">{{ $totalHuman }} <small class="text-muted fs-6">/ 10 GB</small></h3>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $usagePercent > 90 ? 'bg-danger' : ($usagePercent > 70 ? 'bg-warning' : 'bg-success') }}"
                             role="progressbar" style="width: {{ $usagePercent }}%"></div>
                    </div>
                    <small class="text-muted">{{ $usagePercent }}% of free tier, across all targets below</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Targets</p>
                    <h3 class="mb-0">
                        {{ collect($groups)->where('enabled', true)->count() }} enabled
                        <small class="text-muted fs-6">/ {{ count($groups) }} configured</small>
                    </h3>
                    <small class="text-muted">Local dumps scheduled daily at 2:00 AM — upload is manual</small>
                </div>
            </div>
        </div>
    </div>

    @foreach($groups as $key => $group)
        @php
            $statusClass = !$group['enabled'] ? 'status-disabled' : (!$group['configured'] ? 'status-unconfigured' : ($group['is_stale'] ? 'status-stale' : 'status-healthy'));
            $dbIcons = ['app' => 'bi-hdd-stack-fill', 'media' => 'bi-images', 'crm' => 'bi-headset'];
            $dbIcon = $dbIcons[$key] ?? 'bi-database-fill';
        @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="db-icon-circle {{ $statusClass }}">
                            <i class="bi {{ $dbIcon }}"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                {{ $group['label'] }}
                                @if(!$group['enabled'])
                                    <span class="badge bg-secondary-subtle text-secondary border ms-1">Disabled</span>
                                @elseif(!$group['configured'])
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">Not configured</span>
                                @elseif($group['is_stale'])
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">No recent off-server backup</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Healthy</span>
                                @endif
                            </h5>
                            <small class="text-muted">
                                <i class="bi bi-hdd"></i> {{ $group['local']->count() }} local dump(s)
                                &nbsp;·&nbsp;
                                <i class="bi bi-cloud"></i> {{ $group['remote']->count() }} off-server ({{ $group['remote_total_human'] }})
                            </small>
                        </div>
                    </div>
                    @if($group['enabled'] && $group['configured'])
                        @can('backups.run')
                        <form action="{{ route('admin.backups.run.target', $key) }}" method="POST" onsubmit="return confirm('Dump {{ $group['label'] }} to local disk now?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-database-fill-down"></i> Dump Now
                            </button>
                        </form>
                        @endcan
                    @endif
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                @if(!$group['enabled'])
                    <p class="text-muted mb-0">
                        This target is disabled. Set <code>BACKUP_{{ strtoupper($key) }}_ENABLED=true</code> in
                        <code>.env</code> (after filling in its connection details) to turn it on.
                    </p>
                @elseif(!$group['configured'])
                    <p class="text-muted mb-0">
                        Missing connection details. Set <code>{{ strtoupper($key) }}_DB_HOST</code>,
                        <code>{{ strtoupper($key) }}_DB_DATABASE</code>, <code>{{ strtoupper($key) }}_DB_USERNAME</code>
                        and <code>{{ strtoupper($key) }}_DB_PASSWORD</code> in <code>.env</code>.
                    </p>
                @else
                    <h6 class="mb-3 text-muted"><i class="bi bi-hdd"></i> LOCAL DUMPS <small class="fw-normal">(on this server)</small></h6>
                    @if($group['local']->isEmpty())
                        <div class="text-center text-muted py-4 mb-4 border rounded bg-light-subtle">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            No local dumps yet — click "Dump Now" above.
                        </div>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 mb-4">
                            @foreach($group['local'] as $backup)
                                <div class="col">
                                    <div class="card border shadow-sm h-100 backup-file-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-2 mb-2">
                                                <div class="backup-file-icon {{ $backup['uploaded'] ? 'uploaded' : '' }}">
                                                    <i class="bi {{ $backup['uploaded'] ? 'bi-cloud-check-fill' : 'bi-file-earmark-zip-fill' }}"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $backup['size_human'] }}</div>
                                                    <small class="text-muted">{{ $backup['last_modified']->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                            <div class="backup-filename text-muted mb-2" title="{{ $backup['filename'] }}">{{ $backup['filename'] }}</div>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                @if(str_ends_with($backup['filename'], '.enc'))
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-lock-fill"></i> Encrypted</span>
                                                @endif
                                                @if($backup['uploaded'])
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-cloud-check-fill"></i> On R2</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border">Not uploaded</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @can('backups.run')
                                                @if(!$backup['uploaded'] && $r2Configured)
                                                <form action="{{ route('admin.backups.upload', [$key, $backup['filename']]) }}" method="POST" onsubmit="return confirm('Upload {{ $backup['filename'] }} ({{ $backup['size_human'] }}) to R2?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Upload to R2">
                                                        <i class="bi bi-cloud-arrow-up"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @endcan
                                                @can('backups.download')
                                                <a href="{{ route('admin.backups.download-local', [$key, $backup['filename']]) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                @endcan
                                                @can('backups.delete')
                                                <form action="{{ route('admin.backups.destroy-local', [$key, $backup['filename']]) }}" method="POST" data-swal-confirm="Delete this local dump permanently?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <h6 class="mb-3 text-muted"><i class="bi bi-cloud"></i> OFF-SERVER BACKUPS <small class="fw-normal">(Cloudflare R2)</small></h6>
                    @if($group['remote']->isEmpty())
                        <div class="text-center text-muted py-4 border rounded bg-light-subtle">
                            <i class="bi bi-cloud-slash fs-2 d-block mb-1"></i>
                            {{ $r2Configured ? 'None uploaded yet.' : 'R2 not configured.' }}
                        </div>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
                            @foreach($group['remote'] as $backup)
                                <div class="col">
                                    <div class="card border shadow-sm h-100 backup-file-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-2 mb-2">
                                                <div class="backup-file-icon remote">
                                                    <i class="bi bi-cloud-fill"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $backup['size_human'] }}</div>
                                                    <small class="text-muted">{{ $backup['last_modified']->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                            <div class="backup-filename text-muted mb-2" title="{{ $backup['filename'] }}">{{ $backup['filename'] }}</div>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                @if(str_ends_with($backup['filename'], '.enc'))
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-lock-fill"></i> Encrypted</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @can('backups.download')
                                                <a href="{{ route('admin.backups.download', [$key, $backup['filename']]) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                @endcan
                                                @can('backups.delete')
                                                <form action="{{ route('admin.backups.destroy', [$key, $backup['filename']]) }}" method="POST" data-swal-confirm="Delete this off-server backup permanently?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
