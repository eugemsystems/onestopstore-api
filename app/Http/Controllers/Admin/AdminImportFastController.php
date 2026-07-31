<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ImportFastController;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminImportFastController extends ImportFastController
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
                        'message' => 'Unauthorized. Only administrators can access the fast import feature.'
                    ], 403);
                }

                return redirect()->route('admin.dashboard')
                    ->with('error', 'Unauthorized. Only administrators can access the fast import feature.');
            }

            return $next($request);
        });
    }

    /**
     * Show upload + run interface (admin version)
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

        return view('admin.import-fast.index', compact('existingFiles'));
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

        $dir = storage_path('app/jsonfiles');
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
        $filepath = storage_path('app/jsonfiles/' . $filename);

        if (!file_exists($filepath) || !is_file($filepath)) {
            abort(404, 'File not found');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display import history
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $jobs = ImportJob::with('user')
            ->fastImport() // Only show fast-import jobs
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        }

        return view('admin.import-fast.history', compact('jobs'));
    }

    /**
     * Run import using queued jobs (OPTIMIZED FOR PRODUCTION)
     * This processes large JSON files in chunks without overwhelming the server
     */
    public function runQueued(Request $request)
    {
        $request->validate([
            'percentage' => ['required', 'numeric', 'gt:0'],
            'todaysRate' => ['required', 'numeric', 'gt:0'],
            'selectedFiles' => ['nullable', 'array'],
            'selectedFiles.*' => ['string'],
        ]);

        $percentage = (float) $request->input('percentage');
        $todaysRate = (float) $request->input('todaysRate');
        $selectedFiles = $request->input('selectedFiles', []);

        // Generate batch ID
        $batchId = 'import_' . date('Ymd_His') . '_' . \Illuminate\Support\Str::random(8);

        $dir = storage_path('app/jsonfiles');

        // Get files to process
        if (!empty($selectedFiles)) {
            $files = [];
            foreach ($selectedFiles as $filename) {
                $filepath = $dir . '/' . $filename;
                if (file_exists($filepath) && basename($filename) !== 'extracted_categories.json') {
                    $files[] = $filepath;
                }
            }
        } else {
            $files = array_filter(
                glob($dir . '/*.json') ?: [],
                fn($f) => basename($f) !== 'extracted_categories.json'
            );
        }

        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No files selected or files not found',
            ], 400);
        }

        $totalFiles = count($files);
        $totalJobsQueued = 0;

        // Process each file
        foreach ($files as $filepath) {
            $filename = basename($filepath);

            // Create import job record
            $importJob = \App\Models\ImportJob::create([
                'batch_id' => $batchId,
                'import_type' => 'fast-import-queued',
                'filename' => $filename,
                'status' => 'pending',
                'percentage' => $percentage,
                'todays_rate' => $todaysRate,
                'user_id' => auth()->id() ?? null,
            ]);

            try {
                // Read and parse JSON file
                $jsonContent = file_get_contents($filepath);
                $productsArr = json_decode($jsonContent, true);

                if (!is_array($productsArr)) {
                    $importJob->markAsFailed('Invalid JSON format');
                    continue;
                }

                // Handle single product JSON
                if (isset($productsArr['core']) && isset($productsArr['core']['id'])) {
                    $productsArr = [$productsArr];
                }

                $totalProducts = count($productsArr);
                $importJob->update(['total_items' => $totalProducts]);

                // Split into chunks of 50 products each (smaller = better memory management)
                $chunkSize = 50;
                $chunks = array_chunk($productsArr, $chunkSize);
                $totalChunks = count($chunks);

                // Dispatch jobs for each chunk
                foreach ($chunks as $chunkIndex => $productsChunk) {
                    \App\Jobs\ProcessFastImportChunk::dispatch(
                        $importJob->id,
                        $filepath,
                        $chunkIndex,
                        $totalChunks,
                        $productsChunk,
                        $percentage,
                        $todaysRate
                    )->onQueue('import');

                    $totalJobsQueued++;
                }

                $importJob->update(['status' => 'processing']);

            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to queue import for file', [
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
                $importJob->markAsFailed($e->getMessage());
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Queued {$totalJobsQueued} jobs for processing",
            'batch_id' => $batchId,
            'total_files' => $totalFiles,
            'total_jobs' => $totalJobsQueued,
            'note' => 'Import is running in the background. Check history page for progress.',
        ]);
    }

    /**
     * Queue Elasticsearch indexing for products
     */
    public function queueIndexing(Request $request)
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $productIds = $request->input('product_ids');

        if (empty($productIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No product IDs provided',
            ], 400);
        }

        // Split into chunks of 200 products
        $chunkSize = 200;
        $chunks = array_chunk($productIds, $chunkSize);
        $totalChunks = count($chunks);

        foreach ($chunks as $chunkIndex => $chunk) {
            \App\Jobs\IndexProductsToElasticsearch::dispatch(
                $chunk,
                $chunkIndex,
                $totalChunks
            )->onQueue('elasticsearch');
        }

        return response()->json([
            'success' => true,
            'message' => "Queued {$totalChunks} indexing jobs",
            'total_products' => count($productIds),
            'chunks' => $totalChunks,
        ]);
    }

    /**
     * Display import history
     */
    public function history_old(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $jobs = ImportJob::with('user')
            ->fastImport() // Only show fast-import jobs
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        }

        return view('admin.import-fast.history', compact('jobs'));
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

            return redirect()->route('admin.import-fast.history')
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

        return view('admin.import-fast.batch-details', compact('jobs', 'batchStats'));
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
                'import_type' => 'fast-import',
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
}

