{{-- Vendor Banned / Account Suspended — no-reply@raines.africa --}}
@include('emails.partials.layout', [
    'preheader'     => 'Important: Your vendor account for ' . $storeName . ' has been suspended',
    'emailTitle'    => 'Vendor Account Suspended',
    'isInteractive' => false,
])

<div class="email-heading-strip" style="background:linear-gradient(135deg,#7f1d1d 0%,#C0392B 100%);">
    <h1>⚠️ Account Suspended</h1>
    <p>Important Notice — Please Read</p>
</div>

<p>Dear <strong>{{ $vendorName }}</strong>,</p>
<p>
    We regret to inform you that your vendor account for <strong>{{ $storeName }}</strong> has been
    <span style="color:#C0392B;font-weight:700;">SUSPENDED</span> on Raines Africa.
</p>

@if($banReason)
<div style="background:#fef2f2;border-left:4px solid #C0392B;border-radius:6px;padding:16px;margin:20px 0;font-size:14px;">
    <strong>Reason for suspension:</strong>
    <p style="margin:8px 0 0;">{{ $banReason }}</p>
</div>
@endif

<div class="highlight-box">
    <strong>What this means:</strong>
    <ul style="margin:10px 0 0 16px;line-height:2;font-size:14px;">
        <li>Your store is no longer visible to customers</li>
        <li>All your products have been deactivated</li>
        <li>You cannot access your seller dashboard</li>
        <li>Pending orders will be handled according to our policies</li>
    </ul>
</div>

<h2 style="margin-top:24px;">What You Can Do</h2>
<p style="font-size:14px;">
    If you believe this suspension was made in error or would like to appeal, please contact our
    support team — include your <strong>Store ID: {{ $store->id }}</strong> in all communications.
</p>
<p style="font-size:14px;">
    📧 <a href="mailto:admin@raines.africa" style="color:#C0392B;">admin@raines.africa</a>
    &nbsp;|&nbsp; 📞 +263779411028 / +260777265389
</p>

<p>We take vendor compliance seriously to maintain the integrity of our marketplace. Thank you for your understanding.</p>

<p>Best regards,<br><strong>The Raines Africa Team</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => false])
