@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Feedback Details</h1>
        <a href="{{ route('admin.marketing-feedback.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="row">
        <!-- Feedback Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Feedback Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="30%">Submitted At:</th>
                                <td>{{ $feedback->created_at->format('F d, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Order Number:</th>
                                <td>
                                    @if($feedback->order)
                                        <a href="{{ route('admin.orders.show', $feedback->order_number) }}">
                                            {{ $feedback->order_number }}
                                        </a>
                                    @else
                                        {{ $feedback->order_number }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Customer Name:</th>
                                <td>{{ $feedback->user_name ?? $feedback->user?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Customer Email:</th>
                                <td>{{ $feedback->user_email ?? $feedback->user?->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Customer Phone:</th>
                                <td>{{ $feedback->user_phone ?? $feedback->user?->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Country:</th>
                                <td>
                                    @if($feedback->country_name && $feedback->country_code)
                                        <span class="badge bg-info" style="font-size: 1rem;">
                                            <i class="bi bi-flag-fill"></i> {{ $feedback->country_code }}
                                        </span>
                                        <span class="ms-2">{{ $feedback->country_name }}</span>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ordering Process Rating:</th>
                                <td>
                                    @php
                                        $badgeClass = match($feedback->ordering_process_rating) {
                                            'excellent' => 'bg-success',
                                            'good' => 'bg-primary',
                                            'fair' => 'bg-warning',
                                            'poor' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} fs-6">
                                        {{ $feedback->rating_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>How They Heard About Us:</th>
                                <td>
                                    <span class="badge bg-info text-dark fs-6">
                                        {{ $feedback->source_label }}
                                    </span>
                                </td>
                            </tr>
                            @if($feedback->additional_comments)
                            <tr>
                                <th>Additional Comments:</th>
                                <td>
                                    <div class="border rounded p-3 bg-light">
                                        {{ $feedback->additional_comments }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Technical Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Technical Details</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>IP Address:</strong><br>
                        <code>{{ $feedback->ip_address ?? 'N/A' }}</code>
                    </p>
                    <p class="mb-2">
                        <strong>User Agent:</strong><br>
                        <small class="text-muted">{{ $feedback->user_agent ?? 'N/A' }}</small>
                    </p>
                    <p class="mb-2">
                        <strong>Device:</strong><br>
                        <i class="{{ $feedback->device_icon }} me-2 text-primary"></i>
                        <span class="fw-bold">{{ $feedback->device_type }}</span>
                    </p>
                    <p class="mb-0">
                        <strong>Feedback ID:</strong><br>
                        <code>{{ $feedback->id }}</code>
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.marketing-feedback.destroy', $feedback->id) }}"
                          method="POST"
                          data-swal-confirm="Are you sure you want to delete this feedback?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-2"></i>Delete Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

