<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DatabaseBackupService
{
    protected function localDisk()
    {
        return Storage::disk('backups-local');
    }

    protected function remoteDisk()
    {
        return Storage::disk(config('backup.disk'));
    }

    protected function bucket(): string
    {
        return config('filesystems.disks.' . config('backup.disk') . '.bucket');
    }

    protected function remotePrefix(string $targetKey): string
    {
        return trim(config('backup.path'), '/') . '/' . $targetKey . '/';
    }

    protected function localPrefix(string $targetKey): string
    {
        return $targetKey . '/';
    }

    public function targets(): array
    {
        return config('backup.targets', []);
    }

    protected function isTargetRunnable(array $target): bool
    {
        return ($target['enabled'] ?? false)
            && !empty($target['host'])
            && !empty($target['database'])
            && !empty($target['username']);
    }

    public function r2Configured(): bool
    {
        return (bool) config('filesystems.disks.r2.bucket') && (bool) config('filesystems.disks.r2.key');
    }

    /**
     * Dump every enabled, fully-configured target to local disk. Targets
     * that are enabled but missing connection details are skipped (logged),
     * not failed — one misconfigured target shouldn't block the others.
     * Nothing is uploaded to R2 here — that's a separate, explicit step.
     *
     * @return array<string, array{success: bool, filename?: string, size?: int, duration?: float, error?: string}>
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->targets() as $key => $target) {
            if (!($target['enabled'] ?? false)) {
                continue;
            }

            if (!$this->isTargetRunnable($target)) {
                Log::warning("Backup target '{$key}' is enabled but missing host/database/username — skipping.", ['target' => $key]);
                $results[$key] = ['success' => false, 'error' => 'Enabled but missing connection details'];
                continue;
            }

            $results[$key] = $this->dumpTarget($key, $target);
        }

        if (empty($results)) {
            Log::warning('Database backup run completed with no enabled targets configured.');
        }

        return $results;
    }

    /**
     * Dump one target to local disk, optionally encrypting it. Does not
     * upload anywhere — the admin reviews it in the backups list and
     * decides whether/when to push it to R2.
     */
    public function dumpTarget(string $key, ?array $target = null): array
    {
        $target = $target ?? ($this->targets()[$key] ?? null);
        if (!$target) {
            return ['success' => false, 'error' => "Unknown backup target '{$key}'"];
        }
        if (!$this->isTargetRunnable($target)) {
            return ['success' => false, 'error' => 'Missing host/database/username for this target'];
        }

        $start = microtime(true);
        $timestamp = Carbon::now()->format('Y-m-d-His');
        $workDir = storage_path('app/backup-tmp');
        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        $dumpPath = $workDir . DIRECTORY_SEPARATOR . "{$key}-{$timestamp}.dump";
        $finalPath = $dumpPath;
        $filename = "{$key}-{$timestamp}.dump";

        try {
            $this->dumpDatabase($target, $dumpPath);

            if (config('backup.encryption_key')) {
                $finalPath = $this->encryptFile($dumpPath);
                $filename .= '.enc';
            }

            $size = filesize($finalPath);
            if ($size === false || $size === 0) {
                throw new \RuntimeException('Dump file is empty or unreadable.');
            }

            // Move into the local backups disk (rename is instant — same filesystem).
            $localPath = $this->localDisk()->path($this->localPrefix($key) . $filename);
            if (!is_dir(dirname($localPath))) {
                mkdir(dirname($localPath), 0755, true);
            }
            rename($finalPath, $localPath);

            $duration = round(microtime(true) - $start, 1);

            Log::info("Local database dump completed for target '{$key}'", [
                'target' => $key,
                'filename' => $filename,
                'size' => $size,
                'duration_seconds' => $duration,
            ]);

            return ['success' => true, 'filename' => $filename, 'size' => $size, 'duration' => $duration];
        } catch (Throwable $e) {
            if (file_exists($dumpPath)) {
                @unlink($dumpPath);
            }
            if ($finalPath !== $dumpPath && file_exists($finalPath)) {
                @unlink($finalPath);
            }

            Log::error("Local database dump failed for target '{$key}'", ['target' => $key, 'error' => $e->getMessage()]);
            $this->notifySlack("🚨 Dump FAILED [{$target['label']}]: {$e->getMessage()}");

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function dumpDatabase(array $target, string $outputPath): void
    {
        $result = Process::env(['PGPASSWORD' => $target['password'] ?? ''])
            ->timeout(3600)
            ->run([
                config('backup.pg_dump_path'),
                '--host=' . $target['host'],
                '--port=' . $target['port'],
                '--username=' . $target['username'],
                '--dbname=' . $target['database'],
                '--format=custom',
                '--compress=9',
                '--no-owner',
                '--no-privileges',
                '--file=' . $outputPath,
            ]);

        if (!$result->successful()) {
            throw new \RuntimeException('pg_dump failed: ' . trim($result->errorOutput() ?: $result->output()));
        }
    }

    protected function encryptFile(string $path): string
    {
        $encryptedPath = $path . '.enc';

        $result = Process::env(['BACKUP_ENC_KEY' => config('backup.encryption_key')])
            ->timeout(3600)
            ->run([
                'openssl', 'enc', '-aes-256-cbc', '-salt', '-pbkdf2', '-iter', '100000',
                '-in', $path,
                '-out', $encryptedPath,
                '-pass', 'env:BACKUP_ENC_KEY',
            ]);

        if (!$result->successful()) {
            throw new \RuntimeException('Backup encryption failed: ' . trim($result->errorOutput() ?: $result->output()));
        }

        @unlink($path);

        return $encryptedPath;
    }

    /**
     * List local dump files for one target, newest first, flagged with
     * whether each one has already been pushed to R2.
     *
     * @return Collection<int, array{filename: string, size: int, last_modified: Carbon, uploaded: bool}>
     */
    public function listLocalBackups(string $targetKey): Collection
    {
        $prefix = $this->localPrefix($targetKey);
        $disk = $this->localDisk();

        if (!$disk->exists($targetKey)) {
            return collect();
        }

        $remoteFilenames = $this->r2Configured()
            ? $this->listBackups($targetKey)->pluck('filename')->all()
            : [];

        return collect($disk->files($targetKey))
            ->map(function ($path) use ($disk, $remoteFilenames) {
                $filename = basename($path);
                return [
                    'filename' => $filename,
                    'size' => $disk->size($path),
                    'last_modified' => Carbon::createFromTimestamp($disk->lastModified($path)),
                    'uploaded' => in_array($filename, $remoteFilenames, true),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();
    }

    public function deleteLocalBackup(string $targetKey, string $filename): void
    {
        $this->localDisk()->delete($this->localPrefix($targetKey) . basename($filename));
    }

    public function localBackupPath(string $targetKey, string $filename): string
    {
        return $this->localDisk()->path($this->localPrefix($targetKey) . basename($filename));
    }

    /**
     * Push a previously-dumped local file to R2, then prune older R2
     * backups for that target down to its retention budget. The local
     * copy is left in place — deleting it is a separate, explicit action.
     */
    public function uploadLocalBackup(string $targetKey, string $filename): array
    {
        $filename = basename($filename);
        $localKey = $this->localPrefix($targetKey) . $filename;

        if (!$this->localDisk()->exists($localKey)) {
            return ['success' => false, 'error' => 'Local backup file not found'];
        }

        $target = $this->targets()[$targetKey] ?? null;
        if (!$target) {
            return ['success' => false, 'error' => "Unknown backup target '{$targetKey}'"];
        }

        try {
            $localPath = $this->localDisk()->path($localKey);
            $size = filesize($localPath);

            $remoteKey = $this->remotePrefix($targetKey) . $filename;
            $this->upload($localPath, $remoteKey, $size);

            [$kept, $deleted] = $this->applyRetention($targetKey, $target);

            Log::info("Uploaded local backup to R2 for target '{$targetKey}'", [
                'target' => $targetKey,
                'filename' => $filename,
                'size' => $size,
                'kept' => $kept,
                'deleted' => $deleted,
            ]);

            if (config('backup.notify_on_success')) {
                $this->notifySlack(
                    "✅ Uploaded to R2 [{$target['label']}]: `{$filename}` (" . $this->humanSize($size) . "). "
                    . "{$kept} backup(s) retained, {$deleted} pruned."
                );
            }

            return ['success' => true, 'filename' => $filename, 'size' => $size, 'kept' => $kept, 'deleted' => $deleted];
        } catch (Throwable $e) {
            Log::error("Failed to upload local backup to R2 for target '{$targetKey}'", ['target' => $targetKey, 'error' => $e->getMessage()]);
            $this->notifySlack("🚨 Upload to R2 FAILED [{$target['label']}]: {$e->getMessage()}");

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function upload(string $localPath, string $remoteKey, int $expectedSize): void
    {
        $stream = fopen($localPath, 'r');
        try {
            $this->remoteDisk()->put($remoteKey, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $remoteSize = $this->remoteDisk()->size($remoteKey);
        if ($remoteSize !== $expectedSize) {
            throw new \RuntimeException(
                "Upload verification failed for {$remoteKey}: expected {$expectedSize} bytes, remote has {$remoteSize} bytes."
            );
        }
    }

    /**
     * List R2 backups for one target, newest first.
     *
     * @return Collection<int, array{key: string, filename: string, size: int, last_modified: Carbon}>
     */
    public function listBackups(string $targetKey): Collection
    {
        $result = $this->remoteDisk()->getClient()->listObjectsV2([
            'Bucket' => $this->bucket(),
            'Prefix' => $this->remotePrefix($targetKey),
        ]);

        return collect($result['Contents'] ?? [])
            ->map(fn ($obj) => [
                'key' => $obj['Key'],
                'filename' => basename($obj['Key']),
                'size' => (int) $obj['Size'],
                'last_modified' => Carbon::parse($obj['LastModified']),
            ])
            ->sortByDesc('last_modified')
            ->values();
    }

    /**
     * Keep the newest R2 backups for this target while the running total
     * stays under its byte budget, always keeping at least keep_min
     * regardless of size. Delete the rest. Returns [keptCount, deletedCount].
     */
    protected function applyRetention(string $targetKey, array $target): array
    {
        $backups = $this->listBackups($targetKey);
        $maxBytes = $target['max_bytes'];
        $keepMin = $target['keep_min'];

        $kept = 0;
        $runningSize = 0;
        $toDelete = [];

        foreach ($backups as $backup) {
            if ($kept < $keepMin || $runningSize + $backup['size'] <= $maxBytes) {
                $kept++;
                $runningSize += $backup['size'];
            } else {
                $toDelete[] = $backup['key'];
            }
        }

        foreach ($toDelete as $key) {
            $this->remoteDisk()->delete($key);
        }

        return [$kept, count($toDelete)];
    }

    public function deleteBackup(string $targetKey, string $filename): void
    {
        $this->remoteDisk()->delete($this->remotePrefix($targetKey) . basename($filename));
    }

    public function temporaryDownloadUrl(string $targetKey, string $filename, int $minutes = 15): string
    {
        return $this->remoteDisk()->temporaryUrl($this->remotePrefix($targetKey) . basename($filename), now()->addMinutes($minutes));
    }

    public function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    protected function notifySlack(string $message): void
    {
        $webhook = config('slack.slack_webhook_url_server_alerts');
        if (!$webhook) {
            return;
        }

        try {
            Http::timeout(5)->post($webhook, ['text' => $message]);
        } catch (Throwable $e) {
            Log::warning('Failed to send backup Slack notification', ['error' => $e->getMessage()]);
        }
    }
}
