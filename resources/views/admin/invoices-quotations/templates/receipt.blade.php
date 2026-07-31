@php
// Map currency to market/country addresses
$companyAddresses = [
    'USD' => [ // Zimbabwe (USD)
        'address' => 'Shop No. 6 Rhodesville Shops, No 32 Rhodesville Avenue Greendale, Harare',
        'email' => 'admin@raines.africa',
        'phones' => ['+263 77 941 1028', '+263 71 716 8255']
    ],
    'ZMW' => [ // Zambia
        'address' => 'Niyati Plaza, Kalingalinga Area, 35235 Alick Nkhata Rd, Lusaka, Zambia',
        'email' => 'admin@raines.africa',
        'phones' => ['+260 77 726 5389', '+260 76 591 4363']
    ],
    'ZAR' => [ // South Africa
        'address' => '7 Nel Street Roodepoort 1724 South Africa',
        'email' => 'admin@raines.africa',
        'phones' => ['+27 XX XXX XXXX']
    ],
];

// Get company info based on currency
$companyInfo = $companyAddresses[$document->currency_code] ?? $companyAddresses['USD'];

@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $document->document_number }}</title>
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
            padding: 0;  /* keep 0 so we control gutters explicitly */
            margin: 0;
        }

        /* =========================================================
           HEADER — FULL WIDTH BACKGROUND + ALIGNED CONTENT
        ========================================================= */
        .header{
            background-color:#11529c !important;
            color:#fff !important;

            /* Full-bleed background */
            margin-left:-20px;   /* matches @page left margin */
            margin-right:-20px;  /* matches @page right margin */

            /* IMPORTANT: inner content gutter must match the rest (20px) */
            padding: 15px 20px;

            border-bottom:5px solid #e70810;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* DOMPDF-friendly header layout */
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
           CONTENT GUTTER (APPLIES TO EVERYTHING EXCEPT FULL-BLEED HEADER)
        ========================================================= */
        .content-gutter{
            margin-left: 20px;
            margin-right: 20px;
        }

        /* Apply gutter to your existing blocks (no HTML changes needed) */
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
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <div class="logo-left">
                    <img src="https://media.raines.africa/storage/uploads/2025/08/29/2a05a383-cf06-4d12-b728-ec37103526c5.png"
                         alt="Raines Logo" style="height: 50px; margin-bottom: 5px;">
                </div>
                <div class="logo-right">
                    <div class="document-title">RECEIPT</div>
                    <div class="document-number">#{{ $document->document_number }}</div>
                </div>
            </div>
        </div>

        <!-- Information Section -->
        <div class="info-section">
            <div class="info-column">
                <div class="section-title">BILL TO</div>
                <div class="info-line"><strong>{{ $document->customer_name }}</strong></div>
                @if($document->customer_email)
                <div class="info-line">{{ $document->customer_email }}</div>
                @endif
                @if($document->customer_phone)
                <div class="info-line">{{ $document->customer_phone }}</div>
                @endif
                @if($document->customer_address)
                <div class="info-line">{{ $document->customer_address }}</div>
                @endif
            </div>
            <div class="info-column" style="text-align: right;">
                <div class="section-title">INVOICE DETAILS</div>
                <div class="info-line">
                    <span class="label">Issue Date:</span> {{ $document->issue_date->format('M d, Y') }}
                </div>
                @if($document->due_date)
                <div class="info-line">
                    <span class="label">Due Date:</span> {{ $document->due_date->format('M d, Y') }}
                </div>
                @endif
                <div class="info-line">
                    <span class="label">Currency:</span> {{ $document->currency_code }}
                </div>
                <div class="info-line">
                    <span class="label">Status:</span> <strong style="color: #e70810;">{{ strtoupper($document->status) }}</strong>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Image</th>
                    <th>Description</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-right" style="width: 100px;">Unit Price</th>
                    <th class="text-right" style="width: 100px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->items as $item)
                <tr>
                    <td>
                        @if($item->image_url && !str_ends_with(strtolower($item->image_url), '.webp'))
                            <img src="{{ $item->image_url }}" alt="{{ $item->product_name }}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 3px;">
                        @else
                            <div style="width: 45px; height: 45px; background: #f0f0f0; border-radius: 3px; display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:9px; color:#aaa; text-align:center; line-height:1.2;">No<br>Image</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->sku)
                            <br><small style="color: #666;">SKU: {{ $item->sku }}</small>
                        @endif
                        @if($item->description)
                            <br><small style="color: #666;">{{ $item->description }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ $document->currency_code }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right"><strong>{{ $document->currency_code }} {{ number_format($item->subtotal, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span><strong>{{ $document->currency_code }} {{ number_format($document->subtotal, 2) }}</strong></span>
            </div>
            @if($document->discount_amount > 0)
            <div class="totals-row">
                <span>Discount
                    @if($document->discount_type == 'percentage')
                        ({{ number_format($document->discount_value, 2) }}%)
                    @endif
                :</span>
                <span style="color: #e70810;"><strong>-{{ $document->currency_code }} {{ number_format($document->discount_amount, 2) }}</strong></span>
            </div>
            @endif
            @if($document->include_vat && $document->vat_amount > 0)
            <div class="totals-row">
                <span>VAT ({{ number_format($document->vat_percentage, 2) }}%):</span>
                <span><strong>{{ $document->currency_code }} {{ number_format($document->vat_amount, 2) }}</strong></span>
            </div>
            @endif
            @if($document->shipping_total > 0)
            <div class="totals-row">
                <span>Shipping Fee:</span>
                <span style="color: #0066cc;"><strong>{{ $document->currency_code }} {{ number_format($document->shipping_total, 2) }}</strong></span>
            </div>
            @endif
            @if($document->delivery_price > 0)
            <div class="totals-row">
                <span>Delivery Fee
                    @if($document->delivery_method)
                        <br><small style="font-size: 9px; color: #666;">({{ $document->delivery_method }})</small>
                    @endif
                :</span>
                <span style="color: #0066cc;"><strong>{{ $document->currency_code }} {{ number_format($document->delivery_price, 2) }}</strong></span>
            </div>
            @endif
            <div class="totals-row final">
                <span>TOTAL:</span>
                <span>{{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Banking Details -->
        @if(in_array($document->currency_code, ['USD', 'ZWL']))
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

        @if($document->currency_code === 'ZMW')
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

        <!-- Notes -->
        @if($document->notes)
        <div class="notes-section">
            <div style="font-weight: bold; margin-bottom: 5px;">Notes:</div>
            <div>{{ $document->notes }}</div>
        </div>
        @endif

        <!-- Terms & Conditions -->
        @if($document->terms_conditions)
        <div style="margin-top: 20px; margin-left: 20px; margin-bottom: 20px; font-size: 10px; color: #666;">
            <div style="font-weight: bold; margin-bottom: 5px;">Terms & Conditions:</div>
            <div>{{ $document->terms_conditions }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Raines Technologies (PTY) LTD | <span class="footer-accent">www.raines.africa</span></p>
            <p style="margin-top: 10px;">This is a computer-generated document. No signature required.</p>
        </div>
    </div>
</body>
</html>

