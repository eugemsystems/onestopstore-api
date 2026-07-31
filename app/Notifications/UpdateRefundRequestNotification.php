<?php

namespace App\Notifications;

use App\Models\User;
use App\Helpers\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;

class UpdateRefundRequestNotification extends Notification implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    private $refund;
    private $oldStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct($refund, $oldStatus = null)
    {
        $this->refund = $refund;
        $this->oldStatus = $oldStatus;
    }

    // Prevent duplicate emails if the listener job retries (e.g. transient mail failure).
    // Same refund + same status = same unique key → only one job processes within 10 minutes.
    public function uniqueId(): string
    {
        return 'refund-update-' . $this->refund->id . '-' . $this->refund->status;
    }

    public function uniqueFor(): int
    {
        return 600; // 10 minutes
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
        $consumer = User::with('paymentAccounts')->where('id', $this->refund->consumer_id)->first();
        $orderNumber = $this->refund->order->order_number ?? $this->refund->order_id;
        $currencySymbol = $this->refund->order->currency_symbol ?? Helpers::getDefaultCurrencySymbol() ?? '$';

        $wasReopened = $this->refund->status === 'pending' &&
                       $this->oldStatus &&
                       in_array($this->oldStatus, ['approved', 'rejected']);

        $subject = $wasReopened
            ? 'Refund Request Reopened - Under Review'
            : 'Refund Request ' . ucfirst($this->refund->status);

        $paymentAccount = $consumer?->paymentAccounts
            ? $consumer->paymentAccounts->where('status', 1)->first()
            : null;

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.refund-status-updated', [
                'refund'         => $this->refund,
                'customerName'   => $consumer?->name ?? 'Valued Customer',
                'orderNumber'    => $orderNumber,
                'currencySymbol' => $currencySymbol,
                'status'         => $this->refund->status,
                'wasReopened'    => $wasReopened,
                'paymentAccount' => $paymentAccount,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $symbol = (string) (Helpers::getDefaultCurrencySymbol() ?? '');
        $amountStr = $symbol . number_format((float) ($this->refund->amount ?? 0), 2, '.', ',');
        $wasReopened = $this->refund->status === 'pending' &&
                       $this->oldStatus &&
                       in_array($this->oldStatus, ['approved', 'rejected']);

        $statusText = $wasReopened ? 'Reopened' : ucfirst($this->refund->status);
        $icon = $this->refund->status === 'approved' ? '✅' : ($this->refund->status === 'rejected' ? '❌' : '🔄');

        return (new SlackMessage)
            ->text("{$icon} Refund Request {$statusText}")
            ->headerBlock("Refund #{$this->refund->id} - {$statusText}")
            ->sectionBlock(function ($section) use ($statusText, $amountStr) {
                $section->field("*Status:*\n{$statusText}")->markdown();
                $section->field("*Amount:*\n{$amountStr}")->markdown();
                $section->field("*Updated:*\n" . ($this->refund->updated_at?->format('Y-m-d H:i') ?? now()))->markdown();
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        //for consumer
        return [
            'title' => "Refund request updated!",
            'message' => "Your Refund request status has been {$this->refund->status}",
            'type' => "refund"
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
        return ['notify:update-refund-request', 'refund:'.$this->refund->id];
    }
}
