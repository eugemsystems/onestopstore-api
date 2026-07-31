<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\UpdateRefundRequestEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use App\Notifications\UpdateRefundRequestNotification;

class UpdateRefundRequestListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UpdateRefundRequestEvent $event): void
    {
        // Dedup guard: only send once per refund+status within 10 minutes.
        // Covers both event-fired-twice and listener-job-retry scenarios.
        $key = 'refund-notif-sent:' . $event->refund->id . ':' . $event->refund->status;
        if (!Cache::add($key, 1, 600)) {
            \Log::channel('single')->warning('[UpdateRefundRequestListener] duplicate suppressed', [
                'refund_id' => $event->refund->id,
                'status'    => $event->refund->status,
            ]);
            return;
        }

        $consumer = User::where('id', $event->refund->consumer_id)->first();
        if (isset($consumer)) {
            $consumer->notify(new UpdateRefundRequestNotification($event->refund, $event->oldStatus));
        }
    }
}
