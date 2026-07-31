@extends('admin.layout')

@section('title', 'Order Reminder Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">⚙️ Order Reminder Settings</h1>
        <a href="{{ route('admin.order-reminders.index') }}" class="btn btn-secondary">
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
                    <h5 class="mb-0">Reminder Timing Configuration</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.order-reminders.settings.update') }}" method="POST">
                        @csrf

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> These settings control when reminder emails are sent and when orders are automatically cancelled. Changes take effect on the next scheduled run.
                        </div>

                        <div class="mb-4">
                            <label for="first_reminder_hours" class="form-label">
                                <i class="bi bi-1-circle me-2 text-info"></i>
                                <strong>First Reminder After (hours)</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('first_reminder_hours') is-invalid @enderror"
                                   id="first_reminder_hours"
                                   name="first_reminder_hours"
                                   value="{{ old('first_reminder_hours', $settings['first_reminder_hours']) }}"
                                   min="1"
                                   max="168"
                                   required>
                            <div class="form-text">
                                Send the first reminder email after this many hours from order creation.
                                <br><strong>Current setting:</strong> {{ $settings['first_reminder_hours'] }} hours
                            </div>
                            @error('first_reminder_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="second_reminder_hours" class="form-label">
                                <i class="bi bi-2-circle me-2 text-warning"></i>
                                <strong>Second Reminder After (hours)</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('second_reminder_hours') is-invalid @enderror"
                                   id="second_reminder_hours"
                                   name="second_reminder_hours"
                                   value="{{ old('second_reminder_hours', $settings['second_reminder_hours']) }}"
                                   min="1"
                                   max="168"
                                   required>
                            <div class="form-text">
                                Send the final reminder email after this many hours from order creation.
                                <br><strong>Current setting:</strong> {{ $settings['second_reminder_hours'] }} hours
                                <br><span class="text-warning">⚠️ Must be greater than first reminder hours</span>
                            </div>
                            @error('second_reminder_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="auto_cancel_hours" class="form-label">
                                <i class="bi bi-x-circle me-2 text-danger"></i>
                                <strong>Auto-Cancel After (hours)</strong>
                            </label>
                            <input type="number"
                                   class="form-control form-control-lg @error('auto_cancel_hours') is-invalid @enderror"
                                   id="auto_cancel_hours"
                                   name="auto_cancel_hours"
                                   value="{{ old('auto_cancel_hours', $settings['auto_cancel_hours']) }}"
                                   min="1"
                                   max="168"
                                   required>
                            <div class="form-text">
                                Automatically cancel pending orders after this many hours from order creation.
                                <br><strong>Current setting:</strong> {{ $settings['auto_cancel_hours'] }} hours ({{ round($settings['auto_cancel_hours']/24, 1) }} days)
                                <br><span class="text-danger">⚠️ Must be greater than second reminder hours</span>
                            </div>
                            @error('auto_cancel_hours')
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
                                <strong>Order Created</strong>
                                <p class="text-muted mb-0">Customer places an order (payment pending)</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-info">
                                <i class="bi bi-1-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>After {{ $settings['first_reminder_hours'] }} hours</strong>
                                <p class="text-muted mb-0">First reminder email sent</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="bi bi-2-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>After {{ $settings['second_reminder_hours'] }} hours</strong>
                                <p class="text-muted mb-0">Final reminder email sent</p>
                            </div>
                        </div>

                        <div class="timeline-connector"></div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>After {{ $settings['auto_cancel_hours'] }} hours ({{ round($settings['auto_cancel_hours']/24, 1) }} days)</strong>
                                <p class="text-muted mb-0">Order automatically cancelled + notification email sent</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Sidebar -->
        <div class="col-lg-4">
            <!-- Current Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📊 Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Reminders Sent</span>
                            <strong>{{ $stats['total_sent'] }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Failed Emails</span>
                            <strong>{{ $stats['total_failed'] }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-danger" style="width: {{ $stats['total_reminders'] > 0 ? ($stats['total_failed'] / $stats['total_reminders']) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Success Rate</span>
                            <strong>{{ $stats['success_rate'] }}%</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: {{ $stats['success_rate'] }}%"></div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">By Reminder Type</h6>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-1-circle text-info me-1"></i> First Reminders</span>
                            <strong>{{ $stats['by_type']['first'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-2-circle text-warning me-1"></i> Second Reminders</span>
                            <strong>{{ $stats['by_type']['second'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-x-circle text-danger me-1"></i> Cancellations</span>
                            <strong>{{ $stats['by_type']['cancellation'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>

{{--            <!-- Cron Job Info -->--}}
{{--            <div class="card">--}}
{{--                <div class="card-header">--}}
{{--                    <h5 class="mb-0">🔄 Automation Status</h5>--}}
{{--                </div>--}}
{{--                <div class="card-body">--}}
{{--                    <p class="text-muted mb-3">The system automatically processes pending orders every hour using Laravel's scheduler.</p>--}}

{{--                    <div class="alert alert-info mb-0">--}}
{{--                        <h6><i class="bi bi-info-circle me-2"></i>Setup Required</h6>--}}
{{--                        <p class="mb-2">Add this cron entry to your server:</p>--}}
{{--                        <code class="d-block p-2 bg-dark text-white rounded">--}}
{{--                            * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1--}}
{{--                        </code>--}}
{{--                        <p class="mt-2 mb-0"><small>Replace <code>/path/to/project</code> with your actual project path.</small></p>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
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

