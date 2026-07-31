<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Attachment;

/**
 * Service to manage order QR code files via laravel-media
 * - Generates and uploads QR codes to laravel-media
 * - Provides image_url from laravel-media for email embedding
 * - Cleans up QR codes when orders are completed
 */
class OrderQRCodeService
{
    /**
     * Laravel Media API URL
     */
    protected string $mediaApiUrl;

    public function __construct()
    {
        // Use IMAGE_API_URL (same as ticket attachments)
        $this->mediaApiUrl = rtrim(env('IMAGE_API_URL', 'http://localhost:8002/api'), '/');
    }

    /**
     * Generate and upload QR code to laravel-media
     *
     * @param string $orderNumber
     * @param string $qrUrl The URL/data to encode in the QR code
     * @param string $type Type of QR code: 'collection', 'delivery', etc.
     * @param string|null $qrCodePng Pre-generated QR code PNG data (optional)
     * @return string|null image_url from laravel-media, or null on failure
     */
    public function generateAndSave(string $orderNumber, string $qrUrl, string $type = 'collection', ?string $qrCodePng = null): ?string
    {
        try {
            // Generate QR code PNG if not provided
            if (!$qrCodePng) {
                $qrCodePng = QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($qrUrl);
            }

            // Ensure it's a plain string (cast in case it's HtmlString)
            $qrCodePng = (string) $qrCodePng;

            // Filename for the QR code
            $filename = "order_{$orderNumber}_{$type}.png";

            // Skip laravel-media upload if URL is not configured
            if (empty($this->mediaApiUrl)) {
                Log::warning('IMAGE_API_URL not configured, skipping laravel-media upload', [
                    'order_number' => $orderNumber,
                    'type' => $type,
                ]);
                // Return null so caller can use fallback
                return null;
            }

            // Upload to laravel-media
            $response = Http::timeout(10)->attach(
                'image',
                $qrCodePng,
                $filename
            )->post($this->mediaApiUrl . '/attachments');

            if ($response->successful()) {
                $mediaResponse = $response->json();
                $filesArray = $mediaResponse['files'] ?? [];

                if (empty($filesArray)) {
                    throw new \Exception('No files returned from media upload');
                }

                $uploadedFile = $filesArray[0];

                // Store attachment reference in local database
                $attachment = Attachment::updateOrCreate(
                    ['uuid' => $uploadedFile['uuid']],
                    [
                        'name' => $uploadedFile['name'] ?? $filename,
                        'file_name' => $uploadedFile['file_name'] ?? $filename,
                        'image_url' => $uploadedFile['image_url'],
                        'mime_type' => $uploadedFile['mime_type'] ?? 'image/png',
                        'size' => $uploadedFile['size'] ?? strlen($qrCodePng),
                        'disk' => $uploadedFile['disk'] ?? 'public',
                        'collection_name' => 'order_qrcodes',
                        'model_id' => $orderNumber,
                        'model_type' => 'OrderQRCode',
                    ]
                );

                // Return the image_url from laravel-media
                return $uploadedFile['image_url'];
            } else {
                throw new \Exception('Media upload failed: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {

            // Return null so caller can use fallback (CID attachment)
            return null;
        }
    }

    /**
     * Delete QR code file(s) from laravel-media for an order
     *
     * @param string $orderNumber
     * @param string|null $type Specific type to delete, or null to delete all types
     * @return bool Success status
     */
    public function delete(string $orderNumber, ?string $type = null): bool
    {
        try {
            // Find all QR code attachments for this order
            $query = Attachment::where('model_type', 'OrderQRCode')
                ->where('model_id', $orderNumber);

            if ($type) {
                // Filter by type if specified (check file_name contains the type)
                $query->where('file_name', 'like', "%_{$type}.png");
            }

            $attachments = $query->get();

            if ($attachments->isEmpty()) {
                return true;
            }

            $deletedCount = 0;
            foreach ($attachments as $attachment) {
                try {
                    // Delete from laravel-media using UUID
                    $response = Http::delete($this->mediaApiUrl . '/attachments/' . $attachment->uuid);

                    if ($response->successful()) {
                        // Delete local attachment record
                        $attachment->delete();
                        $deletedCount++;
                    } else {
                        Log::warning('Failed to delete QR code from laravel-media', [
                            'uuid' => $attachment->uuid,
                            'response' => $response->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error deleting QR code attachment', [
                        'uuid' => $attachment->uuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }


            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete QR code files', [
                'order_number' => $orderNumber,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if QR code exists for an order
     *
     * @param string $orderNumber
     * @param string $type
     * @return bool
     */
    public function exists(string $orderNumber, string $type = 'collection'): bool
    {
        return Attachment::where('model_type', 'OrderQRCode')
            ->where('model_id', $orderNumber)
            ->where('file_name', 'like', "%_{$type}.png")
            ->exists();
    }

    /**
     * Get image_url for an existing QR code
     *
     * @param string $orderNumber
     * @param string $type
     * @return string|null
     */
    public function getUrl(string $orderNumber, string $type = 'collection'): ?string
    {
        $attachment = Attachment::where('model_type', 'OrderQRCode')
            ->where('model_id', $orderNumber)
            ->where('file_name', 'like', "%_{$type}.png")
            ->first();

        return $attachment ? $attachment->image_url : null;
    }

    /**
     * Clean up old QR codes (optional - for scheduled cleanup)
     * Delete QR codes older than specified days
     *
     * @param int $daysOld
     * @return int Number of files deleted
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        try {
            $cutoffDate = now()->subDays($daysOld);

            $oldAttachments = Attachment::where('model_type', 'OrderQRCode')
                ->where('created_at', '<', $cutoffDate)
                ->get();

            $deletedCount = 0;
            foreach ($oldAttachments as $attachment) {
                try {
                    // Delete from laravel-media
                    $response = Http::delete($this->mediaApiUrl . '/attachments/' . $attachment->uuid);

                    if ($response->successful()) {
                        $attachment->delete();
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old QR code', [
                        'uuid' => $attachment->uuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $deletedCount;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
