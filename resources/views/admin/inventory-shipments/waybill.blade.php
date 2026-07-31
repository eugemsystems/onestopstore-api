<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill - {{ $shipment->order ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        
        .waybill-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header with Logo */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #062a6a;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #062a6a;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 10pt;
            color: #666;
        }
        
        /* Waybill Title */
        .waybill-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            color: #062a6a;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Order Number Section */
        .order-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
            border-left: 4px solid #062a6a;
        }
        
        .order-number {
            font-size: 16pt;
            font-weight: bold;
            color: #062a6a;
            margin-bottom: 5px;
        }
        
        .shipment-info {
            font-size: 10pt;
            color: #666;
        }
        
        /* Product Section - Single Product */
        .product-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 3px solid #062a6a;
            border-radius: 8px;
            background: white;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #062a6a;
            margin-bottom: 20px;
            text-transform: uppercase;
            text-align: center;
            border-bottom: 2px solid #062a6a;
            padding-bottom: 10px;
        }
        
        .product-container {
            display: table;
            width: 100%;
        }
        
        .product-info {
            display: table-cell;
            vertical-align: middle;
            width: 55%;
            padding-right: 20px;
        }
        
        .product-name {
            font-size: 16pt;
            font-weight: bold;
            color: #062a6a;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .product-details {
            margin-top: 15px;
        }
        
        .detail-row {
            margin-bottom: 10px;
            font-size: 11pt;
        }
        
        .detail-label {
            font-weight: bold;
            color: #062a6a;
            display: inline-block;
            width: 120px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .product-qr {
            display: table-cell;
            vertical-align: middle;
            width: 45%;
            text-align: center;
        }
        
        .qr-code-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #062a6a;
        }
        
        .qr-code {
            max-width: 200px;
            width: 100%;
            height: auto;
        }
        
        .qr-code-container svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        
        .qr-label {
            font-size: 9pt;
            color: #666;
            margin-top: 10px;
            text-align: center;
        }
        
        /* Shipment Details Box */
        .shipment-details {
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #062a6a;
        }
        
        .details-title {
            font-size: 12pt;
            font-weight: bold;
            color: #062a6a;
            margin-bottom: 10px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            padding: 8px 5px;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }
        
        .info-table tr:last-child td {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #062a6a;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #062a6a;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        
        .footer-contact {
            margin-bottom: 10px;
        }
        
        .footer-note {
            font-style: italic;
            margin-top: 10px;
            color: #999;
        }
        
        /* Print Styles */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .waybill-container {
                padding: 10px;
            }
            
            .product-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="waybill-container">
        <!-- Header with Logo -->
        <div class="header">
            @php
                $logoPath = public_path('images/logo.png');
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('logo.png');
                }
                $logoExists = file_exists($logoPath);
            @endphp
            
            @if($logoExists)
                <img src="{{ $logoPath }}" alt="Raines Africa Logo" class="logo">
            @else
                <div class="company-name">RAINES AFRICA</div>
                <div class="company-tagline">Quality Products, Trusted Service</div>
            @endif
        </div>
        
        <!-- Waybill Title -->
        <div class="waybill-title">Shipping Waybill</div>
        
        <!-- Order Information -->
        <div class="order-section">
            <div class="order-number">Order #{{ $shipment->order ?? 'N/A' }}</div>
            <div class="shipment-info">
                <strong>Shipment ID:</strong> {{ $shipment->id }} | 
                <strong>Destination:</strong> {{ $shipment->destination }} | 
                <strong>Generated:</strong> {{ $generatedDate }}
            </div>
        </div>
        
        <!-- Single Product Section -->
        <div class="product-section">
            <div class="section-title">Product Information</div>
            
            <div class="product-container">
                <div class="product-info">
                    <div class="product-name">{{ $shipment->title }}</div>
                    
                    <div class="product-details">
                        <div class="detail-row">
                            <span class="detail-label">Quantity:</span>
                            <span class="detail-value">{{ number_format($shipment->quantity) }} units</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Destination:</span>
                            <span class="detail-value">{{ $shipment->destination }}</span>
                        </div>
                        
                        @if($shipment->transporter)
                        <div class="detail-row">
                            <span class="detail-label">Transporter:</span>
                            <span class="detail-value">{{ $shipment->transporter }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="product-qr">
                    <div class="qr-code-container">
                        @if(isset($qrCodePath) && file_exists($qrCodePath))
                            <img src="{{ $qrCodePath }}" alt="QR Code" style="width: 200px; height: 200px; display: block; margin: 0 auto;">
                        @else
                            <div style="width: 200px; height: 200px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                <div style="text-align: center; color: #999;">
                                    <div style="font-size: 12pt;">QR Code</div>
                                    <div style="font-size: 8pt;">Error loading</div>
                                </div>
                            </div>
                        @endif
                        <div class="qr-label">Scan to update status</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-contact">
                <strong>Raines Africa</strong><br>
                Email: info@rainesafrica.com | Website: www.rainesafrica.com<br>
                Contact: +263 123 456 789
            </div>
            <div class="footer-note">
                This waybill was generated automatically. Please scan the QR code upon receipt at local branch.
            </div>
            <div style="margin-top: 10px; font-size: 8pt;">
                Generated on {{ $generatedDate }} | Document ID: WB-{{ $shipment->id }}-{{ now()->timestamp }}
            </div>
        </div>
    </div>
</body>
</html>
