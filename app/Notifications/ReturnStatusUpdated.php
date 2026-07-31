<?php

namespace App\Notifications;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use Illuminate\Notifications\Messages\MailMessage;

class ReturnStatusUpdated extends Notification
{
    use Queueable;

    /** @var ReturnRequest */
    public $returnRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        // Send to database and mail (if mail configured)
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $r = $this->returnRequest;
        // Ensure relations available for friendly text
        $r->loadMissing(['order', 'product', 'user']);

        $userName    = $notifiable->name ?? ($r->user->name ?? 'Customer');
        $orderNumber = $r->order->order_number ?? $r->order_id;
        $productName = $r->product->name ?? ('Product #' . $r->product_id);

        $status = ucfirst((string) $r->status);
        $subject = "Return #{$r->id} Status: {$status}";

        // Build status line
        if ($r->status === 'approved') {
            $outcome = $r->preferred_outcome ? ucfirst($r->preferred_outcome) : '-';
            $line2 = "Your return was approved. Outcome: {$outcome}.";
        } elseif ($r->status === 'rejected') {
            $reason = trim((string) ($r->rejection_reason ?? ''));
            if ($reason === '') { $reason = 'N/A'; }
            $line2 = "Your return was rejected. Reason: {$reason}.";
        } else {
            $line2 = 'Your return status is now: ' . ($r->status ?? '-') . '.';
        }

        // Optional: include original reasons provided by customer
        $extra = [];
        if (!empty($r->return_reason))     { $extra[] = 'Return reason: ' . $r->return_reason; }
        if (!empty($r->sub_reason))        { $extra[] = 'Details: ' . $r->sub_reason; }
        if (!empty($r->description))       { $extra[] = 'Description: ' . $r->description; }
        $extraText = $extra ? ("\n" . implode("\n", $extra)) : '';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $userName . ',')
            ->line("Return #{$r->id} for Order #{$orderNumber} ({$productName})")
            ->line($line2)
            ->line($extraText !== '' ? $extraText : null)
            ->line('Thank you for shopping with us.')
            ->salutation('Regards,' . "\n" . 'Raines Africa');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        $r = $this->returnRequest;
        $r->loadMissing(['order','product']);
        return [
            'type'              => 'return_status_updated',
            'return_id'         => $r->id,
            'order_id'          => $r->order_id,
            'order_number'      => $r->order->order_number ?? null,
            'product_id'        => $r->product_id,
            'product_name'      => $r->product->name ?? null,
            'status'            => $r->status,
            'preferred_outcome' => $r->preferred_outcome,
            'rejection_reason'  => $r->rejection_reason ?? null,
            'message'           => $this->buildMessage($r),
        ];
    }

    protected function buildMessage(ReturnRequest $r)
    {
        if ($r->status === 'approved') {
            return 'Return approved. Outcome: ' . ($r->preferred_outcome ?: '-');
        }
        if ($r->status === 'rejected') {
            return 'Return rejected. Reason: ' . ($r->rejection_reason ?: 'N/A');
        }
        return 'Return status updated to: ' . $r->status;
    }
}
