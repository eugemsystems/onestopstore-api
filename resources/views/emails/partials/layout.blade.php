{{--
    Shared email layout wrapper for Raines Africa emails.

    Usage:
        @include('emails.partials.layout', [
            'preheader'     => 'Short preview text shown in inbox',
            'isInteractive' => true,   // true = admin@, false = no-reply@
        ])
        ... your content ...
        @include('emails.partials.layout-close')

    Or use the full component approach with $slot (see layout-full.blade.php).

    Variables used:
        $preheader      (string, optional)  — inbox preview text
        $isInteractive  (bool, optional)    — controls footer CTA. default false
--}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $emailTitle ?? config('app.name', 'Raines Africa') }}</title>
    @if(!empty($preheader))
    <span style="display:none;font-size:1px;color:#fefefe;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ $preheader }}
    </span>
    @endif
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            line-height: 1.6;
            padding: 32px 16px;
            -webkit-text-size-adjust: 100%;
        }

        /* ─── Wrapper ─── */
        .email-wrap {
            max-width: 620px;
            margin: 0 auto;
        }

        /* ─── Header / Logo bar ─── */
        .email-header {
            background: #1a1a2e;
            border-radius: 12px 12px 0 0;
            padding: 24px 32px;
            text-align: center;
        }
        .email-header img {
            max-height: 52px;
            width: auto;
            display: inline-block;
        }

        /* ─── Body card ─── */
        .email-body {
            background: #ffffff;
            padding: 36px 32px;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
        }

        /* ─── Heading strip ─── */
        .email-heading-strip {
            background: linear-gradient(135deg, #C0392B 0%, #922b21 100%);
            color: #fff;
            padding: 20px 32px 18px;
            margin: 0 0 28px;
            border-radius: 6px;
        }
        .email-heading-strip h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin: 0;
        }
        .email-heading-strip p {
            font-size: 13px;
            opacity: 0.88;
            margin: 4px 0 0;
        }

        /* ─── Typography ─── */
        .email-body h2 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 12px;
        }
        .email-body p {
            font-size: 15px;
            color: #374151;
            margin: 0 0 16px;
            line-height: 1.7;
        }
        .email-body p:last-child { margin-bottom: 0; }

        /* ─── Info card ─── */
        .info-card {
            background: #f9fafb;
            border-left: 4px solid #C0392B;
            border-radius: 6px;
            padding: 16px;
            margin: 20px 0;
            font-size: 14px;
        }
        .info-card .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-card .row:last-child { border-bottom: none; }
        .info-card .label { color: #6b7280; font-weight: 500; flex: 0 0 42%; }
        .info-card .value { color: #111827; font-weight: 500; flex: 0 0 56%; text-align: right; word-break: break-word; }

        /* ─── Summary table ─── */
        .summary-table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 16px 0; }
        .summary-table td { padding: 8px 4px; border-bottom: 1px dashed #e5e7eb; color: #374151; }
        .summary-table td:last-child { text-align: right; }
        .summary-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 15px; color: #111827; padding-top: 12px; }

        /* ─── Buttons ─── */
        .btn {
            display: inline-block;
            padding: 13px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #C0392B 0%, #922b21 100%);
            color: #ffffff !important;
        }
        .btn-secondary {
            background: #1a1a2e;
            color: #ffffff !important;
        }
        .btn-wrap { text-align: center; margin: 24px 0; }

        /* ─── Status badges ─── */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge-success  { background: #dcfce7; color: #166534; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-info     { background: #dbeafe; color: #1e40af; }

        /* ─── Highlight box ─── */
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: 14px 16px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }

        /* ─── Divider ─── */
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }

        /* ─── Footer ─── */
        .email-footer {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            padding: 24px 32px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .email-footer a { color: #C0392B; text-decoration: none; }
        .email-footer p { margin: 4px 0; }
        .email-footer .footer-brand {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        /* ─── Responsive ─── */
        @media (max-width: 640px) {
            body { padding: 16px 8px; }
            .email-header, .email-body, .email-footer { padding-left: 18px; padding-right: 18px; }
            .email-heading-strip { padding: 16px 18px; }
            .info-card .row { flex-direction: column; }
            .info-card .value { text-align: left; margin-top: 2px; }
        }
    </style>
</head>
<body>
<div class="email-wrap">

    {{-- ── Header ── --}}
    <div class="email-header">
        <img src="https://media.raines.africa/storage/uploads/2025/07/24/b35706e8-980f-4c6c-a87d-b0a24e6378fd.png"
             alt="{{ config('app.name', 'Raines Africa') }}" />
    </div>

    {{-- ── Body ── --}}
    <div class="email-body">
