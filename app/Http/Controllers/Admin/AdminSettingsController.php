<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AdminSettingsController extends BaseAdminController
{
    protected string $permissionPrefix = 'settings';

    public function index()
    {
        $this->checkPermission('index');

        $setting = Setting::first();

        // If no settings exist, create default
        if (!$setting) {
            $setting = Setting::create([
                'values' => []
            ]);
        }

        return view('admin.settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $this->checkPermission('edit');

        $request->validate([
            'values' => 'required|json'
        ]);

        DB::beginTransaction();
        try {
            $setting = Setting::first();

            if (!$setting) {
                $setting = Setting::create([
                    'values' => json_decode($request->values, true)
                ]);
            } else {
                $setting->update([
                    'values' => json_decode($request->values, true)
                ]);
            }

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('settings')->event('updated')->on($setting)
                    ->log('System settings updated');
            } catch (\Throwable) {}

            // Clear cache
            reCacheSettings();

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Stream Elasticsearch reindex command output via Server-Sent Events
     */
    public function reindexStream(Request $request)
    {
        $this->checkPermission('edit');

        return response()->stream(function () use ($request) {
            // Set up SSE headers
            echo "retry: 10000\n\n";

            // Disable output buffering
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            @ob_end_clean();
            ob_implicit_flush(true);

            // Send initial message
            $this->sendSSE('output', '🚀 Starting Elasticsearch reindex...');
            $this->sendSSE('output', '');

            try {
                // Get the artisan path
                $artisan = base_path('artisan');
                $php = PHP_BINARY;

                // Build command with options
                $command = [
                    $php,
                    $artisan,
                    'es:reindex-products'
                ];

                // Add chunk size (default 1000)
                $chunk = $request->input('chunk', 1000);
                $command[] = '--chunk=' . $chunk;

                // Add from_id if provided
                if ($request->filled('from_id')) {
                    $command[] = '--from-id=' . $request->input('from_id');
                }

                // Add to_id if provided
                if ($request->filled('to_id')) {
                    $command[] = '--to-id=' . $request->input('to_id');
                }

                // Add boolean flags
                if ($request->input('test') == 1) {
                    $command[] = '--test';
                }

                if ($request->input('dry_run') == 1) {
                    $command[] = '--dry-run';
                }

                if ($request->input('recreate_index') == 1) {
                    $command[] = '--recreate-index';
                }

                if ($request->input('disable_refresh') == 1) {
                    $command[] = '--disable-refresh';
                }

                if ($request->input('index_all') == 1) {
                    $command[] = '--all';
                }

                // Log the command being executed
                $commandStr = implode(' ', $command);
                $this->sendSSE('output', '📝 Executing command:');
                $this->sendSSE('output', $commandStr);
                $this->sendSSE('output', '');
                $this->sendSSE('output', str_repeat('─', 80));
                $this->sendSSE('output', '');

                // Create process for Windows compatibility
                $process = new Process($command, base_path()); // Set working directory

                // Set long timeout
                $process->setTimeout(3600); // 1 hour
                $process->setIdleTimeout(3600);

                // Inherit environment variables (CRITICAL for Elasticsearch credentials!)
                $process->inheritEnvironmentVariables(true);

                // Run process and stream output
                $process->run(function ($type, $buffer) {
                    // Stream each line of output
                    $lines = explode("\n", $buffer);
                    foreach ($lines as $line) {
                        if (trim($line)) {
                            $this->sendSSE('output', $line);
                        }
                    }
                });

                // Check if successful
                if ($process->isSuccessful()) {
                    $this->sendSSE('output', '');
                    $this->sendSSE('output', str_repeat('─', 80));
                    $this->sendSSE('complete', 'Reindex completed successfully!');
                } else {
                    $errorOutput = $process->getErrorOutput();
                    $this->sendSSE('error', 'Process failed: ' . ($errorOutput ?: 'Unknown error'));
                }

            } catch (\Exception $e) {
                $this->sendSSE('error', $e->getMessage());
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Send Server-Sent Event
     */
    private function sendSSE($type, $message)
    {
        echo "data: " . json_encode([
            'type' => $type,
            'message' => $message
        ]) . "\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
