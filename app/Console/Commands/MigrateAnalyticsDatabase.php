<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateAnalyticsDatabase extends Command
{
    protected $signature = 'analytics:migrate {--fresh : Drop all tables and re-run all migrations}';
    protected $description = 'Run migrations for the analytics database (ONLY analytics tables)';

    public function handle(): int
    {
        $this->info('Running migrations for analytics database...');
        $this->newLine();

        // Get only analytics migration files (those starting with 2026_02_08_0000)
        $migrationsPath = database_path('migrations');
        $analyticsMigrations = glob($migrationsPath . '/2026_02_08_0000*.php');

        if (empty($analyticsMigrations)) {
            $this->error('No analytics migrations found!');
            return Command::FAILURE;
        }

        $this->info('Found ' . count($analyticsMigrations) . ' analytics migration(s):');
        foreach ($analyticsMigrations as $migration) {
            $this->line('  - ' . basename($migration));
        }
        $this->newLine();

        try {
            if ($this->option('fresh')) {
                $this->warn('Dropping all tables in analytics database...');

                // Drop all tables in analytics database (PostgreSQL)
                DB::connection('analytics')->statement('DROP SCHEMA public CASCADE');
                DB::connection('analytics')->statement('CREATE SCHEMA public');
                DB::connection('analytics')->statement('GRANT ALL ON SCHEMA public TO postgres');
                DB::connection('analytics')->statement('GRANT ALL ON SCHEMA public TO public');

                $this->info('Tables dropped successfully.');
                $this->newLine();
            }

            // Run each analytics migration individually
            foreach ($analyticsMigrations as $migrationFile) {
                $migrationName = basename($migrationFile, '.php');

                // Check if migrations table exists first
                $tablesExist = true;
                try {
                    DB::connection('analytics')->table('migrations')->count();
                } catch (\Exception $e) {
                    $tablesExist = false;
                }

                // Check if already ran
                $exists = false;
                if ($tablesExist && !$this->option('fresh')) {
                    $exists = DB::connection('analytics')
                        ->table('migrations')
                        ->where('migration', $migrationName)
                        ->exists();
                }

                if (!$exists || $this->option('fresh')) {
                    $this->info("Migrating: {$migrationName}");

                    // Include and run the migration
                    $migration = require $migrationFile;
                    $migration->up();

                    // Record in migrations table
                    DB::connection('analytics')->table('migrations')->insert([
                        'migration' => $migrationName,
                        'batch' => 1,
                    ]);

                    $this->info("<fg=green>Migrated:</>  {$migrationName}");
                } else {
                    $this->comment("Skipped:   {$migrationName} (already ran)");
                }
            }

            $this->newLine();
            $this->info('✓ Analytics database migrations completed successfully!');
            $this->newLine();

            // Show tables created
            $this->info('Tables in analytics database:');
            $tables = DB::connection('analytics')
                ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            foreach ($tables as $table) {
                $this->line('  - ' . $table->tablename);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
