@php
    $isLast = $reminderNumber >= 3;
    $stripColor  = $isLast
        ? 'background:linear-gradient(135deg,#7f1d1d,#b91c1c)'
        : ($reminderNumber === 2
            ? 'background:linear-gradient(135deg,#78350f,#d97706)'
            : 'background:linear-gradient(135deg,#1e3a5f,#2563eb)');
    $headingIcon = $isLast ? '⏳' : ($reminderNumber === 2 ? '⏰' : '🛒');
    $headingText = $isLast ? 'Cart Expiring Soon' : ($reminderNumber === 2 ? 'Still Interested?' : 'You Left Something Behind');
    $itemLabel = $itemCount === 1 ? 'item' : 'items';
@endphp

@include('emails.partials.layout', [
    'preheader'     => $headingText . ' — ' . $itemCount . ' ' . $itemLabel . ' waiting in your cart',
    'emailTitle'    => $headingText,
    'isInteractive' => true,
])

<div class="email-heading-strip" style="{{ $stripColor }}">
    <h1 style="color:#ffffff">{{ $headingIcon }} {{ $headingText }}</h1>
    <p style="color:#ffffff">{{ $itemCount }} {{ $itemLabel }} waiting for you</p>
</div>

<p>Hello <strong>{{ $user->name ?? 'there' }}</strong>,</p>

@if($reminderNumber === 1)
    <div class="highlight-box" style="background:#dbeafe;border-left-color:#3b82f6;color:#1e40af">
        <strong>👋 Friendly Reminder</strong><br>
        You still have {{ $itemCount }} {{ $itemLabel }} sitting in your cart.
    </div>
    <p>We've kept everything just as you left it. Whenever you're ready, picking up where you left off only takes a minute.</p>
    <p>If you need any help deciding or checking out, our team is here for you.</p>

@elseif($reminderNumber === 2)
    <div class="highlight-box" style="background:#fffbeb;border-left-color:#f59e0b;color:#92400e">
        <strong>⏰ Still There</strong><br>
        Your cart is still waiting, but items can sell out.
    </div>
    <p>We noticed you haven't completed your order yet. Popular items don't always stick around, so it might be worth finishing up soon.</p>
    <p>If anything's stopping you from checking out, just reply and let us know — we're happy to help.</p>

@else
    <div class="highlight-box" style="background:#fef2f2;border-left-color:#dc2626;color:#991b1b">
        <strong>⏳ Last Chance</strong><br>
        This is your final reminder about these items.
    </div>
    <p>This is the last reminder we'll send about the {{ $itemCount }} {{ $itemLabel }} in your cart. After this, we'll assume you've moved on — but you're always welcome to start fresh whenever you like.</p>
    <p>If you'd still like to complete your order, now's the time.</p>
@endif

<h2>Cart Summary</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:16px 0;">
    @forelse($items as $item)
    <tr>
        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;width:56px;vertical-align:middle;">
            @if(!empty($item['image_url']))
                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" width="50" height="50" style="width:50px;height:50px;object-fit:cover;border-radius:6px;display:block;border:1px solid #e5e7eb;">
            @else
                <div style="width:50px;height:50px;background:#f3f4f6;border-radius:6px;"></div>
            @endif
        </td>
        <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;vertical-align:middle;">
            <div style="font-size:14px;color:#111827;font-weight:600;">{{ $item['name'] }}</div>
            @if(($item['quantity'] ?? 1) > 1)
                <div style="font-size:12px;color:#6b7280;margin-top:2px;">Qty: {{ $item['quantity'] }}</div>
            @endif
        </td>
        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;vertical-align:middle;text-align:right;white-space:nowrap;">
            <span style="font-size:14px;font-weight:600;color:#111827;">{{ $currencySymbol }}{{ number_format($item['price'], 2) }}</span>
        </td>
    </tr>
    @empty
    <tr>
        <td style="padding:10px 0;color:#6b7280;font-size:14px;">{{ $itemCount }} {{ $itemLabel }} in your cart</td>
    </tr>
    @endforelse
</table>

<div class="btn-wrap">
    <a href="{{ $cartUrl }}" class="btn btn-primary">
        {{ $isLast ? 'Complete Your Order' : 'View Your Cart' }}
    </a>
</div>
<p style="font-size:14px;color:#6b7280;text-align:center">
    @if($reminderNumber === 1) We're here to help if you have any questions!
    @else Need help? Our support team is just a message away. @endif
</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
