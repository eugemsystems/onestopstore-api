@php
    $headingText = $isOverdue ? 'Payment Still Outstanding' : ($urgency === 'critical' ? 'Urgent: Pay Before Deadline' : 'Payment Pending — Action Required');
    $headingIcon = $isOverdue ? '🚨' : ($urgency === 'critical' ? '⚡' : ($urgency === 'urgent' ? '⏰' : '🔔'));
    $headingSub  = $isOverdue ? 'Your payment window has passed. Please settle immediately.' : "You have {$hoursLeft}h left to complete your payment.";
    $stripColor  = $isOverdue ? 'background:linear-gradient(135deg,#0f172a,#dc2626,#0f172a)'
                  : ($urgency === 'critical' ? 'background:linear-gradient(135deg,#7f1d1d,#ef4444,#7f1d1d)'
                  : ($urgency === 'urgent'   ? 'background:linear-gradient(135deg,#78350f,#f59e0b,#78350f)'
                  :                            'background:linear-gradient(135deg,#1e40af,#3b82f6,#1e40af)'));
@endphp

@include('emails.partials.layout', [
    'preheader'     => "Auction Payment Reminder #{$reminderNumber} — " . $auction->title,
    'emailTitle'    => $headingText,
    'isInteractive' => false,
])
<style>
  .reminder-badge { display:inline-block; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); border-radius:20px; padding:4px 14px; font-size:.75rem; font-weight:700; color:#fff; letter-spacing:.5px; text-transform:uppercase; margin-bottom:10px; }
  .auction-item-row { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; margin-bottom:20px; }
  .auction-item-title { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 6px; }
  .auction-item-meta  { font-size:.82rem; color:#64748b; }
  .breakdown-card { border-radius:16px; padding:22px 24px; margin-bottom:22px; }
  .breakdown-card.normal  { background:linear-gradient(135deg,#fefce8,#fef9c3); border:1.5px solid #fde68a; }
  .breakdown-card.overdue { background:linear-gradient(135deg,#fef2f2,#fee2e2); border:1.5px solid #fca5a5; }
  .breakdown-title { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.6px; margin-bottom:14px; }
  .breakdown-card.normal .breakdown-title  { color:#92400e; }
  .breakdown-card.overdue .breakdown-title { color:#991b1b; }
  .bdr { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:.88rem; border-bottom:1px dashed #fde68a; }
  .breakdown-card.overdue .bdr { border-color:#fca5a5; }
  .bdr:last-child { border-bottom:none; }
  .dl { }
  .breakdown-card.normal .dl  { color:#78350f; }
  .breakdown-card.overdue .dl { color:#991b1b; }
  .dv { font-weight:700; color:#0f172a; }
  .dv.deduction { color:#059669; }
  .dv.addition  { color:#dc2626; }
  .total-row { border-radius:10px; padding:14px 18px; margin-top:12px; display:flex; justify-content:space-between; align-items:center; }
  .total-row.overdue { background:linear-gradient(135deg,#dc2626,#b91c1c); }
  .total-row.normal  { background:linear-gradient(135deg,#1d4ed8,#3b82f6); }
  .total-row .tl { color:rgba(255,255,255,.8); font-size:.85rem; font-weight:700; }
  .total-row .tv { color:#fff; font-size:1.3rem; font-weight:900; }
  .deadline-box { border-radius:12px; padding:18px 22px; margin-bottom:22px; text-align:center; }
  .deadline-normal   { background:#eff6ff; border:2px solid #bfdbfe; }
  .deadline-urgent   { background:#fffbeb; border:2px solid #fde68a; }
  .deadline-critical { background:#fff7ed; border:2px solid #fed7aa; }
  .deadline-overdue  { background:#fef2f2; border:2px solid #fca5a5; }
  .deadline-time { font-size:1.6rem; font-weight:900; }
  .deadline-normal .deadline-time   { color:#1d4ed8; }
  .deadline-urgent .deadline-time   { color:#d97706; }
  .deadline-critical .deadline-time { color:#c2410c; }
  .deadline-overdue .deadline-time  { color:#dc2626; }
  .deadline-sub { font-size:.82rem; color:#64748b; margin-top:4px; }
  .warn-box { background:#fef3c7; border:1px solid #fcd34d; border-radius:12px; padding:16px 20px; font-size:.85rem; color:#92400e; line-height:1.7; margin-bottom:22px; }
  .warn-box.danger { background:#fef2f2; border-color:#fca5a5; color:#991b1b; }
  .cta-btn { display:block; text-align:center; color:#fff; font-weight:800; font-size:1rem; padding:16px 32px; border-radius:12px; text-decoration:none; margin:0 0 20px; }
  .cta-normal  { background:linear-gradient(135deg,#1d4ed8,#3b82f6); box-shadow:0 6px 20px rgba(59,130,246,.4); }
  .cta-urgent  { background:linear-gradient(135deg,#d97706,#f59e0b); box-shadow:0 6px 20px rgba(245,158,11,.4); }
  .cta-overdue { background:linear-gradient(135deg,#b91c1c,#dc2626); box-shadow:0 6px 20px rgba(220,38,38,.4); }
</style>

<div class="email-heading-strip" style="{{ $stripColor }}">
    <div class="reminder-badge">📋 Payment Reminder #{{ $reminderNumber }}</div>
    <h1>{{ $headingIcon }} {{ $headingText }}</h1>
    <p>{{ $headingSub }}</p>
</div>

<p>Hi <strong>{{ $winner->name }}</strong>,</p>
<p style="color:#475569">
    @if($isOverdue)
        Your payment for the auction item below is now <strong>overdue</strong>.
        Failure to pay may result in your bidding privileges being suspended.
    @else
        This is reminder #{{ $reminderNumber }} that your payment for the auction item below is still outstanding.
        Please complete your payment before the deadline to avoid losing the item and having your account restricted.
    @endif
</p>

<div class="auction-item-row">
    <div class="auction-item-title">🔨 {{ $auction->title }}</div>
    <div class="auction-item-meta">
        Lot #{{ $auction->id }}
        @if($auction->condition) &nbsp;·&nbsp; {{ ucfirst($auction->condition) }} @endif
        &nbsp;·&nbsp; Ended {{ $auction->ends_at?->format('d M Y, H:i') }}
    </div>
</div>

<div class="breakdown-card {{ $isOverdue ? 'overdue' : 'normal' }}">
    <div class="breakdown-title">💳 Payment Breakdown</div>
    <div class="bdr">
        <span class="dl">Winning Bid</span>
        <span class="dv">{{ $symbol }}{{ number_format($winnerBid, 2) }}</span>
    </div>
    @if($deliveryCost > 0)
    <div class="bdr">
        <span class="dl">🚚 Delivery Fee</span>
        <span class="dv addition">+ {{ $symbol }}{{ number_format($deliveryCost, 2) }}</span>
    </div>
    @endif
    @if($depositPaid > 0)
    <div class="bdr">
        <span class="dl">✅ Deposit Paid (deducted)</span>
        <span class="dv deduction">− {{ $symbol }}{{ number_format($depositPaid, 2) }}</span>
    </div>
    @endif
    <div class="total-row {{ $isOverdue ? 'overdue' : 'normal' }}">
        <span class="tl">{{ $isOverdue ? '🚨 Overdue Balance' : 'Balance Due' }}</span>
        <span class="tv">{{ $symbol }}{{ number_format($balanceDue, 2) }}</span>
    </div>
</div>

@if($deadline)
<div class="deadline-box deadline-{{ $urgency }}">
    @if($isOverdue)
        <div class="deadline-time">⛔ OVERDUE</div>
        <div class="deadline-sub">Payment was due {{ $deadline->format('d M Y, H:i') }}</div>
    @else
        <div class="deadline-time">{{ $hoursLeft }}h remaining</div>
        <div class="deadline-sub">Pay by {{ $deadline->format('d M Y, H:i') }}</div>
    @endif
</div>
@endif

<div class="warn-box {{ $isOverdue ? 'danger' : '' }}">
    ⚠️ <strong>{{ $isOverdue ? 'Immediate action required:' : 'Please note:' }}</strong>
    If payment is not received within {{ $hoursToPay }} hours of winning, your item will be forfeited
    and <strong>your account will be restricted from placing future bids and requesting refunds</strong>.
</div>

<a href="{{ $payUrl }}" class="cta-btn cta-{{ $isOverdue ? 'overdue' : ($urgency === 'normal' ? 'normal' : 'urgent') }}">
    @if($isOverdue) 🚨 Settle Payment Immediately
    @else 💳 Pay {{ $symbol }}{{ number_format($balanceDue, 2) }} Now
    @endif
</a>

<p style="font-size:13px;color:#94a3b8">
    Need help? Contact our support team immediately and reference <strong>Lot #{{ $auction->id }}</strong>.
    If you have already paid, please ignore this reminder — it may take a few hours for confirmation to process.
</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
