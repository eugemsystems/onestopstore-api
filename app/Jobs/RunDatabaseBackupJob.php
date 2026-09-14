<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dumps a database to local disk only — does not upload anywhere. Uploading
 * to R2 is a separate, explicit step (see UploadLocalBackupJob) so an admin
 * can review size/contents before anything leaves the server.
 */
class RunDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // A large pg_dump can take a while — give it plenty of room, and don't
    // let a stuck job retry and pile up duplicate dumps.
    public $timeout = 3600;
    public $tries = 1;

    /**
     * @param string|null $targetKey Dump just this target (e.g. 'media'), or
     *                               null to dump every enabled target.
     */
    public function __construct(protected ?string $targetKey = null)
    {
        // Runs on its own Horizon queue (see config/horizon.php) since the
        // default queue's worker timeout (60s) is nowhere near long enough
        // for this. Set via onQueue() rather than a $queue property because
        // Illuminate\Bus\Queueable already declares that property — PHP 8.4+
        // treats a re-declaration with a different default as a fatal error.
        $this->onQueue('backups');
    }

    public function handle(DatabaseBackupService $service): void
    {
        if ($this->targetKey) {
            $service->dumpTarget($this->targetKey);
        } else {
            $service->run();
        }
    }
}
