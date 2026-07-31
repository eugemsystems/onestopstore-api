@include('emails.partials.layout', [
    'preheader'     => 'Your layby is complete — order created! ' . $application->application_number,
    'emailTitle'    => 'Layby Completed — Order Created',
    'isInteractive' => true,
])
<style>
  .order-badge-box { background:linear-gradient(135deg,#C0392B,#922b21); color:#fff; padding:20px; border-radius:8px; margin:20px 0; text-align:center; }
  .order-badge-box .order-num { font-size:2rem; font-weight:900; margin:6px 0; }
  .product-info-box { border:1px solid #e5e7eb; border-radius:8px; padding:16px; margin:16px 0; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>🎉 Congratulations!</h1>
    <p>Your Layby is Complete</p>
</div>

<p>Hi <strong>{{ $application->user->name }}</strong>,</p>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>Great News!</strong> Your layby has been fully paid and we've created your order.
</div>

<h2>Layby Summary</h2>
<div class="info-card">
    <div class="row"><span class="label">Layby Application</span><span class="value">{{ $application->application_number }}</span></div>
    <div class="row"><span class="label">Total Paid</span><span class="value" style="color:#059669;font-weight:700">{{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}</span></div>
    <div class="row"><span class="label">Duration</span><span class="value">{{ $application->duration_months }} months</span></div>
    <div class="row"><span class="label">Completed</span><span class="value">{{ $application->completed_at ? $application->completed_at->format('M d, Y H:i') : now()->format('M d, Y H:i') }}</span></div>
</div>

@if(isset($order) && $order)
<h2>Your Order Has Been Created</h2>
<div class="order-badge-box">
    <p style="margin:0;font-size:13px;opacity:.8">Order Number</p>
    <div class="order-num">#{{ $order->order_number }}</div>
    <p style="margin:0;font-size:13px;opacity:.8">Status: {{ $order->orderStatus->name ?? 'Processing' }}</p>
</div>

<div class="highlight-box" style="background:#f0fdf4;border-left-color:#059669;color:#166534">
    <strong>What's Next?</strong><br>
    Your order is now being processed for shipping. We'll send you tracking information once your order has been dispatched.
</div>
@endif

<h2>Product Details</h2>
<div class="product-info-box">
    <strong>{{ $application->product_name }}</strong>
    @if($application->variation_display_name)
    <p style="margin:5px 0;color:#6b7280">{{ $application->variation_display_name }}</p>
    @endif
    <p style="margin:5px 0"><strong>Price:</strong> {{ $application->currency_symbol }}{{ number_format($application->product_price, 2) }}</p>
</div>

<div class="btn-wrap">
    @if(isset($order) && $order)
    <a href="{{ env('FRONTEND_URL', config('app.url')) }}/en/account/order/details/{{ $order->order_number }}" class="btn btn-primary">Track Your Order</a>
    @else
    <a href="{{ env('FRONTEND_URL', config('app.url')) }}/en/account/laybys/{{ $application->id }}" class="btn btn-primary">View Layby Details</a>
    @endif
</div>

<p style="font-size:14px;color:#6b7280">Thank you for choosing us! If you have any questions about your order, please contact our customer service team.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
