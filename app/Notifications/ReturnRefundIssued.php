<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReturnRefundIssued extends Notification
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
            ->subject('Refund Issued for Return')
            ->greeting('Hello ' . ($notifiable->name ?? 'Customer') . ',')
            ->line('A refund of ' . number_format((float)$this->amount, 2) . ' has been issued for your approved return.')
            ->line('Depending on your payment provider, it may take several days to reflect.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'return_refund_issued',
            'amount' => $this->amount,
            'message' => 'Refund issued: ' . number_format((float)$this->amount, 2),
        ];
    }
}
