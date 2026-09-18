<?php

namespace App\Http\Controllers\Admin;

use App\Models\DailyFeedRun;
use App\Models\DailyFeedSetting;
use Illuminate\Http\Request;

class AdminDailyFeedController extends BaseAdminController
{
    protected string $permissionPrefix = 'daily-feeds';

    private const FILE_KEYS = ['global_usd', 'global_zmw', 'zimbabwe_usd', 'zambia_zmw'];

    // The two Global feeds are split into a fixed number of files (see
    // GenerateDailyRegionalFeeds::generateShardedFile) so a single ~800MB
    // file doesn't get cut off mid-transfer to Google. Zimbabwe/Zambia stay
    // single-file — small enough to never need this.
    private const SHARDED_KEYS = ['global_usd', 'global_zmw'];

    private const PATH_TEMPLATES = [
        'global_usd' => 'dailyfeeds/global/usd/global-usd-{n}.xml',
        'global_zmw' => 'dailyfeeds/global/zmw/global-zmw-{n}.xml',
    ];

    public function index()
    {
        $this->checkPermission('index');

        $settings = DailyFeedSetting::all_settings();
        $latestRuns = DailyFeedRun::latestPerFile();
        $history = DailyFeedRun::orderByDesc('id')->limit(40)->get();

        return view('admin.daily-feeds.index', [
            'settings' => $settings,
            'latestRuns' => $latestRuns,
            'history' => $history,
            'fileLabels' => DailyFeedRun::LABELS,
            'shardedKeys' => self::SHARDED_KEYS,
            'pathTemplates' => self::PATH_TEMPLATES,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->checkPermission('update-settings');

        $validated = $request->validate([
            'schedule_time' => ['required', 'date_format:H:i'],
            'global_usd_enabled' => ['nullable'],
            'global_usd_min_price' => ['required', 'numeric', 'min:0'],
            'global_usd_limit' => ['required', 'integer', 'min:0'],
            'global_usd_shard_count' => ['required', 'integer', 'min:1', 'max:200'],
            'global_zmw_enabled' => ['nullable'],
            'global_zmw_min_price' => ['required', 'numeric', 'min:0'],
            'global_zmw_limit' => ['required', 'integer', 'min:0'],
            'global_zmw_shard_count' => ['required', 'integer', 'min:1', 'max:200'],
            'zimbabwe_usd_enabled' => ['nullable'],
            'zimbabwe_usd_min_price' => ['required', 'numeric', 'min:0'],
            'zimbabwe_usd_limit' => ['required', 'integer', 'min:0'],
            'zambia_zmw_enabled' => ['nullable'],
            'zambia_zmw_min_price' => ['required', 'numeric', 'min:0'],
            'zambia_zmw_limit' => ['required', 'integer', 'min:0'],
        ]);

        DailyFeedSetting::set('schedule_time', $validated['schedule_time']);

        foreach (self::FILE_KEYS as $key) {
            DailyFeedSetting::set("{$key}_enabled", $request->has("{$key}_enabled") ? '1' : '0');
            DailyFeedSetting::set("{$key}_min_price", (string) $validated["{$key}_min_price"]);
            DailyFeedSetting::set("{$key}_limit", (string) $validated["{$key}_limit"]);
            if (in_array($key, self::SHARDED_KEYS, true)) {
                DailyFeedSetting::set("{$key}_shard_count", (string) $validated["{$key}_shard_count"]);
            }
        }

        return redirect()->route('admin.daily-feeds.index')->with('success', 'Daily feed settings saved.');
    }

    /**
     * Kick off a background run of all 4 feeds (or a single one via ?only=).
     */
    public function runNow(Request $request)
    {
        $this->checkPermission('run-now');

        $only = $request->query('only');
        $batchId = uniqid('daily_feed_', true);

        // PHP_BINARY resolves to the php-fpm binary (not the CLI) when this
        // controller runs under a web request, which silently spawns fpm
        // instead of running the command — always shell out to the 'php'
        // on PATH here instead.
        $phpBin = PHP_SAPI === 'cli' && PHP_BINARY ? PHP_BINARY : 'php';
        $artisan = base_path('artisan');
        $logFile = storage_path("logs/daily-regional-feed-{$batchId}.log");

        $cmd = '"' . $phpBin . '" "' . $artisan . '"';
        $cmd .= ' feed:daily-regional';
        $cmd .= ' --batch=' . escapeshellarg($batchId);
        if ($only && in_array($only, self::FILE_KEYS, true)) {
            $cmd .= ' --only=' . escapeshellarg($only);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $fullCmd = 'start /B "" ' . $cmd . ' > "' . $logFile . '" 2>&1';
            pclose(popen($fullCmd, 'r'));
        } else {
            exec($cmd . ' > ' . escapeshellarg($logFile) . ' 2>&1 &');
        }

        \Log::info('Daily regional feed run started manually', [
            'batch_id' => $batchId,
            'only' => $only,
            'command' => $cmd,
        ]);

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'status_url' => route('admin.daily-feeds.status', ['batchId' => $batchId]),
        ]);
    }

    /**
     * Poll the status of a batch triggered via runNow() (or the scheduler,
     * if the admin has the batch id).
     */
    public function status($batchId)
    {
        $this->checkPermission('index');

        $runs = DailyFeedRun::where('batch_id', $batchId)->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'runs' => $runs->map(fn ($run) => [
                'file_key' => $run->file_key,
                'label' => DailyFeedRun::LABELS[$run->file_key] ?? $run->file_key,
                'status' => $run->status,
                'records_written' => $run->records_written,
                'duration_seconds' => $run->duration_seconds,
                'file_url' => $run->file_url,
                'file_verified' => $run->file_verified,
                'file_size_bytes' => $run->file_size_bytes,
                'shard_count' => $run->shard_count,
                'error_message' => $run->error_message,
            ]),
            'expected_count' => count(self::FILE_KEYS),
        ]);
    }
}
