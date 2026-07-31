<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();

// Horizon graphs:
//Schedule::command('horizon:snapshot')->everyMinute();

// Dispatch up to 100 image jobs each minute
//Schedule::command('app:download-images')->everyMinute();

// If you really need this to run every minute synchronously:
//Schedule::call('App\Http\Controllers\CommissionHistoryController@store')->everyMinute();

// ── Abandoned Cart Detection (runs daily at 2 AM)
Schedule::command(\App\Console\Commands\DetectAbandonedCarts::class)
    ->name('analytics:detect-abandoned-carts')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Process Pending Orders (sends reminders & auto-cancels)
Schedule::command('orders:process-pending')
    ->name('orders:process-pending')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Elasticsearch Reindex (runs daily at 3 AM)
Schedule::command('es:reindex-products', ['--chunk' => 500, '--recreate-index'])
    ->name('elasticsearch:reindex-products')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->then(function () {
        // Clear all caches after reindexing completes
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('categories:cache-tree');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Daily Elasticsearch reindex failed', [
            'timestamp' => now()->toDateTimeString()
        ]);
    });

// ── Expire sale prices when the promotion window closes (hourly)
// Products with no sale_expired_at (perpetual/date-free sales) are NOT touched.
Schedule::command(\App\Console\Commands\ExpireSalePrices::class)
    ->name('products:expire-sales')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Auto-close expired auctions, assign winner, send email, refund deposits ──
// Runs every minute so auctions end within ~60s of ends_at passing.
Schedule::command('auctions:close-expired')
    ->name('auctions:close-expired')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Auction Payment Reminders (checks every 30 minutes) ──────────────────
// Sends reminder emails at reminder_1_hours and reminder_2_hours after auction win.
// Intervals are configurable via Admin → Auctions → Settings.
Schedule::command(\App\Console\Commands\SendAuctionPaymentReminders::class)
    ->name('auction:send-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Auto-Ban Overdue Auction Bidders (hourly) ─────────────────────────────
// Bans users who have not paid within the configured hours_to_pay window.
Schedule::command(\App\Console\Commands\BanOverdueAuctionBidders::class)
    ->name('auction:ban-overdue')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Layby ID Document Reminders (every 12 hours) ──────────────────────────
// Sends up to 5 reminder emails (48h apart) to layby applicants who have not
// uploaded their required ID document. Logs escalated cases for manual review.
Schedule::command(\App\Console\Commands\SendLaybyDocumentReminders::class)
    ->name('layby:send-document-reminders')
    ->twiceDaily(0, 12)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Google Merchant Center feeds: one per currency, regenerated daily ───────
// Runs at 4 AM (after ES reindex at 3 AM). Generates separate feeds per currency.
// Submit to Google Merchant Center:
//   USD: {APP_URL}/storage/feeds/google-merchant-usd.xml
//   ZAR: {APP_URL}/storage/feeds/google-merchant-zar.xml
//   ZMW: {APP_URL}/storage/feeds/google-merchant-zmw.xml
Schedule::command('feed:google-merchant', ['--limit' => 50000, '--currency' => 'USD'])
    ->name('feed:google-merchant-usd')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command('feed:google-merchant', ['--limit' => 50000, '--currency' => 'ZAR'])
    ->name('feed:google-merchant-zar')
    ->dailyAt('04:10')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command('feed:google-merchant', ['--limit' => 50000, '--currency' => 'ZMW'])
    ->name('feed:google-merchant-zmw')
    ->dailyAt('04:20')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Sitemaps: pre-generate all static XML sitemap files ────────────────────
// Runs at 5 AM (after merchant feed at 4 AM). Google fetches static files
// instantly instead of waiting for slow DB queries.
// Files: storage/app/public/sitemaps/ → served at {APP_URL}/storage/sitemaps/
Schedule::command('sitemaps:generate')
    ->name('sitemaps:generate')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Google Indexing API: submit new/updated product URLs for fast indexing ─
// Runs twice daily. --backfill also cycles through older products so the API
// isn't just submitting the same recently-changed products every run.
// Bulk indexing of 1.8M products comes from the sitemap crawl, not this command.
// This command is for fast-tracking new listings and recently changed prices.
Schedule::command('google:submit-urls', ['--hours' => 12, '--limit' => 200, '--backfill' => 100])
    ->name('google:submit-urls')
    ->twiceDaily(6, 18)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// ── Late-order apology emails ──────────────────────────────────────────────
// Only registers the schedule when auto-send is enabled in admin settings.
// Time is pulled from the settings row so admins can change it without a deploy.
(function () {
    // Guard against the table not existing yet (e.g. during php artisan migrate on a fresh DB).
    if (!\Illuminate\Support\Facades\Schema::hasTable('order_apology_settings')) {
        return;
    }
    $settings = \App\Models\OrderApologySetting::current();
    if (!$settings->auto_send_enabled) {
        return;
    }
    [$hour, $minute] = explode(':', $settings->auto_send_time);
    Schedule::command('orders:send-late-apologies')
        ->name('orders:send-late-apologies')
        ->dailyAt("{$hour}:{$minute}")
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
})();
