@extends('admin.layout')

@section('title', 'Late Order Apology Settings')

@section('content')

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="admin-page-icon" style="background:linear-gradient(135deg,#b91c1c,#7f1d1d)">
            <i class="bi bi-gear-fill text-white"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Apology Email Settings</h2>
            <p class="text-muted mb-0 small">
                Configure cooldown and automatic scheduling for late-order apology emails
            </p>
        </div>
    </div>
    <a href="{{ route('admin.late-orders.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Late Orders
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('admin.late-orders.settings.update') }}">
    @csrf

    <div class="row g-4">

        {{-- Cooldown setting --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold" style="background:#f8f9fa">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Email Cooldown
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Minimum number of days between apology emails for the same order.
                        Once an apology is sent, the system will not allow another one until this window expires.
                    </p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Cooldown Period</label>
                        <div class="input-group" style="max-width:200px">
                            <input type="number"
                                   name="cooldown_days"
                                   class="form-control @error('cooldown_days') is-invalid @enderror"
                                   value="{{ old('cooldown_days', $settings->cooldown_days) }}"
                                   min="1"
                                   max="365"
                                   required>
                            <span class="input-group-text">days</span>
                            @error('cooldown_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Default: 4 days</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto-send schedule --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="background:#f8f9fa">
                    <span><i class="bi bi-calendar-check me-2 text-secondary"></i>Automatic Sending</span>
                    <span class="badge {{ $settings->auto_send_enabled ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                        {{ $settings->auto_send_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        When enabled, the system will automatically scan for eligible late orders every day
                        at the configured time and send apology emails without manual intervention.
                        When disabled, emails must be sent manually from the Late Orders page.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Auto-Send</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="auto_send_enabled"
                                       id="auto_send_on" value="1"
                                       {{ old('auto_send_enabled', $settings->auto_send_enabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-success fw-semibold" for="auto_send_on">
                                    <i class="bi bi-play-circle me-1"></i>Enabled
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="auto_send_enabled"
                                       id="auto_send_off" value="0"
                                       {{ old('auto_send_enabled', $settings->auto_send_enabled ? '1' : '0') === '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-secondary fw-semibold" for="auto_send_off">
                                    <i class="bi bi-pause-circle me-1"></i>Disabled
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Daily Run Time</label>
                        <input type="time"
                               name="auto_send_time"
                               class="form-control @error('auto_send_time') is-invalid @enderror"
                               style="max-width:160px"
                               value="{{ old('auto_send_time', $settings->auto_send_time) }}"
                               required>
                        @error('auto_send_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Server time (UTC). Applied on next scheduler tick.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info box --}}
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body py-3 d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-info fs-5 mt-1 flex-shrink-0"></i>
                    <div class="small text-muted">
                        <strong class="text-dark">How it works</strong><br>
                        The scheduler runs the <code>orders:send-late-apologies</code> command daily at the configured time.
                        It scans all paid, non-terminal orders that have at least one item past its ETA, filters out
                        items that are cancelled/delivered/collected, and sends one apology email per eligible order —
                        provided the cooldown window has passed since the last email.
                        You can also trigger this manually from the server at any time:<br>
                        <code>php artisan orders:send-late-apologies</code>
                        &nbsp;·&nbsp;
                        <code>php artisan orders:send-late-apologies --dry-run</code>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-danger px-4 fw-semibold">
            <i class="bi bi-floppy-fill me-2"></i>Save Settings
        </button>
    </div>

</form>

@endsection
