@php
    $isApproved  = $status === 'approved';
    $isRejected  = $status === 'rejected';
    $stripColor  = $isApproved ? 'background:linear-gradient(135deg,#166534,#15803d)' : ($isRejected ? 'background:linear-gradient(135deg,#991b1b,#b91c1c)' : 'background:linear-gradient(135deg,#C0392B,#922b21)');
    $icon        = $isApproved ? '✅' : ($isRejected ? '❌' : ($wasReopened ? '🔄' : '📋'));
    $headingText = $isApproved ? 'Return Request Approved' : ($isRejected ? 'Return Request Rejected' : ($wasReopened ? 'Return Request Reopened' : 'Return Request Update'));
    $preheader   = $isApproved ? 'Your return request has been approved.' : ($isRejected ? 'An update on your return request.' : 'Your return request status has been updated.');
@endphp

@include('emails.partials.layout', [
    'preheader'     => $preheader,
    'emailTitle'    => $headingText,
    'isInteractive' => true,
])

<div class="email-heading-strip" style="{{ $stripColor }}">
    <h1>{{ $icon }} {{ $headingText }}</h1>
    <p>Return Request #{{ $return->id }}</p>
</div>

<p>Dear <strong>{{ $customerName }}</strong>,</p>

@if($isApproved)
    <p>Great news! Your return request has been <strong style="color:#166534">approved</strong>.</p>
@elseif($isRejected)
    <p>We regret to inform you that your return request has been <strong style="color:#b91c1c">rejected</strong>.</p>
@elseif($wasReopened)
    <p>Your return request has been <strong>reopened</strong> and is now under review again. Our team will get back to you shortly.</p>
@else
    <p>The status of your return request has been updated to <strong>{{ ucfirst($status) }}</strong>.</p>
@endif

{{-- Return Details --}}
<div class="info-card">
    <div class="row">
        <span class="label">Order Number</span>
        <span class="value">#{{ $orderNumber }}</span>
    </div>
    <div class="row">
        <span class="label">Product</span>
        <span class="value">{{ $productName }}</span>
    </div>
    <div class="row">
        <span class="label">Preferred Outcome</span>
        <span class="value">{{ ucfirst($return->preferred_outcome) }}</span>
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

@if($productImage)
    <div style="text-align:center;margin:16px 0">
        <img src="{{ $productImage }}" alt="{{ $productName }}"
             style="max-width:120px;height:auto;border-radius:8px;border:1px solid #e5e7eb" />
    </div>
@endif

@if($isApproved)
    <h2>What happens next?</h2>
    @if(in_array($return->preferred_outcome, ['credit', 'wallet']))
        <p>✅ Your Raines Africa Wallet will be credited shortly. You can use this balance on your next purchase or request a withdrawal from your account.</p>
    @elseif($return->preferred_outcome === 'refund')
        <p>✅ A refund has been initiated for your order. You will receive a separate email once the refund is approved with your payment account details and transfer timeline.</p>
    @elseif($return->preferred_outcome === 'replacement')
        <p>✅ We will arrange a replacement for you. Our team will contact you shortly with delivery details.</p>
    @else
        <p>✅ Your return has been approved. We will process it according to your selected outcome and contact you with next steps.</p>
    @endif
    <p>Please allow up to 5–7 business days for completion.</p>

@elseif($isRejected && !empty($return->rejection_reason))
    <div class="highlight-box">
        <strong>Reason for Rejection:</strong><br>
        {{ $return->rejection_reason }}
    </div>
    <p>If you believe this decision was made in error or have additional information to provide, please contact our support team.</p>

@elseif($wasReopened)
    <h2>What happens next?</h2>
    <p>Your return request is now back in our review queue. A member of our team will re-evaluate it and make a final decision within 2–3 business days. You will receive another email once a decision has been made.</p>
@endif

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL', 'https://raines.africa') }}/en/account/returns" class="btn btn-primary">View Return Status</a>
</div>

<hr class="divider">
<p style="font-size:13px;color:#6b7280">Questions? Email us at <a href="mailto:admin@raines.africa" style="color:#C0392B">admin@raines.africa</a> &nbsp;|&nbsp; +263 779 411 028 &nbsp;|&nbsp; +260 777 265 389</p>
<p style="color:#374151">Thank you for shopping with Raines Africa.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
