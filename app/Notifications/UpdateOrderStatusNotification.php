<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $order;
    private $qrCodePngData = null;

    /**
     * The name of the queue on which to place the notification.
     */
    //public $queue = 'emails';


    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Skip email for "arrived_at_local_branch" status (Requirement #4)
        $orderStatus = $this->order->order_status;
        $statusSlug = $orderStatus ? strtolower(trim($orderStatus->slug ?? $orderStatus->name ?? '')) : '';

        if ($statusSlug === 'arrived-at-local-branch' || $statusSlug === 'arrived at local branch') {
            // Only send database notification, no email
            return ['database'];
        }

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
        $order = $this->order->loadMissing(['products','sub_orders.products','billing_address','shipping_address','order_status','consumer']);

        $statusRaw = (string) optional($order->order_status)->name;
        $nameNorm = strtolower(trim($statusRaw));
        $nameNorm = preg_replace('/\s+/', ' ', $nameNorm);

        $frontBase = rtrim((string) (env('FRONTEND_URL', config('app.url'))), '/');
        $manageUrl = $frontBase.'/en/account/order/details/'.$order->order_number;

        // Use APP_URL for backend/admin URLs - this changes automatically between local and production
        $backendBase = rtrim((string) (env('APP_URL', config('app.url'))), '/');
        $adminOrderUrl = $backendBase.'/en/order/details/'.$order->order_number;

        $subject = '';
        $heading = config('app.name').' Order Status Updated';
        $intro = "Your order status has been updated to {$statusRaw}.";
        $ctaUrl = $manageUrl;
        $ctaLabel = 'Manage Order';
        $qrUrl = null;
        $qrNote = null;

        switch ($nameNorm) {
            case 'processing':
                $subject = "Payment received for order #{$order->order_number}";
                $heading = "We're processing your order";
                $intro = "Thank you — we have received your payment for order #{$order->order_number}.\n\nYour order is not ready yet. We'll now begin to process your order.\n\nWe'll email you the moment it's ready.";
                break;

            case 'out for delivery':
                $subject = "Order #{$order->order_number} is out for delivery";
                $heading = 'Your order is on the way';
                $intro = "Your order #{$order->order_number} is out for delivery and scheduled to arrive today before 5:00 PM. If there are any delays or issues, we will notify you promptly.\n\nPlease have the QR code below ready to present to our delivery partner upon arrival.";

                // Generate QR code data - JSON format for scanner compatibility
                $qrData = json_encode([
                    'type' => 'order_delivery',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'auto_collect' => false,
                    'customer_name' => $order->consumer->name ?? 'Customer',
                    'timestamp' => now()->timestamp,
                ], JSON_UNESCAPED_SLASHES);

                // Generate QR code PNG - get raw string
                $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($qrData);

                // Ensure it's a plain string, not HtmlString
                $this->qrCodePngData = (string) $qrCodePng;

                // Upload to laravel-media and get URL
                $qrCodeService = app(\App\Services\OrderQRCodeService::class);
                $qrUrl = $qrCodeService->generateAndSave($order->order_number, $qrData, 'delivery', $this->qrCodePngData);

                // If upload failed, use CID attachment
                if (!$qrUrl) {
                    $qrUrl = 'cid:order_qr_code';
                }

                $qrNote = 'Scan to verify and open order details';
                break;

            case 'delivered':
                $subject = "Order #{$order->order_number} delivered";
                $heading = 'Thank you for your purchase';
                $intro = "Good news — your order #{$order->order_number} has been delivered.\nThank you for choosing " . config('app.name') . ". If anything isn't quite right, please get in touch and our team will assist.";
                break;

            case 'ready for collection':
                $subject = "Order #{$order->order_number} is ready for collection";
                $heading = 'Ready for collection';
                $location_ = str_replace('Free Collection - ', '', trim((string) $order->delivery_description));
                $location = str_replace('- (3-7 Business Days)', '', trim((string) $location_));
                $intro = "Your order #{$order->order_number} is ready for collection.\nYou can pick up your items from" . ($location ? " {$location}." : ' the designated collection point.') . "\nPlease present the QR code below when collecting your items.";

                // Generate QR code data - JSON format for scanner compatibility
                $qrData = json_encode([
                    'type' => 'order_collection',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'auto_collect' => true,
                    'customer_name' => $order->consumer->name ?? 'Customer',
                    'timestamp' => now()->timestamp,
                ], JSON_UNESCAPED_SLASHES);

                // Generate QR code PNG - get raw string
                $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate($qrData);

                // Ensure it's a plain string, not HtmlString
                $this->qrCodePngData = (string) $qrCodePng;

                // Upload to laravel-media and get URL
                $qrCodeService = app(\App\Services\OrderQRCodeService::class);
                $qrUrl = $qrCodeService->generateAndSave($order->order_number, $qrData, 'collection', $this->qrCodePngData);

                // If upload failed, use CID attachment
                if (!$qrUrl) {
                    $qrUrl = 'cid:order_qr_code';
                }

                $qrNote = 'Scan to open order and mark as collected';
                break;

            case 'collected':
                $subject = "Order #{$order->order_number} collected";
                $heading = 'Order collected';
                $location_ = str_replace('Free Collection - ', '', trim((string) $order->delivery_description));
                $location = str_replace('- (3-7 Business Days)', '', trim((string) $location_));
                $time = now()->format('Y-m-d H:i');
                $intro = "Your order #{$order->order_number} has been collected on {$time} at" . ($location ? " {$location}." : ' the collection point.');
                break;

            case 'cancelled':
                $subject = "Order #{$order->order_number} has been cancelled";
                $heading = 'Order cancelled';
                $intro = "Your order #{$order->order_number} has been cancelled.";
                break;

            default:
                $stat = $statusRaw ?: 'updated';
                $subject = "Order #{$order->order_number} status: {$stat}";
                break;
        }

        // Append extra paragraph for select statuses only
        // Exclude: collected, ready for collection, delivered, out for delivery, processing, cancelled
        $statusesNoNote = ['collected','ready for collection','delivered','out for delivery','processing','cancelled'];
        if (!in_array($nameNorm, $statusesNoNote, true)) {
            $intro = trim(($intro ?? '') . "\n\nNote: Your order is still not ready.");
        }

        // Convert order amounts from USD to display currency before passing to template
        $order = $this->convertOrderAmounts($order);

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$order->consumer->name},")
            ->view('emails.order-notification', [
                'subject'   => $subject,
                'heading'   => $heading,
                'intro'     => $intro,
                'order'     => $order,
                'cta_url'   => $ctaUrl,
                'cta_label' => $ctaLabel,
                'qr_url'    => $qrUrl,
                'qr_note'   => $qrNote,
            ]);

        // ALSO attach QR code as embedded image for better email client compatibility
        if ($this->qrCodePngData) {
            $qrCodeData = $this->qrCodePngData;

            $mailMessage->withSymfonyMessage(function ($message) use ($qrCodeData) {
                $message->embed($qrCodeData, 'order_qr_code', 'image/png');
            });
        }

        return $mailMessage;
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
        // Build a Slack message using the new Block Kit based API
        $order = $this->order->loadMissing([
            'order_status', 'consumer', 'products'
        ]);

        $symbol = (string) ($order->currency_symbol ?? '');
        $code   = (string) ($order->currency ?? '');
        $rate   = (float) ($order->exchange_rate ?? 1);

        // Totals
        $itemsSubtotal = 0.0;
        foreach ($order->products as $p) {
            $unit = $p->pivot->single_price ?? $p->price;
            $itemsSubtotal += ((float) $unit) * ((int) $p->pivot->quantity);
        }
        $shipping = (float) ($order->shipping_total ?? 0.0);
        $delivery = (float) ($order->delivery_price ?? 0.0);
        $tax      = (float) ($order->tax_total ?? 0.0);
        $discount = (float) ($order->coupon_total_discount ?? 0.0);
        $grand    = (float) ($order->total ?? ($itemsSubtotal + $shipping + $delivery + $tax - $discount));

        // Convert for display
        $itemsSubtotal *= $rate;
        $shipping      *= $rate;
        $delivery      *= $rate;
        $tax           *= $rate;
        $discount      *= $rate;
        $grand         *= $rate;

        // Sections as multi-line strings for markdown section blocks
        $orderInfo = "*Order Number:* #{$order->order_number}\n"
            ."*Order Status:* ".((string) ($order->order_status->name ?? 'n/a'))."\n"
            ."*Payment Status:* ".((string) ($order->payment_status ?? 'n/a'))."\n"
            ."*Payment Method:* ".(strtoupper((string) ($order->payment_method ?? 'n/a')))."\n"
            .(!empty($order->delivery_description) ? "*Delivery Method:* {$order->delivery_description}\n" : '')
            ."*Placed At:* ".((string) optional($order->created_at)->format('Y-m-d H:i'))."\n"
            ."*Updated:* ".((string) optional($order->updated_at)->format('Y-m-d H:i'));

        // Items
        $itemsLines = [];
        foreach ($order->products as $p) {
            $name = (string) ($p->name ?? 'Item');
            $qty  = (int) ($p->pivot->quantity ?? 1);
            $unit = (float) ($p->pivot->single_price ?? $p->price ?? 0.0);
            $line = $name.' × '.$qty.' — '.$symbol.number_format(($unit * $qty) * $rate, 2, '.', ',').($code ? (' '.$code) : '');
            $itemsLines[] = $line;
        }
        if (empty($itemsLines)) {
            $itemsLines[] = 'No items found.';
        }
        $itemsText = implode("\n", $itemsLines);

        // Summary
        $summaryText = "*Items Subtotal:* {$symbol}".number_format($itemsSubtotal, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Shipping:* {$symbol}".number_format($shipping, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Delivery:* {$symbol}".number_format($delivery, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Tax:* {$symbol}".number_format($tax, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Discount:* - {$symbol}".number_format($discount, 2, '.', ',').($code ? (' '.$code) : '')."\n"
            ."*Grand Total:* {$symbol}".number_format($grand, 2, '.', ',').($code ? (' '.$code) : '');

        $message = (new SlackMessage())
            ->text(':memo: Order Status Updated')
            ->headerBlock('Order #'.$order->order_number)
            ->sectionBlock(function ($section) use ($order, $symbol, $code, $grand) {
                $section->field("*Status:*\n".((string) ($order->order_status->name ?? 'n/a')))->markdown();
                $section->field("*Payment:*\n".((string) ($order->payment_status ?? 'n/a')))->markdown();
                $section->field("*Total:*\n".$symbol.number_format((float) $grand, 2, '.', ',').($code ? (' '.$code) : ''))->markdown();
                $section->field("*Consumer:*\n".((string) ($order->consumer->name ?? 'Guest')))->markdown();
                $section->field("*Placed At:*\n".((string) (optional($order->created_at)->format('Y-m-d H:i') ?? now())))->markdown();
            })
            ->dividerBlock()
            ->headerBlock('Order Information')
            ->sectionBlock(function ($section) use ($orderInfo) {
                $section->text($orderInfo)->markdown();
            })
            ->dividerBlock()
            ->headerBlock('Order Items')
            ->sectionBlock(function ($section) use ($itemsText) {
                $section->text($itemsText)->markdown();
            })
            ->dividerBlock()
            ->headerBlock('Order Summary')
            ->sectionBlock(function ($section) use ($summaryText) {
                $section->text($summaryText)->markdown();
            });

        return $message;
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable)
    {
        $order = $this->order->loadMissing(['products', 'consumer', 'order_status']);

        $statusRaw = (string) optional($order->order_status)->name;
        $nameNorm = strtolower(trim(preg_replace('/\s+/', ' ', $statusRaw)));

        $customerName = $order->consumer->name ?? 'Customer';
        $orderNumber = $order->order_number;
        $total = $order->currency_symbol . ' ' . number_format($order->total * $order->exchange_rate, 2);

        // Get collection location if available
        $location = str_replace(['Free Collection - ', '- (3-7 Business Days)'], '', trim((string) $order->delivery_description));

        // Get product image for header (use first product image or default)
        $headerImage = null;
        if ($order->products->isNotEmpty() && $order->products->first()->featured_image) {
            $headerImage = $order->products->first()->featured_image;
        }

        // Build template data based on status
        $templateId = 'order_notification';  // Default template
        $variables = [];
        $options = [];
        switch ($nameNorm) {
            case 'out for delivery':
                // Use order_notification template (the only one that exists)
                $eta = $order->expected_delivery_at
                    ? $order->expected_delivery_at->format('d M Y H:i')
                    : ($order->delivery_eta ?? 'the end of the day');

                $variables = [
                    $customerName,          // body_1
                    $orderNumber,           // body_2
                    "Out for Delivery",     // body_3 - Status
                    "Expected: {$eta}"      // body_4 - ETA
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;

            case 'ready for collection':
                // Use order_notification template (the only one that exists)
                $collectionLocation = $location ?: 'Raines Africa Collection Point';
                $collectionHours = $order->collection_hours ?? 'Mon–Sat, 08:00–17:00';

                // Map collection locations to Google Maps directions URLs
                $locationMaps = [
                    'harare' => 'https://maps.google.com/?q=32+Shop+6,+Raines+Africa+Harare+Greendale,+Rhodesville+Shopping+Centre',
                    'bulawayo' => 'https://maps.google.com/?q=90+George+Silundika+Street,+Bulawayo',
                    'zambia' => 'https://maps.google.com/?q=Raines+Africa+Lusaka,+Niyati+Plaza',
                    'lusaka' => 'https://maps.google.com/?q=Raines+Africa+Lusaka,+Niyati+Plaza',
                    'mutare' => 'https://maps.google.com/?q=Raines+Africa+Mutare,+Robert+Mugabe+Road',
                ];

                // Determine which location based on delivery description or location string
                $locationLower = strtolower($collectionLocation);
                $mapsUrl = 'https://example.com'; // Default fallback

                if (str_contains($locationLower, 'harare') || str_contains($locationLower, 'greendale') || str_contains($locationLower, 'rhodesville')) {
                    $mapsUrl = $locationMaps['harare'];
                } elseif (str_contains($locationLower, 'bulawayo') || str_contains($locationLower, 'george silundika')) {
                    $mapsUrl = $locationMaps['bulawayo'];
                } elseif (str_contains($locationLower, 'zambia') || str_contains($locationLower, 'lusaka') || str_contains($locationLower, 'niyati')) {
                    $mapsUrl = $locationMaps['zambia'];
                } elseif (str_contains($locationLower, 'mutare') || str_contains($locationLower, 'twin towers') || str_contains($locationLower, 'robert mugabe')) {
                    $mapsUrl = $locationMaps['mutare'];
                }

                $variables = [
                    $customerName,                                                    // body_1
                    $orderNumber,                                                      // body_2
                    "Ready for Collection at {$collectionLocation}",                  // body_3 - Status + location
                    "Collection Hours: {$collectionHours}"                            // body_4 - Collection hours
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => $mapsUrl  // Get Directions button URL
                ];
                break;

            case 'processing':
                $variables = [
                    $customerName,                                // body_1
                    $orderNumber,                                 // body_2
                    "We're processing your order",                // body_3
                    "You'll be notified when it's ready"         // body_4
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;

            case 'delivered':
                $variables = [
                    $customerName,                                // body_1
                    $orderNumber,                                 // body_2
                    'Your order has been delivered successfully', // body_3
                    'Thank you for choosing Raines Africa'        // body_4
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;

            case 'collected':
                $variables = [
                    $customerName,                                // body_1
                    $orderNumber,                                 // body_2
                    'Thank you for collecting your order',        // body_3
                    'We hope to serve you again soon'             // body_4
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;

            case 'cancelled':
                $variables = [
                    $customerName,                                // body_1
                    $orderNumber,                                 // body_2
                    'Your order has been cancelled',              // body_3
                    'Contact us if you have any questions'        // body_4
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;

            default:
                // Generic status update using order_notification template
                $variables = [
                    $customerName,                      // body_1
                    $orderNumber,                       // body_2
                    "Status: {$statusRaw}",            // body_3
                    'Check your account for details'   // body_4
                ];
                $options = [
                    'language' => 'en',
                    'button_url' => 'https://example.com'
                ];
                break;
        }

        return [
            'template_id' => $templateId,
            'variables' => $variables,
            'options' => $options
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        //for consumer
        return [
            'title' => "Order status updated!",
            'message' => "Order Update: Your order #{$this->order->order_number} has been updated and current order status is in {$this->order->order_status->name}. Thank you for your patience!",
            'type' => "order"
        ];
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
        return ['notify:update-order-status', 'order:'.$this->order->order_number];
    }
}
