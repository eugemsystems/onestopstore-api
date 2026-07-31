<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Analytics\PageView;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\UserEvent;
use App\Models\Analytics\CartAbandonment;

class MigrateAnalyticsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:migrate-data
                            {--chunk=1000 : Number of records to process at a time}
                            {--from-date= : Only migrate data from this date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing analytics data from main database to analytics database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting analytics data migration...');
        $this->newLine();

        $chunkSize = (int) $this->option('chunk');
        $fromDate = $this->option('from-date');

        // Check if analytics tables exist in main database
        $mainConnection = config('database.default');

        $tables = [
            'page_views' => PageView::class,
            'user_sessions' => UserSession::class,
            'user_events' => UserEvent::class,
            'cart_abandonments' => CartAbandonment::class,
        ];

        foreach ($tables as $table => $model) {
            if (!$this->tableExistsInMainDb($table)) {
                $this->warn("Table '{$table}' does not exist in main database. Skipping...");
                continue;
            }

            $this->info("Migrating {$table}...");
            $count = $this->migrateTable($table, $model, $chunkSize, $fromDate);
            $this->info("✓ Migrated {$count} records from {$table}");
            $this->newLine();
        }

        $this->info('Analytics data migration completed successfully!');
        return Command::SUCCESS;
    }

    /**
     * Check if table exists in main database
     */
    private function tableExistsInMainDb(string $table): bool
    {
        try {
            $mainConnection = config('database.default');
            return DB::connection($mainConnection)
                ->getSchemaBuilder()
                ->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Migrate a table from main DB to analytics DB
     */
    private function migrateTable(string $table, string $model, int $chunkSize, ?string $fromDate): int
    {
        $mainConnection = config('database.default');
        $totalMigrated = 0;

        // Build query for main database
        $query = DB::connection($mainConnection)->table($table);

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        $totalRecords = $query->count();

        if ($totalRecords === 0) {
            $this->warn("No records to migrate from {$table}");
            return 0;
        }

        $this->info("Found {$totalRecords} records to migrate");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        // Process in chunks to avoid memory issues
        $query->orderBy('id')->chunk($chunkSize, function ($records) use ($model, &$totalMigrated, $bar) {
            $data = [];

            foreach ($records as $record) {
                // Convert stdClass to array
                $data[] = (array) $record;
                $totalMigrated++;
                $bar->advance();
            }

            // Bulk insert into analytics database
            // The model's connection is already set to 'analytics'
            if (!empty($data)) {
                try {
                    /** @var \Illuminate\Database\Eloquent\Model $modelInstance */
                    $modelInstance = new $model();
                    DB::connection('analytics')->table($modelInstance->getTable())->insert($data);
                } catch (\Exception $e) {
                    $this->error("\nError inserting batch: " . $e->getMessage());
                    // Log but continue with next batch
                    $modelInstance = new $model();
                    Log::error('Analytics migration error', [
                        'table' => $modelInstance->getTable(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine();

        return $totalMigrated;
    }
}
