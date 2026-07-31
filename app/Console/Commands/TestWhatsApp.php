<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Msg91WhatsAppService;
use App\Models\User;
use App\Notifications\WhatsAppNotification;

class TestWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:whatsapp
                            {action=send : Action to perform (send|status|user)}
                            {--phone= : Phone number (with country code)}
                            {--template= : Template ID}
                            {--user= : User ID to send notification}
                            {--message-id= : Message ID to check status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test MSG91 WhatsApp integration';

    /**
     * Execute the console command.
     */
    public function handle(Msg91WhatsAppService $whatsapp)
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'send':
                return $this->testSend($whatsapp);

            case 'status':
                return $this->testStatus($whatsapp);

            case 'user':
                return $this->testUserNotification();

            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: send, status, user");
                return 1;
        }
    }

    /**
     * Test sending a WhatsApp message
     */
    protected function testSend(Msg91WhatsAppService $whatsapp)
    {
        $phone = $this->option('phone');
        $template = $this->option('template');

        if (!$phone) {
            $phone = $this->ask('Enter phone number (with country code, e.g., 27888888888)');
        }

        if (!$template) {
            $template = $this->ask('Enter template ID');
        }

        $this->info("📱 Sending WhatsApp message...");
        $this->info("Phone: {$phone}");
        $this->info("Template: {$template}");

        // Ask if template has parameters
        $hasParams = $this->confirm('Does this template have parameters/placeholders?', false);

        $variables = [];
        if ($hasParams) {
            $paramCount = $this->ask('How many parameters does the template have?', '4');

            $variables = [
                'Test User',
                'TEST-' . rand(1000, 9999),
                'R 100.00',
                'Testing',
            ];

            // Adjust variables array to match param count
            $variables = array_slice($variables, 0, (int)$paramCount);

            $this->info("Variables: " . implode(', ', $variables));
        } else {
            $this->info("No parameters - sending template as-is");
        }

        $result = $whatsapp->sendTemplate($phone, $template, $variables);

        if ($result['success']) {
            $this->info("✅ Message sent successfully!");
            $this->info("Message ID: " . ($result['message_id'] ?? 'N/A'));

            if (isset($result['data'])) {
                $this->line("Response: " . json_encode($result['data'], JSON_PRETTY_PRINT));
            }
        } else {
            $this->error("❌ Failed to send message");
            $this->error("Error: " . ($result['error'] ?? 'Unknown error'));

            if (isset($result['data'])) {
                $this->line("Response: " . json_encode($result['data'], JSON_PRETTY_PRINT));
            }

            if (isset($result['status_code'])) {
                $this->error("HTTP Status: " . $result['status_code']);
            }

            if (isset($result['raw_response'])) {
                $this->line("Raw Response: " . $result['raw_response']);
            }
        }

        return $result['success'] ? 0 : 1;
    }

    /**
     * Test checking message status
     */
    protected function testStatus(Msg91WhatsAppService $whatsapp)
    {
        $messageId = $this->option('message-id');

        if (!$messageId) {
            $messageId = $this->ask('Enter message ID');
        }

        $this->info("📊 Checking message status...");
        $this->info("Message ID: {$messageId}");

        $result = $whatsapp->getStatus($messageId);

        if ($result['success']) {
            $this->info("✅ Status retrieved successfully!");
            $this->line(json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error("❌ Failed to get status");
            $this->error("Error: " . $result['error']);
        }

        return $result['success'] ? 0 : 1;
    }

    /**
     * Test sending notification to a user
     */
    protected function testUserNotification()
    {
        $userId = $this->option('user');

        if (!$userId) {
            $userId = $this->ask('Enter user ID');
        }

        $user = User::find($userId);

        if (!$user) {
            $this->error("❌ User not found with ID: {$userId}");
            return 1;
        }

        $this->info("👤 User: {$user->name} ({$user->email})");
        $this->info("📱 Phone: " . ($user->phone ?? 'N/A'));
        $this->info("🌍 Country Code: " . ($user->country_code ?? 'N/A'));

        $whatsappPhone = $user->routeNotificationForWhatsApp();
        $this->info("📲 WhatsApp Number: " . ($whatsappPhone ?? 'N/A'));

        if (!$whatsappPhone) {
            $this->error("❌ User has no phone number configured");
            return 1;
        }

        if (!config('services.msg91.whatsapp_enabled')) {
            $this->warn("⚠️  WhatsApp notifications are disabled in config");
            $this->info("Set MSG91_WHATSAPP_ENABLED=true in .env to enable");
            return 1;
        }

        $template = $this->ask('Enter template ID to send');

        if (!$template) {
            $this->error("Template ID is required");
            return 1;
        }

        $this->info("Sending notification...");

        try {
            $user->notify(new WhatsAppNotification(
                $template,
                [
                    $user->name,
                    'TEST-' . rand(1000, 9999),
                    'R 100.00',
                    'Test Notification',
                ]
            ));

            $this->info("✅ Notification dispatched!");
            $this->info("Check queue: php artisan queue:work");
            $this->info("Check logs: tail -f storage/logs/laravel.log");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send notification");
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}

