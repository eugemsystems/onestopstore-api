@include('emails.partials.layout', [
    'preheader'     => 'We sincerely apologise for the delay with your order #' . $order->order_number,
    'emailTitle'    => 'Order Delay Apology',
    'isInteractive' => true,
])
<style>
  .items-table { width:100%; border-collapse:collapse; margin:16px 0; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; }
  .items-table th { background:#111827; color:#fff; padding:10px 14px; text-align:left; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
  .items-table td { padding:12px 14px; font-size:13px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
  .items-table tr:last-child td { border-bottom:none; }
  .eta-badge { display:inline-block; background:#fef2f2; color:#dc2626; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; }
  .priority-box { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #86efac; border-radius:8px; padding:18px 20px; margin:20px 0; }
  .priority-box h4 { margin:0 0 8px; color:#166534; font-size:15px; }
  .priority-box p { margin:0; color:#15803d; font-size:13px; }
  .order-summary-box { background:#fff; padding:16px 20px; border-radius:8px; margin:16px 0; border:1px solid #e5e7eb; }
  .order-summary-box h3 { margin:0 0 12px; font-size:15px; color:#111827; }
  .order-summary-box p { margin:5px 0; font-size:14px; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#7f1d1d,#b91c1c)">
    <h1>😔 We Sincerely Apologise</h1>
    <p>We're sorry your order is taking longer than expected</p>
</div>

<p>Dear <strong>{{ $order->consumer->name ?? 'Valued Customer' }}</strong>,</p>

<div class="highlight-box" style="background:#fef2f2;border-left-color:#dc2626;color:#7f1d1d">
    <strong>📦 Delay Notice — Order #{{ $order->order_number }}</strong><br>
    We are aware that some of your items have not arrived within the estimated delivery window. Please accept our sincere apologies for this inconvenience.
</div>

<p>We want to personally reach out and apologise for the delay with your order. We completely understand how frustrating it can be when your items don't arrive on time, and we take full responsibility for this.</p>

<p>We want you to know that <strong>your order is now a top priority for our team.</strong> We are actively working to resolve the delay and get your items to you as quickly as possible.</p>

<p style="font-weight:600;color:#111827">The following items are delayed:</p>
<table class="items-table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Original ETA</th>
            <th>Days Overdue</th>
        </tr>
    </thead>
    <tbody>
        @foreach($overdueItems as $item)
        <tr>
            <td>
                <strong>{{ $item['name'] }}</strong>
                @if(!empty($item['variation']))<br><span style="color:#6b7280;font-size:12px">{{ $item['variation'] }}</span>@endif
            </td>
            <td>
                @if(!empty($item['eta']))
                    <span class="eta-badge">{{ \Carbon\Carbon::parse($item['eta'])->format('d M Y') }}</span>
                @else
                    <span style="color:#9ca3af">—</span>
                @endif
            </td>
            <td>
                @if(!empty($item['days_overdue']) && $item['days_overdue'] > 0)
                    <span style="color:#dc2626;font-weight:600">{{ $item['days_overdue'] }} {{ Str::plural('day', $item['days_overdue']) }}</span>
                @else
                    <span style="color:#9ca3af">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="priority-box">
    <h4>✅ What we're doing for you</h4>
    <p>
        Your order has been flagged as <strong>high priority</strong> in our system.
        Our fulfilment team has been notified and will ensure your items are dispatched at the earliest possible opportunity.
        You will receive an update as soon as your order status changes.
    </p>
</div>

<div class="order-summary-box">
    <h3>Order Summary</h3>
    <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
    <p><strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
    <p><strong>Current Status:</strong> {{ $order->order_status->name ?? 'In Progress' }}</p>
    @if($order->shipping_address)
    @php
        $addrCity    = $order->shipping_address->city ?? null;
        $countryRaw  = $order->shipping_address->country ?? null;
        $addrCountry = is_object($countryRaw)
            ? ($countryRaw->name ?? '')
            : (is_array($countryRaw) ? ($countryRaw['name'] ?? '') : (string) ($countryRaw ?? ''));
        $deliveryTo  = implode(', ', array_filter([$addrCity, $addrCountry]));
    @endphp
    @if($deliveryTo)
    <p><strong>Delivery To:</strong> {{ $deliveryTo }}</p>
    @endif
    @endif
</div>

<p>If you have any questions or would like an update, please do not hesitate to contact our support team. We are here to help and will do everything in our power to make this right for you.</p>

<div class="btn-wrap">
    <a href="{{ env('FRONTEND_URL', 'https://raines.africa') }}/en/account/order/details/{{ $order->order_number }}" class="btn btn-primary">Track Your Order</a>
</div>

<p style="color:#6b7280;font-size:13px">Once again, we sincerely apologise for any inconvenience caused. Thank you for your patience and for choosing Raines Africa.</p>
<p style="color:#374151">Warm regards,<br><strong>The {{ config('app.name', 'Raines Africa') }} Team</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => true])
