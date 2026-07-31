<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class AdminElasticsearchController extends Controller
{
    /**
     * Show the Elasticsearch reindex page
     */
    public function reindex()
    {
        return view('admin.elasticsearch.reindex');
    }

    /**
     * Delete the Elasticsearch index
     */
    public function deleteIndex()
    {
        try {
            $client = ElasticsearchService::client();
            $index = ElasticsearchService::indexName();

            // Check if index exists
            $exists = $client->indices()->exists(['index' => $index]);

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => "Index '{$index}' does not exist."
                ]);
            }

            // Delete the index
            $client->indices()->delete(['index' => $index]);

            return response()->json([
                'success' => true,
                'message' => "Index '{$index}' deleted successfully."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting index: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check the Elasticsearch index status
     */
    public function checkIndex()
    {
        try {
            $client = ElasticsearchService::client();
            $index = ElasticsearchService::indexName();

            // Check if index exists
            $exists = $client->indices()->exists(['index' => $index]);

            $data = [
                'index' => $index,
                'exists' => $exists,
            ];

            if ($exists) {
                // Get index stats
                $stats = $client->indices()->stats(['index' => $index]);
                $health = $client->cluster()->health(['index' => $index]);

                $data['count'] = $stats['indices'][$index]['total']['docs']['count'] ?? 0;
                $data['size'] = $this->formatBytes($stats['indices'][$index]['total']['store']['size_in_bytes'] ?? 0);
                $data['health'] = $health['status'] ?? 'unknown';
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error checking index: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Run artisan command and stream output
     */
    public function runArtisanCommand(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        // Accept both GET and POST
        $command = $request->input('command');
        $arguments = $request->input('arguments', []);

        if (!$command) {
            return response()->json([
                'success' => false,
                'message' => 'Command parameter is required'
            ], 400);
        }

        // Whitelist of allowed commands for security
        $allowedCommands = [
            'cache:clear',
            'view:clear',
            'config:clear',
            'route:clear',
            'optimize',
            'optimize:clear',
            'categories:cache-tree',
        ];

        if (!in_array($command, $allowedCommands)) {
            return response()->json([
                'success' => false,
                'message' => 'Command not allowed'
            ], 403);
        }

        return response()->stream(function () use ($command, $arguments) {
            // Disable all output buffering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Start fresh output buffering
            ob_start();

            $safeFlush = function() {
                try {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    @flush();
                } catch (\Exception $e) {
                    // Silently handle flush errors
                }
            };

            echo "data: " . json_encode(['type' => 'status', 'message' => "Running command: {$command}"]) . "\n\n";
            $safeFlush();

            try {
                Artisan::call($command, $arguments, new class($safeFlush) extends \Symfony\Component\Console\Output\StreamOutput {
                    private $safeFlush;

                    public function __construct($safeFlush)
                    {
                        parent::__construct(fopen('php://output', 'w'));
                        $this->setDecorated(false);
                        $this->safeFlush = $safeFlush;
                    }

                    protected function doWrite(string $message, bool $newline): void
                    {
                        if (trim($message)) {
                            echo "data: " . json_encode(['type' => 'output', 'message' => $message]) . "\n\n";
                            ($this->safeFlush)();
                        }
                        if ($newline) {
                            ($this->safeFlush)();
                        }
                    }
                });

                echo "data: " . json_encode(['type' => 'complete', 'message' => "Command '{$command}' completed successfully!"]) . "\n\n";
            } catch (\Exception $e) {
                echo "data: " . json_encode(['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]) . "\n\n";
            }

            $safeFlush();

            if (ob_get_level() > 0) {
                ob_end_flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Start the reindex process and stream output
     */
    /**
     * Launch a reindex as a fully detached background process.
     * Returns a job_id immediately; frontend polls reindexPoll for progress.
     */
    public function reindexStart(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'from_id'         => 'nullable|integer|min:1',
            'to_id'           => 'nullable|integer|min:1',
            'chunk'           => 'nullable|integer|min:1|max:10000',
            'dry_run'         => 'nullable|boolean',
            'disable_refresh' => 'nullable|boolean',
            'recreate_index'  => 'nullable|boolean',
            'test'            => 'nullable|boolean',
            'all'             => 'nullable|boolean',
        ]);

        $arguments = [];
        if ($request->filled('from_id'))        $arguments['--from-id']        = $request->input('from_id');
        if ($request->filled('to_id'))          $arguments['--to-id']          = $request->input('to_id');
        if ($request->filled('chunk'))          $arguments['--chunk']          = $request->input('chunk');
        if ($request->boolean('dry_run'))       $arguments['--dry-run']        = true;
        if ($request->boolean('disable_refresh')) $arguments['--disable-refresh'] = true;
        if ($request->boolean('recreate_index')) $arguments['--recreate-index'] = true;
        if ($request->boolean('test'))          $arguments['--test']           = true;
        if ($request->boolean('all'))           $arguments['--all']            = true;

        $jobId   = uniqid('es_reindex_', true);
        $logFile = storage_path("logs/es-reindex-{$jobId}.log");
        $shFile  = storage_path("logs/es-reindex-{$jobId}.sh");

        $phpBin  = $this->resolvePhpCliPath();
        $artisan = base_path('artisan');

        // Build the artisan command with all options, properly shell-quoted
        $cmdParts = [$phpBin, $artisan, 'es:reindex-products'];
        foreach ($arguments as $key => $value) {
            $cmdParts[] = ($value === true) ? $key : ($key . '=' . $value);
        }
        $escapedCmd = implode(' ', array_map('escapeshellarg', $cmdParts));
        $escapedLog = escapeshellarg($logFile);

        // Write a small shell script so we can append completion markers without quoting nightmares
        $sh  = "#!/bin/sh\n";
        $sh .= "{$escapedCmd} >> {$escapedLog} 2>&1\n";
        $sh .= "if [ \$? -eq 0 ]; then echo '__REINDEX_DONE__' >> {$escapedLog}; else echo '__REINDEX_FAILED__' >> {$escapedLog}; fi\n";
        $sh .= "rm -f " . escapeshellarg($shFile) . "\n";

        file_put_contents($shFile, $sh);
        chmod($shFile, 0755);

        // Launch detached from PHP-FPM's process group using setsid, falling back to nohup
        $launcher = is_executable('/usr/bin/setsid') ? '/usr/bin/setsid' : (is_executable('/bin/setsid') ? '/bin/setsid' : null);
        if ($launcher) {
            exec("{$launcher} " . escapeshellarg($shFile) . " > /dev/null 2>&1 &");
        } else {
            exec("nohup " . escapeshellarg($shFile) . " > /dev/null 2>&1 &");
        }

        \Illuminate\Support\Facades\Cache::put("es_reindex:{$jobId}:logfile", $logFile, now()->addHours(4));

        return response()->json(['job_id' => $jobId]);
    }

    /**
     * Poll progress of a running reindex job.
     * Returns new log lines since the last offset and current status.
     */
    public function reindexPoll(Request $request): \Illuminate\Http\JsonResponse
    {
        $jobId  = $request->input('job_id', '');
        $offset = max(0, (int) $request->input('offset', 0));

        $logFile = \Illuminate\Support\Facades\Cache::get("es_reindex:{$jobId}:logfile");

        if (!$logFile || !file_exists($logFile)) {
            return response()->json(['lines' => [], 'offset' => $offset, 'status' => 'waiting']);
        }

        $allLines = explode("\n", file_get_contents($logFile));
        $total    = count($allLines);
        $status   = 'running';
        $newLines = [];

        for ($i = $offset; $i < $total; $i++) {
            $line = $allLines[$i];
            if ($line === '__REINDEX_DONE__') {
                $status = 'completed';
            } elseif ($line === '__REINDEX_FAILED__') {
                $status = 'failed';
            } elseif (trim($line) !== '') {
                $newLines[] = $line;
            }
        }

        return response()->json([
            'lines'  => $newLines,
            'offset' => $total,
            'status' => $status,
        ]);
    }

    /**
     * Resolve the PHP CLI binary path.
     * PHP_BINARY may point to php-fpm in a web context — we need the CLI binary.
     */
    private function resolvePhpCliPath(): string
    {
        // Allow explicit override via .env
        if ($envPath = env('PHP_CLI_BINARY')) {
            return $envPath;
        }

        $binary = PHP_BINARY;

        // If PHP_BINARY is php-fpm, find the CLI binary in the same directory or common paths
        if (str_contains(basename($binary), 'fpm')) {
            $dir     = dirname($binary);
            $version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $candidates = [
                $dir . '/php',
                $dir . '/php' . $version,
                '/usr/bin/php' . $version,
                '/usr/local/bin/php' . $version,
                '/usr/bin/php',
                '/usr/local/bin/php',
            ];
            foreach ($candidates as $candidate) {
                if (is_executable($candidate)) {
                    return $candidate;
                }
            }
            // Fallback: rely on PATH
            return 'php';
        }

        return $binary;
    }
}
