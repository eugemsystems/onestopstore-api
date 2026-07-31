@include('emails.partials.layout', [
    'preheader'     => 'New contact form submission: ' . $contact->subject,
    'emailTitle'    => 'New Contact Form Submission',
    'isInteractive' => true,
])

<div class="email-heading-strip">
    <h1>📬 New Contact Form Submission</h1>
    <p>{{ $contact->subject }}</p>
</div>

<h2>Sender Details</h2>
<div class="info-card">
    <div class="row"><span class="label">Name</span><span class="value">{{ $contact->name }}</span></div>
    <div class="row"><span class="label">Email</span><span class="value">{{ $contact->email }}</span></div>
    @if(!empty($contact->phone))
    <div class="row"><span class="label">Phone</span><span class="value">{{ $contact->phone }}</span></div>
    @endif
</div>

<h2>Message</h2>
<div class="highlight-box" style="background:#f9fafb;border-left-color:#C0392B;color:#374151;white-space:pre-wrap;">{{ $contact->message }}</div>

<div class="btn-wrap">
    <a href="mailto:{{ $contact->email }}" class="btn btn-primary">Reply to {{ $contact->name }}</a>
</div>

<p style="font-size:14px;color:#6b7280">This message was sent from the contact form on {{ config('app.name') }}.</p>

@include('emails.partials.layout-close', ['isInteractive' => true])
