<?php

namespace App\Listeners;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Events\PlaceOrderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\PlaceOrderNotification;
use App\Jobs\SyncOrderToCrm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PlaceOrderListener
{
    /**
     * Handle the event.
     * NOTE: Removed ShouldQueue to fire synchronously and avoid serialization issues
     * CRM sync job is still queued separately
     */
    public function handle(PlaceOrderEvent $event)
    {

        // Prevent duplicate processing - use a cache lock
        // Wrapped in try/catch: if Redis is down we still want orders to be created
        $lockKey = "place_order_notification:{$event->order->id}";
        try {
            if (Cache::has($lockKey)) {
                return;
            }
            Cache::put($lockKey, true, now()->addMinutes(5));
        } catch (\Exception $cacheEx) {
            Log::warning('PlaceOrderListener: cache check failed (Redis down?), continuing without dedup', [
                'order_id' => $event->order->id,
                'error' => $cacheEx->getMessage(),
            ]);
        }

        $consumer = $event->order->consumer;
        if (isset($consumer) && is_null($event->order->parent_id)) {
            $consumer->notify(new PlaceOrderNotification($event->order, RoleEnum::CONSUMER));
        }

        foreach ($event->order->sub_orders as $sub_order) {
            if (isset($sub_order->store_id)) {
                $vendor = Helpers::getStoreById($sub_order->store_id)?->vendor;
                $vendor->notify(new PlaceOrderNotification($sub_order, RoleEnum::VENDOR));
            }
        }

        $admin = User::role(RoleEnum::ADMIN)->first();
        if (isset($admin)) {
            $admin->notify(new PlaceOrderNotification($event->order, RoleEnum::ADMIN));
        }

        // Sync new order to CRM
        if (is_null($event->order->parent_id)) {
            try {
                SyncOrderToCrm::dispatch($event->order->id, 'created')->afterCommit();
            } catch (\Exception $dispatchEx) {
                Log::warning('PlaceOrderListener: CRM sync dispatch failed (Redis down?)', [
                    'order_id' => $event->order->id,
                    'error' => $dispatchEx->getMessage(),
                ]);
            }
        }
    }
}
