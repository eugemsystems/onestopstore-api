<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * MSG91 WhatsApp Service
 *
 * Documentation: https://docs.msg91.com/whatsapp
 */
class Msg91WhatsAppService
{
    protected $authKey;
    protected $baseUrl;
    protected $senderId;

    public function __construct()
    {
        $this->authKey = config('services.msg91.auth_key');
        // Use the v5 API endpoint
        $this->baseUrl = config('services.msg91.base_url', 'https://control.msg91.com/api/v5');
        $this->senderId = config('services.msg91.whatsapp_sender_id');

    }

    /**
     * Send a WhatsApp template message using MSG91 Bulk API
     *
     * @param string $phoneNumber - Phone number with country code (e.g., 918888888888)
     * @param string $templateId - MSG91 template name
     * @param array $variables - Template variables as key-value pairs
     * @param array $options - Additional options (media, buttons, etc.)
     * @return array
     */
    public function sendTemplate(string $phoneNumber, string $templateId, array $variables = [], array $options = [])
    {
        try {
            // Clean phone number (remove +, spaces, dashes)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Build components based on template requirements
            // MSG91 expects components as an object (stdClass), not array
            $components = new \stdClass();

            // Add header image if provided
            if (!empty($options['header_image'])) {
                $components->header_1 = (object)[
                    'type' => 'image',
                    'value' => $options['header_image']
                ];
            }

            // Add body variables (body_1, body_2, body_3, body_4)
            if (!empty($variables)) {
                $index = 1;
                foreach ($variables as $value) {
                    $propertyName = 'body_' . $index;
                    $components->$propertyName = (object)[
                        'type' => 'text',
                        'value' => (string) $value
                    ];
                    $index++;
                }
            }

            // Add button URL variable (required by template)
            // If not provided, use a default placeholder
            $components->button_1 = (object)[
                'subtype' => 'url',
                'type' => 'text',
                'value' => $options['button_url'] ?? 'https://example.com'
            ];

            // Build the request payload for MSG91 Bulk API
            // IMPORTANT: Match the exact structure from MSG91 documentation
            $payload = [
                'integrated_number' => $this->senderId,
                'content_type' => 'template',
                'payload' => [
                    'messaging_product' => 'whatsapp',
                    'type' => 'template',
                    'template' => [
                        'name' => $templateId,
                        'language' => [
                            'code' => $options['language'] ?? 'en',
                            'policy' => 'deterministic'
                        ],
                        'namespace' => '53e292d4_2ada_44e0_8aa6_8321e21292fb',
                        'to_and_components' => [
                            [
                                'to' => [$cleanPhone],
                                'components' => $components // This will be encoded as object in JSON
                            ]
                        ]
                    ]
                ]
            ];

            // Send the request with correct headers
            $response = Http::withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'protocols' => ['http', 'https']
                ]
            ])->withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/whatsapp/whatsapp-outbound-message/bulk/', $payload);

            $statusCode = $response->status();
            $result = $response->json();


            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $result['id'] ?? $result['data']['id'] ?? null,
                    'data' => $result
                ];
            } else {

                return [
                    'success' => false,
                    'error' => $result['errors'] ?? $result['message'] ?? 'Failed to send WhatsApp message',
                    'status_code' => $statusCode,
                    'data' => $result,
                    'raw_response' => $response->body()
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a simple text message (if your MSG91 account supports it)
     * Note: Most WhatsApp Business API requires approved templates
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendText(string $phoneNumber, string $message)
    {
        try {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            $payload = [
                'integrated_number' => $this->senderId,
                'content_type' => 'text',
                'payload' => [
                    'messaging_product' => 'whatsapp',  // Required by WhatsApp Business API
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]
            ];

            $response = Http::withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'protocols' => ['http', 'https']
                ]
            ])->withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/whatsapp/whatsapp-outbound-message/', $payload);

            $result = $response->json();

            if ($response->successful()) {


                return [
                    'success' => true,
                    'message_id' => $result['id'] ?? null,
                    'data' => $result
                ];
            } else {

                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Failed to send message'
                ];
            }

        } catch (Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format variables for MSG91 template parameters
     *
     * @param array $variables
     * @return array
     */
    protected function formatParameters(array $variables): array
    {
        $parameters = [];

        foreach ($variables as $key => $value) {
            // If it's a simple array (numeric keys), use as-is
            if (is_numeric($key)) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) $value
                ];
            } else {
                // If associative, use the value
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) $value
                ];
            }
        }

        return $parameters;
    }

    /**
     * Check message delivery status
     *
     * @param string $messageId
     * @return array
     */
    public function getStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey
            ])->get($this->baseUrl . '/whatsapp/whatsapp-outbound-message/status', [
                'id' => $messageId
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('MSG91 status check failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

