@include('emails.partials.layout', [
    'preheader'     => 'Your withdrawal request has been approved and is being processed.',
    'emailTitle'    => 'Withdrawal Approved',
    'isInteractive' => false,
])
<style>
  .amount-highlight { font-size:2rem; font-weight:900; color:#059669; text-align:center; padding:20px; background:linear-gradient(135deg,#d4edda,#c3e6cb); border-radius:8px; margin:20px 0; }
  .payment-dest { background:#fffbeb; border-left:4px solid #f59e0b; border-radius:6px; padding:14px 16px; margin:16px 0; }
  .payment-dest h4 { margin:0 0 10px; color:#92400e; font-size:14px; }
  .payment-dest p  { margin:4px 0; font-size:13px; color:#92400e; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>✅ Withdrawal Approved!</h1>
    <p>Your payment is being processed</p>
</div>

<p>Hello <strong>{{ $vendor->name }}</strong>,</p>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>Great news!</strong> Your withdrawal request has been approved and is being processed.
</div>

<div class="amount-highlight">${{ number_format($withdrawal->amount, 2) }}</div>

<h2>Withdrawal Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Request Date</span><span class="value">{{ $withdrawal->created_at->format('F d, Y h:i A') }}</span></div>
    <div class="row"><span class="label">Approved Date</span><span class="value">{{ $withdrawal->approved_at->format('F d, Y h:i A') }}</span></div>
    <div class="row"><span class="label">Amount</span><span class="value" style="color:#059669;font-weight:700">${{ number_format($withdrawal->amount, 2) }}</span></div>
    <div class="row"><span class="label">Payment Method</span><span class="value">{{ $withdrawal->payment_type }}</span></div>
    @if($withdrawal->payment_reference)
    <div class="row"><span class="label">Payment Reference</span><span class="value"><code>{{ $withdrawal->payment_reference }}</code></span></div>
    @endif
    @if($withdrawal->message)
    <div class="row"><span class="label">Your Note</span><span class="value">{{ $withdrawal->message }}</span></div>
    @endif
</div>

@if($paymentDetails)
<div class="payment-dest">
    <h4>💳 Payment Destination</h4>
    @if($withdrawal->payment_type === 'Bank')
        <p><strong>Bank Name:</strong> {{ $paymentDetails['bank_name'] ?? 'N/A' }}</p>
        <p><strong>Account Holder:</strong> {{ $paymentDetails['bank_holder_name'] ?? 'N/A' }}</p>
        <p><strong>Account Number:</strong> {{ $paymentDetails['bank_account_no'] ?? 'N/A' }}</p>
        @if(!empty($paymentDetails['swift']))
        <p><strong>SWIFT Code:</strong> {{ $paymentDetails['swift'] }}</p>
        @endif
    @elseif($withdrawal->payment_type === 'Mobile Money')
        <p><strong>Provider:</strong> {{ $paymentDetails['bank_name'] ?? 'N/A' }}</p>
        <p><strong>Account Name:</strong> {{ $paymentDetails['bank_holder_name'] ?? 'N/A' }}</p>
        <p><strong>Mobile Number:</strong> {{ $paymentDetails['bank_account_no'] ?? 'N/A' }}</p>
    @endif
</div>
@endif

@if($store)
<h2>Store Information</h2>
<div class="info-card">
    <div class="row"><span class="label">Store Name</span><span class="value">{{ $store->store_name }}</span></div>
</div>
@endif

@if($withdrawal->admin_notes)
<h2>Admin Notes</h2>
<div class="highlight-box">{{ $withdrawal->admin_notes }}</div>
@endif

<h2>Next Steps</h2>
<p>
    • The payment will be processed to your registered payment method within 3–5 business days.<br>
    • You will receive the funds in your {{ $withdrawal->payment_type }} account.<br>
    @if($withdrawal->payment_reference)
    • Use payment reference <strong>{{ $withdrawal->payment_reference }}</strong> to track your payment.<br>
    @endif
    • You can view your withdrawal history in your vendor dashboard.
</p>

<div class="btn-wrap">
    <a href="{{ url('/admin/vendor/withdrawals') }}" class="btn btn-primary">View Withdrawal History</a>
</div>

<p style="font-size:14px;color:#6b7280">If you have any questions about this withdrawal, please contact our support team.</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
