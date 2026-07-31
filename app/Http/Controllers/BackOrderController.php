<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Jobs\ProcessBackOrderFile;
use App\Jobs\ReindexProduct;
use ZipArchive;
use Exception;

class BackOrderController extends Controller
{
    private $uploadPath = 'back-orders';
    private $extractPath = 'back-orders/extracted';

    /**
     * Display the back order management page
     */
    public function index()
    {
        return view('back-orders.index');
    }

    /**
     * Upload TXT or ZIP files
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,zip|max:512000', // 500MB max
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('YmdHis');
            $fileName = $timestamp . '_' . $originalName;

            // Store the uploaded file
            $path = $file->storeAs($this->uploadPath, $fileName, 'local');

            // If it's a zip file, extract it
            if ($extension === 'zip') {
                $extractedFiles = $this->extractZipFile($path);

                return response()->json([
                    'success' => true,
                    'message' => 'ZIP file uploaded and extracted successfully',
                    'type' => 'zip',
                    'files' => $extractedFiles,
                    'count' => count($extractedFiles)
                ]);
            }

            // For TXT files, just return the file info
            return response()->json([
                'success' => true,
                'message' => 'TXT file uploaded successfully',
                'type' => 'txt',
                'files' => [[
                    'name' => $originalName,
                    'path' => $path,
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString()
                ]]
            ]);

        } catch (Exception $e) {
            Log::error('Back Order Upload Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract ZIP file and return list of TXT files
     */
    private function extractZipFile($zipPath)
    {
        $fullPath = Storage::disk('local')->path($zipPath);
        $extractTo = Storage::disk('local')->path($this->extractPath);

        // Create extract directory if it doesn't exist
        if (!file_exists($extractTo)) {
            mkdir($extractTo, 0755, true);
        }

        $zip = new ZipArchive;
        $extractedFiles = [];

        if ($zip->open($fullPath) === TRUE) {
            // Extract all files
            $zip->extractTo($extractTo);

            // Get list of TXT files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileInfo = pathinfo($filename);

                // Only process TXT files
                if (isset($fileInfo['extension']) && strtolower($fileInfo['extension']) === 'txt') {
                    $extractedPath = $this->extractPath . '/' . $filename;
                    $fullExtractedPath = $extractTo . '/' . $filename;

                    if (file_exists($fullExtractedPath)) {
                        $extractedFiles[] = [
                            'name' => basename($filename),
                            'path' => $extractedPath,
                            'size' => filesize($fullExtractedPath),
                            'uploaded_at' => now()->toDateTimeString()
                        ];
                    }
                }
            }

            $zip->close();

            // Delete the original ZIP file
            Storage::disk('local')->delete($zipPath);

        } else {
            throw new Exception('Failed to extract ZIP file');
        }

        return $extractedFiles;
    }

    /**
     * Get list of uploaded files
     */
    public function getUploadedFiles()
    {
        try {
            $files = [];

            // Get files from main upload directory
            $uploadedFiles = Storage::disk('local')->files($this->uploadPath);
            foreach ($uploadedFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                    $fullPath = Storage::disk('local')->path($file);
                    $files[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($fullPath),
                        'uploaded_at' => date('Y-m-d H:i:s', filemtime($fullPath))
                    ];
                }
            }

            // Get files from extracted directory
            if (Storage::disk('local')->exists($this->extractPath)) {
                $extractedFiles = Storage::disk('local')->files($this->extractPath);
                foreach ($extractedFiles as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                        $fullPath = Storage::disk('local')->path($file);
                        $files[] = [
                            'name' => basename($file),
                            'path' => $file,
                            'size' => filesize($fullPath),
                            'uploaded_at' => date('Y-m-d H:i:s', filemtime($fullPath))
                        ];
                    }
                }
            }

            // Sort by upload time (newest first)
            usort($files, function($a, $b) {
                return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
            });

            return response()->json([
                'success' => true,
                'files' => $files
            ]);

        } catch (Exception $e) {
            Log::error('Get Uploaded Files Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process selected files
     */
    public function processFiles(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string'
        ]);

        try {
            $selectedFiles = $request->input('files');
            $batchId = 'backorder_' . now()->format('YmdHis');

            $processedCount = 0;
            $totalSkus = 0;

            foreach ($selectedFiles as $filePath) {
                if (Storage::disk('local')->exists($filePath)) {
                    $fullPath = Storage::disk('local')->path($filePath);
                    $skus = $this->extractSkusFromFile($fullPath);
                    $totalSkus += count($skus);

                    // Process in chunks to avoid memory issues
                    $chunks = array_chunk($skus, 100);

                    foreach ($chunks as $chunkIndex => $skuChunk) {
                        ProcessBackOrderFile::dispatch($skuChunk, $batchId, basename($filePath), $chunkIndex);
                    }

                    $processedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Processing started for {$processedCount} file(s) with {$totalSkus} SKUs",
                'batch_id' => $batchId,
                'file_count' => $processedCount,
                'sku_count' => $totalSkus
            ]);

        } catch (Exception $e) {
            Log::error('Process Back Order Files Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract SKUs from a TXT file
     */
    private function extractSkusFromFile($filePath)
    {
        $skus = [];
        $handle = fopen($filePath, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $sku = trim($line);

                // Skip empty lines and comments
                if (!empty($sku) && !str_starts_with($sku, '#') && !str_starts_with($sku, '//')) {
                    $skus[] = $sku;
                }
            }

            fclose($handle);
        }

        // Remove duplicates
        return array_unique($skus);
    }

    /**
     * Get processing status
     */
    public function getStatus(Request $request)
    {
        $batchId = $request->input('batch_id');

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID is required'
            ], 400);
        }

        try {
            // Get status from cache
            $status = Cache::get("backorder_status_{$batchId}", [
                'processed' => 0,
                'updated' => 0,
                'failed' => 0,
                'status' => 'processing'
            ]);

            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Get Back Order Status Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete uploaded files
     */
    public function deleteFiles(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string'
        ]);

        try {
            $deletedCount = 0;

            foreach ($request->input('files') as $filePath) {
                if (Storage::disk('local')->exists($filePath)) {
                    Storage::disk('local')->delete($filePath);
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} file(s) deleted successfully"
            ]);

        } catch (Exception $e) {
            Log::error('Delete Back Order Files Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all uploaded files
     */
    public function clearAll()
    {
        try {
            $deletedCount = 0;

            // Clear main upload directory
            $uploadedFiles = Storage::disk('local')->files($this->uploadPath);
            foreach ($uploadedFiles as $file) {
                Storage::disk('local')->delete($file);
                $deletedCount++;
            }

            // Clear extracted directory
            if (Storage::disk('local')->exists($this->extractPath)) {
                $extractedFiles = Storage::disk('local')->files($this->extractPath);
                foreach ($extractedFiles as $file) {
                    Storage::disk('local')->delete($file);
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "All files cleared ({$deletedCount} files deleted)"
            ]);

        } catch (Exception $e) {
            Log::error('Clear All Back Order Files Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing history
     */
    public function history()
    {
        try {
            // Retrieve history from cache or database
            $history = Cache::get('backorder_history', []);

            return response()->json([
                'success' => true,
                'history' => array_reverse($history) // Newest first
            ]);

        } catch (Exception $e) {
            Log::error('Get Back Order History Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve history: ' . $e->getMessage()
            ], 500);
        }
    }
}
