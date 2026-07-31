{{-- Gift Vouchers — no-reply@raines.africa --}}
@include('emails.partials.layout', [
    'preheader'     => 'Your gift vouchers from Order #' . $order->order_number . ' are ready!',
    'emailTitle'    => 'Your Gift Vouchers',
    'isInteractive' => false,
])

<div class="email-heading-strip">
    <h1>🎁 Your Gift Vouchers</h1>
    <p>Order #{{ $order->order_number }}</p>
</div>

<p>Hi <strong>{{ $customer->name }}</strong>,</p>
<p>Thank you for your purchase! Your payment has been confirmed and your gift vouchers are now ready to use.</p>

<div class="highlight-box">
    <strong>How to redeem:</strong>
    <ol style="margin:10px 0 0 16px;line-height:2;font-size:14px;">
        <li>Log in to your account</li>
        <li>Go to "My Vouchers" section</li>
        <li>Enter the voucher code below</li>
        <li>The amount will be added to your wallet</li>
        <li>Use wallet balance to purchase products</li>
    </ol>
</div>

<h2 style="margin:24px 0 16px;">Your Voucher{{ count($vouchers) > 1 ? 's' : '' }}</h2>

@foreach($vouchers as $voucher)
<div style="background:#fff9f9;border:2px solid #C0392B;border-radius:10px;padding:20px;margin:12px 0;text-align:center;">
    <p style="font-size:28px;font-weight:800;color:#C0392B;margin:0 0 8px;">
        {{ $voucher->currency_code }} {{ number_format($voucher->amount, 2) }}
    </p>
    <div style="background:#f3f4f6;border:1px dashed #d1d5db;border-radius:6px;padding:12px;margin:10px 0;">
        <p style="font-family:monospace;font-size:22px;font-weight:700;color:#1a1a2e;letter-spacing:4px;margin:0;">{{ $voucher->code }}</p>
    </div>
    <p style="font-size:13px;color:#6b7280;margin:8px 0 0;">
        <strong>Status:</strong> Active &nbsp;|&nbsp;
        <strong>Valid Until:</strong>
        @if($voucher->expires_at)
            {{ $voucher->expires_at->format('F d, Y') }}
        @else
            No expiration
        @endif
        &nbsp;|&nbsp; <strong>Product:</strong> {{ $voucher->product->name ?? 'Gift Voucher' }}
    </p>
</div>
@endforeach

<div class="btn-wrap" style="margin-top:20px;">
    <a href="{{ config('app.frontend_url') }}/account/gift-cards" class="btn btn-primary">View My Vouchers</a>
</div>

<div style="background:#fef2f2;border-left:4px solid #C0392B;border-radius:6px;padding:14px 16px;margin:20px 0;font-size:14px;">
    <strong>⚠️ Important Notes:</strong>
    <ul style="margin:8px 0 0 16px;line-height:2;">
        <li>Each voucher can only be redeemed once</li>
        <li>Voucher amounts are added as store credit (wallet)</li>
        <li>Wallet credit cannot be withdrawn as cash</li>
        <li>Keep your codes safe — do not share them</li>
        @php $firstVoucher = is_array($vouchers) ? ($vouchers[0] ?? null) : $vouchers->first(); @endphp
        @if($firstVoucher && $firstVoucher->expires_at)
        <li>These vouchers expire on {{ $firstVoucher->expires_at->format('F d, Y') }}</li>
        @endif
    </ul>
</div>

<p>If you have any questions about your vouchers, contact our support team.</p>
<p>Thank you for shopping with us!<br><strong>The Raines Africa Team</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => false])
