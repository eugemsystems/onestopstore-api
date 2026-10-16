<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneDatabaseBackups extends Command
{
    protected $signature = 'db:backup-prune {--keep=1 : How many of the newest backups to keep per target (local and R2)}';

    protected $description = 'Delete all old database backups (local + R2), keeping only the newest file per target';

    public function handle(DatabaseBackupService $service): int
    {
        $keep = max(1, (int) $this->option('keep'));
        $report = $service->pruneToLatest($keep);
        $failed = false;

        foreach ($report as $target => $row) {
            $this->line(sprintf('%s: %d local + %d off-server backup(s) deleted', $target, $row['local_deleted'], $row['remote_deleted']));
            foreach ($row['errors'] as $file => $error) {
                $failed = true;
                $this->error("  {$file}: {$error}");
            }
        }

        Log::info('Weekly backup prune finished', ['keep' => $keep, 'report' => $report]);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
