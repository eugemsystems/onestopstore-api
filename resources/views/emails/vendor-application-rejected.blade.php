{{-- Vendor Application Rejected — no-reply@raines.africa --}}
@include('emails.partials.layout', [
    'preheader'     => 'An update on your vendor application for ' . $storeName,
    'emailTitle'    => 'Vendor Application Update',
    'isInteractive' => false,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#6b7280 0%,#374151 100%);">
    <h1>Update on Your Vendor Application</h1>
    <p>Raines Africa Vendor Program</p>
</div>

<p>Dear <strong>{{ $vendorName }}</strong>,</p>
<p>
    Thank you for your interest in becoming a vendor on Raines Africa. After careful review of your
    application for <strong>{{ $storeName }}</strong>, we regret that we are unable to approve your
    application at this time.
</p>

@if($rejectionReason)
<div style="background:#fef2f2;border-left:4px solid #C0392B;border-radius:6px;padding:16px;margin:20px 0;font-size:14px;">
    <strong>Reason for decision:</strong>
    <p style="margin:8px 0 0;">{{ $rejectionReason }}</p>
</div>
@endif

<div class="highlight-box">
    <strong>What you can do next:</strong>
    <ul style="margin:10px 0 0 16px;line-height:2;font-size:14px;">
        <li><strong>Review requirements:</strong> Ensure your business meets all vendor requirements</li>
        <li><strong>Improve documentation:</strong> Make sure all required documents are complete and accurate</li>
        <li><strong>Reapply:</strong> You're welcome to submit a new application in the future</li>
        <li><strong>Contact support:</strong> Reach out to us for more information</li>
    </ul>
</div>

<div class="btn-wrap">
    <a href="{{ config('app.frontend_url', config('app.url')) }}/en/seller/become-seller" class="btn btn-secondary">Submit New Application</a>
</div>

<p style="font-size:14px;">
    Need help? Contact our team at
    <a href="mailto:admin@raines.africa" style="color:#C0392B;">admin@raines.africa</a>
    or call +263779411028 / +260777265389.
</p>
<p>We appreciate your interest and hope to work with you in the future.</p>

<p>Best regards,<br><strong>The Raines Africa Team</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => false])
