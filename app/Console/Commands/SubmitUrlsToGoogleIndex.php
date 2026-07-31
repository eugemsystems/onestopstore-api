<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Pushes recently created/updated product URLs to the Google Indexing API
 * so they get indexed within hours instead of waiting weeks for sitemap crawls.
 *
 * Prerequisites:
 *   1. Create a Google Cloud project and enable the "Web Search Indexing API"
 *   2. Create a service account and download the JSON key file
 *   3. Add the service account email as an Owner in Google Search Console
 *   4. Place the key file at storage/app/google/service-account.json
 *      (or set GOOGLE_SERVICE_ACCOUNT_PATH in .env)
 *   5. Set FRONTEND_URL in .env (e.g., https://raines.africa/en)
 *
 * Usage:
 *   php artisan google:submit-urls                 # submit last 24h of changes
 *   php artisan google:submit-urls --hours=48      # submit last 48h
 *   php artisan google:submit-urls --dry-run       # preview without submitting
 *   php artisan google:submit-urls --limit=100     # cap submissions
 */
class SubmitUrlsToGoogleIndex extends Command
{
    protected $signature = 'google:submit-urls
                            {--hours=24 : Look back this many hours for new/updated products}
                            {--limit=200 : Maximum URLs to submit per run}
                            {--backfill=100 : Also submit this many oldest/unsubmitted products each run}
                            {--dry-run : Preview which URLs would be submitted without calling the API}';

    protected $description = 'Submit recently created/updated product URLs to the Google Indexing API for fast indexing';

    private const INDEXING_API_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/indexing';

    public function handle(): void
    {
        $hours    = (int) $this->option('hours');
        $limit    = (int) $this->option('limit');
        $backfill = (int) $this->option('backfill');
        $dryRun   = $this->option('dry-run');

        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://raines.africa/en'), '/');

        // ── Load service account credentials ──────────────────────────
        $keyPath = env('GOOGLE_SERVICE_ACCOUNT_PATH', storage_path('app/google/service-account.json'));

        if (!file_exists($keyPath)) {
            $this->error("Service account key not found at: {$keyPath}");
            $this->line('See command docblock for setup instructions.');
            return;
        }

        // ── Get access token ──────────────────────────────────────────
        $accessToken = $this->getAccessToken($keyPath);
        if (!$accessToken && !$dryRun) {
            $this->error('Failed to obtain Google OAuth2 access token.');
            return;
        }

        // ── Query recently changed products ───────────────────────────
        $cutoff = now()->subHours($hours);

        // Prioritise: featured first, then sale items, then newest
        $recent = DB::table('products')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('created_at', '>=', $cutoff)
                  ->orWhere('updated_at', '>=', $cutoff);
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('is_sale_enable')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->pluck('slug');

        // ── Backfill: oldest products that haven't been touched recently ─────
        // Cycles through the entire catalog over time so every product eventually
        // gets individually submitted to the Indexing API, not just recent ones.
        $backfillSlugs = collect();
        if ($backfill > 0) {
            $excludeSlugs = $recent->toArray();
            $backfillOffset = (int) Cache::get('google_submit_backfill_offset', 0);

            $backfillSlugs = DB::table('products')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereNotIn('slug', $excludeSlugs)
                ->orderBy('id')
                ->offset($backfillOffset)
                ->limit($backfill)
                ->pluck('slug');

            // Advance offset for next run; reset when we reach the end of the catalog
            $nextOffset = $backfillOffset + $backfill;
            $totalCount = DB::table('products')->where('status', 1)->whereNull('deleted_at')->count();
            Cache::put('google_submit_backfill_offset', $nextOffset >= $totalCount ? 0 : $nextOffset, now()->addDays(30));

            $this->info("Backfill: {$backfillSlugs->count()} product(s) from offset {$backfillOffset}.");
        }

        $products = $recent->merge($backfillSlugs)->unique();

        if ($products->isEmpty()) {
            $this->info("No products to submit.");
            return;
        }

        $this->info("Found {$products->count()} product(s) to submit ({$recent->count()} recent + {$backfillSlugs->count()} backfill).");

        $submitted = 0;
        $failed    = 0;
        $errors    = [];

        foreach ($products as $slug) {
            $url = "{$frontendUrl}/product/{$slug}";

            if ($dryRun) {
                $this->line("[DRY RUN] Would submit: {$url}");
                $submitted++;
                continue;
            }

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post(self::INDEXING_API_URL, [
                        'url'  => $url,
                        'type' => 'URL_UPDATED',
                    ]);

                if ($response->successful()) {
                    $submitted++;
                    $this->line("Submitted: {$url}");
                } else {
                    $failed++;
                    $errMsg = $response->json('error.message', $response->body());
                    $errors[] = "{$url}: {$errMsg}";
                    $this->warn("Failed: {$url} — {$errMsg}");

                    // If quota exceeded, stop early
                    if ($response->status() === 429) {
                        $this->warn('Quota exceeded — stopping early. Remaining URLs will be submitted next run.');
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$url}: {$e->getMessage()}";
                $this->warn("Error: {$url} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. Submitted: {$submitted} | Failed: {$failed}");

        Log::info('google:submit-urls', [
            'submitted' => $submitted,
            'failed'    => $failed,
            'dry_run'   => $dryRun,
            'hours'     => $hours,
            'errors'    => array_slice($errors, 0, 10),
        ]);
    }

    /**
     * Generate a short-lived OAuth2 access token using the service account's JWT.
     */
    private function getAccessToken(string $keyPath): ?string
    {
        // Cache token for 55 minutes (tokens last 60 minutes)
        return Cache::remember('google_indexing_api_token', 55 * 60, function () use ($keyPath) {
            try {
                $sa  = json_decode(file_get_contents($keyPath), true);
                $now = time();

                // ── Build JWT ────────────────────────────────────────
                $header  = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
                $payload = $this->base64url(json_encode([
                    'iss'   => $sa['client_email'],
                    'scope' => self::SCOPE,
                    'aud'   => self::TOKEN_URL,
                    'iat'   => $now,
                    'exp'   => $now + 3600,
                ]));

                $signingInput = "{$header}.{$payload}";
                openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
                $jwt = "{$signingInput}." . $this->base64url($signature);

                // ── Exchange JWT for access token ────────────────────
                $response = Http::asForm()->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Google OAuth2 token exchange failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Google OAuth2 token generation failed', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
