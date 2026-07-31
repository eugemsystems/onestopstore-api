<?php

namespace App\Notifications;

use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class CreateWithdrawRequestNotification extends Notification implements ShouldQueue
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
        return ['mail','database','slack'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $vendor = User::where('id', $this->withdrawRequest->vendor_id)->pluck('name')->first();
        $admin = User::role(RoleEnum::ADMIN)->pluck('name')->first();
        return (new MailMessage)
            ->subject("Withdrawal Request from {$vendor}")
            ->greeting("Hello {$admin},")
            ->line("A withdrawal request has been submitted by {$vendor}.")
            ->line("Requested Amount: {$this->withdrawRequest->amount}")
            ->line("Vendor's Message:")
            ->line($this->withdrawRequest->message)
            ->line("Please review and take appropriate action as necessary.");
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $vendor = User::where('id', $this->withdrawRequest->vendor_id)->pluck('name')->first();
        $symbol = (string) (Helpers::getDefaultCurrencySymbol() ?? '');
        $amountStr = $symbol . number_format((float) ($this->withdrawRequest->amount ?? 0), 2, '.', ',');

        return (new SlackMessage)
            ->content(":moneybag: New Withdraw Request")
            ->attachment(function ($attachment) use ($vendor, $amountStr) {
                $attachment->title('Withdraw Request')
                    ->fields([
                        'Vendor'    => (string) $vendor,
                        'Amount'    => (string) $amountStr,
                        'Status'    => (string) ($this->withdrawRequest->status ?? 'pending'),
                        'Requested' => (string) (optional($this->withdrawRequest->created_at)->format('Y-m-d H:i') ?? now()),
                    ]);
            })
            ->attachment(function ($attachment) {
                if (!empty($this->withdrawRequest->message)) {
                    $attachment->title('Message')
                        ->content((string) $this->withdrawRequest->message);
                }
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        //for admin
        $vendor = User::where('id', $this->withdrawRequest->vendor_id)->pluck('name')->first();
        $symbol = Helpers::getDefaultCurrencySymbol();
        return [
            'title' => "New Withdraw Request",
            'message' =>  "A withdrawal request for {$symbol}{$this->withdrawRequest->amount} has been received from a {$vendor}.",
            'type' => "withdraw",
        ];
    }

    // Channel → queue mapping (mail goes to "emails")
    public function viaQueues(): array
    {
        return [
            'mail'     => 'emails',
            'database' => 'default',
            'slack'    => 'default',
        ];
    }

    public function tags(): array {
        return ['notify:create-withdrawal-request', 'withdrawal:'.$this->withdrawRequest->id];
    }
}
