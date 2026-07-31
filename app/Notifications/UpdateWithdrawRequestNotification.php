<?php

namespace App\Notifications;

use App\Models\User;
use App\Helpers\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UpdateWithdrawRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $withdrawRequest;

    /**
     * The name of the queue on which to place the notification.
     */
    //public $queue = 'emails';


    /**
     * Create a new notification instance.
     */
    public function __construct($withdrawRequest)
    {
        $this->withdrawRequest = $withdrawRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $vendor = User::where('id', $this->withdrawRequest->vendor_id)->pluck('name')->first();
        return (new MailMessage)
            ->subject('withdraw Request Status Updated')
            ->greeting('Hello ' . $vendor . ',')
            ->line('We would like to inform you that the status of your refund request has been updated:')
            ->line('Your refund request for ' . $this->withdrawRequest->amount . ' has been ' . $this->withdrawRequest->status . '.')
            ->line('If you require any further assistance, please don’t hesitate to contact us.')
            ->line('Thank you.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // for vendor
        $symbol = Helpers::getDefaultCurrencySymbol();
        return [
            'title' => "Withdraw Request is {$this->withdrawRequest->status} by admin",
            'message' => "Your withdrawal request for {$symbol}{$this->withdrawRequest->amount} has been {$this->withdrawRequest->status}",
            'type' => "withdraw"
        ];
    }

    // Channel → queue mapping (mail goes to "emails")
    public function viaQueues(): array
    {
        return [
            'mail'     => 'emails',
            'database' => 'default',
        ];
    }

    public function tags(): array {
        return ['notify:update-withdrawal-request', 'withdrawal-update:'.$this->withdrawRequest->id];
    }
}
