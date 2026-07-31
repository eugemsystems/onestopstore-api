<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRawTestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $to;

    public function __construct(string $to)
    {
        $this->to = $to;
        // Force this job to the emails queue
        $this->onQueue("emails");
    }

    public function handle(): void
    {
        Mail::raw("Ping from production (queued job on emails queue).", function ($m) {
            $m->to($this->to)->subject("Raines: queued email ping");
        });
    }
}
