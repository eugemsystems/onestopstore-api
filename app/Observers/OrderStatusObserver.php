<?php

namespace App\Observers;

use App\Jobs\SyncOrderStatusToCrm;
use App\Jobs\SyncOrderToCrm;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class OrderStatusObserver
{
    /**
     * Handle the OrderStatus "created" event.
     */
    public function created(OrderStatus $orderStatus): void
    {
        reCacheOrderStatuses();
        SyncOrderStatusToCrm::dispatch($orderStatus->id, 'created')->afterCommit();
    }

    /**
     * Handle the OrderStatus "updated" event.
     */
    public function updated(OrderStatus $orderStatus): void
    {
        $this->reFreshOrderStatuses();
        SyncOrderStatusToCrm::dispatch($orderStatus->id, 'updated')->afterCommit();
    }

    /**
     * Handle the OrderStatus "deleted" event.
     */
    public function deleted(OrderStatus $orderStatus): void
    {
        $this->reFreshOrderStatuses();
    }

    /**
     * Handle the OrderStatus "restored" event.
     */
    public function restored(OrderStatus $orderStatus): void
    {
        $this->reFreshOrderStatuses();
    }

    /**
     * Handle the OrderStatus "force deleted" event.
     */
    public function forceDeleted(OrderStatus $orderStatus): void
    {
        $this->reFreshOrderStatuses();
    }

    public function reFreshOrderStatuses(): void
    {
        reCacheOrderStatuses();
    }

}
