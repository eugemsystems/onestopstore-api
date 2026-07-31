<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //\App\Console\Commands\DownloadImages::class,
        \App\Console\Commands\ReindexProducts::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // NOTE: In Laravel 11, schedules should be defined in routes/console.php
        // This method is kept for backwards compatibility only

        // Schedules are now defined in: routes/console.php
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
