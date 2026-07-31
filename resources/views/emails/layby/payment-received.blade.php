@include('emails.partials.layout', [
    'preheader'     => 'Payment confirmed for your layby — ' . $application->application_number,
    'emailTitle'    => 'Layby Payment Received',
    'isInteractive' => true,
])
<style>
  .progress-bar  { background:#e5e7eb; border-radius:15px; height:28px; overflow:hidden; margin:16px 0; }
  .progress-fill { background:linear-gradient(90deg,#059669,#10b981); height:100%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; min-width:40px; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>💳 Payment Received</h1>
    <p>{{ $application->application_number }}</p>
</div>

<p>Hi <strong>{{ $application->user->name }}</strong>,</p>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>Payment Confirmed!</strong> We've received your layby payment.
</div>

<h2>Payment Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Layby Application</span><span class="value">{{ $application->application_number }}</span></div>
    <div class="row"><span class="label">Payment Amount</span><span class="value" style="color:#059669;font-weight:700">{{ $application->currency_symbol }}{{ number_format($payment->amount, 2) }}</span></div>
    <div class="row"><span class="label">Payment Method</span><span class="value">{{ strtoupper($payment->payment_method) }}</span></div>
    <div class="row"><span class="label">Payment Date</span><span class="value">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : now()->format('M d, Y H:i') }}</span></div>
</div>

<h2>Layby Progress</h2>
<div class="info-card">
    <div class="row"><span class="label">Total Amount</span><span class="value">{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</span></div>
    <div class="row"><span class="label">Total Paid</span><span class="value" style="color:#059669;font-weight:700">{{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}</span></div>
    <div class="row"><span class="label">Balance Remaining</span><span class="value" style="color:#dc2626;font-weight:700">{{ $application->currency_symbol }}{{ number_format($application->balance_remaining, 2) }}</span></div>
</div>

@php $pct = $application->total_amount > 0 ? min(100, round(($application->total_paid / $application->total_amount) * 100, 1)) : 0; @endphp
<div class="progress-bar">
    <div class="progress-fill" style="width:{{ $pct }}%">{{ $pct }}%</div>
</div>

@if($application->balance_remaining > 0)
<p><strong>Next Payment:</strong> {{ $application->currency_symbol }}{{ number_format($application->monthly_amount, 2) }}</p>
@else
<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    🎉 <strong>Congratulations!</strong> Your layby is fully paid!
</div>
@endif

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL') }}/en/account/laybys/{{ $application->id }}" class="btn btn-primary">View Layby Details</a>
</div>

<p style="font-size:14px;color:#6b7280">Thank you for your payment. If you have any questions, please contact our support team.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
