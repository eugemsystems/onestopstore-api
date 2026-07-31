<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>QR Stickers — Bulk</title>
    <style>
        /* Each page = one sticker size: 50mm × 33mm */
        @page {
            margin: 0;
            size: 50mm 33mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: Arial, sans-serif;
            font-size: 6pt;
            color: #000;
            background: #fff;
            width: 50mm;
        }

        /* One sticker per page — page break after each */
        .sticker-page {
            width: 50mm;
            height: 33mm;
            overflow: hidden;
            page-break-after: always;
        }

        /* Inner layout: QR left, info right */
        .sticker-inner {
            display: table;
            width: 50mm;
            height: 33mm;
            border-collapse: collapse;
        }

        .qr-cell {
            display: table-cell;
            width: 28mm;
            vertical-align: middle;
            text-align: center;
            padding: 0.8mm;
        }

        .qr-code {
            width: 27mm;
            height: 27mm;
            display: block;
            margin: auto;
        }

        .info-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 0.8mm 1mm 0.8mm 0;
        }

        .product-name {
            font-size: 6pt;
            font-weight: bold;
            line-height: 1.15;
            margin-bottom: 0.8mm;
            word-wrap: break-word;
        }

        .order-info {
            font-size: 5pt;
            line-height: 1.2;
            color: #333;
        }

        .order-info strong {
            color: #000;
        }

        hr.divider {
            margin: 0.4mm 0;
            border: 0;
            border-top: 0.3pt solid #bbb;
        }

        .dest-badge {
            display: inline-block;
            font-size: 4.5pt;
            font-weight: bold;
            background: #1e293b;
            color: #fff;
            padding: 0.3mm 1.2mm;
            border-radius: 1mm;
            margin-top: 0.5mm;
        }
    </style>
</head>
<body>
    @foreach($stickers as $index => $item)
        <div class="sticker-page">
            <div class="sticker-inner">
                <div class="qr-cell">
                    @if(file_exists($item['qrPath']))
                        <img src="{{ $item['qrPath'] }}" alt="QR" class="qr-code">
                    @endif
                </div>
                <div class="info-cell">
                    <div class="product-name">{{ Str::limit($item['shipment']->title, 45, '…') }}</div>
                    <hr class="divider">
                    <div class="order-info">
                        <strong>Order:</strong> {{ $item['shipment']->order ?? 'N/A' }}<br>
                        <strong>Qty:</strong> {{ $item['shipment']->quantity }}
                        @if($item['shipment']->destination)
                            <br><span class="dest-badge">{{ $item['shipment']->destination }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
