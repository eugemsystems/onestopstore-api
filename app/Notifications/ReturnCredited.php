<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReturnCredited extends Notification
{
    use Queueable;

    protected $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['database','mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Wallet Credited for Return')
            ->greeting('Hello ' . ($notifiable->name ?? 'Customer') . ',')
            ->line('Your account wallet has been credited with ' . number_format((float)$this->amount, 2) . ' due to an approved return.')
            ->line('You can use this balance on your next checkout.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'return_wallet_credited',
            'amount' => $this->amount,
            'message' => 'Wallet credited with ' . number_format((float)$this->amount, 2),
        ];
    }
}
