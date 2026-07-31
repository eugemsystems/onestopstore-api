@php
    $isApproved  = $status === 'approved';
    $isRejected  = $status === 'rejected';
    $stripColor  = $isApproved ? 'background:linear-gradient(135deg,#166534,#15803d)' : ($isRejected ? 'background:linear-gradient(135deg,#991b1b,#b91c1c)' : 'background:linear-gradient(135deg,#C0392B,#922b21)');
    $icon        = $isApproved ? '✅' : ($isRejected ? '❌' : ($wasReopened ? '🔄' : '📋'));
    $headingText = $isApproved ? 'Refund Approved' : ($isRejected ? 'Refund Request Rejected' : ($wasReopened ? 'Refund Request Reopened' : 'Refund Request Update'));
    $preheader   = $isApproved ? 'Great news — your refund has been approved.' : ($isRejected ? 'An update on your refund request.' : 'Your refund request status has been updated.');
@endphp

@include('emails.partials.layout', [
    'preheader'     => $preheader,
    'emailTitle'    => $headingText,
    'isInteractive' => true,
])

<div class="email-heading-strip" style="{{ $stripColor }}">
    <h1>{{ $icon }} {{ $headingText }}</h1>
    <p>Refund Request #{{ $refund->id }}</p>
</div>

<p>Dear <strong>{{ $customerName }}</strong>,</p>

@if($isApproved)
    <p>Great news! Your refund request has been <strong style="color:#166534">approved</strong> and will be processed to your payment account.</p>
@elseif($isRejected)
    <p>We regret to inform you that your refund request has been <strong style="color:#b91c1c">rejected</strong>.</p>
@elseif($wasReopened)
    <p>Your refund request has been <strong>reopened</strong> and is now under review again. Our team will assess it and get back to you shortly.</p>
@else
    <p>The status of your refund request has been updated.</p>
@endif

{{-- Refund Details --}}
<div class="info-card">
    @if($orderNumber)
    <div class="row">
        <span class="label">Order Number</span>
        <span class="value">#{{ $orderNumber }}</span>
    </div>
    @endif
    <div class="row">
        <span class="label">Refund Amount</span>
        <span class="value">{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</span>
    </div>
    <div class="row">
        <span class="label">Status</span>
        <span class="value">
            @if($isApproved)
                <span class="badge badge-success">Approved</span>
            @elseif($isRejected)
                <span class="badge badge-danger">Rejected</span>
            @elseif($wasReopened)
                <span class="badge badge-pending">Pending Review</span>
            @else
                <span class="badge badge-info">{{ ucfirst($status) }}</span>
            @endif
        </span>
    </div>
</div>

@if($isApproved)
    {{-- Payment Account Details — always show WHERE the refund goes (customer's registered account) --}}
    @if($paymentAccount)
        <div class="info-card" style="border-left-color:#166534">
            <div class="row"><span class="label" style="font-weight:700;color:#111827;flex:1">🏦 Refund Destination</span></div>
            @if($paymentAccount->bank_name)
            <div class="row">
                <span class="label">Bank</span>
                <span class="value">{{ $paymentAccount->bank_name }}</span>
            </div>
            @endif
            @if($paymentAccount->bank_holder_name)
            <div class="row">
                <span class="label">Account Holder</span>
                <span class="value">{{ $paymentAccount->bank_holder_name }}</span>
            </div>
            @endif
            @if($paymentAccount->bank_account_no)
            <div class="row">
                <span class="label">Account Number</span>
                <span class="value">{{ $paymentAccount->bank_account_no }}</span>
            </div>
            @endif
            @if($paymentAccount->swift)
            <div class="row">
                <span class="label">SWIFT / BIC</span>
                <span class="value">{{ $paymentAccount->swift }}</span>
            </div>
            @endif
            @if($paymentAccount->paypal_email && !$paymentAccount->bank_name)
            <div class="row">
                <span class="label">PayPal</span>
                <span class="value">{{ $paymentAccount->paypal_email }}</span>
            </div>
            @endif
            <div class="row">
                <span class="label">Amount</span>
                <span class="value" style="color:#166534;font-weight:700;">{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</span>
            </div>
        </div>
        <p>✅ Your refund of <strong>{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</strong> will be transferred to the account above. Please allow 1–3 business days for the funds to reflect.</p>
    @elseif($refund->payment_type === 'wallet')
        <div class="info-card" style="border-left-color:#166534">
            <div class="row"><span class="label" style="font-weight:700;color:#111827;flex:1">💰 Refund Destination</span></div>
            <div class="row">
                <span class="label">Method</span>
                <span class="value">Your Raines Africa Wallet</span>
            </div>
            <div class="row">
                <span class="label">Amount</span>
                <span class="value" style="color:#166534;font-weight:700;">{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</span>
            </div>
        </div>
        <p>✅ Your Raines Africa Wallet has been credited with <strong>{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</strong>. You can use this balance for your next purchase.</p>
    @else
        <p>✅ Your refund of <strong>{{ $currencySymbol }}{{ number_format($refund->amount, 2) }}</strong> will be processed to your registered payment account within 1–3 business days.</p>
    @endif

    <h2 style="margin-top:24px">What happens next?</h2>
    @if(!$paymentAccount && $refund->payment_type === 'wallet')
        <p>Your wallet has already been credited. Log in to your account to check your wallet balance or make a new purchase.</p>
    @else
        <p>Our finance team will process the transfer to your account. If you do not receive the funds within 3 business days, please contact our support team with your refund reference: <strong>#{{ $refund->id }}</strong>.</p>
    @endif

@elseif($isRejected && !empty($refund->reason))
    <div class="highlight-box">
        <strong>Reason for Rejection:</strong><br>
        {{ $refund->reason }}
    </div>
    <p>If you believe this decision was made in error or have additional information to provide, please contact our support team.</p>

@elseif($wasReopened)
    <h2>What happens next?</h2>
    <p>Your refund request is now back in our review queue. A member of our team will re-evaluate it and make a final decision within 2–3 business days. You will receive another email once a decision has been made.</p>
@endif

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL', 'https://raines.africa') }}/en/account/refunds" class="btn btn-primary">View Refund Status</a>
</div>

<hr class="divider">
<p style="font-size:13px;color:#6b7280">If you have any questions, reply to this email or contact us at <a href="mailto:admin@raines.africa" style="color:#C0392B">admin@raines.africa</a> &nbsp;|&nbsp; +263 779 411 028 &nbsp;|&nbsp; +260 777 265 389</p>
<p style="color:#374151">Thank you for shopping with Raines Africa.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
