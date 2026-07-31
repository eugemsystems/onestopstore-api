<?php

namespace App\Jobs;

use App\Models\Attachment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UploadImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    // Let retryUntil() govern longevity; don’t hard-cap to 3.
    public $timeout = 180;

    protected int $attachmentId;

    public function __construct(int $attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    public function uniqueId(): string
    {
        return (string) $this->attachmentId;
    }

    public function tags(): array
    {
        return ['images', 'attachment:'.$this->attachmentId];
    }

    // Keep jobs eligible for days
    public function retryUntil(): CarbonInterface
    {
        return now()->addWeeks(3);
    }

    /**
     * Global safety nets:
     * - RateLimited('images-api'): distributed throttle (no DIY spinning)
     * - WithoutOverlapping per attachment: prevents duplicate concurrent downloads
     * - ThrottlesExceptions: auto backoff on repeated exceptions
     */
    public function middleware(): array
    {
        return [
            // Ensure strictly one image job runs at a time across all workers
            (new WithoutOverlapping('images-global'))->releaseAfter(30),
            // If the handle throws repeatedly (network, 5xx), progressively slow down
            (new ThrottlesExceptions(10, 1))->backoff(60), // 10 exceptions/min => wait ~60s
        ];
    }

    public function handle(): void
    {
        $attachment = Attachment::find($this->attachmentId);
        if (!$attachment || !$attachment->image_url) {
            //Log::info('Attachment skipped', ['attachment_id' => $this->attachmentId]);
            return;
        }

        $imageApi = rtrim(config('app.image_api_url'), '/');
        if (!$imageApi) {
            //Log::error('APP_IMAGE_API_URL (config app.image_api_url) missing');
            return;
        }

        // Enforce throttling: ~20 jobs/minute
        $delay = $this->throttleIfNeeded();
        if ($delay !== null) {
            $this->release($delay);
            return;
        }
        // Reserve slot
        $this->markRun();

        // HTTP client with built-in retry for timeouts, 5xx, DNS hiccups.
        // We DO NOT retry 429 here; we handle it explicitly to respect Retry-After.
        $client = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ])->timeout(60)->retry(
            3,               // attempts
            500,             // ms backoff (linear; we're handling 429 separately)
            fn ($e, $req) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response && $e->response->serverError())
        );

        // --------- DOWNLOAD ---------
        $resp = $client->get($attachment->image_url);

        if ($resp->status() === 429) {
            $delay = $this->delayFromRetryAfter($resp->header('Retry-After')) ?? $this->expJitterDelay();
            //Log::warning('429 on download; releasing', ['attachment_id' => $this->attachmentId, 'delay' => $delay]);
            $this->release($delay);
            return;
        }

        if (!$resp->successful()) {
//            Log::error('Failed to fetch image', [
//                'attachment_id' => $this->attachmentId,
//                'status'        => $resp->status(),
//            ]);
            // Let ThrottlesExceptions handle progressive delays if we throw:
            throw new \RuntimeException("Download failed with status {$resp->status()}");
        }

        $filename = basename(parse_url($attachment->image_url, PHP_URL_PATH)) ?: 'image.jpg';

        // --------- UPLOAD ---------
        $upload = Http::timeout(60)->retry(
            3,
            500,
            fn ($e, $req) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response && $e->response->serverError())
        )->attach('image', $resp->body(), $filename)
            ->post($imageApi.'/attachments/from-api');

        if ($upload->status() === 429) {
            $delay = $this->delayFromRetryAfter($upload->header('Retry-After')) ?? $this->expJitterDelay();
            //Log::warning('429 on upload; releasing', ['attachment_id' => $this->attachmentId, 'delay' => $delay]);
            $this->release($delay);
            return;
        }

        if (!$upload->successful()) {
//            Log::error('Upload failed', [
//                'attachment_id' => $this->attachmentId,
//                'status'        => $upload->status(),
//                'body'          => $upload->body(),
//            ]);
            throw new \RuntimeException("Upload failed with status {$upload->status()}");
        }

        $file = data_get($upload->json(), 'files.0');
        if (!$file) {
            //Log::error('Upload returned no file payload', ['attachment_id' => $this->attachmentId]);
            throw new \RuntimeException("Upload returned empty payload");
        }

        $attachment->update([
            'image_url'    => $file['image_url'] ?? $attachment->image_url,
            'original_url' => $file['image_url'] ?? $attachment->original_url,
            'uuid'         => $file['uuid'] ?? $attachment->uuid,
            'disk'         => $file['disk'] ?? $attachment->disk,
            'file_name'    => $file['file_name'] ?? $attachment->file_name,
            'mime_type'    => $file['mime_type'] ?? $attachment->mime_type,
            'size'         => $file['size'] ?? $attachment->size,
            'takealot_url' => $attachment->image_url,
        ]);
    }

    /** Respect Retry-After seconds or HTTP-date; return seconds (int) or null */
    private function delayFromRetryAfter(?string $header): ?int
    {
        if (!$header) return null;
        if (ctype_digit($header)) return max(5, (int)$header + rand(0, 3)); // small jitter
        $ts = strtotime($header);
        if ($ts === false) return null;
        return max(5, $ts - time() + rand(0, 3));
    }

    /** Exponential backoff with full jitter, capped */
    private function expJitterDelay(): int
    {
        $attempt = max(1, $this->attempts());           // starts at 1
        $base    = 5;                                   // seconds
        $max     = 900;                                 // cap at 15 min
        $raw     = min($max, $base * (2 ** ($attempt - 1)));
        return random_int(0, $raw);                     // full jitter
    }

    /**
     * If we're running too fast, return a delay in seconds. Otherwise null.
     * Policies:
     *  - Minimum 3 seconds between jobs (~20 per minute)
     *  - Keep per-minute count at most 20
     */
    private function throttleIfNeeded(): ?int
    {
        $now = now();

        // Minimum gap between runs
        $lastTs = Cache::get('images:last_run_ts');
        if ($lastTs) {
            $elapsed = $now->getTimestamp() - (int)$lastTs;
            if ($elapsed < 3) {
                return max(1, 3 - $elapsed + 1);
            }
        }

        // Per-minute cap
        $minuteKey = 'images:minute:' . $now->format('YmdHi');
        $count = (int) Cache::get($minuteKey, 0);
        if ($count >= 20) { // cap at 20/min
            $secs = (int) $now->format('s');
            return max(5, 60 - $secs + 1);
        }

        return null;
    }

    // Record execution to enforce throttling
    private function markRun(): void
    {
        $now = now();
        Cache::put('images:last_run_ts', $now->getTimestamp(), $now->copy()->addMinutes(5));

        $minuteKey = 'images:minute:' . $now->format('YmdHi');
        if (!Cache::has($minuteKey)) {
            Cache::put($minuteKey, 0, $now->copy()->addSeconds(70));
        }
        Cache::increment($minuteKey);
    }
}
