<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Payments\PayFast;

class TestLaybyPayfastWebhook extends Command
{
    protected $signature = 'test:layby-payfast-webhook {payment_id=64}';
    protected $description = 'Test PayFast webhook for layby payment (simulates PayFast callback)';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');

        $this->info("Testing PayFast webhook for layby payment ID: {$paymentId}");
        $this->info(str_repeat('=', 60));

        // Simulate PayFast webhook data
        $webhookData = [
            // PayFast standard fields
            'm_payment_id' => 'TEST_M_' . $paymentId,
            'pf_payment_id' => 'TEST_PF_' . time(),
            'payment_status' => 'COMPLETE', // IMPORTANT: Must be COMPLETE
            'item_name' => 'Layby Payment - Test Product',
            'item_description' => 'Payment for layby application #TEST',
            'amount_gross' => '500.00',
            'amount_fee' => '11.50',
            'amount_net' => '488.50',

            // Our custom fields
            'custom_str1' => 'LAYBY_PAYMENT', // CRITICAL: Identifies as layby
            'custom_str2' => (string)$paymentId,
            'custom_str3' => '16', // Application ID
            'custom_int1' => $paymentId, // Payment ID
            'custom_int2' => 16, // Application ID

            // Customer info
            'name_first' => 'Test',
            'name_last' => 'User',
            'email_address' => 'test@example.com',

            // PayFast metadata
            'merchant_id' => config('payfast.merchant_id'),
            'signature' => 'test_signature',
        ];

        $this->info("Webhook data:");
        $this->table(
            ['Field', 'Value'],
            collect($webhookData)->map(fn($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->toArray()
        );

        // Create a mock request with the webhook data
        $mockRequest = Request::create('/api/payfast/webhook', 'POST', $webhookData);

        $this->info("\nCalling PayFast webhook handler...");

        // Call the webhook handler
        try {
            $response = PayFast::webhookHandler($mockRequest);

            $this->info("\n✓ Webhook handler executed successfully!");
            $this->info("Response status: " . $response->getStatusCode());

            $this->info("\n📋 Now check:");
            $this->info("1. Logs: storage/logs/laravel-" . date('Y-m-d') . ".log");
            $this->info("2. Database: SELECT * FROM payfast_transactions ORDER BY id DESC LIMIT 1;");
            $this->info("3. Look for: custom_str1='LAYBY_PAYMENT' or other_fields containing 'LAYBY_PAYMENT'");

        } catch (\Exception $e) {
            $this->error("\n✗ Webhook handler failed!");
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
        }

        return 0;
    }
}

