<?php

namespace App\Http\Controllers;

use App\Models\ImportJob;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportFastController extends Controller
{
    // Fast import settings and counters
    private int $fastChunkSize = 1000; // Increased for better performance with large files
    private int $fastTotalJsonProducts = 0;
    private int $fastProductsCountBefore = 0;
    private float $fastPercentage = 1.0;
    private float $fastTodaysRate = 18.0;
    private array $fastImportedProductIds = []; // Track imported product IDs for selective indexing

    /**
     * Show upload + run interface
     */
    public function index()
    {
        // Get existing JSON files
        $dir = storage_path('app/jsonfiles');
        $existingFiles = [];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.json') ?: [];
            foreach ($files as $file) {
                $basename = basename($file);
                if ($basename !== 'extracted_categories.json') {
                    $existingFiles[] = [
                        'name' => $basename,
                        'size' => filesize($file),
                        'date' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            // Sort by date descending (newest first)
            usort($existingFiles, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }

        return view('import-fast', compact('existingFiles'));
    }


    /**
     * Delete selected JSON files
     */
    public function deleteFiles(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['required', 'string'],
        ]);

        $files = $request->input('files');
        $dir = storage_path('app/jsonfiles');
        $deleted = 0;
        $failed = [];

        foreach ($files as $filename) {
            // Sanitize filename to prevent directory traversal
            $safeFilename = basename($filename);
            $filepath = $dir . '/' . $safeFilename;

            if (file_exists($filepath) && is_file($filepath)) {
                try {
                    File::delete($filepath);
                    $deleted++;
                } catch (\Throwable $e) {
                    $failed[] = $safeFilename;
                }
            } else {
                $failed[] = $safeFilename;
            }
        }

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'failed' => $failed,
            'message' => "Deleted {$deleted} file(s)" . (count($failed) > 0 ? ", failed: " . count($failed) : ""),
        ]);
    }

    /**
     * Handle JSON/ZIP upload into storage/app/jsonfiles
     */
    public function upload(Request $request)
    {
        $request->validate([
            'jsonfile'   => ['required', 'file', 'mimes:json,txt,zip', 'max:512000'], // ~500MB; supports ZIP now
        ]);

        $dir = storage_path('app/jsonfiles');
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $request->file('jsonfile');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $finalName = $name . '-' . date('Ymd-His') . '.' . $extension;

        $file->move($dir, $finalName);

        // Extract ZIP file if it's a ZIP
        if ($extension === 'zip') {
            return $this->extractZipFile($dir . '/' . $finalName, $dir);
        }

        return response()->json([
            'ok' => true,
            'stored_as' => $finalName,
            'path' => 'jsonfiles/' . $finalName,
        ]);
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'uploadId'    => ['required', 'string', 'max:100'],
            'fileName'    => ['required', 'string', 'max:255'],
            'chunkIndex'  => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'chunk'       => ['required', 'file'],
        ]);

        $uploadId    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $request->input('uploadId'));
        $chunkIndex  = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');

        $tmpDir = storage_path('app/jsonfiles/tmp/' . $uploadId);

        // Safe when multiple chunks arrive concurrently
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $request->file('chunk')->move(
            $tmpDir,
            $chunkIndex . '.part'
        );

        return response()->json([
            'ok'       => true,
            'received' => $chunkIndex,
            'total'    => $totalChunks,
        ]);
    }

    /**
     * Accept a chunk and store it under storage/app/jsonfiles/tmp/{uploadId}/{index}.part
     */
    public function uploadChunk_(Request $request)
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

        $tmpDir = storage_path('app/jsonfiles/tmp/'.$uploadId);
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
     * Concatenate all chunks into final file under storage/app/jsonfiles
     */
    public function uploadComplete(Request $request)
    {
        $request->validate([
            'uploadId'    => ['required','string','max:100'],
            'fileName'    => ['required','string','max:255'],
            'totalChunks' => ['required','integer','min:1'],
        ]);

        $uploadId    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $request->input('uploadId'));
        $fileName    = (string) $request->input('fileName');
        $totalChunks = (int) $request->input('totalChunks');

        $tmpDir = storage_path('app/jsonfiles/tmp/'.$uploadId);
        if (!is_dir($tmpDir)) {
            return response()->json(['success' => false, 'message' => 'Upload session not found.'], 422);
        }

        // Detect file extension
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $safe = Str::slug($base) ?: 'import';
        $finalName = $safe.'-'.date('Ymd-His').'.'.$extension;
        $finalPath = storage_path('app/jsonfiles/'.$finalName);
        $finalDir  = dirname($finalPath);
        if (!is_dir($finalDir)) { File::makeDirectory($finalDir, 0755, true); }

        $out = fopen($finalPath, 'wb');
        if ($out === false) {
            return response()->json(['success' => false, 'message' => 'Unable to create final file.'], 500);
        }
        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $part = $tmpDir.DIRECTORY_SEPARATOR.$i.'.part';
                if (!file_exists($part)) {
                    fclose($out);
                    @unlink($finalPath);
                    return response()->json(['success' => false, 'message' => "Missing chunk #$i"], 422);
                }
                $in = fopen($part, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } finally {
            fclose($out);
        }

        // Cleanup tmp dir
        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                @unlink($tmpDir.DIRECTORY_SEPARATOR.$i.'.part');
            }
            @rmdir($tmpDir);
        } catch (\Throwable $__) {}

        // Extract ZIP file if it's a ZIP
        if ($extension === 'zip') {
            return $this->extractZipFile($finalPath, $finalDir);
        }

        return response()->json([
            'success'   => true,
            'stored_as' => $finalName,
            'path'      => 'jsonfiles/'.$finalName,
        ]);
    }

    /**
     * Extract ZIP file and return list of extracted JSON files
     */
    private function extractZipFile(string $zipPath, string $extractToDir): \Illuminate\Http\JsonResponse
    {
        if (!class_exists('ZipArchive')) {
            return response()->json(['success' => false, 'message' => 'ZIP extension not available'], 500);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return response()->json(['success' => false, 'message' => 'Failed to open ZIP file'], 422);
        }

        $extractedFiles = [];
        $zipBaseName = basename($zipPath, '.zip');

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
                'success' => true,
                'extracted' => true,
                'files_extracted' => count($extractedFiles),
                'extracted_files' => $extractedFiles,
                'message' => 'ZIP extracted: ' . count($extractedFiles) . ' JSON file(s)',
                'stored_as' => implode(', ', $extractedFiles),
            ]);

        } catch (\Throwable $e) {
            $zip->close();
            return response()->json(['success' => false, 'message' => 'Extraction failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Run the import command and stream its output live back to the browser.
     * Uses Symfony Process and a StreamedResponse for incremental output.
     */
    public function run(Request $request)
    {
        $request->validate([
            'percentage' => ['required', 'numeric', 'gt:0'],
            'todaysRate' => ['required', 'numeric', 'gt:0'],
            'selectedFiles' => ['nullable', 'string'],
        ]);

        $percentage = $request->input('percentage');
        $todaysRate = $request->input('todaysRate');
        $selectedFilesJson = $request->input('selectedFiles');
        $selectedFiles = $selectedFilesJson ? json_decode($selectedFilesJson, true) : [];

        // Generate a unique batch ID for this import session
        $batchId = 'import_' . date('Ymd_His') . '_' . Str::random(8);

        // Audit trail — single entry for the entire fast import (not per product)
        try {
            $fileList = $selectedFiles ? implode(', ', $selectedFiles) : 'all JSON files';
            ActivityLogger::make()->useLog('import')->event('created')
                ->log("Fast Import started — Batch: {$batchId}, Files: {$fileList}, Rate: {$todaysRate}, %: {$percentage}");
        } catch (\Throwable) {}

        $response = new StreamedResponse(function () use ($percentage, $todaysRate, $selectedFiles, $batchId) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            // Try to continue even if client disconnects
            @ignore_user_abort(true);

            // Send a small prelude to start streaming on some servers
            echo str_repeat(" \n", 10);
            flush();

            // Show batch ID to user
            $this->fastOut("🆔 Batch ID: {$batchId}");
            $this->fastOut("📝 You can use this ID to track or resume this import later");
            $this->fastOut("");

            // Log start details (pid may not be available on all SAPI)
            try {
                $pid = function_exists('getmypid') ? getmypid() : null;

            } catch (\Throwable $__) {}

            // Register a shutdown handler to catch fatal errors
            register_shutdown_function(function () {
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
                // Run the fast import directly (no artisan subprocess)
                $this->fastRunImport((float)$percentage, (float)$todaysRate, $selectedFiles, $batchId);
            } catch (Throwable $e) {
                // Emit error to client and log full stacktrace
                try {
                    $this->fastOut("❌ Exception: " . $e->getMessage());
                } catch (Throwable $__) {
                    // ignore
                }
                Log::error('ImportFastController run() exception', ['exception' => $e]);
            }

            // Log finish and peak memory
            try {
                $peak = function_exists('memory_get_peak_usage') ? memory_get_peak_usage(true) : null;

            } catch (\Throwable $__) {}
        });

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');

        return $response;
    }

    // ===================== Fast Import (direct) =====================

    private function fastOut(string $text): void
    {
        $clean = preg_replace('/\e\[[;\d]*m/', '', $text);
        echo nl2br(e($clean)) . "\n";

        // Log to Laravel log with a clear prefix
        try {

        } catch (\Throwable $__) {
            // ignore logging failures
        }

        // Force output to be sent immediately
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function fastRunImport(float $percentage = null, float $todaysRate = null, array $selectedFiles = [], string $batchId = null): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->fastPercentage = (float) $percentage;
        $this->fastTodaysRate = (float) $todaysRate;

        if ($this->fastPercentage <= 0 || $this->fastTodaysRate <= 0) {
            $this->fastOut("❌ Invalid percentage or today's rate. Both must be > 0.");
            return;
        }

        $this->fastProductsCountBefore = \Illuminate\Support\Facades\DB::table('products')->count();
        $dir = storage_path('app/jsonfiles');
        if (!is_dir($dir)) {
            $this->fastOut("❌ Directory does not exist: $dir");
            return;
        }

        // If specific files are selected, process only those; otherwise process all
        if (!empty($selectedFiles)) {
            $files = [];
            foreach ($selectedFiles as $filename) {
                $filepath = $dir . '/' . $filename;
                if (file_exists($filepath)) {
                    $files[] = $filepath;
                } else {
                    $this->fastOut("⚠️ File not found: $filename");
                }
            }
        } else {
            $files = glob($dir . '/*.json') ?: [];
        }

        if (empty($files)) {
            $this->fastOut("📁 No JSON files to process.");
            return;
        }

        $totalFiles = count($files);
        $this->fastOut("📋 Processing {$totalFiles} selected file(s)...\n");

        // Create import job records for all files
        $importJobs = [];
        $jobCount = 0;
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            if ($filename === 'extracted_categories.json') {
                $this->fastOut("⏭️ Skipping: {$filename}");
                continue; // Skip this file
            }

            $importJobs[$filename] = ImportJob::create([
                'batch_id' => $batchId,
                'import_type' => 'fast-import',
                'filename' => $filename,
                'status' => 'pending',
                'percentage' => $percentage,
                'todays_rate' => $todaysRate,
                'user_id' => auth()->id() ?? null,
            ]);
            $jobCount++;
            $this->fastOut("📝 Created tracking record #{$jobCount}: {$filename}");
        }
        $this->fastOut("✅ Created {$jobCount} tracking records for selected files\n");

        // CRITICAL FIX: Queue all files for background processing instead of synchronous
        // This allows the HTTP request to return immediately, keeping the site responsive
        $this->fastOut("🚀 Queueing files for background processing...\n");

        $totalJobsQueued = 0;
        $filesProcessed = 0;
        $filesFailed = 0;

        foreach ($files as $file) {
            $filename = basename($file);

            if ($filename === 'extracted_categories.json') {
                $this->fastOut('⏭️ Skipping: ' . $filename);
                continue;
            }

            $filesProcessed++;
            $importJob = $importJobs[$filename] ?? null;

            if (!$importJob) {
                $this->fastOut("⚠️ [{$filesProcessed}] {$filename}: No tracking record found, skipping");
                $filesFailed++;
                continue;
            }

            $this->fastOut("\n📦 [{$filesProcessed}] Processing: {$filename}");

            // Read and parse JSON file to get chunks
            try {
                $jsonContent = \Illuminate\Support\Facades\File::get($file);
                $productsArr = json_decode($jsonContent, true);

                if (!is_array($productsArr)) {
                    $importJob->markAsFailed('Invalid JSON format');
                    $this->fastOut("   ❌ Invalid JSON format");
                    $filesFailed++;
                    continue;
                }

                if (isset($productsArr['core']) && isset($productsArr['core']['id'])) {
                    $productsArr = [$productsArr];
                }

                $totalProducts = count($productsArr);
                $importJob->update(['total_items' => $totalProducts]);

                // Split into chunks of 500 products per job
                $chunkSize = 500;
                $chunks = array_chunk($productsArr, $chunkSize);
                $totalChunks = count($chunks);

                $this->fastOut("   📊 {$totalProducts} products → {$totalChunks} chunk(s)");

                // Dispatch each chunk as a separate background job
                foreach ($chunks as $chunkIndex => $chunk) {
                    \App\Jobs\ProcessFastImportChunk::dispatch(
                        $importJob->id,
                        $file,
                        $chunkIndex,
                        $totalChunks,
                        $chunk,
                        (float)$percentage,
                        (float)$todaysRate
                    )->onQueue('import');

                    $totalJobsQueued++;
                }

                $this->fastOut("   ✅ Queued {$totalChunks} job(s) for {$filename}");

            } catch (\Throwable $e) {
                $importJob->markAsFailed($e->getMessage());
                $this->fastOut("   ❌ Failed to queue: " . $e->getMessage());
                Log::error('Failed to queue import file', ['file' => $filename, 'exception' => $e]);
                $filesFailed++;
                continue;
            }
        }

        $this->fastOut("\n" . str_repeat("=", 60));
        $this->fastOut("📊 IMPORT QUEUE SUMMARY");
        $this->fastOut(str_repeat("=", 60));
        $this->fastOut("📁 Total Files Selected: " . count($files));
        $this->fastOut("✅ Files Successfully Queued: " . ($filesProcessed - $filesFailed));
        $this->fastOut("❌ Files Failed: " . $filesFailed);
        $this->fastOut("🔄 Total Background Jobs Queued: {$totalJobsQueued}");
        $this->fastOut("🆔 Batch ID: {$batchId}");
        $this->fastOut(str_repeat("=", 60));
        $this->fastOut("\n⚡ IMPORTANT: Imports are now running in the background!");
        $this->fastOut("✅ Your site remains fully responsive");
        $this->fastOut("📊 Check import history to see progress for each file");
        $this->fastOut("ℹ️  Queue workers must be running: php artisan queue:work --queue=import");
        $this->fastOut("\n💡 TIP: Each file has its own ImportJob record in the history");
        $this->fastOut("💡 All files share the same Batch ID: {$batchId}");
        $this->fastOut("\nYou can safely close this page. Imports will continue in the background.");
    }

    private function fastProcessFile(string $filePath): array
    {
        $jsonContent = \Illuminate\Support\Facades\File::get($filePath);
        $productsArr = json_decode($jsonContent, true);
        if (!is_array($productsArr)) {
            $this->fastOut('❌ Invalid JSON: ' . basename($filePath));
            return ['total' => 0, 'processed' => 0, 'created' => 0, 'skipped' => 0];
        }
        if (isset($productsArr['core']) && isset($productsArr['core']['id'])) {
            $productsArr = [$productsArr];
        }

        $totalProducts = count($productsArr);
        $this->fastTotalJsonProducts += $totalProducts;

        $this->fastOut("[parsing] Processing {$totalProducts} products in batches to avoid blocking...");

        // CRITICAL FIX: Process in batches of 500 products
        // This prevents memory exhaustion and allows incremental database commits
        $batchSize = 500;
        $batches = array_chunk($productsArr, $batchSize);
        $totalProcessed = 0;

        foreach ($batches as $batchIndex => $batch) {
            $this->fastOut("[batch] Processing batch " . ($batchIndex + 1) . "/" . count($batches) . " (" . count($batch) . " products)");

            $result = $this->fastProcessProductBatch($batch, $filePath);
            $totalProcessed += $result['processed'];

            // Clear memory after each batch
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            // Flush output to keep connection alive
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            // Tiny delay between batches to allow other requests
            usleep(10000); // 10ms between batches
        }

        $this->fastOut("[parsing] Completed processing all {$totalProducts} products");

        // Return statistics
        return [
            'total' => $totalProducts,
            'processed' => $totalProcessed,
            'created' => $totalProcessed,
            'skipped' => 0,
        ];
    }

    /**
     * Public method for jobs to process product batches
     * Called from ProcessFastImportChunk job
     */
    public function processProductBatchFromJob(array $productsArr, float $percentage, float $todaysRate): array
    {
        // Set the instance variables needed for processing
        $this->fastPercentage = $percentage;
        $this->fastTodaysRate = $todaysRate;

        // Use the existing batch processing method
        // Note: filePath is not critical for batch processing, just for logging
        return $this->fastProcessProductBatch($productsArr, '');
    }

    /**
     * Public method for post-processing after all chunks complete
     * Called from ProcessFastImportChunk job (last chunk only)
     */
    public function postProcessImport(int $importJobId): void
    {
        try {
            $importJob = \App\Models\ImportJob::find($importJobId);

            if (!$importJob) {
                Log::error('Import job not found for post-processing', ['id' => $importJobId]);
                return;
            }

            // Get all jobs in this batch for statistics
            $batchId = $importJob->batch_id;
            $allJobs = \App\Models\ImportJob::where('batch_id', $batchId)->get();

            // Calculate batch statistics with detailed job information
            $batchStats = [
                'total_files' => $allJobs->count(),
                'total_jobs' => $allJobs->count(),
                'total_items' => $allJobs->sum('total_items'),
                'processed_items' => $allJobs->sum('processed_items'),
                'updated_items' => $allJobs->sum('updated_items'),
                'skipped_items' => $allJobs->sum('skipped_items'),
                'completed_jobs' => $allJobs->where('status', 'completed')->count(),
                'failed_jobs' => $allJobs->where('status', 'failed')->count(),
                // Include detailed job information for email template
                'jobs' => $allJobs->map(function ($job) {
                    return [
                        'filename' => $job->filename,
                        'total_items' => $job->total_items ?? 0,
                        'processed_items' => $job->processed_items ?? 0,
                        'updated_items' => $job->updated_items ?? 0,
                        'skipped_items' => $job->skipped_items ?? 0,
                        'status' => $job->status,
                    ];
                })->toArray(),
            ];

            // Clear product caches
            $this->fastClearProductCaches();

            // Index imported products to Elasticsearch (conditional based on env)
            $elasticsearchStats = [
                'attempted' => 0,
                'indexed' => 0,
                'failed' => 0,
                'duration' => 0,
                'status' => 'skipped',
                'error' => null,
            ];

            // Check if auto-indexing is disabled via .env
            $autoIndexingDisabled = filter_var(env('DISABLE_AUTO_ELASTICSEARCH_INDEXING', false), FILTER_VALIDATE_BOOLEAN);

            if ($autoIndexingDisabled) {
                // Auto-indexing is disabled - only bump cache, skip Elasticsearch
                $elasticsearchStats['status'] = 'disabled';
                $elasticsearchStats['error'] = 'Auto-indexing disabled via DISABLE_AUTO_ELASTICSEARCH_INDEXING=true';
            } else {
                // Auto-indexing is enabled - proceed with Elasticsearch indexing
                try {

                    $esStartTime = microtime(true);

                    // Get product IDs that were updated in the last 2 hours (should cover the import)
                    $recentlyUpdatedProducts = \Illuminate\Support\Facades\DB::table('products')
                        ->where('updated_at', '>=', now()->subHours(2))
                        ->pluck('id')
                        ->toArray();

                    $elasticsearchStats['attempted'] = count($recentlyUpdatedProducts);

                    if (!empty($recentlyUpdatedProducts)) {

                        // Call indexing and capture results
                        $indexingResult = $this->fastIndexProductsToElasticsearch($recentlyUpdatedProducts);

                        $elasticsearchStats['status'] = 'completed';
                        $elasticsearchStats['indexed'] = $indexingResult['indexed'] ?? count($recentlyUpdatedProducts);
                        $elasticsearchStats['failed'] = $indexingResult['failed'] ?? 0;
                        $elasticsearchStats['duration'] = round(microtime(true) - $esStartTime, 2);
                    } else {
                        Log::warning('No recently updated products found for indexing');
                        $elasticsearchStats['status'] = 'no_products';
                    }
                } catch (\Throwable $indexError) {
                    Log::error('Failed to index products to Elasticsearch', [
                        'batch_id' => $batchId,
                        'error' => $indexError->getMessage()
                    ]);
                    $elasticsearchStats['status'] = 'failed';
                    $elasticsearchStats['error'] = $indexError->getMessage();
                    // Don't fail the whole post-processing if indexing fails
                }
            }

            // Add Elasticsearch stats to batch stats
            $batchStats['elasticsearch'] = $elasticsearchStats;

            // Send email notification to admin
            try {
                \Illuminate\Support\Facades\Notification::route('mail', 'admin@raines.africa')
                    ->notify(new \App\Notifications\FastImportCompleted($importJob, $batchStats));

            } catch (\Throwable $emailError) {
                Log::error('Failed to send import completion email', [
                    'job_id' => $importJobId,
                    'error' => $emailError->getMessage()
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Post-processing failed', [
                'job_id' => $importJobId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process a batch of products (called by fastProcessFile or jobs)
     */
    private function fastProcessProductBatch(array $productsArr, string $filePath): array
    {
        $now = now()->toDateTimeString();

        $productsData = $attachmentsData = $attributesData = $tagsData = $productAttributes = [];
        $attributeValuesData = $variationsData = $reviewsData = $productImages = $productTags = [];
        $variationAttributeValues = $thumbnailUpdates = [];
        $categoriesData = []; $categoryLinks = []; $categoryParentHints = [];
        $reEnableSkus = [];

        $seenProducts = $seenAttachments = $seenAttributes = $seenTags = $seenAttrValues = [];
        $seenVariations = $seenProductAttributes = $seenCategoryId = $seenVariationAttributeValues = [];

        $processedCount = 0; // Track for periodic yields

        foreach ($productsArr as $p) {
            $core = $p['core'] ?? [];
            $buybox = $p['buybox']['items'][0] ?? [];
            $desc = $p['description']['html'] ?? '';
            $variants = $p['variants']['selectors'] ?? [];
            $reviewSummary = $p['reviews'] ?? [];
            $starRating = $reviewSummary['star_rating'] ?? 0;
            $reviewCount = $reviewSummary['count'] ?? 0;
            $reviewDistribution = $reviewSummary['distribution'] ?? null;
            $tags = $p['top_navigation']['items'] ?? [];
            $gallery = $p['gallery']['images'] ?? [];
            $breadcrumbs = $p['breadcrumbs']['items'] ?? [];

            // Stock availability is inside buybox.items[0], not at product root
            // Check both locations for backward compatibility
            $stockAvailability = $buybox['stock_availability'] ?? $p['stock_availability'] ?? [];

            // Debug: Log JSON structure for first 2 products to verify extraction
            static $debugCount = 0;

            // Extract product specifications from product_information->items
            $productInfo = $p['product_information'] ?? [];
            $specifications = $this->buildSpecificationsTable($productInfo);

            // Extract warranty and return_policy_text from bullet_point_attributes->items
            $bulletPoints = $p['bullet_point_attributes']['items'] ?? [];
            $warranty = null;
            $returnPolicyText = null;

            foreach ($bulletPoints as $bullet) {
                $bulletType = $bullet['type'] ?? '';
                $bulletText = $bullet['text'] ?? '';

                if ($bulletType === 'warranty' && !empty($bulletText)) {
                    $warranty = $bulletText;
                }

                if ($bulletType === 'hassle_free_exchanges_and_returns' && !empty($bulletText)) {
                    // Replace "30" with "7" in the return policy text
                    $returnPolicyText = str_replace('30', '7', $bulletText);

                }
            }

            if (empty($core['id']) || empty($core['title'])) continue;
            $sku = $core['id']; if (isset($seenProducts[$sku])) continue; $seenProducts[$sku] = true;

            $name = $core['title'];
            $slug = !empty($core['slug']) ? $core['slug'] : \Illuminate\Support\Str::slug($name);
            $shortDescription = $core['subtitle'] ?? strip_tags($desc);

            $rawPrice = $buybox['price'] ?? 0; $price = $this->fastConvertPrice($rawPrice);
            $rawSale = $buybox['sale_price'] ?? null; $salePrice = $rawSale !== null ? $this->fastConvertPrice($rawSale) : null;

            $stockStatus = !empty($variants) ? 'in_stock' : (!empty($buybox['is_add_to_cart_available']) ? 'in_stock' : 'out_of_stock');

            // Check for Takealot Now first (priority delivery)
            $takealotnowInfo = $p['takealotnow_info'] ?? null;
            $estimatedDelivery = ''; // Default fallback

            if (!empty($takealotnowInfo) && isset($takealotnowInfo['is_active']) && $takealotnowInfo['is_active']) {
                // If Takealot Now is available and active, set delivery text
                $estimatedDelivery = 'Get It Tomorrow';
            } elseif (!empty($stockAvailability)) {
                // Otherwise, get estimated delivery text from stock_availability
                $status = isset($stockAvailability['status']) ? trim($stockAvailability['status']) : '';

                // If status is empty, use default
                if (empty($status)) {
                    $estimatedDelivery = ''; // Default fallback
                }
                // Check if status is "In stock" and distribution_centres is available
                elseif (strcasecmp($status, 'In stock') === 0 && isset($stockAvailability['distribution_centres']) && is_array($stockAvailability['distribution_centres'])) {
                    // Use distribution centres mapping for "In stock" products
                    $estimatedDelivery = $this->getEstimatedDeliveryFromDistributionCentres($stockAvailability['distribution_centres']);
                } else {
                    // For other statuses (e.g., "Ships in X days" or "Ships in 14 - 16 work days"), increment by 2
                    $estimatedDelivery = $this->incrementShippingDays($status, 2);
                }

            }

            $productsData[] = [
                'sku' => $sku, 'name' => $name, 'slug' => $slug,
                // Set type to 'variable' when the JSON has variant selectors, 'simple' otherwise
                'type' => !empty($variants) ? 'classified' : 'simple',
                'description' => $desc,
                'specifications' => $specifications, // HTML table with product specs
                'short_description' => $shortDescription,
                'price' => $price, 'quantity' => 9999999, 'sale_price' => $salePrice,
                'stock_status' => $stockStatus, 'created_by_id' => 1,
                'estimated_delivery_text' => $estimatedDelivery,
                'warranty' => $warranty, // Extracted from bullet_point_attributes
                'return_policy_text' => $returnPolicyText, // Extracted from bullet_point_attributes
                'meta_title' => $name,
                'meta_description' => $shortDescription,
                'created_at' => $now, 'updated_at' => $now,
            ];

            // Collect SKUs that should be re-enabled: genuinely in-stock (not lead-time, has distribution centres)
            $isLeadtime = $stockAvailability['is_leadtime'] ?? false;
            $hasCentres = !empty($stockAvailability['distribution_centres']);
            if (!empty($buybox['is_add_to_cart_available']) && !$isLeadtime && $hasCentres) {
                $reEnableSkus[] = (string) $sku;
            }

            $firstImg = $gallery[0] ?? null;
            $thumbnailUpdates[] = [ 'sku' => $sku, 'image' => ($firstImg ? str_replace('{size}','zoom',$firstImg) : null) ];

            $prevId = null;
            foreach ($breadcrumbs as $bc) {
                if (empty($bc['id']) || empty($bc['name'])) continue;
                $catId = (int) $bc['id']; $catSlug = $bc['slug'] ?? \Illuminate\Support\Str::slug($bc['name']);
                if (!isset($seenCategoryId[$catId])) {
                    $seenCategoryId[$catId] = true;
                    $categoriesData[] = [
                        'id' => $catId, 'name' => $bc['name'], 'slug' => $catSlug,
                        'status' => 1, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                if ($prevId !== null) { $categoryParentHints[$catId] = $prevId; }
                $prevId = $catId;
                $categoryLinks[] = [ 'product_sku' => $sku, 'category_id' => $catId ];
            }

            foreach ($gallery as $url) {
                $full = str_replace('{size}','zoom',$url);
                if (!isset($seenAttachments[$full])) {
                    $seenAttachments[$full] = true;
                    $attachmentsData[] = [
                        'image_url' => $full, 'file_name' => $full, 'disk' => 'public',
                        'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                $productImages[] = [ 'product_sku' => $sku, 'attachment_url' => $full ];
            }

            foreach ($tags as $tg) {
                $txt = $tg['text'] ?? null; if (!$txt) continue; $tSlug = \Illuminate\Support\Str::slug($txt);
                if (!isset($seenTags[$tSlug])) {
                    $seenTags[$tSlug] = true;
                    $tagsData[] = [ 'slug' => $tSlug, 'name' => $txt, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now ];
                }
                $productTags[] = [ 'product_sku' => $sku, 'tag_slug' => $tSlug ];
            }

            foreach ($variants as $variant) {
                $attrTitle = $variant['title'] ?? 'Unknown'; $attrSlug = \Illuminate\Support\Str::slug($attrTitle);
                if (!isset($seenAttributes[$attrSlug])) {
                    $seenAttributes[$attrSlug] = true;
                    $attributesData[] = [ 'slug' => $attrSlug, 'name' => $attrTitle, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now ];
                }
                $paKey = $sku.'||'.$attrSlug; if (!isset($seenProductAttributes[$paKey])) {
                    $seenProductAttributes[$paKey] = true;
                    $productAttributes[] = [ 'product_sku' => $sku, 'attribute_slug' => $attrSlug, 'created_at' => $now, 'updated_at' => $now ];
                }

                foreach (($variant['options'] ?? []) as $opt) {
                    $val = is_array($opt['value']) ? ($opt['value']['value'] ?? '') : ($opt['value'] ?? '');
                    $valSlug = \Illuminate\Support\Str::slug($val);
                    $avKey = $attrSlug.'||'.$valSlug;
                    if (!isset($seenAttrValues[$avKey])) {
                        $seenAttrValues[$avKey] = true;
                        $attributeValuesData[] = [ 'attribute_slug' => $attrSlug, 'value_slug' => $valSlug, 'value' => $val, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now ];
                    }
                    $rawVarPrice = $opt['price'] ?? $rawPrice; $varPrice = $this->fastConvertPrice($rawVarPrice);

                    // Check is_enabled flag to determine quantity and stock status
                    $isEnabled = $opt['is_enabled'] ?? true;
                    $varQuantity = $isEnabled ? 999999 : 0;
                    $varStockStatus = $isEnabled ? 'in_stock' : 'out_of_stock';

                    // Handle image — could be an array of URL templates, or a plain string, or absent
                    $rawImg = $opt['image'] ?? null;
                    if (is_array($rawImg)) {
                        $img0 = $rawImg[0] ?? null;           // Array format: take first element
                    } elseif (is_string($rawImg) && strlen($rawImg) > 10) {
                        $img0 = $rawImg;                       // String format: use directly
                    } else {
                        $img0 = null;
                    }
                    $fullOptImg = $img0 ? str_replace('{size}', 'zoom', $img0) : null;

                    if ($fullOptImg && !isset($seenAttachments[$fullOptImg])) {
                        $seenAttachments[$fullOptImg] = true;
                        $attachmentsData[] = [ 'image_url' => $fullOptImg, 'file_name' => $fullOptImg, 'disk' => 'public', 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now ];
                    }
                    $varKey = $sku.'||'.$val; if (!isset($seenVariations[$varKey])) {
                        $seenVariations[$varKey] = true;
                        $variationsData[] = [ 'product_sku' => $sku, 'name' => $val, 'price' => $varPrice, 'quantity' => $varQuantity, 'stock_status' => $varStockStatus, 'variation_image_id' => $fullOptImg, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now ];
                    }
                    // Deduplicate VAV entries — variation_attribute_values has no DB unique constraint
                    // Without this guard, the same [sku, val, attr, val] combo can be inserted multiple times
                    $vavKey = $sku.'||'.$val.'||'.$attrSlug.'||'.$valSlug;
                    if (!isset($seenVariationAttributeValues[$vavKey])) {
                        $seenVariationAttributeValues[$vavKey] = true;
                        $variationAttributeValues[] = [ 'product_sku' => $sku, 'variation_name' => $val, 'attribute_slug' => $attrSlug, 'value_slug' => $valSlug ];
                    }
                }
            }

            // Process reviews based on distribution
            if ($reviewCount > 0 && $reviewDistribution) {
                // Distribution has: num_1_star_ratings, num_2_star_ratings, etc.
                $starCounts = [
                    1 => $reviewDistribution['num_1_star_ratings'] ?? 0,
                    2 => $reviewDistribution['num_2_star_ratings'] ?? 0,
                    3 => $reviewDistribution['num_3_star_ratings'] ?? 0,
                    4 => $reviewDistribution['num_4_star_ratings'] ?? 0,
                    5 => $reviewDistribution['num_5_star_ratings'] ?? 0,
                ];

                // Create review records for each star rating
                foreach ($starCounts as $starLevel => $count) {
                    if ($count > 0) {
                        // Limit to 500 reviews per star rating to avoid excessive records
                        $limitedCount = min($count, 500);
                        for ($i = 0; $i < $limitedCount; $i++) {
                            $reviewsData[] = [
                                'product_sku' => $sku,
                                'rating' => $starLevel,
                                'description' => null,
                                'consumer_id' => null,
                                'created_by_id' => 1,
                                'created_at' => $now,
                                'updated_at' => $now
                            ];
                        }
                    }
                }
            }
        }

        // CRITICAL FIX: Process this batch's data immediately (non-blocking)
        // This releases memory and database locks incrementally

        // CRITICAL FIX: NO LARGE TRANSACTION - Process everything in small non-blocking chunks
        // This prevents the site from freezing during imports

        // Step 1: Upsert products in small batches (no transaction wrapping)
        // NOTE: price and sale_price are NOT updated here - they're handled separately in Step 2 with sale protection
        $this->fastOut("Processing products in small non-blocking batches...");
        $this->fastChunkUpsertNonBlocking('products', $productsData, ['sku'], ['name','slug','type','description','specifications','short_description','quantity','stock_status','estimated_delivery_text','warranty','return_policy_text','meta_title','meta_description','updated_at']);

        $productMap = $this->fastBuildMap('products', 'sku', 'id', $productsData);
        $productIds = array_values($productMap);


        // Track imported product IDs for selective indexing
        $this->fastImportedProductIds = array_merge($this->fastImportedProductIds, $productIds);

        // Brief delay to ensure all Step 1 database commits are complete
        usleep(100000); // 100ms
        $this->fastOut('[products] Step 1 complete. Starting Step 2 price updates with sale protection...');

        // Step 2: Handle price updates (quick bulk operation)

            // Update prices for existing products with sale protection
            $priceUpdates = [];
            $skippedCount = 0;

            // Get existing products to check for active sales
            $existingProducts = \Illuminate\Support\Facades\DB::table('products')
                ->whereIn('id', array_values($productMap))
                ->select('id', 'sku', 'price', 'sale_price', 'is_sale_enable')
                ->get()
                ->keyBy('id');

            $this->fastOut('[products] checking ' . count($existingProducts) . ' products for sale price protection...');

            foreach ($productsData as $p) {
                $pid = $productMap[$p['sku']] ?? null;
                if ($pid) {
                    $existingProduct = $existingProducts[$pid] ?? null;

                    // Check if product has a sale_price > 0 (manual promotion/sale set in admin)
                    // If sale_price > 0, skip price updates to preserve the manual sale price
                    $hasSalePrice = $existingProduct && $existingProduct->sale_price > 0;

                    if ($hasSalePrice) {
                        // Skip ALL price-related fields - product has manual sale price set
                        $priceUpdates[$pid] = [
                            'price' => 'SKIP',
                            'sale_price' => 'SKIP',
                            'is_sale_enable' => 'SKIP',
                        ];
                        $skippedCount++;

                        // Debug log first few skipped products
                        if ($skippedCount <= 3) {
                            $this->fastOut('[PROTECTED] SKU: ' . $p['sku'] . ' | DB sale_price: ' . $existingProduct->sale_price . ' | Keeping existing prices');
                        }
                    } else {
                        // Update all price-related fields
                        $priceUpdates[$pid] = [
                            'price' => (float) ($p['price'] ?? 0),
                            'sale_price' => isset($p['sale_price']) ? (float) $p['sale_price'] : null,
                            'is_sale_enable' => 0, // Reset to 0 for imported products without manual sales
                        ];
                    }
                }
            }

        if (!empty($priceUpdates)) {
            // Process price updates in chunks to avoid blocking
            $this->fastOut('[products] processing price updates in chunks...');
            $allIds = array_keys($priceUpdates);
            $totalUpdated = 0;

            foreach (array_chunk($allIds, 100) as $chunkIndex => $idsChunk) {
                $casePrice = 'CASE id ';
                $caseSale  = 'CASE id ';
                $caseIsEnable = 'CASE id ';

                $hasPriceUpdates = false;
                $hasSaleUpdates = false;
                $hasEnableUpdates = false;

                foreach ($idsChunk as $id) {
                    $vals = $priceUpdates[$id];
                    $priceVal = $vals['price'];
                    $saleVal  = $vals['sale_price'];
                    $isEnableVal = $vals['is_sale_enable'];

                    // Build CASE for price
                    if ($priceVal !== 'SKIP') {
                        $casePrice .= 'WHEN ' . (int)$id . ' THEN ' . (float)$priceVal . ' ';
                        $hasPriceUpdates = true;
                    }

                    // Build CASE for sale_price
                    if ($saleVal !== 'SKIP') {
                        if ($saleVal === null) {
                            $caseSale .= 'WHEN ' . (int)$id . ' THEN NULL ';
                        } else {
                            $caseSale .= 'WHEN ' . (int)$id . ' THEN ' . (float)$saleVal . ' ';
                        }
                        $hasSaleUpdates = true;
                    }

                    // Build CASE for is_sale_enable
                    if ($isEnableVal !== 'SKIP') {
                        $caseIsEnable .= 'WHEN ' . (int)$id . ' THEN ' . (int)$isEnableVal . ' ';
                        $hasEnableUpdates = true;
                    }
                }

                // Only execute UPDATE if there are actual updates to make
                if ($hasPriceUpdates || $hasSaleUpdates || $hasEnableUpdates) {
                    $casePrice .= 'ELSE price END';
                    $caseSale  .= 'ELSE sale_price END';
                    $caseIsEnable .= 'ELSE is_sale_enable END';
                    $idList = implode(',', array_map('intval', $idsChunk));

                    try {
                        \Illuminate\Support\Facades\DB::statement(
                            "UPDATE products SET price = $casePrice, sale_price = $caseSale, is_sale_enable = $caseIsEnable, updated_at = NOW() WHERE id IN ($idList)"
                        );
                        $totalUpdated += count($idsChunk);

                        // Brief delay after each chunk
                        usleep(5000); // 5ms

                    } catch (\Throwable $e) {
                        $this->fastOut('⚠️ Failed to update product prices chunk #' . $chunkIndex . ': ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));
                        Log::error('fastProcessFile price update failed', ['chunk' => $chunkIndex, 'exception' => $e]);
                    }
                }
            }

            if ($totalUpdated > 0) {
                $this->fastOut('[products] prices updated for existing products: ' . $totalUpdated);
            }
            if ($skippedCount > 0) {
                $this->fastOut('[products] products with manual sale prices skipped: ' . $skippedCount);
            }
        }

        // Step 2b: Re-enable products that were disabled but now have genuine stock
        if (!empty($reEnableSkus)) {
            $this->fastOut('[re-enable] Candidates with genuine stock in this batch: ' . count($reEnableSkus));
            $reEnabledTotal = 0;
            foreach (array_chunk($reEnableSkus, 500) as $chunk) {
                $rows = \Illuminate\Support\Facades\DB::table('products')
                    ->whereIn('sku', $chunk)
                    ->where(function ($q) { $q->where('status', 0)->orWhere('is_approved', 0); })
                    ->where('is_permanently_disabled', false)
                    ->whereNull('deleted_at')
                    ->update(['status' => 1, 'is_approved' => 1, 'updated_at' => now()]);
                $reEnabledTotal += $rows;
            }
            $this->fastOut("[re-enable] Products turned back on: {$reEnabledTotal}");
        }

        // Step 3: Delete old related data (chunked to avoid blocking)
        // CRITICAL: Variations are SOFT-DELETED (not hard-deleted) to protect order_products.
        // Hard-deleting variations triggers FK cascades that destroy historical order data.
        $this->fastOut('[cleanup] Removing old related data in chunks...');

        // Chunk productIds to process in batches of 100
        foreach (array_chunk($productIds, 100) as $chunkIndex => $idChunk) {
            $oldVarIds = \Illuminate\Support\Facades\DB::table('variations')
                ->whereIn('product_id', $idChunk)
                ->whereNull('deleted_at')
                ->pluck('id')->toArray();
            if (!empty($oldVarIds)) {
                \Illuminate\Support\Facades\DB::table('variation_attribute_values')->whereIn('variation_id', $oldVarIds)->delete();
            }
            // Soft-delete variations instead of hard-deleting them
            \Illuminate\Support\Facades\DB::table('variations')
                ->whereIn('product_id', $idChunk)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
            \Illuminate\Support\Facades\DB::table('product_images')->whereIn('product_id', $idChunk)->delete();
            \Illuminate\Support\Facades\DB::table('product_tags')->whereIn('product_id', $idChunk)->delete();
            \Illuminate\Support\Facades\DB::table('product_categories')->whereIn('product_id', $idChunk)->delete();
            \Illuminate\Support\Facades\DB::table('product_attributes')->whereIn('product_id', $idChunk)->delete();

            // Delete only SYNTHETIC reviews (consumer_id IS NULL)
            \Illuminate\Support\Facades\DB::table('reviews')
                ->whereIn('product_id', $idChunk)
                ->whereNull('consumer_id')
                ->delete();

            // Brief delay between cleanup chunks
            if ($chunkIndex % 5 === 0) {
                usleep(5000); // 5ms delay every 5 chunks
            }
        }
        $this->fastOut('[cleanup] Cleanup completed');

        // Step 4: Insert new data (non-blocking)
        $this->fastChunkInsertOrIgnore('attachments', $attachmentsData, 'attachments');
            $this->fastChunkInsertOrIgnore('attributes',  $attributesData,  'attributes');
            $this->fastChunkInsertOrIgnore('tags',        $tagsData,        'tags');

            $attrMap = $this->fastBuildMap('attributes','slug','id',$attributesData);
            $finalAV = array_map(function($av) use ($attrMap) {
                return [
                    'attribute_id'  => $attrMap[$av['attribute_slug']] ?? null,
                    'slug'          => $av['value_slug'],
                    'value'         => $av['value'],
                    'created_by_id' => 1,
                    'created_at'    => $av['created_at'],
                    'updated_at'    => $av['updated_at'],
                ];
            }, $attributeValuesData);
            $this->fastChunkInsertOrIgnore('attribute_values', $finalAV, 'attribute_values');

            $attachMap = $this->fastBuildMap('attachments','image_url','id',$attachmentsData);
            $finalVars = [];
            foreach ($variationsData as $v) {
                $pid = $productMap[$v['product_sku']] ?? null; if (!$pid) continue;
                $finalVars[] = [
                    'product_id'         => $pid,
                    'name'               => $v['name'],
                    'price'              => $v['price'],
                    'quantity'           => $v['quantity'] ?? 999999,
                    'stock_status'       => $v['stock_status'] ?? 'in_stock',
                    'variation_image_id' => $attachMap[$v['variation_image_id']] ?? null,
                    'deleted_at'         => null,   // Ensures upsert RESTORES soft-deleted rows
                    'created_at'         => $v['created_at'],
                    'updated_at'         => $v['updated_at'],
                ];
            }
            // Step 4b: Upsert variations — restores soft-deleted rows by including deleted_at=null
            // The DB has a unique index on [product_id, name] (variations_product_name_unique).
            // Step 3 soft-deleted old variations. The upsert will match on [product_id, name]
            // and update ALL columns including deleted_at=null, which RESTORES the row.
            // We do NOT hard-delete because that cascades and destroys order history.
            $this->fastChunkUpsertNonBlocking(
                'variations',
                $finalVars,
                ['product_id', 'name'],
                ['price', 'quantity', 'stock_status', 'variation_image_id', 'deleted_at', 'updated_at']
            );

            // Price update pass: ensure prices are current (upsert above already sets them,
            // but this bulk CASE statement handles any that the upsert may have missed)
            if (!empty($finalVars)) {
                $this->fastOut('[variations] running price update pass...');
                $varProductIds = array_unique(array_column($finalVars, 'product_id'));

                $existingVars = \Illuminate\Support\Facades\DB::table('variations')
                    ->whereIn('product_id', $varProductIds)
                    ->whereNull('deleted_at')
                    ->get(['id', 'product_id', 'name'])
                    ->keyBy(function ($item) {
                        return $item->product_id . '||' . $item->name;
                    });

                $varPriceUpdates = [];
                foreach ($finalVars as $v) {
                    $key = $v['product_id'] . '||' . $v['name'];
                    if (isset($existingVars[$key])) {
                        $varPriceUpdates[$existingVars[$key]->id] = (float)$v['price'];
                    }
                }

                if (!empty($varPriceUpdates)) {
                    foreach (array_chunk(array_keys($varPriceUpdates), 100) as $chunkIdx => $vidsChunk) {
                        $caseVarPrice = 'CASE id ';
                        foreach ($vidsChunk as $vid) {
                            $caseVarPrice .= 'WHEN ' . (int)$vid . ' THEN ' . (float)$varPriceUpdates[$vid] . ' ';
                        }
                        $caseVarPrice .= 'ELSE price END';
                        $vidList = implode(',', array_map('intval', $vidsChunk));
                        try {
                            \Illuminate\Support\Facades\DB::statement(
                                "UPDATE variations SET price = $caseVarPrice, updated_at = NOW() WHERE id IN ($vidList)"
                            );
                        } catch (\Throwable $e) {
                            $this->fastOut('⚠️ Variation price update failed chunk #' . $chunkIdx . ': ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));
                        }
                        usleep(5000);
                    }
                    $this->fastOut('[variations] price update pass complete for ' . count($varPriceUpdates) . ' variations');
                }
            }

            $avMap = $this->fastBuildAVMap($finalAV);
            $varMap = $this->fastBuildVariationMap($finalVars);
            $pivotVAV = [];
            foreach ($variationAttributeValues as $vav) {
                $pid = $productMap[$vav['product_sku']] ?? null;
                $vid = $varMap[(string)$pid.'||'.$vav['variation_name']] ?? null;
                $aid = $attrMap[$vav['attribute_slug']] ?? null;
                $avid = $avMap[(string)$aid.'||'.$vav['value_slug']] ?? null;
                if ($vid && $avid) { $pivotVAV[] = ['variation_id'=>$vid,'attribute_value_id'=>$avid]; }
            }
            $this->fastChunkInsertOrIgnore('variation_attribute_values', $pivotVAV, 'variation_attribute_values');

            $finalPI = [];
            foreach ($productImages as $pi) {
                $pid = $productMap[$pi['product_sku']] ?? null;
                $aid = $attachMap[$pi['attachment_url']] ?? null;
                if ($pid && $aid) { $finalPI[] = ['product_id'=>$pid,'attachment_id'=>$aid]; }
            }
            $this->fastChunkInsertOrIgnore('product_images', $finalPI, 'product_images');

            $tagMap = $this->fastBuildMap('tags','slug','id',$tagsData);
            $finalPT = [];
            foreach ($productTags as $pt) {
                $pid = $productMap[$pt['product_sku']] ?? null;
                $tid = $tagMap[$pt['tag_slug']] ?? null;
                if ($pid && $tid) { $finalPT[] = ['product_id'=>$pid,'tag_id'=>$tid]; }
            }
            $this->fastChunkInsertOrIgnore('product_tags', $finalPT, 'product_tags');

            if (!empty($categoriesData)) {
                $byId = [];
                foreach ($categoriesData as $row) { $byId[$row['id']] = $row; }

                // Sort by ID to ensure consistent lock acquisition order across parallel jobs
                // This reduces deadlock probability when multiple jobs upsert overlapping categories
                ksort($byId);

                $catsForUpsert = array_values($byId);

                // Use advisory lock to serialize category updates across all parallel jobs
                // This completely eliminates deadlocks at the cost of slight serialization
                $this->fastUpsertCategoriesWithLock($catsForUpsert);
            }

            if (!empty($categoryParentHints)) {
                $existingIds = \Illuminate\Support\Facades\DB::table('categories')->pluck('id')->all();
                $exists = array_fill_keys($existingIds, true);
                $finalPairs = [];
                foreach ($categoryParentHints as $childId => $parentId) {
                    $childId = (int)$childId; $parentId = (int)$parentId;
                    if (isset($exists[$childId]) && isset($exists[$parentId])) { $finalPairs[$childId] = $parentId; }
                }
                if (!empty($finalPairs)) {
                    $this->fastUpdateCategoryParentsWithLock($finalPairs);
                }
            }

            $finalRev = [];
            foreach ($reviewsData as $r) {
                $pid = $productMap[$r['product_sku']] ?? null;
                if ($pid) { $finalRev[] = ['product_id'=>$pid,'rating'=>$r['rating'],'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at']]; }
            }
            $this->fastChunkInsertOrIgnore('reviews', $finalRev, 'reviews');

            $this->fastOut('[progress] Processing thumbnails and final associations...');

            // Bulk update thumbnails efficiently
            if (!empty($thumbnailUpdates)) {
                $this->fastOut('[thumbnails] processing ' . count($thumbnailUpdates) . ' thumbnail updates...');
                $imageUrls = array_filter(array_column($thumbnailUpdates, 'image'));
                if (!empty($imageUrls)) {
                    // CRITICAL FIX: Chunk whereIn to avoid PostgreSQL parameter limit
                    $attachmentMap = [];
                    foreach (array_chunk($imageUrls, 5000) as $urlChunk) {
                        $chunkMap = \Illuminate\Support\Facades\DB::table('attachments')
                            ->whereIn('image_url', $urlChunk)
                            ->pluck('id', 'image_url')
                            ->toArray();
                        $attachmentMap = array_merge($attachmentMap, $chunkMap);
                    }

                    $thumbUpdates = [];
                    foreach ($thumbnailUpdates as $tu) {
                        $pid = $productMap[$tu['sku']] ?? null;
                        $aid = isset($tu['image']) ? ($attachmentMap[$tu['image']] ?? null) : null;
                        if ($pid && $aid) {
                            $thumbUpdates[$pid] = $aid;
                        }
                    }

                if (!empty($thumbUpdates)) {
                    // Process thumbnail updates in chunks to avoid blocking
                    $allThumbIds = array_keys($thumbUpdates);
                    $totalUpdated = 0;

                    foreach (array_chunk($allThumbIds, 100) as $chunkIndex => $idsChunk) {
                        $caseThumbnail = 'CASE id ';
                        foreach ($idsChunk as $pid) {
                            $aid = $thumbUpdates[$pid];
                            $caseThumbnail .= 'WHEN ' . (int)$pid . ' THEN ' . (int)$aid . ' ';
                        }
                        $caseThumbnail .= 'END';
                        $idList = implode(',', array_map('intval', $idsChunk));

                        try {
                            \Illuminate\Support\Facades\DB::statement(
                                "UPDATE products SET product_thumbnail_id = $caseThumbnail, product_meta_image_id = $caseThumbnail, updated_at = NOW() WHERE id IN ($idList)"
                            );
                            $totalUpdated += count($idsChunk);

                            // Brief delay after each chunk
                            usleep(5000); // 5ms

                        } catch (\Throwable $e) {
                            $this->fastOut('⚠️ Failed to update thumbnails chunk #' . $chunkIndex . ': ' . \Illuminate\Support\Str::limit($e->getMessage(), 200));
                            Log::error('fastProcessFile thumbnail update failed', ['chunk' => $chunkIndex, 'exception' => $e]);
                        }
                    }

                    if ($totalUpdated > 0) {
                        $this->fastOut('[thumbnails] updated ' . $totalUpdated . ' product thumbnails');
                    }
                }
                }
            }

            $finalPA = [];
            foreach ($productAttributes as $pa) {
                $pid = $productMap[$pa['product_sku']] ?? null;
                $aid = $attrMap[$pa['attribute_slug']] ?? null;
                if ($pid && $aid) { $finalPA[] = ['product_id'=>$pid,'attribute_id'=>$aid,'created_at'=>$pa['created_at'],'updated_at'=>$pa['updated_at']]; }
            }
            $this->fastChunkInsertOrIgnore('product_attributes', $finalPA, 'product_attributes');

            $this->fastOut('[progress] Finalizing product categories...');

            $finalPC = [];
            foreach ($categoryLinks as $cl) {
                $pid = $productMap[$cl['product_sku']] ?? null;
                if ($pid) { $finalPC[] = ['product_id'=>$pid,'category_id' => (int)$cl['category_id']]; }
            }

            if (!empty($finalPC)) {
                $existingIds = \Illuminate\Support\Facades\DB::table('categories')->pluck('id')->all();
                $exists = array_fill_keys($existingIds, true);
                $filtered = []; $missing = [];
                foreach ($finalPC as $row) { if (isset($exists[$row['category_id']])) $filtered[] = $row; else $missing[$row['category_id']] = true; }
                if ($missing) { $this->fastOut('⚠️ Skipped product_categories for missing category IDs: ' . implode(',', array_keys($missing))); }
                $this->fastChunkInsertOrIgnore('product_categories', $filtered, 'product_categories');
            } else {
                $this->fastOut('[product_categories] nothing to insert.');
            }

        // Return statistics for this batch
        return [
            'total' => count($productsArr),
            'processed' => count($productsData),
            'created' => count($productsData),
            'skipped' => 0,
        ];
    }

    private function fastChunkInsertOrIgnore(string $table, array $rows, string $label): void
    {
        if (empty($rows)) return;

        // Use smaller batches to avoid blocking
        $smallBatchSize = 100;
        $chunks = array_chunk($rows, $smallBatchSize);
        $total = 0;

        foreach ($chunks as $index => $chunk) {
            try {
                // Each insert is its own quick transaction
                \Illuminate\Support\Facades\DB::table($table)->insertOrIgnore($chunk);

                $count = count($chunk);
                $total += $count;

                // Only log every 10 batches to reduce output
                if ($index % 10 === 0) {
                    $this->fastOut("[$table] inserted {$total} rows (batch #{$index})");
                }

                // Brief delay to allow other queries
                usleep(5000); // 5ms delay

            } catch (\Throwable $e) {
                $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                $this->fastOut("Query/Insert exception on [$table] chunk #{$index}: $msg");
                Log::error('fastChunkInsertOrIgnore exception', ['table'=>$table,'chunk_index'=>$index,'exception'=>$e]);
            }
        }

        $this->fastOut("[$table] total inserted {$total} rows (non-blocking batch size {$smallBatchSize})");
    }

    private function fastBuildMap(string $table, string $uniqueCol, string $idCol, array $rows): array
    {
        // CRITICAL FIX: Don't use ->values() which resets keys to 0,1,2
        // We need to preserve the actual unique column values as keys
        $keys = collect($rows)->pluck($uniqueCol)->unique();

        if ($keys->isEmpty()) return [];

        // CRITICAL FIX: Chunk whereIn queries to avoid PostgreSQL parameter limit (65535)
        // With large imports, we can have 50k+ attachment URLs which exceeds this limit
        $map = [];
        $chunkSize = 5000; // Safe chunk size well below 65535 parameter limit

        foreach ($keys->chunk($chunkSize) as $chunkIndex => $keyChunk) {
            $chunkMap = \Illuminate\Support\Facades\DB::table($table)
                ->whereIn($uniqueCol, $keyChunk->toArray())
                ->pluck($idCol, $uniqueCol)
                ->toArray();


            // CRITICAL FIX: Use + operator instead of array_merge to preserve numeric keys
            // array_merge() reindexes numeric keys to 0, 1, 2..., which breaks SKU lookups
            $map = $map + $chunkMap;
        }


        return $map;
    }

    private function fastBuildVariationMap(array $finalVars): array
    {
        if (empty($finalVars)) return [];
        $pIds = collect($finalVars)->pluck('product_id')->unique()->values();
        if ($pIds->isEmpty()) return [];

        // CRITICAL FIX: Chunk whereIn queries to avoid PostgreSQL parameter limit
        $map = [];
        $chunkSize = 5000;

        foreach ($pIds->chunk($chunkSize) as $pidChunk) {
            $rows = \Illuminate\Support\Facades\DB::table('variations')
                ->whereIn('product_id', $pidChunk->toArray())
                ->whereNull('deleted_at')   // Only map LIVE (non-deleted) rows
                ->get(['id','product_id','name']);

            foreach ($rows as $row) {
                $key = (string)$row->product_id.'||'.$row->name;
                $map[$key] = $row->id;
            }
        }

        return $map;
    }

    private function fastBuildAVMap(array $finalAV): array
    {
        if (empty($finalAV)) return [];
        $attrIds = collect($finalAV)->pluck('attribute_id')->unique()->values();
        if ($attrIds->isEmpty()) return [];

        // CRITICAL FIX: Chunk whereIn queries to avoid PostgreSQL parameter limit
        $map = [];
        foreach ($attrIds->chunk(5000) as $idChunk) {
            $rows = \Illuminate\Support\Facades\DB::table('attribute_values')
                ->whereIn('attribute_id', $idChunk->toArray())
                ->get(['id','attribute_id','slug']);

            foreach ($rows as $r) {
                $map[$r->attribute_id.'||'.$r->slug] = $r->id;
            }
        }

        return $map;
    }

    private function fastConvertPrice($price): float
    {
        $converted = (float)$price / $this->fastTodaysRate;
        $result = $converted + ($this->fastPercentage / 100 * $converted);
        return round($result, 2);
    }

    private function fastChunkUpsert(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        $total = 0; $i = 0;
        foreach (array_chunk($rows, $this->fastChunkSize) as $chunk) {
            try {
                \Illuminate\Support\Facades\DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
            } catch (\Throwable $e) {
                $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                $this->fastOut("Query/Upsert exception on [$table] chunk #{$i}: $msg");
                Log::error('fastChunkUpsert exception', ['table'=>$table,'chunk_index'=>$i,'exception'=>$e]);
            }
            $count = count($chunk); $total += $count;
            $this->fastOut("[$table] upsert chunk #{$i}: {$count} rows");
            $i++;
        }
        $this->fastOut("[$table] total upserted {$total} rows (chunk size {$this->fastChunkSize}).");
    }

    /**
     * Non-blocking chunk upsert - processes in smaller batches with delays to avoid blocking the site
     * CRITICAL: Uses smaller batches (50 instead of 1000) and adds micro-delays between batches
     * This allows other database queries to execute and prevents site lockup during imports
     */
    private function fastChunkUpsertNonBlocking(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        $total = 0;
        $i = 0;
        $smallBatchSize = 50; // Much smaller batches to avoid long-running transactions
        $maxRetries = 5; // Maximum retry attempts for deadlocks

        foreach (array_chunk($rows, $smallBatchSize) as $chunk) {
            $retries = 0;
            $success = false;

            while (!$success && $retries < $maxRetries) {
                try {
                    // Each batch is its own transaction - commits quickly
                    \Illuminate\Support\Facades\DB::transaction(function () use ($table, $chunk, $uniqueBy, $updateColumns) {
                        \Illuminate\Support\Facades\DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
                    });

                    $count = count($chunk);
                    $total += $count;
                    $success = true;

                    // Only log progress every 10 batches to reduce output
                    if ($i % 10 === 0) {
                        $this->fastOut("[$table] processed {$total} rows (batch #{$i})");
                    }

                    // CRITICAL: Brief delay every batch to allow other queries to execute
                    // This prevents the import from monopolizing the database connection
                    usleep(10000); // 10ms delay - allows ~100 batches per second

                } catch (\Illuminate\Database\QueryException $e) {
                    // Check if this is a deadlock error (PostgreSQL error code 40P01)
                    if ($e->getCode() === '40P01' || str_contains($e->getMessage(), 'deadlock detected')) {
                        $retries++;

                        if ($retries < $maxRetries) {
                            // Exponential backoff: wait longer after each retry
                            $waitTime = min(1000000, 50000 * pow(2, $retries - 1)); // 50ms, 100ms, 200ms, 400ms, 800ms
                            $this->fastOut("[$table] Deadlock detected on batch #{$i}, retry {$retries}/{$maxRetries} after " . ($waitTime / 1000) . "ms");
                            usleep($waitTime);
                            continue;
                        } else {
                            // Max retries exceeded
                            $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                            $this->fastOut("[$table] Deadlock on batch #{$i} - MAX RETRIES EXCEEDED, skipping batch");
                            Log::warning('fastChunkUpsertNonBlocking deadlock - max retries exceeded', [
                                'table' => $table,
                                'chunk_index' => $i,
                                'retries' => $retries,
                                'chunk_size' => count($chunk)
                            ]);
                            break; // Skip this batch and continue with next
                        }
                    } else {
                        // Non-deadlock database error
                        $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                        $this->fastOut("Query/Upsert exception on [$table] chunk #{$i}: $msg");
                        Log::error('fastChunkUpsertNonBlocking exception', [
                            'table' => $table,
                            'chunk_index' => $i,
                            'exception' => $e
                        ]);
                        break; // Skip this batch and continue
                    }
                } catch (\Throwable $e) {
                    // Other unexpected errors
                    $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                    $this->fastOut("Unexpected exception on [$table] chunk #{$i}: $msg");
                    Log::error('fastChunkUpsertNonBlocking unexpected exception', [
                        'table' => $table,
                        'chunk_index' => $i,
                        'exception' => $e
                    ]);
                    break; // Skip this batch and continue
                }
            }

            $i++;
        }

        $this->fastOut("[$table] total upserted {$total} rows (non-blocking batch size {$smallBatchSize})");
    }

    /**
     * Upsert categories with PostgreSQL advisory lock to prevent deadlocks
     *
     * Uses pg_advisory_lock to serialize category updates across all parallel jobs.
     * This completely eliminates deadlocks but serializes category updates.
     * Since categories are a small percentage of import time, this is acceptable.
     */
    private function fastUpsertCategoriesWithLock(array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        // Use a fixed advisory lock key for all category updates
        // This ensures only one job can update categories at a time
        $lockKey = 123456789; // Arbitrary number for categories lock

        $maxWaitSeconds = 30;
        $acquired = false;
        $startTime = microtime(true);

        try {
            // Try to acquire advisory lock (waits if another job holds it)
            $this->fastOut("[categories] Waiting for advisory lock...");

            // pg_advisory_lock() waits until lock is available
            \Illuminate\Support\Facades\DB::statement("SELECT pg_advisory_lock(?)", [$lockKey]);
            $acquired = true;

            $waitTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->fastOut("[categories] Advisory lock acquired after {$waitTime}ms, upserting " . count($categories) . " categories");

            // Now we have exclusive access to categories - upsert in small batches
            $total = 0;
            $batchSize = 100; // Larger batches since we have exclusive lock

            foreach (array_chunk($categories, $batchSize) as $chunk) {
                \Illuminate\Support\Facades\DB::table('categories')->upsert(
                    $chunk,
                    ['id'],
                    ['name', 'slug', 'updated_at']
                );
                $total += count($chunk);
            }

            $this->fastOut("[categories] Successfully upserted {$total} categories with advisory lock");

        } catch (\Throwable $e) {
            $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
            $this->fastOut("[categories] Exception during locked upsert: {$msg}");
            Log::error('fastUpsertCategoriesWithLock exception', [
                'exception' => $e,
                'categories_count' => count($categories)
            ]);
        } finally {
            // Always release the lock
            if ($acquired) {
                \Illuminate\Support\Facades\DB::statement("SELECT pg_advisory_unlock(?)", [$lockKey]);
                $this->fastOut("[categories] Advisory lock released");
            }
        }
    }

    /**
     * Update category parent_id relationships with PostgreSQL advisory lock
     *
     * Uses the same advisory lock as category upserts to prevent deadlocks
     */
    private function fastUpdateCategoryParentsWithLock(array $parentPairs): void
    {
        if (empty($parentPairs)) {
            return;
        }

        // Use the same lock key as category upserts for consistency
        $lockKey = 123456789;
        $acquired = false;
        $startTime = microtime(true);

        try {
            $this->fastOut("[category parents] Waiting for advisory lock...");

            \Illuminate\Support\Facades\DB::statement("SELECT pg_advisory_lock(?)", [$lockKey]);
            $acquired = true;

            $waitTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->fastOut("[category parents] Advisory lock acquired after {$waitTime}ms, updating " . count($parentPairs) . " relationships");

            // Update in batches
            $chunks = array_chunk($parentPairs, 100, true);
            $total = 0;

            foreach ($chunks as $chunk) {
                $ids = array_keys($chunk);
                sort($ids); // Consistent order

                $case = 'CASE id ';
                foreach ($chunk as $child => $parent) {
                    $case .= 'WHEN '.$child.' THEN '.$parent.' ';
                }
                $case .= 'END';

                $idsStr = implode(',', $ids);

                \Illuminate\Support\Facades\DB::statement(
                    "UPDATE categories SET parent_id = $case, updated_at = NOW()
                     WHERE id IN ($idsStr)"
                );

                $total += count($chunk);
            }

            $this->fastOut("[category parents] Successfully updated {$total} parent relationships");

        } catch (\Throwable $e) {
            $msg = \Illuminate\Support\Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
            $this->fastOut("[category parents] Exception during locked update: {$msg}");
            Log::error('fastUpdateCategoryParentsWithLock exception', [
                'exception' => $e,
                'pairs_count' => count($parentPairs)
            ]);
        } finally {
            if ($acquired) {
                \Illuminate\Support\Facades\DB::statement("SELECT pg_advisory_unlock(?)", [$lockKey]);
                $this->fastOut("[category parents] Advisory lock released");
            }
        }
    }

    /**
     * Index only imported products to Elasticsearch after import
     * Returns array with indexing statistics
     */
    private function fastIndexProductsToElasticsearch(array $productIds): array
    {
        $stats = [
            'indexed' => 0,
            'failed' => 0,
        ];

        if (empty($productIds)) {
            $this->fastOut("⚠️ No products to index");
            return $stats;
        }

        try {
            $this->fastOut("\n🔍 Indexing " . count($productIds) . " products to Elasticsearch...");

            // For very large imports, process in chunks to avoid memory/timeout issues
            if (count($productIds) > 1000) {
                $this->fastOut("Large import detected - processing in chunks of 1000 products");
                $chunks = array_chunk($productIds, 1000);

                foreach ($chunks as $chunkIndex => $chunk) {
                    $this->fastOut("Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks) . " (" . count($chunk) . " products)");
                    $this->fastManualElasticsearchIndex($chunk);
                    $stats['indexed'] += count($chunk);

                    // Clear memory between chunks
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                    // Small delay between chunks to prevent overwhelming ES
                    if ($chunkIndex < count($chunks) - 1) {
                        usleep(500000); // 500ms - increased from 100ms for production stability
                    }
                }
                $this->fastOut("✅ Total indexed: {$stats['indexed']} products");
            } else {
                // Use manual indexing for selective product indexing
                $this->fastManualElasticsearchIndex($productIds);
                $stats['indexed'] = count($productIds);
            }

        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Elasticsearch indexing failed: " . $e->getMessage());
            Log::error('Fast import Elasticsearch indexing failed', ['exception' => $e]);
            $stats['failed'] = count($productIds) - $stats['indexed'];
        }

        return $stats;
    }

    /**
     * Queue Elasticsearch indexing jobs (NON-BLOCKING for production)
     * This prevents the server from becoming unresponsive during indexing
     */
    private function fastQueueElasticsearchIndexing(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        try {
            // Split into chunks of 200 products each
            $chunkSize = 200;
            $chunks = array_chunk($productIds, $chunkSize);
            $totalChunks = count($chunks);

            $this->fastOut("Splitting " . count($productIds) . " products into {$totalChunks} chunks for background indexing");

            foreach ($chunks as $chunkIndex => $chunk) {
                \App\Jobs\IndexProductsToElasticsearch::dispatch(
                    $chunk,
                    $chunkIndex,
                    $totalChunks
                )->onQueue('elasticsearch');
            }

            $this->fastOut("✅ Queued {$totalChunks} Elasticsearch indexing jobs");
            $this->fastOut("ℹ️  Jobs will be processed by queue workers in the background");
            $this->fastOut("ℹ️  Monitor progress: php artisan queue:monitor");

        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Failed to queue Elasticsearch indexing: " . $e->getMessage());
            Log::error('Failed to queue Elasticsearch indexing', [
                'product_count' => count($productIds),
                'exception' => $e
            ]);
        }
    }

    /**
     * Manual Elasticsearch indexing for specific products
     */
    private function fastManualElasticsearchIndex(array $productIds): void
    {
        try {
            $elasticsearchService = app(\App\Services\ElasticsearchService::class);
            $client = $elasticsearchService::client();
            $indexName = $elasticsearchService::indexName();

            $this->fastOut("Indexing " . count($productIds) . " products to Elasticsearch index: {$indexName}");

            // Fetch only the imported products with ALL necessary relationships
            // This prevents N+1 queries which can cause timeouts
            // CRITICAL: If productIds > 5000, chunk it to avoid PostgreSQL parameter limit
            $products = collect();

            foreach (array_chunk($productIds, 5000) as $idChunk) {
                $chunk = \App\Models\Product::with([
                    'categories',
                    'tags',
                    'brand',
                    'product_thumbnail',
                    'product_meta_image',
                    'product_galleries',
                    'variations',
                    'attributes.attribute_values',
                    'tax',
                    'store',
                ])
                    ->whereIn('id', $idChunk)
                    ->where('status', 1)
                    ->where('is_approved', 1)
                    ->get();

                $products = $products->merge($chunk);
            }

            if ($products->isEmpty()) {
                $this->fastOut("⚠️ No approved/active products found to index");
                return;
            }

            // PERFORMANCE: Fetch ALL reviews in one bulk query (like artisan reindex does)
            // Instead of making 5000 GET requests (50ms × 5000 = 250 seconds in production)
            // We make 1 SQL query to get all reviews for all products
            $this->fastOut("Fetching reviews for " . count($productIds) . " products...");

            // For very large product sets, fetch reviews in chunks to avoid memory issues
            $reviewRows = collect();
            if (count($productIds) > 1000) {
                $this->fastOut("Large product set - fetching reviews in chunks...");
                $productChunks = array_chunk($productIds, 1000);
                foreach ($productChunks as $chunkIdx => $productChunk) {
                    $chunkReviews = DB::table('reviews as r')
                        ->leftJoin('users as u', 'u.id', '=', 'r.consumer_id')
                        ->whereIn('r.product_id', $productChunk)
                        ->whereNull('r.deleted_at')
                        ->select(
                            'r.id', 'r.product_id', 'r.consumer_id', 'r.rating', 'r.description',
                            'r.created_at', 'r.updated_at',
                            'u.name as consumer_name', 'u.email as consumer_email',
                            'u.profile_image_id as consumer_profile_image_id'
                        )
                        ->orderBy('r.product_id')
                        ->orderBy('r.created_at', 'desc')
                        ->get();

                    $reviewRows = $reviewRows->merge($chunkReviews);

                    // Clear memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
                $reviewRows = $reviewRows->groupBy('product_id');
            } else {
                // CRITICAL: Chunk to avoid PostgreSQL parameter limit
                $reviewRows = collect();
                foreach (array_chunk($productIds, 5000) as $idChunk) {
                    $chunkReviews = DB::table('reviews as r')
                        ->leftJoin('users as u', 'u.id', '=', 'r.consumer_id')
                        ->whereIn('r.product_id', $idChunk)
                        ->whereNull('r.deleted_at')
                        ->select(
                            'r.id', 'r.product_id', 'r.consumer_id', 'r.rating', 'r.description',
                            'r.created_at', 'r.updated_at',
                            'u.name as consumer_name', 'u.email as consumer_email',
                            'u.profile_image_id as consumer_profile_image_id'
                        )
                        ->orderBy('r.product_id')
                        ->orderBy('r.created_at', 'desc')
                        ->get();

                    $reviewRows = $reviewRows->merge($chunkReviews);
                }
                $reviewRows = $reviewRows->groupBy('product_id');
            }

            $reviewRows = $reviewRows
                ->map(function($reviews) {
                    // Limit to 1000 reviews per product to prevent document size issues
                    $limitedReviews = $reviews->take(1000);

                    return $limitedReviews->map(function($r) {
                        // Clean description to remove control characters that cause JSON errors
                        $cleanDescription = null;
                        if ($r->description) {
                            // Remove control characters (ASCII 0-31 except tab, newline, carriage return)
                            $cleanDescription = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $r->description);
                            // Replace multiple spaces/newlines with single space
                            $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription);
                            // Trim whitespace
                            $cleanDescription = trim($cleanDescription);
                            // If empty after cleaning, set to null
                            if ($cleanDescription === '') {
                                $cleanDescription = null;
                            }
                        }

                        return [
                            'id' => (int)$r->id,
                            'consumer_id' => $r->consumer_id ? (int)$r->consumer_id : null,
                            'product_id' => (int)$r->product_id,
                            'rating' => (int)$r->rating,
                            'description' => $cleanDescription,
                            'created_at' => $r->created_at ? str_replace(' ', 'T', $r->created_at) : null,
                            'updated_at' => $r->updated_at ? str_replace(' ', 'T', $r->updated_at) : null,
                            'consumer' => $r->consumer_id ? [
                                'id' => (int)$r->consumer_id,
                                'name' => $r->consumer_name,
                                'email' => $r->consumer_email,
                                'profile_image_id' => $r->consumer_profile_image_id ? (int)$r->consumer_profile_image_id : null,
                            ] : null,
                        ];
                    })->all();
                });

            // Calculate review stats from the reviews
            $reviewStatsMap = $reviewRows->map(function($reviews) {
                if (empty($reviews)) {
                    return [
                        'avg' => null,
                        'count' => 0,
                        'ratings_distribution' => [0, 0, 0, 0, 0]
                    ];
                }
                $ratings = array_column($reviews, 'rating');
                $distribution = [0, 0, 0, 0, 0];
                foreach ($ratings as $rating) {
                    if ($rating >= 1 && $rating <= 5) {
                        $distribution[$rating - 1]++;
                    }
                }
                return [
                    'avg' => round(array_sum($ratings) / count($ratings), 2),
                    'count' => count($ratings),
                    'ratings_distribution' => $distribution
                ];
            });

            $this->fastOut("Reviews fetched for " . $reviewRows->count() . " products with reviews");

            // Check memory usage before starting to build documents
            $memoryUsage = memory_get_usage(true);
            $memoryMB = round($memoryUsage / 1024 / 1024, 2);
            $this->fastOut("Memory usage before building documents: {$memoryMB} MB");

            $count = 0;
            $params = ['body' => []];

            foreach ($products as $product) {
                try {
                    $params['body'][] = [
                        'index' => [
                            '_index' => $indexName,
                            '_id' => $product->id,
                        ]
                    ];

                    // Get new searchable data
                    $newData = $product->toSearchableArray();

                    // Add reviews from database (pre-fetched in bulk query above)
                    // This is MUCH faster than making GET requests to ES (1 SQL query vs 5000 ES requests)
                    if (isset($reviewRows[$product->id])) {
                        $reviews = $reviewRows[$product->id];
                        $stats = $reviewStatsMap[$product->id];

                        $newData['reviews'] = $reviews;
                        $newData['reviews_count'] = $stats['count'];
                        $newData['reviews_avg_rating'] = $stats['avg'];
                        $newData['rating_count'] = $stats['count'];
                        $newData['review_ratings'] = $stats['ratings_distribution'];
                    } else {
                        // No reviews for this product
                        $newData['reviews'] = [];
                        $newData['reviews_count'] = 0;
                        $newData['reviews_avg_rating'] = 0;
                        $newData['rating_count'] = 0;
                        $newData['review_ratings'] = [0, 0, 0, 0, 0];
                    }

                    $params['body'][] = $newData;

                    $count++;
                } catch (\Exception $e) {
                    $this->fastOut("⚠️ Failed to build document for product {$product->id}: " . $e->getMessage());
                    Log::error("Failed to build Elasticsearch document for product", [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Remove the index operation we just added (last item in params body)
                    array_pop($params['body']);
                    continue;
                }

                // Send in batches of 100 with retry logic (reduced from 500 for production stability)
                if ($count % 100 === 0) {
                    $maxRetries = 3;
                    $retryCount = 0;
                    $bulkSuccess = false;

                    while ($retryCount < $maxRetries && !$bulkSuccess) {
                        try {
                            $response = $client->bulk($params);
                            $bulkSuccess = true;
                            $this->fastOut("Indexed {$count} products...");

                            // Check for errors in bulk response
                            if (isset($response['errors']) && $response['errors'] === true) {
                                $errorCount = 0;
                                foreach ($response['items'] as $item) {
                                    if (isset($item['index']['error'])) {
                                        $errorCount++;
                                        if ($errorCount <= 3) { // Log first 3 errors
                                            $this->fastOut("⚠️ Index error: " . json_encode($item['index']['error']));
                                        }
                                    }
                                }
                                if ($errorCount > 3) {
                                    $this->fastOut("⚠️ ... and " . ($errorCount - 3) . " more errors");
                                }
                            }
                        } catch (\Exception $bulkError) {
                            $retryCount++;
                            if ($retryCount >= $maxRetries) {
                                $this->fastOut("⚠️ Bulk index failed after {$maxRetries} retries: " . $bulkError->getMessage());
                                Log::error("Fast import bulk index failed after retries", [
                                    'error' => $bulkError->getMessage(),
                                    'trace' => $bulkError->getTraceAsString(),
                                    'products_in_batch' => count($params['body']) / 2,
                                    'product_ids' => array_column(array_filter($params['body'], function($item) {
                                        return isset($item['id']);
                                    }), 'id')
                                ]);
                            } else {
                                $this->fastOut("⚠️ Bulk index failed (attempt {$retryCount}/{$maxRetries}), retrying...");
                                // Exponential backoff: 200ms, 400ms, 800ms
                                usleep(pow(2, $retryCount) * 100000);
                            }
                        }
                    }
                    $params = ['body' => []];

                    // Force garbage collection to free memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }

            // Send remaining with retry logic
            if (!empty($params['body'])) {
                $maxRetries = 3;
                $retryCount = 0;
                $bulkSuccess = false;

                while ($retryCount < $maxRetries && !$bulkSuccess) {
                    try {
                        $response = $client->bulk($params);
                        $bulkSuccess = true;

                        // Check for errors in bulk response
                        if (isset($response['errors']) && $response['errors'] === true) {
                            $errorCount = 0;
                            foreach ($response['items'] as $item) {
                                if (isset($item['index']['error'])) {
                                    $errorCount++;
                                    if ($errorCount <= 3) { // Log first 3 errors
                                        $this->fastOut("⚠️ Index error: " . json_encode($item['index']['error']));
                                    }
                                }
                            }
                            if ($errorCount > 3) {
                                $this->fastOut("⚠️ ... and " . ($errorCount - 3) . " more errors");
                            }
                        }
                    } catch (\Exception $bulkError) {
                        $retryCount++;
                        if ($retryCount >= $maxRetries) {
                            $this->fastOut("⚠️ Final bulk index failed after {$maxRetries} retries: " . $bulkError->getMessage());
                            Log::error("Fast import final bulk index failed after retries", [
                                'error' => $bulkError->getMessage(),
                                'trace' => $bulkError->getTraceAsString(),
                                'products_in_batch' => count($params['body']) / 2,
                                'product_ids' => array_column(array_filter($params['body'], function($item) {
                                    return isset($item['id']);
                                }), 'id')
                            ]);
                        } else {
                            $this->fastOut("⚠️ Final bulk index failed (attempt {$retryCount}/{$maxRetries}), retrying...");
                            usleep(pow(2, $retryCount) * 100000);
                        }
                    }
                }
            }

            $this->fastOut("✅ Elasticsearch indexing completed: {$count} products indexed");
        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Manual Elasticsearch indexing failed: " . $e->getMessage());
            Log::error('Manual Elasticsearch indexing failed', ['exception' => $e]);
        }
    }

    /**
     * Clear cache for a single product immediately (called during import)
     * This ensures cache is cleared RIGHT AWAY when product is created/updated
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
            Log::warning("Failed to clear cache for product {$productId} during fast import", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Bump products cache version to invalidate ALL product caches
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
            Log::error('Error bumping products cache version in fast import', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear product-related caches after import
     */
    private function fastClearProductCaches(): void
    {
        try {
            $this->fastOut("\n🗑️ Clearing product-related caches...");

            // Bump products cache version to invalidate ALL product API responses
            $currentVersion = (int) \Illuminate\Support\Facades\Cache::get('products_cache_version', 1);
            $newVersion = $currentVersion + 1;
            \Illuminate\Support\Facades\Cache::put('products_cache_version', $newVersion, now()->addDays(365));
            $this->fastOut("📌 Products cache version bumped: {$currentVersion} → {$newVersion}");

            // Clear Laravel cache
            \Illuminate\Support\Facades\Cache::tags(['products'])->flush();

            // Use ApiCacheRefresher to refresh common product endpoints
            $urlsToRefresh = [
                '/api/product?status=1&page=1',
                '/api/product?status=1&trending=1&page=1',
                '/api/product?status=1&is_featured=1&page=1',
                '/api/categoryFront',
                '/api/settingsFront',
            ];

            // Refresh category-specific pages
            try {
                $categories = \Illuminate\Support\Facades\DB::table('categories')
                    ->whereNotNull('slug')
                    ->limit(20) // Top 20 categories
                    ->pluck('slug');

                foreach ($categories as $slug) {
                    $urlsToRefresh[] = "/api/product?category={$slug}&page=1";
                }
            } catch (\Throwable $e) {
                $this->fastOut("⚠️ Could not fetch categories for cache refresh: " . $e->getMessage());
            }

            // Trigger cache refresh
            \App\Services\ApiCacheRefresher::refresh($urlsToRefresh);

            $this->fastOut("✅ Cache clearing completed (" . count($urlsToRefresh) . " endpoints refreshed)");
        } catch (\Throwable $e) {
            $this->fastOut("⚠️ Cache clearing failed: " . $e->getMessage());
            Log::error('Fast import cache clearing failed', ['exception' => $e]);
        }
    }

    /**
     * Show import job history
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $jobs = ImportJob::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        }

        return view('import-fast-history', compact('jobs'));
    }

    /**
     * Show details for a specific batch
     */
    public function batchDetails(Request $request, string $batchId)
    {
        $jobs = ImportJob::where('batch_id', $batchId)
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

            return redirect()->route('import-fast.history')
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
            'started_at' => $jobs->min('started_at'),
            'completed_at' => $jobs->max('completed_at'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'batch' => $batchStats,
                'jobs' => $jobs,
            ]);
        }

        return view('import-fast-batch-details', compact('jobs', 'batchStats'));
    }

    /**
     * Resume failed import jobs
     */
    public function resumeFailed(Request $request)
    {
        $batchId = $request->input('batch_id');

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID required',
            ], 400);
        }

        $failedJobs = ImportJob::where('batch_id', $batchId)
            ->where('status', 'failed')
            ->get();

        if ($failedJobs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No failed jobs found for this batch',
            ]);
        }

        // Mark jobs as pending for retry
        foreach ($failedJobs as $job) {
            $job->update(['status' => 'pending', 'error_message' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => "Marked {$failedJobs->count()} failed job(s) for retry",
            'count' => $failedJobs->count(),
        ]);
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
        //return $deliveryMapping[$centresKey] ?? 'Ships in 5-6 work days';
        return $deliveryMapping[$centresKey] ?? '';
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

            $result = "Ships in {$firstDay}-{$secondDay} work days";

            // Return in format: "Ships in X-Y work days"
            return $result;
        }

        // Pattern to match single number "Ships in X days"
        if (preg_match('/(\d+)/', $text, $matches)) {
            $days = (int)$matches[1] + $increment;
            $result = "Ships in {$days} work days";

            return $result;
        }

        return $text;
    }

    /**
     * Build HTML table from product_information items
     * Creates a table with two columns: display_name and displayable_text
     *
     * @param array $productInfo The product_information array from JSON
     * @return string|null HTML table or null if no items
     */
    private function buildSpecificationsTable(array $productInfo): ?string
    {
        $items = $productInfo['items'] ?? [];

        if (empty($items) || !is_array($items)) {
            return null;
        }

        // Start building HTML table
        $html = '<table class="table table-bordered specifications-table">';
        $html .= '<thead><tr><th>Specification</th><th>Details</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($items as $item) {
            $displayName = $item['display_name'] ?? null;
            $displayableText = $item['displayable_text'] ?? null;

            // Skip if both are empty
            if (empty($displayName) && empty($displayableText)) {
                continue;
            }

            // Clean up the displayable text
            // Remove markdown links like [Fashion](/fashion) and keep just the text
            if ($displayableText) {
                // Remove markdown link syntax: [Text](/url) -> Text
                $displayableText = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $displayableText);

                // Remove markdown list bullets
                $displayableText = preg_replace('/^[\-\*]\s+/', '', $displayableText);

                // Remove escaped parentheses
                $displayableText = str_replace(['\\(', '\\)'], ['(', ')'], $displayableText);

                // Convert markdown separators to readable format
                $displayableText = str_replace(' / ', ' → ', $displayableText);

                // Trim whitespace
                $displayableText = trim($displayableText);
            }

            // Add row to table
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($displayName ?? 'N/A') . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($displayableText ?? 'N/A') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}

