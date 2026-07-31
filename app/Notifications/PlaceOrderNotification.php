<?php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Enums\RoleEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;

class PlaceOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $order;
    private $roleName;

    /**
     * The name of the queue on which to place the notification.
     */
    //public $queue = 'emails';


    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $roleName)
    {
        $this->order = $order;
        $this->roleName = $roleName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // Add WhatsApp channel if enabled
        if (config('services.msg91.whatsapp_enabled', false)) {
            $channels[] = \App\Channels\WhatsAppChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        switch($this->roleName) {
            case RoleEnum::CONSUMER:
               return $this->toConsumerMail();
            case RoleEnum::VENDOR:
                return $this->toVendorMail();
            case RoleEnum::ADMIN:
                return $this->toAdminMail();
            default:
                return $this->toConsumerMail();
        }
    }

    public function toAdminMail(): MailMessage
    {
        $order = $this->order->loadMissing(['products','sub_orders.products','billing_address','shipping_address','order_status']);

        // Convert order amounts to display currency
        $order = $this->convertOrderAmounts($order);

        $subject = "An Order #{$order->order_number} has been placed";
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.order-notification', [
                'subject' => $subject,
                'heading' => 'New order placed',
                'intro'   => 'An order has been placed successfully. Please review and process.',
                'order'   => $order,
            ]);
    }

    public function toVendorMail(): MailMessage
    {
        $order = $this->order->loadMissing(['products','sub_orders.products','billing_address','shipping_address','order_status']);

        // Convert order amounts to display currency
        $order = $this->convertOrderAmounts($order);

        $subject = "New Order #{$order->order_number} from Your Store";
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.order-notification', [
                'subject' => $subject,
                'heading' => 'You have a new order',
                'intro'   => 'A new order has been received for your store.',
                'order'   => $order,
            ]);
    }

    public function toConsumerMail(): MailMessage
    {
        $order = $this->order->loadMissing(['products','sub_orders.products','billing_address','shipping_address','order_status','consumer']);

        // Convert order amounts to display currency
        $order = $this->convertOrderAmounts($order);

        $subject = "Your Order #{$order->order_number} Confirmation";
        $greeting = $order->payment_status === PaymentStatus::PENDING
            ? "Hi {$order->consumer->name},"
            : "Hello {$order->consumer->name},";
        $message = $order->payment_status === PaymentStatus::PENDING
            ? (strtolower($order->payment_method) === 'bank_transfer'
                ? "Thanks for your order. Please complete the bank transfer using the details below. Your order will be processed once payment is confirmed."
                : "Thanks for your order. It's on-hold until we confirm that payment has been received.")
            : "We're excited to confirm your order.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->view('emails.order-notification', [
                'subject' => $subject,
                'heading' => config('app.name').' Order Confirmation',
                'intro'   => $message,
                'order'   => $order,
            ]);
    }

    /**
     * Convert order amounts from USD to display currency
     */
    private function convertOrderAmounts($order)
    {
        $rate = floatval($order->exchange_rate ?? 1.0);

        // Convert main order amounts with proper rounding
        $order->amount = round($order->amount * $rate, 2);
        $order->shipping_total = round(($order->shipping_total ?? 0) * $rate, 2);
        $order->fast_shipping_total = round(($order->fast_shipping_total ?? 0) * $rate, 2);
        $order->delivery_price = round(($order->delivery_price ?? 0) * $rate, 2);
        $order->tax_total = round(($order->tax_total ?? 0) * $rate, 2);
        $order->total = round(($order->total ?? 0) * $rate, 2);
        $order->coupon_total_discount = round(($order->coupon_total_discount ?? 0) * $rate, 2);
        $order->points_amount = round(($order->points_amount ?? 0) * $rate, 2);
        $order->wallet_balance = round(($order->wallet_balance ?? 0) * $rate, 2);

        // Convert product prices in pivot
        foreach ($order->products as $product) {
            if (isset($product->pivot)) {
                $product->pivot->single_price = round(($product->pivot->single_price ?? 0) * $rate, 2);
                $product->pivot->subtotal = round(($product->pivot->subtotal ?? 0) * $rate, 2);
                $product->pivot->fast_shipping_cost = round(($product->pivot->fast_shipping_cost ?? 0) * $rate, 2);
            }
        }

        // Also convert sub-order product prices
        if ($order->sub_orders) {
            foreach ($order->sub_orders as $subOrder) {
                if ($subOrder->products) {
                    foreach ($subOrder->products as $product) {
                        if (isset($product->pivot)) {
                            $product->pivot->single_price = round(($product->pivot->single_price ?? 0) * $rate, 2);
                            $product->pivot->subtotal = round(($product->pivot->subtotal ?? 0) * $rate, 2);
                            $product->pivot->fast_shipping_cost = round(($product->pivot->fast_shipping_cost ?? 0) * $rate, 2);
                        }
                    }
                }
            }
        }

        return $order;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        if ($this->roleName !== RoleEnum::ADMIN) {
            // Only mirror admin alerts to Slack
            return (new SlackMessage)->content('');
        }

        // Build a Slack message mirroring the email (without addresses)
        $order = $this->order->loadMissing(['order_status','consumer','products']);

        $symbol = (string) ($order->currency_symbol ?? '');
        $code   = (string) ($order->currency ?? '');
        $rate   = (float) ($order->exchange_rate ?? 1);

        // Totals (converted)
        $itemsSubtotal = 0.0;
        foreach ($order->products as $p) {
            $unit = (float) ($p->pivot->single_price ?? $p->price);
            $itemsSubtotal += ($unit * (int) $p->pivot->quantity);
        }
        $shipping = (float) ($order->shipping_total ?? 0.0);
        $delivery = (float) ($order->delivery_price ?? 0.0);
        $tax      = (float) ($order->tax_total ?? 0.0);
        $discount = (float) ($order->coupon_total_discount ?? 0.0);
        $grand    = (float) ($order->total ?? ($itemsSubtotal + $shipping + $delivery + $tax - $discount));

        // NO CONVERSION - database amounts are already in USD
        // Just display with currency symbol
        $tax           *= $rate;
        $discount      *= $rate;
        $grand         *= $rate;

        $orderInfo = "*Order Number:* #{$order->order_number}\n"
            ."*Order Status:* ".((string) ($order->order_status->name ?? 'n/a'))."\n"
            ."*Payment Status:* ".((string) ($order->payment_status ?? 'n/a'))."\n"
            ."*Payment Method:* ".(strtoupper((string) ($order->payment_method ?? 'n/a')))."\n"
            .(!empty($order->delivery_description) ? "*Delivery Method:* {$order->delivery_description}\n" : '')
            ."*Placed At:* ".((string) optional($order->created_at)->format('Y-m-d H:i'));

        $itemsLines = [];
        foreach ($order->products as $p) {
            $name = (string) ($p->name ?? 'Item');
            $qty  = (int) ($p->pivot->quantity ?? 1);
            $unit = (float) ($p->pivot->single_price ?? $p->price ?? 0.0);
            $itemsLines[] = $name.' × '.$qty.' — '.$symbol.number_format(($unit * $qty) * $rate, 2, '.', ',').($code ? (' '.$code) : '');
        }
        if (empty($itemsLines)) {
            $itemsLines[] = 'No items found.';
        }
        $itemsText = implode("\n", $itemsLines);

        $summaryText = "*Items Subtotal:* {$symbol}".number_format($itemsSubtotal, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Shipping:* {$symbol}".number_format($shipping, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Delivery:* {$symbol}".number_format($delivery, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Tax:* {$symbol}".number_format($tax, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Discount:* - {$symbol}".number_format($discount, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Grand Total:* {$symbol}".number_format($grand, 2, '.', ',').($code ? (' '.$code) : '');

        $noteText = '';
        if (!empty($order->note)) {
            $noteText = (string) $order->note;
        }

        return (new SlackMessage)
            ->content(":tada: New Order Placed")
            ->attachment(function ($attachment) use ($order, $symbol, $code, $grand) {
                $attachment->title('Order #'.$order->order_number)
                    ->fields([
                        'Status'       => (string) ($order->order_status->name ?? 'n/a'),
                        'Payment'      => (string) ($order->payment_status ?? 'n/a'),
                        'Total'        => (string) ($symbol.number_format((float) $grand, 2, '.', ',').($code ? (' '.$code) : '')),
                        'Consumer'     => (string) ($order->consumer->name ?? 'Guest'),
                        'Placed At'    => (string) (optional($order->created_at)->format('Y-m-d H:i') ?? now()),
                    ]);
            })
            ->attachment(function ($attachment) use ($orderInfo) {
                $attachment->title('Order Information')
                    ->content($orderInfo);
            })
            ->attachment(function ($attachment) use ($itemsText) {
                $attachment->title('Order Items')
                    ->content($itemsText);
            })
            ->attachment(function ($attachment) use ($summaryText) {
                $attachment->title('Order Summary')
                    ->content($summaryText);
            })
            ->attachment(function ($attachment) use ($noteText) {
                if ($noteText !== '') {
                    $attachment->title('Order Note')
                        ->content($noteText);
                }
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        switch($this->roleName) {
            case RoleEnum::CONSUMER:
                $message = "Your order has been successfully placed. Order ID: #{$this->order->order_number}. Thank you for choosing us.";
                break;
            case RoleEnum::VENDOR:
                $message = "A consumer has ordered from your catalog. Order ID: #{$this->order->order_number}. Please ensure prompt fulfillment.";
                break;
            case RoleEnum::ADMIN:
                $message = "An order has been placed successfully. Order ID: #{$this->order->order_number}. Your prompt attention is requested.";
                break;
        }

        return [
            'title' => "Order has been placed",
            'message' => $message,
            'type' => "order"
        ];
    }

    /**
     * Get the WhatsApp representation of the notification.
     *
     * @return array|string|null
     */
    public function toWhatsApp(object $notifiable)
    {
        // Load order data
        $order = $this->order->loadMissing(['products', 'consumer', 'order_status']);

        $orderNumber   = $order->order_number;
        $orderStatus   = $order->order_status->name ?? 'Pending';
        $orderStatusLc = strtolower($orderStatus);
        $consumerName  = $order->consumer->name ?? 'Customer';
        $totalAmount   = $order->currency_symbol . ' ' . number_format($order->total * $order->exchange_rate, 2);
        $paymentStatus = $order->payment_status ?? 'Pending';
        $paymentMethod = $order->payment_method ?? 'N/A';

        // =======================
        // 1) Default variables
        //    (order_notification)
        // =======================

        $nameVar    = $consumerName;  // {{1}}
        $statusVar  = $orderStatus;   // {{3}}
        $detailsVar = '';             // {{4}}

        switch ($this->roleName) {
            case RoleEnum::CONSUMER:
                if ($paymentStatus === PaymentStatus::PENDING) {
                    $detailsVar = strtolower($paymentMethod) === 'bank_transfer'
                        ? 'Please complete the bank transfer. We will process your order once payment is confirmed.'
                        : 'Your order is on hold until we confirm that payment has been received.';
                } else {
                    $detailsVar = "Thank you for your order of {$totalAmount}. We'll keep you updated as it progresses.";
                }
                break;

            case RoleEnum::VENDOR:
                $nameVar   = 'Vendor';
                $statusVar = $orderStatus;
                $itemCount = count($order->products);

                $detailsVar = "New order received. Items: {$itemCount}, Amount: {$totalAmount}. "
                    . "Please prepare the order for shipment/collection.";
                break;

            case RoleEnum::ADMIN:
                $nameVar   = 'Admin';
                $statusVar = $orderStatus;

                $detailsVar = "New order placed by {$consumerName}. "
                    . "Amount: {$totalAmount}, payment via {$paymentMethod}. "
                    . "Please review it in the admin dashboard.";
                break;
        }

        // =======================
        // 2) Common data
        // =======================

        // Image that appears in the header_1 for the default template
        $imageUrl = config('services.msg91.order_header_image')
            ?? (config('app.url') . '/images/whatsapp/order-header.png');

        // "Manage Order" button URL
        // For customers: use frontend URL
        // For admins: use admin panel URL
        if ($notifiable->hasRole('admin') || $notifiable->hasRole('super-admin')) {
            // Admin users get link to admin panel
            $manageOrderUrl = route('admin.orders.show', $orderNumber);
        } else {
            // Customers get link to frontend order page
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $manageOrderUrl = rtrim($frontendUrl, '/') . "/en/account/orders/{$orderNumber}";
        }

        // Recipient number
        $phone = ltrim($notifiable->phone ?? '', '+');

        // Base language + template values (will override in cases)
        $templateName = 'order_notification';
        $language = [
            'code'   => 'en',
            'policy' => 'deterministic',
        ];

        // Default components for order_notification (with header image)
        $components = [
            'header_1' => [
                'type'  => 'image',
                'value' => $imageUrl,
            ],
            'body_1' => [
                'type'  => 'text',
                'value' => $nameVar,       // {{1}}
            ],
            'body_2' => [
                'type'  => 'text',
                'value' => $orderNumber,   // {{2}}
            ],
            'body_3' => [
                'type'  => 'text',
                'value' => $statusVar,     // {{3}}
            ],
            'body_4' => [
                'type'  => 'text',
                'value' => $detailsVar,    // {{4}}
            ],
            'button_1' => [
                'subtype' => 'url',
                'type'    => 'text',
                'value'   => $manageOrderUrl,
            ],
        ];

        // ==========================================
        // 3) Override template for CONSUMER status
        // ==========================================

        if ($this->roleName === RoleEnum::CONSUMER) {

            // --- READY FOR COLLECTION ---
            if ($orderStatusLc === 'ready for collection') {

                $templateName     = 'order_ready_collection';
                $language['code'] = 'en_GB';

                // Map variables:
                // {{1}} = name
                // {{2}} = order number
                // {{3}} = collection location
                // {{4}} = collection hours

                // TODO: replace with your actual fields
                $collectionLocation = $order->collection_location
                    ?? 'Raines Africa Collection Point';
                $collectionHours = $order->collection_hours
                    ?? 'Mon–Sat, 08:00–17:00';

                $components = [
                    'body_1' => [
                        'type'  => 'text',
                        'value' => $consumerName,     // {{1}}
                    ],
                    'body_2' => [
                        'type'  => 'text',
                        'value' => $orderNumber,      // {{2}}
                    ],
                    'body_3' => [
                        'type'  => 'text',
                        'value' => $collectionLocation, // {{3}}
                    ],
                    'body_4' => [
                        'type'  => 'text',
                        'value' => $collectionHours,  // {{4}}
                    ],
                    'button_1' => [
                        'subtype' => 'url',
                        'type'    => 'text',
                        'value'   => $manageOrderUrl,
                    ],
                ];

                // --- OUT FOR DELIVERY ---
            } elseif ($orderStatusLc === 'out for delivery') {

                $templateName     = 'order_out_for_delivery';
                $language['code'] = 'en';

                // Map variables:
                // {{1}} = name
                // {{2}} = order number
                // {{3}} = current status text
                // {{4}} = ETA / delivery time text

                // TODO: replace with your actual ETA field
                $eta = $order->expected_delivery_at
                    ? $order->expected_delivery_at->format('d M Y H:i')
                    : ($order->delivery_eta ?? 'the end of the day');

                $components = [
                    'body_1' => [
                        'type'  => 'text',
                        'value' => $consumerName,   // {{1}}
                    ],
                    'body_2' => [
                        'type'  => 'text',
                        'value' => $orderNumber,    // {{2}}
                    ],
                    'body_3' => [
                        'type'  => 'text',
                        'value' => $orderStatus,    // {{3}} (current status)
                    ],
                    'body_4' => [
                        'type'  => 'text',
                        'value' => $eta,            // {{4}} (delivery before ...)
                    ],
                    'button_1' => [
                        'subtype' => 'url',
                        'type'    => 'text',
                        'value'   => $manageOrderUrl,
                    ],
                ];
            }
            // else → keep default order_notification
        }

        // =======================
        // 4) Final MSG91 payload
        // =======================

        return [
            'integrated_number' => config('services.msg91.integrated_number'),
            'content_type'      => 'template',
            'payload'           => [
                'messaging_product' => 'whatsapp',
                'type'              => 'template',
                'template'          => [
                    'name'      => $templateName,
                    'language'  => $language,
                    'namespace' => config('services.msg91.namespace'),
                    'to_and_components' => [
                        [
                            'to'         => [$phone],
                            'components' => $components,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function toWhatsApp_(object $notifiable)
    {
        // Load order data
        $order = $this->order->loadMissing(['products', 'consumer', 'order_status']);

        $orderNumber = $order->order_number;
        $totalAmount = $order->currency_symbol . ' ' . number_format($order->total * $order->exchange_rate, 2);
        $orderStatus = $order->order_status->name ?? 'Pending';
        $consumerName = $order->consumer->name ?? 'Customer';
        $paymentStatus = $order->payment_status ?? 'Pending';
        $paymentMethod = $order->payment_method ?? 'N/A';

        // Build message based on role using flexible template
        switch($this->roleName) {
            case RoleEnum::CONSUMER:
                $mainMessage = "Thank you for your order! Amount: {$totalAmount} Status: {$orderStatus}";
                $additionalInfo = "We'll notify you when your order is ready";

                // Customize for pending payment
                if ($paymentStatus === PaymentStatus::PENDING) {
                    $additionalInfo = strtolower($paymentMethod) === 'bank_transfer'
                        ? "Please complete the bank transfer. We'll process your order once payment is confirmed"
                        : "Your order is on-hold until we confirm payment has been received";
                }

                return [
                    'template_id' => config('services.msg91.templates.order_notification'),
                    'variables' => [
                        $consumerName,      // {{1}}
                        $orderNumber,       // {{2}}
                        $mainMessage,       // {{3}}
                        $additionalInfo     // {{4}}
                    ]
                ];

            case RoleEnum::VENDOR:
                // Vendor gets order notification
                $itemCount = count($order->products);
                $mainMessage = "New order received! 🔔 Items: {$itemCount} Amount: {$totalAmount}";
                $additionalInfo = "Please prepare the order for shipment/collection. View details in your vendor dashboard";

                return [
                    'template_id' => config('services.msg91.templates.order_notification'),
                    'variables' => [
                        'Vendor',           // {{1}}
                        $orderNumber,       // {{2}}
                        $mainMessage,       // {{3}}
                        $additionalInfo     // {{4}}
                    ]
                ];

            case RoleEnum::ADMIN:
                // Admin gets order notification
                $mainMessage = "New order placed! Customer: {$consumerName} Amount: {$totalAmount} Payment: {$paymentMethod}";
                $additionalInfo = "View details in admin dashboard";

                return [
                    'template_id' => config('services.msg91.templates.order_notification'),
                    'variables' => [
                        'Admin',            // {{1}}
                        $orderNumber,       // {{2}}
                        $mainMessage,       // {{3}}
                        $additionalInfo     // {{4}}
                    ]
                ];
        }

        return null;
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
        return ['notify:place-order', 'order:'.$this->order->order_number];
    }

}
