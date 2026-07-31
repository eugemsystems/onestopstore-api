<?php

namespace App\Console\Commands;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateCrmToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-crm-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'token@raines.africa'],
            [
                'name' => 'Raines Tokens',
                'password' => Hash::make('Str@wberry14'),
                'country_code' => (string) '1',
                'phone' => '9876502520',
                'system_reserve' => true,
            ]
        );
        $user->assignRole(RoleEnum::ADMIN);


        // create a limited-scope token
        $token = $user->createToken('orders-consumer', ['orders:read'])->plainTextToken;
        $this->info($token);
        $this->info("\nImport completed.");
    }
}
