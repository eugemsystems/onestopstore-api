<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\UpdateReturnRequestEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\UpdateReturnRequestNotification;

class UpdateReturnRequestListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UpdateReturnRequestEvent $event): void
    {
        $consumer = User::where('id', $event->return->user_id)->first();
        if (isset($consumer)) {
            $consumer->notify(new UpdateReturnRequestNotification($event->return, $event->oldStatus));
        }
    }
}

