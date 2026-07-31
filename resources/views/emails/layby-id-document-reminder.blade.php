@php
    $stripColor = $isEscalated ? 'background:linear-gradient(135deg,#7f1d1d,#b91c1c)' : 'background:linear-gradient(135deg,#1e3a5f,#2563eb)';
    $progressPct = round(($count / $maxReminders) * 100);
@endphp

@include('emails.partials.layout', [
    'preheader'     => ($isEscalated ? 'Final Notice' : "Reminder {$count} of {$maxReminders}") . ': Upload Your ID Document — ' . $application->application_number,
    'emailTitle'    => 'ID Document Required',
    'isInteractive' => true,
])
<style>
  .progress-bar  { background:#e5e7eb; border-radius:20px; height:8px; margin:16px 0; overflow:hidden; }
  .progress-fill { height:8px; border-radius:20px; }
  .warn-note { background:#fef9e7; border:1px solid #f1c40f; border-radius:6px; padding:14px 18px; margin-top:20px; font-size:13px; color:#7d6608; }
</style>

<div class="email-heading-strip" style="{{ $stripColor }}">
    <h1>{{ $isEscalated ? '⚠️ Final Notice' : "📋 Reminder {$count} of {$maxReminders}" }}</h1>
    <p>ID Document Required — {{ $application->application_number }}</p>
</div>

<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>
    @if($isEscalated)
        This is our <strong>final automated reminder</strong> regarding your layby application.
        Without your ID document, your application cannot be processed further and may be flagged for manual review.
    @else
        We noticed your layby application is still awaiting your ID document.
        To keep your application active and move to the next step, please upload it at your earliest convenience.
    @endif
</p>

<div class="info-card">
    <div class="row"><span class="label">Application</span><span class="value">{{ $application->application_number }}</span></div>
    <div class="row"><span class="label">Product</span><span class="value">{{ $application->product_name }}</span></div>
    <div class="row"><span class="label">Status</span><span class="value">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span></div>
    <div class="row"><span class="label">Reminders Sent</span><span class="value">{{ $count }} of {{ $maxReminders }}</span></div>
</div>

<div class="progress-bar">
    <div class="progress-fill" style="background:{{ $isEscalated ? '#b91c1c' : '#2563eb' }};width:{{ $progressPct }}%"></div>
</div>
<p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:-8px">Reminder {{ $count }} of {{ $maxReminders }}</p>

<div class="btn-wrap">
    <a href="{{ $uploadUrl }}" class="btn btn-primary">Upload My ID Document</a>
</div>

<p style="font-size:14px">If you have already uploaded your document, please disregard this email. Your document may still be under review.</p>

@if($isEscalated)
<div class="warn-note">
    <strong>⚠️ Important:</strong> This is our last automated reminder. If your document is not uploaded,
    your application will be escalated to our team for manual review and may impact your ability to continue
    with this layby.
</div>
@endif

@include('emails.partials.layout-close', ['isInteractive' => true])
