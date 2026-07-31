@include('emails.partials.layout', [
    'preheader'     => 'Your layby application has been approved — ' . $application->application_number,
    'emailTitle'    => 'Layby Application Approved',
    'isInteractive' => true,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>✅ Layby Application Approved</h1>
    <p>{{ $application->application_number }}</p>
</div>

<p>Hi <strong>{{ $application->user->name }}</strong>,</p>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>Good News!</strong> Your layby application has been approved.
</div>

<h2>Application Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Application Number</span><span class="value">{{ $application->application_number }}</span></div>
    <div class="row"><span class="label">Product</span><span class="value">{{ $application->product_name }}</span></div>
    <div class="row"><span class="label">Total Amount</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</span></div>
    <div class="row"><span class="label">Duration</span><span class="value">{{ $application->duration_months }} months</span></div>
</div>

<h2>Payment Schedule</h2>
<div class="info-card">
    <div class="row"><span class="label">Deposit ({{ $application->deposit_percentage }}%)</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->deposit_amount, 2) }}</span></div>
    <div class="row"><span class="label">Monthly Payment</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->monthly_amount, 2) }}</span></div>
</div>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>Next Step:</strong> Please make your first payment to activate your layby.
</div>

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL') }}/en/account/laybys/{{ $application->id }}/payment" class="btn btn-primary">Make Payment</a>
</div>

<p style="font-size:14px;color:#6b7280">If you have any questions, please contact our support team.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
