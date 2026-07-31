<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Auth events - cache user permissions on login
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\CacheUserPermissionsOnLogin::class,
        ],

        // Order events
        \App\Events\PlaceOrderEvent::class => [
            \App\Listeners\PlaceOrderListener::class,
        ],
        \App\Events\UpdateOrderStatusEvent::class => [
            \App\Listeners\UpdateOrderStatusListener::class,
        ],

        // Refund events
        \App\Events\UpdateRefundRequestEvent::class => [
            \App\Listeners\UpdateRefundRequestListener::class,
        ],

        // Return events
        \App\Events\UpdateReturnRequestEvent::class => [
            \App\Listeners\UpdateReturnRequestListener::class,
        ],

        // Vendor Pesepay events
        \Eugem\Pesepay\Events\PaymentSucceeded::class => [
            \App\Listeners\HandlePesePayPayment::class,
            \App\Listeners\LogPesepayTransaction::class,
        ],
        \Eugem\Pesepay\Events\PaymentFailed::class => [
            \App\Listeners\LogPesepayTransaction::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
