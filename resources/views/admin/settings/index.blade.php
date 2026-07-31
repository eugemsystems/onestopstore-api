@extends('admin.layout')

@section('title', 'Settings Management')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
<style>
    .CodeMirror { height: 550px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; }
    .nav-tabs .nav-link { font-size: 0.875rem; padding: 0.5rem 0.9rem; }
    .nav-tabs .nav-link.active { font-weight: 600; }
    .settings-section-title {
        font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .08em; color: #6c757d; margin: 1.5rem 0 0.75rem;
        padding-bottom: 0.4rem; border-bottom: 1px solid #e9ecef;
    }
    .settings-section-title:first-child { margin-top: 0; }
    .form-label { font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; }
    .form-text { font-size: 0.78rem; }
    .tab-pane { padding-top: 1.25rem; }
    .payment-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    .payment-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
    .save-tab-btn { min-width: 130px; }
    .json-key { color: #0066cc; font-weight: bold; }
    .json-string { color: #dd1144; }
    .json-number { color: #009999; }
    .json-boolean { color: #990073; }
    .json-null { color: #999; }
    .badge-tab { font-size: 0.65rem; vertical-align: middle; }
    .mode-switch { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .mode-switch input[type=radio] { display: none; }
    .mode-switch label { cursor: pointer; padding: 0.35rem 0.85rem; border: 1px solid #dee2e6; border-radius: 20px; font-size: 0.82rem; transition: all .15s; }
    .mode-switch input[type=radio]:checked + label { background: #0d6efd; border-color: #0d6efd; color: #fff; }
</style>
@endpush

@section('content')

@php
    $v  = $setting->values ?? [];
    $g  = $v['general']           ?? [];
    $ac = $v['activation']        ?? [];
    $em = $v['email']             ?? [];
    $pm = $v['payment_methods']   ?? [];
    $wp = $v['wallet_points']     ?? [];
    $nl = $v['newsletter']        ?? [];
    $rf = $v['refund']            ?? [];
    $rc = $v['google_reCaptcha']  ?? [];
    $mn = $v['maintenance']       ?? [];
    $vc = $v['vendor_commissions']?? [];
    $dl = $v['delivery']          ?? [];
@endphp

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-gear-fill"></i> Settings Management</h2>
            <p class="text-muted mb-0">Configure all global application settings</p>
        </div>
        <a href="{{ route('admin.elasticsearch.reindex') }}" class="btn btn-success">
            <i class="bi bi-arrow-repeat"></i> Elasticsearch ReIndex
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Master form — one form, one hidden JSON field, multiple save buttons --}}
    <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <textarea name="values" id="masterValues" style="display:none">{{ json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>

        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs mb-0" id="settingsTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-general"><i class="bi bi-sliders"></i> General</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-features"><i class="bi bi-toggles"></i> Features</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-email"><i class="bi bi-envelope"></i> Email</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-payments"><i class="bi bi-credit-card"></i> Payments</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-delivery"><i class="bi bi-truck"></i> Delivery</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-wallet"><i class="bi bi-wallet2"></i> Wallet & Points</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-vendor"><i class="bi bi-shop"></i> Vendor</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-other"><i class="bi bi-three-dots"></i> Other</a></li>
            <li class="nav-item ms-auto"><a class="nav-link text-secondary" data-bs-toggle="tab" href="#tab-json"><i class="bi bi-code-square"></i> JSON Editor</a></li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white shadow-sm">

            {{-- ─── GENERAL TAB ─── --}}
            <div class="tab-pane fade show active" id="tab-general">
                <div class="row">
                    <div class="col-lg-8">

                        <p class="settings-section-title">Site Identity</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" data-json-path="general.site_name" value="{{ $g['site_name'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Site Title <span class="text-muted fw-normal">(browser tab)</span></label>
                                <input type="text" class="form-control" data-json-path="general.site_title" value="{{ $g['site_title'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Site Tagline</label>
                                <input type="text" class="form-control" data-json-path="general.site_tagline" value="{{ $g['site_tagline'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU Prefix</label>
                                <input type="text" class="form-control" data-json-path="general.product_sku_prefix" value="{{ $g['product_sku_prefix'] ?? '' }}" placeholder="e.g. RA">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Frontend URL</label>
                                <input type="url" class="form-control" data-json-path="general.site_url" value="{{ $g['site_url'] ?? '' }}" placeholder="https://yourdomain.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Copyright Text <span class="text-muted fw-normal">(HTML allowed)</span></label>
                                <textarea class="form-control" rows="2" data-json-path="general.copyright">{{ $g['copyright'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <p class="settings-section-title">Specials Button</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Button Label</label>
                                <input type="text" class="form-control" data-json-path="general.specials_button_label" value="{{ $g['specials_button_label'] ?? 'Specials' }}" placeholder="Specials">
                                <div class="form-text">Text shown on the button (desktop &amp; mobile).</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Button URL</label>
                                <input type="url" class="form-control" data-json-path="general.specials_button_url" value="{{ $g['specials_button_url'] ?? '' }}" placeholder="https://yourdomain.com/collections?on_sale=1">
                                <div class="form-text">Where the button links to. Leave blank to use the default collections page.</div>
                            </div>
                        </div>

                        <p class="settings-section-title">Localisation</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Timezone</label>
                                <select class="form-select" data-json-path="general.default_timezone">
                                    @php $tz = $g['default_timezone'] ?? 'Africa/Harare'; @endphp
                                    @foreach([
                                        'Africa/Harare','Africa/Johannesburg','Africa/Lusaka','Africa/Nairobi',
                                        'Africa/Lagos','Africa/Cairo','Africa/Accra','Africa/Dar_es_Salaam',
                                        'UTC','Europe/London','Europe/Paris','America/New_York','America/Los_Angeles',
                                        'Asia/Dubai','Asia/Kolkata','Australia/Sydney'
                                    ] as $timezone)
                                        <option value="{{ $timezone }}" @selected($tz === $timezone)>{{ $timezone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Admin Language Direction</label>
                                <select class="form-select" data-json-path="general.admin_site_language_direction">
                                    <option value="ltr" @selected(($g['admin_site_language_direction'] ?? 'ltr') === 'ltr')>LTR — Left to Right</option>
                                    <option value="rtl" @selected(($g['admin_site_language_direction'] ?? 'ltr') === 'rtl')>RTL — Right to Left</option>
                                </select>
                            </div>
                        </div>

                        <p class="settings-section-title">Order Thresholds</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Minimum Order Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control" data-json-path="general.min_order_amount" value="{{ $g['min_order_amount'] ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Free Shipping Threshold</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control" data-json-path="general.min_order_free_shipping" value="{{ $g['min_order_free_shipping'] ?? 0 }}">
                                    <span class="input-group-text">or above</span>
                                </div>
                            </div>
                        </div>

                        <p class="settings-section-title">Display Mode</p>
                        <div class="mb-3">
                            <label class="form-label d-block">Theme Mode</label>
                            <div class="mode-switch">
                                @foreach(['light-only' => '☀️ Light Only', 'dark-only' => '🌙 Dark Only', 'light' => '☀️ Light (default)', 'dark' => '🌙 Dark (default)'] as $val => $label)
                                    @php $checked = ($g['mode'] ?? 'light-only') === $val; @endphp
                                    <div>
                                        <input type="radio" name="_mode" id="mode_{{ $val }}" value="{{ $val }}" @checked($checked) data-json-path="general.mode" data-type="radio">
                                        <label for="mode_{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-4">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="mb-2"><i class="bi bi-info-circle"></i> Settings Info</h6>
                            <div class="small text-muted mb-1">Record ID: <strong>{{ $setting->id ?? 'N/A' }}</strong></div>
                            <div class="small text-muted mb-1">Updated: <strong>{{ $setting->updated_at?->format('M d, Y H:i') ?? 'N/A' }}</strong></div>
                            <div class="small text-muted">Created: <strong>{{ $setting->created_at?->format('M d, Y H:i') ?? 'N/A' }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save General Settings
                    </button>
                </div>
            </div>

            {{-- ─── FEATURES TAB ─── --}}
            <div class="tab-pane fade" id="tab-features">
                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="settings-section-title">Store Features</p>
                        @foreach([
                            ['coupon_enable',       'Coupon / Vouchers',      'Allow customers to use coupon codes at checkout'],
                            ['multivendor',         'Multi-Vendor',           'Enable multiple vendor storefronts'],
                            ['store_auto_approve',  'Auto-approve Stores',    'Automatically approve new vendor stores'],
                            ['product_auto_approve','Auto-approve Products',  'Automatically approve new vendor products'],
                        ] as [$key, $label, $desc])
                            <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                                <div>
                                    <div class="fw-medium">{{ $label }}</div>
                                    <div class="small text-muted">{{ $desc }}</div>
                                </div>
                                <div class="form-check form-switch ms-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        data-json-path="activation.{{ $key }}"
                                        id="ac_{{ $key }}"
                                        @checked(!empty($ac[$key]))>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <p class="settings-section-title">Loyalty & Payments</p>
                        @foreach([
                            ['point_enable',     'Loyalty Points',     'Enable points reward system for purchases'],
                            ['wallet_enable',    'Wallet',             'Allow customers to maintain a store wallet balance'],
                            ['stock_product_hide','Hide Out-of-Stock', 'Automatically hide products with zero stock from the storefront'],
                        ] as [$key, $label, $desc])
                            <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                                <div>
                                    <div class="fw-medium">{{ $label }}</div>
                                    <div class="small text-muted">{{ $desc }}</div>
                                </div>
                                <div class="form-check form-switch ms-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        data-json-path="activation.{{ $key }}"
                                        id="ac_{{ $key }}"
                                        @checked(!empty($ac[$key]))>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Feature Settings
                    </button>
                </div>
            </div>

            {{-- ─── EMAIL TAB ─── --}}
            <div class="tab-pane fade" id="tab-email">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Mail Driver (Mailer)</label>
                        <select class="form-select" data-json-path="email.mail_mailer">
                            @foreach(['smtp' => 'SMTP', 'mailgun' => 'Mailgun', 'sendmail' => 'Sendmail', 'log' => 'Log (dev only)'] as $val => $lbl)
                                <option value="{{ $val }}" @selected(($em['mail_mailer'] ?? 'smtp') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" data-json-path="email.mail_host" value="{{ $em['mail_host'] ?? '' }}" placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" data-json-path="email.mail_port" value="{{ $em['mail_port'] ?? 587 }}" placeholder="587">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" data-json-path="email.mail_username" value="{{ $em['mail_username'] ?? '' }}" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" data-json-path="email.mail_password" value="{{ $em['mail_password'] ?? '' }}" id="smtpPassword" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePw('smtpPassword',this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Encryption</label>
                        <select class="form-select" data-json-path="email.mail_encryption">
                            <option value="" @selected(($em['mail_encryption'] ?? '') === '')>None</option>
                            <option value="tls" @selected(($em['mail_encryption'] ?? '') === 'tls')>TLS</option>
                            <option value="ssl" @selected(($em['mail_encryption'] ?? '') === 'ssl')>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From Name</label>
                        <input type="text" class="form-control" data-json-path="email.mail_from_name" value="{{ $em['mail_from_name'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From Address</label>
                        <input type="email" class="form-control" data-json-path="email.mail_from_address" value="{{ $em['mail_from_address'] ?? '' }}">
                    </div>

                    <div class="col-12"><p class="settings-section-title">Mailgun (if using Mailgun driver)</p></div>
                    <div class="col-md-6">
                        <label class="form-label">Mailgun Domain</label>
                        <input type="text" class="form-control" data-json-path="email.mailgun_domain" value="{{ $em['mailgun_domain'] ?? '' }}" placeholder="mg.yourdomain.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mailgun Secret Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" data-json-path="email.mailgun_secret" value="{{ $em['mailgun_secret'] ?? '' }}" id="mailgunSecret" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePw('mailgunSecret',this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Email Settings
                    </button>
                </div>
            </div>

            {{-- ─── PAYMENTS TAB ─── --}}
            <div class="tab-pane fade" id="tab-payments">

                {{-- COD --}}
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Cash on Delivery</h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.cod.status" @checked(!empty($pm['cod']['status']))>
                            <label class="form-check-label">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Display Title</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.cod.title" value="{{ $pm['cod']['title'] ?? 'Cash On Delivery' }}">
                        </div>
                    </div>
                </div>

                {{-- Bank Transfer --}}
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h6 class="mb-0"><i class="bi bi-bank"></i> Bank Transfer</h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.bank_transfer.status" @checked(!empty($pm['bank_transfer']['status']))>
                            <label class="form-check-label">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Display Title</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.title" value="{{ $pm['bank_transfer']['title'] ?? 'Bank Transfer' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Name</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.account_name" value="{{ $pm['bank_transfer']['account_name'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.bank_name" value="{{ $pm['bank_transfer']['bank_name'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.account_number" value="{{ $pm['bank_transfer']['account_number'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.branch" value="{{ $pm['bank_transfer']['branch'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">SWIFT / BIC</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.swift" value="{{ $pm['bank_transfer']['swift'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference Prefix</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.bank_transfer.reference_prefix" value="{{ $pm['bank_transfer']['reference_prefix'] ?? 'ORDER' }}">
                        </div>
                    </div>
                </div>

                {{-- PayPal --}}
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h6 class="mb-0"><i class="bi bi-paypal"></i> PayPal</h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.paypal.status" @checked(!empty($pm['paypal']['status']))>
                            <label class="form-check-label">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Display Title</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.paypal.title" value="{{ $pm['paypal']['title'] ?? 'PayPal' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client ID</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.paypal.client_id" value="{{ $pm['paypal']['client_id'] ?? '' }}" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Secret</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" data-json-path="payment_methods.paypal.client_secret" value="{{ $pm['paypal']['client_secret'] ?? '' }}" id="paypalSecret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('paypalSecret',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.paypal.sandbox_mode" id="paypalSandbox" @checked(!empty($pm['paypal']['sandbox_mode']))>
                                <label class="form-check-label" for="paypalSandbox">Sandbox / Test Mode</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pese Pay --}}
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h6 class="mb-0"><i class="bi bi-phone"></i> Pese Pay</h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.pese.status" @checked(!empty($pm['pese']['status']))>
                            <label class="form-check-label">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Display Title</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.pese.title" value="{{ $pm['pese']['title'] ?? 'Pese Pay' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.pese.key" value="{{ $pm['pese']['key'] ?? '' }}" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">API Secret</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" data-json-path="payment_methods.pese.secret" value="{{ $pm['pese']['secret'] ?? '' }}" id="peseSecret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('peseSecret',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payfast --}}
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h6 class="mb-0"><i class="bi bi-shield-check"></i> Payfast</h6>
                        <div class="form-check form-switch mb-0">
                            @php $pfStatus = $pm['payfast']['status'] ?? false; $pfEnabled = $pfStatus === true || $pfStatus === ['on'] || $pfStatus === 'on'; @endphp
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.payfast.status" id="payfastStatus" @checked($pfEnabled)>
                            <label class="form-check-label">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Display Title</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.payfast.title" value="{{ $pm['payfast']['title'] ?? 'Payfast' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Merchant ID</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.payfast.merchant_id" value="{{ $pm['payfast']['merchant_id'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Merchant Key</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="payment_methods.payfast.merchant_key" value="{{ $pm['payfast']['merchant_key'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passphrase</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" data-json-path="payment_methods.payfast.passphrase" value="{{ $pm['payfast']['passphrase'] ?? '' }}" id="payfastPass">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('payfastPass',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-2">
                                @php $pfSandbox = $pm['payfast']['sandbox_mode'] ?? false; $pfSandboxOn = $pfSandbox === true || $pfSandbox === ['on'] || $pfSandbox === 'on'; @endphp
                                <input class="form-check-input" type="checkbox" role="switch" data-json-path="payment_methods.payfast.sandbox_mode" id="payfastSandbox" @checked($pfSandboxOn)>
                                <label class="form-check-label" for="payfastSandbox">Sandbox / Test Mode</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Payment Settings
                    </button>
                </div>
            </div>

            {{-- ─── DELIVERY TAB ─── --}}
            <div class="tab-pane fade" id="tab-delivery">
                <div class="alert alert-info d-flex gap-2">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        The delivery section has complex nested data (shipping options, same-day intervals).
                        Use the <strong>JSON Editor tab</strong> to modify those nested arrays.
                        The fields below cover the most commonly changed delivery settings.
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Default Delivery Title</label>
                        <input type="text" class="form-control" data-json-path="delivery.default.title" value="{{ $dl['default']['title'] ?? 'Standard Delivery' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default Delivery Description</label>
                        <input type="text" class="form-control" data-json-path="delivery.default.description" value="{{ $dl['default']['description'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Default Delivery Option ID</label>
                        <input type="number" class="form-control" data-json-path="delivery.default_delivery" value="{{ $dl['default_delivery'] ?? 1 }}">
                        <div class="form-text">Index (1-based) of the default shipping option</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="delivery.same_day_delivery" id="sameDayToggle" @checked(!empty($dl['same_day_delivery']))>
                            <label class="form-check-label fw-medium" for="sameDayToggle">Enable Same-Day Delivery</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Same-Day Delivery Title</label>
                        <input type="text" class="form-control" data-json-path="delivery.same_day.title" value="{{ $dl['same_day']['title'] ?? 'Express Delivery' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Same-Day Delivery Description</label>
                        <input type="text" class="form-control" data-json-path="delivery.same_day.description" value="{{ $dl['same_day']['description'] ?? '' }}">
                    </div>
                </div>

                <p class="settings-section-title mt-3">Shipping Options Preview <span class="text-muted fw-normal">(edit in JSON Editor to add/remove options)</span></p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr><th>#</th><th>Title</th><th>Description</th><th>Price</th></tr>
                        </thead>
                        <tbody>
                            @forelse($dl['shipping_options'] ?? [] as $i => $opt)
                                <tr>
                                    <td class="text-muted small">{{ $i + 1 }}</td>
                                    <td>{{ $opt['title'] ?? '' }}</td>
                                    <td class="text-muted small">{{ $opt['description'] ?? '' }}</td>
                                    <td>{{ $opt['price'] == 0 ? 'Free' : '$'.$opt['price'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No shipping options defined</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Delivery Settings
                    </button>
                </div>
            </div>

            {{-- ─── WALLET & POINTS TAB ─── --}}
            <div class="tab-pane fade" id="tab-wallet">
                <div class="row g-3">
                    <div class="col-12"><p class="settings-section-title">Loyalty Points Configuration</p></div>
                    <div class="col-md-6">
                        <label class="form-label">Sign-up Bonus Points</label>
                        <input type="number" min="0" class="form-control" data-json-path="wallet_points.signup_points" value="{{ $wp['signup_points'] ?? 0 }}">
                        <div class="form-text">Points awarded to new customers on registration</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Points per Order (earn rate)</label>
                        <input type="number" min="0" class="form-control" data-json-path="wallet_points.reward_per_order_amount" value="{{ $wp['reward_per_order_amount'] ?? 0 }}">
                        <div class="form-text">Points earned per order</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Minimum Order Amount to Earn Points</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control" data-json-path="wallet_points.min_per_order_amount" value="{{ $wp['min_per_order_amount'] ?? 0 }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Point to Currency Ratio</label>
                        <div class="input-group">
                            <span class="input-group-text">1 point =</span>
                            <input type="number" step="0.001" min="0" class="form-control" data-json-path="wallet_points.point_currency_ratio" value="{{ $wp['point_currency_ratio'] ?? 0 }}">
                            <span class="input-group-text">currency units</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Wallet Settings
                    </button>
                </div>
            </div>

            {{-- ─── VENDOR TAB ─── --}}
            <div class="tab-pane fade" id="tab-vendor">
                <div class="row g-3">
                    <div class="col-12"><p class="settings-section-title">Vendor Commission</p></div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="vendor_commissions.status" id="vcStatus" @checked(!empty($vc['status']))>
                            <label class="form-check-label fw-medium" for="vcStatus">Enable Commission System</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="vendor_commissions.is_category_based_commission" id="vcCatBased" @checked(!empty($vc['is_category_based_commission']))>
                            <label class="form-check-label fw-medium" for="vcCatBased">Category-based Commission</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Default Commission Rate (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" class="form-control" data-json-path="vendor_commissions.default_commission_rate" value="{{ $vc['default_commission_rate'] ?? 10 }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Withdrawal Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control" data-json-path="vendor_commissions.min_withdraw_amount" value="{{ $vc['min_withdraw_amount'] ?? 500 }}">
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Vendor Settings
                    </button>
                </div>
            </div>

            {{-- ─── OTHER TAB ─── --}}
            <div class="tab-pane fade" id="tab-other">
                <div class="row g-4">

                    {{-- Maintenance --}}
                    <div class="col-md-6">
                        <p class="settings-section-title">Maintenance Mode</p>
                        <div class="d-flex align-items-center mb-3">
                            <div class="form-check form-switch me-3">
                                <input class="form-check-input" type="checkbox" role="switch" data-json-path="maintenance.maintenance_mode" id="maintMode" @checked(!empty($mn['maintenance_mode']))>
                            </div>
                            <label for="maintMode" class="fw-medium mb-0">
                                Maintenance Mode
                                @if(!empty($mn['maintenance_mode']))
                                    <span class="badge bg-danger ms-1">ACTIVE</span>
                                @endif
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Title</label>
                            <input type="text" class="form-control" data-json-path="maintenance.title" value="{{ $mn['title'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Description</label>
                            <textarea class="form-control" rows="3" data-json-path="maintenance.description">{{ $mn['description'] ?? '' }}</textarea>
                        </div>
                    </div>

                    {{-- Refund --}}
                    <div class="col-md-6">
                        <p class="settings-section-title">Refund Policy</p>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="refund.status" id="rfStatus" @checked(!empty($rf['status']))>
                            <label class="form-check-label fw-medium" for="rfStatus">Allow Refunds</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Refundable Window (days)</label>
                            <div class="input-group" style="max-width:200px">
                                <input type="number" min="1" class="form-control" data-json-path="refund.refundable_days" value="{{ $rf['refundable_days'] ?? 7 }}">
                                <span class="input-group-text">days</span>
                            </div>
                            <div class="form-text">How many days after purchase a refund can be requested</div>
                        </div>

                        <p class="settings-section-title">Newsletter</p>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="newsletter.status" id="nlStatus" @checked(!empty($nl['status']))>
                            <label class="form-check-label fw-medium" for="nlStatus">Enable Newsletter</label>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mailchimp API Key</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="newsletter.mailchip_api_key" value="{{ $nl['mailchip_api_key'] ?? '' }}" autocomplete="off">
                        </div>
                        <div>
                            <label class="form-label">Mailchimp List / Audience ID</label>
                            <input type="text" class="form-control form-control-sm" data-json-path="newsletter.mailchip_list_id" value="{{ $nl['mailchip_list_id'] ?? '' }}">
                        </div>
                    </div>

                    {{-- reCAPTCHA --}}
                    <div class="col-md-6">
                        <p class="settings-section-title">Google reCAPTCHA</p>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" data-json-path="google_reCaptcha.status" id="rcStatus" @checked(!empty($rc['status']))>
                            <label class="form-check-label fw-medium" for="rcStatus">Enable reCAPTCHA</label>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Site Key</label>
                            <input type="text" class="form-control" data-json-path="google_reCaptcha.site_key" value="{{ $rc['site_key'] ?? '' }}">
                        </div>
                        <div>
                            <label class="form-label">Secret Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" data-json-path="google_reCaptcha.secret" value="{{ $rc['secret'] ?? '' }}" id="rcSecret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePw('rcSecret',this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveSettings()">
                        <i class="bi bi-save"></i> Save Other Settings
                    </button>
                </div>
            </div>

            {{-- ─── JSON EDITOR TAB ─── --}}
            <div class="tab-pane fade" id="tab-json">
                <div class="alert alert-warning d-flex gap-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Advanced use only.</strong> Edit the raw JSON carefully.
                        Invalid JSON will prevent saving. All settings from all tabs are stored here.
                        Changes saved from other tabs are reflected here.
                    </div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="formatJsonBtn">
                        <i class="bi bi-code-square"></i> Format / Prettify
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetJsonBtn">
                        <i class="bi bi-arrow-clockwise"></i> Reset to Last Saved
                    </button>
                </div>
                <textarea id="jsonEditorArea" class="form-control font-monospace">{{ json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary save-tab-btn" onclick="saveFromJsonEditor()">
                        <i class="bi bi-save"></i> Save JSON
                    </button>
                    <span class="text-muted small ms-2" id="jsonStatus"></span>
                </div>
            </div>

        </div>{{-- /tab-content --}}
    </form>

</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── CodeMirror for JSON editor tab ──────────────────────────────────
    const jsonArea = document.getElementById('jsonEditorArea');
    const originalJson = jsonArea.value;

    const editor = CodeMirror.fromTextArea(jsonArea, {
        mode: 'application/json',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 2,
        tabSize: 2,
        matchBrackets: true,
        autoCloseBrackets: true,
    });

    // Sync CodeMirror → masterValues whenever its content changes
    editor.on('change', function () {
        syncJsonEditorToMaster();
    });

    // When switching TO the JSON tab, refresh editor with latest master JSON
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            if (e.target.getAttribute('href') === '#tab-json') {
                try {
                    const pretty = JSON.stringify(JSON.parse(document.getElementById('masterValues').value), null, 2);
                    editor.setValue(pretty);
                } catch (err) {
                    // masterValues may be mid-edit; leave as-is
                }
                setTimeout(() => editor.refresh(), 50);
            }
        });
    });

    // ── Format button ──────────────────────────────────────────────────
    document.getElementById('formatJsonBtn').addEventListener('click', function () {
        try {
            const pretty = JSON.stringify(JSON.parse(editor.getValue()), null, 2);
            editor.setValue(pretty);
            showStatus('Formatted ✓', 'success');
        } catch (err) {
            showStatus('Invalid JSON: ' + err.message, 'danger');
        }
    });

    // ── Reset button ───────────────────────────────────────────────────
    document.getElementById('resetJsonBtn').addEventListener('click', async function () {
        const result = await Swal.fire({
            title: 'Reset JSON?',
            text: 'This will restore the JSON to the last saved state and discard unsaved changes.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: SwalConfig.confirmColor,
            cancelButtonColor: SwalConfig.cancelColor,
        });
        if (result.isConfirmed) {
            editor.setValue(originalJson);
            document.getElementById('masterValues').value = originalJson;
            showStatus('Reset to last saved ✓', 'info');
        }
    });

    // ── Sync helpers ────────────────────────────────────────────────────
    function syncJsonEditorToMaster() {
        try {
            JSON.parse(editor.getValue()); // validate
            document.getElementById('masterValues').value = editor.getValue();
            document.getElementById('jsonStatus').textContent = '';
            document.getElementById('jsonStatus').className = '';
        } catch (err) {
            document.getElementById('jsonStatus').textContent = '⚠ Invalid JSON';
            document.getElementById('jsonStatus').className = 'text-danger small ms-2';
        }
    }

    function showStatus(msg, type) {
        const el = document.getElementById('jsonStatus');
        el.textContent = msg;
        el.className = 'text-' + type + ' small ms-2';
        setTimeout(() => { el.textContent = ''; el.className = ''; }, 4000);
    }

    // ── Expose globals ───────────────────────────────────────────────────
    window._cmEditor = editor;
});

// ── Collect all form inputs and build / submit JSON ─────────────────────────
function saveSettings() {
    try {
        const master = JSON.parse(document.getElementById('masterValues').value);

        // Walk every element with a data-json-path attribute
        document.querySelectorAll('[data-json-path]').forEach(function (el) {
            // Skip radio inputs that aren't checked
            if (el.type === 'radio' && !el.checked) return;

            const path = el.getAttribute('data-json-path').split('.');
            let obj = master;

            // Navigate / create nested path
            for (let i = 0; i < path.length - 1; i++) {
                if (obj[path[i]] === undefined || obj[path[i]] === null || typeof obj[path[i]] !== 'object') {
                    obj[path[i]] = {};
                }
                obj = obj[path[i]];
            }

            const key = path[path.length - 1];

            if (el.type === 'checkbox') {
                obj[key] = el.checked;
            } else if (el.type === 'number') {
                obj[key] = el.value === '' ? null : Number(el.value);
            } else if (el.type === 'radio') {
                obj[key] = el.value;
            } else {
                // text, email, url, textarea, select, password
                obj[key] = el.value;
            }
        });

        // Special handling: payfast status/sandbox_mode come as bool but may need array ['on']
        // We save as boolean — the reading code in SettingSeeder historically used ['on'] but the
        // controller reads it loosely, so boolean is fine here.

        document.getElementById('masterValues').value = JSON.stringify(master);
        document.getElementById('settingsForm').submit();

    } catch (err) {
        alert('Error building settings JSON: ' + err.message + '\n\nPlease check the JSON Editor tab for any syntax issues.');
    }
}

// ── Save directly from JSON editor (validate first) ──────────────────────────
function saveFromJsonEditor() {
    const editorVal = window._cmEditor ? window._cmEditor.getValue() : document.getElementById('jsonEditorArea').value;
    try {
        JSON.parse(editorVal); // validate
        document.getElementById('masterValues').value = editorVal;
        document.getElementById('settingsForm').submit();
    } catch (err) {
        Swal.fire({
            title: 'Invalid JSON',
            text: 'Fix the JSON syntax before saving: ' + err.message,
            icon: 'error',
        });
    }
}

// ── Toggle password visibility ───────────────────────────────────────────────
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>
@endpush
