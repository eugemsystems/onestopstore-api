<?php

namespace App\Channels;

use App\Services\Msg91WhatsAppService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    protected $whatsappService;

    public function __construct(Msg91WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Check if WhatsApp is enabled in config
        if (!config('services.msg91.whatsapp_enabled', false)) {
            return;
        }

        // Get the phone number from the notifiable entity
        $phoneNumber = $this->getPhoneNumber($notifiable);

        if (!$phoneNumber) {
//            Log::warning('Cannot send WhatsApp notification: No phone number', [
//                'notifiable_type' => get_class($notifiable),
//                'notifiable_id' => $notifiable->id ?? null
//            ]);
            return;
        }

        // Get the WhatsApp message data from the notification
        $message = $notification->toWhatsApp($notifiable);

        if (!$message) {
//            Log::warning('Notification does not support WhatsApp channel', [
//                'notification' => get_class($notification)
//            ]);
            return;
        }

        // Send the message
        try {
            if (is_array($message)) {
                // Check if it's the new MSG91 format (with payload structure)
                if (isset($message['payload']) && isset($message['integrated_number'])) {
                    // New format: Direct MSG91 API payload
                    // Send directly using HTTP client
                    $result = $this->sendDirectPayload($phoneNumber, $message);
                } elseif (isset($message['template_id'])) {
                    // Old format: Template message (convert to MSG91 format internally)
                    $result = $this->whatsappService->sendTemplate(
                        $phoneNumber,
                        $message['template_id'],
                        $message['variables'] ?? [],
                        $message['options'] ?? []
                    );
                } else {
                    Log::warning('Invalid WhatsApp message format', [
                        'message_keys' => array_keys($message)
                    ]);
                    return;
                }
            } else {
                // Simple text message
                $result = $this->whatsappService->sendText($phoneNumber, $message);
            }

            if (!$result['success']) {
                Log::error('WhatsApp notification failed', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id ?? null,
                    'phone' => $phoneNumber,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp notification exception', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send direct MSG91 payload
     *
     * @param string $phoneNumber
     * @param array $message
     * @return array
     */
    protected function sendDirectPayload($phoneNumber, $message)
    {
        try {
            $authKey = config('services.msg91.auth_key');
            $baseUrl = config('services.msg91.base_url', 'https://control.msg91.com/api/v5');

            $response = \Illuminate\Support\Facades\Http::withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'protocols' => ['http', 'https']
                ]
            ])->withHeaders([
                'authkey' => $authKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($baseUrl . '/whatsapp/whatsapp-outbound-message/bulk/', $message);

            $statusCode = $response->status();
            $result = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $result['id'] ?? $result['data']['id'] ?? null,
                    'data' => $result
                ];
            } else {
                Log::error('MSG91 direct payload failed', [
                    'phone' => $phoneNumber,
                    'status' => $statusCode,
                    'error' => $result
                ]);

                return [
                    'success' => false,
                    'error' => $result['errors'] ?? $result['message'] ?? 'Failed to send WhatsApp message',
                    'status_code' => $statusCode,
                    'data' => $result
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get the phone number from the notifiable entity
     *
     * @param  mixed  $notifiable
     * @return string|null
     */
    protected function getPhoneNumber($notifiable)
    {
        // Try multiple methods to get phone number
        if (method_exists($notifiable, 'routeNotificationForWhatsApp')) {
            return $notifiable->routeNotificationForWhatsApp();
        }

        // Check common phone fields
        $phoneFields = ['whatsapp_number', 'phone', 'mobile', 'phone_number', 'contact_number'];

        foreach ($phoneFields as $field) {
            if (isset($notifiable->$field) && !empty($notifiable->$field)) {
                return $this->formatPhoneNumber($notifiable->$field, $notifiable->country_code ?? null);
            }
        }

        return null;
    }

    /**
     * Format phone number with country code
     *
     * @param string $phone
     * @param string|null $countryCode
     * @return string
     */
    protected function formatPhoneNumber($phone, $countryCode = null)
    {
        // Remove all non-numeric characters
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // If it doesn't start with country code and we have one, prepend it
        if ($countryCode && !str_starts_with($clean, $countryCode)) {
            $countryCode = preg_replace('/[^0-9]/', '', $countryCode);
            $clean = $countryCode . $clean;
        }

        return $clean;
    }
}

