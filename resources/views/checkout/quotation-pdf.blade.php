@php
// Map currency to market/country addresses (copied verbatim from
// resources/views/admin/invoices-quotations/templates/quotation.blade.php)
$companyAddresses = [
    'USD' => [ // Zimbabwe (USD)
        'address' => 'Shop No. 6 Rhodesville Shops, No 32 Rhodesville Avenue Greendale, Harare',
        'email' => 'admin@onestopstore.co.zw',
        'phones' => ['+263 77 941 1028', '+263 71 716 8255']
    ],
    'ZMW' => [ // Zambia
        'address' => 'Niyati Plaza, Kalingalinga Area, 35235 Alick Nkhata Rd, Lusaka, Zambia',
        'email' => 'admin@onestopstore.co.zw',
        'phones' => ['+260 77 726 5389', '+260 76 591 4363']
    ],
    'ZAR' => [ // South Africa
        'address' => '7 Nel Street Roodepoort 1724 South Africa',
        'email' => 'admin@onestopstore.co.zw',
        'phones' => ['+27 XX XXX XXXX']
    ],
];

// Get company info based on currency
$companyInfo = $companyAddresses[$currency] ?? $companyAddresses['USD'];

$totals = $result['total'];
$validUntil = $generatedAt->copy()->addDays(7);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation - {{ $generatedAt->format('Ymd-His') }}</title>
    <style>
        /* =========================================================
           DOMPDF SAFE BASE RESET
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Page margins */
        @page {
            margin: 20px 20px 75px 20px; /* bottom reserved for footer */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.25;
        }

        .validity-notice{
            clear: both;
            margin-top: 25px;
            padding: 20px;
            background: #f5f5f5;
            border-left: 4px solid #e70810;
            margin-left: 20px;
            margin-right: 20px;
        }

        /* =========================================================
           MAIN CONTAINER
        ========================================================= */
        .container{
            width: 100%;
            padding: 0;
            margin: 0;
        }

        /* =========================================================
           HEADER — FULL WIDTH BACKGROUND + ALIGNED CONTENT
        ========================================================= */
        .header{
            background-color:#11529c !important;
            color:#fff !important;

            margin-left:-20px;
            margin-right:-20px;

            padding: 15px 20px;

            border-bottom:5px solid #e70810;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .logo-section {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .logo-left,
        .logo-right {
            display: table-cell;
            vertical-align: middle;
        }

        .logo-left {
            width: 55%;
            text-align: left;
            padding-left: 5px;
        }

        .logo-right {
            width: 45%;
            text-align: right;
            padding-right: 5px;
        }

        .document-title {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .document-number {
            font-size: 14px;
            opacity: 0.9;
        }

        /* =========================================================
           CONTENT GUTTER
        ========================================================= */
        .content-gutter{
            margin-left: 20px;
            margin-right: 20px;
        }

        .info-section,
        table,
        .totals-section,
        .banking-details,
        .notes-section,
        .terms-block{
            margin-left: 20px;
            margin-right: 20px;
        }

        /* =========================================================
           INFO SECTIONS
        ========================================================= */
        .info-section {
            display: table;
            width: 100%;
            margin-top: 15px;
            margin-left: 0px;
        }

        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            color: #11529c;
            margin-bottom: 8px;
            border-bottom: 2px solid #e70810;
            padding-bottom: 4px;
        }

        .info-line {
            margin-bottom: 4px;
        }

        .label {
            font-weight: bold;
            color: #666;
        }

        /* =========================================================
           TABLE (ITEMS)
        ========================================================= */
        table {
            width: 98%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 10px;
            margin-left: 10px;
        }

        thead {
            display: table-header-group;
        }

        th {
            background: #11529c !important;
            color: #ffffff !important;
            padding: 6px;
            font-weight: bold;
            text-align: left;
            border-bottom: 3px solid #e70810;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        td {
            padding: 5px 6px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        tr:nth-child(even) td {
            background: #f9f9f9;
        }

        /* =========================================================
           ALIGNMENT HELPERS
        ========================================================= */
        .text-right,
        th.text-right {
            text-align: right !important;
        }

        .text-center,
        th.text-center {
            text-align: center !important;
        }

        /* =========================================================
           TOTALS SECTION
        ========================================================= */
        .totals-section {
            margin-top: 10px;
            float: right;
            width: 300px;
            margin-right: 12px;
        }

        .totals-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #ddd;
            padding: 3px 0;
        }

        .totals-row span {
            display: table-cell;
        }

        .totals-row span:last-child {
            text-align: right;
        }

        .totals-row.final {
            background: #11529c !important;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: bold;
            padding: 6px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* =========================================================
           BANKING DETAILS
        ========================================================= */
        .banking-details {
            clear: both;
            margin-top: 25px;
            padding: 12px;
            background: #f5f5f5;
            border-left: 4px solid #e70810;
        }

        .banking-title {
            font-weight: bold;
            font-size: 13px;
            color: #11529c;
            margin-bottom: 8px;
        }

        /* =========================================================
           NOTES & TERMS
        ========================================================= */
        .notes-section {
            margin-top: 20px;
            padding: 10px;
            background: #fffbe6;
            border-left: 4px solid #e70810;
        }

        /* =========================================================
           FOOTER — ALWAYS BOTTOM
        ========================================================= */
        .footer {
            position: fixed;
            left: 20px;
            right: 20px;
            bottom: 20px;

            text-align: center;
            font-size: 9px;
            color: #666;
            padding-top: 8px;
            border-top: 2px solid #ddd;
            background: #ffffff;
        }

        .footer p {
            margin: 0 !important;
        }

        .footer-accent {
            color: #e70810;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                <div class="logo-left">
                    <img src="https://media.onestopstore.co.zw/storage/uploads/2025/08/29/2a05a383-cf06-4d12-b728-ec37103526c5.png"
                         alt="Raines Logo" style="height: 50px; margin-bottom: 5px;">
                </div>
                <div class="logo-right">
                    <div class="document-title">QUOTATION</div>
                    <div class="document-number">Generated {{ $generatedAt->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-column">
                <div class="section-title">PREPARED FOR</div>
                <div class="info-line"><strong>{{ $user->name ?? 'Valued Customer' }}</strong></div>
                @if($user->email ?? null)<div class="info-line">{{ $user->email }}</div>@endif
                @if($user->phone ?? null)<div class="info-line">{{ $user->phone }}</div>@endif
            </div>
            <div class="info-column" style="text-align: right;">
                <div class="section-title">QUOTATION DETAILS</div>
                <div class="info-line"><span class="label">Generated:</span> {{ $generatedAt->format('M d, Y H:i') }}</div>
                <div class="info-line"><span class="label">Valid Until:</span> <strong style="color: #e70810;">{{ $validUntil->format('M d, Y') }}</strong></div>
                <div class="info-line"><span class="label">Currency:</span> {{ $currency }}</div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Image</th>
                    <th>Description</th>
                    <th class="text-center" style="width: 60px;">Qty</th>
                    <th class="text-right" style="width: 100px;">Unit Price</th>
                    <th class="text-right" style="width: 100px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lineItems as $item)
                <tr>
                    <td>
                        @if(!empty($item['product_image_url']) && !str_ends_with(strtolower($item['product_image_url']), '.webp'))
                            <img src="{{ $item['product_image_url'] }}" alt="{{ $item['product_name'] }}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 3px;">
                        @else
                            <div style="width: 45px; height: 45px; background: #f0f0f0; border-radius: 3px; display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:9px; color:#aaa; text-align:center; line-height:1.2;">No<br>Image</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $item['product_name'] ?? 'Product' }}</strong>
                        @if(!empty($item['product_sku']))
                            <br><small style="color: #666;">SKU: {{ $item['product_sku'] }}</small>
                        @endif
                        @if(!empty($item['variation_display_name']))
                            <br><small style="color: #666;">{{ $item['variation_display_name'] }}</small>
                        @endif
                        @if(!empty($item['estimated_delivery_text']))
                            <br><small style="color: #999;">Est. delivery: {{ $item['estimated_delivery_text'] }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item['quantity'], 0) }}</td>
                    <td class="text-right">{{ $currency }} {{ number_format($item['single_price'], 2) }}</td>
                    <td class="text-right"><strong>{{ $currency }} {{ number_format($item['subtotal'], 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span><strong>{{ $currency }} {{ number_format($totals['sub_total'], 2) }}</strong></span>
            </div>
            @if(($totals['coupon_total_discount'] ?? 0) > 0)
            <div class="totals-row">
                <span>Discount:</span>
                <span style="color: #e70810;"><strong>-{{ $currency }} {{ number_format($totals['coupon_total_discount'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['tax_total'] ?? 0) > 0)
            <div class="totals-row">
                <span>Tax:</span>
                <span><strong>{{ $currency }} {{ number_format($totals['tax_total'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['shipping_total'] ?? 0) > 0)
            <div class="totals-row">
                <span>Shipping Fee:</span>
                <span style="color: #0066cc;"><strong>{{ $currency }} {{ number_format($totals['shipping_total'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['fast_shipping_total'] ?? 0) > 0)
            <div class="totals-row">
                <span>Expedited Shipping:</span>
                <span style="color: #0066cc;"><strong>{{ $currency }} {{ number_format($totals['fast_shipping_total'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['delivery_price'] ?? 0) > 0)
            <div class="totals-row">
                <span>Delivery Fee:</span>
                <span style="color: #0066cc;"><strong>{{ $currency }} {{ number_format($totals['delivery_price'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['convert_point_amount'] ?? 0) < 0)
            <div class="totals-row">
                <span>Points Applied:</span>
                <span style="color: #e70810;"><strong>{{ $currency }} {{ number_format($totals['convert_point_amount'], 2) }}</strong></span>
            </div>
            @endif
            @if(($totals['convert_wallet_balance'] ?? 0) < 0)
            <div class="totals-row">
                <span>Wallet Applied:</span>
                <span style="color: #e70810;"><strong>{{ $currency }} {{ number_format($totals['convert_wallet_balance'], 2) }}</strong></span>
            </div>
            @endif
            <div class="totals-row final">
                <span>TOTAL:</span>
                <span>{{ $currency }} {{ number_format($totals['total'], 2) }}</span>
            </div>
        </div>

        <p class="validity-notice">
            <strong style="color: red; padding-left: 12px;">Important:</strong> This quotation is valid until <strong style="color: #e70810;">{{ $validUntil->format('F d, Y') }}</strong>.
            Prices are indicative and subject to change based on stock availability and current pricing at the time of order; this is not a binding order or invoice.
        </p>

        @if(in_array($currency, ['USD', 'ZWL']))
        <div class="banking-details">
            <div class="banking-title">BANKING DETAILS - ZIMBABWE</div>
            <div class="banking-info">
                <div><span class="label">Bank:</span> CBZ - Commercial Bank of Zimbabwe</div>
                <div><span class="label">Account Number:</span> 12626684910022</div>
                <div><span class="label">Account Holder:</span> Raines Technologies (PTY) LTD</div>
                <div><span class="label">Account Type:</span> Cheque</div>
                <div><span class="label">Branch:</span> Harare</div>
            </div>
        </div>
        @endif

        @if($currency === 'ZMW')
        <div class="banking-details">
            <div class="banking-title">BANKING DETAILS - ZAMBIA</div>
            <div class="banking-info">
                <div><span class="label">Bank:</span> First National Bank</div>
                <div><span class="label">Account Number:</span> 63100161916 (ZMW)</div>
                <div><span class="label">Account Name:</span> Galaxy Raines Tech</div>
                <div><span class="label">Branch Name and No:</span> Commercial Suite 260001</div>
                <div><span class="label">Swift Code:</span> FIRNZMLX</div>
            </div>
        </div>
        @endif

        <div class="footer">
            <p><strong>Raines Technologies (PTY) LTD</strong></p>
            <p>{{ $companyInfo['address'] }}</p>
            <p>Email: <span class="footer-accent">{{ $companyInfo['email'] }}</span> | Web: <span class="footer-accent">www.onestopstore.co.zw</span></p>
            <p>Phone: @foreach($companyInfo['phones'] as $phone){{ $phone }}@if(!$loop->last) | @endif @endforeach</p>
            <p style="margin-top: 10px; font-size: 9px;">This quotation is computer-generated and is for informational purposes only. It does not constitute a binding order.</p>
        </div>
    </div>
</body>
</html>
