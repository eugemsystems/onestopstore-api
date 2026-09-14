@extends('admin.layout')

@section('title', 'Cart Reminder Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">⚙️ Cart Reminder Settings</h1>
        <a href="{{ route('admin.cart-reminders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Settings Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cart Reminder Timing Configuration</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.cart-reminders.settings.update') }}" method="POST">
                        @csrf

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> These settings control when abandoned cart reminder emails are sent. Changes take effect on the next scheduled run.
                        </div>

                        <div class="mb-4">
                            <label for="first_hours" class="form-label">
                                <i class="bi bi-1-circle me-2 text-info"></i>
                                <strong>First Reminder After (hours)</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('first_hours') is-invalid @enderror"
                                   id="first_hours"
                                   name="first_hours"
                                   value="{{ old('first_hours', $settings['first_hours']) }}"
                                   min="1"
                                   max="168"
                                   required>
                            <div class="form-text">
                                Send the first abandoned-cart reminder after this many hours since the cart was started.
                                <br><strong>Current setting:</strong> {{ $settings['first_hours'] }} hours
                            </div>
                            @error('first_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="spacing_hours" class="form-label">
                                <i class="bi bi-arrow-repeat me-2 text-warning"></i>
                                <strong>Spacing Between Reminders (hours)</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('spacing_hours') is-invalid @enderror"
                                   id="spacing_hours"
                                   name="spacing_hours"
                                   value="{{ old('spacing_hours', $settings['spacing_hours']) }}"
                                   min="1"
                                   max="168"
                                   required>
                            <div class="form-text">
                                Wait this many hours between each follow-up reminder.
                                <br><strong>Current setting:</strong> {{ $settings['spacing_hours'] }} hours
                            </div>
                            @error('spacing_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="max_count" class="form-label">
                                <i class="bi bi-hash me-2 text-danger"></i>
                                <strong>Maximum Reminders Per Cart</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('max_count') is-invalid @enderror"
                                   id="max_count"
                                   name="max_count"
                                   value="{{ old('max_count', $settings['max_count']) }}"
                                   min="1"
                                   max="10"
                                   required>
                            <div class="form-text">
                                Stop sending reminders after this many have been sent for a single abandoned cart.
                                <br><strong>Current setting:</strong> {{ $settings['max_count'] }} reminders
                            </div>
                            @error('max_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Timeline Visualization -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">📅 Timeline Visualization</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">This is how your current settings work:</p>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success">
                                <i class="bi bi-cart-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Cart Started</strong>
                                <p class="text-muted mb-0">Customer adds an item to their cart and does not check out</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-info">
                                <i class="bi bi-1-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>After {{ $settings['first_hours'] }} hours</strong>
                                <p class="text-muted mb-0">First reminder email sent</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Every {{ $settings['spacing_hours'] }} hours after that</strong>
                                <p class="text-muted mb-0">Follow-up reminder emails sent</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger">
                                <i class="bi bi-hash"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Up to {{ $settings['max_count'] }} reminders</strong>
                                <p class="text-muted mb-0">No further reminders sent once the maximum count is reached</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -40px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
.timeline-connector {
    position: absolute;
    left: -20px;
    width: 2px;
    height: 30px;
    background: #dee2e6;
}
.timeline-content {
    padding-left: 20px;
}
</style>
@endsection
