<?php

namespace App\Notifications;

use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FastImportCompleted extends Notification
{
    use Queueable;

    protected $importJob;
    protected $batchStats;

    /**
     * Create a new notification instance.
     */
    public function __construct(ImportJob $importJob, array $batchStats = [])
    {
        $this->importJob = $importJob;
        $this->batchStats = $batchStats;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $job = $this->importJob;
        $stats = $this->batchStats;

        return (new MailMessage)
            ->subject('✅ Fast Import Completed - ' . ($stats['total_files'] > 1 ? $stats['total_files'] . ' Files' : $job->filename))
            ->view('emails.fast-import-completed', [
                'importJob' => $job,
                'batchStats' => $stats,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'import_job_id' => $this->importJob->id,
            'batch_id' => $this->importJob->batch_id,
            'filename' => $this->importJob->filename,
            'status' => $this->importJob->status,
            'total_items' => $this->importJob->total_items,
            'processed_items' => $this->importJob->processed_items,
            'elasticsearch_status' => $this->batchStats['elasticsearch']['status'] ?? 'unknown',
        ];
    }
}
