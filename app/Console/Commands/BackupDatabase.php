<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {target? : Dump only this target (app, media, crm) instead of all enabled targets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump configured databases to local disk for review (upload to R2 is a separate, manual step via /admin/backups)';

    public function handle(DatabaseBackupService $service): int
    {
        $targetArg = $this->argument('target');

        if ($targetArg) {
            $this->info("Dumping target '{$targetArg}'...");
            $results = [$targetArg => $service->dumpTarget($targetArg)];
        } else {
            $this->info('Dumping all enabled targets...');
            $results = $service->run();
        }

        if (empty($results)) {
            $this->warn('No enabled backup targets configured — nothing to do.');
            return self::SUCCESS;
        }

        $failures = 0;
        foreach ($results as $key => $result) {
            if ($result['success']) {
                $this->info(sprintf(
                    '[%s] OK: %s (%s, %ss)',
                    $key,
                    $result['filename'],
                    $service->humanSize($result['size']),
                    $result['duration']
                ));
            } else {
                $failures++;
                $this->error("[{$key}] FAILED: " . $result['error']);
            }
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
