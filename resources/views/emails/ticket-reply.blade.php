{{-- Ticket Reply Notification — admin@ (admin→customer) or no-reply@ (customer→admin) --}}
@include('emails.partials.layout', [
    'preheader'    => 'New reply on Ticket #' . $ticketNumber,
    'emailTitle'   => 'Ticket Reply Notification',
    'isInteractive' => $isFromAdmin,
])

<div class="email-heading-strip">
    <h1>💬 New Reply on Your Ticket</h1>
    <p>{{ $isFromAdmin ? 'A support agent' : 'The customer' }} has responded to ticket #{{ $ticketNumber }}</p>
</div>

<div class="info-card">
    <div class="row">
        <span class="label">Ticket Number</span>
        <span class="value">#{{ $ticketNumber }}</span>
    </div>
    <div class="row">
        <span class="label">Subject</span>
        <span class="value">{{ $ticketSubject }}</span>
    </div>
</div>

<h2 style="font-size:15px;margin:20px 0 10px;">Reply from {{ $repliedByName }}
    @if($isFromAdmin)
        <span class="badge badge-info" style="font-size:11px;vertical-align:middle;margin-left:6px;">Support Team</span>
    @endif
</h2>
<p style="font-size:13px;color:#6b7280;margin:-6px 0 10px;">
    {{ $repliedAt->format('M d, Y \a\t h:i A') }}
</p>

<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px 20px;font-size:14px;line-height:1.8;word-wrap:break-word;">{!! $messageContent !!}</div>

<div class="btn-wrap" style="margin-top:24px;">
    <a href="{{ $ticketUrl }}" class="btn btn-primary">View Ticket &amp; Reply</a>
</div>

<hr class="divider">
<p style="font-size:13px;color:#6b7280;">
    All replies are tracked in your ticket history. Visit the ticket page to respond.
</p>

@include('emails.partials.layout-close', ['isInteractive' => $isFromAdmin])
