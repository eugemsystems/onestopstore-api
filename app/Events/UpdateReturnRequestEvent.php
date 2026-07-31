<?php

namespace App\Events;

use App\Models\ReturnRequest;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class UpdateReturnRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;
    public $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(ReturnRequest $return, $oldStatus = null)
    {
        $this->return = $return;
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

