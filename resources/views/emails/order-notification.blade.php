@php
    use App\Helpers\Helpers;
    use App\Models\Attachment;
    $symbol = $order->currency_symbol ?? ($currency['symbol'] ?? (Helpers::getDefaultCurrencySymbol() ?? ''));
    $code   = $order->currency ?? ($currency['code'] ?? (Helpers::getDefaultCurrencyCode() ?? ''));
    $rate   = (float) ($order->exchange_rate ?? 1);

    $itemsSubtotal = 0.0;
    foreach ($order->products as $p) {
        $unit = $p->pivot->single_price ?? $p->price;
        $itemsSubtotal += ($unit * $p->pivot->quantity);
    }
    if (isset($order->sub_orders) && $order->sub_orders) {
        foreach ($order->sub_orders as $subOrder) {
            if ($subOrder->products) {
                foreach ($subOrder->products as $p) {
                    $unit = $p->pivot->single_price ?? $p->price;
                    $itemsSubtotal += ($unit * $p->pivot->quantity);
                }
            }
        }
    }

    $shipping    = $order->shipping_total ?? 0.0;
    $fastShip    = $order->fast_shipping_total ?? 0.0;
    $delivery    = $order->delivery_price ?? 0.0;
    $tax         = $order->tax_total ?? 0.0;
    $discount    = $order->coupon_total_discount ?? 0.0;
    $grandDisplay = $itemsSubtotal + $shipping + $fastShip + $delivery + $tax - $discount;

    $paymentStatusClass = 'badge-pending';
    if (strtoupper($order->payment_status) === 'COMPLETED') {
        $paymentStatusClass = 'badge-success';
    } elseif (strtoupper($order->payment_status) === 'CANCELLED') {
        $paymentStatusClass = 'badge-danger';
    }

    $settings = Helpers::getSettings();
    $banking  = data_get($settings, 'payment_methods.bank_transfer');
    if (empty($banking)) {
        $methods = data_get($settings, 'payment_methods', []);
        if (is_array($methods)) {
            foreach ($methods as $m) {
                if (is_array($m) && strtolower(data_get($m, 'name')) === 'bank_transfer') {
                    $banking = $m;
                    break;
                }
            }
        }
    }
    $showBanking = strtolower($order->payment_method ?? '') === 'bank_transfer';
@endphp

@include('emails.partials.layout', [
    'preheader'     => ($subject ?? 'Order Notification') . ' — #' . $order->order_number,
    'emailTitle'    => $subject ?? (config('app.name') . ' Order Notification'),
    'isInteractive' => true,
])
<style>
  .section-card { background:#f9fafb; border-radius:10px; border-left:4px solid #C0392B; margin:16px 0; overflow:hidden; }
  .dr { display:table; width:100%; border-bottom:1px solid #f3f4f6; }
  .dr:last-child { border-bottom:none; }
  .dl { display:table-cell; width:40%; padding:10px 12px; font-size:13px; color:#6b7280; font-weight:500; vertical-align:middle; }
  .dv { display:table-cell; width:60%; padding:10px 12px; font-size:13px; font-weight:600; color:#111827; text-align:right; vertical-align:middle; word-break:break-word; }
  .address-box { background:#f9fafb; border-radius:6px; padding:12px; margin-top:8px; font-size:14px; line-height:1.5; }
  .addr-table { width:100%; border-collapse:collapse; }
  .addr-table td { vertical-align:top; padding-right:12px; }
  .addr-title { font-size:15px; font-weight:600; color:#374151; margin:0 0 6px; }
  .summary-tbl { width:100%; border-collapse:collapse; }
  .summary-tbl td { padding:8px 0; border-bottom:1px dashed #e5e7eb; font-size:14px; color:#374151; }
  .summary-tbl td:last-child { text-align:right; }
  .summary-tbl tr:last-child td { border-bottom:none; font-weight:700; font-size:15px; color:#111827; padding-top:12px; }
  .banking-header { display:flex; align-items:flex-start; gap:14px; margin-bottom:16px; }
  .bank-icon { width:44px; height:44px; background:#fde8e8; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#C0392B; flex-shrink:0; }
  .bank-title { font-size:17px; font-weight:600; color:#111827; margin:0 0 4px; }
  .bank-sub { font-size:13px; color:#6b7280; }
</style>

<div class="email-heading-strip">
    <h1>{{ $heading ?? 'Order Notification' }}</h1>
    <p>Order #{{ $order->order_number }} &nbsp;·&nbsp; {{ optional($order->created_at)->format('F j, Y') }}</p>
</div>

<p style="white-space:pre-line">{!! nl2br(e($intro ?? "Thank you for your order. We are processing it and will notify you once it's on its way.")) !!}</p>

@if(!empty($cta_url))
<div class="btn-wrap">
    <a href="{{ $cta_url }}" class="btn btn-primary">{{ $cta_label ?? 'Manage Order' }}</a>
</div>
@endif

@if(!empty($qr_url))
<div style="text-align:center;margin:16px 0">
    <img src="{{ $qr_url }}" alt="Order QR Code for #{{ $order->order_number }}" width="240" height="240"
         style="max-width:240px;border:8px solid #f3f4f6;border-radius:12px;display:block;margin:0 auto" />
    @if(!empty($qr_note))
    <p style="color:#6b7280;font-size:13px;margin-top:8px;text-align:center">{{ $qr_note }}</p>
    @endif
</div>
@endif

<h2>Order Information</h2>
<div class="section-card">
    <div class="dr"><span class="dl">Order Number</span><span class="dv">#{{ $order->order_number }}</span></div>
    <div class="dr"><span class="dl">Order Status</span><span class="dv">{{ optional($order->order_status)->name ?? optional($order->orderStatus)->name ?? 'Pending' }}</span></div>
    <div class="dr">
        <span class="dl">Payment Status</span>
        <span class="dv"><span class="badge {{ $paymentStatusClass }}">{{ $order->payment_status }}</span></span>
    </div>
    <div class="dr">
        <span class="dl">Payment Method</span>
        <span class="dv">
            @if(strtolower($order->payment_method ?? '') === 'cod') Payment at the office
            @elseif(strtolower($order->payment_method ?? '') === 'bank_transfer') Bank Transfer
            @else {{ strtoupper($order->payment_method ?? '—') }} @endif
        </span>
    </div>
    @if(!empty($order->delivery_description))
    <div class="dr"><span class="dl">Delivery Method</span><span class="dv">{{ $order->delivery_description }}</span></div>
    @endif
    <div class="dr">
        <span class="dl">Total Amount</span>
        <span class="dv" style="color:#C0392B;font-size:15px">{{ $symbol }}{{ number_format($grandDisplay, 2, '.', ',') }} {{ $code }}</span>
    </div>
    @if($order->note)
    <div class="dr"><span class="dl">Order Note</span><span class="dv" style="color:#6b7280">{{ $order->note }}</span></div>
    @endif
</div>

@if($order->billing_address || $order->shipping_address)
<h2>Address Details</h2>
<table class="addr-table">
    <tr>
        @if($order->billing_address)
        <td>
            <p class="addr-title">Billing Address</p>
            <div class="address-box">
                {{ $order->billing_address->title ?? '' }}<br>
                @if(!empty($order->billing_address->phone))
                    +{{ $order->billing_address->country_code ?? '' }} {{ $order->billing_address->phone }}<br>
                @endif
                {{ $order->billing_address->street ?? '' }}<br>
                {{ $order->billing_address->city ?? '' }}, {{ optional($order->billing_address->state)->name ?? '' }} {{ $order->billing_address->pincode ?? '' }}<br>
                {{ optional($order->billing_address->country)->name ?? '' }}
            </div>
        </td>
        @endif
        @if($order->shipping_address)
        <td>
            <p class="addr-title">Shipping Address</p>
            <div class="address-box">
                {{ $order->shipping_address->title ?? '' }}<br>
                @if(!empty($order->shipping_address->phone))
                    +{{ $order->shipping_address->country_code ?? '' }} {{ $order->shipping_address->phone }}<br>
                @endif
                {{ $order->shipping_address->street ?? '' }}<br>
                {{ $order->shipping_address->city ?? '' }}, {{ optional($order->shipping_address->state)->name ?? '' }} {{ $order->shipping_address->pincode ?? '' }}<br>
                {{ optional($order->shipping_address->country)->name ?? '' }}
            </div>
        </td>
        @endif
    </tr>
</table>
@endif

<h2>Order Items</h2>
@include('orders-table', ['order' => $order, 'symbol' => $symbol, 'code' => $code])

@if($showBanking)
    @php
        $all_settings = getCachedSettings()->pluck('values')->first();
        $bankingData = data_get($all_settings, 'payment_methods.bank_transfer', []);
        $accounts = [];
        if (is_array($bankingData)) {
            if (isset($bankingData['accounts']) && is_array($bankingData['accounts'])) {
                $accounts = $bankingData['accounts'];
            } else {
                foreach ($bankingData as $k => $v) {
                    if (is_array($v)) { $accounts[] = $v; }
                }
            }
        }
    @endphp
    <h2>Bank Transfer Details</h2>
    <div class="banking-header">
        <div class="bank-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M12 6v12"></path><path d="M22 10h-4"></path><path d="M6 10H2"></path>
            </svg>
        </div>
        <div>
            <div class="bank-title">Bank Transfer Details</div>
            <div class="bank-sub">Please make a bank transfer using the details below. Use your Order Number (<strong>#{{ $order->order_number }}</strong>) as the payment reference.</div>
        </div>
    </div>
    @if(!empty($bankingData) && data_get($bankingData, 'status') && !empty($accounts))
        @foreach($accounts as $bank)
        <table class="summary-tbl" style="margin-bottom:12px">
            <tr><td>Account Holder</td><td>{{ $bankingData['company'] }}</td></tr>
            <tr><td>Bank Name</td><td>{{ $bank['bank'] }}</td></tr>
            <tr><td>Account Number</td><td>{{ $bank['account_number'] }}</td></tr>
            <tr><td>BIC</td><td>{{ $bank['bic'] }}</td></tr>
        </table>
        @endforeach
    @else
        <p style="color:#6b7280;font-size:14px">Bank transfer details are not configured yet. Please contact support.</p>
    @endif
@endif

<h2>Order Summary</h2>
<table class="summary-tbl">
    <tr><td>Items Subtotal</td><td>{{ $symbol }}{{ number_format($itemsSubtotal, 2, '.', ',') }} {{ $code }}</td></tr>
    <tr><td>Shipping</td><td>{{ $symbol }}{{ number_format($shipping, 2, '.', ',') }} {{ $code }}</td></tr>
    @if(($order->fast_shipping_total ?? 0) > 0)
    <tr><td>Fast Shipping</td><td>{{ $symbol }}{{ number_format($fastShip, 2, '.', ',') }} {{ $code }}</td></tr>
    @endif
    <tr><td>Delivery</td><td>{{ $symbol }}{{ number_format($delivery, 2, '.', ',') }} {{ $code }}</td></tr>
    <tr><td>Tax</td><td>{{ $symbol }}{{ number_format($tax, 2, '.', ',') }} {{ $code }}</td></tr>
    <tr><td>Discount</td><td>- {{ $symbol }}{{ number_format($discount, 2, '.', ',') }} {{ $code }}</td></tr>
    <tr><td>Grand Total</td><td>{{ $symbol }}{{ number_format($grandDisplay, 2, '.', ',') }} {{ $code }}</td></tr>
</table>

@include('emails.partials.layout-close', ['isInteractive' => true])
