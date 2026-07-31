{{-- Forgot Password — no-reply@raines.africa --}}
@php
    $appName = config('app.name', 'Raines Africa');
    $baseUrl  = config('app.frontend_url') ?? config('app.url') ?? url('/');
    $resetPath = rtrim(config('app.reset_password_path') ?? '/reset-password', '/');
    $resetUrl  = rtrim($baseUrl, '/') . $resetPath;
    $querySep  = parse_url($resetUrl, PHP_URL_QUERY) ? '&' : '?';
    $resetUrl .= $querySep . 'token=' . urlencode($token);
@endphp
@include('emails.partials.layout', ['preheader' => 'Reset your Raines Africa account password', 'emailTitle' => 'Reset Your Password', 'isInteractive' => false])

<div class="email-heading-strip">
    <h1>🔒 Reset Your Password</h1>
    <p>We received a request to reset your {{ $appName }} account password.</p>
</div>

<p>Use the token below in the app to set a new password:</p>

<div style="background:#f3f4f6;border:1px dashed #d1d5db;border-radius:8px;padding:16px 20px;margin:20px 0;text-align:center;">
    <p style="font-size:12px;color:#6b7280;margin:0 0 6px;">Password Reset Token</p>
    <p style="font-family:monospace;font-size:22px;font-weight:700;color:#1a1a2e;letter-spacing:3px;margin:0;">{{ $token }}</p>
</div>

<p>Or click the button below to go directly to the reset page:</p>

<div class="btn-wrap">
    <a href="{{ $resetUrl }}" class="btn btn-primary">Reset Password</a>
</div>

<hr class="divider">
<p style="font-size:13px;color:#6b7280;">
    If you did not request a password reset, you can safely ignore this email — your password will remain unchanged.
</p>
<p style="font-size:13px;color:#6b7280;margin-top:8px;">
    <strong>Link not working?</strong> Copy and paste the URL below into your browser:<br>
    <span style="word-break:break-all;color:#C0392B;">{{ $resetUrl }}</span>
</p>

@include('emails.partials.layout-close', ['isInteractive' => false])
