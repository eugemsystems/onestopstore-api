<?php

namespace App\Notifications;

use App\Models\LaybyApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LaybyIdDocumentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const MAX_REMINDERS = 5;

    public function __construct(
        private readonly LaybyApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app         = $this->application;
        $count       = $app->id_document_reminder_count;
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        // Upload happens from the My Laybys page — no per-application document route exists
        $uploadUrl   = $frontendUrl . '/en/account/laybys/'.$this->application->id;
        $isEscalated = $count >= self::MAX_REMINDERS;

        return (new MailMessage)
            ->subject($isEscalated
                ? "⚠️ Final Notice: ID Document Required — {$app->application_number}"
                : "📋 Reminder {$count} of " . self::MAX_REMINDERS . ": Please Upload Your ID — {$app->application_number}"
            )
            ->view('emails.layby-id-document-reminder', [
                'user'        => $notifiable,
                'application' => $app,
                'uploadUrl'   => $uploadUrl,
                'count'       => $count,
                'maxReminders'=> self::MAX_REMINDERS,
                'isEscalated' => $isEscalated,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'               => 'layby_id_document_reminder',
            'title'              => "ID Document Required — {$this->application->application_number}",
            'message'            => "Your layby application for \"{$this->application->product_name}\" is awaiting your ID document. Please upload it to proceed.",
            'application_id'     => $this->application->id,
            'application_number' => $this->application->application_number,
        ];
    }
}
