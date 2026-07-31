@extends('admin.layout')

@section('title', 'Layby Settings')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.layby.index') }}" class="btn btn-link ps-0">
                        ← Back to Applications
                    </a>
                    <h2 class="mb-0">Layby Settings</h2>
                    <p class="text-muted">Configure deposit percentages and payment durations</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.layby.settings.update') }}" method="POST">
        @csrf

        <div class="row">
            <!-- Sale Products Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Sale Products (On Promotion)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> These settings apply to products with active sale prices.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deposit Percentage <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                       name="sale_products_deposit_percentage"
                                       class="form-control"
                                       value="{{ $settings['sale_products_deposit_percentage'] ?? 30 }}"
                                       min="10"
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentage of product price required as deposit</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Duration <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                       name="sale_products_duration_months"
                                       class="form-control"
                                       value="{{ $settings['sale_products_duration_months'] ?? 3 }}"
                                       min="1"
                                       max="24"
                                       required>
                                <span class="input-group-text">months</span>
                            </div>
                            <small class="text-muted">Fixed payment duration for sale products (only this option will be available)</small>
                        </div>

                        <div class="border-top pt-3">
                            <h6 class="fw-bold">Example Calculation:</h6>
                            <p class="mb-1">Product Price: $450</p>
                            <p class="mb-1">Deposit ({{ $settings['sale_products_deposit_percentage'] ?? 30 }}%): ${{ number_format(450 * (($settings['sale_products_deposit_percentage'] ?? 30) / 100), 2) }}</p>
                            <p class="mb-1">Remaining: ${{ number_format(450 - (450 * (($settings['sale_products_deposit_percentage'] ?? 30) / 100)), 2) }}</p>
                            <p class="mb-0">Monthly Payment: ${{ number_format((450 - (450 * (($settings['sale_products_deposit_percentage'] ?? 30) / 100))) / ($settings['sale_products_duration_months'] ?? 3), 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regular Products Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Regular Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> These settings apply to products without sale prices.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deposit Percentage <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                       name="regular_products_deposit_percentage"
                                       class="form-control"
                                       value="{{ $settings['regular_products_deposit_percentage'] ?? 30 }}"
                                       min="10"
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentage of product price required as deposit</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Payment Durations <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="regular_products_duration_months"
                                   class="form-control"
                                   value="{{ $settings['regular_products_duration_months'] ?? '6' }}"
                                   placeholder="e.g., 6,12,18"
                                   required>
                            <small class="text-muted">Enter months separated by commas (e.g., 6,12,18 for 6, 12, and 18 months options)</small>
                        </div>

                        <div class="border-top pt-3">
                            <h6 class="fw-bold">Example Calculation:</h6>
                            <p class="mb-1">Product Price: $600</p>
                            <p class="mb-1">Deposit ({{ $settings['regular_products_deposit_percentage'] ?? 30 }}%): ${{ number_format(600 * (($settings['regular_products_deposit_percentage'] ?? 30) / 100), 2) }}</p>
                            <p class="mb-1">Remaining: ${{ number_format(600 - (600 * (($settings['regular_products_deposit_percentage'] ?? 30) / 100)), 2) }}</p>
                            @php
                                $durations = explode(',', $settings['regular_products_duration_months'] ?? '6');
                            @endphp
                            @foreach($durations as $duration)
                                <p class="mb-0">{{ trim($duration) }} months: ${{ number_format((600 - (600 * (($settings['regular_products_deposit_percentage'] ?? 30) / 100))) / trim($duration), 2) }}/month</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- ID Upload Requirement -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Verification Requirements</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="require_id_upload"
                                   id="require_id_upload"
                                   value="1"
                                   {{ ($settings['require_id_upload'] ?? 1) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="require_id_upload">
                                <strong>Require ID Document Upload</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            When enabled, customers must upload a government-issued ID (passport, ID card, or driver's license) to apply for layby.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-check-circle me-2"></i>
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

