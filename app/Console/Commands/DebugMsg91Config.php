<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class DebugMsg91Config extends Command
{
    protected $signature = 'debug:msg91config';
    protected $description = 'Debug MSG91 configuration issues';

    public function handle()
    {
        $this->info('🔍 MSG91 Configuration Debugging');
        $this->newLine();

        // Step 1: Check .env file
        $this->info('Step 1: Checking .env file...');
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (strpos($envContent, 'MSG91_AUTH_KEY') !== false) {
                preg_match('/MSG91_AUTH_KEY=(.+)/', $envContent, $matches);
                $authKey = $matches[1] ?? 'NOT FOUND';
                $this->line('  .env AUTH_KEY: ' . substr($authKey, 0, 15) . '...' . substr($authKey, -5));
            } else {
                $this->error('  MSG91_AUTH_KEY not found in .env');
            }
        }
        $this->newLine();

        // Step 2: Check loaded config
        $this->info('Step 2: Checking loaded configuration...');
        $loadedAuthKey = config('services.msg91.auth_key');
        $this->line('  Config AUTH_KEY: ' . substr($loadedAuthKey, 0, 15) . '...' . substr($loadedAuthKey, -5));
        $this->line('  Sender ID: ' . config('services.msg91.whatsapp_sender_id'));
        $this->line('  Enabled: ' . (config('services.msg91.whatsapp_enabled') ? 'true' : 'false'));
        $this->newLine();

        // Step 3: Check if they match
        $envAuthKey = trim(explode("\n", shell_exec('grep MSG91_AUTH_KEY ' . $envPath))[0] ?? '');
        $envAuthKey = explode('=', $envAuthKey)[1] ?? '';

        if ($envAuthKey === $loadedAuthKey) {
            $this->info('✅ .env and loaded config MATCH');
        } else {
            $this->error('❌ .env and loaded config DO NOT MATCH');
            $this->warn('  This means configuration is cached!');
            $this->newLine();
            $this->info('  Running: php artisan config:clear');
            Artisan::call('config:clear');
            $this->info('  ✅ Config cache cleared');
            $this->newLine();
            $this->info('  Please run this command again to verify');
        }
        $this->newLine();

        // Step 4: Check cached config file
        $cachedConfigPath = base_path('bootstrap/cache/config.php');
        if (file_exists($cachedConfigPath)) {
            $this->warn('⚠️  Cached config file exists: bootstrap/cache/config.php');
            $this->info('  Deleting it...');
            unlink($cachedConfigPath);
            $this->info('  ✅ Deleted');
        } else {
            $this->info('✅ No cached config file found');
        }
        $this->newLine();

        // Step 5: Recommendation
        $this->info('Recommendation:');
        $this->line('  1. Always run: php artisan config:clear after changing .env');
        $this->line('  2. Never use: php artisan config:cache in development');
        $this->line('  3. In production, restart PHP-FPM/web server after config changes');

        return 0;
    }
}
