<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BotDetectionService;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use Illuminate\Support\Facades\DB;

class AnalyzeBotTraffic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:analyze-bots
                            {--days=7 : Number of days to analyze}
                            {--clean : Remove detected bot traffic from analytics}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optionally clean bot traffic from analytics data';

    protected BotDetectionService $botDetection;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->botDetection = app(BotDetectionService::class);
        $days = $this->option('days');
        $clean = $this->option('clean');
        $dryRun = $this->option('dry-run');

        $this->info("Analyzing bot traffic from the last {$days} days...");
        $this->newLine();

        // Analyze Page Views
        $this->info('📊 Analyzing Page Views...');
        $pageViewStats = $this->analyzePageViews($days);

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Page Views', number_format($pageViewStats['total']), '100%'],
                ['Bot Traffic', number_format($pageViewStats['bots']), $pageViewStats['bot_percentage'] . '%'],
                ['Human Traffic', number_format($pageViewStats['humans']), $pageViewStats['human_percentage'] . '%'],
            ]
        );

        $this->newLine();

        // Analyze Sessions
        $this->info('📊 Analyzing Sessions...');
        $sessionStats = $this->analyzeSessions($days);

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Sessions', number_format($sessionStats['total']), '100%'],
                ['Bot Sessions', number_format($sessionStats['bots']), $sessionStats['bot_percentage'] . '%'],
                ['Human Sessions', number_format($sessionStats['humans']), $sessionStats['human_percentage'] . '%'],
            ]
        );

        $this->newLine();

        // Show top bot user agents
        $this->info('🤖 Top Bot User Agents:');
        $topBots = $this->getTopBotUserAgents($days, 10);

        if (!empty($topBots)) {
            $this->table(
                ['Bot Name', 'Requests', 'User Agent Sample'],
                $topBots
            );
        } else {
            $this->line('No bot traffic detected.');
        }

        $this->newLine();

        // Clean bot traffic if requested
        if ($clean || $dryRun) {
            $this->newLine();
            if ($dryRun) {
                $this->warn('🔍 DRY RUN MODE - No data will be deleted');
            } else {
                $this->warn('⚠️  CLEANING BOT TRAFFIC - This will permanently delete data!');
            }

            if (!$dryRun && !$this->confirm('Are you sure you want to delete bot traffic?')) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }

            $deleted = $this->cleanBotTraffic($days, $dryRun);

            if ($dryRun) {
                $this->info("Would delete {$deleted['page_views']} page views and {$deleted['sessions']} sessions");
            } else {
                $this->info("✅ Deleted {$deleted['page_views']} page views and {$deleted['sessions']} sessions");
            }
        } else {
            $this->newLine();
            $this->info('💡 Tip: Use --clean to remove bot traffic from analytics');
            $this->info('💡 Tip: Use --dry-run to preview what would be deleted');
        }

        return Command::SUCCESS;
    }

    /**
     * Analyze page views for bot traffic
     */
    protected function analyzePageViews(int $days): array
    {
        $cutoffDate = now()->subDays($days);

        $pageViews = PageView::where('created_at', '>=', $cutoffDate)
            ->select('user_agent')
            ->get();

        $total = $pageViews->count();
        $bots = 0;

        foreach ($pageViews as $view) {
            if ($this->botDetection->isBot($view->user_agent)) {
                $bots++;
            }
        }

        $humans = $total - $bots;

        return [
            'total' => $total,
            'bots' => $bots,
            'humans' => $humans,
            'bot_percentage' => $total > 0 ? round(($bots / $total) * 100, 2) : 0,
            'human_percentage' => $total > 0 ? round(($humans / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Analyze sessions for bot traffic
     */
    protected function analyzeSessions(int $days): array
    {
        $cutoffDate = now()->subDays($days);

        $sessions = UserSession::where('started_at', '>=', $cutoffDate)
            ->select('user_agent')
            ->get();

        $total = $sessions->count();
        $bots = 0;

        foreach ($sessions as $session) {
            if ($this->botDetection->isBot($session->user_agent)) {
                $bots++;
            }
        }

        $humans = $total - $bots;

        return [
            'total' => $total,
            'bots' => $bots,
            'humans' => $humans,
            'bot_percentage' => $total > 0 ? round(($bots / $total) * 100, 2) : 0,
            'human_percentage' => $total > 0 ? round(($humans / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get top bot user agents
     */
    protected function getTopBotUserAgents(int $days, int $limit): array
    {
        $cutoffDate = now()->subDays($days);

        $userAgents = PageView::where('created_at', '>=', $cutoffDate)
            ->select('user_agent', DB::raw('COUNT(*) as count'))
            ->groupBy('user_agent')
            ->orderByDesc('count')
            ->limit($limit * 3) // Get more to filter
            ->get();

        $bots = [];

        foreach ($userAgents as $ua) {
            if ($this->botDetection->isBot($ua->user_agent)) {
                $bots[] = [
                    'name' => $this->botDetection->getBotName($ua->user_agent),
                    'count' => number_format($ua->count),
                    'sample' => substr($ua->user_agent, 0, 80) . (strlen($ua->user_agent) > 80 ? '...' : ''),
                ];

                if (count($bots) >= $limit) {
                    break;
                }
            }
        }

        return $bots;
    }

    /**
     * Clean bot traffic from analytics
     */
    protected function cleanBotTraffic(int $days, bool $dryRun): array
    {
        $cutoffDate = now()->subDays($days);

        // Get bot session IDs
        $botSessionIds = [];

        $sessions = UserSession::where('started_at', '>=', $cutoffDate)
            ->select('session_id', 'user_agent')
            ->get();

        foreach ($sessions as $session) {
            if ($this->botDetection->isBot($session->user_agent)) {
                $botSessionIds[] = $session->session_id;
            }
        }

        if ($dryRun) {
            $pageViewsToDelete = PageView::whereIn('session_id', $botSessionIds)->count();
            $sessionsToDelete = count($botSessionIds);
        } else {
            $pageViewsToDelete = PageView::whereIn('session_id', $botSessionIds)->delete();
            $sessionsToDelete = UserSession::whereIn('session_id', $botSessionIds)->delete();
        }

        return [
            'page_views' => $pageViewsToDelete,
            'sessions' => $sessionsToDelete,
        ];
    }
}

