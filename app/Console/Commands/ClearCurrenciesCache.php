<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCurrenciesCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-currencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the active currencies cache to force a fresh fetch from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing active currencies cache...');

        Cache::forget('active_currencies_list');

        $this->info('✓ Active currencies cache cleared successfully!');
        $this->info('The next request will fetch fresh data from the database.');

        // Optionally regenerate the cache immediately
        if ($this->confirm('Would you like to regenerate the cache now?', true)) {
            $this->info('Regenerating currencies cache...');
            regenerateCachedActiveCurrencies();
            $this->info('✓ Cache regenerated successfully!');
        }

        return Command::SUCCESS;
    }
}

