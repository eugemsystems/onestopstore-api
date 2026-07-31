@include('emails.partials.layout', [
    'preheader'     => 'An update on your layby application — ' . $application->application_number,
    'emailTitle'    => 'Layby Application Status',
    'isInteractive' => true,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#7f1d1d,#b91c1c)">
    <h1>❌ Layby Application Status</h1>
    <p>{{ $application->application_number }}</p>
</div>

<p>Hi <strong>{{ $application->user->name }}</strong>,</p>

<div class="highlight-box" style="background:#fef2f2;border-left-color:#dc2626;color:#991b1b">
    <strong>Application Not Approved</strong><br>
    Unfortunately, we were unable to approve your layby application at this time.
</div>

<h2>Application Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Application Number</span><span class="value">{{ $application->application_number }}</span></div>
    <div class="row"><span class="label">Product</span><span class="value">{{ $application->product_name }}</span></div>
</div>

@if($application->rejection_reason)
<h2>Reason</h2>
<div class="highlight-box">{{ $application->rejection_reason }}</div>
@endif

<div class="highlight-box" style="background:#fffbeb;border-left-color:#f59e0b;color:#92400e">
    <strong>What You Can Do:</strong><br>
    • Contact our support team for more information<br>
    • Review and resubmit your application<br>
    • Choose alternative payment methods
</div>

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL', config('app.url')) }}/en/contact-us" class="btn btn-primary">Contact Support</a>
</div>

<p style="font-size:14px;color:#6b7280">We're here to help. Please reach out if you have any questions.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
