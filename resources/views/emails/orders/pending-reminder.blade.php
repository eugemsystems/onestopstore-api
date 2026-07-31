@php
    $isCancellation = $reminderType === 'cancellation';
    $headingText = $isCancellation ? 'Order Update' : 'Order Reminder';
    $stripColor  = $isCancellation
        ? 'background:linear-gradient(135deg,#7f1d1d,#b91c1c)'
        : ($reminderType === 'second'
            ? 'background:linear-gradient(135deg,#78350f,#d97706)'
            : 'background:linear-gradient(135deg,#1e3a5f,#2563eb)');
    $headingIcon = $isCancellation ? '📋' : ($reminderType === 'second' ? '⏰' : '👋');
    $paymentMethodDisplay = strtolower($order->payment_method) === 'cod'
        ? 'Payment At The Office'
        : ucwords(str_replace('_', ' ', $order->payment_method));
@endphp

@include('emails.partials.layout', [
    'preheader'     => $headingText . ' — Order #' . $order->order_number,
    'emailTitle'    => $headingText,
    'isInteractive' => true,
])

<div class="email-heading-strip" style="{{ $stripColor }}">
    <h1>{{ $headingIcon }} {{ $headingText }}</h1>
    <p>Order #{{ $order->order_number }}</p>
</div>

<p>Hello <strong>{{ $order->consumer->name ?? 'Customer' }}</strong>,</p>

@if($reminderType === 'first')
    <div class="highlight-box" style="background:#dbeafe;border-left-color:#3b82f6;color:#1e40af">
        <strong>👋 Friendly Reminder</strong><br>
        We noticed your order is still pending, and we'd love to make sure you don't miss out!
    </div>
    <p>Your items are safely reserved and waiting for you. Whenever you're ready, you can complete your order at your convenience.</p>
    <p>If you need any help completing your purchase, our team is here to assist.</p>

@elseif($reminderType === 'second')
    <div class="highlight-box" style="background:#fffbeb;border-left-color:#f59e0b;color:#92400e">
        <strong>⏰ Gentle Reminder</strong><br>
        Your order will be automatically cancelled in 48 hours if not completed.
    </div>
    <p>We noticed your order is still pending. Please complete your order within the next 48 hours to avoid automatic cancellation.</p>
    <p>If you need any help completing your purchase, our team is here to assist. Thank you for choosing us!</p>

@else
    <div class="highlight-box" style="background:#fef2f2;border-left-color:#dc2626;color:#991b1b">
        <strong>📋 Order Update</strong><br>
        Your order has been automatically cancelled.
    </div>
    <p>We wanted to let you know that your order has been automatically cancelled as payment was not completed within the required timeframe.</p>
    <p>We completely understand that plans change! You're always welcome to place a new order whenever you're ready — your favorite items are still available.</p>

    <div class="info-card" style="border-left-color:#3b82f6;background:#eff6ff">
        <p style="margin:0;color:#1e40af">
            <strong>💭 We'd Love Your Feedback</strong><br>
            If you don't mind sharing, we'd appreciate your feedback on what stopped you from completing your order:
        </p>
        <div style="text-align:center;margin-top:12px">
            <a href="{{ env('FRONTEND_URL') }}/{{ app()->getLocale() }}/order-feedback?order={{ $order->order_number }}&token={{ $order->getFeedbackToken() }}"
               class="btn btn-primary" style="font-size:13px;padding:10px 22px">👉 Share Your Feedback</a>
        </div>
    </div>

    <p>If you have any questions or need assistance, please reach out to our support team. We hope to serve you again soon!</p>
@endif

<h2>Order Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Order Number</span><span class="value">#{{ $order->order_number }}</span></div>
    <div class="row"><span class="label">Order Date</span><span class="value">{{ $order->created_at->format('F d, Y H:i') }}</span></div>
    <div class="row"><span class="label">Total Amount</span><span class="value">{{ $order->currency_symbol }}{{ number_format($order->total, 2) }}</span></div>
    <div class="row"><span class="label">Payment Method</span><span class="value">{{ $paymentMethodDisplay }}</span></div>
    <div class="row"><span class="label">Status</span><span class="value">{{ $order->orderStatus->name ?? 'Pending' }}</span></div>
</div>

@if(!$isCancellation)
<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL') }}/{{ app()->getLocale() }}/account/order/details/{{ $order->order_number }}" class="btn btn-primary">
        Complete Your Order
    </a>
</div>
<p style="font-size:14px;color:#6b7280;text-align:center">
    @if($reminderType === 'first') We're here to help if you have any questions!
    @else Need help? Our support team is just a message away. @endif
</p>
@else
<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL') }}/{{ app()->getLocale() }}/collections" class="btn btn-primary">Browse Our Products</a>
</div>
@endif

@include('emails.partials.layout-close', ['isInteractive' => true])
