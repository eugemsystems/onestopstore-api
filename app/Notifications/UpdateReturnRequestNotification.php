<?php

namespace App\Notifications;

use App\Models\User;
use App\Helpers\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;

class UpdateReturnRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $return;
    private $oldStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct($return, $oldStatus = null)
    {
        $this->return = $return;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $consumer = User::where('id', $this->return->user_id)->pluck('name')->first();
        $orderNumber = $this->return->order->order_number ?? $this->return->order_id;
        $productName = $this->return->product->name ?? 'Product';
        $productImage = $this->return->product->product_thumbnail->image_url ?? null;

        // Detect if this was a reopen action (from rejected/approved to pending)
        $wasReopened = $this->return->status === 'pending' &&
                       $this->oldStatus &&
                       in_array($this->oldStatus, ['approved', 'rejected']);

        $subject = $wasReopened
            ? 'Return Request Reopened - Under Review'
            : 'Return Request ' . ucfirst($this->return->status);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.return-status-updated', [
                'return' => $this->return,
                'customerName' => $consumer,
                'orderNumber' => $orderNumber,
                'productName' => $productName,
                'productImage' => $productImage,
                'status' => $this->return->status,
                'wasReopened' => $wasReopened,
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $orderNumber = $this->return->order->order_number ?? $this->return->order_id;
        $productName = $this->return->product->name ?? 'Product';
        $wasReopened = $this->return->status === 'pending' &&
                       $this->oldStatus &&
                       in_array($this->oldStatus, ['approved', 'rejected']);

        $statusText = $wasReopened ? 'Reopened' : ucfirst($this->return->status);
        $icon = $this->return->status === 'approved' ? '✅' : ($this->return->status === 'rejected' ? '❌' : '🔄');

        $message = (new SlackMessage)
            ->text("{$icon} Return Request {$statusText}")
            ->headerBlock("Return #{$this->return->id} - {$statusText}")
            ->sectionBlock(function ($section) use ($statusText, $orderNumber, $productName) {
                $section->field("*Status:*\n{$statusText}")->markdown();
                $section->field("*Order:*\n#{$orderNumber}")->markdown();
                $section->field("*Product:*\n{$productName}")->markdown();
                $section->field("*Updated:*\n" . ($this->return->updated_at?->format('Y-m-d H:i') ?? now()))->markdown();
            });

        // Add rejection reason if present
        if (!empty($this->return->rejection_reason)) {
            $message->dividerBlock()
                    ->sectionBlock(function ($section) {
                        $section->text("*Rejection Reason:*\n" . $this->return->rejection_reason)->markdown();
                    });
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Return request updated!",
            'message' => "Your return request status has been {$this->return->status}",
            'type' => "return"
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
        return ['notify:update-return-request', 'return:'.$this->return->id];
    }
}

