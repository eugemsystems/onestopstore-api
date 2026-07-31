{{-- Auction Outbid Notification --}}
@include('emails.partials.layout', [
    'preheader'     => 'You\'ve been outbid on ' . $auction->title,
    'emailTitle'    => 'You\'ve Been Outbid',
    'isInteractive' => false,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#1a1a2e 0%,#7c3aed 100%);">
    <h1>🔨 You've Been Outbid!</h1>
    <p>Don't let it go, {{ $outbidUser->name }}!</p>
</div>

<p>Hi <strong>{{ $outbidUser->name }}</strong>,</p>
<p>
    Someone just placed a higher bid on an auction you were leading. Act fast — the auction is still open!
</p>

<div class="info-card" style="margin-bottom:20px;">
    <div class="row">
        <span class="label">Auction Item</span>
        <span class="value" style="font-weight:700;">{{ $auction->title }}</span>
    </div>
    <div class="row">
        <span class="label">Current Bid</span>
        <span class="value" style="font-size:20px;font-weight:800;color:#7c3aed;">${{ number_format($newBidAmount, 2) }}</span>
    </div>
    @if($auction->ends_at)
    <div class="row">
        <span class="label">Auction Closes</span>
        <span class="value">{{ $auction->ends_at->format('d M Y, H:i') }}</span>
    </div>
    @endif
    @if($userMaxAutoBid > 0)
    <div class="row">
        <span class="label">Your Auto-Bid Max</span>
        <span class="value" style="color:#dc2626;">${{ number_format($userMaxAutoBid, 2) }} — <em>reached</em></span>
    </div>
    @endif
</div>

<div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:8px;padding:16px;margin-bottom:20px;font-size:14px;color:#7c3aed;">
    ⚡ <strong>Bid now to take the lead again!</strong><br>
    You can also set a higher Auto-Bid maximum from your account page to let us bid on your behalf automatically.
</div>

<div class="btn-wrap">
    <a href="{{ config('app.frontend_url') }}/en/auction/{{ $auction->id }}" class="btn btn-primary" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
        🔨 Bid Again Now
    </a>
</div>

<p style="font-size:13px;color:#6b7280;">
    Button not working? Paste this into your browser:<br>
    <span style="word-break:break-all;color:#7c3aed;">{{ config('app.frontend_url') }}/en/auction/{{ $auction->id }}</span>
</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
