<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BotDetectionService;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\UserEvent;
use Illuminate\Support\Facades\DB;

class CleanBotSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:clean-bot-sessions
                            {--days=7 : Number of days to scan}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and clean bot sessions using IP and user agent analysis';

    protected BotDetectionService $botDetection;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->botDetection = app(BotDetectionService::class);
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("🔍 Scanning sessions from the last {$days} days for bot traffic...");
        $this->newLine();

        $cutoffDate = now()->subDays($days);

        // Get all sessions with user agent and IP
        $sessions = UserSession::where('created_at', '>=', $cutoffDate)
            ->whereNotNull('user_agent')
            ->whereNotNull('ip_address')
            ->select('id', 'session_id', 'user_agent', 'ip_address', 'browser', 'os', 'country', 'city')
            ->get();

        $this->info("📊 Found {$sessions->count()} sessions to analyze");
        $this->newLine();

        $botSessions = [];
        $suspiciousSessions = [];

        $progressBar = $this->output->createProgressBar($sessions->count());
        $progressBar->start();

        foreach ($sessions as $session) {
            if ($this->botDetection->isBot($session->user_agent, $session->ip_address)) {
                $botSessions[] = [
                    'session_id' => $session->session_id,
                    'ip' => $session->ip_address,
                    'location' => ($session->city ?? 'Unknown') . ', ' . ($session->country ?? 'Unknown'),
                    'browser' => $session->browser ?? 'Unknown',
                    'os' => $session->os ?? 'Unknown',
                    'user_agent' => substr($session->user_agent, 0, 80),
                ];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        if (empty($botSessions)) {
            $this->info('✅ No bot sessions detected!');
            return Command::SUCCESS;
        }

        $this->warn("🤖 Found " . count($botSessions) . " bot sessions:");
        $this->newLine();

        // Show sample of detected bots
        $sampleSize = min(10, count($botSessions));
        $this->table(
            ['Session ID', 'IP Address', 'Location', 'Browser', 'OS'],
            array_slice(array_map(function($bot) {
                return [
                    substr($bot['session_id'], 0, 20) . '...',
                    $bot['ip'],
                    $bot['location'],
                    $bot['browser'],
                    $bot['os'],
                ];
            }, $botSessions), 0, $sampleSize)
        );

        if (count($botSessions) > $sampleSize) {
            $this->info("... and " . (count($botSessions) - $sampleSize) . " more");
        }

        $this->newLine();

        // Count related data
        $botSessionIds = array_column($botSessions, 'session_id');
        $pageViewsCount = PageView::whereIn('session_id', $botSessionIds)->count();
        $eventsCount = UserEvent::whereIn('session_id', $botSessionIds)->count();

        $this->info("📈 Impact:");
        $this->line("  - Bot Sessions: " . count($botSessions));
        $this->line("  - Page Views: {$pageViewsCount}");
        $this->line("  - Events: {$eventsCount}");
        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 DRY RUN MODE - No data will be deleted");
            return Command::SUCCESS;
        }

        if (!$force && !$this->confirm('⚠️  Do you want to delete this bot data? This cannot be undone!')) {
            $this->info('Aborted.');
            return Command::SUCCESS;
        }

        $this->info('🧹 Cleaning bot traffic...');

        // Delete in transaction
        DB::connection('analytics')->transaction(function() use ($botSessionIds) {
            $deleted = [
                'events' => UserEvent::whereIn('session_id', $botSessionIds)->delete(),
                'page_views' => PageView::whereIn('session_id', $botSessionIds)->delete(),
                'sessions' => UserSession::whereIn('session_id', $botSessionIds)->delete(),
            ];

            $this->newLine();
            $this->info("✅ Deleted:");
            $this->line("  - {$deleted['sessions']} sessions");
            $this->line("  - {$deleted['page_views']} page views");
            $this->line("  - {$deleted['events']} events");
        });

        $this->newLine();
        $this->info('✅ Bot traffic cleaned successfully!');

        return Command::SUCCESS;
    }
}

