@extends('admin.layout')

@section('title', 'App Version Settings')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-phone-fill"></i> App Version Settings</h2>
            <p class="text-muted mb-0">Manage Flutter app update prompts for Android and iOS</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <ul class="nav nav-tabs mb-4" id="platformTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="android-tab" data-bs-toggle="tab" data-bs-target="#android" type="button" role="tab">
                <i class="bi bi-android2"></i> Android
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ios-tab" data-bs-toggle="tab" data-bs-target="#ios" type="button" role="tab">
                <i class="bi bi-apple"></i> iOS
            </button>
        </li>
    </ul>

    <div class="tab-content" id="platformTabsContent">
        @foreach(['android', 'ios'] as $platform)
        @php $v = $versions[$platform] ?? null; @endphp
        <div class="tab-pane fade {{ $platform === 'android' ? 'show active' : '' }}" id="{{ $platform }}" role="tabpanel">
            <form action="{{ route('admin.app-version.update', $platform) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0">
                                    <i class="bi bi-{{ $platform === 'android' ? 'android2' : 'apple' }}"></i>
                                    {{ strtoupper($platform) }} Version Details
                                </h5>
                            </div>
                            <div class="card-body">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">
                                        Latest Version <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-8">
                                        <input type="text" name="latest_version" class="form-control @error('latest_version') is-invalid @enderror"
                                               value="{{ old('latest_version', $v?->latest_version) }}"
                                               placeholder="e.g. 2.7.1" required>
                                        <small class="text-muted">Semver format: MAJOR.MINOR.PATCH</small>
                                        @error('latest_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">
                                        Latest Build <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-8">
                                        <input type="number" name="latest_build" class="form-control @error('latest_build') is-invalid @enderror"
                                               value="{{ old('latest_build', $v?->latest_build) }}"
                                               min="1" required>
                                        <small class="text-muted">Monotonically increasing integer (pubspec.yaml +N)</small>
                                        @error('latest_build')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">
                                        Minimum Version <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-8">
                                        <input type="text" name="minimum_version" class="form-control @error('minimum_version') is-invalid @enderror"
                                               value="{{ old('minimum_version', $v?->minimum_version) }}"
                                               placeholder="e.g. 2.6.0" required>
                                        <small class="text-muted">Users below this version are force-updated</small>
                                        @error('minimum_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">Force Update Override</label>
                                    <div class="col-md-8 d-flex align-items-center gap-3">
                                        <div class="form-check form-switch mb-0">
                                            <input type="hidden" name="force_update" value="0">
                                            <input class="form-check-input" type="checkbox" name="force_update" value="1" id="force_update_{{ $platform }}" role="switch"
                                                   {{ old('force_update', $v?->force_update) ? 'checked' : '' }}>
                                            <label class="form-check-label text-danger fw-semibold" for="force_update_{{ $platform }}">
                                                Block all users until updated
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">Release Notes</label>
                                    <div class="col-md-8">
                                        <textarea name="release_notes" class="form-control" rows="5"
                                                  placeholder="- New feature&#10;- Bug fixes">{{ old('release_notes', $v?->release_notes) }}</textarea>
                                        <small class="text-muted">Markdown supported. Shown in app update dialog.</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">Store URL</label>
                                    <div class="col-md-8">
                                        <input type="url" name="store_url" class="form-control @error('store_url') is-invalid @enderror"
                                               value="{{ old('store_url', $v?->store_url) }}"
                                               placeholder="{{ $platform === 'android' ? 'https://play.google.com/store/apps/details?id=...' : 'https://apps.apple.com/app/id...' }}">
                                        @error('store_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label fw-semibold">Released At</label>
                                    <div class="col-md-8">
                                        <input type="date" name="released_at" class="form-control"
                                               value="{{ old('released_at', $v?->released_at?->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save {{ strtoupper($platform) }}
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Current Record</h6>
                            </div>
                            <div class="card-body">
                                @if($v)
                                    <div class="mb-2"><small class="text-muted">Latest</small><div class="fw-bold">{{ $v->latest_version }} (build {{ $v->latest_build }})</div></div>
                                    <div class="mb-2"><small class="text-muted">Minimum</small><div class="fw-bold">{{ $v->minimum_version }}</div></div>
                                    <div class="mb-2"><small class="text-muted">Force Update</small>
                                        <div>
                                            @if($v->force_update)
                                                <span class="badge bg-danger">ON</span>
                                            @else
                                                <span class="badge bg-success">OFF</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-2"><small class="text-muted">Released At</small><div class="fw-bold">{{ $v->released_at?->format('d M Y') ?? '—' }}</div></div>
                                    <div class="mb-2"><small class="text-muted">Last Updated</small><div class="fw-bold">{{ $v->updated_at->format('d M Y H:i') }}</div></div>
                                @else
                                    <p class="text-muted">No record yet. Save to create.</p>
                                @endif
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-header bg-white border-bottom">
                                <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Update Logic</h6>
                            </div>
                            <div class="card-body small text-muted">
                                <p><strong>current &lt; minimum</strong> → force install (blocks app)</p>
                                <p><strong>current &lt; latest</strong> → optional prompt</p>
                                <p class="mb-0"><strong>Force Update ON</strong> → blocks all users regardless of version</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @endforeach
    </div>

</div>
@endsection
