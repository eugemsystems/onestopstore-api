{{-- Auction Won — no-reply@raines.africa --}}
@include('emails.partials.layout', [
    'preheader'     => 'Congratulations! You won the auction for ' . $auction->title,
    'emailTitle'    => 'You Won the Auction!',
    'isInteractive' => false,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#1a1a2e 0%,#C0392B 100%);">
    <h1>🏆 You Won the Auction!</h1>
    <p>Congratulations, {{ $winner->name }}!</p>
</div>

<p>Hi <strong>{{ $winner->name }}</strong>,</p>
<p>
    Great news — you placed the winning bid on the auction below. To claim your item, please complete payment
    within <strong>48 hours</strong> using your preferred payment method.
</p>

<div class="info-card" style="margin-bottom:20px;">
    <div class="row">
        <span class="label">Item</span>
        <span class="value" style="font-weight:700;">{{ $auction->title }}</span>
    </div>
    <div class="row">
        <span class="label">Condition</span>
        <span class="value">{{ ucfirst($auction->condition) }}</span>
    </div>
    <div class="row">
        <span class="label">Auction Ended</span>
        <span class="value">{{ $auction->ends_at->format('d M Y, H:i') }}</span>
    </div>
    <div class="row">
        <span class="label">Your Winning Bid</span>
        <span class="value" style="font-size:20px;font-weight:800;color:#C0392B;">${{ number_format($auction->winner_bid, 2) }}</span>
    </div>
    @if($auction->description)
    <div class="row">
        <span class="label">Description</span>
        <span class="value">{{ Str::limit($auction->description, 120) }}</span>
    </div>
    @endif
</div>

<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-bottom:20px;font-size:14px;color:#15803d;">
    ✅ <strong>What happens next?</strong><br>
    Once you complete payment, your order will be confirmed and our team will contact you to arrange collection or delivery. All sales are final — no returns on auction items.
</div>

<div class="btn-wrap">
    <a href="{{ $payUrl }}" class="btn btn-primary">💳 Pay Now — ${{ number_format($auction->winner_bid, 2) }}</a>
</div>

<p style="font-size:13px;color:#6b7280;">
    ⏰ <strong>Please note:</strong> If payment is not received within 48 hours, the item may be offered to the next highest bidder.
</p>
<p style="font-size:13px;color:#6b7280;">
    Button not working? Paste this link into your browser:<br>
    <span style="word-break:break-all;color:#C0392B;">{{ $payUrl }}</span>
</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
