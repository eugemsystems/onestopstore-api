@extends('admin.layout')
@section('title', 'Auction Settings - Admin Panel')
@section('content')

<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">
            <i class="bi bi-gear-fill"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Auction Settings</h2>
            <p class="text-muted mb-0 small">Configure global rules for all auctions</p>
        </div>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Auctions
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.auctions.settings.save') }}" method="POST">
@csrf

<div class="row g-4">
    <div class="col-lg-7">

        {{-- Email Verification --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-envelope-check me-2"></i>Email Verification</div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="require_email_verification" name="require_email_verification" value="1"
                           {{ $settings->require_email_verification ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="require_email_verification">
                        Require email verification before bidding
                    </label>
                </div>
                <div class="form-text">
                    When enabled, users must verify their email address before they can place any bid.
                    Unverified users will see a clear prompt to verify their email on the auction detail page.
                </div>
            </div>
        </div>

        {{-- Refundable Bid Deposit --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-cash-coin me-2"></i>Refundable Bid Deposit</div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="bid_fee_enabled" name="bid_fee_enabled" value="1"
                           {{ $settings->bid_fee_enabled ? 'checked' : '' }}
                           onchange="toggleFeeAmount(this)">
                    <label class="form-check-label fw-semibold" for="bid_fee_enabled">
                        Enable refundable bid deposit
                    </label>
                </div>
                <div class="form-text mb-3">
                    When enabled, users must pay a deposit before placing their first bid on any auction.
                    If the user <strong>wins</strong>, the deposit is <strong>deducted from the total they owe</strong>.
                    If they <strong>don't win</strong>, the deposit is <strong>automatically refunded</strong> to their wallet.
                </div>

                <div id="feeAmountWrap" style="display: {{ $settings->bid_fee_enabled ? 'block' : 'none' }}">
                    <label class="form-label fw-semibold">Deposit Amount (USD) <span class="text-danger">*</span></label>
                    <div class="input-group" style="max-width: 240px">
                        <span class="input-group-text">$</span>
                        <input type="number" name="bid_fee_amount" class="form-control @error('bid_fee_amount') is-invalid @enderror"
                               value="{{ old('bid_fee_amount', $settings->bid_fee_amount) }}"
                               step="0.01" min="0" placeholder="e.g. 10.00">
                        @error('bid_fee_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text mt-1">
                        This amount is charged once per user per auction (not per bid).
                        It will be deducted from the winner's final payment.
                    </div>
                </div>
            </div>
        </div>

        {{-- Delivery Options --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-truck me-2"></i>Home Delivery</div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="delivery_enabled" name="delivery_enabled" value="1"
                           {{ $settings->delivery_enabled ? 'checked' : '' }}
                           onchange="toggleDelivery(this)">
                    <label class="form-check-label fw-semibold" for="delivery_enabled">
                        Offer home delivery option to auction winners
                    </label>
                </div>
                <div class="form-text mb-3">
                    When enabled, the winner can choose between free collection or paid home delivery
                    when completing their auction payment.
                </div>

                <div id="deliveryWrap" style="display: {{ $settings->delivery_enabled ? 'block' : 'none' }}">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Delivery Fee (USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="delivery_price"
                                       class="form-control @error('delivery_price') is-invalid @enderror"
                                       value="{{ old('delivery_price', $settings->delivery_price) }}"
                                       step="0.01" min="0" placeholder="e.g. 25.00">
                                @error('delivery_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Coverage Radius (km) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="delivery_radius_km"
                                       class="form-control @error('delivery_radius_km') is-invalid @enderror"
                                       value="{{ old('delivery_radius_km', $settings->delivery_radius_km) }}"
                                       step="1" min="1" placeholder="e.g. 15">
                                <span class="input-group-text">km</span>
                                @error('delivery_radius_km')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-text mt-2">
                        Delivery is available within the radius you set. This message is shown to the winner on the payment page.
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Deadline & Reminders --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-clock-history me-2"></i>Payment Deadline &amp; Reminders</div>
            <div class="card-body">
                <div class="form-text mb-3">
                    Set how many hours after winning an auction the winner has to pay, and when reminder emails should be sent.
                    These settings are used by the scheduled cron jobs.
                </div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Hours to Pay <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="hours_to_pay" class="form-control @error('hours_to_pay') is-invalid @enderror"
                                   value="{{ old('hours_to_pay', $settings->hours_to_pay ?? 48) }}"
                                   min="1" max="720" step="1" placeholder="48">
                            <span class="input-group-text">hrs</span>
                            @error('hours_to_pay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Hours winner has to pay after winning</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Reminder 1 (after win)</label>
                        <div class="input-group">
                            <input type="number" name="reminder_1_hours" class="form-control @error('reminder_1_hours') is-invalid @enderror"
                                   value="{{ old('reminder_1_hours', $settings->reminder_1_hours ?? 12) }}"
                                   min="1" step="1" placeholder="12">
                            <span class="input-group-text">hrs</span>
                            @error('reminder_1_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">First reminder email sent N hours after win</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Reminder 2 (after win)</label>
                        <div class="input-group">
                            <input type="number" name="reminder_2_hours" class="form-control @error('reminder_2_hours') is-invalid @enderror"
                                   value="{{ old('reminder_2_hours', $settings->reminder_2_hours ?? 24) }}"
                                   min="1" step="1" placeholder="24">
                            <span class="input-group-text">hrs</span>
                            @error('reminder_2_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Second reminder email sent N hours after win</div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Settings
        </button>
    </div>

    {{-- Info sidebar --}}
    <div class="col-lg-5">
        <div class="card border-0" style="background: linear-gradient(135deg,#f8f4ff,#f0ebff)">
            <div class="card-body">
                <h6 class="fw-bold text-purple mb-3"><i class="bi bi-info-circle me-1"></i>How it works</h6>
                <ol class="mb-0 small" style="line-height:2">
                    <li>User visits an active auction and clicks <strong>Place Bid</strong></li>
                    <li>System checks <strong>email verification</strong> (if enabled)</li>
                    <li>System checks <strong>deposit payment</strong> (if enabled)</li>
                    <li>If deposit is required, user is shown a <em>Pay Deposit</em> button with payment gateway options</li>
                    <li>Once deposit is confirmed, user can bid normally</li>
                    <li>If user <strong>wins</strong>, deposit is deducted from the final payment amount</li>
                    <li>If user <strong>loses</strong>, deposit is <em>automatically refunded</em> to their wallet</li>
                </ol>
            </div>
        </div>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
function toggleFeeAmount(checkbox) {
    const wrap   = document.getElementById('feeAmountWrap');
    const hidden = document.getElementById('bid_fee_amount_hidden');
    if (checkbox.checked) {
        wrap.style.display = 'block';
        hidden.disabled = true;
    } else {
        wrap.style.display = 'none';
        hidden.disabled = false;
    }
}
function toggleDelivery(checkbox) {
    const wrap   = document.getElementById('deliveryWrap');
    const priceHidden  = document.getElementById('delivery_price_hidden');
    const radiusHidden = document.getElementById('delivery_radius_hidden');
    if (checkbox.checked) {
        wrap.style.display = 'block';
        priceHidden.disabled  = true;
        radiusHidden.disabled = true;
    } else {
        wrap.style.display = 'none';
        priceHidden.disabled  = false;
        radiusHidden.disabled = false;
    }
}
</script>
@endpush
