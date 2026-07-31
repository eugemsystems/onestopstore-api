<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateCrmToken extends Command
{
    protected $signature = 'token:generate-crm';
    protected $description = 'Generate a new API token for CRM authentication';

    public function handle()
    {
        $this->info('Generating new CRM API token...');

        $user = User::find(1);

        if (!$user) {
            $this->error('No users found in database!');
            return 1;
        }

        // Revoke old crm-access tokens
        $user->tokens()->where('name', 'crm-access')->delete();

        // Create new token
        $token = $user->createToken('crm-access')->plainTextToken;

        $this->newLine();
        $this->info('✅ Token generated successfully!');
        $this->newLine();
        $this->line("User: {$user->email}");
        $this->line("Token: {$token}");
        $this->newLine();
        $this->info('Add this to laravel-crm/.env:');
        $this->line("API_TOKEN={$token}");
        $this->newLine();
        $this->warn('Remember to run: php artisan config:clear (in CRM)');

        return 0;
    }
}
