<?php

namespace Database\Seeders;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\DB;


class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Remove any max execution and memory limits
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // Example seeders
        $this->call([
            CountriesSeeder::class,
            StateSeeder::class,
            RoleSeeder::class,
            CurrencySeeder::class,
//            DefaultImagesSeeder::class,
            ThemeSeeder::class,
            SettingSeeder::class,
            HomePageSeeder::class,
            ThemeOptionSeeder::class,
            OrderStatusSeeder::class,
        ]);

        //$this->attachmentSeeding();
        $this->command->info('Seeding completed successfully.');
        // ─────────────────────────────────────────────────────────


        // ─────────────────────────────────────────────────────────
        //  Run imports with NO timeout and real-time output
        // ─────────────────────────────────────────────────────────
//        $this->command->info('Extracting categories from the json files...');
//        $this->runCommandNoTimeout('php artisan app:extract-categories');
//
//        $this->command->info('Importing categories to the database from the created json file...');
//        $this->runCommandNoTimeout('php artisan app:import-categories');
//        //$this->runCommandNoTimeout('php artisan app:products-import-fast');
//        //$this->runCommandNoTimeout('php artisan app:index-search-key-words');
//
//        // Clear caches etc.
//        $this->command->info('Clearing caches and creating symlinks on storage...');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('storage:link');
    }

    /**
     * Run a command with NO timeout, showing real-time output.
     */
    private function runCommandNoTimeout(string $command)
    {
        // Split the command into an array for Symfony Process
        $process = new Process(explode(' ', $command));

        // Set no time limit
        $process->setTimeout(null);       // No max runtime
        $process->setIdleTimeout(null);   // No idle timeout

        // Start the process and display output immediately
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        // If command failed, throw an exception
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }


}
