<?php

namespace App\Events;

use App\Models\Refund;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class UpdateRefundRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $refund;
    public $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Refund $refund, $oldStatus = null)
    {
        $this->refund = $refund;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {

    }
}
