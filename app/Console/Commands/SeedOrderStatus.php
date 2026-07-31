<?php

namespace App\Console\Commands;

use App\Enums\OrderEnum;
use App\Models\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedOrderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-order-status';

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
        $orderStatus = [
            [
                'name' => OrderEnum::PENDING,
                'system_reserve' => 1,
                'sequence' => '1'
            ],
            [
                'name' => OrderEnum::PROCESSING,
                'system_reserve' => 1,
                'sequence' => '2'
            ],
            [
                'name' => OrderEnum::CANCELLED,
                'system_reserve' => 1,
                'sequence' => '3'
            ],
            [
                'name' => OrderEnum::SHIPPED,
                'system_reserve' => 1,
                'sequence' => '4'
            ],
            [
                'name' => OrderEnum::OUT_FOR_DELIVERY,
                'system_reserve' => 1,
                'sequence' => '5'
            ],
            [
                'name' => OrderEnum::DELIVERED,
                'system_reserve' => 1,
                'sequence' => '6'
            ]
        ];

        foreach ($orderStatus as $status) {
            if (!OrderStatus::where('name', $status['name'])->first()) {
                OrderStatus::create([
                    'name' => $status['name'],
                    'system_reserve' =>  $status['system_reserve'],
                    'sequence' => $status['sequence']
                ]);
            }
        }

        DB::table('seeders')->updateOrInsert([
            'name' => 'OrderStatusSeeder',
            'is_completed' => true
        ]);


        $this->info("\nImport completed.");
    }
}
