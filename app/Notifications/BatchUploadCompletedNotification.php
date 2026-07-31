<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class BatchUploadCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $chunkNumber;
    public $jobCount;

    /**
     * Create a new notification instance.
     */
    public function __construct($chunkNumber, $jobCount)
    {
        $this->chunkNumber = $chunkNumber;
        $this->jobCount = $jobCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }


    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->success()
            ->content("✅ Chunk #{$this->chunkNumber} completed with {$this->jobCount} uploads.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    // Channel → queue mapping (mail goes to "emails")
    public function viaQueues(): array
    {
        return [
            'slack' => 'default',
        ];
    }

    public function tags(): array {
        return ['notify:batch-upload-complete', 'job:'.$this->jobCount];
    }

}
