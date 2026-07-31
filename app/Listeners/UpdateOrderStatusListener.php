<?php

namespace App\Listeners;

use App\Enums\RoleEnum;
use App\Events\UpdateOrderStatusEvent;
use App\Models\User;
use App\Notifications\PlaceOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\UpdateOrderStatusNotification;
use App\Jobs\SyncOrderToCrm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UpdateOrderStatusListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UpdateOrderStatusEvent $event)
    {
        // Prevent duplicate processing - use a cache lock with order status
        $lockKey = "update_order_status_notification:{$event->order->id}:{$event->order->order_status_id}";

        // If we already processed this status update in the last 5 minutes, skip it
        if (Cache::has($lockKey)) {
            return;
        }

        // Set cache lock for 5 minutes
        Cache::put($lockKey, true, now()->addMinutes(5));

        $consumer = $event->order->consumer;

        $crmHandlesCustomerEmails = config('services.crm.enabled') && env('CRM_SEND_CUSTOMER_EMAILS', false);

        if (isset($consumer) && !$crmHandlesCustomerEmails) {
            $consumer->notify(new UpdateOrderStatusNotification($event->order));
        }

        $admin = User::role(RoleEnum::ADMIN)->first();
        if (isset($admin)) {
            $admin->notify(new UpdateOrderStatusNotification($event->order));
        }

        // Sync order status update to CRM
        SyncOrderToCrm::dispatch($event->order->id, 'updated')->afterCommit();
    }
}
