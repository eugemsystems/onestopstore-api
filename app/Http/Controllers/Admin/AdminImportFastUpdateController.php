<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminImportFastUpdateController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Only admin role can access this feature
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole('admin')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Only administrators can access the fast import update feature.'
                    ], 403);
                }

                return redirect()->route('admin.dashboard')
                    ->with('error', 'Unauthorized. Only administrators can access the fast import update feature.');
            }

            return $next($request);
        });
    }

    /**
     * Show upload + run interface (admin version for updates)
     */
    public function index()
    {
        // Get existing JSON files from the UPDATE directory
        $dir = storage_path('app/jsonfiles-update');
        $existingFiles = [];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.json') ?: [];
            foreach ($files as $file) {
                $basename = basename($file);
                $existingFiles[] = [
                    'name' => $basename,
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
            // Sort by date descending (newest first)
            usort($existingFiles, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }

        return view('admin.import-fast-update.index', compact('existingFiles'));
    }

    /**
     * Handle JSON upload into storage/app/jsonfiles-update
     */
    public function upload(Request $request)
    {
        $request->validate([
            'jsonfile' => ['required', 'file', 'mimes:json,txt', 'max:204800'], // ~200MB
        ]);

        $dir = storage_path('app/jsonfiles-update');
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $request->file('jsonfile');
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $finalName = $name . '-' . date('Ymd-His') . '.json';
        $file->move($dir, $finalName);

        return response()->json([
            'ok' => true,
            'stored_as' => $finalName,
            'path' => 'jsonfiles-update/' . $finalName,
        ]);
    }

    /**
     * Accept a chunk and store it under storage/app/jsonfiles-update/tmp/{uploadId}/{index}.part
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'uploadId'    => ['required','string','max:100'],
            'fileName'    => ['required','string','max:255'],
            'chunkIndex'  => ['required','integer','min:0'],
            'totalChunks' => ['required','integer','min:1'],
            'chunk'       => ['required','file'],
        ]);

        $uploadId    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $request->input('uploadId'));
        $chunkIndex  = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');

        $tmpDir = storage_path('app/jsonfiles-update/tmp/'.$uploadId);
        if (!is_dir($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }

        $request->file('chunk')->move($tmpDir, $chunkIndex.'.part');

        return response()->json([
            'ok' => true,
            'received' => $chunkIndex,
            'total' => $totalChunks,
        ]);
    }

    /**
     * Concatenate all chunks into final file under storage/app/jsonfiles-update
     */
    public function uploadComplete(Request $request)
    {
        $request->validate([
            'uploadId' => ['required','string','max:100'],
            'fileName' => ['required','string','max:255'],
        ]);

        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $request->input('uploadId'));
        $fileName = (string) $request->input('fileName');

        $tmpDir = storage_path('app/jsonfiles-update/tmp/'.$uploadId);
        if (!is_dir($tmpDir)) {
            return response()->json(['ok' => false, 'error' => 'Upload not found'], 404);
        }

        $parts = glob($tmpDir.'/*.part');
        if (empty($parts)) {
            return response()->json(['ok' => false, 'error' => 'No chunks found'], 400);
        }

        // Sort by chunk index
        usort($parts, function($a, $b) {
            $aNum = (int) basename($a, '.part');
            $bNum = (int) basename($b, '.part');
            return $aNum <=> $bNum;
        });

        $finalDir = storage_path('app/jsonfiles-update');
        if (!is_dir($finalDir)) {
            File::makeDirectory($finalDir, 0755, true);
        }

        // Detect file extension
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));
        $finalName = $name . '-' . date('Ymd-His') . '.' . $extension;
        $finalPath = $finalDir.'/'.$finalName;

        $out = fopen($finalPath, 'wb');
        foreach ($parts as $part) {
            $in = fopen($part, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        // Clean up temp directory
        File::deleteDirectory($tmpDir);

        // Extract ZIP file if it's a ZIP
        if ($extension === 'zip') {
            return $this->extractZipFile($finalPath, $finalDir);
        }

        return response()->json([
            'ok' => true,
            'stored_as' => $finalName,
            'path' => 'jsonfiles-update/'.$finalName,
        ]);
    }

    /**
     * Extract ZIP file and return list of extracted JSON files
     */
    private function extractZipFile(string $zipPath, string $extractToDir): \Illuminate\Http\JsonResponse
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['ok' => false, 'error' => 'ZIP extension not available'], 500);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return response()->json(['ok' => false, 'error' => 'Failed to open ZIP file'], 422);
        }

        $extractedFiles = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileInfo = pathinfo($filename);

                // Only extract JSON files
                if (isset($fileInfo['extension']) && strtolower($fileInfo['extension']) === 'json') {
                    // Create safe filename
                    $safeName = Str::slug($fileInfo['filename']) . '.json';
                    $extractPath = $extractToDir . '/' . $safeName;

                    // Extract using stream to avoid loading entire file into memory
                    $stream = $zip->getStream($filename);
                    if ($stream !== false) {
                        $outputStream = fopen($extractPath, 'wb');
                        if ($outputStream !== false) {
                            // Stream copy instead of loading all into memory
                            stream_copy_to_stream($stream, $outputStream);
                            fclose($outputStream);
                            fclose($stream);
                            $extractedFiles[] = $safeName;
                        }
                    }
                }
            }

            $zip->close();

            // Delete the ZIP file after extraction
            @unlink($zipPath);

            return response()->json([
                'ok' => true,
                'extracted' => true,
                'files_extracted' => count($extractedFiles),
                'extracted_files' => $extractedFiles,
                'message' => 'ZIP extracted: ' . count($extractedFiles) . ' JSON file(s)',
                'stored_as' => implode(', ', $extractedFiles),
            ]);

        } catch (\Throwable $e) {
            $zip->close();
            return response()->json(['ok' => false, 'error' => 'Extraction failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete selected JSON files
     */
    public function deleteFiles(Request $request)
    {
        $files = $request->input('files', []);

        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No files specified'
            ], 400);
        }

        $dir = storage_path('app/jsonfiles-update');
        $deleted = 0;
        $errors = [];

        foreach ($files as $filename) {
            // Sanitize filename to prevent directory traversal
            $filename = basename($filename);
            $filepath = $dir . '/' . $filename;

            if (file_exists($filepath) && is_file($filepath)) {
                if (unlink($filepath)) {
                    $deleted++;
                } else {
                    $errors[] = "Failed to delete: {$filename}";
                }
            } else {
                $errors[] = "File not found: {$filename}";
            }
        }

        if ($deleted > 0 && empty($errors)) {
            return response()->json([
                'success' => true,
                'message' => "{$deleted} file(s) deleted successfully"
            ]);
        } elseif ($deleted > 0 && !empty($errors)) {
            return response()->json([
                'success' => true,
                'message' => "{$deleted} file(s) deleted, " . count($errors) . " error(s)",
                'errors' => $errors
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete files',
                'errors' => $errors
            ], 500);
        }
    }

    /**
     * Download a JSON file
     */
    public function downloadFile(Request $request)
    {
        $filename = $request->query('file');

        if (empty($filename)) {
            abort(400, 'No file specified');
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filepath = storage_path('app/jsonfiles-update/' . $filename);

        if (!file_exists($filepath) || !is_file($filepath)) {
            abort(404, 'File not found');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Run the price update import
     */
    public function run(Request $request)
    {
        // CRITICAL: Set memory limit FIRST before any processing
        ini_set('memory_limit', '2048M'); // 2GB for large files

        $request->validate([
            'percentage'   => ['required', 'numeric'],
            'todaysRate'   => ['required', 'numeric'],
            'selectedFiles' => ['nullable'],
        ]);

        $percentage = (float) $request->input('percentage');
        $todaysRate = (float) $request->input('todaysRate');

        // Decode selectedFiles if it's a JSON string
        $selectedFiles = $request->input('selectedFiles', []);
        if (is_string($selectedFiles)) {
            $selectedFiles = json_decode($selectedFiles, true) ?? [];
        }
        if (!is_array($selectedFiles)) {
            $selectedFiles = [];
        }

        // Generate a unique batch ID for this import session
        $batchId = 'import_' . date('Ymd_His') . '_' . Str::random(8);

        $response = new StreamedResponse(function() use ($percentage, $todaysRate, $selectedFiles, $batchId) {
            // Disable ALL output buffering
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Prevent any buffering
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');

            // Set time limit - increase for production
            set_time_limit(0); // Unlimited
            ini_set('max_execution_time', '0'); // Unlimited
            ini_set('memory_limit', '512M'); // Increase memory

            // Disable query log to save memory
            DB::connection()->disableQueryLog();

            // Send initial content to force headers
            echo "<!-- Starting stream -->\n";
            flush();

            // Send immediate visible output to establish connection
            echo "<div style='font-family: monospace; padding: 10px;'>\n";
            flush();

            // Show batch ID to user
            $this->fastOut("🆔 Batch ID: {$batchId}");
            $this->fastOut("📝 You can use this ID to track or resume this import later");
            $this->fastOut("");

            // Capture fatal errors
            register_shutdown_function(function() {
                $err = error_get_last();
                if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
                    $msg = "FATAL ERROR: {$err['message']} in {$err['file']} on line {$err['line']}";
                    try {
                        $this->fastOut("❌ " . $msg);
                    } catch (Throwable $__) {
                        // best-effort
                    }
                    Log::error($msg, $err);
                }
            });

            try {
                $this->runPriceUpdate($percentage, $todaysRate, $selectedFiles, $batchId);
            } catch (Throwable $e) {
                try {
                    $this->fastOut("❌ Exception: " . $e->getMessage());
                } catch (Throwable $__) {
                    // ignore
                }
                Log::error('AdminImportFastUpdateController run() exception', ['exception' => $e]);
            }

            // Close the div and final flush
            echo "</div>\n";
            flush();
        });

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('X-Accel-Buffering', 'no'); // Nginx: disable buffering
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive'); // Keep connection alive
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Output to browser
     */
    private function fastOut(string $text): void
    {
        $clean = preg_replace('/\e\[[;\d]*m/', '', $text);
        echo nl2br(e($clean)) . "\n";

        // Force flush output
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Run the price update import process
     */
    private function runPriceUpdate(float $percentage, float $todaysRate, array $selectedFiles, string $batchId): void
    {
        $this->fastOut("🔄 Starting Price & Review Update Import...");
        $this->fastOut("⚙️ Settings: Percentage={$percentage}, Today's Rate={$todaysRate}");

        $dir = storage_path('app/jsonfiles-update');
        if (!is_dir($dir)) {
            $this->fastOut("❌ Directory not found: {$dir}");
            return;
        }

        // Get files to process
        $jsonFiles = [];
        if (!empty($selectedFiles)) {
            foreach ($selectedFiles as $filename) {
                $filepath = $dir . '/' . basename($filename);
                if (file_exists($filepath)) {
                    $jsonFiles[] = $filepath;
                }
            }
        } else {
            $jsonFiles = glob($dir . '/*.json') ?: [];
        }

        if (empty($jsonFiles)) {
            $this->fastOut("❌ No JSON files found to process");
            return;
        }

        $this->fastOut("📁 Found " . count($jsonFiles) . " file(s) to process");

        // Create import job records for all files
        $importJobs = [];
        foreach ($jsonFiles as $filepath) {
            $filename = basename($filepath);
            $importJobs[$filename] = ImportJob::create([
                'batch_id' => $batchId,
                'import_type' => 'fast-import-update',
                'filename' => $filename,
                'status' => 'pending',
                'percentage' => $percentage,
                'todays_rate' => $todaysRate,
                'user_id' => auth()->id(),
            ]);
        }
        $this->fastOut("✅ Created tracking records for all files");
        $this->fastOut("");

        $totalProductsFound = 0;
        $totalProductsUpdated = 0;
        $totalProductsSkipped = 0;

        // Track updated product IDs for Elasticsearch reindexing
        $updatedProductIds = [];

        // Track skip reasons
        $skipReasons = [
            'no_sku' => 0,
            'no_price' => 0,
            'invalid_price' => 0,
            'price_too_large' => 0,
            'product_not_found' => 0,
            'has_active_sale' => 0,
        ];

        foreach ($jsonFiles as $filepath) {
            $filename = basename($filepath);
            $this->fastOut("\n📄 Processing: {$filename}");

            // Get the import job for this file
            $importJob = $importJobs[$filename];
            $importJob->markAsProcessing();
            $this->fastOut("🔄 Status: Processing");

            // Initialize per-file counters
            $fileProductCount = 0;
            $fileUpdatedCount = 0;
            $fileSkippedCount = 0;

            try {
                // Check file size and warn if large
                $fileSize = filesize($filepath);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                $this->fastOut("📦 File size: {$fileSizeMB} MB");

                // Increase memory limit for large files
                if ($fileSizeMB > 30) {
                    ini_set('memory_limit', '2048M'); // Increase to 2GB for large files
                    $this->fastOut("⚙️ Increased memory limit to 2048M for large file");
                }

                // For VERY large files (>200MB), use on-the-fly processing
                if ($fileSizeMB > 200) {
                    $this->fastOut("📖 Using on-the-fly streaming processor for very large file...");

                    // Process items as they're parsed instead of loading all into memory
                    $result = $this->streamAndProcessJsonFile(
                        $filepath,
                        $percentage,
                        $todaysRate,
                        $fileProductCount,
                        $fileUpdatedCount,
                        $fileSkippedCount,
                        $skipReasons,
                        $updatedProductIds
                    );

                    $fileProductCount = $result['productCount'];
                    $fileUpdatedCount = $result['updatedCount'];
                    $fileSkippedCount = $result['skippedCount'];
                    $skipReasons = $result['skipReasons'];
                    $updatedProductIds = array_merge($updatedProductIds, $result['updatedIds']);

                    // Skip normal processing since we already processed on-the-fly
                    $totalProductsFound += $fileProductCount;
                    $totalProductsUpdated += $fileUpdatedCount;
                    $totalProductsSkipped += $fileSkippedCount;

                    // Update import job with final statistics
                    $importJob->update([
                        'total_items' => $fileProductCount, // For streaming, we don't know total upfront
                        'processed_items' => $fileProductCount,
                        'updated_items' => $fileUpdatedCount,
                        'skipped_items' => $fileSkippedCount,
                        'skip_reasons' => $skipReasons,
                    ]);
                    $importJob->markAsCompleted();

                    $this->fastOut("✅ {$filename}: Found={$fileProductCount}, Updated={$fileUpdatedCount}, Skipped={$fileSkippedCount}");
                    $this->fastOut("✅ Status: Completed");
                    continue; // Skip to next file
                }

                // For files larger than 50MB, use streaming parser (lowered from 100MB)
                if ($fileSizeMB > 50) {
                    $this->fastOut("📖 Using streaming parser for large file...");
                    $parseResult = $this->streamJsonFile($filepath);
                    $data = $parseResult['data'];
                    $totalItems = $parseResult['count']; // Count from parser, not from count()
                } else {
                    $this->fastOut("📖 Reading JSON file...");
                    $content = file_get_contents($filepath);

                    $this->fastOut("🔍 Parsing JSON...");
                    $data = json_decode($content, true);

                    // Free memory
                    unset($content);

                    if (!is_array($data)) {
                        $this->fastOut("⚠️ Invalid JSON format in {$filename}");
                        continue;
                    }

                    $totalItems = count($data);
                }

                if (!is_array($data)) {
                    $this->fastOut("⚠️ Invalid JSON format in {$filename}");
                    $importJob->markAsFailed("Invalid JSON format");
                    continue;
                }

                $this->fastOut("✅ Found {$totalItems} items in JSON");

                // Update import job with total items count
                $importJob->update([
                    'total_items' => $totalItems,
                ]);

                $fileProductCount = 0;
                $fileUpdatedCount = 0;
                $fileSkippedCount = 0;

                $progressInterval = max(1, floor($totalItems / 20)); // Show progress every 5%
                $keepAliveInterval = max(1, floor($totalItems / 100)); // Keep-alive every 1%

                foreach ($data as $index => $item) {
                    // Keep connection alive - send something every 1% even if no progress shown
                    if ($index > 0 && $index % $keepAliveInterval === 0) {
                        echo " "; // Send space to keep connection alive
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();

                        // Check if connection is still alive
                        if (connection_aborted()) {
                            $this->fastOut("⚠️ Connection aborted by client");
                            break;
                        }
                    }

                    // Show progress every N items
                    if ($index > 0 && $index % $progressInterval === 0) {
                        $percent = round(($index / $totalItems) * 100);
                        $this->fastOut("⏳ Progress: {$percent}% ({$index}/{$totalItems})");

                        // Force flush to show progress
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();

                        // Free memory periodically
                        if ($index % ($progressInterval * 5) === 0) {
                            gc_collect_cycles();
                        }
                    }

                    if (!isset($item['product_views']['buybox_summary'])) {
                        continue;
                    }

                    $fileProductCount++;
                    $buybox = $item['product_views']['buybox_summary'];
                    $coreData = $item['product_views']['core'] ?? [];
                    $reviewSummary = $item['product_views']['review_summary'] ?? null;
                    $stockSummary = $item['product_views']['stock_availability_summary'] ?? null;

                    // Extract brand from core.brand
                    $brandName = $coreData['brand'] ?? null;
                    $brandId = null;
                    if ($brandName && trim($brandName) !== '') {
                        $brandId = $this->getOrCreateBrand(trim($brandName));
                    }

                    // Extract SKU from core.id (this is the SKU in the database)
                    $sku = $coreData['id'] ?? null;

                    if (!$sku) {
                        $skipReasons['no_sku']++;
                        $fileSkippedCount++;
                        continue;
                    }

                    // Extract price from pretty_price (falling back to app_pretty_price)
                    $prettyPrice = $buybox['pretty_price'] ?? $buybox['app_pretty_price'] ?? null;

                    if (!$prettyPrice) {
                        $skipReasons['no_price']++;
                        $fileSkippedCount++;
                        continue;
                    }

                    // Parse the price from "R 9,999" format to 9999
                    $cleanPrice = $this->parsePrettyPrice($prettyPrice);

                    if (!$cleanPrice) {
                        $skipReasons['invalid_price']++;
                        $fileSkippedCount++;
                        continue;
                    }

                    // Calculate converted price
                    $convertedPrice = $this->calculatePrice($cleanPrice, $percentage, $todaysRate);

                    // Check if price is too large for database field (NUMERIC(8,2) max is 999999.99)
                    if ($convertedPrice > 999999.99) {
                        $skipReasons['price_too_large']++;
                        $fileSkippedCount++;
                        continue;
                    }

                    // Check if product exists by SKU first
                    $product = DB::table('products')->where('sku', $sku)->first();

                    // If not found by SKU, try by slug as fallback
                    if (!$product) {
                        $slug = $coreData['slug'] ?? null;
                        if ($slug) {
                            $product = DB::table('products')->where('slug', $slug)->first();
                        }
                    }

                    if (!$product) {
                        // Skip if product doesn't exist (tried both SKU and slug)
                        $skipReasons['product_not_found']++;
                        $fileSkippedCount++;
                        continue;
                    }

                    // Check if product has a sale_price > 0 (manual promotion/sale set in admin)
                    // Skip price update if sale_price > 0 to preserve manual sale pricing
                    $hasSalePrice = $product->sale_price > 0;

                    // Prepare update data (only price, NOT sale_price)
                    $updateData = [
                        'updated_at' => now(),
                    ];

                    // Only update price if product does NOT have a manual sale price
                    if (!$hasSalePrice) {
                        $updateData['price'] = $convertedPrice;
                    } else {
                        // Skip price update for products with manual sale prices
                        $skipReasons['has_manual_sale_price']++;
                    }

                    // Add brand_id if brand was found/created
                    if ($brandId) {
                        $updateData['brand_id'] = $brandId;
                    }

                    // Add stock status if available
                    $detectedStockStatus = $this->determineStockStatus($stockSummary);
                    if ($detectedStockStatus) {
                        $updateData['stock_status'] = $detectedStockStatus;
                    }

                    // Add estimated_delivery_text from stock_availability_summary
                    if ($stockSummary) {
                        $estimatedDelivery = null;
                        $status = isset($stockSummary['status']) ? trim($stockSummary['status']) : '';

                        // If status is empty, use default
                        if (empty($status) || $status==='') {
                            $estimatedDelivery = 'Ships in 4-6 work days';
                        }
                        // Check if status is "In stock" and distribution_centres is available
                        elseif (strcasecmp($status, 'In stock') === 0 && isset($stockSummary['distribution_centres']) && is_array($stockSummary['distribution_centres'])) {
                            // Use distribution centres mapping for "In stock" products
                            $estimatedDelivery = $this->getEstimatedDeliveryFromDistributionCentres($stockSummary['distribution_centres']);
                        } else {
                            // For other statuses (e.g., "Ships in X days"), increment by 2
                            $estimatedDelivery = $this->incrementShippingDays($status, 2);
                        }

                        if ($estimatedDelivery) {
                            $updateData['estimated_delivery_text'] = $estimatedDelivery;
                        }
                    }

                    // Populate meta_title with product name if available
                    if (isset($coreData['title']) && !empty(trim($coreData['title']))) {
                        $updateData['meta_title'] = trim($coreData['title']);
                    }

                    // Populate meta_description with short_description (subtitle) if available
                    if (isset($coreData['subtitle']) && !empty(trim($coreData['subtitle']))) {
                        $updateData['meta_description'] = trim($coreData['subtitle']);
                    }

                    // Add review data if available - Create reviews matching distribution
                    if ($reviewSummary && isset($reviewSummary['review_count']) && $reviewSummary['review_count'] > 0) {
                        try {
                            $reviewCount = $reviewSummary['review_count'];
                            $distribution = $reviewSummary['distribution'] ?? [];

                            // Delete all existing system-generated reviews for this product
                            DB::table('reviews')
                                ->where('product_id', $product->id)
                                ->whereNull('consumer_id') // System-generated reviews have no consumer
                                ->whereNull('deleted_at')
                                ->delete();

                            // Create reviews based on the actual distribution
                            if (!empty($distribution)) {
                                // Extract distribution counts
                                $ratings = [
                                    1 => $distribution['num_1_star_ratings'] ?? 0,
                                    2 => $distribution['num_2_star_ratings'] ?? 0,
                                    3 => $distribution['num_3_star_ratings'] ?? 0,
                                    4 => $distribution['num_4_star_ratings'] ?? 0,
                                    5 => $distribution['num_5_star_ratings'] ?? 0,
                                ];

                                // Prepare batch insert array for better performance
                                $reviewsToInsert = [];

                                // Create ALL reviews from distribution (no limit!)
                                foreach ($ratings as $stars => $count) {
                                    if ($count > 0) {
                                        for ($i = 0; $i < $count; $i++) {
                                            $reviewsToInsert[] = [
                                                'product_id' => $product->id,
                                                'rating' => $stars,
                                                'description' => "Imported {$stars}-star review",
                                                'consumer_id' => null,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ];
                                        }
                                    }
                                }

                                // Batch insert all reviews (chunked for performance)
                                if (!empty($reviewsToInsert)) {
                                    // Insert in chunks of 100 for better performance
                                    foreach (array_chunk($reviewsToInsert, 100) as $chunk) {
                                        DB::table('reviews')->insert($chunk);
                                    }
                                }
                            } else {
                                // Fallback: If no distribution, use star_rating to create ALL reviews
                                $starRating = $reviewSummary['star_rating'] ?? 0;
                                if ($starRating > 0 && $reviewCount > 0) {
                                    $roundedRating = round($starRating);

                                    $reviewsToInsert = [];
                                    // Create ALL reviews (no limit!)
                                    for ($i = 0; $i < $reviewCount; $i++) {
                                        $reviewsToInsert[] = [
                                            'product_id' => $product->id,
                                            'rating' => (int) $roundedRating,
                                            'description' => "Imported review",
                                            'consumer_id' => null,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ];
                                    }

                                    // Batch insert in chunks for performance
                                    if (!empty($reviewsToInsert)) {
                                        foreach (array_chunk($reviewsToInsert, 100) as $chunk) {
                                            DB::table('reviews')->insert($chunk);
                                        }
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // Log error but don't fail the whole import
                            Log::warning("Failed to update review for product {$product->id}: " . $e->getMessage());
                        }
                    }

                    // Update price and stock by the product ID (we found it by SKU or slug)
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update($updateData);

                    // IMMEDIATELY clear cache for this product to prevent stale data
                    $this->clearSingleProductCache($product->id);

                    $fileUpdatedCount++;

                    // Track updated product ID for Elasticsearch reindexing
                    $updatedProductIds[] = $product->id;
                }

                $totalProductsFound += $fileProductCount;
                $totalProductsUpdated += $fileUpdatedCount;
                $totalProductsSkipped += $fileSkippedCount;

                // Update import job with final statistics
                $importJob->update([
                    'total_items' => $totalItems,
                    'processed_items' => $fileProductCount,
                    'updated_items' => $fileUpdatedCount,
                    'skipped_items' => $fileSkippedCount,
                    'skip_reasons' => $skipReasons,
                ]);
                $importJob->markAsCompleted();

                $this->fastOut("✅ {$filename}: Found={$fileProductCount}, Updated={$fileUpdatedCount}, Skipped={$fileSkippedCount}");
                $this->fastOut("✅ Status: Completed");

            } catch (Throwable $e) {
                // Mark import job as failed
                $importJob->markAsFailed($e->getMessage());

                $this->fastOut("❌ Error processing {$filename}: " . $e->getMessage());
                $this->fastOut("❌ Status: Failed");
                Log::error("Price update import error", ['file' => $filename, 'exception' => $e]);
            }
        }

        $this->fastOut("\n🎉 Price & Review Update Import Completed!");
        $this->fastOut("=========================================");
        $this->fastOut("Total JSON files processed: " . count($jsonFiles));
        $this->fastOut("Total products found in JSON: {$totalProductsFound}");
        $this->fastOut("Total products updated: {$totalProductsUpdated}");
        $this->fastOut("Total products skipped: {$totalProductsSkipped}");

        // Show skip reasons breakdown if there were any skips
        if ($totalProductsSkipped > 0) {
            $this->fastOut("\n📊 Skip Reasons Breakdown:");
            $this->fastOut("-----------------------------------------");
            if ($skipReasons['product_not_found'] > 0) {
                $this->fastOut("  ❌ Product not found in DB: {$skipReasons['product_not_found']}");
            }
            if ($skipReasons['has_active_sale'] > 0) {
                $this->fastOut("  🏷️ Has active sale (price not updated): {$skipReasons['has_active_sale']}");
            }
            if ($skipReasons['no_sku'] > 0) {
                $this->fastOut("  ❌ Missing SKU in JSON: {$skipReasons['no_sku']}");
            }
            if ($skipReasons['no_price'] > 0) {
                $this->fastOut("  ❌ Missing price in JSON: {$skipReasons['no_price']}");
            }
            if ($skipReasons['invalid_price'] > 0) {
                $this->fastOut("  ❌ Invalid price format: {$skipReasons['invalid_price']}");
            }
            if ($skipReasons['price_too_large'] > 0) {
                $this->fastOut("  ❌ Price too large (>999999.99): {$skipReasons['price_too_large']}");
            }
            $this->fastOut("-----------------------------------------");
        }

        $this->fastOut("=========================================");

        // Post-import tasks: Clear caches and reindex to Elasticsearch
        $this->fastOut("\n📊 Starting post-import tasks...");
        $uniqueProductIds = array_unique($updatedProductIds);
        $this->fastOut("Products updated: " . count($uniqueProductIds));

        if (count($uniqueProductIds) > 0) {
            // STEP 1: Bump cache version FIRST to invalidate all product caches immediately
            $this->fastOut("\n🔄 Bumping cache version...");
            $this->bumpProductsCacheVersion();

            // STEP 2: Clear targeted product caches (redundant but ensures thorough clearing)
            $this->fastOut("\n🗑️ Clearing product-related caches...");
            $this->clearProductCachesOptimized($uniqueProductIds);

            // STEP 3: Reindex to Elasticsearch (conditional based on env)
            // Check if auto-indexing is disabled via .env
            $autoIndexingDisabled = filter_var(env('DISABLE_AUTO_ELASTICSEARCH_INDEXING', false), FILTER_VALIDATE_BOOLEAN);

            if ($autoIndexingDisabled) {
                // Auto-indexing is disabled - skip Elasticsearch reindexing
                $this->fastOut("\n⚠️ Elasticsearch auto-indexing is DISABLED via env variable");
                $this->fastOut("   Cache cleared/bumped, but Elasticsearch indexing skipped.");
                $this->fastOut("   You'll need to manually reindex products later.");
            } else {
                // Auto-indexing is enabled - proceed with Elasticsearch reindexing
                $this->fastOut("\n🔍 Starting Elasticsearch reindexing...");
                $this->reindexProductsToElasticsearchOptimized($uniqueProductIds);
            }
        } else {
            $this->fastOut("⚠️ No products to reindex or clear cache for");
        }

        $this->fastOut("✅ Post-import tasks completed!");

        // Delete processed JSON files (cleanup like Fast Import)
        $this->fastOut("\n🗑️ Cleaning up JSON files...");
        $this->deleteProcessedJsonFiles($jsonFiles);

        $this->fastOut("\n✨ All done!");
    }

    /**
     * Calculate price with percentage and rate (matching Python helper.py formula)
     * Formula from helper.py:
     *   converted_price = price_rands / today_rate
     *   percentage_converted = percentage / 100
     *   final_price = (percentage_converted * converted_price) + converted_price
     *
     * Example: price=5399, rate=18, percentage=90
     *   converted_price = 5399 / 18 = 299.94
     *   percentage_converted = 90 / 100 = 0.9
     *   final_price = (0.9 * 299.94) + 299.94 = 269.95 + 299.94 = 569.89
     */
    private function calculatePrice(float $originalPrice, float $percentage, float $rate): float
    {
        // Step 1: Divide price by rate
        $convertedPrice = $originalPrice / $rate;

        // Step 2: Convert percentage to decimal
        $percentageConverted = $percentage / 100;

        // Step 3: Calculate final price
        $finalPrice = ($percentageConverted * $convertedPrice) + $convertedPrice;

        return round($finalPrice, 2);
    }

    /**
     * Parse pretty price format "R 9,999" to numeric value 9999
     */
    private function parsePrettyPrice(string $prettyPrice): ?float
    {
        // Remove currency symbols, spaces, and commas
        // Examples: "R 9,999" -> 9999, "R 1,234.56" -> 1234.56
        $cleaned = preg_replace('/[^\d.]/', '', $prettyPrice);

        if (empty($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Brand cache to avoid repeated database queries
     */
    private static $brandCache = [];

    /**
     * Get or create brand by name
     * Uses static cache to minimize database queries during import
     */
    private function getOrCreateBrand(string $brandName): ?int
    {
        // Check cache first
        if (isset(self::$brandCache[$brandName])) {
            return self::$brandCache[$brandName];
        }

        try {
            $slug = \Illuminate\Support\Str::slug($brandName);

            // Try to find existing brand by name or slug
            $brand = \App\Models\Brand::where('name', $brandName)
                ->orWhere('slug', $slug)
                ->first();

            if (!$brand) {
                // Create new brand if it doesn't exist
                $brand = \App\Models\Brand::create([
                    'name' => $brandName,
                    'slug' => $slug,
                    'status' => 1,
                ]);
            }

            // Cache the brand ID
            self::$brandCache[$brandName] = $brand->id;

            return $brand->id;
        } catch (\Exception $e) {
            // Log error but don't fail the entire import
            Log::error("Error creating/fetching brand: {$brandName}", ['exception' => $e]);
            return null;
        }
    }

    /**
     * Stream parse large JSON files to avoid memory exhaustion
     * Reads the file line by line and extracts JSON objects
     * Returns array with 'data' and 'count' to avoid expensive count() on huge arrays
     */
    private function streamJsonFile(string $filepath): array
    {
        $this->fastOut("🔍 Streaming JSON parse (memory-efficient)...");

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new \Exception("Could not open file: {$filepath}");
        }

        $data = [];
        $buffer = '';
        $depth = 0;
        $inObject = false;
        $objectStart = 0;
        $lineNum = 0;
        $objectsFound = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNum++;
                $buffer .= $line;

                // Count braces to track depth
                for ($i = 0; $i < strlen($line); $i++) {
                    $char = $line[$i];

                    if ($char === '{') {
                        if ($depth === 0) {
                            $inObject = true;
                            $objectStart = strlen($buffer) - strlen($line) + $i;
                        }
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;

                        // Complete object found
                        if ($depth === 0 && $inObject) {
                            $objectEnd = strlen($buffer) - strlen($line) + $i + 1;
                            $objectJson = substr($buffer, $objectStart, $objectEnd - $objectStart);

                            // Try to parse this object
                            $object = json_decode($objectJson, true);
                            if ($object !== null) {
                                $data[] = $object;
                                $objectsFound++;

                                // Show progress every 1000 objects
                                if ($objectsFound % 1000 === 0) {
                                    $this->fastOut("  → Parsed {$objectsFound} objects...");

                                    // Flush output
                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();

                                    // Free memory periodically for HUGE files
                                    if ($objectsFound % 10000 === 0) {
                                        gc_collect_cycles();

                                        // For files with > 100k objects, clear old data to save memory
                                        if ($objectsFound > 100000 && count($data) > 50000) {
                                            $this->fastOut("  ⚠️ Warning: Very large file ({$objectsFound} objects). Consider splitting into smaller files.");
                                        }
                                    }
                                }
                            }

                            $inObject = false;

                            // Clear processed part of buffer to save memory
                            $buffer = substr($buffer, $objectEnd);
                            $objectStart = 0;
                        }
                    }
                }

                // Prevent buffer from growing too large
                if (!$inObject && strlen($buffer) > 10000) {
                    $buffer = '';
                }
            }

            $this->fastOut("✅ Streaming parse complete: {$objectsFound} objects loaded");

        } finally {
            fclose($handle);
        }

        // Return both data and count to avoid expensive count() later
        return [
            'data' => $data,
            'count' => $objectsFound
        ];
    }

    /**
     * Stream parse AND process JSON file on-the-fly for VERY large files
     * Processes each item immediately instead of loading all into memory
     * This avoids memory issues with 40k+ object arrays
     */
    private function streamAndProcessJsonFile(
        string $filepath,
        float $percentage,
        float $todaysRate,
        int &$fileProductCount,
        int &$fileUpdatedCount,
        int &$fileSkippedCount,
        array &$skipReasons,
        array &$updatedProductIds
    ): array {
        $this->fastOut("🔍 Streaming with immediate processing (memory-efficient)...");

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new \Exception("Could not open file: {$filepath}");
        }

        $buffer = '';
        $depth = 0;
        $inObject = false;
        $objectStart = 0;
        $objectsProcessed = 0;
        $localProductCount = 0;
        $localUpdatedCount = 0;
        $localSkippedCount = 0;
        $localUpdatedIds = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $buffer .= $line;

                // Count braces to track depth
                for ($i = 0; $i < strlen($line); $i++) {
                    $char = $line[$i];

                    if ($char === '{') {
                        if ($depth === 0) {
                            $inObject = true;
                            $objectStart = strlen($buffer) - strlen($line) + $i;
                        }
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;

                        // Complete object found - PROCESS IT IMMEDIATELY
                        if ($depth === 0 && $inObject) {
                            $objectEnd = strlen($buffer) - strlen($line) + $i + 1;
                            $objectJson = substr($buffer, $objectStart, $objectEnd - $objectStart);

                            // Parse this single object
                            $item = json_decode($objectJson, true);
                            if ($item !== null) {
                                // PROCESS IT RIGHT NOW (don't store it)
                                $this->processUpdateItem(
                                    $item,
                                    $percentage,
                                    $todaysRate,
                                    $localProductCount,
                                    $localUpdatedCount,
                                    $localSkippedCount,
                                    $skipReasons,
                                    $localUpdatedIds
                                );

                                $objectsProcessed++;

                                // Show progress every 1000 objects
                                if ($objectsProcessed % 1000 === 0) {
                                    $this->fastOut("  → Processed {$objectsProcessed} objects... (Updated: {$localUpdatedCount}, Skipped: {$localSkippedCount})");

                                    // Flush output
                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();

                                    // Aggressive memory cleanup
                                    if ($objectsProcessed % 5000 === 0) {
                                        gc_collect_cycles();
                                    }
                                }
                            }

                            $inObject = false;

                            // Clear processed part of buffer to save memory
                            $buffer = substr($buffer, $objectEnd);
                            $objectStart = 0;
                        }
                    }
                }

                // Prevent buffer from growing too large
                if (!$inObject && strlen($buffer) > 10000) {
                    $buffer = '';
                }
            }

            $this->fastOut("✅ On-the-fly processing complete: {$objectsProcessed} objects processed");

        } finally {
            fclose($handle);
        }

        return [
            'productCount' => $localProductCount,
            'updatedCount' => $localUpdatedCount,
            'skippedCount' => $localSkippedCount,
            'skipReasons' => $skipReasons,
            'updatedIds' => $localUpdatedIds
        ];
    }

    /**
     * Process a single update item (extracted for on-the-fly processing)
     */
    private function processUpdateItem(
        array $item,
        float $percentage,
        float $todaysRate,
        int &$productCount,
        int &$updatedCount,
        int &$skippedCount,
        array &$skipReasons,
        array &$updatedIds
    ): void {
        if (!isset($item['product_views']['buybox_summary'])) {
            return;
        }

        $productCount++;
        $buybox = $item['product_views']['buybox_summary'];
        $coreData = $item['product_views']['core'] ?? [];
        $reviewSummary = $item['product_views']['review_summary'] ?? null;
        $stockSummary = $item['product_views']['stock_availability_summary'] ?? null;

        // Extract brand from core.brand
        $brandName = $coreData['brand'] ?? null;
        $brandId = null;
        if ($brandName && trim($brandName) !== '') {
            $brandId = $this->getOrCreateBrand(trim($brandName));
        }

        // Extract SKU from core.id
        $sku = $coreData['id'] ?? null;
        if (!$sku) {
            $skipReasons['no_sku']++;
            $skippedCount++;
            return;
        }

        // Extract price
        $prettyPrice = $buybox['pretty_price'] ?? $buybox['app_pretty_price'] ?? null;
        if (!$prettyPrice) {
            $skipReasons['no_price']++;
            $skippedCount++;
            return;
        }

        $cleanPrice = $this->parsePrettyPrice($prettyPrice);
        if (!$cleanPrice) {
            $skipReasons['invalid_price']++;
            $skippedCount++;
            return;
        }

        // Calculate final price
        $converted = $cleanPrice / $todaysRate;
        $finalPrice = $converted + ($percentage / 100 * $converted);
        $finalPrice = round($finalPrice, 2);

        // Check if price is too large
        if ($finalPrice > 999999.99) {
            $skipReasons['price_too_large']++;
            $skippedCount++;
            return;
        }

        // Find product by SKU
        $product = \App\Models\Product::where('sku', $sku)->first();
        if (!$product) {
            $product = \App\Models\Product::where('slug', $coreData['slug'] ?? '')->first();
        }

        if (!$product) {
            $skipReasons['product_not_found']++;
            $skippedCount++;
            return;
        }

        // Check if product has a sale_price > 0 (manual promotion/sale set in admin)
        // Skip price update if sale_price > 0 to preserve manual sale pricing
        $hasSalePrice = $product->sale_price > 0;

        // Prepare update data
        $updateData = [
            'updated_at' => now(),
        ];

        // Only update price if product does NOT have a manual sale price
        if (!$hasSalePrice) {
            $updateData['price'] = $finalPrice;
        } else {
            // Skip price update for products with manual sale prices
            $skipReasons['has_manual_sale_price']++;
        }

        // Add brand_id if brand was found/created
        if ($brandId) {
            $updateData['brand_id'] = $brandId;
        }

        // Update stock status
        $detectedStockStatus = $this->determineStockStatus($stockSummary);
        if ($detectedStockStatus) {
            $updateData['stock_status'] = $detectedStockStatus;
        }

        // Update reviews (simplified for on-the-fly processing)
        if ($reviewSummary) {
            try {
                // Delete existing reviews
                DB::table('reviews')
                    ->where('product_id', $product->id)
                    ->whereNull('consumer_id')
                    ->whereNull('deleted_at')
                    ->delete();

                // Create new reviews if distribution exists
                $distribution = $reviewSummary['distribution'] ?? null;
                if (!empty($distribution)) {
                    $ratings = [
                        1 => $distribution['num_1_star_ratings'] ?? 0,
                        2 => $distribution['num_2_star_ratings'] ?? 0,
                        3 => $distribution['num_3_star_ratings'] ?? 0,
                        4 => $distribution['num_4_star_ratings'] ?? 0,
                        5 => $distribution['num_5_star_ratings'] ?? 0,
                    ];

                    $reviewsToInsert = [];
                    $totalCreated = 0;

                    foreach ($ratings as $stars => $count) {
                        if ($count > 0 && $totalCreated < 100) {
                            $createCount = min($count, 100 - $totalCreated);
                            for ($i = 0; $i < $createCount; $i++) {
                                $reviewsToInsert[] = [
                                    'product_id' => $product->id,
                                    'rating' => $stars,
                                    'description' => "Imported {$stars}-star review",
                                    'consumer_id' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                                $totalCreated++;
                            }
                        }
                    }

                    if (!empty($reviewsToInsert)) {
                        foreach (array_chunk($reviewsToInsert, 50) as $chunk) {
                            DB::table('reviews')->insert($chunk);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore review errors
            }
        }

        // Update product
        DB::table('products')
            ->where('id', $product->id)
            ->update($updateData);

        $updatedCount++;
        $updatedIds[] = $product->id;
    }

    /**
     * Index only updated products to Elasticsearch after import
     */
    private function fastIndexProductsToElasticsearch(array $productIds): void
    {
        if (empty($productIds)) {
            $this->fastOut("⚠️ No products to index");
            return;
        }

        try {
            $this->fastOut("🔍 Indexing " . count($productIds) . " products to Elasticsearch...");

            // Use Artisan command to properly reindex products with ALL fields
            // This ensures consistency with the main reindex command
            $this->fastReindexProductsByIds($productIds);

        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Elasticsearch indexing failed: " . $e->getMessage());
            Log::error('Fast import update Elasticsearch indexing failed', ['exception' => $e]);
        }
    }

    /**
     * Reindex specific products by their exact IDs
     * This directly indexes ONLY the specified product IDs, not a range
     * Ensures ALL product fields are indexed correctly
     */
    private function fastReindexProductsByIds(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        try {
            $this->fastOut("Reindexing " . count($productIds) . " specific products to Elasticsearch...");

            // Get Elasticsearch service
            $elasticsearchService = app(\App\Services\ElasticsearchService::class);
            $client = $elasticsearchService::client();
            $indexName = $elasticsearchService::indexName();

            // Process in chunks to avoid memory issues
            $chunkSize = 100;
            $chunks = array_chunk($productIds, $chunkSize);
            $totalIndexed = 0;

            $this->fastOut("Processing in " . count($chunks) . " batches of up to {$chunkSize} products each...");

            foreach ($chunks as $chunkIndex => $idChunk) {
                // Fetch products with ALL relationships (matching ReindexProducts command)
                $products = \App\Models\Product::with([
                    'categories',
                    'tags',
                    'brand',
                    'product_thumbnail',
                    'product_meta_image',
                    'product_galleries',
                    'reviews',
                    'variations.attribute_values',
                ])
                ->whereIn('id', $idChunk)
                ->where('status', 1)
                ->where('is_approved', 1)
                ->get();

                if ($products->isEmpty()) {
                    $this->fastOut("⚠️ No approved products found in chunk " . ($chunkIndex + 1));
                    continue;
                }

                $params = ['body' => []];

                foreach ($products as $product) {
                    // Build the full document (same structure as ReindexProducts command)
                    $document = $this->buildProductDocument($product);

                    $params['body'][] = [
                        'index' => [
                            '_index' => $indexName,
                            '_id' => $product->id,
                        ]
                    ];

                    $params['body'][] = $document;

                    // Bulk index every 50 products
                    if (count($params['body']) >= 100) { // 50 products * 2 lines each = 100 lines
                        $client->bulk($params);
                        $params = ['body' => []];
                    }
                }

                // Index remaining products in this chunk
                if (!empty($params['body'])) {
                    $client->bulk($params);
                }

                $totalIndexed += $products->count();
                $this->fastOut("Reindexed {$totalIndexed} / " . count($productIds) . " products...");

                // Flush output
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Clear memory
                unset($products);
                gc_collect_cycles();
            }

            $this->fastOut("✅ Elasticsearch reindexing completed: {$totalIndexed} specific products reindexed with ALL fields");

        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Elasticsearch reindexing error: " . $e->getMessage());
            Log::error('Fast reindex products error', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Build a complete Elasticsearch document for a product
     * Matches the structure from ReindexProducts command
     */
    private function buildProductDocument($product): array
    {
        // Calculate review ratings distribution
        $reviewRatings = [0, 0, 0, 0, 0]; // [1-star, 2-star, 3-star, 4-star, 5-star]
        $reviewsAvgRating = null;
        $reviewsCount = 0;

        if ($product->reviews && $product->reviews->count() > 0) {
            $ratings = $product->reviews->pluck('rating')->all();
            $reviewsCount = count($ratings);
            $reviewsAvgRating = array_sum($ratings) / $reviewsCount;

            // Calculate distribution
            foreach ($ratings as $rating) {
                $index = (int)$rating - 1; // Convert 1-5 to 0-4
                if ($index >= 0 && $index < 5) {
                    $reviewRatings[$index]++;
                }
            }
        }

        // Build reviews array (full objects)
        $reviews = [];
        if ($product->reviews) {
            foreach ($product->reviews as $review) {
                $reviews[] = [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'description' => $review->description ?? '',
                    'consumer_id' => $review->consumer_id,
                    'created_at' => $review->created_at ? $review->created_at->format('Y-m-d\TH:i:s') : null,
                ];
            }
        }

        // Build variations array
        $variations = [];
        if ($product->variations) {
            foreach ($product->variations as $variation) {
                $attributeValues = [];
                if ($variation->attribute_values) {
                    foreach ($variation->attribute_values as $av) {
                        $attributeValues[] = [
                            'id' => $av->id,
                            'value' => $av->value,
                            'slug' => $av->slug,
                        ];
                    }
                }

                $variations[] = [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'price' => $variation->price,
                    'sale_price' => $variation->sale_price,
                    'sku' => $variation->sku,
                    'stock_status' => $variation->stock_status,
                    'attribute_values' => $attributeValues,
                ];
            }
        }

        // Build product thumbnail
        $productThumbnail = null;
        if ($product->product_thumbnail) {
            $productThumbnail = [
                'id' => $product->product_thumbnail->id,
                'image_url' => $product->product_thumbnail->image_url,
                'original_url' => $product->product_thumbnail->original_url ?? null,
                'file_name' => $product->product_thumbnail->file_name,
            ];
        }

        // Build product galleries
        $productGalleries = [];
        if ($product->product_galleries) {
            foreach ($product->product_galleries as $gallery) {
                $productGalleries[] = [
                    'id' => $gallery->id,
                    'image_url' => $gallery->image_url,
                    'original_url' => $gallery->original_url ?? null,
                ];
            }
        }

        // Build categories array
        $categories = [];
        $categorySlugs = [];
        if ($product->categories) {
            foreach ($product->categories as $category) {
                $categories[] = $category->name;
                $categorySlugs[] = $category->slug;
            }
        }

        // Build tags array
        $tags = [];
        if ($product->tags) {
            foreach ($product->tags as $tag) {
                $tags[] = $tag->name;
            }
        }

        // Build brand object
        $brand = null;
        if ($product->brand) {
            $brand = [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ];
        }

        // Return complete document
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description ?? '',
            'short_description' => $product->short_description ?? '',
            'type' => $product->type,
            'unit' => $product->unit,
            'weight' => $product->weight,
            'quantity' => $product->quantity,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'discount' => $product->discount,
            'is_sale_enable' => $product->is_sale_enable,
            'is_featured' => $product->is_featured,
            'is_trending' => $product->is_trending,
            'is_return' => $product->is_return,
            'is_cod' => $product->is_cod,
            'is_free_shipping' => $product->is_free_shipping,
            'shipping_days' => $product->shipping_days,
            'status' => $product->status,
            'is_approved' => $product->is_approved,
            'stock_status' => $product->stock_status,
            'created_at' => $product->created_at ? $product->created_at->format('Y-m-d\TH:i:s') : null,
            'brand_id' => $product->brand_id,
            'categories' => $categories,
            'category_slugs' => $categorySlugs,
            'tags' => $tags,
            'brand' => $brand,
            'product_thumbnail' => $productThumbnail,
            'product_galleries' => $productGalleries,
            'reviews' => $reviews,
            'review_ratings' => $reviewRatings,
            'reviews_avg_rating' => $reviewsAvgRating,
            'reviews_count' => $reviewsCount,
            'rating_count' => $reviewsCount,
            'variations' => $variations,
            'combined' => $product->name . ' ' . $product->sku,
        ];
    }

    /**
     * Manual Elasticsearch indexing for specific products
     * NOTE: This method is deprecated - it only indexes a subset of fields
     * Use fastReindexProductsByIds() instead which indexes ALL fields correctly
     */
    /* DEPRECATED - DO NOT USE
    private function fastManualElasticsearchIndex(array $productIds): void
    {
        // This method only indexed: id, name, sku, categories, tags, combined, price, is_featured, is_trending, status, is_approved
        // Missing: product_thumbnail, product_galleries, reviews, review_ratings, variations, brand, sale_price, discount, stock_status, etc.
        // Use fastReindexProductsByIds() instead
    }
    */

    /**
     * Show import job history
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $jobs = ImportJob::with('user')
            ->fastImportUpdate() // Only show fast-import-update jobs
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        }

        return view('admin.import-fast-update.history', compact('jobs'));
    }

    /**
     * Show details for a specific batch
     */
    public function batchDetails(Request $request, string $batchId)
    {
        $jobs = ImportJob::forBatch($batchId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($jobs->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found',
                ], 404);
            }

            return redirect()->route('admin.import-fast-update.history')
                ->with('error', 'Batch not found');
        }

        // Calculate batch statistics
        $batchStats = [
            'batch_id' => $batchId,
            'total_files' => $jobs->count(),
            'completed' => $jobs->where('status', 'completed')->count(),
            'failed' => $jobs->where('status', 'failed')->count(),
            'processing' => $jobs->where('status', 'processing')->count(),
            'pending' => $jobs->where('status', 'pending')->count(),
            'total_items' => $jobs->sum('total_items'),
            'updated_items' => $jobs->sum('updated_items'),
            'skipped_items' => $jobs->sum('skipped_items'),
            'total_duration' => $jobs->sum('duration_seconds'),
            'started_at' => $jobs->first()->started_at,
            'completed_at' => $jobs->where('status', '!=', 'pending')->max('completed_at'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'batch' => $batchStats,
                'jobs' => $jobs,
            ]);
        }

        return view('admin.import-fast-update.batch-details', compact('jobs', 'batchStats'));
    }

    /**
     * Determine stock status from JSON data
     *
     * @param array|null $stockSummary
     * @return string|null Returns 'in_stock' or 'out_of_stock', or null if unavailable
     */
    private function determineStockStatus(?array $stockSummary): ?string
    {
        if (!$stockSummary || !isset($stockSummary['status'])) {
            return null;
        }

        $stockStatus = strtolower(trim($stockSummary['status']));

        // Check for "out of stock" variations first (more specific)
        if (str_contains($stockStatus, 'out of stock') ||
            str_contains($stockStatus, 'out-of-stock') ||
            str_contains($stockStatus, 'outofstock') ||
            str_contains($stockStatus, 'out of  stock') ||
            str_contains($stockStatus, 'unavailable') ||
            str_contains($stockStatus, 'discontinued') ||
            str_contains($stockStatus, 'not available') ||
            str_contains($stockStatus, 'sold out') ||
            preg_match('/\bout\b.*\bstock\b/', $stockStatus)) {
            return 'out_of_stock';
        }
        // Check for "in stock" variations
        elseif (str_contains($stockStatus, 'in stock') ||
                str_contains($stockStatus, 'in-stock') ||
                str_contains($stockStatus, 'instock') ||
                str_contains($stockStatus, 'available') ||
                str_contains($stockStatus, 'ships') ||
                preg_match('/\bin\b.*\bstock\b/', $stockStatus)) {
            return 'in_stock';
        }

        // Default to in_stock for unknown statuses
        return 'in_stock';
    }

    /**
     * Resume failed imports
     */
    public function resumeFailed(Request $request)
    {
        $request->validate([
            'batch_id' => ['required', 'string'],
            'percentage' => ['required', 'numeric'],
            'todaysRate' => ['required', 'numeric'],
        ]);

        $batchId = $request->input('batch_id');
        $percentage = (float) $request->input('percentage');
        $todaysRate = (float) $request->input('todaysRate');

        // Get failed and pending jobs for this batch
        $failedJobs = ImportJob::forBatch($batchId)
            ->whereIn('status', ['failed', 'pending'])
            ->get();

        if ($failedJobs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No failed or pending jobs found for this batch',
            ], 404);
        }

        // Extract the filenames to retry
        $selectedFiles = $failedJobs->pluck('filename')->toArray();

        // Create a new batch ID for the retry
        $newBatchId = 'retry_' . $batchId . '_' . date('His');

        // Reset the failed jobs to pending with new batch ID
        foreach ($failedJobs as $job) {
            ImportJob::create([
                'batch_id' => $newBatchId,
                'import_type' => 'fast-import-update',
                'filename' => $job->filename,
                'status' => 'pending',
                'percentage' => $percentage,
                'todays_rate' => $todaysRate,
                'user_id' => auth()->id(),
            ]);
        }

        // Return the new batch ID and selected files for processing
        return response()->json([
            'success' => true,
            'message' => 'Retry batch created',
            'batch_id' => $newBatchId,
            'files_to_retry' => count($selectedFiles),
            'redirect_to_run' => true,
            'run_params' => [
                'percentage' => $percentage,
                'todaysRate' => $todaysRate,
                'selectedFiles' => $selectedFiles,
            ],
        ]);
    }

    /**
     * Get import statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_batches' => ImportJob::select('batch_id')->distinct()->count(),
            'total_imports' => ImportJob::count(),
            'completed' => ImportJob::completed()->count(),
            'failed' => ImportJob::failed()->count(),
            'processing' => ImportJob::where('status', 'processing')->count(),
            'pending' => ImportJob::pending()->count(),
            'total_items_processed' => ImportJob::sum('processed_items'),
            'total_items_updated' => ImportJob::sum('updated_items'),
            'total_items_skipped' => ImportJob::sum('skipped_items'),
            'average_duration' => round(ImportJob::whereNotNull('duration_seconds')->avg('duration_seconds'), 2),
        ];

        // Recent batches
        $recentBatches = ImportJob::select('batch_id', DB::raw('MIN(created_at) as started_at'), DB::raw('COUNT(*) as file_count'))
            ->groupBy('batch_id')
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'recent_batches' => $recentBatches,
        ]);
    }

    /**
     * Delete processed JSON files after import completion
     */
    private function deleteProcessedJsonFiles(array $jsonFiles): void
    {
        try {
            $deletedCount = 0;
            $failedCount = 0;

            foreach ($jsonFiles as $filepath) {
                try {
                    if (file_exists($filepath)) {
                        unlink($filepath);
                        $deletedCount++;
                        $this->fastOut("  ✅ Deleted: " . basename($filepath));
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->fastOut("  ❌ Failed to delete: " . basename($filepath));
                    Log::warning("Failed to delete JSON file", [
                        'file' => $filepath,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($deletedCount > 0) {
                $this->fastOut("✅ Deleted {$deletedCount} JSON file(s)");
            }
            if ($failedCount > 0) {
                $this->fastOut("⚠️ Failed to delete {$failedCount} JSON file(s)");
            }
        } catch (\Exception $e) {
            $this->fastOut("⚠️ Error during file cleanup: " . $e->getMessage());
            Log::error('Error deleting JSON files in Fast Import Update', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear cache for a single product immediately (called during update loop)
     * This ensures cache is cleared RIGHT AWAY when product is updated
     */
    private function clearSingleProductCache(int $productId): void
    {
        try {
            $keysToForget = [
                "product:{$productId}",
                "product_details:{$productId}",
                "product_with_relations:{$productId}",
                "api_product_{$productId}",
            ];

            foreach ($keysToForget as $key) {
                \Cache::forget($key);
            }
        } catch (\Exception $e) {
            // Silent fail - don't stop import if cache clearing fails
            Log::warning("Failed to clear cache for product {$productId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear product-related caches for updated products (minimal approach)
     *
     * IMPORTANT: We do NOT clear all caches as that affects ALL users.
     * Products are served from Elasticsearch, so reindexing is the main cache invalidation.
     * This method only clears specific product caches if they exist.
     */
    private function clearProductCachesOptimized(array $productIds)
    {
        try {
            $this->fastOut("Clearing targeted product caches...");

            // Process in chunks to avoid memory issues
            $chunks = array_chunk($productIds, 100);
            $clearedCount = 0;

            foreach ($chunks as $chunk) {
                foreach ($chunk as $productId) {
                    // Only clear specific product caches that might exist
                    $keysToForget = [
                        "product:{$productId}",
                        "product_details:{$productId}",
                        "product_with_relations:{$productId}",
                        "api_product_{$productId}",
                    ];

                    foreach ($keysToForget as $key) {
                        if (\Cache::has($key)) {
                            \Cache::forget($key);
                            $clearedCount++;
                        }
                    }
                }

                // Small delay to prevent cache stampede
                if (count($productIds) > 1000) {
                    usleep(5000); // 5ms
                }
            }

            $this->fastOut("✅ Cache cleared: {$clearedCount} keys removed");

        } catch (\Exception $e) {
            $this->fastOut("⚠️ Cache clearing failed: " . $e->getMessage());
            Log::error('Error clearing product caches', [
                'error' => $e->getMessage(),
                'product_count' => count($productIds)
            ]);
        }
    }

    /**
     * Bump products cache version to invalidate ALL product caches
     * This is the proper way to invalidate caches without Cache::flush()
     */
    private function bumpProductsCacheVersion(): void
    {
        try {
            $currentVersion = (int) \Cache::get('products_cache_version', 1);
            $newVersion = $currentVersion + 1;
            \Cache::put('products_cache_version', $newVersion, now()->addDays(365));

            $this->fastOut("✅ Cache version bumped: v{$currentVersion} → v{$newVersion}");
        } catch (\Exception $e) {
            $this->fastOut("⚠️ Cache version bump failed: " . $e->getMessage());
            Log::error('Error bumping products cache version', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reindex updated products to Elasticsearch (optimized for large batches)
     */
    private function reindexProductsToElasticsearchOptimized(array $productIds)
    {
        try {
            if (!env('USE_ELASTIC_SEARCH', false)) {
                $this->fastOut("⚠️ Elasticsearch disabled (USE_ELASTIC_SEARCH=false), skipping reindex");
                return;
            }

            if (empty($productIds)) {
                $this->fastOut("⚠️ No product IDs provided for indexing");
                return;
            }

            // Test Elasticsearch connection FIRST before processing
            $this->fastOut("Testing Elasticsearch connection...");
            try {
                // Set a temporary time limit for connection test (30 seconds)
                $originalTimeLimit = ini_get('max_execution_time');
                set_time_limit(30);

                $testService = app(\App\Services\ElasticsearchService::class);
                $testClient = $testService::client();

                // Try to ping Elasticsearch with a short timeout
                $pingResult = $testClient->ping();

                // Restore original time limit
                set_time_limit((int)$originalTimeLimit);

                $this->fastOut("✅ Elasticsearch connection successful (ping: " . ($pingResult ? 'OK' : 'FAILED') . ")");

                if (!$pingResult) {
                    throw new \Exception("Elasticsearch ping returned false");
                }
            } catch (\Exception $e) {
                // Restore original time limit even on error
                if (isset($originalTimeLimit)) {
                    set_time_limit((int)$originalTimeLimit);
                }

                $this->fastOut("❌ Elasticsearch connection failed: " . $e->getMessage());
                $this->fastOut("⚠️ Skipping indexing - Elasticsearch is not reachable");
                Log::error("Elasticsearch connection test failed in Fast Import Update", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return;
            }

            $totalProducts = count($productIds);

            // Always use direct indexing with progress feedback
            // (Artisan command blocks and shows no progress)
            $this->fastOut("Preparing to reindex {$totalProducts} products...");
            $indexed = 0;
            $failed = 0;

            try {
                $this->fastOut("Connecting to Elasticsearch...");

                // Create Elasticsearch client with timeout protection
                $elasticsearchService = app(\App\Services\ElasticsearchService::class);
                $client = $elasticsearchService::client();
                $indexName = $elasticsearchService::indexName();

                $this->fastOut("✅ Connected to Elasticsearch index: {$indexName}");
                $this->fastOut("Fetching products from database...");

                // Fetch products with relationships BEFORE indexing (same pattern as Fast Import)
                // For large datasets, fetch in chunks to avoid memory issues
                if ($totalProducts > 500) {
                    $this->fastOut("Large dataset detected - will process in chunks");

                    $productChunks = array_chunk($productIds, 100);
                    $totalChunks = count($productChunks);

                    foreach ($productChunks as $chunkIndex => $chunkIds) {
                        $this->fastOut("📦 Processing chunk " . ($chunkIndex + 1) . "/{$totalChunks} (" . count($chunkIds) . " products)...");

                        $products = \App\Models\Product::with(['categories', 'tags', 'brand', 'product_thumbnail', 'product_meta_image', 'product_galleries'])
                            ->whereIn('id', $chunkIds)
                            ->where('status', 1)
                            ->where('is_approved', 1)
                            ->get();

                        foreach ($products as $product) {
                            try {
                                $client->index([
                                    'index' => $indexName,
                                    'id' => $product->id,
                                    'body' => $product->toSearchableArray(),
                                ]);
                                $indexed++;
                            } catch (\Exception $e) {
                                $failed++;
                                Log::warning("Failed to reindex product {$product->id}", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        $this->fastOut("✅ Chunk " . ($chunkIndex + 1) . "/{$totalChunks} completed - Indexed: {$indexed}, Failed: {$failed}");

                        // Free memory
                        unset($products);
                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                    }
                } else {
                    // For smaller datasets, process all at once
                    $products = \App\Models\Product::with(['categories', 'tags', 'brand', 'product_thumbnail', 'product_meta_image', 'product_galleries'])
                        ->whereIn('id', $productIds)
                        ->where('status', 1)
                        ->where('is_approved', 1)
                        ->get();

                    $this->fastOut("✅ Fetched {$products->count()} products to index");

                    if ($products->isEmpty()) {
                        $this->fastOut("⚠️ No approved/active products found to index");
                        return;
                    }

                    $this->fastOut("Starting indexing...");
                    $progressInterval = max(1, (int)ceil($products->count() / 10)); // Show progress 10 times

                    foreach ($products as $index => $product) {
                        try {
                            $client->index([
                                'index' => $indexName,
                                'id' => $product->id,
                                'body' => $product->toSearchableArray(),
                            ]);
                            $indexed++;

                            // Progress update
                            if (($indexed % $progressInterval === 0) || ($indexed === $products->count())) {
                                $percent = round(($indexed / $products->count()) * 100);
                                $this->fastOut("⏳ Indexing: {$percent}% ({$indexed}/{$products->count()})");
                            }
                        } catch (\Exception $e) {
                            $failed++;
                            if ($failed <= 3) { // Only log first 3 failures to avoid spam
                                $this->fastOut("⚠️ Failed to index product {$product->id}: " . $e->getMessage());
                            }
                            Log::warning("Failed to reindex product {$product->id}", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->fastOut("❌ Elasticsearch connection/indexing failed: " . $e->getMessage());
                Log::error("Fast Import Update Elasticsearch indexing failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'product_count' => $totalProducts
                ]);

                // Don't throw - continue with import completion
                $this->fastOut("⚠️ Continuing without Elasticsearch indexing");
                return;
            }

            $this->fastOut("✅ Elasticsearch indexing completed!");
            $this->fastOut("✅ Successfully indexed: {$indexed}");
            if ($failed > 0) {
                $this->fastOut("⚠️ Failed: {$failed}");
            }

        } catch (\Exception $e) {
            $this->fastOut("⚠️ Elasticsearch reindexing failed: " . $e->getMessage());
            Log::error('Error during Elasticsearch reindexing for fast import update', [
                'error' => $e->getMessage(),
                'product_count' => count($productIds)
            ]);
        }
    }

    /**
     * Map distribution centres to estimated delivery days for "In stock" products
     */
    private function getEstimatedDeliveryFromDistributionCentres(array $distributionCentres): string
    {
        // Extract centre codes (CPT, DBN, JHB)
        $centres = [];
        foreach ($distributionCentres as $centre) {
            if (isset($centre['text']) && !empty($centre['text'])) {
                $centres[] = strtoupper(trim($centre['text']));
            }
        }

        // Sort centres alphabetically for consistent matching
        sort($centres);
        $centresKey = implode(', ', $centres);

        // Mapping of distribution centres to delivery days
        $deliveryMapping = [
            'CPT, DBN, JHB' => 'Ships in 3-5 work days',
            'CPT, DBN' => 'Ships in 4-6 work days',
            'CPT' => 'Ships in 4-6 work days',
            'DBN' => 'Ships in 4-6 work days',
            'JHB' => 'Ships in 3-5 work days',
            'CPT, JHB' => 'Ships in 3-5 work days',
            'DBN, JHB' => 'Ships in 3-5 work days',
        ];

        // Return mapped delivery time or default
        return $deliveryMapping[$centresKey] ?? 'Ships in 5-6 work days';
    }

    /**
     * Increment shipping days in a text string by a specified number
     * Example: "Ships in 4 - 6 work days" + 2 = "Ships in 6 - 8 work days"
     */
    private function incrementShippingDays(string $text, int $increment): ?string
    {
        // Pattern to match "Ships in X - Y work days" or "Ships in X-Y days" etc.
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $text, $matches)) {
            $firstDay = (int)$matches[1] + $increment;
            $secondDay = (int)$matches[2] + $increment;

            // Return in format: "Ships in X-Y work days"
            return "Ships in {$firstDay}-{$secondDay} work days";
        }

        // Pattern to match single number "Ships in X days"
        if (preg_match('/(\d+)/', $text, $matches)) {
            $days = (int)$matches[1] + $increment;
            return "Ships in {$days} work days";
        }

        // If no pattern matched, return original text
        return $text;
    }
}




