<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Jobs\UploadImages;

class DownloadImages extends Command
{
    protected $signature = 'app:download-images
                            {--chunk=20 : DB chunk size}
                            {--limit=10 : Max jobs to dispatch this run}
                            {--busy-threshold=50 : Skip if images queue has more than this many pending}';

    protected $description = 'Dispatch UploadImages jobs for attachments that need uploads.';

    public function handle(): int
    {
        $limit         = (int) $this->option('limit');
        $chunkSize     = (int) $this->option('chunk');
        $busyThreshold = (int) $this->option('busy-threshold');
        $queueName     = 'images';

        // If queue is already busy, skip this minute
        $pending = Queue::size($queueName);
        if (is_numeric($pending) && $pending > $busyThreshold) {
            $this->warn("Skipping: queue '{$queueName}' has {$pending} pending (> {$busyThreshold}).");
            return self::SUCCESS;
        }

        $total = 0;
        $this->info("Scanning attachments (chunk={$chunkSize}) — max {$limit} jobs this run");

        DB::table('attachments')
            ->whereNull('original_url')
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($attachments) use (&$total, $limit, $queueName) {
                if ($total >= $limit) return false;

                $jobs = [];
                $idx = 0;
                foreach ($attachments as $a) {
                    if ($total >= $limit) break;

                    $delay = now()->addSeconds(($idx % 120)); // spreads first 120 jobs over 2 minutes
                    $jobs[] = (new UploadImages((int)$a->id))
                        ->onQueue($queueName)
                        ->delay($delay);

                    $total++; $idx++;
                }

                if ($jobs) {
                    Bus::batch($jobs)
                        ->name('AttachmentUpload: '.now()->format('Y-m-d H:i:s'))
                        ->allowFailures()
                        ->onQueue($queueName)
                        ->dispatch();
                    $this->line('Dispatched '.count($jobs).' jobs (running total: '.$total.')');
                }

                return $total < $limit;
            });

        $this->info("Done. Dispatched {$total} job(s).");
        return self::SUCCESS;
    }
}
