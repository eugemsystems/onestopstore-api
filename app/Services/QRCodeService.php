<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    /**
     * Generate QR code for an order item
     * 
     * @param int $orderId
     * @param int $orderNumber
     * @param int $productId
     * @param string $productName
     * @param string $productSku
     * @param int $pivotId
     * @return string Base64 encoded QR code
     */
    public function generateOrderItemQRCode(
        int $orderId,
        int $orderNumber,
        int $productId,
        string $productName,
        string $productSku,
        int $pivotId
    ): string {
        // Create QR code data with all necessary information
        $qrData = json_encode([
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'product_id' => $productId,
            'product_name' => $productName,
            'product_sku' => $productSku,
            'pivot_id' => $pivotId,
            'type' => 'order_item',
            'timestamp' => now()->timestamp,
        ]);

        // Generate QR code as base64
        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrData);

        // Convert to base64
        return base64_encode($qrCode);
    }

    /**
     * Generate QR code as SVG for display
     * 
     * @param string $qrData
     * @return string SVG QR code
     */
    public function generateQRCodeSVG(string $qrData): string
    {
        return QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrData);
    }

    /**
     * Decode QR code data
     * 
     * @param string $qrData
     * @return array|null
     */
    public function decodeQRData(string $qrData): ?array
    {
        try {
            $data = json_decode($qrData, true);
            
            // Validate that it's an order item QR code
            if (isset($data['type']) && $data['type'] === 'order_item' && isset($data['pivot_id'])) {
                return $data;
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save QR code to storage
     * 
     * @param string $base64QRCode
     * @param string $filename
     * @return string Path to saved file
     */
    public function saveQRCode(string $base64QRCode, string $filename): string
    {
        $qrCodeData = base64_decode($base64QRCode);
        $path = 'qr-codes/' . $filename;
        
        Storage::disk('public')->put($path, $qrCodeData);
        
        return $path;
    }

    /**
     * Generate QR code directly as PNG for download
     * 
     * @param string $qrData
     * @return string Binary PNG data
     */
    public function generateQRCodePNG(string $qrData): string
    {
        return QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrData);
    }
}
