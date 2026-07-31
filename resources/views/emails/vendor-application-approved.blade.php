{{-- Vendor Application Approved — no-reply@raines.africa --}}
@include('emails.partials.layout', [
    'preheader'     => 'Your vendor application for ' . $storeName . ' has been approved!',
    'emailTitle'    => 'Vendor Application Approved',
    'isInteractive' => false,
])

<div class="email-heading-strip">
    <h1>🎉 Application Approved!</h1>
    <p>Your vendor account on Raines Africa is now active</p>
</div>

<p>Dear <strong>{{ $vendorName }}</strong>,</p>
<p>
    We're delighted to inform you that your vendor application for
    <strong>{{ $storeName }}</strong> has been
    <span style="color:#166534;font-weight:700;">APPROVED</span>!
    You can now start listing products and selling on Raines Africa.
</p>

<div class="highlight-box">
    <strong>What happens next?</strong>
    <ul style="margin:10px 0 0 16px;line-height:2;">
        <li>Your vendor account is now active</li>
        <li>You can start listing your products</li>
        <li>Access your seller dashboard to manage your store</li>
        <li>Configure your payment and shipping settings</li>
    </ul>
</div>

<div class="btn-wrap">
    <a href="{{ config('app.url') }}/admin/login" class="btn btn-primary">Access Your Seller Dashboard</a>
</div>

<h2 style="margin-top:24px;">Next Steps</h2>
<ol style="padding-left:20px;font-size:14px;line-height:2.2;color:#374151;">
    <li><strong>Login:</strong> Use your registered email and password</li>
    <li><strong>Complete your profile:</strong> Add your logo, banner, and store description</li>
    <li><strong>Add products:</strong> Start listing with images and descriptions</li>
    <li><strong>Set up payments:</strong> Configure how you receive payments</li>
    <li><strong>Start selling!</strong> Your products will be visible to customers</li>
</ol>

<hr class="divider">
<h2>Need Help?</h2>
<p style="font-size:14px;">Our support team is here to help — contact us at
    <a href="mailto:admin@raines.africa" style="color:#C0392B;">admin@raines.africa</a>
    or call +263779411028 / +260777265389.
</p>
<p>We're excited to have you as part of the Raines Africa seller community!</p>

<p>Best regards,<br><strong>The Raines Africa Team</strong></p>

@include('emails.partials.layout-close', ['isInteractive' => false])
