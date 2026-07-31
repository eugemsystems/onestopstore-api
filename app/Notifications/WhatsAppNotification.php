<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Channels\WhatsAppChannel;

/**
 * Generic WhatsApp Notification
 *
 * Use this for sending custom WhatsApp messages
 */
class WhatsAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $templateId;
    protected $variables;
    protected $options;

    /**
     * Create a new notification instance.
     *
     * @param string $templateId - MSG91 template ID
     * @param array $variables - Template variables
     * @param array $options - Additional options (language, media, etc.)
     */
    public function __construct(string $templateId, array $variables = [], array $options = [])
    {
        $this->templateId = $templateId;
        $this->variables = $variables;
        $this->options = $options;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable)
    {
        return [
            'template_id' => $this->templateId,
            'variables' => $this->variables,
            'options' => array_merge([
                'language' => 'en'
            ], $this->options)
        ];
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'WhatsApp Notification',
            'message' => 'A WhatsApp message was sent',
            'template_id' => $this->templateId,
            'type' => 'whatsapp'
        ];
    }
}

