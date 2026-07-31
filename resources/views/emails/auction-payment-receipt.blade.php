@include('emails.partials.layout', [
    'preheader'     => 'Your auction payment has been confirmed — ' . $auction->title,
    'emailTitle'    => 'Auction Payment Confirmed',
    'isInteractive' => false,
])
<style>
  .receipt-card { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:12px; padding:22px; margin-bottom:20px; }
  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px dashed #bbf7d0; font-size:14px; }
  .detail-row:last-child { border-bottom:none; }
  .dl { color:#64748b; }
  .dv { font-weight:700; color:#0f172a; }
  .dv.green { color:#059669; }
  .deposit-row { background:#ecfdf5; border-radius:6px; margin:2px -6px; padding:1px 6px; }
  .deposit-row .dl, .deposit-row .dv { color:#059669; }
  .total-row { background:linear-gradient(135deg,#064e3b,#059669); border-radius:8px; padding:12px 16px; margin-top:10px; display:flex; justify-content:space-between; align-items:center; }
  .total-row .tl { color:#6ee7b7; font-size:.88rem; font-weight:700; }
  .total-row .tv { color:#fff; font-size:1.15rem; font-weight:900; }
  .method-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:20px; font-size:.78rem; font-weight:700; }
  .method-delivery   { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
  .method-collection { background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }
  .order-badge { display:inline-block; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:4px 12px; font-size:.82rem; font-weight:700; color:#334155; font-family:monospace; }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>✅ Payment Confirmed!</h1>
    <p>Your auction payment has been received</p>
</div>

<p>Hi <strong>{{ $winner->name }}</strong>,</p>
<p style="color:#475569">Great news — we have received and confirmed your payment for the auction item below. Here is your payment receipt for your records.</p>

<div class="receipt-card">
    <p style="font-size:1rem;font-weight:800;color:#0f172a;margin:0 0 14px">🔨 {{ $auction->title }}</p>
    <div class="detail-row"><span class="dl">Lot #</span><span class="dv">#{{ $auction->id }}</span></div>
    <div class="detail-row"><span class="dl">Condition</span><span class="dv">{{ ucfirst($auction->condition) }}</span></div>
    <div class="detail-row"><span class="dl">Auction Ended</span><span class="dv">{{ $auction->ends_at?->format('d M Y, H:i') }}</span></div>
    <div class="detail-row"><span class="dl">Winning Bid</span><span class="dv">{{ $symbol }}{{ number_format($winnerBid, 2) }}</span></div>
    @if($deliveryCost > 0)
    <div class="detail-row"><span class="dl">Delivery Fee</span><span class="dv">+ {{ $symbol }}{{ number_format($deliveryCost, 2) }}</span></div>
    @endif
    @if($depositPaid > 0)
    <div class="detail-row deposit-row"><span class="dl">✅ Deposit Applied</span><span class="dv">− {{ $symbol }}{{ number_format($depositPaid, 2) }}</span></div>
    @endif
    <div class="total-row"><span class="tl">Balance Paid</span><span class="tv">{{ $symbol }}{{ number_format($amountPaid, 2) }}</span></div>
</div>

<div class="info-card">
    <div class="row"><span class="label">Order Reference</span><span class="value"><span class="order-badge">#{{ $order->order_number }}</span></span></div>
    <div class="row">
        <span class="label">Fulfillment</span>
        <span class="value">
            @if(($auction->fulfillment_method ?? 'collection') === 'delivery')
                <span class="method-pill method-delivery">🚚 Delivery</span>
                @if($auction->delivery_address) — {{ $auction->delivery_address }}@endif
            @else
                <span class="method-pill method-collection">🏪 Collection</span>
                @if($auction->branch) — {{ $auction->branch }} branch@endif
            @endif
        </span>
    </div>
</div>

@if(!empty($qr_url))
<div style="text-align:center;margin:20px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
    <p style="font-size:.78rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px">Your Collection QR Code</p>
    <img src="{{ $qr_url }}" alt="Collection QR Code" width="180" height="180"
         style="display:block;margin:0 auto;border-radius:10px;border:3px solid #e2e8f0" />
    <p style="font-size:.8rem;color:#94a3b8;margin:10px 0 0">{{ $qr_note ?? 'Show this QR code when collecting your auction item' }}</p>
</div>
@endif

<div class="info-card" style="border-left-color:#059669;background:#f0fdf4">
    <p style="margin:0;color:#15803d">🎉 <strong>What happens next?</strong><br>
    Our team will contact you shortly to arrange {{ ($auction->fulfillment_method ?? 'collection') === 'delivery' ? 'delivery of your item' : 'a collection time at our branch' }}.
    Please keep this receipt for your records.</p>
</div>

<div class="btn-wrap">
    <a href="{{ $wonItemsUrl }}" class="btn btn-primary">View My Won Items</a>
</div>

<p style="font-size:13px;color:#94a3b8">Need help? Contact our support team and reference your order <strong>#{{ $order->order_number }}</strong>.</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
