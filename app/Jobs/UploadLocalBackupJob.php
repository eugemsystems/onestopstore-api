<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadLocalBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    public function __construct(protected string $targetKey, protected string $filename)
    {
        // See RunDatabaseBackupJob for why this isn't a $queue property.
        $this->onQueue('backups');
    }

    public function handle(DatabaseBackupService $service): void
    {
        $service->uploadLocalBackup($this->targetKey, $this->filename);
    }
}
