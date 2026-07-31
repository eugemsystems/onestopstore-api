<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sticker</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 50mm 33mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 50mm;
            height: 33mm;
            overflow: hidden;
        }

        body {
            font-family: Arial, sans-serif;
            color: #000;
            font-size: 6pt;
            position: relative;
        }

        .sticker-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 50mm;
            height: 33mm;
        }

        .sticker-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .qr-cell {
            width: 30mm;
            vertical-align: middle;
            text-align: center;
            padding: 0.5mm;
        }

        .qr-code {
            width: 30mm;
            height: 30mm;
            display: block;
            margin: auto;
        }

        .info-cell {
            vertical-align: middle;
            padding: 0.5mm 1mm;
        }

        .product-name {
            font-size: 6pt;
            font-weight: normal;
            line-height: 1.1;
            margin-bottom: 0.5mm;
            word-wrap: break-word;
        }

        .order-info {
            font-size: 5pt;
            line-height: 1.1;
        }

        hr {
            margin: 0.3mm 0;
            border: 0;
            border-top: 0.3pt solid #999;
        }
    </style>
</head>
<body>
    <div class="sticker-content">
        <table class="sticker-table">
            <tr>
                <td class="qr-cell">
                    @if(file_exists($qrCodePath))
                        <img src="{{ $qrCodePath }}" alt="QR" class="qr-code">
                    @endif
                </td>
                <td class="info-cell">
                    <div class="product-name">{{ Str::limit($shipment->title, 50, '...') }}</div>
                    <hr>
                    <div class="order-info">
                        <strong>Order:</strong><br>{{ $shipment->order ?? 'N/A' }}
                        <hr>
                        <strong>Qty:</strong> {{ $shipment->quantity }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
