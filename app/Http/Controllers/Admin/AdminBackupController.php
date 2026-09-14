<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\RunDatabaseBackupJob;
use App\Jobs\UploadLocalBackupJob;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminBackupController extends BaseAdminController
{
    protected string $permissionPrefix = 'backups';

    public function index(Request $request, DatabaseBackupService $service)
    {
        $this->checkPermission('view');

        $r2Configured = $service->r2Configured();
        $groups = [];
        $listError = null;

        foreach ($service->targets() as $key => $target) {
            $configured = !empty($target['host']) && !empty($target['database']) && !empty($target['username']);

            $local = collect();
            $remote = collect();

            if ($target['enabled'] && $configured) {
                $local = $service->listLocalBackups($key)->map(function ($b) use ($service) {
                    $b['size_human'] = $service->humanSize($b['size']);
                    return $b;
                });

                if ($r2Configured) {
                    try {
                        $remote = $service->listBackups($key)->map(function ($b) use ($service) {
                            $b['size_human'] = $service->humanSize($b['size']);
                            return $b;
                        });
                    } catch (Throwable $e) {
                        Log::error("Failed to list R2 backups for target '{$key}'", ['error' => $e->getMessage()]);
                        $listError = $listError ?? ('Could not reach R2 to list backups: ' . $e->getMessage());
                    }
                }
            }

            $lastRemote = $remote->first();
            $lastRemoteAge = $lastRemote ? $lastRemote['last_modified']->diffInHours(now()) : null;

            $groups[$key] = [
                'key' => $key,
                'label' => $target['label'],
                'enabled' => $target['enabled'],
                'configured' => $configured,
                'local' => $local,
                'remote' => $remote,
                'remote_total_human' => $service->humanSize((int) $remote->sum('size')),
                'is_stale' => $target['enabled'] && $configured && ($lastRemoteAge === null || $lastRemoteAge > 26),
            ];
        }

        $totalRemoteBytes = collect($groups)->sum(fn ($g) => $g['remote']->sum('size'));
        $freeTierBytes = 10 * 1000 * 1000 * 1000; // Cloudflare R2 free tier

        return view('admin.backups.index', [
            'groups' => $groups,
            'r2Configured' => $r2Configured,
            'listError' => $listError,
            'totalHuman' => $service->humanSize($totalRemoteBytes),
            'usagePercent' => min(100, round(($totalRemoteBytes / $freeTierBytes) * 100, 1)),
            'encrypted' => (bool) config('backup.encryption_key'),
        ]);
    }

    /**
     * Dump a target (or all enabled targets) to local disk only.
     */
    public function run(Request $request, ?string $target = null)
    {
        $this->checkPermission('run');

        RunDatabaseBackupJob::dispatch($target);

        $message = $target
            ? "Dump for '{$target}' started in the background — refresh in a bit to see it in the local list."
            : 'Dumps started in the background for all enabled targets — refresh in a bit to see them appear.';

        return redirect()->route('admin.backups.index')->with('success', $message);
    }

    /**
     * Push a local dump to R2.
     */
    public function upload(string $target, string $filename)
    {
        $this->checkPermission('run');

        UploadLocalBackupJob::dispatch($target, $filename);

        return redirect()->route('admin.backups.index')
            ->with('success', "Upload started for {$filename} — refresh in a bit to see it under off-server backups.");
    }

    public function downloadLocal(string $target, string $filename, DatabaseBackupService $service)
    {
        $this->checkPermission('download');

        $path = $service->localBackupPath($target, $filename);
        if (!is_file($path)) {
            return redirect()->route('admin.backups.index')->with('error', 'That local backup no longer exists.');
        }

        return response()->download($path, basename($filename));
    }

    public function destroyLocal(string $target, string $filename, DatabaseBackupService $service)
    {
        $this->checkPermission('delete');

        $service->deleteLocalBackup($target, $filename);

        return redirect()->route('admin.backups.index')->with('success', 'Local backup deleted.');
    }

    public function download(string $target, string $filename, DatabaseBackupService $service)
    {
        $this->checkPermission('download');

        $url = $service->temporaryDownloadUrl($target, $filename, 15);

        return redirect($url);
    }

    public function destroy(string $target, string $filename, DatabaseBackupService $service)
    {
        $this->checkPermission('delete');

        $service->deleteBackup($target, $filename);

        return redirect()->route('admin.backups.index')->with('success', 'Off-server backup deleted.');
    }
}
