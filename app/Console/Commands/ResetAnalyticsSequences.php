<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAnalyticsSequences extends Command
{
    protected $signature = 'analytics:reset-sequences';
    protected $description = 'Reset PostgreSQL sequences for analytics tables to fix duplicate key errors';

    public function handle(): int
    {
        $this->info('Resetting PostgreSQL sequences for analytics database...');
        $this->newLine();

        $tables = [
            'page_views',
            'user_sessions',
            'user_events',
            'cart_abandonments',
        ];

        try {
            foreach ($tables as $table) {
                // Get the max ID from the table
                $maxId = DB::connection('analytics')
                    ->table($table)
                    ->max('id');

                if ($maxId) {
                    $nextId = $maxId + 1;

                    // Reset the sequence to max_id + 1
                    DB::connection('analytics')->statement(
                        "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), {$nextId}, false);"
                    );

                    $this->info("✓ {$table}: sequence reset to {$nextId} (max ID: {$maxId})");
                } else {
                    $this->comment("○ {$table}: table is empty, no sequence reset needed");
                }
            }

            $this->newLine();
            $this->info('✓ All sequences reset successfully!');
            $this->newLine();
            $this->info('You can now insert new analytics records without duplicate key errors.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to reset sequences: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
