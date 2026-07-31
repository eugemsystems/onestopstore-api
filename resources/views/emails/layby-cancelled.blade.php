@include('emails.partials.layout', [
    'preheader'     => 'Your layby application has been cancelled — ' . $application->application_number,
    'emailTitle'    => 'Layby Application Cancelled',
    'isInteractive' => true,
])
<style>
  .reason-box { background:#fff3cd; border-left:4px solid #f59e0b; border-radius:6px; padding:14px 16px; margin:16px 0; font-size:14px; color:#92400e; }
  .refund-note { background:#d1ecf1; border-left:4px solid #17a2b8; border-radius:6px; padding:14px 16px; margin:16px 0; font-size:14px; color:#0c5460; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#7f1d1d,#b91c1c)">
    <h1>❌ Layby Application Cancelled</h1>
    <p>{{ $application->application_number }}</p>
</div>

<p>Dear <strong>{{ $user->name }}</strong>,</p>
<p>We regret to inform you that your layby application has been cancelled by our team.</p>

<div class="info-card">
    <div class="row"><span class="label">Application Number</span><span class="value"><strong>{{ $application->application_number }}</strong></span></div>
    <div class="row"><span class="label">Product</span><span class="value">{{ $application->product_name }}</span></div>
    @if($application->variation_display_name)
    <div class="row"><span class="label">Variation</span><span class="value">{{ $application->variation_display_name }}</span></div>
    @endif
    <div class="row"><span class="label">Total Amount</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</span></div>
    <div class="row"><span class="label">Amount Paid</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}</span></div>
    <div class="row"><span class="label">Application Date</span><span class="value">{{ $application->created_at->format('F d, Y') }}</span></div>
    <div class="row"><span class="label">Cancelled Date</span><span class="value">{{ now()->format('F d, Y') }}</span></div>
</div>

<div class="reason-box">
    <strong>Reason for Cancellation:</strong><br>{{ $reason }}
</div>

@if($application->total_paid > 0)
<div class="refund-note">
    <strong>Refund Processing:</strong> If you have made any payments, our team will process your refund within 5–7 business days. You will receive a separate email confirmation once the refund has been processed.
</div>
@endif

<h2>What Happens Next?</h2>
<p>
    • If you have made any payments, a refund will be processed to your original payment method<br>
    • You will receive an email confirmation once the refund is complete<br>
    • You can view your cancellation details in your account dashboard<br>
    • If you have any questions, please contact our support team
</p>

<div class="btn-wrap">
    <a href="{{ config('app.frontend_url') }}/account/laybys" class="btn btn-primary">View My Laybys</a>
</div>

<p>We apologize for any inconvenience this may have caused.</p>

<hr class="divider">
<p style="font-size:13px;color:#6b7280">📧 admin@raines.africa &nbsp;|&nbsp; +263 779 411 028 &nbsp;|&nbsp; +260 777 265 389</p>
<p style="color:#374151">Thank you for your understanding.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
